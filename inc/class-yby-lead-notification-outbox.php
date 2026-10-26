<?php
/** Durable, per-lead email transport outbox. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Email transport receipt outbox. SMTP accepted does not imply inbox delivered.
 * Processing rows are intentionally never auto-reclaimed: after a process crash,
 * the provider may already have accepted the email. Human reconciliation required.
 */
class YBY_Lead_Notification_Outbox {
    const MAX_ATTEMPTS = 4;
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'yby_lead_notifications';
    }
    public static function enqueue_and_attempt( $lead, $result ) {
        global $wpdb;
        $lead_id = isset( $result['lead_id'] ) ? (int) $result['lead_id'] : 0;
        $case_id = isset( $result['case_id'] ) ? (string) $result['case_id'] : '';
        if ( ! self::enqueue( $lead_id, $case_id ) ) {
            // The lead is already persisted; record the failure independently,
            // and never attempt SMTP without a durable outbox claim.
            $logged = $wpdb->insert( YBY_Database::activities_table_name(), array(
                'lead_id' => $lead_id, 'activity_type' => 'email_outbox_unavailable',
                'content' => 'outbox_write_failed', 'new_value' => 'queue_unavailable',
                'created_by' => 0, 'created_at' => current_time( 'mysql' ),
            ), array( '%d', '%s', '%s', '%s', '%d', '%s' ) );
            if ( false === $logged ) {
                error_log( 'YBY lead notification fallback audit failed for lead ' . $lead_id );
            }
            if ( ! wp_schedule_single_event( time() + 300, 'yby_lead_notification_recover', array( $lead_id, 1 ) ) ) {
                error_log( 'YBY lead notification recovery scheduling failed for lead ' . $lead_id );
            }
            error_log( 'YBY lead notification outbox unavailable for lead ' . $lead_id );
            return array( 'status' => 'queue_unavailable', 'error_code' => 'outbox_write_failed' );
        }
        return self::attempt( $lead, $result );
    }
    public static function enqueue( $lead_id, $case_id ) {
        global $wpdb;
        if ( $lead_id < 1 || ! YBY_Database::notification_table_exists() ) { return false; }
        $now = current_time( 'mysql' );
        $created = $wpdb->insert( self::table_name(), array(
            'lead_id' => $lead_id, 'case_id' => $case_id,
            'recipient_email' => (string) YBY_Config::get_lead_notification_primary_recipient_email(), 'status' => 'pending',
            'attempts' => 0, 'created_at' => $now, 'updated_at' => $now,
        ), array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' ) );
        if ( false !== $created ) { return true; }
        return (int) $wpdb->get_var( $wpdb->prepare(
            'SELECT lead_id FROM ' . self::table_name() . ' WHERE lead_id = %d', $lead_id
        ) ) === $lead_id;
    }
    public static function attempt( $lead, $result ) {
        global $wpdb;
        $lead_id = isset( $result['lead_id'] ) ? (int) $result['lead_id'] : 0;
        $now = current_time( 'mysql' );
        $claim = $wpdb->query( $wpdb->prepare(
            "UPDATE " . self::table_name() . " SET status = 'processing', attempts = attempts + 1,
             recipient_email = %s,
             claimed_at = %s, last_attempt_at = %s, updated_at = %s
             WHERE lead_id = %d AND attempts < %d AND status IN ('pending','failed')
             AND (next_retry_at IS NULL OR next_retry_at <= %s)",
            (string) YBY_Config::get_lead_notification_primary_recipient_email(), $now, $now, $now, $lead_id, self::MAX_ATTEMPTS, $now
        ) );
        if ( 1 !== (int) $claim ) {
            return array( 'status' => self::status( $lead_id ), 'error_code' => 'not_claimed' );
        }
        try {
            // Rendering/normalizing the payload can itself fail. Keep it inside
            // the guarded attempt so a pre-SMTP exception is durably recorded.
            $payload = self::build_payload( $lead, $result );
            $mail = ( new YBY_Notification_Manager() )->sendLeadNotification( $payload );
        } catch ( \Throwable $throwable ) {
            $mail = array( 'mail_sent' => false, 'mail_error_code' => 'notification_exception' );
        }
        $accepted = is_array( $mail ) && ! empty( $mail['mail_sent'] );
        $error = $accepted ? '' : sanitize_key( (string) ( $mail['mail_error_code'] ?? '' ) );
        if ( ! $accepted && '' === $error ) { $error = 'notification_unknown_failure'; }
        $attempts = (int) $wpdb->get_var( $wpdb->prepare(
            'SELECT attempts FROM ' . self::table_name() . ' WHERE lead_id = %d', $lead_id
        ) );
        $retry = ! $accepted && $attempts < self::MAX_ATTEMPTS;
        $delay = min( 3600, 60 * (int) pow( 3, max( 0, $attempts - 1 ) ) );
        $next = $retry ? wp_date( 'Y-m-d H:i:s', time() + $delay ) : null;
        $status = $accepted ? 'smtp_accepted' : ( $retry ? 'failed' : 'exhausted' );
        $written = $wpdb->update( self::table_name(), array(
            'status' => $status, 'last_error_code' => $error,
            'next_retry_at' => $next, 'accepted_at' => $accepted ? $now : null,
            'claimed_at' => null, 'updated_at' => $now,
        ), array( 'lead_id' => $lead_id, 'status' => 'processing' ),
        array( '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d', '%s' ) );
        if ( 1 !== (int) $written ) {
            // Do not blindly retry: the SMTP call may already have been accepted.
            error_log( 'YBY lead mail receipt update failed for lead ' . $lead_id );
            return array( 'status' => 'processing', 'error_code' => 'receipt_write_failed' );
        }
        $wpdb->insert( YBY_Database::activities_table_name(), array(
            'lead_id' => $lead_id, 'activity_type' => 'email_notification',
            'content' => $error, 'new_value' => $status,
            'created_by' => 0, 'created_at' => $now,
        ), array( '%d', '%s', '%s', '%s', '%d', '%s' ) );
        if ( $retry && ! wp_schedule_single_event( time() + $delay, 'yby_lead_notification_retry', array( $lead_id ) ) ) {
            error_log( 'YBY lead notification retry scheduling failed for lead ' . $lead_id );
        }
        do_action( 'yby_lead_notification_result', is_array( $mail ) ? $mail : array(), $lead_id, (string) ( $result['case_id'] ?? '' ) );
        return array( 'status' => $status, 'error_code' => $error );
    }
    public static function status( $lead_id ) {
        global $wpdb;
        $value = $wpdb->get_var( $wpdb->prepare(
            'SELECT status FROM ' . self::table_name() . ' WHERE lead_id = %d', (int) $lead_id
        ) );
        return is_string( $value ) && '' !== $value ? $value : 'queue_unavailable';
    }
    /** Recover a saved lead whose initial outbox insert failed; no mail was sent. */
    public static function recover( $lead_id, $round = 1 ) {
        global $wpdb;
        $lead_id = (int) $lead_id;
        $round = (int) $round;
        if ( $lead_id < 1 || $round < 1 || $round > self::MAX_ATTEMPTS ) { return; }
        $lead = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . YBY_Database::leads_table_name() . ' WHERE id = %d', $lead_id
        ), ARRAY_A );
        if ( ! is_array( $lead ) ) {
            error_log( 'YBY outbox recovery missing lead ' . $lead_id );
            return;
        }
        if ( ! self::enqueue( $lead_id, (string) $lead['case_id'] ) ) {
            if ( $round < self::MAX_ATTEMPTS ) {
                $delay = min( 3600, 300 * $round );
                if ( ! wp_schedule_single_event( time() + $delay, 'yby_lead_notification_recover', array( $lead_id, $round + 1 ) ) ) {
                    error_log( 'YBY outbox recovery reschedule failed for lead ' . $lead_id );
                }
            } else {
                error_log( 'YBY outbox recovery exhausted for lead ' . $lead_id );
            }
            return;
        }
        $lead['custom_fields'] = json_decode( (string) ( $lead['custom_fields'] ?? '{}' ), true );
        if ( ! is_array( $lead['custom_fields'] ) ) { $lead['custom_fields'] = array(); }
        self::attempt( $lead, array( 'lead_id' => $lead_id,
            'case_id' => $lead['case_id'], 'status' => $lead['status'] ) );
    }

    public static function retry( $lead_id ) {
        global $wpdb;
        $lead_id = (int) $lead_id;
        if ( $lead_id < 1 ) { return; }
        $lead = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . YBY_Database::leads_table_name() . ' WHERE id = %d', $lead_id
        ), ARRAY_A );
        if ( ! is_array( $lead ) ) {
            error_log( 'YBY lead retry missing stored lead ' . $lead_id );
            return;
        }
        $lead['custom_fields'] = json_decode( (string) ( $lead['custom_fields'] ?? '{}' ), true );
        if ( ! is_array( $lead['custom_fields'] ) ) { $lead['custom_fields'] = array(); }
        self::attempt( $lead, array( 'lead_id' => $lead_id,
            'case_id' => $lead['case_id'], 'status' => $lead['status'] ) );
    }
    /** A bounded hourly fallback for missed one-shot WP-Cron events. */
    public static function schedule_sweep() {
        if ( ! wp_next_scheduled( 'yby_lead_notification_sweep' ) ) {
            wp_schedule_event( time() + 300, 'hourly', 'yby_lead_notification_sweep' );
        }
    }
    public static function sweep() {
        global $wpdb;
        if ( ! YBY_Database::notification_table_exists() ) { return; }
        // Include orphaned pending tasks after a five-minute grace period.
        // Manual requeue may succeed while wp_schedule_single_event() fails.
        // A row is atomically claimed by attempt(); even two workers cannot send twice.
        $grace = wp_date( 'Y-m-d H:i:s', time() - 300 );
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT lead_id FROM " . self::table_name() .
            " WHERE attempts < %d AND (
                (status = 'failed' AND next_retry_at <= %s)
                OR (status = 'pending' AND updated_at <= %s)
             ) ORDER BY updated_at ASC LIMIT 20",
            self::MAX_ATTEMPTS, current_time( 'mysql' ), $grace
        ) );
        foreach ( (array) $ids as $lead_id ) { self::retry( (int) $lead_id ); }
    }
    /**
     * Explicitly ignore a notification, never the underlying inquiry.
     * The status update and reason audit are one InnoDB transaction. The
     * atomic WHERE disallows in-flight processing or already accepted mail.
     *
     * @return bool True only when state and reason were both committed.
     */
    public static function ignore_notification( $lead_id, $reason ) {
        global $wpdb;
        $lead_id = (int) $lead_id;
        if ( ! current_user_can( 'andy_core_settings_manage' ) || $lead_id < 1 ) { return false; }
        $reason = trim( sanitize_text_field( (string) $reason ) );
        if ( '' === $reason || strlen( $reason ) > 600 ) { return false; }
        if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return false; }
        $now = current_time( 'mysql' );
        $changed = $wpdb->query( $wpdb->prepare(
            "UPDATE " . self::table_name() . " SET status = 'ignored',
             next_retry_at = NULL, claimed_at = NULL, updated_at = %s
             WHERE lead_id = %d AND status IN ('pending', 'failed', 'exhausted')",
            $now, $lead_id
        ) );
        if ( 1 !== (int) $changed ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }
        $audited = $wpdb->insert( YBY_Database::activities_table_name(), array(
            'lead_id' => $lead_id,
            'activity_type' => 'email_notification_ignored',
            'content' => $reason,
            'new_value' => 'ignored',
            'created_by' => get_current_user_id(),
            'created_at' => $now,
        ), array( '%d', '%s', '%s', '%s', '%d', '%s' ) );
        if ( false === $audited || false === $wpdb->query( 'COMMIT' ) ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( 'YBY notification ignore audit failed for lead ' . $lead_id );
            return false;
        }
        // Remove only this lead's retry events. A concurrently loaded callback
        // will still fail the atomic status claim and cannot send the email.
        wp_clear_scheduled_hook( 'yby_lead_notification_retry', array( $lead_id ) );
        return true;
    }

    /** Audit reason remains visible without repurposing SMTP error fields. */
    public static function ignored_reason( $lead_id ) {
        global $wpdb;
        return (string) $wpdb->get_var( $wpdb->prepare(
            'SELECT content FROM ' . YBY_Database::activities_table_name() .
            ' WHERE lead_id = %d AND activity_type = %s ORDER BY id DESC LIMIT 1',
            (int) $lead_id, 'email_notification_ignored'
        ) );
    }

    /** Schedule an explicit resend only for a confirmed failed/exhausted receipt. */
    public static function request_manual_retry( $lead_id ) {
        global $wpdb;
        if ( ! current_user_can( 'andy_core_settings_manage' ) || $lead_id < 1 ) { return false; }
        $changed = $wpdb->query( $wpdb->prepare(
            "UPDATE " . self::table_name() . " SET status = 'pending',
             attempts = 0, next_retry_at = NULL, claimed_at = NULL,
             last_error_code = '', updated_at = %s
             WHERE lead_id = %d AND status IN ('failed', 'exhausted')",
            current_time( 'mysql' ), $lead_id
        ) );
        if ( 1 !== (int) $changed ) { return false; }
        if ( ! wp_schedule_single_event( time() + 10, 'yby_lead_notification_retry', array( $lead_id ) ) ) {
            error_log( 'YBY manual notification retry scheduling failed for lead ' . $lead_id );
            return false;
        }
        return true;
    }
    public static function list_recent( $status = '', $limit = 100 ) {
        global $wpdb;
        if ( ! YBY_Database::notification_table_exists() ) { return array(); }
        $allowed = array( 'pending', 'processing', 'smtp_accepted', 'failed', 'exhausted' );
        $limit = max( 1, min( 100, (int) $limit ) );
        if ( in_array( $status, $allowed, true ) ) {
            return (array) $wpdb->get_results( $wpdb->prepare(
                'SELECT * FROM ' . self::table_name() . ' WHERE status = %s ORDER BY id DESC LIMIT %d',
                $status, $limit
            ), ARRAY_A );
        }
        return (array) $wpdb->get_results( $wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . ' ORDER BY id DESC LIMIT %d', $limit
        ), ARRAY_A );
    }
    /** Missing outbox receipts previously recorded in the separate lead activity audit. */
    public static function unqueued_count() {
        global $wpdb;
        if ( ! YBY_Database::notification_table_exists() ) { return 0; }
        return (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT a.lead_id) FROM " . YBY_Database::activities_table_name() . " a
             LEFT JOIN " . self::table_name() . " n ON n.lead_id = a.lead_id
             WHERE a.activity_type = 'email_outbox_unavailable' AND n.lead_id IS NULL"
        );
    }
    public static function actionable_count() {
        global $wpdb;
        if ( ! YBY_Database::notification_table_exists() ) { return 0; }
        // Stale pending must also trigger an operator warning if cron is stalled.
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::table_name() .
            " WHERE status IN ('failed','exhausted','processing')
               OR (status = 'pending' AND updated_at <= %s)",
            wp_date( 'Y-m-d H:i:s', time() - 300 )
        ) );
    }
    protected static function build_payload( $lead, $result ) {
        $mapper = new YBY_Inquiry_Lead_Mapper();
        $fields = isset( $lead['custom_fields'] ) && is_array( $lead['custom_fields'] ) ? $lead['custom_fields'] : array();
        $phone = preg_replace( '/[^\d+]/', '', (string) ( $lead['whatsapp'] ?? '' ) );
        return array_merge( $lead, array(
            'lead_id' => (int) $result['lead_id'],
            'case_id' => (string) $result['case_id'],
            'status' => (string) $result['status'],
            'custom_fields' => $fields,
            'custom_fields_json' => $mapper->build_custom_fields_json( $fields ),
            'contact_email' => (string) ( $lead['email'] ?? '' ),
            'contact_whatsapp_url' => '' === $phone ? '' : 'https://wa.me/' . rawurlencode( ltrim( $phone, '+' ) ),
            'landing_page_url' => (string) ( $lead['source_url'] ?? '' ),
        ) );
    }
}
add_action( 'yby_lead_notification_retry', array( 'YBY_Lead_Notification_Outbox', 'retry' ), 10, 1 );
add_action( 'yby_lead_notification_recover', array( 'YBY_Lead_Notification_Outbox', 'recover' ), 10, 2 );
add_action( 'init', array( 'YBY_Lead_Notification_Outbox', 'schedule_sweep' ) );
add_action( 'yby_lead_notification_sweep', array( 'YBY_Lead_Notification_Outbox', 'sweep' ) );
