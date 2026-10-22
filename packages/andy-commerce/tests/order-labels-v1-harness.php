<?php
/** Andy Commerce native HPOS label contract; no order mutations. */
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ );
if ( ! defined( 'ANDY_COMMERCE_PLUGIN_URL' ) ) define( 'ANDY_COMMERCE_PLUGIN_URL', 'https://dev.example.test/plugins/andy-commerce/' );
if ( ! defined( 'ANDY_COMMERCE_VERSION' ) ) define( 'ANDY_COMMERCE_VERSION', '1.0.0' );
$GLOBALS['hooks'] = array();
$GLOBALS['screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
$GLOBALS['allowed'] = true;
$GLOBALS['assets'] = array();
function is_admin() { return true; }
function get_current_screen() { return $GLOBALS['screen']; }
function current_user_can( $cap ) { return $GLOBALS['allowed']; }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $s ) ); }
function wp_unslash( $s ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook] = $cb; }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook] = $cb; }
function wp_enqueue_script( $key, $url, $deps, $ver, $footer ) { $GLOBALS['assets'][] = compact( 'key', 'url', 'deps', 'ver', 'footer' ); }
class WC_Order {
    private $id;
    private $items;
    public function __construct( $id, $items ) { $this->id = $id; $this->items = $items; }
    public function get_id() { return $this->id; }
    public function get_items( $type = '' ) { return $this->items; }
}
class FakeProduct {
    private $sku;
    public function __construct( $sku ) { $this->sku = $sku; }
    public function get_sku() { return $this->sku; }
}
class FakeItem {
    private $product;
    public function __construct( $product ) { $this->product = $product; }
    public function get_product() { return $this->product; }
}
require dirname( __DIR__ ) . '/includes/class-andy-commerce-order-labels.php';
$ok = static function ( $cond, $msg ) { if ( ! $cond ) { fwrite( STDERR, "FAIL: $msg\n" ); exit( 1 ); } };
Andy_Commerce_Order_Labels::boot();
foreach ( array( 'woocommerce_admin_order_buyer_name', 'woocommerce_order_number', 'gettext', 'admin_enqueue_scripts' ) as $hook ) {
    $ok( isset( $GLOBALS['hooks'][$hook] ), "hook $hook" );
}
$order = new WC_Order( 39935, array(
    new FakeItem( new FakeProduct( '414sleeve' ) ),
    new FakeItem( new FakeProduct( 'TX640A' ) ),
    new FakeItem( null ),
) );
$_GET = array( 'page' => 'wc-orders' );
$ok( Andy_Commerce_Order_Labels::list_skus( 'Buyer Name', $order ) === '414sleeve TX640A', 'list SKUs in actual item order' );
$ok( Andy_Commerce_Order_Labels::native_numeric_number( '39935-old-suffix', $order ) === '39935', 'list number is numeric ID' );
$ok( Andy_Commerce_Order_Labels::list_skus( 'Buyer Name', new WC_Order( 10, array() ) ) === '', 'no sku yields just ID' );
$ok( Andy_Commerce_Order_Labels::detail_heading_format( '%1$s #%2$s', '%1$s #%2$s', 'woocommerce' ) === '%1$s #%2$s', 'list does not affect detail' );
Andy_Commerce_Order_Labels::list_script();
$ok( count( $GLOBALS['assets'] ) === 1 && strpos( $GLOBALS['assets'][0]['url'], 'orders-list-labels-v1.js' ) !== false, 'only native list script' );
$_GET['action'] = 'edit';
$_GET['id'] = '39936';
$ok( Andy_Commerce_Order_Labels::list_skus( 'Buyer', $order ) === 'Buyer', 'detail does not rewrite buyer' );
$ok( Andy_Commerce_Order_Labels::native_numeric_number( '39935-bear', $order ) === '39935', 'detail number numeric ID' );
$format = Andy_Commerce_Order_Labels::detail_heading_format( '%1$s #%2$s', '%1$s #%2$s', 'woocommerce' );
$ok( sprintf( $format, 'Order', '39936' ) === '订单 39936', 'native server-rendered detail heading' );
$ok( Andy_Commerce_Order_Labels::detail_heading_format( 'unrelated', 'other', 'woocommerce' ) === 'unrelated', 'other translations unchanged' );
$ok( Andy_Commerce_Order_Labels::detail_heading_format( 'other', '%1$s #%2$s', 'another-domain' ) === 'other', 'other domains unchanged' );
$GLOBALS['assets'] = array();
Andy_Commerce_Order_Labels::list_script();
$ok( count( $GLOBALS['assets'] ) === 0, 'no list JS on edit' );
$_GET['action'] = 'trash';
$ok( Andy_Commerce_Order_Labels::native_numeric_number( 'custom', $order ) === 'custom', 'non-edit action untouched' );
$_GET = array( 'page' => 'wc-orders' );
$GLOBALS['screen'] = (object) array( 'id' => 'dashboard' );
$ok( Andy_Commerce_Order_Labels::list_skus( 'Buyer', $order ) === 'Buyer', 'not on dashboard' );
$GLOBALS['screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
$GLOBALS['allowed'] = false;
$ok( Andy_Commerce_Order_Labels::native_numeric_number( 'original', $order ) === 'original', 'capability guarded' );
$bootstrap = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce.php' );
$features = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce-orders-features.php' );
$ok( strpos( $features, 'Andy_Commerce_Order_Labels::boot_list();' ) !== false
    && strpos( $features, 'Andy_Commerce_Order_Labels::boot_detail();' ) !== false, 'both Core module boot callbacks' );
$ok( strpos( $bootstrap, 'Andy_Commerce_Order_Labels::boot();' ) === false, 'no unconditional labels boot' );
echo "PASS Andy Commerce HPOS detail/order-list display-only labels\n";
