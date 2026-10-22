<?php
/** M8 pure simulation: never invokes WooCommerce or WordPress writes. */
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/class-andy-commerce-m8-promotion-evaluator.php';
$n=0;
$ok=static function(bool $cond,string $name)use(&$n):void {
 if(!$cond)throw new RuntimeException("M8 FAIL: ".$name);
 ++$n;
};
$cart=['items'=>[
 ['product_id'=>101,'quantity'=>2,'discounted_line_minor'=>8000],
 ['product_id'=>202,'quantity'=>1,'discounted_line_minor'=>4500]
],'currency'=>'USD','country'=>'US','manual_coupon'=>null];
$draft=static function(string $id,string $type,array $rule,int $priority=0):array {
 return ['id'=>$id,'type'=>$type,'rule'=>$rule,'status'=>'draft','active'=>false,
 'currency'=>'USD','countries'=>['US'],'priority'=>$priority,'stack_with_coupon'=>false];
};
$get=static fn(array $c,array $ds):array=>Andy_Commerce_M8_Promotion_Evaluator::preview($c,$ds);
$spend=$draft('spend','spend_save',['threshold_minor'=>6900,'discount_minor'=>500]);
$r=$get($cart,[$spend]);
$ok($r['projected_discount_minor']===500,'spent threshold fixed amount');
$ok($r['applied']===false&&$r['runtime_enabled']===false&&$r['gift_awarded']===false,'no active discount or gift');
$ok($r['qualified_minor']===12500,'post-discount basis without tax or shipping');
$ok($get($cart,[$draft('p','spend_save',['threshold_minor'=>6900,'percent_bp'=>1250])])['projected_discount_minor']===1562,'percentage basis points');
$ok($get($cart,[$draft('b','bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1,'percent_bp'=>5000])])['projected_discount_minor']===2250,'BOGO distinct SKU');
$ok($get($cart,[$draft('loop','bogo',['buy_product_id'=>101,'buy_qty'=>1,'get_product_id'=>101,'get_qty'=>1,'percent_bp'=>5000])])['projected_discount_minor']===0,'self gift loop denied');
$ok($get($cart,[$draft('bundle','bundles',['product_ids'=>[101,202],'discount_minor'=>800])])['projected_discount_minor']===800,'bundle discount');
$ok($get($cart,[$draft('partial','bundles',['product_ids'=>[101,999],'discount_minor'=>800])])['projected_discount_minor']===0,'bundle missing product denied');
$ok($get($cart,[$draft('tier','tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>3,'percent_bp'=>2000]]])])['projected_discount_minor']===2500,'highest applicable tier');
$ok($get($cart,[$draft('gift','free_gift',['product_id'=>999])])['gift_awarded']===false,'no gift award');
$ok($get($cart,[$draft('gift','free_gift',['product_id'=>999])])['projected_discount_minor']===0,'free gift remains disabled');
$ok($get($cart,[$draft('lo','spend_save',['threshold_minor'=>6900,'discount_minor'=>100],1),$draft('hi','spend_save',['threshold_minor'=>6900,'discount_minor'=>500],3)])['selected_campaign']==='hi','deterministic priority');
$ok($get($cart,[$draft('b','spend_save',['threshold_minor'=>6900,'discount_minor'=>100]),$draft('a','spend_save',['threshold_minor'=>6900,'discount_minor'=>200])])['selected_campaign']==='a','stable ID tie break');
$withCoupon=$cart;$withCoupon['manual_coupon']='SAVE10';
$ok($get($withCoupon,[$spend])['projected_discount_minor']===0,'existing manual coupon blocks default non-stacking');
$stack=$spend;$stack['stack_with_coupon']=true;
$ok($get($withCoupon,[$stack])['projected_discount_minor']===500,'explicit draft preview stack policy');
$nonUS=$cart;$nonUS['country']='DE';
$ok($get($nonUS,[$spend])['projected_discount_minor']===0,'country restriction');
$other=$cart;$other['currency']='EUR';
$ok($get($other,[$spend])['projected_discount_minor']===0,'currency restriction');
$invalid=$cart;$invalid['items'][0]['discounted_line_minor']=-1;
$ok(($get($invalid,[$spend])['invalid']??false)===true,'negative amount denied');
$invalid=$cart;$invalid['items']=[];
$ok(($get($invalid,[$spend])['invalid']??false)===true,'empty cart denied');
$unknown=$draft('u','spend_save',['threshold_minor'=>6900,'discount_minor'=>200]);
$unknown['active']=true;
$ok($get($cart,[$unknown])['projected_discount_minor']===0,'live active records excluded from offline draft evaluator');
$bad=$draft('bad','spend_save',['threshold_minor'=>6900,'discount_minor'=>100000001]);
$ok($get($cart,[$bad])['projected_discount_minor']===0,'overflow denied');
$over=$cart;$over['items']=array_fill(0,101,$cart['items'][0]);
$ok(($get($over,[$spend])['invalid']??false)===true,'line count bounded');
echo "M8_SIMULATION_PASS=$n; RUNTIME_WRITES=0; GIFTS=OFF; SHIPPING_UNCHANGED\n";
