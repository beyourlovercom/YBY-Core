<?php
if(!defined('ABSPATH'))define('ABSPATH',__DIR__);
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function wp_parse_args($a,$d=array()){return array_merge($d,is_array($a)?$a:array());}
function apply_filters($t,$v){return $v;}
class FakeDate{private $v;function __construct($v){$this->v=$v;}function date($f){return $this->v;}}
class FakeProduct{function get_sku(){return '=DANGEROUS-SKU';}}
class FakeItem{function get_product(){return new FakeProduct();}function get_product_id(){return 9;}function get_variation_id(){return 10;}function get_name(){return '+Formula Product';}function get_quantity(){return 2;}function get_subtotal(){return 20;}function get_total(){return 18;}}
class FakeOrder{function get_items($t=''){return array(new FakeItem());}function get_id(){return 101;}function get_order_number(){return '007-101';}function get_date_created(){return new FakeDate('2026-09-23 10:00:00');}function get_date_paid(){return null;}function get_status(){return 'processing';}function get_currency(){return 'USD';}function get_subtotal(){return 20;}function get_discount_total(){return 2;}function get_shipping_total(){return 5;}function get_total_tax(){return 0;}function get_total(){return 23;}function get_payment_method(){return 'stripe';}function get_transaction_id(){return 'txn_1';}function get_customer_id(){return 77;}function get_billing_email(){return 'buyer@example.com';}function get_billing_first_name(){return 'Ann';}function get_billing_last_name(){return 'Lee';}function get_billing_company(){return '=SUM(1,1)';}function get_billing_phone(){return '+123456';}function get_billing_address_1(){return '1, Main "Road"';}function get_billing_address_2(){return "Suite\n2";}function get_billing_city(){return 'LA';}function get_billing_state(){return 'CA';}function get_billing_postcode(){return '90001';}function get_billing_country(){return 'US';}function get_shipping_first_name(){return 'Ann';}function get_shipping_last_name(){return 'Lee';}function get_shipping_company(){return '@Warehouse';}function get_shipping_phone(){return '+123456';}function get_shipping_address_1(){return '1 Main';}function get_shipping_address_2(){return '';}function get_shipping_city(){return 'LA';}function get_shipping_state(){return 'CA';}function get_shipping_postcode(){return '90001';}function get_shipping_country(){return 'US';}}
class FakeAdapter{function iterate_orders($f,$b=200){yield new FakeOrder();}}
class YBY_Woo_Order_Export_Module{static function defaults(){return array('batch_size'=>200,'bom'=>true);}}
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-export-presets.php';
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-csv-streamer.php';
$a=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$h=fopen('php://temp','w+');$stats=(new YBY_Woo_Order_CSV_Streamer())->stream($h,'full',array(),array('batch_size'=>200,'bom'=>true),new FakeAdapter());rewind($h);$csv=stream_get_contents($h);fclose($h);
$a(substr($csv,0,3)==="\xEF\xBB\xBF",'BOM');
$a($stats['order_count']===1&&$stats['row_count']===1,'stats');
$a(strpos($csv,"'=SUM(1,1)")!==false,'formula company protection');
$a(strpos($csv,"'=DANGEROUS-SKU")!==false,'formula SKU protection');
$a(strpos($csv,"'+Formula Product")!==false,'formula product protection');
$a(strpos($csv, '"1, Main ""Road"""')!==false,'CSV quote/comma escaping');
$a(strpos($csv,"Suite\n2")!==false,'newline safe');
echo "PASS v190-csv-stream-harness\n";
