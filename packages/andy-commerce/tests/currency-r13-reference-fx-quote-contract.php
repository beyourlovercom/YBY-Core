<?php
/** Real-rate schema and expiry are pure; no network or saved Woo data required. */
declare(strict_types=1);
define('ABSPATH',__DIR__.'/');
function wp_json_encode($v): string {return json_encode($v,JSON_UNESCAPED_SLASHES);}
require dirname(__DIR__).'/includes/class-andy-commerce-currency-reference-fx-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-r13-fx-quote-guard.php';
$now=time();
$obs=gmdate('Y-m-d',$now);
$rows=[];
foreach(['AUD'=>1.42400,'CAD'=>1.40110,'EUR'=>0.89290,'GBP'=>0.74856] as $code=>$rate)
 $rows[]=['date'=>$obs,'base'=>'USD','quote'=>$code,'rate'=>$rate];
$parsed=Andy_Commerce_Currency_Reference_FX_V1::parse($rows,$now);
$good=Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($parsed,$now);
if($good['rate_micros']['CAD']!==1401100||$good['rate_micros']['EUR']!==892900
   ||$good['is_settlement_rate']!==false)throw new RuntimeException('Reference FX validation failed');
$reject=static function(array $v,int $at): void {
 try{Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($v,$at);}
 catch(InvalidArgumentException $e){return;}
 throw new RuntimeException('Unsafe FX input accepted');
};
$stale=$parsed;$stale['retrieved_at_utc']=$now-21601;$reject($stale,$now);
$future=$parsed;$future['retrieved_at_utc']=$now+1;$reject($future,$now);
$missing=$parsed;unset($missing['rate_micros']['CAD']);$reject($missing,$now);
$changed=$parsed;$changed['rate_source']='MERCHANT_SETTLEMENT_FX';$reject($changed,$now);
$badRate=$parsed;$badRate['rate_micros']['GBP']=0;$reject($badRate,$now);
$old=$parsed;$old['quote_dates']['EUR']=gmdate('Y-m-d',$now-691200);$reject($old,$now);
echo 'P6F_REFERENCE_FX_SOURCE_VALIDATED_AND_EXPIRY_REJECTED_PASS tests=7\n';
