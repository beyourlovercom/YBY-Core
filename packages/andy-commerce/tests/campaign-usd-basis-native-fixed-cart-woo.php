<?php
/**
 * Native WooCommerce fixed-cart coupon entitlement at USD199 boundary.
 *
 * This intentionally writes ONLY to one-use disposable WordPress test DB.
 * It MUST NEVER be run on a real Dev, staging, production or customer store.
 *
 * CI: R6_DISPOSABLE_WOO=1 wp eval-file <this-file> --path=$RUNNER_TEMP/woo
 */
// WP-CLI eval-file wraps the source in eval(); strict_types cannot appear here.
if (!defined('ABSPATH') || !defined('DB_NAME') || getenv('R6_DISPOSABLE_WOO') !== '1'
    || !in_array(DB_NAME, ['commerce_p5_isolated', 'aicc_campaign_c3_20261027'], true)
    || !in_array(wp_parse_url(home_url('/'), PHP_URL_HOST), ['commerce-p5.invalid', '127.0.0.1'], true)
    || get_option('woocommerce_currency') !== 'USD') {
    throw new RuntimeException('R6_DISPOSABLE_WOO_STRICT_ISOLATION_DENIED');
}
if (!class_exists('Andy_Commerce_Campaign_USD_Basis_V1')) {
    $file = dirname(__DIR__) . '/includes/class-andy-commerce-campaign-usd-basis-v1.php';
    require_once $file;
}
if (!class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
    || !class_exists('Andy_Commerce_Currency_Reference_FX_V1')) {
    throw new RuntimeException('R6_SOURCE_COMMERCE_CLASSES_MISSING');
}
if (get_option('woocommerce_prices_include_tax', 'no') !== 'no') {
    throw new RuntimeException('R6_TEST_PRICES_INCLUDE_TAX_FORBIDDEN');
}

// Override only the current PHP process: do not alter the local DB site's URL,
// base-currency options, FX options, or checkout provider configuration.
add_filter('home_url', static function ($url) {
    return preg_replace('~^https?://[^/]+~', 'https://dev.beyourlover.com', $url);
}, 999);
add_filter('pre_option_andy_commerce_currency_runtime_dev_v1', static fn() =>
    ['enabled'=>true, 'allowed'=>['USD','EUR','GBP','AUD','CAD']]);
add_filter('pre_option_andy_commerce_currency_runtime_fx_snapshot_v1', static fn() =>
    [
        'base'=>'USD',
        'rate_source'=>Andy_Commerce_Currency_Reference_FX_V1::SOURCE,
        'retrieved_at_utc'=>time(),
        'rate_micros'=>[
            'USD'=>1000000, 'EUR'=>894610, 'GBP'=>749160,
            'AUD'=>1423900, 'CAD'=>1400900,
        ],
    ]);
Andy_Commerce_Currency_Runtime_Dev_V1::boot();
wc_load_cart();
if (!WC()->session || !WC()->cart) {
    throw new RuntimeException('R6_NATIVE_WOO_SESSION_NOT_AVAILABLE');
}

$fixtures = [];
$checks = 0;
try {
    $p = new WC_Product_Simple();
    $p->set_name('R6 DISPOSABLE FIXED CART TEST ONLY');
    $p->set_status('publish');
    $p->set_catalog_visibility('hidden');
    $p->set_regular_price('219.00');
    $p->set_manage_stock(false);
    $pid = $p->save();
    if (!$pid) throw new RuntimeException('R6_PRODUCT_FIXTURE_SAVE_FAILED');
    $fixtures[] = ['product', $pid];
    $coupon = new WC_Coupon();
    $coupon->set_code('r6-disposable-fixedcart-20');
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount('20.00');
    $cid = $coupon->save();
    if (!$cid) throw new RuntimeException('R6_COUPON_FIXTURE_SAVE_FAILED');
    $fixtures[] = ['coupon', $cid];

    foreach (['218.99'=>19899, '219.00'=>19900, '219.01'=>19901] as $price=>$expected) {
        $p->set_regular_price($price);
        $p->set_price($price);
        $p->save();
        foreach (['USD','EUR','GBP','AUD','CAD'] as $currency) {
            WC()->session->set('ac_currency_runtime_dev_selected',$currency);
            if (Andy_Commerce_Currency_Runtime_Dev_V1::selected() !== $currency)
                throw new RuntimeException('R6_SELECTED_CURRENCY_NOT_MATCHED_' . $currency);
            WC()->cart->empty_cart();
            if (!WC()->cart->add_to_cart($pid, 1))
                throw new RuntimeException('R6_CANNOT_ADD_TEST_PRODUCT');
            if (!WC()->cart->apply_coupon($coupon->get_code()))
                throw new RuntimeException('R6_NATIVE_COUPON_REJECTED');
            WC()->cart->calculate_totals();
            $quote = Andy_Commerce_Campaign_USD_Basis_V1::quote(WC()->cart);
            $line = current(WC()->cart->get_cart());
            if (!($quote['ok']??false) || $quote['basis_minor'] !== $expected)
                throw new RuntimeException('R6_NATIVE_FX_MISMATCH ' . $currency . ': ' . wp_json_encode($quote));
            $slots=$quote['basis_minor']>=19900?2:1;
            if ($slots!==($expected>=19900?2:1))
                throw new RuntimeException('R6_NATIVE_GIFT_THRESHOLD_BAD');
            echo 'R6_NATIVE_COUPON '
                 . $currency.' USD_NET='.$expected
                 . ' LOCAL_LINE='.($line['line_total']??'?')
                 . ' SLOTS='.$slots . PHP_EOL;
            ++$checks;
        }
    }
    if ($checks!==15)throw new RuntimeException('R6_INCOMPLETE_MATRIX');
    echo "R6_NATIVE_WOO_FIXED_COUPON_BOUNDARY_PASS=15\n";
} finally {
    if (WC()->cart) WC()->cart->empty_cart();
    if (WC()->session) WC()->session->set('ac_currency_runtime_dev_selected','USD');
    foreach ($fixtures as [$type,$id]) {
        if ($type==='coupon') wp_delete_post($id,true);
        else {
            $product=wc_get_product($id);
            if ($product) $product->delete(true);
        }
    }
    echo 'R6_DISPOSABLE_FIXTURES_REMOVED='.count($fixtures).PHP_EOL;
}
