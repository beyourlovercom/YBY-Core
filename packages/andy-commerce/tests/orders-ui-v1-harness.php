<?php
/** Orders UI V1.2 — native first-paint and read-only Woo screen contract. */
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ );
define( 'ANDY_COMMERCE_PLUGIN_URL', 'https://example.test/plugins/andy-commerce/' );
define( 'ANDY_COMMERCE_VERSION', '1.2.0' );
$GLOBALS['hooks'] = array();
$GLOBALS['assets'] = array();
$GLOBALS['meta'] = array();
$GLOBALS['writes'] = 0;
$GLOBALS['can'] = true;
$GLOBALS['screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
function add_action( $hook, $fn ) { $GLOBALS['hooks'][ $hook ] = $fn; }
function add_filter( $hook, $fn, $priority = 10, $count = 1 ) { $GLOBALS['hooks'][ $hook ] = $fn; }
function current_user_can( $cap ) { return $GLOBALS['can']; }
function get_current_screen() { return $GLOBALS['screen']; }
function get_current_user_id() { return 7; }
function get_user_meta( $user, $key, $single ) { return $GLOBALS['meta'][ $key ] ?? ''; }
function update_user_meta( $user, $key, $value ) { $GLOBALS['writes']++; $GLOBALS['meta'][ $key ] = $value; }
function wp_enqueue_style( $h, $src, $deps, $ver ) { $GLOBALS['assets']['style'] = compact( 'h', 'src', 'deps', 'ver' ); }
function wp_enqueue_script( $h, $src, $deps, $ver, $footer ) { $GLOBALS['assets']['script'] = compact( 'h', 'src', 'deps', 'ver', 'footer' ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function wp_create_nonce( $action ) { return 'nonce'; }
function check_ajax_referer( $action, $field ) { $GLOBALS['nonce'] = $action === 'andy_commerce_orders_ui_v1' && $field === 'nonce'; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
function wp_unslash( $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function get_column_headers( $screen ) { return array(
    'cb' => '<input type="checkbox">', 'order_number' => 'Order', 'order_date' => 'Date',
    'order_status' => 'Status', 'andy_commerce_order_coupons' => '折扣码', 'order_total' => 'Total',
    'affiliate_referral' => 'Affiliate Referral', 'wc_actions' => 'Actions'
); }
function wp_send_json_error( $data, $status = 200 ) { throw new RuntimeException( 'error:' . $status ); }
function wp_send_json_success( $data ) { throw new RuntimeException( 'success' ); }
require dirname( __DIR__ ) . '/includes/class-andy-commerce-orders-ui.php';
$ok = static function ( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); }
};
Andy_Commerce_Orders_UI::boot();
foreach ( array( 'admin_enqueue_scripts', 'admin_body_class', 'screen_settings',
    'manage_woocommerce_page_wc-orders_columns', 'manage_edit-shop_order_columns',
    'wp_ajax_andy_commerce_orders_ui_save' ) as $hook ) {
    $ok( isset( $GLOBALS['hooks'][ $hook ] ), 'missing ' . $hook );
}
$ok( Andy_Commerce_Orders_UI::on_orders_screen( $GLOBALS['screen'] ), 'HPOS supported' );
$ok( Andy_Commerce_Orders_UI::on_orders_screen( (object) array( 'id' => 'edit-shop_order' ) ), 'legacy supported' );
$ok( ! Andy_Commerce_Orders_UI::on_orders_screen( (object) array( 'id' => 'dashboard' ) ), 'scope' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( 'wrong' ), 'reject not array' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'grid' ) ), 'reject bad mode' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array( 'cb' ) ) ), 'protect bulk checkbox' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array( '../bad' ) ) ), 'sanitize ids' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array_fill( 0, 41, 'x' ) ) ), 'limit size' );
$clean = Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array( 'order_date', 'order_total', 'order_date' ) ) );
$ok( $clean === array( 'layout' => 'cards', 'columns' => array( 'order_date', 'order_total' ) ), 'deduplicate' );

Andy_Commerce_Orders_UI::enqueue();
$ok( isset( $GLOBALS['assets']['style'], $GLOBALS['assets']['script'] ), 'assets loaded' );
$ok( str_contains( $GLOBALS['assets']['style']['src'], 'orders-ui-v1-2.css' ), 'V1.2 initial CSS only' );
$ok( str_contains( $GLOBALS['assets']['script']['src'], 'orders-ui-v1-2.js' ), 'V1.2 screen controls only' );
$ok( $GLOBALS['writes'] === 0, 'loading writes nothing' );
$ok( str_contains( Andy_Commerce_Orders_UI::body_class( 'wp-admin' ), 'ac-orders-layout-standard' ), 'initial server class' );

$heads = get_column_headers( $GLOBALS['screen'] );
$ok( array_keys( Andy_Commerce_Orders_UI::order_columns( $heads ) ) === array_keys( $heads ), 'native initial column order' );
$options = Andy_Commerce_Orders_UI::screen_settings( '<p>native options</p>', $GLOBALS['screen'] );
foreach ( array( 'native options', '订单展示方式', '订单列排序', 'ac_orders_layout', '保存布局并刷新', '折扣码', 'Order' ) as $item ) {
    $ok( str_contains( $options, $item ), 'native settings includes ' . $item );
}
$ok( ! str_contains( $options, '<form' ), 'no nested screen options form' );

$GLOBALS['meta'][ Andy_Commerce_Orders_UI::META_KEY ] = array( 'layout' => 'cards', 'columns' => array( 'order_total', 'andy_commerce_order_coupons' ) );
$keys = array_keys( Andy_Commerce_Orders_UI::order_columns( $heads ) );
$ok( $keys[0] === 'cb' && $keys[1] === 'order_total' && $keys[2] === 'andy_commerce_order_coupons'
    && $keys[count($keys)-1] === 'wc_actions', 'server-rendered column order pins checkbox/actions' );
$ok( str_contains( Andy_Commerce_Orders_UI::body_class( 'wp-admin' ), 'ac-orders-layout-cards' ), 'server-rendered cards' );
$ok( str_contains( Andy_Commerce_Orders_UI::screen_settings( '', $GLOBALS['screen'] ), 'value="cards" checked="checked"' ), 'correct radio first paint' );

$GLOBALS['screen'] = (object) array( 'id' => 'dashboard' );
$GLOBALS['assets'] = array();
Andy_Commerce_Orders_UI::enqueue();
$ok( empty( $GLOBALS['assets'] ), 'no assets outside Orders' );
$ok( Andy_Commerce_Orders_UI::screen_settings( 'unchanged', $GLOBALS['screen'] ) === 'unchanged', 'other screen unchanged' );
$GLOBALS['screen'] = (object) array( 'id' => 'edit-shop_order' );
$GLOBALS['assets'] = array();
Andy_Commerce_Orders_UI::enqueue();
$ok( isset( $GLOBALS['assets']['style'] ), 'legacy CSS' );
$GLOBALS['can'] = false;
$GLOBALS['assets'] = array();
Andy_Commerce_Orders_UI::enqueue();
$ok( empty( $GLOBALS['assets'] ), 'permission guard' );
$ok( Andy_Commerce_Orders_UI::order_columns( $heads ) === $heads, 'no reordering without permission' );
$GLOBALS['can'] = true;
$_POST = array( 'layout' => 'compact', 'columns' => array( 'order_date', 'order_total' ) );
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'success' === $e->getMessage(), 'save success' ); }
$ok( $GLOBALS['writes'] === 1 && $GLOBALS['nonce'], 'nonce and explicit save only' );
$_POST = array( 'layout' => 'bad' );
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'error:400' === $e->getMessage(), 'invalid returns 400' ); }
$ok( $GLOBALS['writes'] === 1, 'invalid request writes nothing' );
$GLOBALS['can'] = false;
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'error:403' === $e->getMessage(), 'permission' ); }
$ok( $GLOBALS['writes'] === 1, 'denied request writes nothing' );

$js = file_get_contents( dirname( __DIR__ ) . '/assets/js/orders-ui-v1-2.js' );
$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/orders-ui-v1-2.css' );
$ok( str_contains( $css, 'form:is(#posts-filter, #wc-orders-filter)' ), 'HPOS and legacy form selectors are both scoped before first paint' );
$ok( str_contains( $css, 'grid-column:2;grid-row:1;justify-self:end;' ), 'search aligns right on desktop first row' );
$ok( str_contains( $css, 'grid-column:1;grid-row:1;align-self:center;' ), 'native bulk and filters align left on same row' );
$ok( str_contains( $css, '.tablenav.top .tablenav-pages' ), 'top pagination hidden at first paint' );
$ok( str_contains( $css, '.tablenav.bottom .tablenav-pages' ), 'bottom native pagination kept' );
$ok( str_contains( $css, 'body.ac-orders-layout-cards' ), 'card view server class' );
$ok( str_contains( $js, 'ac-orders-native-options' ), 'only screen options interaction' );
$ok( ! str_contains( $js, 'appendChild(' ), 'no post-paint relocation' );
$ok( ! str_contains( $js, 'wp-list-table' ), 'no table DOM mutation' );
$bootstrap = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce.php' );
$root = file_get_contents( dirname( __DIR__ ) . '/andy-commerce.php' );
$ok( str_contains( $root, "Andy_Commerce_Orders_Features', 'register" ), 'Orders UI registered through Core Registry' );
$ok( ! str_contains( $bootstrap, 'Andy_Commerce_Orders_UI::boot();' ), 'Orders UI no longer boots unconditionally' );
echo 'PASS Orders UI V1.2 HPOS/legacy first paint, Screen Options, permissions and bottom pagination' . PHP_EOL;
