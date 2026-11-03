<?php
declare(strict_types=1);
define('ABSPATH','/unit/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-c2c3-preview.php';
$checks=0;
$ok=static function(bool $v,string $message) use (&$checks):void { ++$checks;if(!$v)throw new RuntimeException($message); };
$rates=['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000];
$rules=[['id'=>'promo10','name'=>'Promo','type'=>'discount','active'=>false,'priority'=>100,'threshold_usd_minor'=>9900,'discount_usd_minor'=>1000]];
foreach ($rates as $code=>$rate) {
  foreach ([9899,9900,10000] as $price) {
    $q=['currency'=>$code,'rate_micros'=>$rate,'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
      'threshold_usd_minor'=>9900,'shipping_usd_minor'=>1000,'tax_usd_minor'=>700,
      'lines'=>[['qty'=>1,'unit_usd_minor'=>$price,'discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>false]]];
    foreach ([0,1,3,4,5] as $zone) {
      $a=Andy_Commerce_Currency_C2C3_Preview::calculate($q,$rules,$zone,false);
      $ok($a['mode']==='DEV_SHADOW_NO_PAYMENT'&&!$a['real_checkout_enabled'],'no commerce writes');
      $ok($a['eligible_subtotal_usd_minor']===$price,'USD is eligibility base');
      $ok($a['projected_campaign_discount_usd_minor']===($price>=9900?1000:0),'threshold independent of local FX');
      $ok($a['order_actual_total_usd_minor']===$price+1700,'actual USD quote unaffected by shadow marketing');
      $ok($a['order_display_total_minor']===Andy_Commerce_Currency_OS_V1::convert_usd_minor($price,$rate)
         +Andy_Commerce_Currency_OS_V1::convert_usd_minor(1000,$rate)
         +Andy_Commerce_Currency_OS_V1::convert_usd_minor(700,$rate),'display-only total consistent');
      $ok(!$a['shipping_policy_preview']['charge_waived']&&!$a['shipping_native_rate_applied'],'shipping never waived by shadow');
      $ok($a['shipping_zone_id']===$zone,'verified zone reflected');
      $ok($a['fx_snapshot']['rate_micros']===$rate,'single immutable FX snapshot');
    }
  }
}
try {
 Andy_Commerce_Currency_C2C3_Preview::calculate([
  'currency'=>'EUR','rate_micros'=>0,'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
  'threshold_usd_minor'=>6900,'shipping_usd_minor'=>0,'tax_usd_minor'=>0,
  'lines'=>[['qty'=>1,'unit_usd_minor'=>5000,'discount_usd_minor'=>0]]
 ],$rules,5);
 $ok(false,'invalid FX must fail closed');
} catch(InvalidArgumentException $e){$ok(true,'invalid FX fails closed');}
echo "PASS C2/C3 cross-currency, zones, USD campaign reconciliation: {$checks} assertions\n";
