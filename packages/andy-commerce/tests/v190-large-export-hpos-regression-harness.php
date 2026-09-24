<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); }
function apply_filters( $tag, $value ) { return $value; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function get_userdata( $id ) { return 77 === (int) $id ? (object) array( 'user_email' => 'registered@example.com' ) : false; }
function wc_get_order_notes( $args ) {
	return 3 === (int) ( $args['order_id'] ?? 0 )
		? array( (object) array( 'content' => "Long note\nwith emoji 😀", 'date_created' => '2026-09-23 12:00:00', 'customer_note' => 0, 'added_by' => 'system' ) )
		: array();
}
class YBY_Woo_Order_Export_Module {
	public static function defaults() { return array( 'batch_size' => 200, 'bom' => false ); }
}
class V190Date {
	private $value;
	public function __construct( $value = '2026-09-23 10:00:00' ) { $this->value = $value; }
	public function date( $format ) { return $this->value; }
}
class V190Product {
	private $sku;
	public function __construct( $sku ) { $this->sku = $sku; }
	public function get_sku() { return $this->sku; }
}
class V190Item {
	private $id; private $variation; private $name; private $sku; private $qty;
	public function __construct( $id, $variation = 0, $name = 'Product', $sku = 'SKU', $qty = 1 ) {
		$this->id = $id; $this->variation = $variation; $this->name = $name; $this->sku = $sku; $this->qty = $qty;
	}
	public function get_product() { return new V190Product( $this->sku ); }
	public function get_product_id() { return $this->id; }
	public function get_variation_id() { return $this->variation; }
	public function get_name() { return $this->name; }
	public function get_quantity() { return $this->qty; }
	public function get_total() { return 19.5 * $this->qty; }
	public function get_subtotal() { return 20 * $this->qty; }
	public function get_data() { return array( 'product_id' => $this->id, 'variation_id' => $this->variation, 'quantity' => $this->qty ); }
}
class V190Fee {
	public function get_total() { return 2.5; }
	public function get_total_tax() { return 0.25; }
	public function get_data() { return array( 'total' => 2.5, 'total_tax' => 0.25 ); }
}
class V190Coupon {
	public function get_data() { return array( 'code' => 'coupon-code', 'discount' => 5 ); }
}
class V190Refund {
	public function get_data() { return array( 'id' => 9001, 'amount' => 4, 'reason' => 'refund-test' ); }
}
class V190Order {
	private $id; private $scenario;
	public function __construct( $id, $scenario = 'scale' ) { $this->id = $id; $this->scenario = $scenario; }
	public function get_id() { return $this->id; }
	public function get_order_number() { return 'ORD-' . $this->id; }
	public function get_date_created() { return new V190Date(); }
	public function get_date_paid() { return 'failed' === $this->scenario ? null : new V190Date( '2026-09-23 10:05:00' ); }
	public function get_status() { return in_array( $this->scenario, array( 'cancelled','failed' ), true ) ? $this->scenario : 'processing'; }
	public function get_currency() { return 'multi_currency' === $this->scenario ? 'EUR' : ( 'international' === $this->scenario ? 'JPY' : 'USD' ); }
	public function get_subtotal() { return 40; }
	public function get_discount_total() { return 'coupon' === $this->scenario ? 5 : 0; }
	public function get_total_discount() { return $this->get_discount_total(); }
	public function get_shipping_total() { return 5; }
	public function get_shipping_tax() { return 0.5; }
	public function get_total_tax() { return 1.5; }
	public function get_total() { return 46.5 - $this->get_discount_total(); }
	public function get_order_key() { return 'wc_order_key_' . $this->id; }
	public function get_payment_method() { return 'stripe'; }
	public function get_payment_method_title() { return 'Card'; }
	public function get_transaction_id() { return 'txn-' . $this->id; }
	public function get_customer_ip_address() { return '203.0.113.8'; }
	public function get_customer_user_agent() { return 'Regression Agent'; }
	public function get_shipping_method() { return 'Standard'; }
	public function get_customer_id() { return $this->get_user_id(); }
	public function get_user_id() { return 'guest' === $this->scenario ? 0 : 77; }
	public function get_billing_first_name() { return 'Ann'; }
	public function get_billing_last_name() { return 'Lee'; }
	public function get_billing_company() { return 'missing_company' === $this->scenario ? '' : 'BYL Customer'; }
	public function get_billing_email() { return 'buyer@example.com'; }
	public function get_billing_phone() { return 'missing_phone' === $this->scenario ? '' : '+1-555-0100'; }
	public function get_billing_address_1() { return '1 Main Street'; }
	public function get_billing_address_2() { return ''; }
	public function get_billing_postcode() { return '90001'; }
	public function get_billing_city() { return 'Los Angeles'; }
	public function get_billing_state() { return 'CA'; }
	public function get_billing_country() { return 'international' === $this->scenario ? 'JP' : 'US'; }
	public function get_shipping_first_name() { return 'Ann'; }
	public function get_shipping_last_name() { return 'Lee'; }
	public function get_shipping_company() { return 'missing_company' === $this->scenario ? '' : 'BYL Customer'; }
	public function get_shipping_phone() { return 'missing_phone' === $this->scenario ? '' : '+1-555-0100'; }
	public function get_shipping_address_1() { return 'international' === $this->scenario ? '東京都渋谷区1-2-3' : '1 Main Street'; }
	public function get_shipping_address_2() { return ''; }
	public function get_shipping_postcode() { return 'international' === $this->scenario ? '150-0001' : '90001'; }
	public function get_shipping_city() { return 'international' === $this->scenario ? '東京' : 'Los Angeles'; }
	public function get_shipping_state() { return 'international' === $this->scenario ? 'Tokyo' : 'CA'; }
	public function get_shipping_country() { return 'international' === $this->scenario ? 'JP' : 'US'; }
	public function get_customer_note() { return 'long_text' === $this->scenario ? str_repeat( 'Long free text ', 200 ) . '😀' : ''; }
	public function get_meta( $key, $single = true ) { return '_wc_order_attribution_utm_source' === $key ? 'regression' : ''; }
	public function is_download_permitted() { return false; }
	public function get_items( $type = 'line_item' ) {
		if ( 'line_item' === $type ) {
			if ( 'multi_item' === $this->scenario ) { return array( new V190Item( 1 ), new V190Item( 2 ) ); }
			if ( 'variable' === $this->scenario ) { return array( new V190Item( 3, 33, 'Variable Product', 'VAR-SKU' ) ); }
			return array( new V190Item( 1 ) );
		}
		if ( 'fee' === $type ) { return array( new V190Fee() ); }
		if ( 'coupon' === $type && 'coupon' === $this->scenario ) { return array( new V190Coupon() ); }
		return array();
	}
	public function get_refunds() { return 'refunded' === $this->scenario ? array( new V190Refund() ) : array(); }
}
class V190Adapter {
	public $passes = 0; private $orders;
	public function __construct( $orders ) { $this->orders = $orders; }
	public function iterate_orders( $filters, $batch = 200 ) { $this->passes++; foreach ( $this->orders as $order ) { yield $order; } }
}
class V190ScaleAdapter {
	public $passes = 0; private $count;
	public function __construct( $count ) { $this->count = $count; }
	public function iterate_orders( $filters, $batch = 200 ) {
		$this->passes++;
		for ( $i = 1; $i <= $this->count; $i++ ) { yield new V190Order( $i, 'scale' ); }
	}
}
require_once dirname( __DIR__ ) . '/inc/class-yby-woo-order-export-presets.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-woo-order-csv-streamer.php';
$assert = function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$scenarios = array( 'one','multi_item','variable','coupon','refunded','cancelled','failed','guest','registered','multi_currency','missing_phone','missing_company','international','long_text','emoji' );
$orders = array(); foreach ( $scenarios as $index => $scenario ) { $orders[] = new V190Order( $index + 1, $scenario ); }
$adapter = new V190Adapter( $orders ); $handle = tmpfile();
$stats = ( new YBY_Woo_Order_CSV_Streamer() )->stream( $handle, 'byl_full_order_report', array(), array( 'batch_size' => 200, 'bom' => false ), $adapter );
rewind( $handle ); $csv = stream_get_contents( $handle ); fclose( $handle );
$assert( 15 === $stats['order_count'] && 15 === $stats['row_count'] && 2 === $adapter->passes, 'scenario matrix count and two-pass streaming' );
foreach ( array( 'VAR-SKU','coupon-code','refund-test','cancelled','failed','EUR','JPY','東京','😀' ) as $needle ) { $assert( false !== strpos( $csv, $needle ), 'scenario output: ' . $needle ); }
$assert( false !== strpos( $csv, 'Product Item 2 Name' ), 'multi-item dynamic columns' );
$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-woo-order-query-adapter.php' ) . file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-woo-order-csv-streamer.php' );
foreach ( array( '$wpdb','wp_posts','wp_postmeta' ) as $forbidden ) { $assert( false === strpos( $source, $forbidden ), 'HPOS/storage abstraction: ' . $forbidden ); }
$assert( false !== strpos( $source, 'wc_get_orders' ), 'official Woo order API contract' );

foreach ( array( 1000, 5000, 10000 ) as $count ) {
	gc_collect_cycles(); $before = memory_get_usage( true ); $started = microtime( true );
	$scale = new V190ScaleAdapter( $count ); $handle = tmpfile();
	$scale_stats = ( new YBY_Woo_Order_CSV_Streamer() )->stream( $handle, 'byl_processing_orders', array(), array( 'batch_size' => 200, 'bom' => false ), $scale );
	$elapsed = microtime( true ) - $started; fclose( $handle ); gc_collect_cycles(); $after = memory_get_usage( true );
	$delta = max( 0, $after - $before );
	$assert( $count === $scale_stats['order_count'] && $count === $scale_stats['row_count'], "scale {$count} counts" );
	$assert( 2 === $scale->passes, "scale {$count} two passes" );
	$assert( $delta <= 32 * 1024 * 1024, "scale {$count} bounded memory" );
	echo 'METRIC orders=' . $count . ' batch=200 elapsed_s=' . number_format( $elapsed, 3, '.', '' ) . ' memory_delta_mb=' . number_format( $delta / 1048576, 2, '.', '' ) . "\n";
}
echo "PASS v190-large-export-hpos-regression-harness\n";
