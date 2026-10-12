<?php
/** M5 is only a non-registered, offline progress contract test. */
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-progress-projection.php';
$basis='post_discount_merchandise_subtotal_excluding_tax_and_shipping';
$good=['effective_policy_basis'=>$basis,'woocommerce_rate_confirmed'=>true,
    'source'=>'woocommerce_selected_shipping_rate',
    'discounted_merchandise_minor'=>3000,'threshold_minor'=>4900];
$cases=0;
$check=static function(bool $ok,string $reason)use(&$cases):void{
    $cases++;if(!$ok)throw new RuntimeException('M5_FAIL '.$reason);
};
$x=Andy_Commerce_M5_Progress_Projection::project($good);
$check($x['show_progress'] && $x['remaining_minor']===1900 && $x['percent']===61,'read-only amount');
$check(!$x['gift_enabled'] && !$x['claim_free_shipping'],'never grants rewards');
$a=Andy_Commerce_M5_Progress_Projection::project($good+['bad'=>'irrelevant']);
$check($a['authority']==='woocommerce','Woo authority');
$legacy=Andy_Commerce_M5_Progress_Projection::project(array_replace($good,['effective_policy_basis'=>'pre_discount_merchandise_subtotal']));
$check(!$legacy['show_progress'] && $legacy['reason']==='legacy_policy_migration_pending','legacy BYL49 progress hidden');
$unknown=Andy_Commerce_M5_Progress_Projection::project(array_replace($good,['woocommerce_rate_confirmed'=>false]));
$check(!$unknown['show_progress'],'unknown shipping rate hidden');
$fake=Andy_Commerce_M5_Progress_Projection::project(array_replace($good,['source'=>'andy_commerce']));
$check(!$fake['show_progress'],'no noncanonical authority');
$threshold=Andy_Commerce_M5_Progress_Projection::project(array_replace($good,['discounted_merchandise_minor'=>6000]));
$check($threshold['percent']===100 && $threshold['remaining_minor']===0 && !$threshold['claim_free_shipping'],'threshold is not award');
$bad=Andy_Commerce_M5_Progress_Projection::project(array_replace($good,['threshold_minor'=>0]));
$check(!$bad['show_progress'],'no zero threshold');
$entry=file_get_contents(dirname(__DIR__).'/andy-commerce.php');
$check(!str_contains($entry,'class-andy-commerce-m5-progress-projection'),'no runtime hooks');
echo "M5_OFFLINE_PROGRESS_PASS={$cases}\n";
