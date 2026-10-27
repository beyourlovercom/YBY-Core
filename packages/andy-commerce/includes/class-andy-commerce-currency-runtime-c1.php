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
    public const TEST_RATES = ['USD'=>1000000, 'EUR'=>900000, 'GBP'=>780000, 'AUD'=>1500000, 'CAD'=>1350000];
    private static bool $referenceUnavailable = false;

    public static function test_currency(): string {
        $code = defined('AICC_CURRENCY_RUNTIME_TEST_CURRENCY')
            ? AICC_CURRENCY_RUNTIME_TEST_CURRENCY : 'EUR';
        if (self::allowed() && function_exists('WC') && WC()->session) {
            $saved = WC()->session->get('aicc_currency_runtime_selected');
            if (is_string($saved) && array_key_exists($saved, self::TEST_RATES)) $code = $saved;
        }
        if (!is_string($code) || !array_key_exists($code, self::TEST_RATES)) {
            throw new InvalidArgumentException('Unsupported sandbox currency; fail closed');
        }
        return $code;
    }

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
        if (defined('AICC_CURRENCY_R13_FX_SNAPSHOT_FILE')) {
            try { self::quote(self::test_currency()); }
            catch (Throwable $e) {
                self::$referenceUnavailable=true;
                add_filter('rest_pre_dispatch',[__CLASS__,'blocked_rest_request'],1,3);
                add_action('woocommerce_checkout_process',[__CLASS__,'blocked_classic_checkout'],1);
                add_filter('woocommerce_available_payment_gateways',[__CLASS__,'blocked_gateways'],9999);
                add_action('woocommerce_before_cart',[__CLASS__,'blocked_notice'],1);
                add_action('woocommerce_before_checkout_form',[__CLASS__,'blocked_notice'],1);
                return;
            }
        }
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
        if (class_exists('Andy_Commerce_Currency_Runtime_C4')) Andy_Commerce_Currency_Runtime_C4::boot();
        if (class_exists('Andy_Commerce_Currency_Runtime_C6')) Andy_Commerce_Currency_Runtime_C6::boot();
        if (class_exists('Andy_Commerce_Currency_R13_Native_Tax_Shipping')) Andy_Commerce_Currency_R13_Native_Tax_Shipping::boot();
    }

    /** REST failure is explicit and occurs before the gateway is invoked. */
    public static function blocked_rest_request($result,$server,$request) {
        if (!self::$referenceUnavailable || !($request instanceof WP_REST_Request)) return $result;
        if (str_starts_with($request->get_route(),'/wc/store/v1/')) {
            return new WP_Error('aicc_fx_rate_unavailable',
                'Exchange rates are temporarily unavailable. Please try again later.',
                ['status'=>503]);
        }
        return $result;
    }
    public static function blocked_classic_checkout(): void {
        if (self::$referenceUnavailable) wc_add_notice(
            'Exchange rates are temporarily unavailable. Please try again later.','error');
    }
    public static function blocked_gateways($gateways): array {
        return self::$referenceUnavailable ? [] : (array)$gateways;
    }
    public static function blocked_notice(): void {
        if (self::$referenceUnavailable) wc_print_notice(
            'Exchange rates are temporarily unavailable. Please try again later.','error');
    }

    /** Consistent per-shopper frozen quote: reject a changed/expired provider file. */
    public static function quote(string $currency): array {
        if (!array_key_exists($currency,self::TEST_RATES))
            throw new InvalidArgumentException('Unexpected shopper currency');
        if (defined('AICC_CURRENCY_R13_FX_SNAPSHOT_FILE')) {
            if (!class_exists('Andy_Commerce_Currency_R13_FX_Quote_Guard'))
                throw new InvalidArgumentException('R13 reference rate validator missing');
            $quote=Andy_Commerce_Currency_R13_FX_Quote_Guard::selected_rate($currency);
            if (WC()->session) {
                $key='aicc_currency_r13_fx_quote_lock';
                $old=WC()->session->get($key);
                if (is_array($old) && $old!==$quote)
                    throw new InvalidArgumentException('Shopper FX reference changed or expired; restart quote');
                WC()->session->set($key,$quote);
            }
            return $quote;
        }
        return ['currency'=>$currency,'rate_micros'=>self::TEST_RATES[$currency],
            'rate_source'=>'CI_SYNTHETIC_LOCKED','retrieved_at_utc'=>1793000000,
            'quote_date'=>null,'snapshot_hash'=>null,'is_settlement_rate'=>false];
    }

    public static function select_for_sandbox(string $code): bool {
        if (!self::allowed() || !array_key_exists($code, self::TEST_RATES)
            || !function_exists('WC') || !WC()->session) return false;
        WC()->session->set('aicc_currency_runtime_selected', $code);
        WC()->session->__unset('aicc_currency_r13_fx_quote_lock');
        // Invalidate Woo's cached shipping calculations across currency switches.
        foreach (array_keys(WC()->cart?WC()->cart->get_shipping_packages():[]) as $index)
            WC()->session->__unset('shipping_for_package_'.$index);
        return self::test_currency() === $code;
    }

    public static function currency($code): string {
        return self::allowed() ? self::test_currency() : (string)$code;
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
        $local = Andy_Commerce_Currency_OS_V1::convert_usd_minor($minor, self::quote(self::test_currency())['rate_micros']);
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
            $q=self::quote(self::test_currency());
            $hash['aicc_currency_runtime'] = [$q['currency'],$q['rate_micros'],$q['snapshot_hash']];
        }
        return $hash;
    }
}
