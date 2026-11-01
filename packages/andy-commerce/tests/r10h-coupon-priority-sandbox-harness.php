<?php
/** R10-H deterministic coupon collision contract; NO WordPress/site writes. */
declare(strict_types=1);
$base=sys_get_temp_dir().DIRECTORY_SEPARATOR.'r10h_'.getmypid();
if (file_exists($base)) exit(3);
mkdir($base,0700,true);
define('ABSPATH',$base.DIRECTORY_SEPARATOR);
file_put_contents(ABSPATH.'.andy-r10b-isolated-local','ISOLATED_ONLY');
define('ANDY_COMMERCE_R10C_ISOLATED_UAT',true);
define('WC_VERSION','11.2.1');
$GLOBALS['coupon_definitions']=[
 'auto10'=>['percent','10',false],
 'manual5'=>['fixed_cart','5',false],
 'manual7'=>['fixed_product','7',false],
 'freecode'=>['fixed_cart','0',true],
 'giftcredit'=>['store_credit','10',false],
 'zero'=>['fixed_cart','0',false],
];
class WC_Coupon {
 private string $code;
 public function __construct($code){$this->code=(string)$code;
  if(!isset($GLOBALS['coupon_definitions'][$this->code]))throw new RuntimeException('UNKNOWN_CODE');}
 public function get_code(){return $this->code;}
 public function get_free_shipping(){return $GLOBALS['coupon_definitions'][$this->code][2];}
 public function get_discount_type(){return $GLOBALS['coupon_definitions'][$this->code][0];}
 public function get_amount(){return $GLOBALS['coupon_definitions'][$this->code][1];}
}
class Andy_Commerce_Observation_Mode {
 public static bool $observing=false;
 public static function is_observing(): bool{return self::$observing;}
}
class Fake_Cart {
 public array $applied=[];
 public function get_applied_coupons(): array {return $this->applied;}
}
$GLOBALS['r10_cart']=new Fake_Cart();
function WC(){return (object)['cart'=>$GLOBALS['r10_cart']];}
$GLOBALS['opts']=[
 'siteurl'=>'http://127.0.0.1:14141',
 'woocommerce_currency'=>'USD',
 'andy_commerce_r10c_shipping_enabled_v1'=>'no',
 'andy_commerce_r10c_coupon_enabled_v1'=>'yes'
];
function get_option($key,$default=null){return $GLOBALS['opts'][$key]??$default;}
function wc_get_price_decimals(){return 2;}
function get_plugins(){return [
 'woocommerce/woocommerce.php'=>['Version'=>'11.2.1'],
 'yaycurrency-pro/yay-currency.php'=>['Version'=>'3.3.5'],
 'wt-smart-coupon-pro/wt-smart-coupon-pro.php'=>['Version'=>'3.2.0'],
 'woocommerce-smart-coupons/woocommerce-smart-coupons.php'=>['Version'=>'9.51.0'],
];}
function is_plugin_active($slug){return isset(get_plugins()[$slug]);}
require dirname(__DIR__).'/includes/class-andy-commerce-r10c-transaction-compatibility.php';
$tests=[];
function test_case(string $name,array $existing,string $incoming,bool $expected,bool $priorVendor=true){
 $GLOBALS['r10_cart']->applied=$existing;
 $result=Andy_Commerce_R10C_Transaction_Compatibility::single_ordinary_discount($priorVendor,new WC_Coupon($incoming));
 $ok=$result===$expected;
 $GLOBALS['tests'][]=[$name,$ok,$result,$expected];
}
test_case('first_manual_coupon_accepted',[],'manual5',true);
test_case('second_manual_coupon_rejected',['manual5'],'manual7',false);
test_case('repeat_same_code_accepted',['manual5'],'manual5',true);
test_case('automatic_first_blocks_manual',['auto10'],'manual5',false);
test_case('manual_first_blocks_automatic',['manual5'],'auto10',false);
test_case('existing_free_shipping_does_not_consume_slot',['freecode'],'manual5',true);
test_case('incoming_free_shipping_survives_one_discount',['manual5'],'freecode',true);
test_case('store_credit_nonstandard_does_not_consume_slot',['giftcredit'],'manual5',true);
test_case('zero_value_does_not_consume_slot',['zero'],'manual5',true);
test_case('vendor_preexisting_veto_is_respected',[],'manual5',false,false);
Andy_Commerce_Observation_Mode::$observing=true;
test_case('global_observe_no_coupon_interception',['manual5'],'manual7',true);
Andy_Commerce_Observation_Mode::$observing=false;
$GLOBALS['opts']['andy_commerce_r10c_coupon_enabled_v1']='no';
test_case('module_off_no_coupon_interception',['manual5'],'manual7',true);
$GLOBALS['opts']['andy_commerce_r10c_coupon_enabled_v1']='yes';
$GLOBALS['r10_cart']->applied=['no-such-code'];
$result=Andy_Commerce_R10C_Transaction_Compatibility::single_ordinary_discount(true,new WC_Coupon('manual5'));
$GLOBALS['tests'][]=['unknown_prior_code_fails_closed',$result===false,$result,false];
unlink(ABSPATH.'.andy-r10b-isolated-local');rmdir($base);
$failed=0;
foreach($GLOBALS['tests'] as [$name,$ok,$actual,$expected]){
 echo 'R10H_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)$failed++;
}
echo 'R10H_CONTRACT='.(count($GLOBALS['tests'])-$failed).'/'.count($GLOBALS['tests'])."\n";
echo "R10H_POLICY_GAP=AUTO_FIRST_BLOCKS_MANUAL (requires Owner policy decision)\n";
exit($failed?1:0);
