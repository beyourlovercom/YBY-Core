<?php
/** Disposable WP+MySQL integration. Every wp_mail is intercepted (no real SMTP). */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) ) { exit( 81 ); }
function p0_smoke_assert( $ok, $message ) {
    if ( ! $ok ) { throw new RuntimeException( 'LEAD_OUTBOX_MYSQL_FAIL:' . $message ); }
}
global $wpdb;
p0_smoke_assert( YBY_Database::notification_table_exists(), 'migration_did_not_create_outbox' );
p0_smoke_assert( YBY_Database::leads_table_exists(), 'lead_table_missing' );
$GLOBALS['p0_mail_attempts'] = 0;
$GLOBALS['p0_mail_accept'] = false;
add_filter( 'pre_wp_mail', static function( $unused, $atts ) {
    ++$GLOBALS['p0_mail_attempts'];
    return (bool) $GLOBALS['p0_mail_accept'];
}, 10, 2 );
$lead = array(
    'brand' => 'ci', 'website' => 'https://example.test', 'source_url' => 'https://example.test/quote',
    'name' => 'CI offline lead', 'company' => 'No external mail', 'country' => 'US',
    'email' => 'offline@example.test', 'whatsapp' => '', 'buyer_type' => '',
    'product_interest' => 'Integration test', 'quantity' => '', 'project_details' => '',
    'source_component' => 'inquiry_modal', 'source_preset' => '', 'source_page' => '',
    'form_version' => '', 'page_profile' => '', 'custom_fields' => array(),
    'utm_source' => '', 'utm_medium' => '', 'utm_campaign' => '',
    'utm_term' => '', 'gclid' => '', 'fbclid' => '',
);
$stored = YBY_Lead_Service::create( $lead );
p0_smoke_assert( ! empty($stored['success']) && $stored['lead_id'] > 0, 'lead_storage' );
$lead_id = (int) $stored['lead_id'];
$first = YBY_Lead_Notification_Outbox::enqueue_and_attempt( $lead, $stored );
p0_smoke_assert( 'failed' === $first['status'], 'first_transport_failure' );
p0_smoke_assert( 1 === $GLOBALS['p0_mail_attempts'], 'first_mail_filter_called_once' );
$receipt = $wpdb->get_row( $wpdb->prepare(
    'SELECT * FROM ' . YBY_Lead_Notification_Outbox::table_name() . ' WHERE lead_id = %d', $lead_id
), ARRAY_A );
p0_smoke_assert( is_array($receipt) && (int) $receipt['attempts'] === 1 && 'failed' === $receipt['status'], 'receipt_persisted_mysql' );
p0_smoke_assert( 'mail_send_failed' === $receipt['last_error_code'], 'error_code_persisted' );
p0_smoke_assert( (string) $receipt['case_id'] === (string) $stored['case_id'], 'case_id_linkage' );
p0_smoke_assert( false !== wp_next_scheduled( 'yby_lead_notification_retry', array( $lead_id ) ), 'retry_scheduled' );
YBY_Lead_Notification_Outbox::retry( $lead_id );
p0_smoke_assert( 1 === $GLOBALS['p0_mail_attempts'], 'early_retry_blocked_by_mysql' );
$wpdb->update( YBY_Lead_Notification_Outbox::table_name(),
    array( 'next_retry_at' => wp_date('Y-m-d H:i:s', time() - 60) ),
    array( 'lead_id' => $lead_id ), array('%s'), array('%d') );
$GLOBALS['p0_mail_accept'] = true;
YBY_Lead_Notification_Outbox::retry( $lead_id );
$receipt = $wpdb->get_row( $wpdb->prepare(
    'SELECT * FROM ' . YBY_Lead_Notification_Outbox::table_name() . ' WHERE lead_id = %d', $lead_id
), ARRAY_A );
p0_smoke_assert( 'smtp_accepted' === $receipt['status'] && (int) $receipt['attempts'] === 2, 'success_recorded_mysql' );
p0_smoke_assert( 2 === $GLOBALS['p0_mail_attempts'], 'retry_attempt_count' );
YBY_Lead_Notification_Outbox::retry( $lead_id );
p0_smoke_assert( 2 === $GLOBALS['p0_mail_attempts'], 'accepted_receipt_never_resent' );
$events = (int) $wpdb->get_var( $wpdb->prepare(
    'SELECT COUNT(*) FROM ' . YBY_Database::activities_table_name() .
    ' WHERE lead_id = %d AND activity_type = %s', $lead_id, 'email_notification'
) );
p0_smoke_assert( 2 === $events, 'audited_both_attempts' );
$receipts = (int) $wpdb->get_var( $wpdb->prepare(
    'SELECT COUNT(*) FROM ' . YBY_Lead_Notification_Outbox::table_name() . ' WHERE lead_id = %d', $lead_id
) );
p0_smoke_assert( 1 === $receipts, 'unique_receipt_enforced' );
echo "LEAD_NOTIFICATION_WORDPRESS_MYSQL_PASS 15 assertions; simulated_email_attempts=2; live_smtp=0\n";
