<?php
if(!defined('ABSPATH'))define('ABSPATH',__DIR__);
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function sanitize_text_field($v){return trim(strip_tags((string)$v));}
function sanitize_email($v){return filter_var((string)$v,FILTER_SANITIZE_EMAIL);}
function absint($v){return abs((int)$v);}
function apply_filters($tag,$value){return $value;}
$GLOBALS['wc_pages']=array();
$GLOBALS['wc_calls']=array();
function wc_get_orders($args){$GLOBALS['wc_calls'][]=$args;$page=$args['page'];return $GLOBALS['wc_pages'][$page]??(object)array('orders'=>array(),'max_num_pages'=>1);}
require_once dirname(__DIR__).'/inc/class-yby-woo-order-query-adapter.php';
$a=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$f=YBY_Woo_Order_Query_Adapter::sanitize_filters(array('date_from'=>'2026-09-30','date_to'=>'2026-09-01','statuses'=>array('wc-completed','wc-processing','WC-COMPLETED'),'order_ids'=>'12, 13;12 bad 14','customer_email'=>'a@example.com','currency'=>'usd','payment_method'=>'Stripe Card'));
$a($f['date_from']==='2026-09-01'&&$f['date_to']==='2026-09-30','date swap');
$a($f['order_ids']===array(12,13,14),'order IDs');
$a($f['currency']==='USD','currency');
$a($f['payment_method']==='stripecard','payment sanitize');
$q=YBY_Woo_Order_Query_Adapter::build_query_args($f,2,999);
$a($q['limit']===500&&$q['page']===2&&$q['paginate']===true&&$q['return']==='objects','bounded pagination');
$a($q['date_created']==='2026-09-01...2026-09-30','date query');
$a($q['billing_email']==='a@example.com'&&$q['currency']==='USD','official query fields');
$GLOBALS['wc_pages'][1]=(object)array('orders'=>array('A','B'),'max_num_pages'=>2);
$GLOBALS['wc_pages'][2]=(object)array('orders'=>array('C'),'max_num_pages'=>2);
$rows=iterator_to_array((new YBY_Woo_Order_Query_Adapter())->iterate_orders(array(),200),false);
$a($rows===array('A','B','C'),'paginated iterator');
$a(count($GLOBALS['wc_calls'])===2,'two bounded calls');
echo "PASS v190-woo-query-adapter-harness\n";
