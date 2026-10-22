<?php
/**
 * M4 OFFLINE shadow comparator. Never called from Woo runtime.
 * Compare proposed post-discount merchandise basis with legacy pre-discount
 * subtotal WITHOUT modifying actual BYL49 rules, shipping or Woo totals.
 */
declare(strict_types=1);
final class Andy_Commerce_M4_Shipping_Shadow {
    public static function compare(array $items, int $thresholdMinor): array {
        if ($thresholdMinor < 0 || $thresholdMinor > 100000000) {
            throw new InvalidArgumentException('Invalid currency-minor threshold');
        }
        if (count($items) > 1000) { throw new InvalidArgumentException('Too many lines'); }
        $legacy = 0;
        $proposed = 0;
        foreach ($items as $line) {
            if (!is_array($line) || !is_bool($line['gift'] ?? null)
                || !is_bool($line['eligible'] ?? null)) {
                throw new InvalidArgumentException('Invalid line flags');
            }
            if ($line['gift'] || !$line['eligible']) { continue; }
            $before = $line['line_subtotal_minor'] ?? null;
            $after = $line['line_total_minor'] ?? null;
            if (!is_int($before) || !is_int($after) || $before < 0
                || $after < 0 || $after > $before || $before > 100000000) {
                throw new InvalidArgumentException('Invalid merchandise values');
            }
            $legacy += $before;
            $proposed += $after;
            if ($legacy > 1000000000 || $proposed > 1000000000) {
                throw new InvalidArgumentException('Merchandise sum too large');
            }
        }
        $oldEligible = $legacy >= $thresholdMinor;
        $newEligible = $proposed >= $thresholdMinor;
        return [
            'legacy_basis' => 'pre_discount_merchandise_subtotal',
            'proposed_basis' => 'post_discount_merchandise_subtotal_excluding_tax_and_shipping',
            'legacy_merchandise_minor' => $legacy,
            'proposed_merchandise_minor' => $proposed,
            'threshold_minor' => $thresholdMinor,
            'legacy_eligible' => $oldEligible,
            'proposed_eligible' => $newEligible,
            'disagreement' => $oldEligible !== $newEligible,
            'shadow_only' => true,
            'runtime_enabled' => false
        ];
    }
}
