<?php
/**
 * Andy Commerce M2 — Pure Promotion Sandbox Evaluator.
 *
 * No WordPress bootstrap, hooks, database access, cart mutation, coupon writes,
 * order creation or activation. Only normalized, synthetic Woo line data.
 *
 * IMPORTANT: This file is deliberately NOT included by andy-commerce.php.
 * Live promotion rules must use a separately authorized server integration.
 */
declare(strict_types=1);

final class Andy_Commerce_Marketing_Pure_Evaluator {
    private const MAX_MINOR = 1000000000000;
    private const MAX_UNITS = 5000;

    /**
     * Evaluate mutually exclusive automatic offers; optionally simulate one
     * manually entered coupon already reflected in Woo line_total_minor.
     *
     * $cart = [
     *   'currency'=>'USD',
     *   'lines'=>[ ['key'=>'abc','product_id'=>123,'quantity'=>2,
     *                'line_total_minor'=>1998,'eligible'=>true], ... ],
     *   'applied_coupon_count'=>0
     * ]
     * line_total_minor is Woo's POST-DISCOUNT, EX-TAX merchandise line_total.
     * No shipping, tax or fees may be included in this input.
     *
     * $offers: status draft|active (sandbox simulations ONLY), priority integer,
     * id string, type bogo|bundle|spend_save|tiered, currency.
     */
    public static function evaluate(array $cart, array $offers, array $context = []): array {
        if (($context['sandbox'] ?? false) !== true) {
            throw new InvalidArgumentException('M2 evaluator is sandbox-only; runtime use is not permitted');
        }
        $currency = $cart['currency'] ?? null;
        if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new InvalidArgumentException('Invalid cart currency');
        }
        $couponCount = $cart['applied_coupon_count'] ?? 0;
        if (!is_int($couponCount) || $couponCount < 0 || $couponCount > 1) {
            return self::blocked($currency, 'more_than_one_coupon');
        }
        $lines = self::normalizeLines($cart['lines'] ?? null);
        $basis = self::sumTotals($lines);
        $base = [
            'currency' => $currency,
            'basis' => 'post_discount_merchandise_subtotal_excluding_tax_and_shipping',
            'basis_minor' => $basis,
            'coupon_count' => $couponCount,
            'winner' => null,
            'discount_minor' => 0,
            'after_auto_minor' => $basis,
            'free_shipping_eligibility_minor' => $basis,
            'requires_checkout_recalculation' => true,
            'mode' => 'isolated_sandbox_only',
        ];
        if (!is_array($offers) || count($offers) > 100) {
            throw new InvalidArgumentException('Invalid campaign collection');
        }
        $candidates = [];
        $observedOfferIds = [];
        foreach ($offers as $offer) {
            if (!is_array($offer) || !self::eligibleOffer($offer, $currency, $couponCount, $context)) { continue; }
            if (isset($observedOfferIds[$offer['id']])) {
                throw new InvalidArgumentException('Duplicate campaign ID in the sandbox');
            }
            $observedOfferIds[$offer['id']] = true;
            try {
                $discount = self::compute($offer, $lines, $basis);
            } catch (InvalidArgumentException $invalidOffer) {
                continue; // Bad campaign fails closed, never guesses an award.
            }
            if ($discount <= 0) { continue; }
            $candidates[] = [
                'id' => $offer['id'],
                'type' => $offer['type'],
                'priority' => $offer['priority'] ?? 0,
                'discount_minor' => min($basis, $discount),
            ];
        }
        // Non-stackable by construction. Priority, benefit, ID stable order.
        usort($candidates, static function (array $a, array $b): int {
            return ($b['priority'] <=> $a['priority'])
                ?: ($b['discount_minor'] <=> $a['discount_minor'])
                ?: strcmp($a['id'], $b['id']);
        });
        if ($candidates) {
            $win = $candidates[0];
            $base['winner'] = ['id' => $win['id'], 'type' => $win['type']];
            $base['discount_minor'] = $win['discount_minor'];
            $base['after_auto_minor'] = $basis - $win['discount_minor'];
            $base['free_shipping_eligibility_minor'] = $base['after_auto_minor'];
        }
        return $base;
    }

    private static function blocked(string $currency, string $reason): array {
        return [
            'currency'=>$currency, 'basis'=>'post_discount_merchandise_subtotal_excluding_tax_and_shipping',
            'basis_minor'=>0, 'winner'=>null, 'discount_minor'=>0,
            'after_auto_minor'=>0, 'free_shipping_eligibility_minor'=>0,
            'mode'=>'isolated_sandbox_only', 'blocked_reason'=>$reason,
            'requires_checkout_recalculation'=>true,
        ];
    }

    private static function normalizeLines($raw): array {
        if (!is_array($raw) || count($raw) > 300) {
            throw new InvalidArgumentException('Invalid merchandise lines');
        }
        $out = []; $keys = []; $units = 0;
        foreach ($raw as $line) {
            if (!is_array($line)) { throw new InvalidArgumentException('Invalid line'); }
            $key = $line['key'] ?? null;
            $id = $line['product_id'] ?? null;
            $quantity = $line['quantity'] ?? null;
            $total = $line['line_total_minor'] ?? null;
            if (!is_string($key) || !preg_match('/^[a-zA-Z0-9_.-]{1,90}$/D', $key)
                || isset($keys[$key]) || !is_int($id) || $id < 1
                || !is_int($quantity) || $quantity < 1
                || !is_int($total) || $total < 0 || $total > self::MAX_MINOR) {
                throw new InvalidArgumentException('Invalid line key, item ID, quantity or minor total');
            }
            $keys[$key] = true; $units += $quantity;
            if ($units > self::MAX_UNITS) { throw new InvalidArgumentException('Too many item units'); }
            if (($line['eligible'] ?? true) === false || ($line['is_reward'] ?? false) === true) {
                continue; // Promo rewards cannot self-qualify; exclude nonmerchandise.
            }
            $out[] = ['key'=>$key, 'id'=>$id, 'qty'=>$quantity, 'total'=>$total];
        }
        return $out;
    }

    private static function sumTotals(array $lines): int {
        $sum = 0;
        foreach ($lines as $line) {
            if ($sum > self::MAX_MINOR - $line['total']) {
                throw new InvalidArgumentException('Cart money upper bound exceeded');
            }
            $sum += $line['total'];
        }
        return $sum;
    }

    private static function eligibleOffer(array $o, string $currency, int $couponCount, array $ctx): bool {
        if (!isset($o['id'], $o['type'], $o['currency'], $o['status'])
            || !is_string($o['id']) || !preg_match('/^[a-zA-Z0-9_-]{1,60}$/D', $o['id'])
            || !is_string($o['type']) || !in_array($o['type'], ['bogo','bundle','spend_save','tiered'], true)
            || $o['currency'] !== $currency
            || !in_array($o['status'], ['draft','active'], true)
            || !is_int($o['priority'] ?? 0) || ($o['priority'] ?? 0) < -1000
            || ($o['priority'] ?? 0) > 1000) {
            return false;
        }
        if ($couponCount > 0 && ($o['stack_with_coupon'] ?? false) !== true) { return false; }
        // Missing runtime clock means scheduled offers are always inactive.
        if (isset($o['start_unix']) || isset($o['end_unix'])) {
            if (!isset($ctx['now_unix']) || !is_int($ctx['now_unix'])) { return false; }
            foreach (['start_unix','end_unix'] as $field) {
                if (isset($o[$field]) && !is_int($o[$field])) { return false; }
            }
            if (isset($o['start_unix']) && $ctx['now_unix'] < $o['start_unix']) { return false; }
            if (isset($o['end_unix']) && $ctx['now_unix'] >= $o['end_unix']) { return false; }
        }
        if (isset($o['country']) && ($ctx['country'] ?? null) !== $o['country']) { return false; }
        return true;
    }

    private static function compute(array $o, array $lines, int $basis): int {
        return match ($o['type']) {
            'bogo' => self::bogo($o, $lines),
            'bundle' => self::bundle($o, $lines),
            'spend_save' => self::spendSave($o, $lines),
            'tiered' => self::tiered($o, $lines),
            default => 0,
        };
    }

    private static function ids($list): array {
        if (!is_array($list) || !$list || count($list) > 200) {
            throw new InvalidArgumentException('Product selector is empty or invalid');
        }
        foreach ($list as $id) {
            if (!is_int($id) || $id <= 0) { throw new InvalidArgumentException('Invalid selector ID'); }
        }
        return array_values(array_unique($list));
    }

    private static function chosen(array $lines, ?array $ids): array {
        if ($ids === null) { return $lines; }
        return array_values(array_filter($lines, static fn($l) => in_array($l['id'], $ids, true)));
    }

    private static function subtotal(array $lines): int { return self::sumTotals($lines); }
    private static function countUnits(array $lines): int {
        return array_sum(array_column($lines,'qty'));
    }

    private static function unitPrices(array $lines): array {
        $units = [];
        foreach ($lines as $line) {
            // Coupon-allocated line_total may have a 1-minor-unit remainder.
            $each = intdiv($line['total'], $line['qty']);
            $extra = $line['total'] % $line['qty'];
            for ($i=0; $i<$line['qty']; $i++) {
                $units[] = ['minor'=>$each + ($i < $extra ? 1 : 0), 'key'=>$line['key'], 'index'=>$i];
            }
        }
        usort($units, static fn($a,$b) =>
            ($a['minor'] <=> $b['minor']) ?: strcmp($a['key'],$b['key']) ?: ($a['index'] <=> $b['index'])
        );
        return $units;
    }

    private static function bogo(array $o, array $lines): int {
        $buyIds = self::ids($o['buy_product_ids'] ?? null);
        $getIds = self::ids($o['get_product_ids'] ?? null);
        $buyN = $o['buy_qty'] ?? null;
        $getN = $o['get_qty'] ?? null;
        $bps = $o['reward_bps'] ?? 10000;
        if (!is_int($buyN) || $buyN<1 || $buyN>100 || !is_int($getN) || $getN<1 || $getN>100
            || !is_int($bps) || $bps<1 || $bps>10000) {
            throw new InvalidArgumentException('Invalid BOGO ratio or reward');
        }
        $buy = self::chosen($lines,$buyIds);
        $get = self::chosen($lines,$getIds);
        // Exclude free/zero-priced qualifying items to prevent synthetic BOGO.
        $buy = array_values(array_filter($buy, static fn($l)=>$l['total']>0));
        $get = array_values(array_filter($get, static fn($l)=>$l['total']>0));
        $overlap = array_intersect($buyIds,$getIds);
        sort($buyIds);sort($getIds);
        if ($overlap && $buyIds !== $getIds) { return 0; } // mixed overlapping pools fail closed
        $getUnits = self::unitPrices($get);
        if (!$getUnits || !$buy) { return 0; }
        if ($buyIds === $getIds) {
            $rewardQty = intdiv(self::countUnits($buy), $buyN + $getN) * $getN;
        } else {
            $rewardQty = min(intdiv(self::countUnits($buy),$buyN)*$getN,count($getUnits));
        }
        $discount = 0;
        foreach (array_slice($getUnits,0,$rewardQty) as $unit) {
            $discount += intdiv($unit['minor']*$bps,10000);
        }
        return $discount;
    }

    private static function bundle(array $o, array $lines): int {
        $requirements = $o['requires'] ?? null;
        if (!is_array($requirements) || !$requirements || count($requirements)>30) {
            throw new InvalidArgumentException('Invalid bundle');
        }
        $wanted = [];
        foreach ($requirements as $row) {
            if (!is_array($row) || !is_int($row['product_id'] ?? null)
                || !is_int($row['quantity'] ?? null) || $row['product_id']<=0
                || $row['quantity']<1 || $row['quantity']>100
                || isset($wanted[$row['product_id']])) {
                throw new InvalidArgumentException('Duplicate or invalid bundle requirement');
            }
            $wanted[$row['product_id']] = $row['quantity'];
        }
        $unitsById = [];
        foreach ($lines as $line) {
            if (!isset($wanted[$line['id']]) || $line['total']<=0) { continue; }
            $unitsById[$line['id']] = array_merge($unitsById[$line['id']] ?? [], self::unitPrices([$line]));
        }
        $numBundles = self::MAX_UNITS;
        foreach ($wanted as $id=>$qty) {
            $numBundles = min($numBundles, intdiv(count($unitsById[$id] ?? []), $qty));
        }
        if ($numBundles<=0) { return 0; }
        $bundleCost = 0;
        foreach ($wanted as $id=>$qty) {
            // Highest-value eligible units receive the bundle discount.
            usort($unitsById[$id],static fn($a,$b) => $b['minor'] <=> $a['minor'] ?: strcmp($a['key'],$b['key']));
            foreach (array_slice($unitsById[$id],0,$qty*$numBundles) as $unit) {
                $bundleCost += $unit['minor'];
            }
        }
        $hasPrice = isset($o['bundle_price_minor']);$hasBps = isset($o['discount_bps']);
        if ($hasPrice === $hasBps) { throw new InvalidArgumentException('Choose price OR percentage, not both'); }
        if ($hasPrice) {
            $price=$o['bundle_price_minor'];
            if (!is_int($price) || $price<0 || $price>self::MAX_MINOR) { throw new InvalidArgumentException('Invalid bundle price'); }
            return max(0,$bundleCost-$price*$numBundles);
        }
        $bps=$o['discount_bps'];
        if (!is_int($bps) || $bps<1 || $bps>10000) { throw new InvalidArgumentException('Invalid bundle discount'); }
        return intdiv($bundleCost*$bps,10000);
    }

    private static function spendSave(array $o, array $lines): int {
        $ids=isset($o['product_ids'])?self::ids($o['product_ids']):null;
        $base=self::subtotal(self::chosen($lines,$ids));
        $threshold=$o['threshold_minor']??null;
        if (!is_int($threshold) || $threshold<1 || $threshold>self::MAX_MINOR || $base<$threshold) { return 0; }
        return self::amountOrPercent($o,$base);
    }

    private static function tiered(array $o,array $lines): int {
        $ids=isset($o['product_ids'])?self::ids($o['product_ids']):null;
        $chosen=array_values(array_filter(self::chosen($lines,$ids),static fn($l)=>$l['total']>0));
        // Zero-value products must not inflate a quantity-tier threshold.
        $qty=self::countUnits($chosen);$base=self::subtotal($chosen);
        $tiers=$o['tiers']??null;
        if (!is_array($tiers)||!$tiers||count($tiers)>20) { return 0; }
        $selected=null;$min=0;
        foreach($tiers as $tier) {
            if(!is_array($tier)||!is_int($tier['min_qty']??null)||$tier['min_qty']<1) { return 0; }
            if($tier['min_qty']<=$qty && $tier['min_qty']>$min) {
                $selected=$tier;$min=$tier['min_qty'];
            }
        }
        return $selected ? self::amountOrPercent($selected,$base) : 0;
    }

    private static function amountOrPercent(array $rule,int $base): int {
        $hasFixed=isset($rule['discount_minor']);$hasPercent=isset($rule['discount_bps']);
        if ($hasFixed === $hasPercent) { throw new InvalidArgumentException('Choose fixed or percent benefit'); }
        if ($hasFixed) {
            $discount=$rule['discount_minor'];
            if (!is_int($discount)||$discount<0||$discount>self::MAX_MINOR) { throw new InvalidArgumentException('Invalid fixed discount'); }
            return min($discount,$base);
        }
        $bps=$rule['discount_bps'];
        if (!is_int($bps)||$bps<1||$bps>10000) { throw new InvalidArgumentException('Invalid discount percent'); }
        return intdiv($base*$bps,10000);
    }
}
