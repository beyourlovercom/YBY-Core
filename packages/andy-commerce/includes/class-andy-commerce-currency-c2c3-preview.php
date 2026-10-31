<?php
/**
 * C2/C3 deterministic display + promotion/shipping reconciliation model.
 * Read-only Dev preview: accepts verified, caller-supplied FX snapshots.
 * Never hooks Woo prices, promotions, shipping, checkout or gateways.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_Currency_C2C3_Preview {
    public static function calculate(
        array $request, array $rules, int $zoneId, bool $nativeFreeVerified = false
    ): array {
        if (!class_exists('Andy_Commerce_Currency_OS_V1')
            || !class_exists('Andy_Commerce_M9_USD_Rule_Engine')) {
            throw new LogicException('C2/C3 preview dependencies unavailable');
        }
        $quote = Andy_Commerce_Currency_OS_V1::quote($request);
        $marketing = Andy_Commerce_M9_USD_Rule_Engine::preview(
            $quote, $rules, $zoneId, $nativeFreeVerified
        );
        if (($marketing['base_currency'] ?? null) !== 'USD'
            || ($marketing['currency'] ?? null) !== $quote['currency']
            || ($marketing['fx_snapshot'] ?? null) !== $quote['fx_snapshot']) {
            throw new LogicException('FX snapshot reconciliation failed');
        }
        $rate = $quote['fx_snapshot']['rate_micros'];
        $projectedUsd = $marketing['projected_discount_usd_minor'];
        $projectedLocal = Andy_Commerce_Currency_OS_V1::convert_usd_minor($projectedUsd, $rate);
        $shipping = $marketing['shipping'];
        return [
            'mode'=>'DEV_SHADOW_NO_PAYMENT',
            'runtime_enabled'=>false,
            'base_currency'=>'USD',
            'display_currency'=>$quote['currency'],
            'fx_snapshot'=>$quote['fx_snapshot'],
            'merchandise_usd_minor'=>$quote['merchandise_net_usd_minor'],
            'merchandise_display_minor'=>$quote['merchandise_net_local_minor'],
            'eligible_subtotal_usd_minor'=>$quote['eligible_subtotal_usd_minor'],
            'projected_campaign_discount_usd_minor'=>$projectedUsd,
            'projected_campaign_discount_display_minor'=>$projectedLocal,
            'campaign_display_after_usd_minor'=>$marketing['qualified_after_one_discount_usd_minor'],
            'campaign_display_after_local_minor'=>$marketing['qualified_after_one_discount_local_minor'],
            'selected_discount_id'=>$marketing['selected_discount_id'],
            'eligible_gift_ids'=>$marketing['eligible_gift_ids'],
            'shipping_zone_id'=>$zoneId,
            'shipping_policy_preview'=>$shipping,
            'shipping_native_rate_applied'=>false,
            'shipping_actual_usd_minor'=>$quote['shipping_usd_minor'],
            'shipping_display_minor'=>$quote['shipping_local_minor'],
            'tax_actual_usd_minor'=>$quote['tax_usd_minor'],
            'tax_display_minor'=>$quote['tax_local_minor'],
            'order_actual_total_usd_minor'=>$quote['accounting_total_usd_minor'],
            'order_display_total_minor'=>$quote['payable_local_minor'],
            'real_checkout_enabled'=>false,
        ];
    }
}
