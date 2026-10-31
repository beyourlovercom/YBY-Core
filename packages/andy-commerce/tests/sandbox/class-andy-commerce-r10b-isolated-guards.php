<?php
/**
 * R10-B shipping and coupon candidates: ISOLATED UAT ONLY.
 *
 * No automatic boot. The guard requires a local, explicitly marked
 * disposable WordPress root; never load on Dev or production.
 * Shipping: repair only the native Woo float-equality false negative.
 * Coupon: reject a second positive-value, ordinary discount code;
 * preserve Woo and vendors' decisions for gift, credit, free-shipping,
 * unknown and nonstandard coupon types.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10B_Isolated_Guards {
    private const ORDINARY = array('percent', 'fixed_cart', 'fixed_product');
    private static bool $registered = false;

    public static function sandbox_allowed(): bool {
        if (!function_exists('get_option') || !function_exists('WC')) return false;
        $url = (string) get_option('siteurl', '');
        $marker = rtrim(ABSPATH, '/\\') . '/.andy-r10b-isolated-local';
        return is_file($marker) && $url === 'http://127.0.0.1:14141';
    }

    public static function boot(): bool {
        if (self::$registered) return true;
        if (!self::sandbox_allowed() || !class_exists('WC_Coupon')) return false;
        // Earliest availability observer: later vendor filters can still deny.
        add_filter('woocommerce_shipping_free_shipping_is_available',
            array(self::class, 'boundary_only'), -100000, 3);
        // After both coupon vendors' validation, but before validation returns.
        add_filter('woocommerce_coupon_is_valid',
            array(self::class, 'single_ordinary_discount'), 100000, 2);
        self::$registered = true;
        return true;
    }

    private static function discount_coupon($coupon): bool {
        if (!($coupon instanceof WC_Coupon)) return false;
        try {
            if ($coupon->get_free_shipping()) return false;
            return in_array($coupon->get_discount_type(), self::ORDINARY, true)
                && is_numeric($coupon->get_amount())
                && (float)$coupon->get_amount() > 0.0;
        } catch (Throwable $e) { return false; }
    }

    /** Never convert a failed vendor validation into a pass. */
    public static function single_ordinary_discount($valid, $coupon) {
        if (!$valid || !self::sandbox_allowed() || !self::discount_coupon($coupon)) return $valid;
        $cart = WC()->cart ?? null;
        if (!is_object($cart) || !method_exists($cart, 'get_applied_coupons')) return $valid;
        $code = (string)$coupon->get_code();
        // Keep the earliest applied ordinary discount; later discounts are
        // invalid. It works both during application and on cart recalculation.
        foreach ((array)$cart->get_applied_coupons() as $applied) {
            $applied = (string)$applied;
            if ($applied === $code) return $valid;
            try {
                $existing = new WC_Coupon($applied);
                if (self::discount_coupon($existing)) return false;
            } catch (Throwable $e) {
                // Unknown/unreadable coupons are not authorized as exemptions.
                return false;
            }
        }
        return $valid;
    }

    /**
     * Native Woo has already decided. Only turn an exact minor-unit equality
     * false-negative to true when binary double representation differs by
     * < 1e-9; never grant a sub-cent discount or a separate free-ship policy.
     * Non-Woo/late vendor rules may still deny free shipping.
     */
    public static function boundary_only($available, $package, $method) {
        if ($available || !self::sandbox_allowed()
            || !is_object($method) || !is_array($package)
            || ($method->id ?? '') !== 'free_shipping'
            || ($method->enabled ?? '') !== 'yes'
            || !in_array($method->requires ?? '', array('either', 'min_amount'), true)
            || ($method->ignore_discounts ?? '') !== 'no'
            || !function_exists('wc_get_price_decimals')
            || wc_get_price_decimals() !== 2
            || !class_exists('WC_Shipping_Zones')) return $available;
        $cart = WC()->cart ?? null;
        if (!is_object($cart) || !method_exists($cart, 'get_displayed_subtotal')
            || !method_exists($cart, 'get_discount_total')
            || !method_exists($cart, 'get_shipping_packages')) return $available;
        // Require exactly the Woo matched zone / method instance.
        $zone = WC_Shipping_Zones::get_zone_matching_package($package);
        if (!is_object($zone) || !method_exists($zone, 'get_shipping_methods')) return $available;
        $owned = false;
        foreach ($zone->get_shipping_methods(false) as $installed) {
            if ($installed->id === 'free_shipping'
                && (int)$installed->instance_id === (int)($method->instance_id ?? -1)
                && ($installed->enabled ?? '') === 'yes') { $owned = true; break; }
        }
        if (!$owned) return $available;
        try {
            $actual = (float)$cart->get_displayed_subtotal()
                - (float)$cart->get_discount_total();
            if ($cart->display_prices_including_tax()) {
                $actual -= (float)$cart->get_discount_tax();
            }
            $minimum = $method->min_amount ?? null;
            if (!is_numeric($minimum) || !is_finite($actual)
                || $actual < 0 || $actual > 1000000
                || !is_finite((float)$minimum)
                || (float)$minimum <= 0 || (float)$minimum > 1000000) return $available;
            $minimum = (float)$minimum;
            $minorCart = (int)round($actual * 100);
            $minorMin = (int)round($minimum * 100);
            $delta = $minimum - $actual;
            // No broad overrides. The ONLY repairable state is:
            // same currency cents, tiny negative binary epsilon.
            if ($minorCart !== $minorMin || $delta <= 0 || $delta >= 1e-9) return $available;
            // A valid free-shipping coupon would already be natively granted.
            // This candidate repairs only the minimum-amount calculation.
            return true;
        } catch (Throwable $e) { return $available; }
    }
}
