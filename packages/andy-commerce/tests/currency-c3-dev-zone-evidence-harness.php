<?php
/** Dev observed native Woo shipping zone ID contract, read-only regression.
 * Observed on dev.beyourlover.com Woo shipping admin on 2026-10-23.
 * Zone names and zones are mutable Woo configuration: evidence, NOT hardcoded routing.
 */
declare(strict_types=1);
define('ABSPATH','/unit/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-c2c3-preview.php';
$expected=[
  1=>['zone'=>'Hong Kong, Free Shipping on $0','threshold'=>null],
  5=>['zone'=>'Zone 1, $10 / $69 Free','threshold'=>6900],
  4=>['zone'=>'Zones, $20 / $129 Free','threshold'=>12900],
  3=>['zone'=>'War Zone, $30 / $169 Free','threshold'=>16900],
  0=>['zone'=>'Rest of world fallback','threshold'=>6900],
];
$n=0;
foreach($expected as $zone=>$e) {
 $req=['currency'=>'USD','rate_micros'=>1000000,'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
 'threshold_usd_minor'=>6900,'shipping_usd_minor'=>0,'tax_usd_minor'=>0,
 'lines'=>[['qty'=>1,'unit_usd_minor'=>12000,'discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>false]]];
 $quote=Andy_Commerce_Currency_C2C3_Preview::calculate($req,[],$zone,false);
 if($quote['shipping_policy_preview']['threshold_usd_minor']!==$e['threshold'])throw new RuntimeException('zone '.$zone.' mismatch');
 if($zone===1 && $quote['shipping_policy_preview']['qualifies_by_policy_preview'])throw new RuntimeException('HK free rate requires native verification');
 if($quote['shipping_policy_preview']['charge_waived'])throw new RuntimeException('Do not waive shipping');
 $n+=3;
}
echo "PASS C3 Dev observed zone ID evidence: {$n} checks, native Woo authority unchanged\n";
