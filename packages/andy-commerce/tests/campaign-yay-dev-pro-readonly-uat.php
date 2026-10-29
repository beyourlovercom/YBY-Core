<?php
/**
 * Actual Dev Woo + YayCurrency Pro quote proof; synthetic cart IN MEMORY.
 * Must only run as WP-CLI against dev.beyourlover.com.
 * Does not persist cart, Woo products/coupons/orders or charge a gateway.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI
    || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
    || get_option('woocommerce_currency')!=='USD'
    || !class_exists('WC_Product') || !class_exists('WC_Cart')
    || !class_exists('Yay_Currency\\Helpers\\YayCurrencyHelper')) {
    throw new RuntimeException('YAY_DEV_UAT_STRICT_DEV_ONLY');
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-campaign-source-usd-discounts-v1.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-campaign-yay-usd-basis-v1.php';
$testId=(int)getenv('AICC_YAY_TEST_CURRENCY_ID');
$map=[40058=>'USD',40060=>'EUR',40062=>'GBP',40063=>'AUD',40064=>'CAD'];
$wanted=$map[$testId]??null;
if(!$wanted)throw new RuntimeException('YAY_DEV_UAT_NOT_ALLOWLISTED');
$selected=\Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
if(($selected['currency']??null)!==$wanted) {
    throw new RuntimeException('YAY_DEV_UAT_SELECTED_CURRENCY_MISMATCH');
}
$cases=[['198.99',19899,1],['199.00',19900,2],['199.01',19901,2]];
$passes=0;
foreach($cases as [$usd,$minor,$slots]){
    $p=wc_get_product(40026);
    if(!($p instanceof WC_Product))throw new RuntimeException('YAY_DEV_UAT_PRODUCT_MISSING');
    // The unsaved product object exists only in this WP-CLI process.
    $p->set_price($usd);
    $p->set_regular_price($usd);
    $local=(string)$p->get_price('view');
    $cart=new WC_Cart();
    $cart->set_cart_contents(['aicc-uat-only'=>[
        'data'=>$p,'quantity'=>1,
        'line_subtotal'=>$local,'line_total'=>$local,
    ]]);
    $quote=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote($cart);
    if(!($quote['ok']??false)
        ||($quote['currency']??null)!==$wanted
        ||($quote['basis_minor']??null)!==$minor
        ||($quote['gift_slots']??null)!==$slots) {
        throw new RuntimeException('YAY_DEV_UAT_QUOTE_FAILED_'.wp_json_encode([
            'selected'=>$wanted,'source'=>$usd,'shopper_local'=>$local,'quote'=>$quote
        ]));
    }
    echo 'YAY_DEV_USD_SOURCE_PASS code='.$wanted.' base='.$usd.' local='.$local.' slots='.$slots.' checkout_currency='.$quote['woo_effective_currency'].PHP_EOL;
    ++$passes;
}
echo 'YAY_DEV_PRO_READONLY_PASS='.$passes.' currency='.$wanted.' NO_ORDERS=YES NO_DB_WRITES=YES'.PHP_EOL;
