<?php
/**
 * DEV, read-only: real Yay Pro + candidate BF2026 plugin contract.
 * Run with --skip-plugins=black-friday-2026 and approved preboot test ID.
 * No order, coupon, payment, session, inventory or product DB write.
 */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI
  || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
  || get_option('woocommerce_currency')!=='USD'
  || class_exists('BYL_BF2026_Gifts')
  || !class_exists('WooCommerce') || !class_exists('WC_Cart')) {
    throw new RuntimeException('BF_YAY_DEV_UAT_ISOLATION_DENIED');
}
$base=dirname(__DIR__);
require_once $base.'/includes/class-andy-commerce-campaign-source-usd-discounts-v1.php';
require_once $base.'/includes/class-andy-commerce-campaign-yay-usd-basis-v1.php';
$candidate='/home/u247343654/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php';
if(!is_file($candidate)
  || hash_file('sha256',$candidate)!=='9111c0cb31605ff1111a40586b399218d84ec5c70f2d3c15ec55a68ea017f58a'){
    throw new RuntimeException('BF_YAY_DEV_UAT_CANDIDATE_SHA_MISMATCH');
}
require_once $candidate;
$map=[40058=>'USD',40060=>'EUR',40062=>'GBP',40063=>'AUD',40064=>'CAD'];
$code=$map[(int)getenv('AICC_YAY_TEST_CURRENCY_ID')]??null;
if(!$code || Andy_Commerce_Campaign_Yay_USD_Basis_V1::storefront_currency()!==$code){
    throw new RuntimeException('BF_YAY_DEV_UAT_CURRENCY_NOT_MATCHED');
}
$passes=0;
$assert=static function($condition,$message) {
    if(!$condition)throw new RuntimeException('BF_YAY_DEV_UAT_FAILED_'.$message);
};
foreach([['198.99',19899,1],['199.00',19900,2],['199.01',19901,2]] as [$usd,$basis,$slots]){
    $product=wc_get_product(40026);
    if(!$product instanceof WC_Product)throw new RuntimeException('BF_YAY_DEV_UAT_FIXTURE_NOT_FOUND');
    $product->set_price($usd);
    $product->set_regular_price($usd);
    $local=(string)$product->get_price('view');

    $cart=new WC_Cart();
    $cart->set_cart_contents(['merch'=>
        ['data'=>$product,'quantity'=>1,'product_id'=>40026,'line_subtotal'=>$local,'line_total'=>$local]]);

    $got=BYL_BF2026_Gifts::eligibility($cart);
    $assert(($got['basis_minor']??null)===$basis && ($got['slots']??null)===$slots,
        'threshold_'.$code.'_'.$usd.'_'.wp_json_encode($got));
    $passes++;

    // A selected gift with any arbitrary SKU price may not increase source
    // merchandise or independently unlock an extra gift.
    $gift=wc_get_product(40027);
    $gift->set_price('49.00');
    $giftLocal=(string)$gift->get_price('view');
    $cart->set_cart_contents([
        'merch'=>['data'=>$product,'quantity'=>1,'product_id'=>40026,
            'line_subtotal'=>$local,'line_total'=>$local],
        'gift'=>['data'=>$gift,'quantity'=>1,'product_id'=>40027,
            'andy_campaign_pack_id'=>'byl-black-friday-2026',
            '_byl_bf2026_gift'=>true,'line_subtotal'=>$giftLocal,'line_total'=>$giftLocal],
    ]);
    $got2=BYL_BF2026_Gifts::eligibility($cart);
    $assert(($got2['basis_minor']??null)===$basis && ($got2['slots']??null)===$slots,
        'gift_excluded_'.$code.'_'.$usd);
    $passes++;
    echo 'BF_YAY_DEV_PASS currency='.$code.' USD='.$usd.' local='.$local.' gifts='.$slots.PHP_EOL;
}
echo 'BF_YAY_DEV_READONLY_PASS='.$passes.' currency='.$code.' CHECKOUT_CODE='.get_woocommerce_currency().' NO_ORDER_PAYMENT_WRITE=YES'.PHP_EOL;
