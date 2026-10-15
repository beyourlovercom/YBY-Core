<?php
/**
 * Offline, deterministic M2 contract tests. No WordPress, Woo DB, remote IO.
 */
declare(strict_types=1);
require dirname(__DIR__).'/includes/class-andy-commerce-marketing-pure-evaluator.php';
function check(bool $v,string $message):void {
    if (!$v) { fwrite(STDERR,"FAIL: {$message}\n");exit(1); }
}
function cart(array $lines,int $coupons=0,string $currency='USD'):array {
    return ['currency'=>$currency,'lines'=>$lines,'applied_coupon_count'=>$coupons];
}
function line(string $key,int $id,int $qty,int $money,array $rest=[]):array {
    return array_merge(['key'=>$key,'product_id'=>$id,'quantity'=>$qty,'line_total_minor'=>$money],$rest);
}
function offer(string $id,string $type,array $policy=[]):array {
    return array_merge(['id'=>$id,'type'=>$type,'currency'=>'USD','status'=>'draft','priority'=>0],$policy);
}
function runEval(array $cart,array $offers,array $context=[]):array {
    return Andy_Commerce_Marketing_Pure_Evaluator::evaluate($cart,$offers,array_merge(['sandbox'=>true],$context));
}
function blockedThrows(callable $test,string $msg):void {
    try {$test();} catch(InvalidArgumentException $e){return;}
    check(false,$msg);
}
// The only money input is pre-normalized Woo line_total after coupons, excluding taxes.
// $120 merchandise - $20 coupon: $100 qualifying; - $30 coupon: $90.
$c100=cart([line('sku1',1001,1,10000)],1);
$c90=cart([line('sku1',1001,1,9000)],1);
$spend=offer('spend-100','spend_save',[
    'threshold_minor'=>10000,'discount_minor'=>1000,'stack_with_coupon'=>true
]);
$r=runEval($c100,[$spend]);
check($r['basis_minor']===10000 && $r['discount_minor']===1000,'threshold inclusive at discounted $100');
check($r['after_auto_minor']===9000 && $r['free_shipping_eligibility_minor']===9000,'shipping threshold is after automatic award');
check($r['basis']==='post_discount_merchandise_subtotal_excluding_tax_and_shipping','canonical eligibility basis');
check($r['requires_checkout_recalculation']===true && $r['mode']==='isolated_sandbox_only','no runtime claims');
check(runEval($c90,[$spend])['winner']===null,'coupon brings spend under threshold');
check(runEval($c100,[offer('default','spend_save',['threshold_minor'=>9000,'discount_minor'=>500])])['discount_minor']===0,
    'automatic campaigns nonstackable with a manual coupon by default');
check(runEval(cart([line('a',1,1,15000)],2),[$spend])['blocked_reason']==='more_than_one_coupon','single entered code gate');
check(runEval(cart([line('a',1,1,12000)],0,'EUR'),[$spend])['winner']===null,'mismatched currency off');
check(runEval(cart([line('a',1,1,12000)]),[offer('other-country','spend_save',
    ['threshold_minor'=>10000,'discount_minor'=>1000,'country'=>'US'])])['winner']===null,'unknown destination => no zone claim');
check(runEval(cart([line('a',1,1,12000)]),[offer('other-country','spend_save',
    ['threshold_minor'=>10000,'discount_minor'=>1000,'country'=>'US'])],['country'=>'US'])['discount_minor']===1000,
    'matching zone can qualify');
check(runEval(cart([line('a',1,1,12000)]),[offer('time','spend_save',
    ['threshold_minor'=>10000,'discount_minor'=>1000,'start_unix'=>2000])])['winner']===null,'missing clock blocks scheduled campaign');
check(runEval(cart([line('a',1,1,12000)]),[offer('time','spend_save',
    ['threshold_minor'=>10000,'discount_minor'=>1000,'start_unix'=>2000,'end_unix'=>3000])],['now_unix'=>2500])['discount_minor']===1000,
    'deterministic injected campaign window');
check(runEval(cart([line('a',1,1,12000)]),[offer('time','spend_save',
    ['threshold_minor'=>10000,'discount_minor'=>1000,'end_unix'=>2500])],['now_unix'=>2500])['winner']===null,
    'campaign ending timestamp exclusive');
// Gift lines never count toward spend, including discount after coupon.
$giftCart=cart([line('paid',12,1,9900),line('free-gift',13,1,5000,['is_reward'=>true])]);
check(runEval($giftCart,[offer('goal','spend_save',['threshold_minor'=>10000,'discount_minor'=>900])])['basis_minor']===9900,
    'free gift excluded from its own threshold');
check(runEval($giftCart,[offer('goal','spend_save',['threshold_minor'=>10000,'discount_minor'=>900])])['winner']===null,
    'gift money must not unlock award');
check(runEval(cart([line('paid',12,1,9900),line('skip',14,1,3000,['eligible'=>false])]),[])['basis_minor']===9900,
    'nonmerchandise ineligible lines excluded');

// Buy 1 get 1, same SKU => floor(3/2)=1 gift. Cheapest post-discount unit allocated.
$bogo=offer('buy1get1','bogo',['buy_product_ids'=>[4],'get_product_ids'=>[4],
    'buy_qty'=>1,'get_qty'=>1,'reward_bps'=>10000]);
check(runEval(cart([line('a',4,3,2999)]),[$bogo])['discount_minor']===999,
    'one cheapest remainder-distributed unit free from three units 2999 cents');
check(runEval(cart([line('a',4,1,1000)]),[$bogo])['discount_minor']===0,
    'one BOGO SKU unit alone not enough');
check(runEval(cart([line('a',4,4,4000)]),[$bogo])['discount_minor']===2000,
    'four units yields two free');
$half=offer('half','bogo',['buy_product_ids'=>[4],'get_product_ids'=>[4],
    'buy_qty'=>1,'get_qty'=>1,'reward_bps'=>5000]);
check(runEval(cart([line('a',4,2,2001)]),[$half])['discount_minor']===500,
    '50 percent discount rounded down in integer cents');
// Different pools: buy 2 x product4 -> discount cheapest product5 reward.
$separate=offer('buy2get1','bogo',['buy_product_ids'=>[4],'get_product_ids'=>[5],
    'buy_qty'=>2,'get_qty'=>1]);
check(runEval(cart([line('buy',4,2,10000),line('reward',5,2,1599)]),[$separate])['discount_minor']===799,
    'BOGO disjoint buy/award pools and cheapest unit');
$partial=offer('mixed','bogo',['buy_product_ids'=>[4,5],'get_product_ids'=>[5],
    'buy_qty'=>1,'get_qty'=>1]);
check(runEval(cart([line('buy',4,1,10000),line('reward',5,1,6000)]),[$partial])['discount_minor']===0,
    'partially overlapping BOGO selector fails closed to avoid self qualification');
check(runEval(cart([line('buy',4,1,0),line('reward',5,1,2000)]),[
    offer('zero-buy','bogo',['buy_product_ids'=>[4],'get_product_ids'=>[5],'buy_qty'=>1,'get_qty'=>1])
])['discount_minor']===0,'zero-value free item not qualifying buy');

// Fixed-price bundle: total $60 coupon-discounted products, $50 bundle price => $10 benefit.
$bundle=offer('kit','bundle',['requires'=>[['product_id'=>11,'quantity'=>1],['product_id'=>22,'quantity'=>1]],
    'bundle_price_minor'=>5000]);
check(runEval(cart([line('one',11,1,3500),line('two',22,1,2500)]),[$bundle])['discount_minor']===1000,
    'bundle fixed-price selected set');
check(runEval(cart([line('one',11,1,3500)]),[$bundle])['winner']===null,'incomplete bundle no award');
check(runEval(cart([line('one',11,2,7000),line('two',22,2,5000)]),[$bundle])['discount_minor']===2000,
    'two complete bundle sets');
check(runEval(cart([line('one',11,1,3500),line('two',22,1,2500)]),[
    offer('bundle-percent','bundle',['requires'=>[['product_id'=>11,'quantity'=>1],['product_id'=>22,'quantity'=>1]],
      'discount_bps'=>1000])
])['discount_minor']===600,'10% bundle discount');
check(runEval(cart([line('one',11,1,3500),line('two',22,1,2500)]),[
    offer('bundle-overprice','bundle',['requires'=>[['product_id'=>11,'quantity'=>1],['product_id'=>22,'quantity'=>1]],
      'bundle_price_minor'=>7000])
])['discount_minor']===0,'bundle cannot charge more as a discount');

// Tiered quantity: 2 units = 10%, 3 = 20%. Other SKUs excluded.
$tier=offer('qty-tier','tiered',['product_ids'=>[11],
    'tiers'=>[['min_qty'=>2,'discount_bps'=>1000],['min_qty'=>3,'discount_bps'=>2000]]]);
check(runEval(cart([line('one',11,2,3001),line('other',30,3,10000)]),[$tier])['discount_minor']===300,
    'tier 2, currency-minor rounding, excluded unrelated product');
check(runEval(cart([line('one',11,3,4500)]),[$tier])['discount_minor']===900,'tier 3 20 percent');
check(runEval(cart([line('one',11,1,1500)]),[$tier])['discount_minor']===0,'below quantity tier no promo');

// Mutually exclusive campaigns. Priority wins; ties choose higher saving then lexical ID.
$offers=[
    offer('small','spend_save',['threshold_minor'=>1,'discount_minor'=>300,'priority'=>10]),
    offer('large','spend_save',['threshold_minor'=>1,'discount_minor'=>500,'priority'=>10]),
    offer('top-priority','spend_save',['threshold_minor'=>1,'discount_minor'=>100,'priority'=>11]),
];
$base=cart([line('valid',1,1,5000)]);
check(runEval($base,$offers)['winner']['id']==='top-priority','higher business priority wins, no stacking');
check(runEval($base,array_slice($offers,0,2))['winner']['id']==='large','equal priority best permissible benefit');
check(runEval($base,[offer('z','spend_save',['threshold_minor'=>1,'discount_minor'=>500]),
    offer('a','spend_save',['threshold_minor'=>1,'discount_minor'=>500])])['winner']['id']==='a',
    'deterministic tie by campaign ID');
check(runEval($base,[offer('over','spend_save',['threshold_minor'=>1,'discount_minor'=>9000])])['discount_minor']===5000,
    'cannot discount beyond eligible merchandise money');
check(runEval($base,[offer('invalid','spend_save',['threshold_minor'=>1,'discount_bps'=>500,'discount_minor'=>500]),
    offer('valid','spend_save',['threshold_minor'=>1,'discount_minor'=>300])])['winner']['id']==='valid',
    'invalid campaign ignored without affecting valid offers');
// Abuse, incomplete state, external caller fails closed.
blockedThrows(fn()=>Andy_Commerce_Marketing_Pure_Evaluator::evaluate($base,[]),'no non-sandbox callers');
blockedThrows(fn()=>runEval(cart([line('a',1,1,-1)]),[]),'negative merchandise total');
blockedThrows(fn()=>runEval(cart([line('a',1,0,0)]),[]),'invalid quantity');
blockedThrows(fn()=>runEval(cart([line('a',1,1,100),line('a',2,1,100)]),[]),'duplicate line key');
blockedThrows(fn()=>runEval(cart([line('a',1,1,1000000000001)]),[]),'money upper bound');
blockedThrows(fn()=>runEval(cart([line('a',1,5001,100)]),[]),'unit upper bound');
blockedThrows(fn()=>runEval($base,[offer('same','spend_save',['threshold_minor'=>1,'discount_minor'=>10]),
    offer('same','spend_save',['threshold_minor'=>1,'discount_minor'=>100])]),
    'duplicate campaign ID must be rejected');
check(runEval(cart([line('pay',11,1,1500),line('free',11,2,0)]),[$tier])['discount_minor']===0,
    'free zero-priced merchandise cannot artificially unlock quantity tier');
check(runEval(cart([line('pay',11,3,4500),line('free',11,2,0)]),[$tier])['discount_minor']===900,
    'real paid units keep tier reward while free units are ignored');

$bootstrap=file_get_contents(dirname(__DIR__).'/andy-commerce.php');
$runtime=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
check(!str_contains($bootstrap,'marketing-pure-evaluator')&&!str_contains($runtime,'marketing-pure-evaluator'),
    'sandbox evaluator NOT wired into Woo runtime/bootstrap');
echo "PASS M2 pure sandbox: post-coupon ex-tax threshold, BOGO, bundles, tiers, Spend & Save, single coupon, non-stack, regions, time, gifts and safety\n";
