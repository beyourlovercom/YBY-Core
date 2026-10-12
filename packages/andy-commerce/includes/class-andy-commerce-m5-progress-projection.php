<?php
/**
 * M5 OFFLINE read-only shipping progress projection contract.
 * No Store API route, cart hook, coupon, gift award, or shipping mutation.
 * A future Woo-authoritative adapter must supply confirmed eligibility data.
 */
declare(strict_types=1);
final class Andy_Commerce_M5_Progress_Projection {
    private const TARGET_BASIS = 'post_discount_merchandise_subtotal_excluding_tax_and_shipping';

    public static function project(array $source): array {
        $base = [
            'schema_version'=>1,
            'state'=>'hidden',
            'show_progress'=>false,
            'gift_enabled'=>false,
            'claim_free_shipping'=>false,
            'remaining_minor'=>null,
            'qualified_minor'=>null,
            'threshold_minor'=>null,
            'percent'=>null,
            'authority'=>'woocommerce',
        ];
        // Never display a misleading new-basis progress bar against legacy BYL49.
        if (($source['effective_policy_basis'] ?? '') !== self::TARGET_BASIS) {
            return $base + ['reason'=>'legacy_policy_migration_pending'];
        }
        // This field is supplied by a future Woo-only adapter, never a promo
        // evaluator or visitor input. A missing selected rate fails closed.
        $selectedRate = $source['selected_rate_id'] ?? null;
        if (($source['woocommerce_rate_confirmed'] ?? false) !== true
            || ($source['source'] ?? '') !== 'woocommerce_selected_shipping_rate'
            || !is_string($selectedRate) || strlen($selectedRate) > 128
            || !preg_match('/^[A-Za-z0-9_-]+:[A-Za-z0-9_.-]+$/D', $selectedRate)) {
            return $base + ['reason'=>'woo_authority_unconfirmed'];
        }
        $current=$source['discounted_merchandise_minor'] ?? null;
        $threshold=$source['threshold_minor'] ?? null;
        if (!is_int($current) || !is_int($threshold) || $current < 0 || $threshold <= 0
            || $current > 1000000000 || $threshold > 1000000000) {
            return $base + ['reason'=>'invalid_currency_basis'];
        }
        // This projection may never assert an actual free-shipping award;
        // Woo shipping rate remains authoritative even at threshold.
        $remaining=max(0,$threshold-$current);
        return [
            'schema_version'=>1,'state'=>'ready','show_progress'=>true,
            'gift_enabled'=>false,'claim_free_shipping'=>false,
            'remaining_minor'=>$remaining,'qualified_minor'=>$current,
            'threshold_minor'=>$threshold,'percent'=>min(100, intdiv($current*100,$threshold)),
            'authority'=>'woocommerce','reason'=>'read_only_projection',
        ];
    }
}
