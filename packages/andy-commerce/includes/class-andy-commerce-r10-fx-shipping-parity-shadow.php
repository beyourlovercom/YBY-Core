<?php
/**
 * R10 read-only offline currency/shipping/coupon fixture contract.
 *
 * NO WordPress hook registration, orders, live FX retrieval or Woo runtime.
 * Source USD cents are authoritative. Local display never decides rewards.
 * Multiple manual coupons fail CLOSED, pending separate verification of the
 * two concurrently active WWW Smart Coupons plugins.
 */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/class-andy-commerce-m4-shipping-shadow.php';
require_once __DIR__ . '/class-andy-commerce-currency-os-v1.php';

final class Andy_Commerce_R10_FX_Shipping_Parity_Shadow {
    private const CURRENCIES = ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];

    public static function compare(
        array $items, array $manualCouponCodes, int $shippingThresholdUsdMinor,
        string $currency, int $syntheticFxMicros,
        string $nativeRequires = 'min_amount',
        bool $verifiedNativeFreeShippingCoupon = false
    ): array {
        if ( ! in_array( $currency, self::CURRENCIES, true ) ) {
            throw new InvalidArgumentException( 'Unsupported preview currency' );
        }
        // WW W native setting requires = either. Never infer a free-shipping
        // coupon from its code: verification is an explicit external input.
        if ( ! in_array( $nativeRequires, [ 'min_amount', 'either' ], true ) ) {
            throw new InvalidArgumentException( 'Unsupported Woo shipping requirement' );
        }
        if ( $verifiedNativeFreeShippingCoupon && count( $manualCouponCodes ) !== 1 ) {
            throw new InvalidArgumentException( 'Free shipping coupon must have one verified cart code' );
        }
        if ( count( $manualCouponCodes ) > 1 ) {
            throw new InvalidArgumentException( 'Multiple manual coupons require explicit stacking review' );
        }
        foreach ( $manualCouponCodes as $code ) {
            if ( ! is_string( $code ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,50}$/D', $code ) ) {
                throw new InvalidArgumentException( 'Invalid synthetic manual coupon' );
            }
        }
        $native = Andy_Commerce_M4_Shipping_Shadow::compare( $items, $shippingThresholdUsdMinor );
        $qualified = $native['proposed_eligible'];
        $legacyQualified = $native['legacy_eligible'];
        if ( 'either' === $nativeRequires && $verifiedNativeFreeShippingCoupon ) {
            $qualified = true;
            $legacyQualified = true;
        }
        $usd = $native['proposed_merchandise_minor'];
        $local = Andy_Commerce_Currency_OS_V1::convert_usd_minor( $usd, $syntheticFxMicros );
        return [
            'mode' => 'OFFLINE_FIXTURE_SHADOW_NO_WOO_NO_PAYMENT',
            'input_provenance' => 'SYNTHETIC_NOT_LIVE_QUOTE',
            'currency' => $currency,
            'fx_source' => 'SYNTHETIC_NOT_YAYCURRENCY',
            'synthetic_fx_rate_micros' => $syntheticFxMicros,
            'source_usd_eligible_minor' => $usd,
            'display_local_minor' => $local,
            'source_usd_threshold_minor' => $shippingThresholdUsdMinor,
            'shipping_requirement' => $nativeRequires,
            'verified_free_shipping_coupon_fixture' => $verifiedNativeFreeShippingCoupon,
            'shipping_preview_only' => $qualified,
            'legacy_shipping_preview_only' => $legacyQualified,
            'shipping_policy_disagreement' => $qualified !== $legacyQualified,
            'manual_coupon_count' => count( $manualCouponCodes ),
            'runtime_enabled' => false,
            'woo_hooks_registered' => 0,
            'woo_mutations' => 0,
            'real_checkout_verified' => false,
            'no_vendor_plugin_compatibility_claim' => true,
        ];
    }
}
