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
$_GET['tab'] = 'mail_delivery';
unset( $_POST['yby_mail_retry_lead_id'] );
ob_start();
YBY_Lead_Notification_Admin::render();
$html = ob_get_clean();
lead_admin_check( false !== strpos( $html, '通知记录' ), 'page_heading' );
lead_admin_check( false !== strpos( $html, (string) $receipt['case_id'] ), 'case_id_visibility' );
lead_admin_check( false !== strpos( $html, 'mail_send_failed' ), 'error_visibility' );
lead_admin_check( false !== strpos( $html, '手动重发' ), 'manual_retry_button_visibility' );
lead_admin_check( false !== strpos( $html, 'yby_mail_retry_nonce' ), 'nonce_present' );
lead_admin_check( false !== strpos( $html, '忽略通知' ), 'ignore_button_visible' );
lead_admin_check( false !== strpos( $html, 'yby_mail_ignore_nonce' ), 'ignore_nonce_present' );
lead_admin_check( false !== strpos( $html, 'yby_mail_ignore_reason' ), 'ignore_reason_required' );
lead_admin_check( false !== strpos( $html, 'SMTP accepted' ), 'delivery_semantics' );
ob_start();
YBY_Lead_Notification_Admin::render_notice();
$notice = ob_get_clean();
lead_admin_check( false !== strpos( $notice, '询盘邮件异常' ), 'admin_warning' );
// The third-level route must render its own table, independently of the
// former Inquiry → Notification configuration page (without sending mail).
$_GET['tab'] = 'mail_delivery';
ob_start();
( new YBY_Inquiry_Admin( 'yby-core', YBY_CORE_VERSION ) )->render_page();
$route_html = ob_get_clean();
lead_admin_check( false !== strpos( $route_html, '通知记录' ), 'independent_tertiary_route_heading' );
lead_admin_check( false !== strpos( $route_html, (string) $receipt['case_id'] ), 'independent_route_renders_failed_receipt' );
if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) {
    $links = YBY_Andy_OS_Navigation::tertiary_items( 'andy-core-leads' );
    $matches = array_values( array_filter( $links, static function( $link ) {
        return false !== strpos( $link['url'], 'tab=mail_delivery' );
    } ) );
    lead_admin_check( 1 === count( $matches ), 'single_canonical_tertiary_route' );
    lead_admin_check( false !== strpos( $matches[0]['title'], '通知记录' ), 'tertiary_menu_title' );
    lead_admin_check( false !== strpos( $matches[0]['title'], '待处理' ), 'failed_mail_count_visible_in_menu' );
}
$wpdb->update( $table, array( 'status' => $original, 'last_error_code' => '' ),
    array( 'lead_id' => $lead_id ), array( '%s', '%s' ), array( '%d' ) );
// Ignored receipts remain visible with a reason, without a resend control.
$_GET['mail_status'] = 'ignored';
ob_start();
YBY_Lead_Notification_Admin::render();
$ignored_html = ob_get_clean();
lead_admin_check( false !== strpos( $ignored_html, '已忽略' ), 'ignored_filter_visible' );
lead_admin_check( false !== strpos( $ignored_html, 'Offline test: customer already contacted' ), 'ignored_reason_visible' );
unset( $_GET['mail_status'] );
echo "LEAD_NOTIFICATION_ADMIN_WP_PASS 13 assertions; live_email=0\n";
