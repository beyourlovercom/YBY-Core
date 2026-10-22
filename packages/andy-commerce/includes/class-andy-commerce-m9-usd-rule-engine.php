<?php
/**
 * Marketing M9: multiple independent USD-threshold qualification and reward
 * previews, powered by the Currency OS post-discount USD ledger.
 *
 * IMPORTANT: PURE SOURCE-ONLY SHADOW. Never registers discounts, gifts,
 * shipping methods, checkout hooks, payment actions or order writes.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M9_USD_Rule_Engine {
    private const TYPES = ['gift', 'discount', 'shipping'];
    private const LIMIT = 10000000000; // USD 100 million in minor units.
    private const MAX_RULES = 100;

    private static function amount($amount, string $name): int {
        if (!is_int($amount) || $amount < 0 || $amount > self::LIMIT) {
            throw new InvalidArgumentException('Invalid USD amount: ' . $name);
        }
        return $amount;
    }

    private static function rules(array $rules): array {
        if (count($rules) > self::MAX_RULES) {
            throw new InvalidArgumentException('Too many independent rules');
        }
        $ids = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)
                || !is_string($rule['id'] ?? null)
                || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $rule['id'])
                || isset($ids[$rule['id']])
                || !in_array($rule['type'] ?? null, self::TYPES, true)
                || !is_bool($rule['active'] ?? null)
                || !is_int($rule['priority'] ?? null)
                || $rule['priority'] < 0 || $rule['priority'] > 1000
                || !is_string($rule['name'] ?? null)
                || $rule['name'] === '' || strlen($rule['name']) > 120) {
                throw new InvalidArgumentException('Malformed or duplicated campaign rule');
            }
            self::amount($rule['threshold_usd_minor'] ?? null, 'rule threshold');
            if ($rule['type'] === 'discount') {
                self::amount($rule['discount_usd_minor'] ?? null, 'discount');
                if ($rule['discount_usd_minor'] === 0) {
                    throw new InvalidArgumentException('Zero-value discount');
                }
            } elseif ($rule['type'] === 'gift') {
                if (!is_int($rule['gift_product_id'] ?? null)
                    || $rule['gift_product_id'] <= 0) {
                    throw new InvalidArgumentException('Gift requires a valid SKU product ID');
                }
            } else {
                // Native shipping-zone rules are owned by Woo; accepting a
                // merchant-supplied shipping rule here would cause authority drift.
                if (($rule['source'] ?? '') !== 'native_woo_zone_shadow') {
                    throw new InvalidArgumentException('Shipping rule must be native-zone shadow');
                }
            }
            $ids[$rule['id']] = true;
        }
        return $rules;
    }

    /**
     * A fully-validated post-discount Currency OS V1 quote is the single
     * source of truth; never derive eligibility from converted local totals.
     *
     * $zoneId: Woo's selected region 0/1/3/4/5 (no address inference).
     * $nativeFreeVerified: only positive evidence from native Woo for zone 1.
     */
    public static function preview(
        array $quote, array $rules, int $zoneId,
        bool $nativeFreeVerified = false
    ): array {
        if (($quote['mode'] ?? null) !== 'DEV_SHADOW_NO_PAYMENT'
            || ($quote['base_currency'] ?? null) !== 'USD'
            || !in_array($quote['currency'] ?? null, ['USD','EUR','GBP','AUD','CAD'], true)
            || !is_array($quote['fx_snapshot'] ?? null)
            || !is_int($quote['fx_snapshot']['rate_micros'] ?? null)
            || !class_exists('Andy_Commerce_Currency_OS_V1')
            || !in_array($zoneId, [0,1,3,4,5], true)) {
            throw new InvalidArgumentException('Unverified Currency OS ledger or Woo zone');
        }
        $subtotal = self::amount($quote['eligible_subtotal_usd_minor'] ?? null, 'eligible subtotal');
        $net = self::amount($quote['merchandise_net_usd_minor'] ?? null, 'merchandise total');
        if ($subtotal > $net) {
            throw new InvalidArgumentException('Eligible subtotal greater than merchandise');
        }
        $rules = self::rules($rules);
        $evaluations = [];
        $discounts = [];
        $gifts = [];
        foreach ($rules as $rule) {
            $qualifies = $subtotal >= $rule['threshold_usd_minor'];
            $eligible = $qualifies && $rule['active'] === false;
            $entry = [
                'id'=>$rule['id'], 'type'=>$rule['type'], 'name'=>$rule['name'],
                'threshold_usd_minor'=>$rule['threshold_usd_minor'],
                'qualification_usd_minor'=>$subtotal,
                'qualified'=>$qualifies,
                'status'=>$rule['active'] ? 'blocked_active_rule' :
                    ($qualifies ? 'preview_qualified' : 'below_threshold'),
                'reward_applied'=>false, 'gift_awarded'=>false,
            ];
            if ($eligible && $rule['type'] === 'discount') {
                $saving = min($subtotal, $rule['discount_usd_minor']);
                $discounts[] = [
                    'id'=>$rule['id'],
                    'priority'=>$rule['priority'],
                    'discount_usd_minor'=>$saving
                ];
                $entry['potential_discount_usd_minor'] = $saving;
            }
            if ($eligible && $rule['type'] === 'gift') {
                $entry['gift_product_id'] = $rule['gift_product_id'];
                $gifts[] = $rule['id']; // eligible only, NEVER create product.
            }
            $evaluations[] = $entry;
        }
        // Most benefit first, priority second, then stable ID; one projected
        // USD discount. Future stacking must have its own explicit policy.
        usort($discounts, static function (array $a, array $b): int {
            return ($b['discount_usd_minor'] <=> $a['discount_usd_minor'])
                ?: (($b['priority'] <=> $a['priority']) ?: strcmp($a['id'], $b['id']));
        });
        $winner = $discounts[0] ?? null;
        $award = $winner['discount_usd_minor'] ?? 0;
        $after = $subtotal - $award;
        // Real Woo conditional shipping fee/eligibility STILL belongs to
        // native destination shipping methods. Zone1 thresholdless free rate
        // needs independent Woo availability verification.
        $zoneThreshold = [0=>6900,1=>null,3=>16900,4=>12900,5=>6900][$zoneId];
        $shippingQualifies = $zoneThreshold === null ? $nativeFreeVerified : (
            $subtotal > 0 && $after >= $zoneThreshold
        );
        $rateMicros = $quote['fx_snapshot']['rate_micros'];
        $local = Andy_Commerce_Currency_OS_V1::convert_usd_minor($after, $rateMicros);
        return [
            'mode'=>'DEV_SHADOW_NO_PAYMENT',
            'runtime_enabled'=>false,
            'base_currency'=>'USD',
            'currency'=>$quote['currency'],
            'fx_snapshot'=>$quote['fx_snapshot'],
            'qualifying_before_campaign_usd_minor'=>$subtotal,
            'qualified_after_one_discount_usd_minor'=>$after,
            'qualified_after_one_discount_local_minor'=>$local,
            'projected_discount_usd_minor'=>$award,
            'selected_discount_id'=>$winner['id'] ?? null,
            'eligible_gift_ids'=>$gifts,
            'evaluations'=>$evaluations,
            'shipping'=>[
                'zone_id'=>$zoneId,
                'threshold_usd_minor'=>$zoneThreshold,
                'qualifies_by_policy_preview'=>$shippingQualifies,
                'native_rate_verified'=>($zoneId===1 && $nativeFreeVerified),
                'charge_waived'=>false,
                'special_parcel_fee_waived'=>false,
                'pillow_per_unit_fee_waived'=>false,
                'method_selected'=>false,
            ],
            'actual_discount_applied'=>false,
            'actual_gift_awarded'=>false,
            'real_checkout_enabled'=>false,
        ];
    }
}
