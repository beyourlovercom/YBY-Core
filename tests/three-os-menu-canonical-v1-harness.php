<?php
/** Andy Core Canonical V1 menu/permissions regression, no WP, DB, mail or network. */
define( 'ABSPATH', __DIR__ );
define( 'YBY_CORE_VERSION', 'test' );
define( 'YBY_CORE_PLUGIN_URL', 'https://example.test/' );
$tests = 0;
function os_check( $condition, $message ) {
    global $tests;
    ++$tests;
    if ( ! $condition ) { throw new RuntimeException( 'THREE_OS_FAIL: ' . $message ); }
}
class YBY_Project_Studio { public static function menu_slug() { return 'yby-os'; } }
class YBY_Inquiry_Admin { public static function page_slug() { return 'andy-core-leads'; } }
class YBY_Module_Registry {
    public static $enabled = array( 'inquiry_os' => true, 'newsletter_os' => true, 'email_os' => true );
    public static function is_enabled( $key ) { return ! empty( self::$enabled[ $key ] ); }
}
$GLOBALS['registered'] = array();
$GLOBALS['removed'] = array();
function add_submenu_page( $parent, $page_title, $title, $capability, $slug, $callback ) {
    $GLOBALS['registered'][] = array( $parent, $title, $capability, $slug );
}
function remove_submenu_page( $parent, $slug ) { $GLOBALS['removed'][] = array( $parent, $slug ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function add_query_arg( $args, $url = '' ) {
    if ( ! is_array( $args ) ) { return $url; }
    return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . http_build_query( $args );
}
function current_user_can( $cap ) { return ! empty( $GLOBALS['caps'][ $cap ] ); }
function esc_html( $str ) { return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $str, $domain = '' ) { return esc_html( $str ); }
function esc_attr( $str ) { return esc_html( $str ); }
function esc_url( $str ) { return esc_html( $str ); }
function wp_die( $s ) { throw new RuntimeException( 'DENIED' ); }
require dirname( __DIR__ ) . '/admin/class-yby-three-os-admin.php';
$controller = new YBY_Three_OS_Admin();
$controller->register_menus();
os_check( 2 === count( $GLOBALS['registered'] ), 'two new submenus' );
os_check( array( 'yby-os', 'Newsletter OS', 'andy_core_newsletter_view', 'andy-core-newsletter' ) === $GLOBALS['registered'][0], 'newsletter has independent cap' );
os_check( array( 'yby-os', 'Email OS', 'andy_core_mail_view', 'andy-core-email' ) === $GLOBALS['registered'][1], 'mail has independent cap' );
os_check( 'subscribers' === array_key_first( YBY_Three_OS_Admin::newsletter_tabs() ), 'newsletter default subscribers' );
os_check( 'overview' === array_key_first( YBY_Three_OS_Admin::email_tabs() ), 'email default overview' );
os_check( 'leads' === array_key_first( YBY_Three_OS_Admin::inquiry_tabs() ), 'inquiry default leads' );
os_check( ! isset( YBY_Three_OS_Admin::newsletter_tabs()['email_templates'] ), 'templates belong to Email' );
os_check( ! isset( YBY_Three_OS_Admin::email_tabs()['popup_subscribe'] ), 'subscriber popup is not Email' );
os_check( isset( YBY_Three_OS_Admin::newsletter_tabs()['popup_lottery'] ), 'newsletter lottery ownership' );
os_check( ! isset( YBY_Three_OS_Admin::inquiry_tabs()['popup_subscribe'] ), 'subscriber popup is not Inquiry' );
os_check( str_contains( YBY_Three_OS_Admin::url( YBY_Three_OS_Admin::NEWSLETTER, 'subscribers' ), 'page=andy-core-newsletter&tab=subscribers' ), 'newsletter canonical URL' );
os_check( str_contains( YBY_Three_OS_Admin::url( YBY_Three_OS_Admin::EMAIL, 'overview' ), 'page=andy-core-email&tab=overview' ), 'email canonical URL' );
$GLOBALS['submenu'] = array( 'yby-os' => array(
    array( 'Project Studio', 'manage_options', 'yby-project-studio' ),
    array( 'Email OS', 'manage_options', 'yby-core-popups' ),
    array( 'Newsletter OS', 'andy_core_newsletter_view', 'andy-core-newsletter' ),
    array( 'Settings', 'manage_options', 'yby-core' ),
    array( 'Email OS', 'andy_core_mail_view', 'andy-core-email' ),
    array( 'Inquiry OS', 'andy_core_leads_view', 'andy-core-leads' ),
) );
$controller->hide_old_menu();
os_check( array( 'yby-os', 'yby-core-popups' ) === $GLOBALS['removed'][0], 'legacy menu only hidden' );
$GLOBALS['submenu']['yby-os'] = array_values( array_filter(
    $GLOBALS['submenu']['yby-os'], static fn( $item ) => 'yby-core-popups' !== $item[2]
) );
$controller->order_os_menus();
os_check( array( 'andy-core-leads','andy-core-newsletter','andy-core-email','yby-project-studio','yby-core' ) ===
    array_column( $GLOBALS['submenu']['yby-os'], 2 ), 'three OS menus together, preserve others' );
$GLOBALS['caps'] = array();
try { $controller->render_newsletter(); os_check( false, 'unauthorized newsletter must die' ); }
catch ( RuntimeException $e ) { os_check( 'DENIED' === $e->getMessage(), 'newsletter denied without capability' ); }
try { $controller->render_email(); os_check( false, 'unauthorized mail must die' ); }
catch ( RuntimeException $e ) { os_check( 'DENIED' === $e->getMessage(), 'mail denied without capability' ); }
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$popup = file_get_contents( $root . '/admin/class-yby-popup-admin.php' );
$view = file_get_contents( $root . '/admin/views/popup-settings.php' );
$source = file_get_contents( $root . '/admin/class-yby-three-os-admin.php' );
$activator = file_get_contents( $root . '/inc/class-yby-activator.php' );
$registry = file_get_contents( $root . '/inc/class-yby-module-registry.php' );
$inquiry = file_get_contents( $root . '/admin/class-yby-inquiry-admin.php' );
$templates = file_get_contents( $root . '/admin/views/email-template-settings.php' );
os_check( str_contains( $core, "'register_menus', 21" ), 'new menu registration' );
os_check( str_contains( $core, "'hide_old_menu', 1000" ), 'legacy nav hidden' );
os_check( str_contains( $core, "'order_os_menus', 1001" ), 'ordered after old menu removal' );
os_check( str_contains( $core, "'redirect_legacy_get', 20" ), 'old GET preserved' );
os_check( str_contains( $source, "'GET' !== ( \$_SERVER['REQUEST_METHOD'] ?? '' )" ), 'no legacy POST redirects' );
os_check( str_contains( $source, "'yby-core-popups'" ), 'old slug remains recognized' );
os_check( str_contains( $source, "'bulk_marketing' === \$tab ? 'campaigns'" ), 'legacy marketing tab maps to campaigns' );
os_check( str_contains( $popup, 'render_embedded' ) && str_contains( $popup, "check_admin_referer" ), 'existing form and nonce handler reused' );
os_check( str_contains( $view, 'empty( $embedded )' ), 'mixed old tabs not rendered in new module' );
os_check( str_contains( $inquiry, "Inquiry OS" ), 'renamed Inquiry menu' );
os_check( str_contains( $templates, "'page' => 'andy-core-email'" ), 'mail template links moved' );
os_check( str_contains( $registry, "'newsletter_os' => array(" ), 'newsletter registered in module registry' );
os_check( str_contains( $registry, "'page' => 'andy-core-newsletter'" ), 'module canonical newsletter URL' );
os_check( str_contains( $activator, "'andy_core_newsletter_view'" ), 'administrator grants newsletter read cap' );
os_check( str_contains( $activator, "'andy_core_mail_view'" ), 'administrator grants mail read cap' );
os_check( str_contains( $source, 'SELECT id,email,status,form_source,submitted_at,status_updated_at' ), 'minimal subscriber query fields' );
os_check( str_contains( $source, 'YBY_Newsletter_Store::exists()' ), 'no new newsletter table creation' );
os_check( str_contains( $source, 'current_user_can( self::NEWSLETTER_VIEW )' ), 'newsletter list permission verified on request' );
os_check( str_contains( $source, 'current_user_can( self::MAIL_VIEW )' ), 'mail overview permission verified on request' );
os_check( ! preg_match( '/\b(?:wp_mail|as_enqueue_async_action|as_schedule_single_action|as_schedule_recurring_action|dbDelta|update_option|DELETE FROM|INSERT INTO)\s*\(/', $source ), 'new module has zero outbound or DB writes' );
os_check( ! str_contains( $source, 'confirm_hash,confirm_expires_at' ) && ! str_contains( $source, 'unsubscribe_hash' ), 'never select sensitive confirmation tokens' );
os_check( ! str_contains( $source, "type=\"submit\">导出" ), 'no export action' );
os_check( ! str_contains( $source, 'wp_mail(' ), 'no direct mail sending in menu' );
YBY_Module_Registry::$enabled['newsletter_os'] = false;
$GLOBALS['registered'] = array();
$controller->register_menus();
os_check( 1 === count( $GLOBALS['registered'] ) && 'andy-core-email' === $GLOBALS['registered'][0][3], 'newsletter module disabled hides its page' );
echo 'PASS three-os-menu-canonical-v1 assertions=' . $tests . "\n";
