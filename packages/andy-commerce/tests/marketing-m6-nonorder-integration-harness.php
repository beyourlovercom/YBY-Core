<?php
/** M6 synthetic end-to-end non-order Marketing shipping shadow canary. */
declare(strict_types=1);
$dir=dirname(__DIR__);
require_once $dir.'/includes/class-andy-commerce-m4-shipping-shadow.php';
require_once $dir.'/includes/class-andy-commerce-m5-progress-projection.php';
require_once $dir.'/includes/class-andy-commerce-m5-woo-cart-adapter.php';
$checks=0;
$ok=static function(bool $v,string $case) use (&$checks):void {
    ++$checks; if(!$v)throw new RuntimeException('M6 FAIL: '.$case);
};
$old='pre_discount_merchandise_subtotal';
$new='post_discount_merchandise_subtotal_excluding_tax_and_shipping';
$shadow=Andy_Commerce_M4_Shipping_Shadow::compare([[
    'line_subtotal_minor'=>6000,'line_total_minor'=>4500,
    'gift'=>false,'eligible'=>true,
]],4900);
$ok($shadow['legacy_eligible'] && !$shadow['proposed_eligible']
    && $shadow['disagreement'] && $shadow['runtime_enabled']===false,'legacy mismatch shadow only');
$cart=[
    'items'=>[['totals'=>['line_total'=>'4500']]],
    'totals'=>['currency_minor_unit'=>2],
    'shipping_rates'=>[['shipping_rates'=>[['rate_id'=>'flat_rate:7','selected'=>true]]]],
];
$source=static fn(array $c,string $b,bool $trusted=true):array =>
    Andy_Commerce_M5_Woo_Cart_Adapter::source($c,$b,4900,$trusted);
$legacy=Andy_Commerce_M5_Progress_Projection::project($source($cart,$old));
$ok(!$legacy['show_progress'] && $legacy['reason']==='legacy_policy_migration_pending','real legacy BYL49 hides progress');
$planned=Andy_Commerce_M5_Progress_Projection::project($source($cart,$new));
$ok($planned['show_progress'] && $planned['remaining_minor']===400 && $planned['percent']===91,'proposed policy progress offline only');
$ok(!$planned['claim_free_shipping'] && !$planned['gift_enabled'],'cannot award shipping or gift');
$untrusted=Andy_Commerce_M5_Progress_Projection::project($source($cart,$new,false));
$ok(!$untrusted['show_progress'] && $untrusted['reason']==='woo_authority_unconfirmed','no visitor-fed Woo evidence');
$noRate=$cart;$noRate['shipping_rates'][0]['shipping_rates'][0]['selected']=false;
$ok(!Andy_Commerce_M5_Progress_Projection::project($source($noRate,$new))['show_progress'],'no selected shipping rate');
$multi=$cart;$multi['shipping_rates'][]=$cart['shipping_rates'][0];
$ok(!Andy_Commerce_M5_Progress_Projection::project($source($multi,$new))['show_progress'],'multiple selected packages fail closed');
$bad=$cart;$bad['items'][0]['totals']['line_total']='-1';
$ok(!Andy_Commerce_M5_Progress_Projection::project($source($bad,$new))['show_progress'],'negative amount rejected');
$empty=$cart;$empty['items']=[];
$ok(!Andy_Commerce_M5_Progress_Projection::project($source($empty,$new))['show_progress'],'empty cart hidden');
$xss=$cart;$xss['shipping_rates'][0]['shipping_rates'][0]['rate_id']='flat_rate:<script>';
$ok(!Andy_Commerce_M5_Progress_Projection::project($source($xss,$new))['show_progress'],'untrusted rate ID rejected');
$free=$cart;$free['items'][0]['totals']['line_total']='6000';
$free['shipping_rates'][0]['shipping_rates'][0]['rate_id']='free_shipping:3';
$full=Andy_Commerce_M5_Progress_Projection::project($source($free,$new));
$ok($full['percent']===100 && $full['remaining_minor']===0 && !$full['claim_free_shipping'],'threshold never grants free rate');
$entry=file_get_contents($dir.'/andy-commerce.php');
$ok(!str_contains($entry,'class-andy-commerce-m4-shipping-shadow')
    && !str_contains($entry,'class-andy-commerce-m5-progress-projection')
    && !str_contains($entry,'class-andy-commerce-m5-woo-cart-adapter'),'all synthetic components unbootstrapped');
echo "M6_MARKETING_NONORDER_INTEGRATION_PASS={$checks}; WRITES=0; GIFTS=OFF; SHIPPING_POLICY_UNCHANGED\n";
