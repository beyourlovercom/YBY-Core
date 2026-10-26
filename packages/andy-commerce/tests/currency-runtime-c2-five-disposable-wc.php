<?php
/** Five currency native Woo + Store API smoke in ephemeral isolated MySQL. */
if (!defined('ABSPATH') || !defined('AICC_CURRENCY_RUNTIME_SANDBOX')
    || AICC_CURRENCY_RUNTIME_SANDBOX !== true || !defined('DB_NAME')
    || DB_NAME !== 'commerce_p5_isolated'
    || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !class_exists('Andy_Commerce_Currency_Runtime_C1')
    || !Andy_Commerce_Currency_Runtime_C1::allowed()) {
    throw new RuntimeException('C-R2 test environment guard');
}
$code=Andy_Commerce_Currency_Runtime_C1::test_currency();
$expected=['USD'=>1250,'EUR'=>1125,'GBP'=>975,'AUD'=>1875,'CAD'=>1688][$code]??null;
if (!$expected) throw new RuntimeException('Unsupported currency fixture');
$assert=static function($okay,$msg) use($code) {
    if(!$okay) throw new RuntimeException('C-R2 '.$code.': '.$msg);
};
$assert(get_woocommerce_currency()===$code,'currency selection');
$p=new WC_Product_Simple();
$p->set_name('Currency five-way synthetic');
$p->set_status('publish');
$p->set_regular_price('12.50');
$p->set_price('12.50');
$id=$p->save();
$product=wc_get_product($id);
$assert(abs((float)$product->get_price()-$expected/100)<0.001,'product view converted');
$assert(abs((float)$product->get_price('edit')-12.50)<0.001,'USD source retained');
$assert(abs((float)$product->get_price()-$expected/100)<0.001,'no double conversion');
if(function_exists('wc_load_cart')) wc_load_cart();
WC()->cart->empty_cart();
$key=WC()->cart->add_to_cart($id,2);
$assert(is_string($key)&&$key!=='','cart add product');
WC()->cart->calculate_totals();
$expectedCart=$expected*2/100; // native Woo computes unit price then multiplies quantity.
$assert(abs((float)WC()->cart->get_cart_contents_total()-$expectedCart)<0.001,'native cart total');
$r=rest_do_request(new WP_REST_Request('GET','/wc/store/v1/products/'.$id));
$assert(!is_wp_error($r)&&$r instanceof WP_REST_Response && $r->get_status()===200,'native Store API GET');
$json=json_decode(wp_json_encode($r->get_data()),true);
$prices=$json['prices']??[];
$assert(($prices['currency_code']??null)===$code,'Store API currency');
$assert((string)($prices['price']??'')===(string)$expected,'Store API minor price');
echo 'C_R2_FIVE_CURRENCY_WOO_STORE_API_PASS code='.$code.' product_minor='.$expected.' cart_minor='.($expected*2).PHP_EOL;
