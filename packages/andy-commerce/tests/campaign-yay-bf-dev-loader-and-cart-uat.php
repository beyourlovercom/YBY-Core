<?php
/**
 * DEV-ONLY: load the EXACT staged BF Yay candidate alongside the normal
 * (already-loaded) BF plugin, under a distinct test-only class name.
 *
 * No --skip-plugins needed. No real BF boot/activation or Woo writes.
 * All sample WC_Product changes stay in memory.
 *
 * WP-CLI:
 * AICC_YAY_TEST_CURRENCY_ID=40060 wp --path=<DEV> \
 *   --require=campaign-yay-dev-pro-preboot.php \
 *   eval-file campaign-yay-bf-dev-loader-and-cart-uat.php
 */
$aiccPhase='start';
try {
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI
 || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
 || get_option('woocommerce_currency')!=='USD'
 || get_option('andy_campaign_lite_master','no')!=='no'
 || !class_exists('BYL_BF2026_Gifts',false)
 || !class_exists('WC_Cart')
 || !class_exists('Yay_Currency\Helpers\YayCurrencyHelper')) {
    throw new RuntimeException('BF_DEV_UAT_ENVIRONMENT_NOT_ISOLATED');
}
$source='/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/black-friday-2026/includes/class-byl-bf2026-gifts.php';
$candidate='/home/u247343654/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php';
$path=dirname(__DIR__);
if(!is_file($source) ||
   hash_file('sha256',$source)!=='b08dc6993b533387eb2811c9d56ef5b216b65d0c04bd901b92ccd8a1587e147d'
 || !is_file($candidate) ||
   hash_file('sha256',$candidate)!=='9111c0cb31605ff1111a40586b399218d84ec5c70f2d3c15ec55a68ea017f58a'){
    throw new RuntimeException('BF_DEV_UAT_EXACT_HASH_GATE_DENIED');
}
/**
 * A prior Andy Commerce build may already define the same public class.
 * This test loads the new source under a private class name and never
 * replaces the running site's class, plugin hooks or currency engine.
 */
$expected=[
 'discounts'=>[
     'file'=>$path.'/includes/class-andy-commerce-campaign-source-usd-discounts-v1.php',
     'sha'=>'3122ce21279c40dddc1fd59fb27fba1ffc4e8ce7e0c52186e54542f7abe71059',
 ],
 'adapter'=>[
     'file'=>$path.'/includes/class-andy-commerce-campaign-yay-usd-basis-v1.php',
     'sha'=>'4957fca5933482d2f0555b60a77cda459290c42fb0dfd86d27c8501865d54be3',
 ],
];
foreach($expected as $kind=>$row){
    if(!is_file($row['file']) || hash_file('sha256',$row['file'])!==$row['sha'])
        throw new RuntimeException('BF_DEV_UAT_SOURCE_SHA_NOT_MATCHED_'.$kind);
}
$isolatedLoad=static function(string $body) {
    $tmp=tempnam(sys_get_temp_dir(),'aicc-uat-');
    if(!$tmp)throw new RuntimeException('BF_DEV_UAT_NO_TEMP');
    try{
        if(file_put_contents($tmp,$body)===false)
            throw new RuntimeException('BF_DEV_UAT_TEMP_WRITE_ERROR');
        require $tmp;
    } finally { @unlink($tmp); }
};
$discounts=file_get_contents($expected['discounts']['file']);
$discountNeedle='final class Andy_Commerce_Campaign_Source_USD_Discounts_V1 {';
if(substr_count($discounts,$discountNeedle)!==1)
    throw new RuntimeException('BF_DEV_UAT_DISCOUNTS_RENAME_DENIED');
$discounts=str_replace($discountNeedle,
    'final class Andy_Commerce_Campaign_Source_USD_Discounts_Candidate_UAT {',
    $discounts);
$aiccPhase='load_isolated_discounts';
$isolatedLoad($discounts);

$adapter=file_get_contents($expected['adapter']['file']);
$requireNeedle="require_once __DIR__ . '/class-andy-commerce-campaign-source-usd-discounts-v1.php';";
if(substr_count($adapter,$requireNeedle)!==1)
    throw new RuntimeException('BF_DEV_UAT_ADAPTER_REQUIRE_ANCHOR_MISSING');
$adapter=str_replace($requireNeedle,'/* independent private USD discount class loaded above */',$adapter);
$adapter=str_replace('Andy_Commerce_Campaign_Source_USD_Discounts_V1',
    'Andy_Commerce_Campaign_Source_USD_Discounts_Candidate_UAT',$adapter);
$adapterNeedle='final class Andy_Commerce_Campaign_Yay_USD_Basis_V1 {';
if(substr_count($adapter,$adapterNeedle)!==1)
    throw new RuntimeException('BF_DEV_UAT_ADAPTER_RENAME_DENIED');
$adapter=str_replace('Andy_Commerce_Campaign_Yay_USD_Basis_V1',
    'Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT',$adapter);
$aiccPhase='load_isolated_adapter';
$isolatedLoad($adapter);
if(!class_exists('Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT',false))
    throw new RuntimeException('BF_DEV_UAT_PRIVATE_ADAPTER_NOT_LOADED');

$ids=[40058=>'USD',40060=>'EUR',40062=>'GBP',40063=>'AUD',40064=>'CAD'];
$wanted=$ids[(int)getenv('AICC_YAY_TEST_CURRENCY_ID')]??null;
if(!$wanted || Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT::storefront_currency()!==$wanted)
    throw new RuntimeException('BF_DEV_UAT_YAY_CONTEXT_MISMATCH');

$needle='final class BYL_BF2026_Gifts {';
$body=file_get_contents($candidate);
if(substr_count($body,$needle)!==1)
    throw new RuntimeException('BF_DEV_UAT_CANDIDATE_CLASS_NOT_FOUND');
$body=str_replace($needle,'final class BYL_BF2026_Gifts_Candidate_UAT {',$body);
$body=str_replace('Andy_Commerce_Campaign_Yay_USD_Basis_V1',
    'Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT',$body);
$aiccPhase='load_isolated_bf_candidate';
$isolatedLoad($body);
if(!class_exists('BYL_BF2026_Gifts_Candidate_UAT',false))
    throw new RuntimeException('BF_DEV_UAT_CANDIDATE_NOT_LOADED');
echo 'BF_DEV_LOADER_PASS=1 currency='.$wanted.' original_plugin_active=YES candidate_hooks_not_booted=YES'.PHP_EOL;

$aiccPhase='cart_checks';
$passes=0;
$assert=static function($value,$why) {
    if(!$value)throw new RuntimeException('BF_DEV_CART_UAT_FAIL_'.$why);
};
foreach([
    ['0.00',0,0],
    ['198.99',19899,1],
    ['199.00',19900,2],
    ['199.01',19901,2],
] as [$usd,$minor,$slots]){
    $p=wc_get_product(40026);
    if(!($p instanceof WC_Product))throw new RuntimeException('BF_DEV_UAT_PRODUCT_MISSING');
    // WC_Product fetched from Dev but NOT saved.
    $p->set_price($usd);
    $p->set_regular_price($usd);
    $local=(string)$p->get_price('view');
    $cart=new WC_Cart();
    $cart->set_cart_contents(['merchandise'=>[
        'data'=>$p,'product_id'=>40026,'variation_id'=>0,'variation'=>[],
        'quantity'=>1,'line_subtotal'=>$local,'line_total'=>$local,
        'line_subtotal_tax'=>0,'line_tax'=>0,'line_tax_data'=>[],
    ]]);
    $got=BYL_BF2026_Gifts_Candidate_UAT::eligibility($cart);
    $assert(($got['basis_minor']??null)===$minor && ($got['slots']??null)===$slots,
        $wanted.'_USD_'.$usd.'_'.wp_json_encode($got));
    $passes++;
    $gift=wc_get_product(40027);
    if(!($gift instanceof WC_Product))throw new RuntimeException('BF_DEV_UAT_GIFT_PRODUCT_MISSING');
    $gift->set_price('39.00');
    $giftLocal=(string)$gift->get_price('view');
    $cart->set_cart_contents([
      'merchandise'=>['data'=>$p,'product_id'=>40026,'quantity'=>1,
          'line_subtotal'=>$local,'line_total'=>$local],
      'gift'=>['data'=>$gift,'product_id'=>40027,'quantity'=>1,
          'andy_campaign_pack_id'=>'byl-black-friday-2026',
          '_byl_bf2026_gift'=>true,'line_subtotal'=>$giftLocal,'line_total'=>$giftLocal],
    ]);
    $got=BYL_BF2026_Gifts_Candidate_UAT::eligibility($cart);
    $assert(($got['basis_minor']??null)===$minor && ($got['slots']??null)===$slots,
        $wanted.'_GIFT_EXCLUSION_'.$usd);
    $passes++;
    echo 'BF_DEV_CART_READONLY_PASS currency='.$wanted.' USD='.$usd.
         ' local='.$local.' gift_slots='.$slots.PHP_EOL;
}
echo 'BF_DEV_CANDIDATE_CART_PASS='.$passes.' currency='.$wanted.
     ' no_orders_no_payments=YES in_memory_only=YES'.PHP_EOL;

} catch (Throwable $aiccCaught) {
    // Only programmatic Dev-only status. No customer identifiers/keys/trace.
    echo 'BF_DEV_UAT_DIAGNOSTIC_PHASE='.$aiccPhase.PHP_EOL;
    echo 'BF_DEV_UAT_DIAGNOSTIC_CLASS='.get_class($aiccCaught).PHP_EOL;
    echo 'BF_DEV_UAT_DIAGNOSTIC_REASON='.substr(preg_replace('/[^a-zA-Z0-9_.,:{}\- ]/','?',(string)$aiccCaught->getMessage()),0,280).PHP_EOL;
    exit(23);
}
