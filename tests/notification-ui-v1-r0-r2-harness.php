<?php
/**
 * R1/R2 Notification UI module contract: isolated, OFF by default.
 * No live WordPress DB or WooCommerce side effects.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['notify_test_options'] = array();
$GLOBALS['notify_test_scripts'] = array();
$GLOBALS['notify_test_localized'] = array();
$GLOBALS['notify_test_hooks'] = array();
$GLOBALS['notify_test_admin'] = false;
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['notify_test_options'] ) ? $GLOBALS['notify_test_options'][ $key ] : $default;
}
function update_option( $key, $v, $autoload = null ) { $GLOBALS['notify_test_options'][ $key ] = $v; return true; }
function is_admin() { return $GLOBALS['notify_test_admin']; }
function is_feed() { return false; }
function is_embed() { return false; }
function wp_enqueue_script( $handle, $src, $deps, $version, $footer ) {
	$GLOBALS['notify_test_scripts'][] = compact( 'handle', 'src', 'deps', 'version', 'footer' );
}
function wp_localize_script( $handle, $object_name, $data ) {
	$GLOBALS['notify_test_localized'][] = compact( 'handle', 'object_name', 'data' );
}
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['notify_test_hooks'][ $hook ][] = $callback;
}
function admin_url( $p = '' ) { return '/wp-admin/' . $p; }
function add_query_arg( $args, $u ) { return $u . '?' . http_build_query( $args ); }

define( 'YBY_CORE_VERSION', '2.3.1' );
define( 'YBY_CORE_PLUGIN_URL', 'https://example.com/wp-content/plugins/yby-core/' );
require dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require dirname( __DIR__ ) . '/inc/class-yby-module-runtime.php';
require dirname( __DIR__ ) . '/inc/class-yby-notification-ui-module.php';
function verify( $ok, $reason ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$reason}\n" ); exit( 1 ); } }

verify( YBY_Notification_UI_Module::register_module(), 'register notification_ui' );
$meta = YBY_Module_Registry::module( 'notification_ui' );
verify( 'ready' === $meta['status'] && false === $meta['default_enabled'], 'OFF by default' );
verify( empty( $meta['settings'] ), 'no independent menu or settings screen' );
verify( 'andy_core_settings_manage' === $meta['capability'], 'centralized capability' );
verify( ! in_array( 'notification_ui', YBY_Module_Registry::defaults(), true ), 'defaults exclude notification_ui' );
verify( ! YBY_Module_Registry::is_enabled( 'notification_ui' ), 'module initially OFF' );

$runtime = new YBY_Module_Runtime();
$runtime->boot_registered_extensions();
verify( count( $GLOBALS['notify_test_hooks']['wp_enqueue_scripts'] ?? array() ) === 1, 'OFF only loads shared native notice skin hook' );
YBY_Notification_UI_Module::enqueue_assets( $meta );
verify( empty( $GLOBALS['notify_test_scripts'] ), 'OFF enqueues no assets' );

$original = YBY_Module_Registry::defaults();
YBY_Module_Registry::save( $original );
verify( ! YBY_Module_Registry::is_enabled( 'notification_ui' ), 'existing enabled modules unchanged' );
$with_notification = array_merge( $original, array( 'notification_ui' ) );
YBY_Module_Registry::save( $with_notification );
verify( YBY_Module_Registry::is_enabled( 'notification_ui' ), 'central switch ON' );
verify( count( YBY_Module_Registry::enabled_modules() ) === count( $with_notification ), 'no other switch disturbed' );
$runtime_on = new YBY_Module_Runtime();
$runtime_on->boot_registered_extensions();
verify( count( $GLOBALS['notify_test_hooks']['wp_enqueue_scripts'] ?? array() ) === 2, 'native skin and enabled island hooks' );
verify( YBY_Notification_UI_Module::should_enqueue_assets( $meta ), 'ON frontend available' );
$GLOBALS['notify_test_admin'] = true;
verify( ! YBY_Notification_UI_Module::should_enqueue_assets( $meta ), 'never on admin pages' );
$GLOBALS['notify_test_admin'] = false;
YBY_Notification_UI_Module::enqueue_assets( $meta );
verify( count( $GLOBALS['notify_test_scripts'] ) === 1, 'one JS asset' );
verify( count( $GLOBALS['notify_test_localized'] ) === 1, 'one localized config' );
$script = $GLOBALS['notify_test_scripts'][0];
$config = $GLOBALS['notify_test_localized'][0];
verify( str_contains( $script['src'], 'andy-core-notification-ui.js' ), 'Core-owned JS source' );
verify( str_contains( $config['data']['cssUrl'], 'andy-core-notification-ui.css' ), 'Core-owned shadow CSS' );
verify( 'AndyCoreNotificationUIConfig' === $config['object_name'], 'dedicated config namespace' );
verify( 2000 === $config['data']['holdMs'] && 1200 === $config['data']['dedupeMs'], 'LELO internal timing' );

YBY_Module_Registry::save( $original );
verify( ! YBY_Module_Registry::is_enabled( 'notification_ui' ), 'OFF restores module gate' );
$GLOBALS['notify_test_scripts'] = array();
YBY_Notification_UI_Module::enqueue_assets( $meta );
verify( empty( $GLOBALS['notify_test_scripts'] ), 'OFF after ON skips scripts' );
verify( YBY_Module_Registry::enabled_modules() === $original, 'other modules unchanged after OFF' );

$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$module_view = file_get_contents( $root . '/admin/views/modules-page.php' );
$ui_js = file_get_contents( $root . '/public/assets/js/andy-core-notification-ui.js' );
$ui_css = file_get_contents( $root . '/public/assets/css/andy-core-notification-ui.css' );
verify( str_contains( $core, 'YBY_Notification_UI_Module' ), 'bootstrap registry hook exists' );
verify( str_contains( $module_view, 'name="yby_core_modules[]"' ), 'uses current module switch table' );
verify( ! str_contains( $ui_css, '.woocommerce-message' ) && ! str_contains( $ui_js, 'wc_print_notices' ), 'no Woo notice suppression in R2' );
verify( ! str_contains( $ui_js, 'fetch(' ) && ! str_contains( $ui_js, 'XMLHttpRequest' ), 'no network calls from UI runtime' );
verify( ! str_contains( $ui_css, '@keyframes countdown' ) && ! str_contains( $ui_css, '.progress-bar' ), 'no progress UI' );
echo "PASS notification-ui-v1-r0-r2-harness (OFF/ON/OFF, registry, isolated assets)\n";
