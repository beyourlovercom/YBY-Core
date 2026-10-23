<?php
/** Commerce-only L3 consumer contract. All UI tests are synthetic. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['currency_l3_allowed'] = true;
$GLOBALS['currency_l3_filter'] = array();
class Andy_Commerce_Currency_V2_Dev {
    public static function access() { return $GLOBALS['currency_l3_allowed']; }
}
function __( $text, $domain = '' ) { return $text; }
function add_menu_page( ...$args ) { return 'toplevel_page_andy-commerce'; }
function add_submenu_page( ...$args ) { return 'andy-commerce_page_andy-commerce'; }
function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
    $GLOBALS['currency_l3_filter'] = array( $tag, $callback, $priority, $accepted_args );
}
require_once dirname( __DIR__ ) . '/admin/class-andy-commerce-admin.php';
$check = static function ( $ok, $msg ) {
    if ( ! $ok ) { fwrite( STDERR, "FAIL COMMERCE L3: $msg\n" ); exit(1); }
};
$admin = new Andy_Commerce_Admin();
$admin->add_admin_menu();
$check( $GLOBALS['currency_l3_filter'][0] === 'andy_core_l3_external_items'
    && $GLOBALS['currency_l3_filter'][3] === 2, 'consumer hook declared during native admin_menu' );
$check( count( Andy_Commerce_Admin::supported_tabs() ) === 10, 'controller-owned real tab allowlist' );
$items = Andy_Commerce_Admin::register_l3_items( array(), 'andy-commerce' );
$check( count( $items ) === 1 && $items[0]['title'] === '多币种'
    && $items[0]['after'] === 'settings', 'Commerce declares Currency after Settings' );
$check( call_user_func( $items[0]['route_check'], 'currency-os', 'andy-commerce' ) === true, 'real route gate' );
$check( Andy_Commerce_Admin::register_l3_items( array(), 'andy-core-newsletter' ) === array(),
    'cannot register for another host page' );
$check( Andy_Commerce_Admin::currency_l3_route_exists( 'unsupported-tab', 'andy-commerce' ) === false,
    'controller rejects nonexistent tab' );
$GLOBALS['currency_l3_allowed'] = false;
$check( Andy_Commerce_Admin::register_l3_items( array(), 'andy-commerce' ) === array(),
    'Currency disabled/unavailable hides declaration' );
$check( Andy_Commerce_Admin::currency_l3_route_exists( 'currency-os', 'andy-commerce' ) === false,
    'route check fails when module unavailable' );
echo "PASS andy-commerce-l3-consumer-v1-harness\n";
