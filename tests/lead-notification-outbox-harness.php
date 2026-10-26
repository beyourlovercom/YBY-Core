<?php
/** Isolated, offline outbox regression: no WordPress, SMTP, or real messages. */
error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
$GLOBALS['mail_success'] = false;
$GLOBALS['sent_count'] = 0;
$GLOBALS['cron_events'] = array();
$GLOBALS['hooks'] = array();
$GLOBALS['actions'] = array();
function add_action( $name, $callback, $priority = 10, $argc = 1 ) { $GLOBALS['hooks'][$name] = $callback; }
function do_action( $name, ...$args ) { $GLOBALS['actions'][] = array( $name, $args ); }
function current_time( $format ) { return 'mysql' === $format ? gmdate( 'Y-m-d H:i:s' ) : time(); }
function wp_date( $format, $stamp ) { return gmdate( $format, $stamp ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ); }
function wp_schedule_single_event( $when, $hook, $args ) { if ( ! empty( $GLOBALS['cron_fail'] ) ) { return false; } $GLOBALS['cron_events'][] = array( $when, $hook, $args ); return true; }
function wp_next_scheduled( $hook ) { return false; }
function wp_schedule_event( $when, $schedule, $hook ) { return true; }
function current_user_can( $cap ) { return true; }
class YBY_Config {
    public static function get_lead_notification_primary_recipient_email() { return 'ops@example.test'; }
}
class YBY_Database {
    public static function notification_table_exists() { return true; }
    public static function leads_table_name() { return 'wp_yby_leads'; }
    public static function activities_table_name() { return 'wp_yby_lead_activities'; }
}
class YBY_Inquiry_Lead_Mapper {
    public function build_custom_fields_json( $fields ) { if ( ! empty( $GLOBALS['payload_fail'] ) ) { throw new RuntimeException( 'synthetic payload exception' ); } return json_encode( $fields ); }
}
class YBY_Notification_Manager {
    public function sendLeadNotification( $payload ) {
        ++$GLOBALS['sent_count'];
        return array( 'mail_sent' => $GLOBALS['mail_success'], 'mail_error_code' => $GLOBALS['mail_success'] ? '' : 'mail_send_failed' );
    }
}
class Outbox_Fake_Db {
    public $prefix = 'wp_';
    public $insert_id = 0;
    public $receipts = array();
    public $leads = array();
    public $activities = array();
    public $allow_enqueue = true;
    public function prepare( $sql, ...$args ) {
        $i = 0;
        return preg_replace_callback( '/%[sd]/', function( $m ) use ( $args, &$i ) {
            $v = $args[$i++];
            return '%d' === $m[0] ? (string) (int) $v : "'" . str_replace( "'", "''", (string) $v ) . "'";
        }, $sql );
    }
    public function insert( $table, $data, $formats = null ) {
        if ( 'wp_yby_lead_activities' === $table ) {
            $this->activities[] = $data;
            $this->insert_id = count( $this->activities );
            return 1;
        }
        $id = (int) $data['lead_id'];
        if ( ! $this->allow_enqueue || isset( $this->receipts[$id] ) ) { return false; }
        $this->receipts[$id] = array_merge( array(
            'attempts' => 0, 'last_error_code' => '', 'next_retry_at' => null,
            'claimed_at' => null, 'accepted_at' => null,
        ), $data );
        $this->insert_id = $id;
        return 1;
    }
    private function id( $query ) {
        return preg_match( '/lead_id\s*=\s*(\d+)/i', $query, $m ) ? (int) $m[1] : 0;
    }
    public function get_var( $sql ) {
        $id = $this->id( $sql );
        if ( ! isset( $this->receipts[$id] ) ) { return null; }
        if ( stripos( $sql, 'SELECT attempts' ) !== false ) { return $this->receipts[$id]['attempts']; }
        if ( stripos( $sql, 'SELECT status' ) !== false ) { return $this->receipts[$id]['status']; }
        return $id;
    }
    public function query( $sql ) {
        $id = $this->id( $sql );
        if ( ! isset( $this->receipts[$id] ) ) { return 0; }
        $row = &$this->receipts[$id];
        if ( stripos( $sql, "status = 'processing'" ) !== false ) {
            if ( ! in_array( $row['status'], array('pending','failed'), true ) ||
                 (int) $row['attempts'] >= 4 ||
                 ( $row['next_retry_at'] && $row['next_retry_at'] > gmdate('Y-m-d H:i:s') ) ) { return 0; }
            $row['status'] = 'processing';
            $row['attempts']++;
            return 1;
        }
        if ( stripos( $sql, "status = 'pending'" ) !== false ) {
            if ( ! in_array( $row['status'], array('failed','exhausted'), true ) ) { return 0; }
            $row['status'] = 'pending';
            $row['attempts'] = 0;
            $row['next_retry_at'] = null;
            return 1;
        }
        return 0;
    }
    public function update( $table, $changes, $where, $formats = null, $where_formats = null ) {
        $id = (int) $where['lead_id'];
        if ( ! isset($this->receipts[$id]) || $this->receipts[$id]['status'] !== $where['status'] ) { return 0; }
        $this->receipts[$id] = array_merge( $this->receipts[$id], $changes );
        return 1;
    }
    public function get_row( $sql, $format ) {
        $id = preg_match( '/WHERE id\s*=\s*(\d+)/i', $sql, $m ) ? (int) $m[1] : 0;
        return $this->leads[$id] ?? null;
    }
    public function get_col( $sql ) {
        $ids = array();
        foreach ( $this->receipts as $id => $row ) {
            if ( $row['attempts'] < 4 && ( ( $row['status'] === 'failed' && $row['next_retry_at'] <= gmdate('Y-m-d H:i:s') ) || ( $row['status'] === 'pending' && $row['updated_at'] <= gmdate('Y-m-d H:i:s', time() - 300) ) ) ) { $ids[] = $id; }
        }
        return array_slice( $ids, 0, 20 );
    }
}
$wpdb = new Outbox_Fake_Db();
require_once dirname( __DIR__ ) . '/inc/class-yby-lead-notification-outbox.php';
function expect_outbox( $value, $message ) { if ( ! $value ) { throw new RuntimeException( $message ); } }
$lead = array( 'name' => 'Offline', 'email' => 'offline@example.test', 'whatsapp' => '', 'custom_fields' => array(),
    'source_url' => 'https://example.test', 'case_id' => 'C-101', 'status' => 'received' );
$wpdb->leads[101] = array_merge( $lead, array('custom_fields' => '{}') );
$result = array( 'lead_id' => 101, 'case_id' => 'C-101', 'status' => 'received' );
$first = YBY_Lead_Notification_Outbox::enqueue_and_attempt( $lead, $result );
expect_outbox( 'failed' === $first['status'], 'First failure is recorded' );
expect_outbox( 1 === $GLOBALS['sent_count'] && 1 === $wpdb->receipts[101]['attempts'], 'One attempt only' );
expect_outbox( 1 === count($GLOBALS['cron_events']), 'Failure schedules bounded retry' );
YBY_Lead_Notification_Outbox::enqueue_and_attempt( $lead, $result );
expect_outbox( 1 === $GLOBALS['sent_count'], 'Duplicate submit must not bypass retry wait' );
YBY_Lead_Notification_Outbox::retry(101);
expect_outbox( 1 === $GLOBALS['sent_count'], 'Early cron cannot bypass retry wait' );
$wpdb->receipts[101]['next_retry_at'] = gmdate('Y-m-d H:i:s', time() - 5);
$GLOBALS['mail_success'] = true;
YBY_Lead_Notification_Outbox::retry(101);
expect_outbox( 'smtp_accepted' === $wpdb->receipts[101]['status'], 'Retry acceptance stored' );
expect_outbox( 2 === $GLOBALS['sent_count'] && 2 === $wpdb->receipts[101]['attempts'], 'Two attempts total' );
YBY_Lead_Notification_Outbox::retry(101);
expect_outbox( 2 === $GLOBALS['sent_count'], 'Accepted mail never auto-resends' );
expect_outbox( ! YBY_Lead_Notification_Outbox::request_manual_retry( 101 ), 'Accepted mail cannot be resent manually' );
$wpdb->receipts[101]['status'] = 'processing';
expect_outbox( ! YBY_Lead_Notification_Outbox::request_manual_retry( 101 ), 'Uncertain processing cannot be resent blindly' );
$GLOBALS['mail_success'] = false;
$wpdb->allow_enqueue = false;
$outage = YBY_Lead_Notification_Outbox::enqueue_and_attempt($lead, array('lead_id'=>102,'case_id'=>'C-102','status'=>'received'));
expect_outbox( 'queue_unavailable' === $outage['status'], 'Queue failure must not call mail' );
expect_outbox( 2 === $GLOBALS['sent_count'], 'No send without durable queue' );
expect_outbox( 'email_outbox_unavailable' === end($wpdb->activities)['activity_type'], 'Enqueue failure is saved in lead activity log' );
expect_outbox( 'yby_lead_notification_recover' === end($GLOBALS['cron_events'])[1], 'Enqueue failure schedules recovery' );
$wpdb->allow_enqueue = true;
$wpdb->leads[102] = array_merge( $lead, array('case_id' => 'C-102', 'custom_fields' => '{}') );
YBY_Lead_Notification_Outbox::recover(102, 1);
expect_outbox( 'failed' === $wpdb->receipts[102]['status'] && 3 === $GLOBALS['sent_count'], 'Recovery only attempts after durable queue exists' );
YBY_Lead_Notification_Outbox::recover(102, 1);
expect_outbox( 3 === $GLOBALS['sent_count'], 'Repeat recovery does not send before retry window' );
$wpdb->receipts[102]['status'] = 'exhausted';
$GLOBALS['cron_fail'] = true;
expect_outbox( ! YBY_Lead_Notification_Outbox::request_manual_retry( 102 ), 'Failed scheduler reports false' );
expect_outbox( 'pending' === $wpdb->receipts[102]['status'], 'Requeue state remains pending' );
$wpdb->receipts[102]['updated_at'] = gmdate('Y-m-d H:i:s', time() - 360 );
$GLOBALS['mail_success'] = true;
YBY_Lead_Notification_Outbox::sweep();
expect_outbox( 'smtp_accepted' === $wpdb->receipts[102]['status'], 'Sweep repairs missed pending schedule' );
expect_outbox( 4 === $GLOBALS['sent_count'], 'Sweep makes only one retry attempt' );
YBY_Lead_Notification_Outbox::sweep();
expect_outbox( 4 === $GLOBALS['sent_count'], 'Sweep never resends accepted mail' );
$GLOBALS['payload_fail'] = true;
$no_send = YBY_Lead_Notification_Outbox::enqueue_and_attempt( $lead, array( 'lead_id' => 103, 'case_id' => 'C-103', 'status' => 'received' ) );
expect_outbox( 'failed' === $no_send['status'], 'Pre-send payload exception stored as failure' );
expect_outbox( 'notification_exception' === $wpdb->receipts[103]['last_error_code'], 'Exception recorded as safe code' );
expect_outbox( 4 === $GLOBALS['sent_count'], 'Payload exception never sends email' );
echo "LEAD_NOTIFICATION_OUTBOX_OFFLINE_PASS 23 assertions\n";
