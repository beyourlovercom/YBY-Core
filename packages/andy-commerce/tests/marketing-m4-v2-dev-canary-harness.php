<?php
/** M4 BYL49 v2: no network / DB / real Woo; gate and hook replacement contract. */
declare(strict_types=1);
define('ABSPATH','/synthetic');
define('WP_ENVIRONMENT_TYPE','staging');
define('DAY_IN_SECONDS',86400);
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['options']=[];
$GLOBALS['wp_filter']=['woocommerce_shipping_free_shipping_is_available'=>(object)['callbacks'=>[
  10=>[['function'=>['Andy_Commerce_Shipping_Promotion_Runtime','filter_free_shipping']]]]]];
function home_url():string{return 'https://'.$GLOBALS['host'].'/';}
function get_option(string $k,$fallback=null){return $GLOBALS['options'][$k]??$fallback;}
function wp_json_encode($x,int $flags=0):string{return json_encode($x,$flags);}
function has_filter(string $name,$callback):int|false{
 foreach(($GLOBALS['wp_filter'][$name]->callbacks??[]) as $priority=>$rows)
  foreach($rows as $row)if($row['function']===$callback)return (int)$priority;
 return false;
}
function remove_filter(string $name,$callback,int $priority):bool {
 foreach(($GLOBALS['wp_filter'][$name]->callbacks[$priority]??[]) as $i=>$r)if($r['function']===$callback){
   array_splice($GLOBALS['wp_filter'][$name]->callbacks[$priority],$i,1);return true;
 }
 return false;
}
function add_filter(string $name,$callback,int $priority,int $args):void {
 $GLOBALS['wp_filter'][$name]->callbacks[$priority][]= ['function'=>$callback,'args'=>$args];
}
final class Andy_Commerce_Shipping_Promotion_Runtime {
 public static function filter_free_shipping($prior,$p,$m):bool{return $prior;}
}
final class Andy_Commerce_Shipping_Promotion_Policy {
 public const OPTION_NAME='andy_commerce_shipping_promotion_settings_v1';
 public const ELIGIBILITY_BASIS='pre_discount_merchandise_subtotal';
 public static function is_runtime_active():bool{return true;}
 public static function settings_source():string{return 'byl-platform-legacy';}
 public static function preset():array{return ['legacy_option_name'=>'byl_shipping_promotion_settings'];}
 public static function get_persisted_settings():?array{
  $v=get_option('byl_shipping_promotion_settings',null);
  if(is_array($v))$v['free_shipping_code']=strtolower($v['free_shipping_code']);
  return $v;
 }
 public static function get_persisted_zone_effective_threshold(int $zone):?float{
   $cfg=self::get_persisted_settings();if(!$cfg)return null;
   $row=$cfg['zone_overrides'][(string)$zone]??['mode'=>'global'];
   return ($row['mode']==='override'?(float)$row['threshold']:(float)$cfg['global_threshold']);
 }
 public static function normalize_code(string $code):string{return strtoupper($code);}
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-m4-v2-dev-canary.php';
$N=0;
$ok=static function(bool $good,string $name)use(&$N):void {
 ++$N;if(!$good)throw new RuntimeException('M4 V2 FAIL: '.$name);
};
$policy=[
 'schema_version'=>1,'mode'=>'automatic','free_shipping_code'=>'BYL49',
 'global_threshold'=>49,'eligibility_basis'=>'pre_discount_merchandise_subtotal',
 'zone_overrides'=>[
   '0'=>['mode'=>'global','threshold'=>49],
   '1'=>['mode'=>'override','threshold'=>0],
   '3'=>['mode'=>'override','threshold'=>169],
   '4'=>['mode'=>'override','threshold'=>129],
   '5'=>['mode'=>'override','threshold'=>69],
 ]];
$commerce=$policy;$commerce['global_threshold']=69;
$commerce['zone_overrides']['0']['threshold']=69;
$GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]=$commerce;
$GLOBALS['options']['byl_shipping_promotion_settings']=$policy;
$gate=Andy_Commerce_M4_V2_Dev_Canary::candidate_gate();
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'OFF default');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::boot(),'cannot remove legacy before opt-in');
$GLOBALS['options'][Andy_Commerce_M4_V2_Dev_Canary::OPTION_NAME]=$gate;
$GLOBALS['host']='www.beyourlover.com';
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'WWW disallowed even with gate');
$GLOBALS['host']='dev.beyourlover.com';
$ok(Andy_Commerce_M4_V2_Dev_Canary::enabled(),'matching Dev opt-in');
$ok(Andy_Commerce_M4_V2_Dev_Canary::boot(),'single-authority switch');
$ok($gate['effective_source']==='byl-platform-legacy'
  && $gate['v1_fingerprint']!==$gate['commerce_v1_fingerprint'],'dual-source fingerprint captures mismatch');
$ok(has_filter('woocommerce_shipping_free_shipping_is_available',
 [Andy_Commerce_Shipping_Promotion_Runtime::class,'filter_free_shipping'])===false,'legacy filter removed');
$ok(has_filter('woocommerce_shipping_free_shipping_is_available',
 [Andy_Commerce_M4_V2_Dev_Canary::class,'filter_free_shipping'])===10,'v2 only');
$changes=$policy;$changes['global_threshold']=68;
$GLOBALS['options']['byl_shipping_promotion_settings']=$changes;
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'active legacy policy drift invalidates gate');
$GLOBALS['options']['byl_shipping_promotion_settings']=$policy;
$changes=$commerce;$changes['global_threshold']=80;
$GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]=$changes;
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'dormant Commerce config drift invalidates gate');
$GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]=$commerce;
$oldGate=$gate;$oldGate['expires_utc']=time()-1;
$GLOBALS['options'][Andy_Commerce_M4_V2_Dev_Canary::OPTION_NAME]=$oldGate;
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'expired gate invalidated');
$futureGate=$gate;$futureGate['expires_utc']=time()+43201;
$GLOBALS['options'][Andy_Commerce_M4_V2_Dev_Canary::OPTION_NAME]=$futureGate;
$ok(!Andy_Commerce_M4_V2_Dev_Canary::enabled(),'unbounded TTL invalidated');
$GLOBALS['options'][Andy_Commerce_M4_V2_Dev_Canary::OPTION_NAME]=$gate;
$regular=[['line_subtotal'=>60.0,'line_total'=>45.0]];
$gift=[['line_subtotal'=>150.0,'line_total'=>150.0,'andy_commerce_auto_gift'=>true]];
$ok(Andy_Commerce_M4_V2_Dev_Canary::amount_minor($regular,2)===4500,'discounted amount');
$ok(Andy_Commerce_M4_V2_Dev_Canary::amount_minor(array_merge($regular,$gift),2)===4500,'auto gift excluded');
foreach([0,1,3,4,5] as $zone){
 $threshold=Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold($zone);
 $result=Andy_Commerce_M4_V2_Dev_Canary::evaluate($regular,$threshold,2);
 $ok($result['threshold_minor']===[49,0,169,129,69][array_search($zone,[0,1,3,4,5],true)]*100,
    'zone exact threshold '.$zone);
 $ok($result['eligible']===($zone===1),'zone permission '.$zone);
}
$ok(Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>69]],69,2)['eligible'],'exact $69');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>68.99]],69,2)['eligible'],'below $69');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>128.99]],129,2)['eligible'],'below $129');
$ok(Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>169]],169,2)['eligible'],'exact $169');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>45]],49,2)['eligible'],'legacy $49 example no free');
$ok(Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>49]],49,2)['eligible'],'legacy $49 boundary');
$ok(Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>0]],0,2)['eligible'],'existing zone1 zero preserved');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>-1]],69,2)['confirmed'],'negative invalid');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>'NaN']],69,2)['confirmed'],'nan invalid');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>12.5]],69,5)['confirmed'],'currency minor precision invalid');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([],69,2)['confirmed'],'empty cart fails closed');
$ok(Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>40],['line_total'=>20]],69,2)['qualified_minor']===6000,'multiple line sum');
$ok(!Andy_Commerce_M4_V2_Dev_Canary::evaluate([['line_total'=>40],['line_total'=>100000001]],69,2)['confirmed'],'limit');
$entry=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
$ok(str_contains($entry,'Andy_Commerce_M4_V2_Dev_Canary::boot()')&&
    str_contains($entry,"'dev.beyourlover.com'"),'bootstrap still Dev-only');
$ok($GLOBALS['options'][Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME]===$commerce,'dormant Commerce option intact');
$ok($GLOBALS['options']['byl_shipping_promotion_settings']===$policy,'active legacy policy intact');
echo "M4_BYL49_V2_DEV_CANARY_HARNESS_PASS=$N; V1_PRESERVED=YES; WRITES=0\n";
