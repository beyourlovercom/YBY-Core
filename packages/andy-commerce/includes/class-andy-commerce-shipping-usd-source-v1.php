<?php
/**
 * Read-only, source-USD merchandise subtotal for Woo shipping eligibility.
 * Never recover USD by inverse FX of YayCurrency display prices.
 * No hooks, option mutations, orders or checkout writes.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Shipping_USD_Source_V1 {
    private const ALLOWED_CURRENCIES = array('USD', 'EUR', 'GBP', 'AUD', 'CAD');

    private static function cents($value): ?int {
        if (!is_numeric($value) || !is_scalar($value)) return null;
        $value = (float) $value;
        if (!is_finite($value) || $value < 0 || $value > 1000000) return null;
        $cents = (int) round($value * 100);
        if (abs($value * 100 - $cents) > 0.011) return null;
        return $cents;
    }

    private static function storefront_currency(): ?string {
        if (!function_exists('get_option') || get_option('woocommerce_currency', '') !== 'USD'
            || get_option('woocommerce_prices_include_tax', 'no') !== 'no'
            || !function_exists('wc_get_price_decimals') || wc_get_price_decimals() !== 2) return null;

        $helper = 'Yay_Currency\\Helpers\\YayCurrencyHelper';
        if (!class_exists($helper)) return 'USD'; // Native Woo, base currency only.
        if (!method_exists($helper, 'detect_current_currency')) return null;
        try { $selected = $helper::detect_current_currency(); }
        catch (Throwable $e) { return null; }
        if (!is_array($selected) || (int)($selected['status'] ?? 0) !== 1) return null;
        $currency = $selected['currency'] ?? null;
        return is_string($currency) && in_array($currency, self::ALLOWED_CURRENCIES, true)
            ? $currency : null;
    }

    public static function subtotal($cart): ?float {
        if (!is_object($cart) || !method_exists($cart, 'get_cart')) return null;
        $currency = self::storefront_currency();
        if ($currency === null) return null;
        $lines = $cart->get_cart();
        if (!is_array($lines) || !$lines || count($lines) > 200) return null;

        if ($currency !== 'USD' && (!function_exists('has_filter')
            || has_filter('yay_currency_convert_price') === false
            || !function_exists('apply_filters'))) return null;

        $total = 0;
        $units = 0;
        foreach ($lines as $line) {
            if (!is_array($line)) return null;
            // A free promotional gift must never count toward a shipping threshold.
            if (array_key_exists('andy_campaign_pack_id', $line)
                || array_key_exists('_byl_bf2026_gift', $line)
                || array_key_exists('_byl_bf2026_upgrade', $line)
                || !empty($line['andy_commerce_free_gift'])) continue;
            $quantity = $line['quantity'] ?? null;
            $product = $line['data'] ?? null;
            if (!is_int($quantity) || $quantity < 1 || $quantity > 100
                || !is_object($product) || !method_exists($product, 'get_price')) return null;
            $sourceUnit = self::cents($product->get_price('edit'));
            if ($sourceUnit === null) return null;
            $localUnit = $sourceUnit;
            if ($currency !== 'USD') {
                try {
                    $converted = apply_filters(
                        'yay_currency_convert_price',
                        number_format($sourceUnit / 100, 2, '.', '')
                    );
                } catch (Throwable $e) { return null; }
                $localUnit = self::cents($converted);
                if ($localUnit === null) return null;
            }
            $actualLocal = self::cents($line['line_subtotal'] ?? null);
            if ($actualLocal === null
                || abs($actualLocal - $localUnit * $quantity) > min(10, 2 * $quantity + 1)) return null;
            $total += $sourceUnit * $quantity;
            $units += $quantity;
            if ($total > 100000000 || $units > 500) return null;
        }
        return $units > 0 ? $total / 100 : null;
    }
}
