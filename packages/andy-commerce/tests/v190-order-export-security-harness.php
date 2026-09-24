<?php
if(!defined('ABSPATH'))define('ABSPATH',__DIR__);
function sanitize_text_field($v){return trim(strip_tags((string)$v));}function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}function absint($v){return abs((int)$v);}function sanitize_email($v){return filter_var((string)$v,FILTER_SANITIZE_EMAIL);}function get_option($k,$d=false){return $d;}function update_option($k,$v,$a=false){$GLOBALS['audit_saved']=$v;return true;}
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-query-adapter.php';
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-export-audit.php';
$a=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$summary=YBY_Woo_Order_Export_Audit::summarize_filters(array('customer_email'=>'secret@example.com','order_ids'=>'1,2','date_from'=>'2026-09-01'));
$a($summary['has_customer_email_filter']===true&&!isset($summary['customer_email']),'audit must not store email value');
YBY_Woo_Order_Export_Audit::record(array('export_id'=>'x','admin_user_id'=>4,'preset'=>'default','filters'=>$summary,'order_count'=>2,'row_count'=>3,'duration_ms'=>10,'status'=>'success'));
$blob=json_encode($GLOBALS['audit_saved']);
$a(strpos($blob,'secret@example.com')===false,'PII absent from audit');
$controller=file_get_contents(dirname(__DIR__) . '/includes/class-yby-woo-order-export-controller.php');
$a(strpos($controller,"'POST' !== strtoupper")!==false,'POST gate');
$a(strpos($controller,"current_user_can( 'manage_woocommerce' )")!==false,'capability gate');
$a(strpos($controller,'check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD )')!==false,'nonce gate');
$a(strpos($controller,"fopen( 'php://output', 'wb' )")!==false,'direct stream');
$a(strpos($controller,'Content-Disposition: attachment')!==false,'attachment response');
$a(strpos($controller,'Cache-Control: no-store')!==false,'no-store');
foreach(array('wp_insert_post','wp_update_post','update_post_meta','set_status(','save()','wc_create_order') as $forbidden){$a(strpos($controller,$forbidden)===false,'forbidden order write: '.$forbidden);}
$a(strpos($controller,'file_put_contents')===false,'no persistent CSV file');
$module=file_get_contents(dirname(__DIR__) . '/includes/class-yby-woo-order-export-module.php');
$a(strpos($module,"admin_post_' . YBY_Woo_Order_Export_Controller::ACTION")!==false,'authenticated admin-post handler');
echo "PASS v190-order-export-security-harness\n";
