<?php
/**
 * M5 Woo server snapshot -> eligibility projection v2 (code-only, unbootstrapped).
 * Woo shipping rate is the only authority; policy basis/zone/currency come
 * from trusted server callers, never from browser payloads or a campaign draft.
 */
declare(strict_types=1);

final class Andy_Commerce_M5_Woo_Cart_Adapter {
    public static function source(
        array $cart,
        string $serverPolicyBasis,
        int $thresholdMinor,
        bool $trustedServerWooSnapshot = false,
        ?callable $serverLineEligibility = null
    ): array {
        $base = [
            'effective_policy_basis'=>$serverPolicyBasis,
            'source'=>'unverified',
            'woocommerce_rate_confirmed'=>false,
            'selected_rate_id'=>null,
            'discounted_merchandise_minor'=>null,
            'threshold_minor'=>$thresholdMinor,
        ];
        // Explicit server-derived line eligibility is mandatory: automatically
        // awarded gift lines must never qualify themselves for more offers.
        if (!$trustedServerWooSnapshot || !$serverLineEligibility || $thresholdMinor <= 0
            || $thresholdMinor > 1000000000 || !isset($cart['items'],$cart['shipping_rates'],$cart['totals'])
            || !is_array($cart['items']) || !is_array($cart['shipping_rates'])
            || !is_array($cart['totals']) || count($cart['items']) < 1
            || count($cart['items']) > 1000 || count($cart['shipping_rates']) !== 1) {
            return $base;
        }
        $code=$cart['totals']['currency_code']??null;
        $units=$cart['totals']['currency_minor_unit']??null;
        if (!is_string($code) || !preg_match('/^[A-Z]{3}$/D',$code)
            || !is_int($units) || $units < 0 || $units > 4) {return $base;}
        $total=0;
        $eligibleCount=0;
        foreach ($cart['items'] as $item) {
            if (!is_array($item) || !isset($item['totals']) || !is_array($item['totals'])) return $base;
            $itemCode=$item['totals']['currency_code']??($item['prices']['currency_code']??$code);
            $itemUnits=$item['totals']['currency_minor_unit']??($item['prices']['currency_minor_unit']??$units);
            if ($itemCode!==$code || $itemUnits!==$units) return $base;
            $raw=$item['totals']['line_total']??null;
            if (!is_string($raw) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D',$raw)) return $base;
            try { $isEligible=$serverLineEligibility($item); }
            catch (Throwable $e) {return $base;}
            if (!is_bool($isEligible)) return $base;
            if (!$isEligible) continue;
            $eligibleCount++;
            $total+=(int)$raw;
            if ($total>1000000000) return $base;
        }
        if ($eligibleCount===0) return $base;
        $package=$cart['shipping_rates'][0];
        if (!is_array($package) || !isset($package['shipping_rates'])
            || !is_array($package['shipping_rates'])) return $base;
        $selected=[];
        foreach ($package['shipping_rates'] as $rate) {
            if (!is_array($rate)) return $base;
            if (($rate['selected']??false)===true) $selected[]=$rate['rate_id']??null;
        }
        if (count($selected)!==1 || !is_string($selected[0])
            || strlen($selected[0]) > 128
            || !preg_match('/^[A-Za-z0-9_-]+:[A-Za-z0-9_.-]+$/D',$selected[0])) return $base;
        return [
            'effective_policy_basis'=>$serverPolicyBasis,
            'source'=>'woocommerce_selected_shipping_rate',
            'woocommerce_rate_confirmed'=>true,
            'selected_rate_id'=>$selected[0],
            'discounted_merchandise_minor'=>$total,
            'threshold_minor'=>$thresholdMinor,
        ];
    }
}
