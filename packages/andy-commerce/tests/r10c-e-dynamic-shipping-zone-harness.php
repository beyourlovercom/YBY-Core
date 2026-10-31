<?php
/** R10-C-E negative/positive method-zone contract: no real WP, no network. */
declare(strict_types=1);
$base=sys_get_temp_dir().DIRECTORY_SEPARATOR.'r10c-e-zone-fixture-'.getmypid();
if (file_exists($base)) exit(3);
mkdir($base,0700);
define('ABSPATH',$base.DIRECTORY_SEPARATOR);
file_put_contents(ABSPATH.'.andy-r10b-isolated-local','test-only');
define('ANDY_COMMERCE_R10C_ISOLATED_UAT',true);
define('WC_VERSION','11.2.1');
$GLOBALS['r10_options']=[
 'siteurl'=>'http://127.0.0.1:14141',
 'woocommerce_currency'=>'USD',
 'woocommerce_prices_include_tax'=>'no',
 'andy_commerce_r10c_shipping_enabled_v1'=>'yes',
 'andy_commerce_r10c_coupon_enabled_v1'=>'no'
];
function get_option($name,$default=null){return $GLOBALS['r10_options'][$name]??$default;}
function wc_get_price_decimals(){return 2;}
function get_plugins(){return [
 'woocommerce/woocommerce.php'=>['Version'=>'11.2.1'],
 'yaycurrency-pro/yay-currency.php'=>['Version'=>'3.3.5'],
 'wt-smart-coupon-pro/wt-smart-coupon-pro.php'=>['Version'=>'3.2.0'],
 'woocommerce-smart-coupons/woocommerce-smart-coupons.php'=>['Version'=>'9.51.0']
];}
function is_plugin_active($slug){return isset(get_plugins()[$slug]);}
class WC_Coupon{}
class Andy_Commerce_Observation_Mode {
 static public bool $observe=false;
 static public function is_observing(){return self::$observe;}
}
class R10Cart {
 public float $subtotal=63.479999999999996;
 public int $classId=0;
 public function get_displayed_subtotal(){return $this->subtotal;}
 public function get_discount_total(){return 0.0;}
 public function display_prices_including_tax(){return false;}
 public function get_cart(){return [['data'=>new class($this) {
  private $cart;
  function __construct($c){$this->cart=$c;}
  function get_shipping_class_id(){return $this->cart->classId;}
 }]];}
}
$GLOBALS['woo']=(object)['cart'=>new R10Cart()];
function WC(){return $GLOBALS['woo'];}
class R10Zone {
 public int $zoneId=0;
 public array $methods=[];
 public function get_id(){return $this->zoneId;}
 public function get_shipping_methods($enabledOnly=false){return $this->methods;}
}
class WC_Shipping_Zones {
 public static function get_zone_matching_package($package){return $GLOBALS['r10zone'];}
}
$GLOBALS['r10zone']=new R10Zone();
require dirname(__DIR__).'/includes/class-andy-commerce-r10c-transaction-compatibility.php';
$tested=[];
function candidate($zoneId,$id,$requires='min_amount',$min=63.480000000000004,
 $cart=63.479999999999996) {
 $zone=$GLOBALS['r10zone'];$zone->zoneId=$zoneId;
 $GLOBALS['woo']->cart->subtotal=$cart;
 $method=(object)['id'=>'free_shipping','instance_id'=>$id,
 'enabled'=>'yes','requires'=>$requires,'ignore_discounts'=>'no','min_amount'=>$min];
 $zone->methods=[$method];
 return Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,[],$method);
}
function checkit($label,$actual,$expected) {
 ++$GLOBALS['r10total'];
 $ok=$actual===$expected;
 echo $label.'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok) $GLOBALS['r10fails'][]=$label;
}
$GLOBALS['r10fails']=[];
$GLOBALS['r10total']=0;
checkit('default_zone_actual_method11',candidate(0,11),true);
checkit('country_zone5_method9',candidate(5,9),true);
checkit('country_zone4_method8_threshold129',candidate(4,8,'min_amount',
 118.680000000000006,118.679999999999993),true);
checkit('country_zone3_method6_threshold169',candidate(3,6,'min_amount',
 155.480000000000018,155.479999999999990),true);
checkit('no_threshold_zone1_method2',candidate(1,2,''),false);
$wrong=(object)['id'=>'free_shipping','instance_id'=>77,'enabled'=>'yes',
 'requires'=>'min_amount','ignore_discounts'=>'no','min_amount'=>63.480000000000004];
checkit('unmatched_method_instance',Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,[],$wrong),false);
$GLOBALS['r10zone']->methods=[];
checkit('missing_native_method',Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,[],$wrong),false);
candidate(0,11);
$other=(object)['id'=>'free_shipping','instance_id'=>11,'enabled'=>'yes',
 'requires'=>'either','ignore_discounts'=>'no','min_amount'=>63.480000000000004];
checkit('metadata_mismatch_rejected',Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,[],$other),false);
checkit('below_minimum_1_cent',candidate(0,11,'min_amount',63.48,63.47),false);
candidate(0,11);
$GLOBALS['woo']->cart->classId=5;
$m=$GLOBALS['r10zone']->methods[0];
checkit('special_class_fail_closed',Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,[],$m),false);
$GLOBALS['woo']->cart->classId=0;
Andy_Commerce_Observation_Mode::$observe=true;
checkit('global_observe_always_off',candidate(0,11),false);
Andy_Commerce_Observation_Mode::$observe=false;
$GLOBALS['r10_options']['andy_commerce_r10c_shipping_enabled_v1']='no';
checkit('module_off_always_off',candidate(0,11),false);
unlink(ABSPATH.'.andy-r10b-isolated-local');
rmdir(ABSPATH);
echo 'R10C_E_DYNAMIC_ZONE_TOTAL='.($GLOBALS['r10total']-count($GLOBALS['r10fails'])).'/'.$GLOBALS['r10total']."\n";
exit($GLOBALS['r10fails']?1:0);
