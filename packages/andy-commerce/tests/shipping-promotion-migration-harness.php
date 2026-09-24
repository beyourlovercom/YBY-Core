<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['options'] = array();
function get_option($k,$d=false){return array_key_exists($k,$GLOBALS['options'])?$GLOBALS['options'][$k]:$d;}
function update_option($k,$v,$a=false){$GLOBALS['options'][$k]=$v;return true;}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function home_url($p='/'){return 'https://localdev.beyourlover.com'.$p;}
function apply_filters($h,$v){return $v;}
function sanitize_text_field($v){return trim((string)$v);}
function absint($v){return abs((int)$v);}
function add_action(){}
function add_filter(){}
function register_setting(){}
function wc_format_coupon_code($v){return strtolower((string)$v);}
class WC_Shipping_Zones {
 public static function get_zones(){return array();}
 public static function get_zone($id){return new class { public function get_shipping_methods(){return array();} };}
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-site-preset.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-shipping-promotion-policy.php';
$a=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$p=Andy_Commerce_Site_Preset::current();
$a($p['id']==='byl'&&$p['free_shipping_code']==='BYL49'&&(float)$p['global_threshold']===49.0,'BYL preset detection');
$GLOBALS['options']['byl_shipping_promotion_settings']=array(
 'schema_version'=>1,'mode'=>'require_code','free_shipping_code'=>'BYL49','global_threshold'=>49.0,
 'eligibility_basis'=>'pre_discount_merchandise_subtotal','zone_overrides'=>array('0'=>array('mode'=>'global','threshold'=>49.0))
);
$a(Andy_Commerce_Shipping_Promotion_Policy::maybe_migrate_legacy_settings(),'legacy settings should migrate once');
$n=$GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]??null;
$a(is_array($n)&&$n['mode']==='require_code'&&$n['free_shipping_code']==='BYL49','migrated BYL values preserved');
$a(Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active(),'migrated policy active');
$a(Andy_Commerce_Shipping_Promotion_Policy::maybe_migrate_legacy_settings()===false,'migration idempotent');
echo "PASS shipping-promotion-migration-harness\n";
