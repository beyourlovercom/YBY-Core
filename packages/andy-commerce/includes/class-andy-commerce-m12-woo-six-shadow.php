<?php
/**
 * M12: verified native Woo cart -> existing M10 six-type preview.
 * NO Woo hooks, no activity activation, no checkout/gateway/stock writes.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
require_once __DIR__.'/class-andy-commerce-m11-wc-cart-reader.php';
require_once __DIR__.'/class-andy-commerce-m10-six-type-simulator.php';

final class Andy_Commerce_M12_Woo_Six_Shadow {
    public static function preview(
        WC_Cart $cart,
        array $classificationByCartKey,
        int $thresholdUsdMinor,
        array $inactiveDrafts,
        string $dateUtc,
        string $displayCurrency = 'USD',
        int $syntheticFxMicros = 1000000
    ): array {
        if (!function_exists('WC') || !WC() || !isset(WC()->customer)
            || !is_object(WC()->customer)) {
            throw new InvalidArgumentException('No trusted native Woo customer');
        }
        $country = WC()->customer->get_shipping_country();
        if (!is_string($country) || !preg_match('/^[A-Z]{2}$/D', $country)) {
            throw new InvalidArgumentException('Unverified Woo shipping country');
        }
        $read = Andy_Commerce_M11_WC_Cart_Reader::read(
            $cart, $classificationByCartKey, $thresholdUsdMinor
        );
        $items = [];
        foreach ($read['catalog_lines'] as $line) {
            if (!$line['eligible_for_promotion']) { continue; }
            $items[] = [
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'discounted_line_minor' => $line['discounted_line_minor'],
                'catalog_present' => true,
            ];
        }
        if (!$items) {
            throw new InvalidArgumentException('No classified eligible native Woo lines');
        }
        $eligible = array_sum(array_column($items, 'discounted_line_minor'));
        $preview = Andy_Commerce_M10_Six_Type_Simulator::preview([
            'source' => 'WOO_NATIVE_READ_ONLY',
            'verified_native_woo' => true,
            'verified_eligible_usd_minor' => $eligible,
            'trusted' => true,
            'date_utc' => $dateUtc,
            'country' => $country,
            'currency' => $displayCurrency,
            'fx_rate_micros' => $syntheticFxMicros,
            'manual_coupon' => $read['applied_native_coupon_codes'][0] ?? null,
            'items' => $items,
        ], $inactiveDrafts);
        if (($preview['reason'] ?? '') === 'invalid_snapshot'
            || ($preview['reason'] ?? '') === 'native_woo_mismatch'
            || ($preview['runtime_enabled'] ?? null) !== false
            || ($preview['actual_discount_applied'] ?? null) !== false
            || ($preview['actual_gift_awarded'] ?? null) !== false) {
            throw new LogicException('M12 six-type preview must fail closed');
        }
        return [
            'mode' => 'WOO_NATIVE_SHADOW_NO_MUTATION',
            'input_provenance' => 'WOO_NATIVE_READ_ONLY',
            'fx_source' => 'DEV_SYNTHETIC_NO_SETTLEMENT',
            'native_woo' => [
                'shipping_zone_id' => $read['shipping_zone_id'],
                'actual_payable_usd_minor' => $read['native_payable_usd_minor'],
                'merchandise_net_usd_minor' => $read['native_merchandise_net_usd_minor'],
                'eligible_usd_minor' => $eligible,
                'shipping_usd_minor' => $read['snapshot']['shipping_usd_minor'],
                'tax_usd_minor' => $read['snapshot']['tax_usd_minor'],
            ],
            'simulation' => $preview,
            'runtime_enabled' => false,
            'real_checkout_enabled' => false,
            'native_woo_mutations' => 0,
        ];
    }
}
