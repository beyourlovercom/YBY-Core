<?php
/** Native Shopify skin: real Woo HPOS editor module, bounded read-only contract. */
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ );
if ( ! defined( 'ANDY_COMMERCE_PLUGIN_URL' ) ) define( 'ANDY_COMMERCE_PLUGIN_URL', 'https://dev.example.test/wp-content/plugins/andy-commerce/' );
$GLOBALS['hooks'] = array();
$GLOBALS['styles'] = array();
$GLOBALS['scripts'] = array();
$GLOBALS['local'] = array();
$GLOBALS['screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
$GLOBALS['is_admin'] = true;
function is_admin() { return $GLOBALS['is_admin']; }
function get_current_screen() { return $GLOBALS['screen']; }
function current_user_can( $cap, $id = null ) { return true; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function add_action( $hook, $fn, $priority = 10 ) { $GLOBALS['hooks'][ $hook ] = $fn; }
function add_filter( $hook, $fn, $priority = 10 ) { $GLOBALS['hooks'][ $hook ] = $fn; }
function wp_enqueue_style( $h, $url, $dep = array(), $ver = '' ) { $GLOBALS['styles'][$h] = $url; }
function wp_enqueue_script( $h, $url, $dep = array(), $ver = '', $footer = true ) { $GLOBALS['scripts'][$h] = $url; }
function wp_localize_script( $handle, $name, $payload ) { $GLOBALS['local'][$name] = $payload; }
function wp_create_nonce( $nonce ) { return 'nonce_test'; }
function get_user_meta( $id, $key, $single ) { return array(); }
function get_current_user_id() { return 1; }
function get_userdata( $id ) { return null; }
function wc_get_order( $id ) { return false; } // No real orders or data access during test.
function sanitize_html_class( $id ) { return $id; }
function wp_add_inline_style( $handle, $css ) { $GLOBALS['inline'] = $css; }
require dirname( __DIR__ ) . '/includes/class-andy-commerce-native-orders-detail.php';
$check = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } };
Andy_Commerce_Native_Order_Detail_Skin::boot();
foreach ( array(
  'admin_enqueue_scripts', 'woocommerce_admin_order_data_header_right',
  'woocommerce_admin_order_data_after_payment_info',
  'woocommerce_admin_order_totals_after_total',
  'add_meta_boxes_woocommerce_page_wc-orders', 'admin_body_class',
  'wp_ajax_andy_commerce_native_orders_save_pin'
) as $hook ) $check( isset( $GLOBALS['hooks'][$hook] ), $hook . ' boot hook missing' );
$_GET = array( 'page' => 'wc-orders', 'action' => 'edit', 'id' => '39936' );
$body = Andy_Commerce_Native_Order_Detail_Skin::body_class( 'wp-admin' );
$check( str_contains( $body, 'byl-native-orders-shopify-v2' ), 'Shopify detail CSS class' );
Andy_Commerce_Native_Order_Detail_Skin::enqueue();
$check( isset( $GLOBALS['styles']['andy-commerce-native-order-detail'] ), 'native CSS enqueued' );
$check( isset( $GLOBALS['scripts']['andy-commerce-native-order-detail'] ), 'native JS enqueued' );
$check( str_contains( $GLOBALS['styles']['andy-commerce-native-order-detail'], 'andy-commerce/assets/native-orders/native-orders.css' ), 'CSS from Commerce package' );
$check( str_contains( $GLOBALS['scripts']['andy-commerce-native-order-detail'], 'andy-commerce/assets/native-orders/native-orders.js' ), 'JS from Commerce package' );
$check( $GLOBALS['local']['BYLNativeOrders']['orderId'] === 39936, 'correct HPOS ID' );
$check( $GLOBALS['local']['BYLNativeOrders']['nonce'] === 'nonce_test', 'nonce localized' );
$check( $GLOBALS['local']['BYLNativeOrders']['ajaxUrl'] === 'https://dev.example.test/wp-admin/admin-ajax.php', 'ajaxUrl' );
$css = file_get_contents( dirname( __DIR__ ) . '/assets/native-orders/native-orders.css' );
$js  = file_get_contents( dirname( __DIR__ ) . '/assets/native-orders/native-orders.js' );
$check( str_contains( $css, 'grid-template-columns:minmax(0,1fr) max-content!important' ), 'action bar pinned beside title' );
$check( str_contains( $css, 'grid-row:2!important' ), 'Woo payment summary in second row' );
$check( str_contains( $css, '@media(max-width:900px)' ), 'mobile stylesheet responsive' );
$check( str_contains( $js, "action:'andy_commerce_native_orders_save_pin'" ), 'AJAX pin action Commerce-scoped' );
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce-native-orders-detail.php' );
$check( ! str_contains( $source, 'register_activation_hook(' ), 'does not manage other plugin activations' );
$check( ! str_contains( $source, "'plugins_loaded'" ), 'not unconditionally booted' );
echo "PASS Andy Commerce native Order Detail UI PHP hooks, scoped assets, 2-row header and responsive skin\n";
