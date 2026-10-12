<?php
/**
 * M5/M6 source-only adapter for a Woo Store API cart snapshot.
 * NEVER bootstrapped: not a REST route, filter or shipping authority.
 */
declare(strict_types=1);

final class Andy_Commerce_M5_Woo_Cart_Adapter {
    public static function source(array $cart, string $serverPolicyBasis, int $serverThresholdMinor, bool $trustedServerWooSnapshot = false): array {
        $base = [
            'effective_policy_basis' => $serverPolicyBasis,
            'source' => 'unverified',
            'woocommerce_rate_confirmed' => false,
            'selected_rate_id' => null,
            'discounted_merchandise_minor' => null,
            'threshold_minor' => $serverThresholdMinor,
        ];
        // Trust only server-side Woo snapshots and a trusted policy parameter.
        // A client-supplied body cannot establish this source assertion.
        if ($trustedServerWooSnapshot !== true
            || !isset($cart['items'], $cart['shipping_rates'], $cart['totals'])
            || !is_array($cart['items']) || !is_array($cart['shipping_rates'])
            || !is_array($cart['totals']) || $serverThresholdMinor <= 0
            || $serverThresholdMinor > 1000000000) {
            return $base;
        }
        $units = $cart['totals']['currency_minor_unit'] ?? null;
        if (!is_int($units) || $units < 0 || $units > 4 || count($cart['items']) === 0 || count($cart['items']) > 1000) {
            return $base;
        }
        $total = 0;
        foreach ($cart['items'] as $item) {
            if (!is_array($item) || !isset($item['totals']) || !is_array($item['totals'])
                || !isset($item['totals']['line_total'])) {
                return $base;
            }
            // Woo Store API provides integer minor-unit amounts as digit strings.
            $raw = $item['totals']['line_total'];
            if (!is_string($raw) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', $raw)) {
                return $base;
            }
            $total += (int) $raw;
            if ($total > 1000000000) { return $base; }
        }
        $selected = [];
        foreach ($cart['shipping_rates'] as $package) {
            if (!is_array($package) || !isset($package['shipping_rates'])
                || !is_array($package['shipping_rates'])) { return $base; }
            foreach ($package['shipping_rates'] as $rate) {
                if (!is_array($rate)) { return $base; }
                if (($rate['selected'] ?? false) === true) {
                    $selected[] = $rate['rate_id'] ?? null;
                }
            }
        }
        // Multiple shipping packages are not flattened into a fictitious rate.
        if (count($selected) !== 1 || !is_string($selected[0])
            || strlen($selected[0]) > 128
            || !preg_match('/^[A-Za-z0-9_-]+:[A-Za-z0-9_.-]+$/D', $selected[0])) {
            return $base;
        }
        return [
            'effective_policy_basis' => $serverPolicyBasis,
            'source' => 'woocommerce_selected_shipping_rate',
            'woocommerce_rate_confirmed' => true,
            'selected_rate_id' => $selected[0],
            'discounted_merchandise_minor' => $total,
            'threshold_minor' => $serverThresholdMinor,
        ];
    }
}
