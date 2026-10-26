<?php
/**
 * Standalone, no-network notifications contract harness.
 * Does not send email or mutate a real WordPress database.
 */
define( 'ABSPATH', __DIR__ );
$GLOBALS['yby_test_options'] = array();
$GLOBALS['yby_hooks'] = array();
function get_option( $key, $fallback = null ) {
    if ( 'admin_email' === $key ) { return 'site-admin@example.test'; }
    return $GLOBALS['yby_test_options'][ $key ] ?? $fallback;
}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
    $GLOBALS['yby_hooks'][ $hook ] = array( $callback, $priority, $args );
}
function add_action( $hook, $callback ) { $GLOBALS['yby_hooks'][ $hook ] = $callback; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_email( $email ) { return filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ); }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false; }
function check( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( 'MAIL_ADMIN_NOTIFICATION_FAIL: ' . $message ); }
}
require dirname( __DIR__ ) . '/inc/class-yby-mail-admin-notifications.php';
YBY_Mail_Admin_Notifications::register();
check( isset( $GLOBALS['yby_hooks']['wp_send_new_user_notification_to_admin'] ), 'registration hook registered' );
check( isset( $GLOBALS['yby_hooks']['wpforms_process_before_form_data'] ), 'WPForms hook registered' );
check( YBY_Mail_Admin_Notifications::filter_registration_admin( true ), 'default preserves admin registration mail' );

$config = YBY_Mail_Admin_Notifications::sanitize( array(
    'registration_admin' => '',
    'newsletter_admin' => '',
    'newsletter_form_ids' => "26680, 26680, not-a-number, 100",
    'newsletter_admin_recipients' => 'SUPPORT@BEYOURLOVER.COM;invalid;support@beyourlover.com',
) );
check( $config['newsletter_form_ids'] === '26680,100', 'form ID dedup and input sanitation' );
check( $config['newsletter_admin_recipients'] === 'support@beyourlover.com', 'admin email sanitation' );
$GLOBALS['yby_test_options'][YBY_Mail_Admin_Notifications::OPTION] = $config;
check( ! YBY_Mail_Admin_Notifications::filter_registration_admin( true ), 'disable only admin registration mail' );
check( ! YBY_Mail_Admin_Notifications::filter_registration_admin( false ), 'preserve upstream disabled registration' );
$src = array(
    'id' => 26680,
    'settings' => array( 'notifications' => array(
        'admin' => array( 'email' => '{admin_email}', 'subject' => 'New Entry' ),
        'support' => array( 'email' => 'support@beyourlover.com', 'subject' => 'New Entry' ),
        'mixed' => array( 'email' => 'support@beyourlover.com, customer@example.test', 'subject' => 'Test' ),
        'customer' => array( 'email' => '{field_id="1"}', 'subject' => 'Confirm subscription' ),
        'thirdparty' => array( 'email' => 'other@example.test', 'subject' => 'Other team' ),
    ) ),
);
$changed = YBY_Mail_Admin_Notifications::filter_wpforms_form_data( $src );
check( ! isset( $changed['settings']['notifications']['admin'] ), 'admin smart tag removed on target form' );
check( ! isset( $changed['settings']['notifications']['support'] ), 'explicit admin recipient removed on target form' );
check( $changed['settings']['notifications']['mixed']['email'] === 'customer@example.test', 'mixed To preserves customer' );
check( $changed['settings']['notifications']['customer']['email'] === '{field_id="1"}', 'customer confirmation preserved' );
check( $changed['settings']['notifications']['thirdparty']['email'] === 'other@example.test', 'unrelated recipient preserved' );
check( $src['settings']['notifications']['admin']['email'] === '{admin_email}', 'stored source not mutated' );
$other = $src; $other['id'] = 31000;
check( YBY_Mail_Admin_Notifications::filter_wpforms_form_data( $other ) === $other, 'other forms unaffected, including inquiry' );
check( YBY_Mail_Admin_Notifications::filter_wpforms_form_data( 'unexpected' ) === 'unexpected', 'invalid input safe' );
$GLOBALS['yby_test_options'][YBY_Mail_Admin_Notifications::OPTION]['newsletter_admin'] = true;
check( YBY_Mail_Admin_Notifications::filter_wpforms_form_data( $src ) === $src, 'reactivation restores notification' );
class FakeWooEmail {
    public $id = 'new_order';
    public $title = 'New order';
    public function is_enabled() { return true; }
}
class FakeWooMailer {
    public function get_emails() { return array( new FakeWooEmail() ); }
}
class FakeWoo {
    public function mailer() { return new FakeWooMailer(); }
}
function WC() { return new FakeWoo(); }
function admin_url( $path ) { return '/wp-admin/' . $path; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
ob_start();
YBY_Mail_Admin_Notifications::render_woo_email_summary();
$html = ob_get_clean();
check( strpos( $html, 'New order' ) !== false, 'Woo email inventory displayed' );
check( strpos( $html, 'section=new_order' ) !== false, 'Woo email native settings link' );
check( strpos( $html, '已启用' ) !== false, 'Woo source state read only' );
echo "MAIL_ADMIN_NOTIFICATIONS_NO_NETWORK_PASS\n";
