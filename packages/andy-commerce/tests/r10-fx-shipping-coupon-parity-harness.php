<?php
/**
 * R10 offline rehearsal: five synthetic FX rates and two-coupon fail-closed.
 * This NEVER contacts www/Dev, bootstraps Woo or reads private customer carts.
 */
declare(strict_types=1);
define( 'ABSPATH', '/synthetic-only/' );
require_once __DIR__ . '/../includes/class-andy-commerce-r10-fx-shipping-parity-shadow.php';

$assert = static function ($ok, $label): void {
    if (!$ok) {fwrite(STDERR, 'FAIL_R10_SHADOW '.$label.PHP_EOL); exit(1);}
};
$line = static fn(int $before, int $after, bool $gift=false, bool $eligible=true): array =>
    ['gift'=>$gift, 'eligible'=>$eligible, 'line_subtotal_minor'=>$before, 'line_total_minor'=>$after];
$rates = ['USD'=>1000000,'EUR'=>920000,'GBP'=>800000,'AUD'=>1520000,'CAD'=>1350000];
$scenarios = [
    ['below_69',[$line(6899,6899)],[],false,false],
    ['exact_69',[$line(6900,6900)],[],true,true],
    ['above_69',[$line(6901,6901)],[],true,true],
    ['coupon_79_to_64',[$line(7900,6400)],['TEST10'],false,true],
    ['coupon_79_to_69',[$line(7900,6900)],['TEST10'],true,true],
    ['gift_excluded',[$line(6800,6800),$line(1000,0,true)],[],false,false],
    ['ineligible_excluded',[$line(6900,6900,false,false)],[],false,false],
];
$scored = 0; $examples=[];
foreach($rates as $currency=>$fx) {
    foreach($scenarios as [$id,$items,$coupons,$expect,$old]) {
        $r = Andy_Commerce_R10_FX_Shipping_Parity_Shadow::compare($items,$coupons,6900,$currency,$fx);
        $assert($r['shipping_preview_only']===$expect,$currency.'-'.$id.'-shipping');
        $assert($r['legacy_shipping_preview_only']===$old,$currency.'-'.$id.'-legacy');
        $assert($r['runtime_enabled']===false && $r['woo_mutations']===0
            && $r['woo_hooks_registered']===0,$currency.'-'.$id.'-side-effects');
        $assert($r['real_checkout_verified']===false
            && $r['fx_source']==='SYNTHETIC_NOT_YAYCURRENCY',$currency.'-'.$id.'-provenance');
        $assert($r['display_local_minor']===Andy_Commerce_Currency_OS_V1::convert_usd_minor(
            $r['source_usd_eligible_minor'],$fx),$currency.'-'.$id.'-conversion');
        $scored++;
        if($id==='coupon_79_to_64') $examples[$currency] = [
            'source_usd_minor'=>$r['source_usd_eligible_minor'],
            'synthetic_local_minor'=>$r['display_local_minor'],
            'shipping_preview_qualified'=>$r['shipping_preview_only'],
            'legacy_disagreement'=>$r['shipping_policy_disagreement'],
        ];
    }
    foreach([
        ['MULTI_COUPON',[$line(7900,6900)],['MANUAL_A','MANUAL_B'],6900,$currency,$fx],
        ['INVALID_FX',[$line(6900,6900)],[],6900,$currency,1],
    ] as [$label,$items,$codes,$threshold,$cur,$rate]) {
        try {
            Andy_Commerce_R10_FX_Shipping_Parity_Shadow::compare($items,$codes,$threshold,$cur,$rate);
            $assert(false,$label.'-'.$currency.'-accepted');
        } catch(InvalidArgumentException $e) {$scored++;}
    }
}
echo 'R10_OFFLINE_SHADOW_PASS='.$scored.'_CHECKS_5_SYNTHETIC_FX_7_SCENARIOS_2_FAIL_CLOSED_CASES'.PHP_EOL;
echo 'R10_EXAMPLES='.json_encode($examples,JSON_UNESCAPED_SLASHES).PHP_EOL;
