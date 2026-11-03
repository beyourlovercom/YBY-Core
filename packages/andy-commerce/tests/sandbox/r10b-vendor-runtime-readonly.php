<?php
/**
 * R10B actual WordPress read-only preflight, to be invoked solely on marked
 * isolated local WooCommerce. Never edits cart/order/coupon/payment/session.
 */
declare(strict_types=1);
if (!defined('ABSPATH') || !function_exists('get_option')) {
    fwrite(STDERR, "R10B_WP_NOT_BOOTSTRAPPED\n");
    exit(3);
}
$marker = rtrim(ABSPATH, '/\\') . '/.andy-r10b-isolated-local';
$hostname = parse_url((string) get_option('siteurl', ''), PHP_URL_HOST);
if (!is_file($marker) || !is_string($hostname)
    || !preg_match('/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.test|[a-z0-9.-]+\.localhost)$/iD', $hostname)) {
    fwrite(STDERR, "R10B_FORBIDDEN_NONISOLATED_SITE\n");
    exit(3);
}
if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$required = [
    'woocommerce/woocommerce.php' => ['WooCommerce', '11.2.1'],
    'yaycurrency-pro/yay-currency.php' => ['YayCurrency Pro', '3.3.5'],
    'wt-smart-coupon-pro/wt-smart-coupon-pro.php' => ['WebToffee Smart Coupons Pro', '3.2.0'],
    'woocommerce-smart-coupons/woocommerce-smart-coupons.php' => ['StoreApps WooCommerce Smart Coupons', '9.51.0'],
];
$plugins = get_plugins();
$inventory = [];
$ready = true;
foreach ($required as $file => [$label, $expected]) {
    $record = $plugins[$file] ?? null;
    $actual = is_array($record) ? (string) ($record['Version'] ?? '') : null;
    $active = function_exists('is_plugin_active') && is_plugin_active($file);
    $valid = $active && $actual === $expected;
    $ready = $ready && $valid;
    $inventory[] = [
        'plugin' => $label, 'expected_version' => $expected,
        'actual_version' => $actual, 'active' => (bool) $active,
        'exact_match' => (bool) $valid,
    ];
}
$free = get_option('woocommerce_free_shipping_1_settings', []);
$flat = get_option('woocommerce_flat_rate_3_settings', []);
$read = static function ($option, $key) {
    return is_array($option) && isset($option[$key]) && is_scalar($option[$key])
        ? (string) $option[$key] : null;
};
$settings = [
    'woocommerce_currency' => (string) get_option('woocommerce_currency', ''),
    'woocommerce_enable_coupons' => (string) get_option('woocommerce_enable_coupons', ''),
    'woocommerce_calc_discounts_sequentially' => (string) get_option('woocommerce_calc_discounts_sequentially', ''),
    'free_shipping_requires' => $read($free, 'requires'),
    'free_shipping_min_amount' => $read($free, 'min_amount'),
    'free_shipping_ignore_discounts' => $read($free, 'ignore_discounts'),
    'flat_rate_cost' => $read($flat, 'cost'),
    'woocommerce_hpos' => (string) get_option('woocommerce_custom_orders_table_enabled', ''),
];
if ($settings['woocommerce_currency'] !== 'USD') { $ready = false; }
$report = [
    'gate' => 'R10B_DISPOSABLE_NATIVE_INVENTORY_ONLY',
    'status' => $ready ? 'READY_FOR_APPROVED_QUOTE_TEST_NOT_PASS' : 'BLOCKED',
    'site_type' => 'isolated_test',
    'active_plugin_versions' => $inventory,
    'shipping_coupon_config' => $settings,
    'actual_checkout_quote_verified' => false,
    'currency_switching_verified' => false,
    'real_coupon_stacking_verified' => false,
    'writes_performed' => 0,
];
echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($ready ? 0 : 2);
