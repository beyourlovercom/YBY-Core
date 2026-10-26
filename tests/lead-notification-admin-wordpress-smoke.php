<?php
/**
 * Disposable WordPress + MySQL admin UX contract smoke.
 * No HTTP POST, cron dispatch or wp_mail() calls.
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) ) { exit( 81 ); }
function lead_admin_check( $pass, $what ) {
    if ( ! $pass ) { throw new RuntimeException( 'LEAD_ADMIN_SMOKE_FAIL:' . $what ); }
}
global $wpdb;
wp_set_current_user( 1 );
lead_admin_check( current_user_can( 'andy_core_settings_manage' ), 'capability_boot' );
lead_admin_check( YBY_Database::notification_table_exists(), 'schema_missing' );
$table = YBY_Lead_Notification_Outbox::table_name();
$receipt = $wpdb->get_row( "SELECT lead_id, case_id, status FROM {$table} ORDER BY id DESC LIMIT 1", ARRAY_A );
lead_admin_check( is_array( $receipt ), 'test_receipt_missing' );
$lead_id = (int) $receipt['lead_id'];
$original = $receipt['status'];
// Change only the disposable synthetic case. Do not submit or send real email.
$wpdb->update( $table,
    array( 'status' => 'failed', 'last_error_code' => 'mail_send_failed' ),
    array( 'lead_id' => $lead_id ), array( '%s', '%s' ), array( '%d' ) );
$_GET['page'] = 'andy-core-leads';
$_GET['tab'] = 'notification';
unset( $_POST['yby_mail_retry_lead_id'] );
ob_start();
YBY_Lead_Notification_Admin::render();
$html = ob_get_clean();
lead_admin_check( false !== strpos( $html, '询盘邮件通知记录' ), 'page_heading' );
lead_admin_check( false !== strpos( $html, (string) $receipt['case_id'] ), 'case_id_visibility' );
lead_admin_check( false !== strpos( $html, 'mail_send_failed' ), 'error_visibility' );
lead_admin_check( false !== strpos( $html, '手动重发' ), 'manual_retry_button_visibility' );
lead_admin_check( false !== strpos( $html, 'yby_mail_retry_nonce' ), 'nonce_present' );
lead_admin_check( false !== strpos( $html, 'SMTP accepted' ), 'delivery_semantics' );
ob_start();
YBY_Lead_Notification_Admin::render_notice();
$notice = ob_get_clean();
lead_admin_check( false !== strpos( $notice, '询盘邮件异常' ), 'admin_warning' );
$wpdb->update( $table, array( 'status' => $original, 'last_error_code' => '' ),
    array( 'lead_id' => $lead_id ), array( '%s', '%s' ), array( '%d' ) );
echo "LEAD_NOTIFICATION_ADMIN_WP_PASS 8 assertions; live_email=0\n";
