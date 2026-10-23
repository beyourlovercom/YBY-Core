<?php
/** Synthetic WordPress authority/route tests. Never touches a live Woo site. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['l3_caps'] = array( 'manage_woocommerce' );
$GLOBALS['l3_callback'] = true;
$GLOBALS['l3_wp_registered'] = true;
$GLOBALS['l3_items'] = array();
$GLOBALS['l3_calls'] = 0;
function is_admin() { return true; }
function current_user_can( $cap ) { return in_array( $cap, $GLOBALS['l3_caps'], true ); }
function did_action( $hook ) { return $GLOBALS['l3_wp_registered'] && $hook === 'admin_menu' ? 1 : 0; }
function get_plugin_page_hookname( $page, $parent = '' ) {
    return $parent ? $parent . '_page_' . $page : 'toplevel_page_' . $page;
}
function has_action( $hook ) { return $GLOBALS['l3_callback'] && $hook === 'toplevel_page_andy-commerce' ? 10 : false; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function wp_strip_all_tags( $title ) { return strip_tags( $title ); }
function apply_filters( $hook, $items, $page ) {
    ++$GLOBALS['l3_calls'];
    return $hook === 'andy_core_l3_external_items' && $page === 'andy-commerce'
        ? $GLOBALS['l3_items'] : array();
}
require_once dirname( __DIR__ ) . '/admin/class-yby-andy-l3-registry-v1.php';
$check = static function ( $ok, $message ) {
    if ( ! $ok ) { fwrite( STDERR, 'FAIL L3 REGISTRY: ' . $message . "\n" ); exit( 1 ); }
};
$base = array();
foreach ( array( 'overview' => '总览', 'order-export' => '订单导出',
    'checkout-shipping' => '结账运费', 'marketing-promotions' => '促销',
    'settings' => '设置' ) as $tab => $title ) {
    $base[] = array( 'title' => $title,
        'url' => admin_url( 'admin.php?page=andy-commerce&tab=' . $tab ) );
}
$valid = array(
    'id' => 'andy-commerce.currency-os',
    'parent' => 'andy-commerce',
    'page' => 'andy-commerce',
    'title' => '多币种',
    'tab' => 'currency-os',
    'capability' => 'manage_woocommerce',
    'after' => 'settings',
    'route_check' => static function ( $tab, $page ) {
        return $tab === 'currency-os' && $page === 'andy-commerce';
    },
);
$run = static function () use ( $base ) {
    return YBY_Andy_L3_Registry_V1::extend( $base, 'andy-commerce' );
};
$GLOBALS['l3_items'] = array( $valid );
$result = $run();
$check( count( $result ) === 6 && array_column( $result, 'title' ) ===
    array( '总览', '订单导出', '结账运费', '促销', '设置', '多币种' ), 'native order and after anchor' );
$check( str_ends_with( $result[5]['url'], 'page=andy-commerce&tab=currency-os' ), 'Core builds canonical admin URL' );
$check( $run() === $result, 'repeat evaluation is idempotent' );
$GLOBALS['l3_items'] = array( $valid, $valid );
$check( count( $run() ) === 6, 'duplicate id and route rejected' );
$duplicate_route = $valid;
$duplicate_route['id'] = 'andy-commerce.alternative';
$GLOBALS['l3_items'] = array( $valid, $duplicate_route );
$check( count( $run() ) === 6, 'duplicate route with different id rejected' );
$bad = array(
    array( 'title' => '<script>alert(1)</script>' ),
    array( 'url' => 'https://attacker.invalid/' ),
    array( 'url' => 'javascript:alert(1)' ),
    array( 'parent' => 'andy-core-newsletter' ),
    array( 'page' => 'andy-core-leads' ),
    array( 'id' => 'andy-core-leads.currency-os' ),
    array( 'capability' => 'read' ),
    array( 'tab' => '../config' ),
    array( 'tab' => 'settings' ),
    array( 'after' => 'nonexistent-tab' ),
    array( 'route_check' => static function () { return false; } ),
    array( 'route_check' => static function () { throw new RuntimeException( 'disabled' ); } ),
);
foreach ( $bad as $index => $override ) {
    $GLOBALS['l3_items'] = array( array_replace( $valid, $override ) );
    $check( $run() === $base, 'invalid claim fail-closed #' . $index );
}
$GLOBALS['l3_items'] = array( $valid );
$GLOBALS['l3_caps'] = array( 'andy_core_newsletter_view', 'read' );
$before = $GLOBALS['l3_calls'];
$check( $run() === $base && $GLOBALS['l3_calls'] === $before,
    'newsletter-only never evaluates Woo extensions' );
$GLOBALS['l3_caps'] = array( 'manage_woocommerce' );
$GLOBALS['l3_callback'] = false;
$check( $run() === $base, 'no real WP controller callback hides extension' );
$GLOBALS['l3_callback'] = true;
$GLOBALS['l3_wp_registered'] = false;
$check( $run() === $base, 'admin_menu not registered hides extension' );
$GLOBALS['l3_wp_registered'] = true;
$GLOBALS['l3_items'] = array();
$check( $run() === $base, 'disabled plugin disappears immediately' );
$GLOBALS['l3_items'] = array( array_replace( $valid, array(
    'id' => 'andy-commerce.next-menu', 'tab' => 'next-menu', 'title' => '新功能',
    'route_check' => static function () { return true; },
) ) );
$check( count( $run() ) === 6 && $run()[5]['title'] === '新功能',
    'future Commerce declaration works without editing Core' );
$check( YBY_Andy_L3_Registry_V1::extend( $base, 'andy-core-leads' ) === $base,
    'cross-page role isolation' );
echo "PASS andy-core-l3-registry-v1-harness\n";
