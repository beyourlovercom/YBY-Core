<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); }
function apply_filters( $tag, $value ) { return $value; }
function get_userdata( $id ) { return $id ? (object) array( 'user_email' => 'registered@example.com' ) : false; }
function wc_get_order_notes( $args ) {
    return array( (object) array( 'content' => "Packed\nnow", 'date_created' => '2026-09-23 11:00:00', 'customer_note' => 1, 'added_by' => 'operator' ) );
}
class YBY_Woo_Order_Export_Module { public static function defaults() { return array( 'batch_size' => 200, 'bom' => false ); } }
class BylDate { public function date( $format ) { return '2026-09-23 10:00:00'; } }
class BylProduct { public function get_sku() { return '=BAD-SKU'; } }
class BylFee { public function get_total() { return 3.25; } public function get_total_tax() { return 0.25; } }
class BylTax { public function get_rate_id() { return 4; } public function get_rate_code() { return 'VAT-20'; } public function get_tax_total() { return 1.2; } public function get_label() { return 'VAT'; } public function get_compound() { return false; } }
class BylShipping { public function get_meta( $key, $single = true ) { return array( 'Items' => '1x Standard', 'method_id' => 'flat_rate', 'taxes' => array( '4' => '1.20' ) )[ $key ] ?? ''; } }
class BylCoupon { public function get_code() { return 'SAVE'; } }
class WC_Coupon { public function __construct( $code ) {} public function get_amount() { return 7.5; } }
class BylRefund { public function get_amount() { return 4; } public function get_reason() { return 'Changed mind'; } public function get_date_created() { return new BylDate(); } }
class BylItem {
    public function get_product() { return new BylProduct(); }
    public function get_product_id() { return 9; }
    public function get_variation_id() { return 0; }
    public function get_name() { return '+BAD-NAME &amp; FRIEND'; }
    public function get_quantity() { return 2; }
    public function get_total() { return 18; }
    public function get_subtotal() { return 20; }
}
class BylOrder {
    private $items;
    public function __construct( $items ) { $this->items = $items; }
    public function get_items( $type = 'line_item' ) {
        if ( 'line_item' === $type ) { return $this->items; }
        if ( 'fee' === $type ) { return array( new BylFee() ); }
        return array();
    }
    public function get_id() { return 101; }
    public function get_order_number() { return '=ORDER'; }
    public function get_date_created() { return new BylDate(); }
    public function get_date_paid() { return null; }
    public function get_status() { return 'processing'; }
    public function get_currency() { return 'USD'; }
    public function get_total() { return 23; }
    public function get_subtotal() { return 20; }
    public function get_discount_total() { return 2; }
    public function get_total_discount() { return 2; }
    public function get_shipping_total() { return 5; }
    public function get_shipping_tax() { return 0; }
    public function get_total_tax() { return 0; }
    public function get_customer_id() { return 77; }
    public function get_user_id() { return 77; }
    public function get_billing_email() { return 'billing@example.com'; }
    public function get_shipping_first_name() { return 'Ann'; }
    public function get_shipping_last_name() { return 'Lee'; }
    public function get_meta( $key, $single = true ) { return '_wc_order_attribution_utm_source' === $key ? 'newsletter' : ''; }
    public function is_download_permitted() { return false; }
}
class BylAdapter {
    public $passes = 0;
    private $orders;
    public function __construct( $orders ) { $this->orders = $orders; }
    public function iterate_orders( $filters, $batch = 200 ) { $this->passes++; foreach ( $this->orders as $order ) { yield $order; } }
}
class BylRichOrder extends BylOrder {
    public function get_items( $type = 'line_item' ) {
        if ( 'fee' === $type ) { return array( new BylFee() ); }
        if ( 'tax' === $type ) { return array( new BylTax() ); }
        if ( 'shipping' === $type ) { return array( new BylShipping() ); }
        if ( 'coupon' === $type ) { return array( new BylCoupon() ); }
        return parent::get_items( $type );
    }
    public function get_coupon_codes() { return array( 'SAVE' ); }
    public function get_refunds() { return array( new BylRefund() ); }
}
require_once dirname( __DIR__ ) . '/includes/class-yby-woo-order-export-presets.php';
require_once dirname( __DIR__ ) . '/includes/class-yby-woo-order-csv-streamer.php';
$assert = function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$p = YBY_Woo_Order_Export_Presets::presets();
$assert( array_keys( $p ) === array( 'default','full','byl_processing_orders','byl_full_order_report' ), 'exact preset ids' );
$assert( $p['byl_processing_orders']['label'] === 'BYL Processing Orders' && $p['byl_processing_orders']['row_mode'] === 'order_row', 'processing preset' );
$assert( $p['byl_full_order_report']['label'] === 'BYL Full Report' && $p['byl_full_order_report']['row_mode'] === 'order_row', 'full preset' );
$assert( $p['default']['row_mode'] === 'line_item' && $p['full']['row_mode'] === 'line_item', 'generic row modes unchanged' );

$generic_full = array( 'order_id','order_number','order_date','paid_date','status','currency','subtotal','discount','shipping','tax','total','payment_method','transaction_id','customer_id','customer_email','billing_first_name','billing_last_name','billing_company','billing_email','billing_phone','billing_address_1','billing_address_2','billing_city','billing_state','billing_postcode','billing_country','shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postcode','shipping_country','product_id','variation_id','sku','product_name','quantity','line_subtotal','line_total' );
$assert( $p['full']['columns'] === $generic_full, 'generic full columns frozen' );

$processing = array( 'order_number','order_date','status','order_total','order_currency','customer_email','billing_email','billing_phone','shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_postcode','shipping_city','shipping_state','shipping_country' );
$assert( $p['byl_processing_orders']['columns'] === $processing, 'processing exact audited order' );
$assert( $p['byl_processing_orders']['header_labels'] === array_combine( $processing, $processing ), 'processing exact audited headers' );

$expected_full = array( 'order_id','order_number','order_date','paid_date','status','shipping_total','shipping_tax_total','fee_total','fee_tax_total','tax_total','cart_discount','order_discount','discount_total','order_total','order_subtotal','order_key','order_currency','payment_method','payment_method_title','transaction_id','customer_ip_address','customer_user_agent','shipping_method','customer_id','customer_user','customer_email','billing_first_name','billing_last_name','billing_company','billing_email','billing_phone','billing_address_1','billing_address_2','billing_postcode','billing_city','billing_state','billing_country','shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_postcode','shipping_city','shipping_state','shipping_country','customer_note','wt_import_key','tax_items','shipping_items','fee_items','coupon_items','refund_items','order_notes','download_permissions','meta:_wc_order_attribution_device_type','meta:_wc_order_attribution_referrer','meta:_wc_order_attribution_session_count','meta:_wc_order_attribution_session_entry','meta:_wc_order_attribution_session_pages','meta:_wc_order_attribution_session_start_time','meta:_wc_order_attribution_source_type','meta:_wc_order_attribution_user_agent','meta:_wc_order_attribution_utm_source' );
$full = $p['byl_full_order_report']['columns'];
$assert( $full === $expected_full, 'audited full semantic order' );
$assert( $p['byl_full_order_report']['header_labels'] === array_combine( $expected_full, $expected_full ), 'full exact audited headers' );
$assert( in_array( 'wt_import_key', $full, true ), 'wt_import_key retained for read-only compatibility' );
$assert( false === strpos( implode( ',', $full ), '_stripe' ) && false === strpos( implode( ',', $full ), '_ppcp' ), 'no stale provider fields' );

$adapter = new BylAdapter( array( new BylOrder( array( new BylItem(), new BylItem() ) ) ) );
$h = fopen( 'php://temp', 'w+' );
$stats = ( new YBY_Woo_Order_CSV_Streamer() )->stream( $h, 'byl_processing_orders', array(), array( 'batch_size' => 200, 'bom' => false ), $adapter );
rewind( $h );
$lines = array_values( array_filter( explode( "\n", trim( stream_get_contents( $h ) ) ) ) );
fclose( $h );
$header = str_getcsv( $lines[0] );
$row = str_getcsv( $lines[1] );
$assert( $stats['order_count'] === 1 && $stats['row_count'] === 1 && $adapter->passes === 2, 'bounded two-pass one-row export' );
$assert( array_slice( $header, 0, 18 ) === $processing, 'processing CSV base headers exact' );
$assert( array_slice( $header, 18 ) === array( 'line_item_1','line_item_2','Product Item 1 Name','Product Item 1 id','Product Item 1 SKU','Product Item 1 Quantity','Product Item 1 Total','Product Item 1 Subtotal','Product Item 2 Name','Product Item 2 id','Product Item 2 SKU','Product Item 2 Quantity','Product Item 2 Total','Product Item 2 Subtotal' ), 'legacy dynamic header order' );
$joined = implode( ',', $row );
$assert( false !== strpos( $joined, "'=ORDER" ) && false !== strpos( $joined, "'+BAD-NAME & FRIEND" ) && false !== strpos( $joined, "'=BAD-SKU" ), 'formula injection protection and HTML entity decode' );
$assert( in_array( 'registered@example.com', $row, true ), 'BYL customer_email preserves registered-user semantics' );
$row_map = array_combine( $header, $row );
$assert( 'name:+BAD-NAME & FRIEND|product_id:9|sku:=BAD-SKU|quantity:2|total:18.00|sub_total:20.00' === $row_map['line_item_1'], 'legacy line item serialization and decimals' );

$empty_adapter = new BylAdapter( array( new BylOrder( array() ) ) );
$h = fopen( 'php://temp', 'w+' );
$stats = ( new YBY_Woo_Order_CSV_Streamer() )->stream( $h, 'byl_full_order_report', array(), array( 'batch_size' => 200, 'bom' => false ), $empty_adapter );
rewind( $h );
$lines = array_values( array_filter( explode( "\n", trim( stream_get_contents( $h ) ) ) ) );
fclose( $h );
$assert( $stats['order_count'] === 1 && $stats['row_count'] === 1 && $empty_adapter->passes === 2, 'empty order emits one row with two bounded passes' );
$full_header = str_getcsv( $lines[0] ); $full_row = str_getcsv( $lines[1] );
$assert( $full_header === $expected_full && count( $full_row ) === count( $expected_full ), 'full CSV base headers and width exact' );
$full_map = array_combine( $full_header, $full_row );
$assert( '77' === (string) $full_map['customer_id'] && '77' === $full_map['customer_user'], 'registered customer id semantics' );
$assert( 'registered@example.com' === $full_map['customer_email'], 'registered customer email semantics' );
$assert( '3.25' === $full_map['fee_total'] && '0.25' === $full_map['fee_tax_total'], 'fee totals use fee items' );
$assert( '' === $full_map['tax_items'] && '' === $full_map['shipping_items'] && '' === $full_map['coupon_items'] && '' === $full_map['refund_items'], 'empty legacy collections are blank' );
$assert( "'=ORDER" === $full_map['wt_import_key'], 'wt_import_key preserves formula-safe order number' );
$assert( false !== strpos( $full_map['order_notes'], 'content:Packed now|date:2026-09-23 11:00:00|customer:1|added_by:operator' ), 'legacy note serialization' );
$assert( 'newsletter' === $full_map['meta:_wc_order_attribution_utm_source'], 'attribution meta value' );

$rich_adapter = new BylAdapter( array( new BylRichOrder( array( new BylItem() ) ) ) );
$h = fopen( 'php://temp', 'w+' );
( new YBY_Woo_Order_CSV_Streamer() )->stream( $h, 'byl_full_order_report', array(), array( 'batch_size' => 200, 'bom' => false ), $rich_adapter );
rewind( $h ); $rich_lines = array_values( array_filter( explode( "\n", trim( stream_get_contents( $h ) ) ) ) ); fclose( $h );
$rich_map = array_combine( str_getcsv( $rich_lines[0] ), str_getcsv( $rich_lines[1] ) );
$assert( 'items:1x Standard|method_id:flat_rate|taxes:{"4":"1.20"}' === $rich_map['shipping_items'], 'legacy shipping serialization' );
$assert( 'name:|total:3.25|tax:0.25|tax_data:null' === $rich_map['fee_items'], 'legacy fee serialization' );
$assert( 'rate_id:4|code:VAT-20|total:1.20|label:VAT|tax_rate_compound:' === $rich_map['tax_items'], 'legacy tax serialization' );
$assert( 'code:SAVE|amount:7.50' === $rich_map['coupon_items'], 'legacy coupon amount semantics' );
$assert( 'amount:4|reason:Changed mind|date:2026-09-23 10:00:00' === $rich_map['refund_items'], 'legacy refund serialization' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-yby-woo-order-csv-streamer.php' ) . file_get_contents( dirname( __DIR__ ) . '/includes/class-yby-woo-order-export-presets.php' );
foreach ( array( 'update_post_meta', 'update_meta_data', '->save(', 'set_status', 'wf_order_exported_status' ) as $forbidden ) {
    $assert( false === strpos( $source, $forbidden ), 'no business write: ' . $forbidden );
}
echo "PASS v190-byl-preset-migration-harness\n";
