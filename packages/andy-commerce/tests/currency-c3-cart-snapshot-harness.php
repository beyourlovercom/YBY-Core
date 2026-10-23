<?php
declare(strict_types=1);
define('ABSPATH','/unit/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-c2c3-preview.php';
require dirname(__DIR__).'/includes/class-andy-commerce-c3-cart-snapshot-adapter.php';
$n=0;
$ok=static function(bool $value,string $label) use (&$n):void{$n++;if(!$value)throw new RuntimeException($label);};
$cart=[
 'base_currency'=>'USD','threshold_usd_minor'=>6900,'shipping_usd_minor'=>3000,'tax_usd_minor'=>0,
 'lines'=>[
  ['quantity'=>1,'unit_usd_minor'=>9900,'applied_discount_usd_minor'=>1000,'eligible_for_promotion'=>true,'free_gift'=>false,'special_shipping'=>false],
  ['quantity'=>1,'unit_usd_minor'=>4000,'applied_discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>false,'special_shipping'=>true],
  ['quantity'=>1,'unit_usd_minor'=>3000,'applied_discount_usd_minor'=>0,'eligible_for_promotion'=>false,'free_gift'=>true,'special_shipping'=>false],
 ]
];
$rules=[];
foreach(['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000] as $code=>$micro){
 $request=Andy_Commerce_C3_Cart_Snapshot_Adapter::quote_request($cart,$code,$micro,'DEV_SYNTHETIC',1792600000);
 $q=Andy_Commerce_Currency_C2C3_Preview::calculate($request,$rules,5,false);
 $ok($q['eligible_subtotal_usd_minor']===8900,'discounted regular USD eligible unchanged');
 $ok($q['merchandise_usd_minor']===15900,'oversized item and gift included accounting');
 $ok($q['shipping_actual_usd_minor']===3000,'special shipping USD30 held');
 $ok(!$q['shipping_policy_preview']['special_parcel_fee_waived'],'special fee not waived');
 $ok(!$q['real_checkout_enabled'],'read-only adapter never activates checkout');
}
foreach([
 ['base_currency'=>'EUR'],
 ['lines'=>[['quantity'=>1]]],
 ['lines'=>[]],
 ['lines'=>array_fill(0,101,$cart['lines'][0])],
 ['lines'=>[['quantity'=>0,'unit_usd_minor'=>100,'applied_discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>false,'special_shipping'=>false]]]
] as $invalid){
 try{Andy_Commerce_C3_Cart_Snapshot_Adapter::quote_request(array_replace($cart,$invalid),'EUR',900000,'DEV_SYNTHETIC',1792600000);$ok(false,'bad snapshot must fail');}
 catch(InvalidArgumentException $e){$ok(true,'unverified snapshot rejected');}
}
echo "PASS C3 read-only explicit cart snapshot: {$n} assertions\n";
