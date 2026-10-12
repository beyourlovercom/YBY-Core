<?php
/** M5 Dev Woo Store API endpoint projection: deterministic read-only stubs. */
declare(strict_types=1);
define('ABSPATH','/synthetic');define('WP_ENVIRONMENT_TYPE','staging');define('DAY_IN_SECONDS',86400);define('ARRAY_A','array_a');define('ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED',true);
$GLOBALS['options']=[]; $GLOBALS['host']='dev.beyourlover.com';$GLOBALS['rate_id']='flat_rate:8';
$GLOBALS['picked']=['flat_rate:8'];$GLOBALS['zone']=0;$GLOBALS['numPackages']=1;
function home_url():string{return 'https://'.$GLOBALS['host'].'/';}
function get_option(string $key,$fallback=null){return $GLOBALS['options'][$key]??$fallback;}
function wp_json_encode($v,int $flags=0):string{return json_encode($v,$flags);}
function has_filter(string $name,$callback):int|false{
 return $callback===[Andy_Commerce_M4_V2_Dev_Canary::class,'filter_free_shipping']?10:false;
}
function wc_get_price_decimals():int{return 2;}
final class MockCart {public array $lines=[['line_total'=>45.0],['line_total'=>100.0,'andy_commerce_auto_gift'=>true]];
 public function get_cart():array{return $this->lines;}}
final class MockShipping{public function get_packages():array{
 $x=[['destination'=>['country'=>'US'],'rates'=>[
  $GLOBALS['rate_id']=>new MockRate($GLOBALS['rate_id'])]]];
 if($GLOBALS['numPackages']===2)$x[]=$x[0];
 return $x;
}}
final class MockRate{private string $id;public function __construct(string $id){$this->id=$id;}public function get_id():string{return $this->id;}}
final class MockSession{public function get(string $key,$default=null){return $GLOBALS['picked'];}}
final class MockWoo{public MockCart $cart;public MockSession $session;private MockShipping $ship;
 public function __construct(){$this->cart=new MockCart();$this->ship=new MockShipping();$this->session=new MockSession();}
 public function shipping():MockShipping{return $this->ship;}}
$GLOBALS['wc']=new MockWoo();
function WC():MockWoo{return $GLOBALS['wc'];}
final class MockZone{public function get_id():int{return $GLOBALS['zone'];}}
final class WC_Shipping_Zones {public static function get_zone_matching_package(array $p):MockZone{return new MockZone();}}
final class Andy_Commerce_Shipping_Promotion_Policy {
 public const OPTION_NAME='andy_commerce_shipping_promotion_settings_v1';
 public const ELIGIBILITY_BASIS='pre_discount_merchandise_subtotal';

 public static function settings_source():string{return 'byl-platform-legacy';}
 public static function preset():array{return ['legacy_option_name'=>'byl_shipping_promotion_settings'];}
 public static function get_persisted_settings():?array{
  $v=get_option('byl_shipping_promotion_settings');
  if(is_array($v))$v['free_shipping_code']=strtolower($v['free_shipping_code']);
  return $v;
 }
 public static function is_runtime_active():bool{return true;}
 public static function get_persisted_zone_effective_threshold(int $id):?float{
   $cfg=self::get_persisted_settings();
   $z=$cfg['zone_overrides'][(string)$id]??[];
   return ($z['mode']??'')==='override'?(float)$z['threshold']:(float)$cfg['global_threshold'];
 }
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-m4-v2-dev-canary.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-progress-projection.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-store-api-readonly-extension.php';
$settings=[
 'schema_version'=>1,'eligibility_basis'=>'pre_discount_merchandise_subtotal',
 'mode'=>'automatic','global_threshold'=>49,'free_shipping_code'=>'BYL49',
 'zone_overrides'=>[
 '0'=>['mode'=>'global','threshold'=>49],
 '1'=>['mode'=>'override','threshold'=>0],
 '3'=>['mode'=>'override','threshold'=>169],
 '4'=>['mode'=>'override','threshold'=>129],
 '5'=>['mode'=>'override','threshold'=>69]]];
$commerce=$settings;$commerce['global_threshold']=69;$commerce['zone_overrides']['0']['threshold']=69;
$GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]=$commerce;
$GLOBALS['options']['byl_shipping_promotion_settings']=$settings;
$N=0;
$assert=static function(bool $v,string $what)use(&$N):void {
 $N++;if(!$v)throw new RuntimeException('M5 STORE ACTUAL FAIL '.$what);
};
$hidden=Andy_Commerce_M5_Store_API_Readonly_Extension::data();
$assert(!$hidden['show_progress']&&$hidden['reason']==='v2_canary_not_authoritative','option off fail closed');
$GLOBALS['options'][Andy_Commerce_M4_V2_Dev_Canary::OPTION_NAME]=
 Andy_Commerce_M4_V2_Dev_Canary::candidate_gate();
$ready=Andy_Commerce_M5_Store_API_Readonly_Extension::data();
$assert($ready['show_progress']&&$ready['qualified_minor']===4500,'eligible line discounted $45 (gift excluded)');
$assert($ready['threshold_minor']===4900&&$ready['remaining_minor']===400,'real active BYL zone0 threshold $49');
$assert($ready['percent']===91,'fraction matches actual zone');
$assert(!$ready['gift_enabled']&&!$ready['claim_free_shipping'],'never issues gifts or shipping');
$GLOBALS['zone']=5;
$assert(Andy_Commerce_M5_Store_API_Readonly_Extension::data()['threshold_minor']===6900,'zone 5 $69');
$GLOBALS['zone']=4;
$assert(Andy_Commerce_M5_Store_API_Readonly_Extension::data()['threshold_minor']===12900,'zone 4 $129');
$GLOBALS['zone']=3;
$assert(Andy_Commerce_M5_Store_API_Readonly_Extension::data()['threshold_minor']===16900,'zone 3 $169');
$GLOBALS['zone']=1;
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'zone1 zero threshold no bar');
$GLOBALS['zone']=0;
$GLOBALS['numPackages']=2;
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'multi-package hidden');
$GLOBALS['numPackages']=1;
$GLOBALS['picked']=['free_shipping:99'];
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'unknown chosen rate hidden');
$GLOBALS['picked']=['flat_rate:8','flat_rate:9'];
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'multiple chosen rates hidden');
$GLOBALS['picked']=['flat_rate:8'];
$GLOBALS['host']='www.beyourlover.com';
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'WWW host disallowed');
$GLOBALS['host']='dev.beyourlover.com';
$old=$settings;$old['global_threshold']=69;
$GLOBALS['options']['byl_shipping_promotion_settings']=$old;
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'active policy drift hides progress');
$GLOBALS['options']['byl_shipping_promotion_settings']=$settings;
$GLOBALS['wc']->cart->lines=[['line_total'=>49.0]];
$full=Andy_Commerce_M5_Store_API_Readonly_Extension::data();
$assert($full['percent']===100&&$full['remaining_minor']===0&&!$full['claim_free_shipping'],'100% no false free award');
$GLOBALS['wc']->cart->lines=[['line_total'=>-1]];
$assert(!Andy_Commerce_M5_Store_API_Readonly_Extension::data()['show_progress'],'malformed totals hidden');
$schema=Andy_Commerce_M5_Store_API_Readonly_Extension::schema();
$assert(isset($schema['remaining_minor'],$schema['percent'],$schema['threshold_minor'])
    && $schema['show_progress']['readonly']===true,'schema compatible with Cart V2');
echo "M5_WOO_STORE_API_DEV_CANARY_PASS=$N; ZONES=0,1,3,4,5; AWARDS=0; WRITES=0\n";
