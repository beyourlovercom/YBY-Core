<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
define( 'VIWEC_VER', '1.2.14' );
define( 'WACVP_VERSION', '1.1.7' );
$GLOBALS['p7_posts'] = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
$GLOBALS['p7_status'] = array( 1=>'publish',2=>'publish',3=>'publish',4=>'publish',5=>'publish',6=>'publish',7=>'draft',8=>'publish',9=>'publish',10=>'publish' );
$GLOBALS['p7_type'] = array( 1=>'new_order',2=>'customer_partially_refunded_order',3=>'customer_partially_refunded_order',4=>'default',5=>'default',6=>'ghost_mail',7=>'failed_order',8=>'customer_invoice_pending',9=>'abandoned_cart',10=>'abandoned_cart' );
$GLOBALS['p7_rules'] = array(
	1=>array(), 2=>array(), 3=>array(), 4=>array(), 5=>array(), 6=>array(), 7=>array(), 8=>array(),
	9=>array( 'countries'=>array('US'), 'price_type'=>'total' ), 10=>array( 'products'=>array('123'), 'price_type'=>'total' )
);
function sanitize_key($v){ return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v)); }
function sanitize_text_field($v){ return trim(strip_tags((string)$v)); }
function absint($v){ return abs((int)$v); }
function post_type_exists($t){ return 'viwec_template' === $t; }
function get_posts($args){ return $GLOBALS['p7_posts']; }
function get_post_status($id){ return $GLOBALS['p7_status'][$id] ?? ''; }
function get_post_meta($id,$key,$single=true){ if('viwec_settings_type'===$key)return $GLOBALS['p7_type'][$id]??''; if('viwec_setting_rules'===$key)return $GLOBALS['p7_rules'][$id]??array(); return ''; }
function get_the_title($id){ return 'Template '.$id; }
function admin_url($path=''){ return 'https://example.test/wp-admin/'.$path; }
function add_query_arg($key,$value=null,$url=''){
	if(is_array($key)){ foreach($key as $k=>$v){ $url=add_query_arg($k,$v,$url); } return $url; }
	return $url.(false===strpos($url,'?')?'?':'&').rawurlencode($key).'='.rawurlencode((string)$value);
}
require_once dirname(__DIR__).'/inc/class-yby-email-legacy-customizer-governance.php';
$templates=array(
	'woocommerce:new_order'=>array('provider'=>'woocommerce','source_id'=>'new_order'),
	'woocommerce:failed_order'=>array('provider'=>'woocommerce','source_id'=>'failed_order'),
	'wordpress:new_user'=>array('provider'=>'wordpress','source_id'=>'new_user'),
);
class P7_Test_Governance extends YBY_Email_Legacy_Customizer_Governance { protected function legacy_supported_types(){ return array('customer_partially_refunded_order','customer_invoice_pending','abandoned_cart'); } }
$report=(new P7_Test_Governance())->scan($templates);
$assert=static function($ok,$msg){ if(!$ok){ fwrite(STDERR,"FAIL: $msg\n"); exit(1); } };
$assert(true===$report['active'] && '1.2.14'===$report['version'],'active/version detection');
$assert(9===$report['published'] && 1===$report['draft'],'status counts');
$assert('mapped'===$report['mappings']['new_order']['state'],'single mapping state');
$assert('woocommerce_native'===$report['mappings']['failed_order']['state'],'draft-only mapping must not hijack Woo editor');
$assert(2===$report['default_count'],'default count');
$assert(1===$report['rule_variant_types'],'recognized rule variant count');
$assert(isset($report['legacy_special_types']['customer_invoice_pending']),'built-in VillaTheme special type');
$assert(isset($report['legacy_special_types']['abandoned_cart']),'third-party VillaTheme extension type');
$assert(in_array('multiple_default_templates',$report['warnings'],true),'default duplicate warning');
$assert(in_array('multiple_unconditional:customer_partially_refunded_order',$report['warnings'],true),'legacy special duplicate warning');
$assert(in_array('unmatched_published_types',$report['warnings'],true),'unmatched type warning');
$assert(in_array('ghost_mail',$report['unmatched_types'],true),'unmatched type surfaced');
$assert(isset($report['legacy_special_types']['abandoned_cart']),'WACV abandoned_cart must be recognized as legacy extension');
$assert(1===$report['rule_variant_types'],'rule variant count across declared legacy types');
$source=file_get_contents(dirname(__DIR__).'/inc/class-yby-email-legacy-customizer-governance.php');
foreach(array('update_post_meta','wp_update_post','wp_delete_post','wp_trash_post') as $mutation){
	$assert(false===strpos($source,$mutation),'governance scanner must stay read-only: '.$mutation);
}
$core=file_get_contents(dirname(__DIR__).'/inc/class-yby-core.php');
$assert(false!==strpos($core,'class-yby-email-legacy-customizer-governance.php'),'governance bootstrap missing');
$assert(false===strpos($core,'woocommerce_email_'),'Woo runtime takeover remains forbidden');
echo "PASS email-os-legacy-governance-harness\n";
