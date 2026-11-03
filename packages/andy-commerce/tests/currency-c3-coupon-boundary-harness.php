<?php
/** Synthetic boundary only; no live coupons or Woo writes. */
declare(strict_types=1);
define('ABSPATH','/test/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-c2c3-preview.php';
require dirname(__DIR__).'/includes/class-andy-commerce-c3-cart-snapshot-adapter.php';
$n=0;
foreach(['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000] as $currency=>$fx){
 foreach([[6900,0,true],[6900,1,false],[6899,0,false],[7900,1000,true],[7900,1001,false],[7900,1010,false]] as [$gross,$discount,$qualify]){
  $snapshot=['base_currency'=>'USD','verified_woo_data'=>true,'threshold_usd_minor'=>6900,'shipping_usd_minor'=>1000,'tax_usd_minor'=>0,
    'lines'=>[['quantity'=>1,'unit_usd_minor'=>$gross,'applied_discount_usd_minor'=>$discount,'eligible_for_promotion'=>true,'free_gift'=>false,'special_shipping'=>false]]];
  $request=Andy_Commerce_C3_Cart_Snapshot_Adapter::quote_request($snapshot,$currency,$fx,'DEV_SYNTHETIC',1792600000);
  $r=Andy_Commerce_Currency_C2C3_Preview::calculate($request,[],5,false);
  if($r['eligible_subtotal_usd_minor']!==$gross-$discount||$r['shipping_policy_preview']['qualifies_by_policy_preview']!==$qualify)
    throw new RuntimeException('Post-coupon boundary mismatch '.$currency.' '.($gross-$discount));
  if($r['shipping_policy_preview']['charge_waived']||$r['real_checkout_enabled'])
    throw new RuntimeException('Shadow preview must not modify Woo shipping or checkout');
  $n+=3;
 }
}
echo "PASS C3 coupon shipping boundaries: {$n} cross-currency assertions; synthetic only\n";
