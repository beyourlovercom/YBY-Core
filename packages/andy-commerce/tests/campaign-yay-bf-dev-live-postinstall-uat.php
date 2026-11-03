<?php
/**
 * DEV-only read-only post-install UAT of actual Black Friday 0.1.1-dev class.
 * WP-CLI --require campaign-yay-dev-pro-preboot.php sets test-only currency.
 * No Woo session/order, payment, product save, coupon or customer DB mutation.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')
    || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
    || get_option('woocommerce_currency')!=='USD'
    || get_option('andy_campaign_lite_master','no')!=='no'
    || !class_exists('BYL_BF2026_Gifts')
    || !class_exists('Andy_Commerce_Campaign_Yay_USD_Basis_V1')
    || !method_exists('Andy_Commerce_Campaign_Yay_USD_Basis_V1','storefront_currency')
    || !class_exists('WC_Cart')) {
    throw new RuntimeException('DEV_BF_LIVE_UAT_GATE_DENIED');
}
$bf=ABSPATH.'wp-content/plugins/black-friday-2026/includes/class-byl-bf2026-gifts.php';
if(hash_file('sha256',$bf)!=='9111c0cb31605ff1111a40586b399218d84ec5c70f2d3c15ec55a68ea017f58a') {
    throw new RuntimeException('DEV_BF_LIVE_SHA_MISMATCH');
}
$map=[40058=>'USD',40060=>'EUR',40062=>'GBP',40063=>'AUD',40064=>'CAD'];
$currency=$map[(int)getenv('AICC_YAY_TEST_CURRENCY_ID')]??null;
if(!$currency || Andy_Commerce_Campaign_Yay_USD_Basis_V1::storefront_currency()!==$currency) {
    throw new RuntimeException('DEV_BF_LIVE_CURRENCY_NOT_MATCHED');
}
$count=0;
foreach([['0.00',0,0],['198.99',19899,1],['199.00',19900,2],['199.01',19901,2]] as [$usd,$basis,$slots]){
    $p=wc_get_product(40026);
    if(!$p instanceof WC_Product)throw new RuntimeException('DEV_BF_TEST_PRODUCT_MISSING');
    $p->set_regular_price($usd);
    $p->set_price($usd);
    $local=(string)$p->get_price('view');
    $cart=new WC_Cart();
    $line=['data'=>$p,'product_id'=>40026,'quantity'=>1,'line_subtotal'=>$local,'line_total'=>$local];
    $cart->set_cart_contents(['merch'=>$line]);
    $got=BYL_BF2026_Gifts::eligibility($cart);
    if(($got['basis_minor']??null)!==$basis || ($got['slots']??null)!==$slots){
        throw new RuntimeException('DEV_BF_LIVE_USD_ELIGIBILITY_FAIL_'.$currency.'_'.$usd);
    }
    ++$count;
    $gift=wc_get_product(40027);
    if(!$gift instanceof WC_Product)throw new RuntimeException('DEV_BF_TEST_GIFT_MISSING');
    $gift->set_price('39.00');
    $giftLocal=(string)$gift->get_price('view');
    $giftLine=['data'=>$gift,'product_id'=>40027,'quantity'=>1,
        'andy_campaign_pack_id'=>'byl-black-friday-2026',
        '_byl_bf2026_gift'=>true,'line_subtotal'=>$giftLocal,'line_total'=>$giftLocal];
    $cart->set_cart_contents(['merch'=>$line,'gift'=>$giftLine]);
    $got=BYL_BF2026_Gifts::eligibility($cart);
    if(($got['basis_minor']??null)!==$basis || ($got['slots']??null)!==$slots){
        throw new RuntimeException('DEV_BF_LIVE_GIFT_EXCLUSION_FAIL_'.$currency.'_'.$usd);
    }
    ++$count;
}
if(BYL_BF2026_Gifts::ready()!==false)throw new RuntimeException('DEV_BF_READY_UNEXPECTEDLY_ON');
echo 'DEV_BF_ACTUAL_INSTALLED_CLASS_PASS='.$count.' currency='.$currency.' READY_FALSE=YES NO_CART_ORDER_PAYMENT_DB_WRITE=YES'.PHP_EOL;
