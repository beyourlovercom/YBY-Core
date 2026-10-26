<?php
/** C-R1 real Woo cart pricing proof: disposable DB only, fixed synthetic FX. */
if (!defined('ABSPATH') || !defined('AICC_CURRENCY_RUNTIME_SANDBOX') || !AICC_CURRENCY_RUNTIME_SANDBOX
    || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !defined('DB_NAME') || DB_NAME !== 'commerce_p5_isolated'
    || !class_exists('Andy_Commerce_Currency_Runtime_C1')
    || !Andy_Commerce_Currency_Runtime_C1::allowed()) {
    throw new RuntimeException('C-R1 isolated sandbox guard failed');
}
$assert=static function($pass,$label) { if(!$pass) throw new RuntimeException('C-R1 '.$label); };
$assert(get_woocommerce_currency()==='EUR','EUR cart currency missing');
$p=new WC_Product_Simple();
$p->set_name('C-R1 USD base only');
$p->set_status('publish');
$p->set_regular_price('20.00');
$p->set_sale_price('12.50');
$id=$p->save();
$check=wc_get_product($id);
$assert($check && (string)$check->get_price('edit')==='12.50','raw USD unchanged');
$assert(abs((float)$check->get_price()-11.25)<0.001,'single product 12.50 USD -> 11.25 EUR');
$assert(abs((float)$check->get_regular_price()-18.00)<0.001,'regular 20.00 USD -> 18.00 EUR');
$assert(abs((float)$check->get_sale_price()-11.25)<0.001,'sale 12.50 USD -> 11.25 EUR');
$assert(abs((float)$check->get_price()-11.25)<0.001,'repeat read double conversion');
if (function_exists('wc_load_cart')) wc_load_cart();
if (!WC()->cart) throw new RuntimeException('Woo cart not available');
WC()->cart->empty_cart();
$cart_key=WC()->cart->add_to_cart($id,2);
$assert(is_string($cart_key) && $cart_key!=='','native cart add');
WC()->cart->calculate_totals();
$assert(abs((float)WC()->cart->get_cart_contents_total()-22.50)<0.001,'native cart subtotal EUR');
$assert(abs((float)WC()->cart->get_total('edit')-22.50)<0.001,'native cart payable EUR');
$assert((string)wc_get_product($id)->get_price('edit')==='12.50','database USD unchanged after totals');
echo 'C_R1_NATIVE_WOO_EUR_PRODUCT_AND_CART_PASS USD12.50*2 EUR22.50'.PHP_EOL;
echo 'C_R1_USD_SOURCE_STABLE_NO_DOUBLE_CONVERSION_PASS'.PHP_EOL;
echo 'C_R1_GATEWAY_NOT_CONNECTED=TRUE'.PHP_EOL;
