<?php
/**
 * Controlled L3 registered against genuine, disposable WordPress roles/hooks.
 * ONLY run through CI WP-CLI on the synthetic Newsletter MySQL installation.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 'CI WP-CLI only' );
}
if ( 'http://newsletter-validation.test' !== get_option( 'siteurl' ) ) {
    WP_CLI::error( 'Refusing L3 role test outside the disposable Newsletter CI site.' );
}
if ( ! defined( 'WP_ADMIN' ) ) { define( 'WP_ADMIN', true ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$assert = static function ( $ok, $label ) {
    if ( ! $ok ) { WP_CLI::error( 'ANDY_L3_NATIVE_ROLE_FAIL: ' . $label ); }
};
$assert( is_admin(), 'genuine WordPress admin context' );
$assert( class_exists( 'YBY_Andy_L3_Registry_V1' ), 'real Core V1 installed' );
$assert( class_exists( 'YBY_Andy_OS_Navigation' ), 'Core navigation loaded' );

$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
$assert( ! empty( $admins ), 'disposable administrator exists' );
$admin_id = (int) $admins[0];
wp_set_current_user( $admin_id );
// WooCommerce is intentionally not installed in this disposable WP job.
// Grant a Woo-equivalent capability to this *one test session* only, not a role.
add_filter( 'user_has_cap', static function ( $caps ) use ( $admin_id ) {
    if ( get_current_user_id() === $admin_id ) { $caps['manage_woocommerce'] = true; }
    return $caps;
}, 10, 1 );
$assert( current_user_can( 'manage_woocommerce' ), 'administrator fixture cap' );

// Prepare the genuine WordPress admin menu tables, matching the established
// tests/andy-os-wordpress-admin-runtime.php fixture without touching Dev.
global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages,
       $_wp_submenu_nopriv, $_wp_menu_nopriv, $plugin_page, $pagenow, $parent_file;
$menu = array();
$submenu = array();
$admin_page_hooks = array();
$_registered_pages = array();
$_parent_pages = array();
$_wp_submenu_nopriv = array();
$_wp_menu_nopriv = array();
$plugin_page = 'andy-commerce';
$pagenow = 'admin.php';
$parent_file = '';
$_GET['page'] = 'andy-commerce';

$fixture_enabled = true;
$route_enabled = true;
add_action( 'admin_menu', static function () {
    // This is a genuine WordPress-registered admin controller, not a mock
    // get_plugin_page_hookname/has_action implementation.
    add_menu_page( 'Commerce CI', 'Commerce CI', 'manage_woocommerce',
        'andy-commerce', static function () { echo 'CI_REGISTERED_CONTROLLER'; },
        'dashicons-cart', 57 );
}, 35 );
add_filter( YBY_Andy_L3_Registry_V1::FILTER,
    static function ( $items, $page ) use ( &$fixture_enabled, &$route_enabled ) {
        if ( ! $fixture_enabled || 'andy-commerce' !== $page ) { return $items; }
        $items[] = array(
            'id' => 'andy-commerce.ci-role-tab',
            'parent' => 'andy-commerce',
            'page' => 'andy-commerce',
            'tab' => 'ci-role-tab',
            'title' => 'CI Role Tab',
            'capability' => 'manage_woocommerce',
            'after' => 'settings',
            'route_check' => static function ( $tab, $host ) use ( &$route_enabled ) {
                return $route_enabled && 'ci-role-tab' === $tab
                    && 'andy-commerce' === $host;
            },
        );
        return $items;
    }, 10, 2 );

do_action( 'admin_menu' );
$assert( has_action( get_plugin_page_hookname( 'andy-commerce', '' ) ),
    'registered WordPress controller callback survives Core nav consolidation' );
$native = YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' );
$labels = array_column( $native, 'title' );
$assert( $labels === array( '总览','订单导出','结账运费','促销','设置','CI Role Tab' ),
    'real WP admin context displays valid plugin registration once' );
$assert( $native === YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ),
    'repeat request deterministic' );
$assert( user_can_access_admin_page(), 'privileged user can access registered controller' );

$route_enabled = false;
$assert( count( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) ) === 5,
    'controller route disabled hides extension in real WordPress' );
$route_enabled = true;
$fixture_enabled = false;
$assert( count( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) ) === 5,
    'deactivated consumer filter removes extension' );
$fixture_enabled = true;

// Create disposable-only WP users; wp_insert_user does not send any notification.
// No Dev, production, customer or AffiliateWP account is ever accessed.
$roles = array( 'subscriber', 'editor' );
foreach ( $roles as $role ) {
    $uid = wp_insert_user( array(
        'user_login' => 'l3_ci_' . $role,
        'user_pass' => 'ci-only-no-network-' . $role,
        'user_email' => 'l3-' . $role . '@example.test',
        'role' => $role,
    ) );
    $assert( ! is_wp_error( $uid ), 'disposable role account provisioned: ' . $role );
    wp_set_current_user( (int) $uid );
    $assert( ! current_user_can( 'manage_woocommerce' ), 'real role Woo capability denied: ' . $role );
    $assert( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) === array(),
        'real WP role cannot see L3 Core or extension items: ' . $role );
    $plugin_page = 'andy-commerce';
    $_GET['page'] = 'andy-commerce';
    $assert( ! user_can_access_admin_page(),
        'real WordPress direct controller access denied: ' . $role );
}
wp_set_current_user( 0 );
$assert( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) === array(),
    'anonymous user menu is empty' );
$assert( ! user_can_access_admin_page(), 'anonymous direct controller denied' );
WP_CLI::success( 'ANDY_L3_DISPOSABLE_WORDPRESS_REAL_ROLE_PASS' );
