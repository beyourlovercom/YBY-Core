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
// Native-only stores have no WPForms requirement and no admin newsletter sender.
check( ! YBY_Mail_Admin_Notifications::wpforms_available(), 'WPForms optional on native-only site' );
class YBY_Newsletter_REST_Controller {
    public static function enabled() { return true; }
    public static function outbound_enabled() { return false; }
    public static function subscribe_ready() { return false; }
}
$status = YBY_Mail_Admin_Notifications::native_newsletter_status();
check( $status['enabled'] && ! $status['outbound'] && ! $status['ready'], 'native readiness shown as state, not enabled by Core' );
check( $status['administrator_notification'] === false, 'native sends no admin newsletter notification' );
function current_user_can( $capability ) { return true; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function checked( $actual, $expected, $echo = true ) {
    return $actual === $expected ? ' checked="checked"' : '';
}
function wp_nonce_field( $action ) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function submit_button( $label ) { echo '<button>' . esc_html( $label ) . '</button>'; }
ob_start();
YBY_Mail_Admin_Notifications::render();
$native_ui = ob_get_clean();
check( strpos( $native_ui, 'Andy Core 原生 Newsletter' ) !== false, 'native source identified in admin UI' );
check( strpos( $native_ui, '不发送（内置规则，无需开关）' ) !== false, 'native admin notice is read only and off' );
check( strpos( $native_ui, 'Newsletter 外发授权' ) !== false, 'customer confirmation delivery gate disclosed' );
check( strpos( $native_ui, 'name="mail_admin[newsletter_admin]"' ) === false, 'legacy WPForms toggle hidden when plugin absent' );
check( strpos( $native_ui, 'name="mail_admin[newsletter_form_ids]"' ) === false, 'legacy WPForms form selector hidden' );
check( strpos( $native_ui, '保存管理员通知规则' ) !== false, 'native admin registration toggle still editable' );
check( strpos( $native_ui, 'New order' ) !== false, 'native mail UI retains Woo summary' );
// When a legacy site installs WPForms, retain the per-form administrator rules.
class WPForms {}
check( YBY_Mail_Admin_Notifications::wpforms_available(), 'WPForms optional provider detected' );
ob_start();
YBY_Mail_Admin_Notifications::render();
$legacy_ui = ob_get_clean();
check( strpos( $legacy_ui, 'WPForms 旧站兼容通知' ) !== false, 'WPForms optional provider can render legacy controls' );
check( strpos( $legacy_ui, 'name="mail_admin[newsletter_admin]"' ) !== false, 'legacy admin toggle appears on WPForms site' );
check( strpos( $legacy_ui, 'id="yby-newsletter-forms"' ) !== false, 'legacy form IDs appear on WPForms site' );
check( strpos( $legacy_ui, 'id="yby-newsletter-admins"' ) !== false, 'legacy recipients appear on WPForms site' );
check( strpos( $legacy_ui, 'Andy Core 原生 Newsletter' ) !== false, 'native status remains separate from legacy rules' );
echo "MAIL_ADMIN_NOTIFICATIONS_NO_NETWORK_PASS\n";
