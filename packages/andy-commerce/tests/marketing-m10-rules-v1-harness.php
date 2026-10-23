<?php
/** Dev shadow M10: six templates, percent/fixed/gift USD consistency. */
declare(strict_types=1);
define('ABSPATH','/test/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-marketing-v2-rule-fields.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
$n=0;
$assert=static function(bool $ok,string $why)use(&$n){if(!$ok)throw new RuntimeException("M10 RULE FAIL $why");++$n;};
$reject=static function(callable $fn,string $why)use($assert){
    try {$fn();}catch(InvalidArgumentException $e){$assert(true,$why);return;}
    $assert(false,'not rejected '.$why);
};
$rule=static function(string $id,string $type,int $threshold,int $reward,int $priority=100):array {
    $base=['id'=>$id,'name'=>$id,'type'=>$type,'active'=>false,'priority'=>$priority,'threshold_usd_minor'=>$threshold];
    if($type==='gift')$base['gift_product_id']=$reward;
    if($type==='discount')$base['discount_usd_minor']=$reward;
    return $base;
};
$fixed=$rule('fixed20','discount',16900,2000);
$percent=$rule('pct15','discount',16900,0);
unset($percent['discount_usd_minor']);
$percent['discount_percent_bp']=1500;
$gift=$rule('gift99','gift',9900,1234);
$rules=[$fixed,$percent,$gift];
foreach(['USD'=>1000000,'EUR'=>890000,'GBP'=>746500,'AUD'=>1421700,'CAD'=>1404200] as $currency=>$rate) {
    foreach([0,1,3,4,5] as $zone){
        $quote=Andy_Commerce_Currency_OS_V1::quote([
          'currency'=>$currency,'rate_micros'=>$rate,
          'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
          'threshold_usd_minor'=>0,'shipping_usd_minor'=>0,'tax_usd_minor'=>0,
          'lines'=>[['qty'=>1,'unit_usd_minor'=>18000,'discount_usd_minor'=>500,
            'eligible_for_promotion'=>true,'free_gift'=>false]]
        ]);
        $res=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,$rules,$zone);
        $assert($res['selected_discount_id']==='pct15',"$currency/$zone selects highest 15pct");
        $assert($res['projected_discount_usd_minor']===2625,"$currency/$zone 175USD*15pct = 26.25");
        $assert($res['qualified_after_one_discount_usd_minor']===14875,"$currency/$zone 148.75USD post");
        $assert($res['qualified_after_one_discount_local_minor']===
            Andy_Commerce_Currency_OS_V1::convert_usd_minor(14875,$rate),"$currency/$zone FX after USD");
        $assert($res['eligible_gift_ids']===['gift99'],"$currency/$zone gift qualified but not awarded");
        $assert(!$res['actual_discount_applied']&&!$res['actual_gift_awarded']&&!$res['runtime_enabled'],"$currency/$zone OFF");
        $assert(!$res['shipping']['charge_waived']&&!$res['shipping']['special_parcel_fee_waived']&&!$res['shipping']['pillow_per_unit_fee_waived'],"$currency/$zone freight unchanged");
        $assert($res['shipping']['qualifies_by_policy_preview']===($zone===0||$zone===5||$zone===4),"$currency/$zone shipping uses post discount");
    }
}
$fixed['priority']=200;
$percent['discount_percent_bp']=1000; // USD17.50 < USD20
$ref=Andy_Commerce_Currency_OS_V1::quote([
 'currency'=>'USD','rate_micros'=>1000000,'rate_source'=>'DEV_SYNTHETIC',
 'rate_timestamp'=>1792600000,'threshold_usd_minor'=>0,'shipping_usd_minor'=>0,'tax_usd_minor'=>0,
 'lines'=>[['qty'=>1,'unit_usd_minor'=>17500,'discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>false]]
]);
$res=Andy_Commerce_M9_USD_Rule_Engine::preview($ref,[$fixed,$percent,$gift],5);
$assert($res['selected_discount_id']==='fixed20','fixed larger than ten percent');
$percent['discount_percent_bp']=10000;
$res=Andy_Commerce_M9_USD_Rule_Engine::preview($ref,[$fixed,$percent],5);
$assert($res['qualified_after_one_discount_usd_minor']===0,'100pct cap to zero');
foreach([
 ['discount_percent_bp'=>0],['discount_percent_bp'=>10001],
 ['discount_percent_bp'=>-1],['discount_percent_bp'=>'1500'],
 ['discount_percent_bp'=>1500,'discount_usd_minor'=>1000],
 []] as $case) {
    $bad=$rule('bad','discount',1000,100);
    unset($bad['discount_usd_minor']);
    $bad=array_merge($bad,$case);
    $reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview($ref,[$bad],5),'invalid percent mode');
}
$giftFields=Andy_Commerce_Marketing_V2_Rule_Fields::normalize('free_gift',
 ['free_gift'=>['threshold'=>'99.00','product_id'=>'1234']]);
$assert($giftFields===['threshold_minor'=>9900,'product_id'=>1234],'gift schema fixed');
$reject(fn()=>Andy_Commerce_Marketing_V2_Rule_Fields::normalize('free_gift',
 ['free_gift'=>['product_id'=>'1234']]),'missing gift threshold');
$reject(fn()=>Andy_Commerce_Marketing_V2_Rule_Fields::normalize('free_gift',
 ['free_gift'=>['threshold'=>'0','product_id'=>'1234']]),'zero gift threshold');
echo "PASS MARKETING_M10_RULES={$n}; FIVE_FX=5; ZONES=5; REAL_AWARDS=OFF\n";
