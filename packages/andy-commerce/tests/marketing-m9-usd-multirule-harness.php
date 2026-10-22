<?php
/** M9 standalone multi-promotion rule engine. Offline only, no Woo mutations. */
declare(strict_types=1);
define('ABSPATH','/tmp/andy-commerce-m9/');
require dirname(__DIR__) . '/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__) . '/includes/class-andy-commerce-m9-usd-rule-engine.php';
$count=0;
function m9check(bool $condition,string $message):void{
    global $count;++$count;
    if (!$condition)throw new RuntimeException('FAIL: '.$message);
}
function m9reject(callable $callback,string $message):void {
    try {$callback();}catch(InvalidArgumentException $ex){m9check(true,$message);return;}
    m9check(false,'Expected rejection: '.$message);
}
function m9quote(string $currency,int $rate,int $goods=18000,int $discount=1500):array {
    return Andy_Commerce_Currency_OS_V1::quote([
      'currency'=>$currency,'rate_micros'=>$rate,
      'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
      'threshold_usd_minor'=>0,'shipping_usd_minor'=>1000,
      'tax_usd_minor'=>700,
      'lines'=>[
        ['qty'=>1,'unit_usd_minor'=>$goods,'discount_usd_minor'=>$discount,
          'eligible_for_promotion'=>true,'free_gift'=>false],
        ['qty'=>2,'unit_usd_minor'=>3000,'discount_usd_minor'=>0,
          'eligible_for_promotion'=>false,'free_gift'=>false],
      ]]);
}
function m9rules():array {
    return [
      ['id'=>'gift99','name'=>'$99 Gift','type'=>'gift','active'=>false,
       'priority'=>100,'threshold_usd_minor'=>9900,'gift_product_id'=>123],
      ['id'=>'off169','name'=>'$169 off $20','type'=>'discount','active'=>false,
       'priority'=>100,'threshold_usd_minor'=>16900,'discount_usd_minor'=>2000],
      ['id'=>'off129','name'=>'$129 off $10','type'=>'discount','active'=>false,
       'priority'=>200,'threshold_usd_minor'=>12900,'discount_usd_minor'=>1000],
    ];
}
foreach(['USD'=>1000000,'EUR'=>890000,'GBP'=>746500,'AUD'=>1421700,'CAD'=>1404200] as $currency=>$rate) {
    foreach([0=>6900,1=>null,3=>16900,4=>12900,5=>6900] as $zone=>$minimum) {
        $result=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote($currency,$rate),m9rules(),$zone);
        m9check($result['qualifying_before_campaign_usd_minor']===16500,
          "$currency zone $zone source subtotal USD165 after prior discounts");
        m9check($result['selected_discount_id']==='off129'
            && $result['projected_discount_usd_minor']===1000,
          "$currency zone $zone 129 rule selected, 169 not qualified");
        m9check($result['qualified_after_one_discount_usd_minor']===15500,
          "$currency zone $zone new USD155 qualifies shipping after discount");
        m9check($result['qualified_after_one_discount_local_minor']
            ===Andy_Commerce_Currency_OS_V1::convert_usd_minor(15500,$rate),
          "$currency zone $zone local amount derived only from USD155");
        m9check($result['eligible_gift_ids']===['gift99'],
          "$currency zone $zone gift eligible, not awarded");
        m9check(!$result['shipping']['charge_waived']
            && !$result['shipping']['special_parcel_fee_waived']
            && !$result['shipping']['pillow_per_unit_fee_waived'],
          "$currency zone $zone none of shipping/special fees waived");
        m9check($result['shipping']['threshold_usd_minor']===$minimum,
          "$currency zone $zone native policy threshold retained");
        m9check($result['shipping']['qualifies_by_policy_preview']===($zone!==1&&$zone!==3),
          "$currency zone $zone region shipping recalculated after M9 discount");
        m9check($result['mode']==='DEV_SHADOW_NO_PAYMENT'
            && !$result['runtime_enabled'] && !$result['actual_discount_applied']
            && !$result['actual_gift_awarded'],
          "$currency zone $zone no real award or checkout");
    }
}
$quote=m9quote('USD',1000000,19000,0);
$x=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),3);
m9check($x['selected_discount_id']==='off169'
    && $x['projected_discount_usd_minor']===2000
    && $x['qualified_after_one_discount_usd_minor']===17000,
    'USD190 qualifies 169 off20; reduced subtotal USD170');
m9check($x['shipping']['qualifies_by_policy_preview'],
    'High zone USD170 retains 169 shipping qualification');
$quote=m9quote('USD',1000000,18000,500);
$x=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),3);
m9check($x['selected_discount_id']==='off169'
    && $x['qualified_after_one_discount_usd_minor']===15500
    && !$x['shipping']['qualifies_by_policy_preview'],
    'USD175 pre-award, USD155 post-award fails zone169 shipping');
$quote=m9quote('EUR',890000,11900,0);
$x=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),5);
m9check($x['selected_discount_id']===null
    && $x['eligible_gift_ids']===['gift99']
    && $x['qualified_after_one_discount_usd_minor']===11900,
    'USD119 qualifies gift but not discount');
$quote=m9quote('USD',1000000,9000,0);
$x=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),5);
m9check($x['eligible_gift_ids']===[] && $x['selected_discount_id']===null,
    'USD90 below all gift/discount thresholds');
$rules=m9rules();$rules[2]['priority']=1;
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000,19000,0),$rules,5);
m9check($x['selected_discount_id']==='off169','Highest valid benefit overrides priority');
$rules=m9rules();$rules[1]['discount_usd_minor']=1000;$rules[2]['discount_usd_minor']=1000;
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000,19000,0),$rules,5);
m9check($x['selected_discount_id']==='off129','Tie prefers higher priority');
$rules=m9rules();$rules[1]['active']=true;
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000,19000,0),$rules,5);
m9check($x['selected_discount_id']==='off129','Active unapproved rule never evaluated');
$rules=m9rules();$rules[0]['active']=true;
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5);
m9check($x['eligible_gift_ids']===[],'Active unapproved gift never projected');
$rules=m9rules();$rules[1]['discount_usd_minor']=500000;
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000,19000,0),$rules,5);
m9check($x['projected_discount_usd_minor']===19000
    && $x['qualified_after_one_discount_usd_minor']===0
    && !$x['shipping']['qualifies_by_policy_preview'],
    'Discount capped to qualified base, cannot produce negative/free-shipping');
$rules=m9rules();$rules[0]['id']=$rules[1]['id'];
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Duplicate IDs');
$rules=m9rules();$rules[0]['gift_product_id']=0;
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Invalid gift product ID');
$rules=m9rules();$rules[1]['discount_usd_minor']=-10;
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Negative fixed discount');
$rules=m9rules();$rules[1]['discount_usd_minor']=0;
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Zero fixed discount');
$rules=m9rules();$rules[1]['type']='BOGO';
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Unknown type');
$rules=m9rules();$rules[0]['threshold_usd_minor']='9900';
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'String USD threshold prohibited');
$rules=m9rules();$rules[1]['active']='false';
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Malformed activity state');
$rules=m9rules();$rules[1]['priority']=1001;
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Invalid priority');
$rules=m9rules();$rules[1]['id']='danger<script>';
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Unsafe ID');
$rules=m9rules();$rules[]=array_merge($rules[0],['id'=>'ship1','type'=>'shipping','source'=>'manual']);
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Manual free-shipping authority rejected');
$rules=array_fill(0,101,m9rules()[0]);
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),$rules,5),'Too many rules');
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),m9rules(),2),'Unknown Woo zone');
$quote=m9quote('USD',1000000);$quote['mode']='LIVE_PAYMENT';
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),5),'Not a shadow ledger');
$quote=m9quote('USD',1000000);$quote['eligible_subtotal_usd_minor']=$quote['merchandise_net_usd_minor']+1;
m9reject(fn()=>Andy_Commerce_M9_USD_Rule_Engine::preview($quote,m9rules(),5),'Invalid eligible greater than net');
$x=Andy_Commerce_M9_USD_Rule_Engine::preview(m9quote('USD',1000000),m9rules(),1,true);
m9check($x['shipping']['qualifies_by_policy_preview'] && $x['shipping']['native_rate_verified']
    && !$x['shipping']['charge_waived'],'Hong Kong only has verified-native free-method preview');
echo "PASS Marketing M9 multi-rule: {$count} USD-first assertions across 5 currencies and 5 zones. All rewards OFF.\n";
