<?php
/**
 * Native WordPress L3 permission UAT with one fresh WP bootstrap per role.
 * CI-only disposable WordPress/MySQL fixture. Never accesses Dev users.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 'CI WP-CLI only' );
}
if ( 'http://newsletter-validation.test' !== get_option( 'siteurl' ) ) {
    WP_CLI::error( 'Refusing role test outside disposable Newsletter CI WordPress.' );
}
if ( ! defined( 'WP_ADMIN' ) ) { define( 'WP_ADMIN', true ); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$phase = getenv( 'ANDY_L3_CI_ROLE' );
if ( ! in_array( $phase, array( 'admin', 'subscriber', 'editor', 'anonymous' ), true ) ) {
    WP_CLI::error( 'Invalid role phase (admin/subscriber/editor/anonymous).' );
}
$assert = static function ( $ok, $label ) use ( $phase ) {
    if ( ! $ok ) { WP_CLI::error( 'ANDY_L3_NATIVE_ROLE_FAIL (' . $phase . '): ' . $label ); }
};
$assert( is_admin(), 'genuine WordPress admin context' );
$assert( class_exists( 'YBY_Andy_L3_Registry_V1' ), 'real Core V1 loaded' );
$assert( class_exists( 'YBY_Andy_OS_Navigation' ), 'Core navigation loaded' );

// Every invocation is a separate WP-CLI / WordPress bootstrap. Unlike
// switching users after admin_menu, this reproduces role-based menu setup.
if ( 'admin' === $phase ) {
    $ids = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
    $assert( ! empty( $ids ), 'disposable administrator exists' );
    $admin_id = (int) $ids[0];
    wp_set_current_user( $admin_id );
    // WooCommerce is absent. Grant only this CI administrator request a
    // temporary Woo-equivalent capability; do not modify stored WP roles.
    add_filter( 'user_has_cap', static function ( $caps ) use ( $admin_id ) {
        if ( get_current_user_id() === $admin_id ) { $caps['manage_woocommerce'] = true; }
        return $caps;
    }, 10, 1 );
} elseif ( 'anonymous' === $phase ) {
    wp_set_current_user( 0 );
} else {
    $user = get_user_by( 'login', 'l3_ci_' . $phase );
    $assert( $user instanceof WP_User, 'disposable restricted fixture exists' );
    wp_set_current_user( $user->ID );
}
$capable = 'admin' === $phase;
$assert( current_user_can( 'manage_woocommerce' ) === $capable,
    'WordPress user capability correct at request bootstrap' );

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
    // Real add_menu_page and native WordPress permission registration.
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
$hook = get_plugin_page_hookname( 'andy-commerce', '' );
$items = YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' );
$plugin_page = 'andy-commerce';
$_GET['page'] = 'andy-commerce';

if ( $capable ) {
    $assert( has_action( $hook ), 'original WordPress controller registered for admin' );
    $assert( array_column( $items, 'title' ) === array(
        '总览', '订单导出', '结账运费', '促销', '设置', 'CI Role Tab' ),
        'one Core-validated business link after five native tabs' );
    $assert( $items === YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ),
        'repeat reads deterministic' );
    $assert( user_can_access_admin_page(), 'real WordPress router allows privileged role' );
    $route_enabled = false;
    $assert( count( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) ) === 5,
        'disabled controller route hides extension' );
    $route_enabled = true;
    $fixture_enabled = false;
    $assert( count( YBY_Andy_OS_Navigation::tertiary_items( 'andy-commerce' ) ) === 5,
        'consumer deactivation removes extension' );

    // Only the disposable MySQL CI DB is mutated. No account notifications.
    foreach ( array( 'subscriber', 'editor' ) as $role ) {
        $id = wp_insert_user( array(
            'user_login' => 'l3_ci_' . $role,
            'user_pass' => 'ci-only-no-network-' . $role,
            'user_email' => 'l3-' . $role . '@example.test',
            'role' => $role,
        ) );
        $assert( ! is_wp_error( $id ), 'disposable fixture created: ' . $role );
    }
} else {
    $assert( ! has_action( $hook ), 'real WP menu callback omitted for restricted role' );
    $assert( $items === array(), 'no native or extended Woo L3 menu' );
    $assert( ! user_can_access_admin_page(),
        'WordPress page permission denies direct Commerce URL' );
}
WP_CLI::success( 'ANDY_L3_DISPOSABLE_WORDPRESS_REAL_ROLE_PASS: ' . $phase );
