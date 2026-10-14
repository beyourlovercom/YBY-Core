<?php
/**
 * Real WordPress admin-menu runtime UAT (disposable WP-CI only).
 * Evaluated by WP CLI after plugin activation; does not write live data.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI only' );
}
if ( ! defined( 'WP_ADMIN' ) ) { define( 'WP_ADMIN', true ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';

function andy_os_wp_assert( $ok, $label ) {
	if ( ! $ok ) { WP_CLI::error( 'ANDY_OS_WP_RUNTIME_FAIL ' . $label ); }
}
$admin = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
andy_os_wp_assert( ! empty( $admin ), 'admin exists' );
wp_set_current_user( (int) $admin[0] );
andy_os_wp_assert( class_exists( 'YBY_Andy_OS_Navigation' ), 'adapter bootstrapped' );
andy_os_wp_assert( is_admin(), 'admin context' );
andy_os_wp_assert( current_user_can( 'manage_options' ), 'WP admin capability' );
// WooCommerce is intentionally absent from this disposable WP baseline.
// Simulate its existing capability for this process only; no role writes.
$test_admin_id = (int) $admin[0];
add_filter( 'user_has_cap', static function ( $allcaps ) use ( $test_admin_id ) {
	if ( get_current_user_id() === $test_admin_id ) {
		$allcaps['manage_woocommerce'] = true;
	}
	return $allcaps;
}, 10, 1 );

global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages, $plugin_page, $pagenow;
$menu = array();
$submenu = array();
$admin_page_hooks = array();
$_registered_pages = array();
$_parent_pages = array();
$plugin_page = '';
$pagenow = 'admin.php';

// Simulate separately shipped Commerce menu on a genuine WordPress menu
// registry, without activating WooCommerce or changing site options.
$fixture_rendered = 0;
add_action( 'admin_menu', function () use ( &$fixture_rendered ) {
	add_menu_page( 'Andy Commerce', 'Andy Commerce', 'manage_options', 'andy-commerce', static function () use ( &$fixture_rendered ) { ++$fixture_rendered; }, 'dashicons-cart', 57 );
	add_submenu_page( 'andy-commerce', 'Overview', 'Overview', 'manage_options', 'andy-commerce', static function () use ( &$fixture_rendered ) { ++$fixture_rendered; } );
}, 35 );
add_action( 'admin_menu', function () {
	// Contract fixture: Business OS is site-gated and is not enabled on a
	// disposable validation.test host. Keep the real WordPress route engine.
	add_submenu_page( 'yby-content', 'Business', 'Business', 'manage_options', 'andy-business', static function () { echo 'BUSINESS_FIXTURE'; } );
}, 40 );

do_action( 'admin_menu' );
$root_slugs = array_values( array_map( static function ( $row ) { return $row[2] ?? ''; }, $menu ) );
andy_os_wp_assert( in_array( 'yby-os', $root_slugs, true ), 'Andy OS root present' );
andy_os_wp_assert( ! in_array( 'yby-content', $root_slugs, true ), 'Andy Content top-level removed' );
andy_os_wp_assert( ! in_array( 'andy-commerce', $root_slugs, true ), 'Andy Commerce top-level removed' );
$sub_slugs = array_column( $submenu['yby-os'] ?? array(), 2 );
foreach ( array( 'andy-os-overview','andy-os-content','andy-os-inquiry','andy-os-marketing','andy-os-commerce','andy-os-settings','yby-content','andy-business','andy-commerce' ) as $slug ) {
	andy_os_wp_assert( in_array( $slug, $sub_slugs, true ), 'slug maintained: ' . $slug );
}
andy_os_wp_assert( user_can_access_admin_page() || isset( $submenu['yby-os'] ), 'root registered' );
andy_os_wp_assert( has_action( 'toplevel_page_andy-commerce' ), 'original Commerce callback registered' );
$before = $fixture_rendered;
do_action( 'toplevel_page_andy-commerce' );
andy_os_wp_assert( $fixture_rendered > $before, 'Commerce original callback callable' );
$business_slug = 'andy-business';
$business_hook = get_plugin_page_hookname( $business_slug, 'yby-os' );
andy_os_wp_assert( is_string( $business_hook ) && has_action( $business_hook ), 'Business page callable under new parent' );

$_GET['page'] = 'andy-os-overview';
ob_start(); YBY_Andy_OS_Navigation::render_section(); $output = ob_get_clean();
andy_os_wp_assert( false !== strpos( $output, 'Andy OS' ) && false !== strpos( $output, 'Content' ), 'overview functional' );
$_GET['page'] = 'andy-os-content';
ob_start(); YBY_Andy_OS_Navigation::render_section(); $output = ob_get_clean();
andy_os_wp_assert( false !== strpos( $output, 'Andy OS' ), 'content section renders' );

// A role without module capabilities must never see a privileged section.
wp_set_current_user( 0 );
andy_os_wp_assert( ! current_user_can( 'manage_options' ), 'anonymous not administrator' );
andy_os_wp_assert( ! current_user_can( 'andy_core_settings_manage' ), 'anonymous not content manager' );
andy_os_wp_assert( ! current_user_can( 'manage_woocommerce' ), 'anonymous not Commerce manager' );
WP_CLI::success( 'ANDY_OS_WORDPRESS_MENU_RUNTIME_PASS' );
