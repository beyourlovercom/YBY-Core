<?php
/**
 * M11: read-only, fail-closed Woo cart -> verified USD snapshot.
 * Caller supplies explicit per-cart-line business classification. This adapter
 * never infers special shipping, gift eligibility, or checkout from product names.
 * It never invokes Woo mutation APIs (calculate, add fee, coupons, orders).
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M11_WC_Cart_Reader {
    public static function read(
        WC_Cart $cart,
        array $classificationByCartKey,
        int $thresholdUsdMinor
    ): array {
        if (!function_exists('get_woocommerce_currency')
            || get_woocommerce_currency() !== 'USD'
            || !function_exists('wc_get_price_decimals')
            || wc_get_price_decimals() !== 2) {
            throw new InvalidArgumentException('M11 requires native USD Woo ledger with 2 decimals');
        }
        if ($thresholdUsdMinor < 0 || $thresholdUsdMinor > 100000000) {
            throw new InvalidArgumentException('Untrusted USD threshold');
        }
        $items = $cart->get_cart();
        if (!is_array($items) || count($items) < 1 || count($items) > 100) {
            throw new InvalidArgumentException('Empty/oversized cart');
        }
        $applied = $cart->get_applied_coupons();
        if (!is_array($applied) || count($applied) > 1) {
            throw new InvalidArgumentException('Only one native coupon is supported');
        }
        if (abs((float) $cart->get_fee_total()) > 0.00001) {
            throw new InvalidArgumentException('Unverified fee allocations');
        }
        $minor = static function($value): int {
            if (!is_numeric($value) || !is_finite((float) $value)
                || (float) $value < 0 || (float) $value > 10000000) {
                throw new InvalidArgumentException('Invalid native Woo money value');
            }
            return (int) round(((float) $value) * 100);
        };
        $verifiedLines = [];
        $totalNet = 0;
        foreach ($items as $key => $item) {
            if (!is_array($item) || !isset($classificationByCartKey[$key])
                || !is_array($classificationByCartKey[$key])) {
                throw new InvalidArgumentException('Missing explicit cart-line classification');
            }
            $policy = $classificationByCartKey[$key];
            foreach (['eligible_for_promotion', 'special_shipping', 'free_gift'] as $field) {
                if (!array_key_exists($field, $policy) || !is_bool($policy[$field])) {
                    throw new InvalidArgumentException('Unverified cart-line business flag: '.$field);
                }
            }
            $qty = $item['quantity'] ?? null;
            if (!is_int($qty) || $qty < 1 || $qty > 100) {
                throw new InvalidArgumentException('Invalid Woo quantity');
            }
            $subtotal = $minor($item['line_subtotal'] ?? null);
            $net = $minor($item['line_total'] ?? null);
            if ($subtotal < $net || $subtotal % $qty !== 0) {
                throw new InvalidArgumentException('Line amount or per-unit precision cannot reconcile');
            }
            $verifiedLines[] = [
                'quantity' => $qty,
                'unit_usd_minor' => intdiv($subtotal, $qty),
                'applied_discount_usd_minor' => $subtotal - $net,
                'eligible_for_promotion' => $policy['eligible_for_promotion'],
                'special_shipping' => $policy['special_shipping'],
                'free_gift' => $policy['free_gift'],
            ];
            $totalNet += $net;
        }
        $merchandiseNet = $minor($cart->get_cart_contents_total());
        $shipping = $minor($cart->get_shipping_total());
        $tax = $minor($cart->get_total_tax());
        $actual = $minor($cart->get_total('edit'));
        if (abs($merchandiseNet - $totalNet) > 1
            || abs($actual - ($merchandiseNet + $shipping + $tax)) > 1) {
            throw new InvalidArgumentException('Native Woo total reconciliation failed');
        }
        $packages = $cart->get_shipping_packages();
        if (!is_array($packages) || count($packages) > 1) {
            throw new InvalidArgumentException('Multiple shipping packages not verified');
        }
        $zoneId = 0;
        if (count($packages) === 1) {
            if (!class_exists('WC_Shipping_Zones')) {
                throw new InvalidArgumentException('Woo shipping zones unavailable');
            }
            $zone = WC_Shipping_Zones::get_zone_matching_package($packages[0]);
            $zoneId = (int) $zone->get_id();
        } elseif ($shipping > 0) {
            throw new InvalidArgumentException('Shipping charge without a package');
        }
        return [
            'snapshot' => [
                'verified_woo_data' => true,
                'cart_empty' => false,
                'base_currency' => 'USD',
                'threshold_usd_minor' => $thresholdUsdMinor,
                'shipping_usd_minor' => $shipping,
                'tax_usd_minor' => $tax,
                'lines' => $verifiedLines,
            ],
            'shipping_zone_id' => $zoneId,
            'applied_native_coupon_codes' => array_values($applied),
            'native_merchandise_net_usd_minor' => $merchandiseNet,
            'native_payable_usd_minor' => $actual,
            'mode' => 'READ_ONLY_NEVER_EXECUTE_WOO',
        ];
    }
}
