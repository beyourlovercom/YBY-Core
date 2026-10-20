<?php
/**
 * Order Coupon Column V1: offline HPOS / legacy and read-only regression.
 *
 * No WordPress database, orders, payments, options or coupons are mutated.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['coupon_test_hooks'] = array();
$GLOBALS['coupon_test_orders'] = array();
$GLOBALS['coupon_test_legacy_reads'] = 0;

function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['coupon_test_hooks']['filter'][ $name ] = array( $callback, $priority, $accepted_args );
}
function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['coupon_test_hooks']['action'][ $name ] = array( $callback, $priority, $accepted_args );
}
function __( $text, $domain = '' ) { return $text; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
class WC_Order {
	private $codes;
	public function __construct( $codes ) { $this->codes = $codes; }
	public function get_coupon_codes() { return $this->codes; }
}
function wc_get_order( $id ) {
	++$GLOBALS['coupon_test_legacy_reads'];
	return $GLOBALS['coupon_test_orders'][ $id ] ?? false;
}

require dirname( __DIR__ ) . '/includes/class-andy-commerce-order-coupon-column.php';

$assert = static function ( $condition, $reason ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: " . $reason . PHP_EOL ); exit( 1 ); }
};

Andy_Commerce_Order_Coupon_Column::boot();
$hooks = $GLOBALS['coupon_test_hooks'];
$assert( isset( $hooks['filter']['manage_woocommerce_page_wc-orders_columns'] ), 'HPOS column registration' );
$assert( isset( $hooks['action']['manage_woocommerce_page_wc-orders_custom_column'] ), 'HPOS render hook' );
$assert( $hooks['action']['manage_woocommerce_page_wc-orders_custom_column'][2] === 2, 'HPOS callback args' );
$assert( isset( $hooks['filter']['manage_edit-shop_order_columns'] ), 'legacy column registration' );
$assert( isset( $hooks['action']['manage_shop_order_posts_custom_column'] ), 'legacy render hook' );

$input = array( 'cb' => 'Select', 'order_number' => 'Order', 'order_status' => 'Status', 'order_total' => 'Total' );
$with_column = Andy_Commerce_Order_Coupon_Column::add_column( $input );
$assert( array_keys( $with_column ) === array( 'cb', 'order_number', 'order_status', Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, 'order_total' ), 'column before Total' );
$assert( $with_column[ Andy_Commerce_Order_Coupon_Column::COLUMN_KEY ] === '折扣码', 'column heading' );
$assert( $input === array( 'cb' => 'Select', 'order_number' => 'Order', 'order_status' => 'Status', 'order_total' => 'Total' ), 'input not mutated' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( $with_column ) === $with_column, 'idempotent' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( array( 'status' => 'Status', 'total' => 'Total' ) ) === array( 'status' => 'Status', Andy_Commerce_Order_Coupon_Column::COLUMN_KEY => '折扣码', 'total' => 'Total' ), 'fallback total placement' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( array( 'status' => 'Status' ) ) === array( 'status' => 'Status', Andy_Commerce_Order_Coupon_Column::COLUMN_KEY => '折扣码' ), 'fallback end placement' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( 'invalid' ) === 'invalid', 'invalid columns fail closed' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( array( 'smart_coupons' => '折扣码', 'order_total' => 'Total' ) ) === array( 'smart_coupons' => '折扣码', 'order_total' => 'Total' ), 'Smart Coupons coexistence avoids duplicate' );
$assert( Andy_Commerce_Order_Coupon_Column::add_column( array( 'coupon_used' => 'Discounts', 'order_total' => 'Total' ) ) === array( 'coupon_used' => 'Discounts', 'order_total' => 'Total' ), 'existing coupon key avoids duplicate' );

$order = new WC_Order( array( 'greensoul', 'inkshrimp', '<script>alert("x")</script>' ) );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_hpos( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, $order );
$html = ob_get_clean();
$assert( substr_count( $html, 'class="andy-commerce-order-coupon"' ) === 3, 'all codes displayed' );
$assert( false !== strpos( $html, 'greensoul' ) && false !== strpos( $html, 'inkshrimp' ), 'code text' );
$assert( false === strpos( $html, '<script>' ) && false !== strpos( $html, '&lt;script&gt;' ), 'HTML escaping' );
$assert( 0 === $GLOBALS['coupon_test_legacy_reads'], 'HPOS must use supplied order object without per-row lookup' );

ob_start();
Andy_Commerce_Order_Coupon_Column::render_hpos( 'other_column', $order );
$assert( '' === ob_get_clean(), 'unrelated column untouched' );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_hpos( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, new WC_Order( array() ) );
$assert( '&mdash;' === ob_get_clean(), 'no-coupon order dash' );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_hpos( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, new WC_Order( array( '', null ) ) );
$assert( '&mdash;' === ob_get_clean(), 'invalid/empty coupon codes dash' );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_hpos( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, false );
$assert( '&mdash;' === ob_get_clean(), 'non-order fail closed' );

$GLOBALS['coupon_test_orders'][42] = new WC_Order( array( 'mir20' ) );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_legacy( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, 42 );
$assert( false !== strpos( ob_get_clean(), 'mir20' ), 'legacy order coupon via WC order API' );
$assert( $GLOBALS['coupon_test_legacy_reads'] === 1, 'legacy does one order lookup' );
ob_start();
Andy_Commerce_Order_Coupon_Column::render_legacy( Andy_Commerce_Order_Coupon_Column::COLUMN_KEY, -1 );
$assert( '&mdash;' === ob_get_clean() && $GLOBALS['coupon_test_legacy_reads'] === 1, 'bad order ID no query' );

$bootstrap = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce.php' );
$assert( false !== strpos( $bootstrap, 'Andy_Commerce_Order_Coupon_Column::boot()' ), 'Commerce-only admin bootstrap' );
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce-order-coupon-column.php' );
foreach ( array( 'update_post_meta(', 'update_option(', 'save(', 'set_coupon', 'wp_insert_post(', '$wpdb' ) as $forbidden ) {
	$assert( false === strpos( $source, $forbidden ), 'read-only source excludes writes: ' . $forbidden );
}
echo "PASS andy-commerce-order-coupon-column-harness (HPOS/legacy, XSS, coexistence, read-only)" . PHP_EOL;
