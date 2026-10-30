<?php
/** Read-only bounded policy and callback attribution tests, no real DB/WP. */
declare(strict_types=1);
define('ABSPATH','/dummy/');
$GLOBALS['opts'] = array();
$GLOBALS['host'] = 'dev.beyourlover.com';
$GLOBALS['hooks'] = array();
$GLOBALS['executed'] = 0;
class BYL_Shipping_Promotion_Runtime {}
class Andy_Commerce {}
function is_admin() {return true;}
function add_action($n,$c,$p=10) {$GLOBALS['hooks'][]=$n;}
function current_user_can($name) {return $name==='manage_options';}
function add_management_page() {}
function get_option($name,$default=null) {return array_key_exists($name,$GLOBALS['opts'])?$GLOBALS['opts'][$name]:$default;}
function home_url($path='/') {return 'https://'.$GLOBALS['host'].$path;}
function wp_parse_url($url,$component) {return parse_url($url,$component);}
function get_plugins(){return array('woocommerce/woocommerce.php'=>array('Name'=>'WooCommerce','Version'=>'11.2.1'));}
function is_plugin_active($slug){return true;}
function get_bloginfo($arg){return '7.1.4';}
function wp_get_environment_type(){return 'staging';}
function esc_html($val){return htmlspecialchars((string)$val, ENT_QUOTES,'UTF-8');}
function esc_html__($val,$d){return $val;}
function wp_get_theme(){return new class {function get($arg){return $arg==='Name'?'BYL V3':'0.1.0';}};}
function wp_die($message){throw new RuntimeException($message);}
function injected_callback(){ $GLOBALS['executed']++; throw new RuntimeException('callback executed'); }
require dirname(__DIR__).'/andy-commerce-observer.php';
$legacy=array('schema_version'=>1,'mode'=>'automatic','free_shipping_code'=>'BYL69','global_threshold'=>69.0,'eligibility_basis'=>'pre_discount_merchandise_subtotal','zone_overrides'=>array('0'=>array('mode'=>'override','threshold'=>99),'1'=>array('mode'=>'global','threshold'=>49)));
$commerce=array('schema_version'=>1,'mode'=>'automatic','free_shipping_code'=>'BYL49','global_threshold'=>49.0,'eligibility_basis'=>'pre_discount_merchandise_subtotal','zone_overrides'=>array());
$GLOBALS['opts']=array('byl_shipping_promotion_settings'=>$legacy,'andy_commerce_shipping_promotion_settings_v1'=>$commerce,'woocommerce_currency'=>'USD');
$GLOBALS['wp_filter']=array('woocommerce_shipping_chosen_method'=>(object) array('callbacks'=>array(20=>array(
 'a'=>array('function'=>array('Andy_Commerce_Shipping_Promotion_Runtime','prefer_available_free_shipping')),
 'b'=>array('function'=>'injected_callback'),
 'c'=>array('function'=>array('BYL_Shipping_Promotion_Runtime','on_choice')),
))));
function render_html() {ob_start();Andy_Commerce_Read_Only_Observer::render_admin_page();return ob_get_clean();}
function check($expr,$message){if(!$expr){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
$html=render_html();
check(strpos($html,'Legacy BYL policy')!==false,'legacy wins without handoff');
check(strpos($html,'69.00')!==false && strpos($html,'Zone 0: 99.00')!==false,'scalar zone thresholds read correctly');
check(strpos($html,'Andy Commerce policy')===false,'Commerce not selected without handoff');
check(strpos($html,'Andy Commerce</td>')!==false && strpos($html,'BYL legacy</td>')!==false,'callback owners classified');
check(strpos($html,'injected_callback')!==false && $GLOBALS['executed']===0,'callback never invoked');
check($GLOBALS['hooks']===array('admin_menu'),'admin_menu only');
check(strpos($html,'<form')===false,'no form');
$GLOBALS['opts']['andy_commerce_byl_promotion_handoff_v1']='commerce';
$html=render_html();
check(strpos($html,'Andy Commerce policy')!==false && strpos($html,'49.00')!==false,'handoff selects Commerce');
$GLOBALS['opts']['andy_commerce_shipping_promotion_settings_v1']['global_threshold']='bogus';
$html=render_html();
check(strpos($html,'No verified active policy')!==false,'handoff invalid Commerce refuses fallback');
$GLOBALS['opts']['andy_commerce_byl_promotion_handoff_v1']=null;
$html=render_html();
check(strpos($html,'Legacy BYL policy')!==false,'legacy still selected without handoff');
$GLOBALS['opts']['byl_shipping_promotion_settings']['eligibility_basis']='invalid';
$html=render_html();
check(strpos($html,'No verified active policy')!==false,'both invalid fail closed');
$GLOBALS['host']='example.test';
$GLOBALS['opts']['andy_commerce_shipping_promotion_settings_v1']=$commerce;
$html=render_html();
check(strpos($html,'Andy Commerce policy')!==false,'generic Commerce selected');
$GLOBALS['host']='x';
$html=render_html();
check(strpos($html,'Andy Commerce policy')!==false,'short hostname safe');
check($GLOBALS['executed']===0,'zero third party callback execution');
echo "PASS observer-policy-provenance-harness shipping priority/read-only/handoff/invalid schema/host/callback owners\n";
