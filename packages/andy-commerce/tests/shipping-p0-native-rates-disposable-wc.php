<?php
/**
 * Woo 11.2.1 NATIVE shipping-rate contract in a disposable WP + MySQL only.
 * Simulates the exact YayCurrency Pro selected-currency and forward FX hooks;
 * does not bundle / claim to execute licensed YayCurrency Pro itself.
 */
if (!defined('ABSPATH') || !defined('DB_NAME')
    || DB_NAME !== 'commerce_p5_isolated'
    || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL
    || !class_exists('WC_Shipping_Free_Shipping')) {
    throw new RuntimeException('P0_RATE_ISOLATION_GATE');
}
if (!class_exists('Yay_Currency\\Helpers\\YayCurrencyHelper')) {
    eval('namespace Yay_Currency\\Helpers; final class YayCurrencyHelper {
        public static string $currency = "USD";
        public static function detect_current_currency(): array {
            return ["currency"=>self::$currency,"status"=>1];
        }
    }');
}
$assert = static function(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('P0_NATIVE_RATE_FAIL: '.$message);
};
$assert(Andy_Commerce_Shipping_Promotion_Runtime::class !== '', 'runtime present');
add_filter('andy_commerce_site_preset', static function($preset) {
    return ['id'=>'byl','global_threshold'=>49.0,
        'free_shipping_code'=>'BYL49',
        'legacy_option_name'=>'byl_shipping_promotion_settings'];
}, 999);
update_option('byl_shipping_promotion_settings', [
    'schema_version'=>1,
    'mode'=>'automatic',
    'free_shipping_code'=>'BYL49',
    'global_threshold'=>49.0,
    'eligibility_basis'=>'pre_discount_merchandise_subtotal',
    'zone_overrides'=>[],
], false);
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('P0 US synthetic country zone');
$zone->set_zone_order(1);
$zoneId = $zone->save();
$zone->add_location('US', 'country');
$flatId = $zone->add_shipping_method('flat_rate');
$freeId = $zone->add_shipping_method('free_shipping');
update_option('woocommerce_flat_rate_'.$flatId.'_settings', [
    'title'=>'Flat Rate','cost'=>'10','tax_status'=>'none'
], false);
update_option('woocommerce_free_shipping_'.$freeId.'_settings', [
    'title'=>'Free Shipping','requires'=>'min_amount','min_amount'=>'69','ignore_discounts'=>'yes'
], false);
WC_Cache_Helper::get_transient_version('shipping', true);
$p60 = new WC_Product_Simple();
$p60->set_name('P0 60 source USD'); $p60->set_status('publish');
$p60->set_regular_price('60.00'); $p60->set_price('60.00');
$p60->set_tax_status('none'); $id60=$p60->save();
$p15 = new WC_Product_Simple();
$p15->set_name('P0 15 source USD'); $p15->set_status('publish');
$p15->set_regular_price('15.00'); $p15->set_price('15.00');
$p15->set_tax_status('none'); $id15=$p15->save();
$assert($id60>0 && $id15>0, 'product fixture created');

$GLOBALS['p0_rate'] = 1.0;
add_filter('yay_currency_convert_price', static function($value) {
    return number_format((float)$value * $GLOBALS['p0_rate'],2,'.','');
}, 10, 1);
add_filter('woocommerce_product_get_price', static function($value, $product) {
    return number_format((float)$value * $GLOBALS['p0_rate'],2,'.','');
}, 20, 2);

if (function_exists('wc_load_cart')) wc_load_cart();
$assert(WC()->cart instanceof WC_Cart,'real Woo cart present');
WC()->customer->set_shipping_country('US');
WC()->customer->set_shipping_state('CA');
WC()->customer->set_shipping_postcode('90001');
WC()->customer->set_shipping_city('Los Angeles');
WC()->customer->set_billing_country('US');
$cfg=['USD'=>1.0, 'EUR'=>.897, 'GBP'=>.7505, 'AUD'=>1.4187, 'CAD'=>1.4];
$n=0;
foreach ($cfg as $currency=>$fx) {
    \Yay_Currency\Helpers\YayCurrencyHelper::$currency=$currency;
    $GLOBALS['p0_rate']=$fx;
    foreach ([60,75] as $subtotal) {
        WC()->cart->empty_cart();
        WC()->shipping()->reset_shipping();
        $key=WC()->cart->add_to_cart($id60,1);
        $assert(is_string($key),'cart $60 inserted '.$currency);
        if ($subtotal===75) {
            $key2=WC()->cart->add_to_cart($id15,1);
            $assert(is_string($key2),'cart $15 inserted '.$currency);
        }
        WC()->cart->calculate_totals();
        $packages=WC()->shipping()->get_packages();
        $rates=[];
        foreach ($packages as $package) {
            foreach ((array)($package['rates']??[]) as $rate) {
                $rates[]=$rate->get_method_id();
            }
        }
        $eligible=$subtotal>=69;
        $free=in_array('free_shipping',$rates,true);
        $basis=Andy_Commerce_Shipping_USD_Source_V1::subtotal(WC()->cart);
        $assert($basis !== null && abs($basis-$subtotal)<.011,
            "$currency $subtotal canonical USD basis");
        $assert($free===$eligible,
            "$currency $subtotal native Woo rates ".implode(',',$rates));
        $state=Andy_Commerce_Shipping_Promotion_Runtime::get_cart_presentation_state();
        $assert(($state['eligible']??null)===$eligible,
            "$currency $subtotal Store API extension eligibility");
        $n++;
        echo "P0_REAL_WC_RATE_PASS $currency USD=$subtotal methods=".
            implode(',',$rates)." display=".$state['display_currency'].PHP_EOL;
    }
}
$fields=apply_filters('woocommerce_shipping_package_hash_ignored_fields',
    ['subtotal','total','rates'], ['contents'=>[]]);
$assert(!in_array('subtotal',$fields,true)&&!in_array('total',$fields,true)
    &&in_array('rates',$fields,true),'cache includes pricing');
echo "P0_WOO_NATIVE_RATES_AND_CACHE_PASS cases=$n; isolated DB; no gateway\\n";
