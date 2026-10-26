<?php
/**
 * Currency Runtime C-R1: real Woo product/cart price conversion in a disposable
 * local test environment only. This is NOT a release-ready payment/checkout
 * implementation. The guard must stay fail-closed on Dev and production.
 *
 * Source of truth: Woo raw product prices in USD, never rewritten to DB.
 * Scoped experiment: EUR at an explicit, synthetic immutable FX snapshot.
 * No Marketing, shipping, payment, order or refund hooks in this stage.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Currency_Runtime_C1 {
    public const TEST_CURRENCY = 'EUR';
    public const TEST_RATE_MICROS = 900000;

    public static function allowed(): bool {
        if (!defined('AICC_CURRENCY_RUNTIME_SANDBOX') || AICC_CURRENCY_RUNTIME_SANDBOX !== true
            || !defined('DB_NAME') || DB_NAME !== 'commerce_p5_isolated'
            || !function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'local'
            || !function_exists('home_url') || wp_parse_url(home_url('/'), PHP_URL_HOST) !== 'commerce-p5.invalid'
            || !class_exists('WooCommerce') || !class_exists('Andy_Commerce_Currency_OS_V1')) {
            return false;
        }
        return true;
    }

    public static function boot(): void {
        if (!self::allowed()) return;
        add_filter('woocommerce_currency', [__CLASS__, 'currency'], 60, 1);
        // Woo's product prices are stored as USD. Reading a "view" price quotes
        // an EUR amount, while "edit" remains unchanged and reproducible.
        foreach (['woocommerce_product_get_price',
                  'woocommerce_product_get_regular_price',
                  'woocommerce_product_get_sale_price',
                  'woocommerce_product_variation_get_price',
                  'woocommerce_product_variation_get_regular_price',
                  'woocommerce_product_variation_get_sale_price'] as $hook) {
            add_filter($hook, [__CLASS__, 'price'], 60, 2);
        }
        add_filter('woocommerce_variation_prices_price', [__CLASS__, 'variation_price'], 60, 3);
        add_filter('woocommerce_variation_prices_regular_price', [__CLASS__, 'variation_price'], 60, 3);
        add_filter('woocommerce_variation_prices_sale_price', [__CLASS__, 'variation_price'], 60, 3);
        add_filter('woocommerce_get_variation_prices_hash', [__CLASS__, 'variation_hash'], 60, 3);
    }

    public static function currency($code): string {
        return self::allowed() ? self::TEST_CURRENCY : (string)$code;
    }

    private static function from_usd($value) {
        if ($value === '' || $value === null) return $value;
        // Exact two-decimal Woo base price; do not silently round an
        // unexpected precision or accept arbitrary user-supplied text.
        if (!is_scalar($value)) throw new InvalidArgumentException('Currency base price is not scalar');
        $raw = (string)$value;
        if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D', $raw)) {
            throw new InvalidArgumentException('Currency base price must be a valid USD amount');
        }
        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '');
        $minor = (int)$whole * 100 + (int)str_pad($fraction, 2, '0');
        $local = Andy_Commerce_Currency_OS_V1::convert_usd_minor($minor, self::TEST_RATE_MICROS);
        return number_format($local / 100, 2, '.', '');
    }

    public static function price($value, $product) {
        if (!self::allowed()) return $value;
        return self::from_usd($value);
    }

    public static function variation_price($value, $variation, $parent) {
        if (!self::allowed()) return $value;
        return self::from_usd($value);
    }

    public static function variation_hash($hash, $product, $for_display) {
        if (self::allowed() && is_array($hash)) {
            $hash['aicc_currency_runtime'] = [self::TEST_CURRENCY, self::TEST_RATE_MICROS];
        }
        return $hash;
    }
}
