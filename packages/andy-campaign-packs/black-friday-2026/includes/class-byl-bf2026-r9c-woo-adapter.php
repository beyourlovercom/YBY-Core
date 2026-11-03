<?php
/**
 * R9-C1 native Woo decision adapter; intentionally UNREGISTERED.
 * Never boots itself or mutates WC cart/order/coupons.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R9C_Woo_Adapter {
    private const PACK = 'byl-black-friday-2026';

    private static function gift_line(array $line): bool {
        return ($line['andy_campaign_pack_id'] ?? null) === self::PACK
            && ($line['_byl_bf2026_gift'] ?? null) === true;
    }

    /** Call only after native Woo coupon totals have been calculated. */
    public static function quote($cart, string $shipping_country): array {
        if (!class_exists('WC_Cart') || !$cart instanceof WC_Cart
            || !class_exists('BYL_BF2026_R9_Policy')
            || !function_exists('get_woocommerce_currency')
            || get_woocommerce_currency() !== 'USD') {
            return ['ok'=>false, 'reason'=>'source_usd_unavailable'];
        }
        $basis = 0;
        $ordinary = 0;
        foreach ($cart->get_cart() as $line) {
            if (!is_array($line)) { return ['ok'=>false,'reason'=>'malformed_cart_line']; }
            if (self::gift_line($line)) { continue; }
            $amount = $line['line_total'] ?? null;
            if (!is_numeric($amount) || (float)$amount < 0 || !is_finite((float)$amount)
                || (float)$amount > 10000000) {
                return ['ok'=>false,'reason'=>'invalid_post_coupon_amount'];
            }
            $basis += (int)round(((float)$amount)*100);
            ++$ordinary;
        }
        if ($basis > 1000000000) { return ['ok'=>false,'reason'=>'basis_overflow']; }
        $entitlement = BYL_BF2026_R9_Policy::eligibility($ordinary ? $basis : 0, $shipping_country);
        return ['ok'=>true, 'basis_minor'=>$basis, 'entitlement'=>$entitlement];
    }

    /**
     * Prepare selection only: server manifest is NOT shopper input.
     * Callers must verify WP nonce/session, then recheck and apply the plan
     * in an explicitly authorized cart transaction.
     */
    public static function plan_premium($cart, string $shipping_country, int $product_id,
        array $reviewed_prices, array $reviewed_approvals): array {
        $quote = self::quote($cart, $shipping_country);
        if (!$quote['ok']) { return $quote; }
        $price = $reviewed_prices[$product_id] ?? null;
        $decision = BYL_BF2026_R9_Policy::verify_premium(
            $product_id, is_int($price) ? $price : -1,
            $reviewed_prices, $quote['entitlement']
        );
        if (!$decision['ok']) { return $decision; }
        if (!class_exists('BYL_BF2026_R8_Readiness') || !function_exists('wc_get_product')) {
            return ['ok'=>false,'reason'=>'approval_engine_unavailable'];
        }
        $approval = BYL_BF2026_R8_Readiness::inspect(
            [$product_id=>['upgrade_minor'=>$price]], $reviewed_approvals
        );
        if (($approval['rows'][0]['status'] ?? '') !== 'APPROVED'
            || !in_array('US',$reviewed_approvals[$product_id]['allowed_countries'] ?? [],true)) {
            return ['ok'=>false,'reason'=>'sku_warehouse_or_country_unapproved'];
        }
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('simple') || !$product->is_purchasable()
            || !$product->is_in_stock() || $product->backorders_allowed()) {
            return ['ok'=>false,'reason'=>'product_not_purchasable'];
        }
        $used = 0;
        $old_premium_key = null;
        $old_free_key = null;
        $seen = [];
        foreach ($cart->get_cart() as $key=>$line) {
            if (!self::gift_line($line)) { continue; }
            $id = (int)($line['product_id'] ?? 0);
            if ($id <= 0 || (int)($line['quantity'] ?? 0) !== 1 || isset($seen[$id])
                || !isset($reviewed_prices[$id])) {
                return ['ok'=>false,'reason'=>'invalid_existing_gift'];
            }
            $seen[$id] = true;
            ++$used;
            if ($reviewed_prices[$id] > 0) {
                if ($old_premium_key !== null) { return ['ok'=>false,'reason'=>'multiple_existing_upgrades']; }
                $old_premium_key = $key;
            } elseif ($old_free_key === null) {
                $old_free_key = $key;
            }
        }
        $slots = $quote['entitlement']['gift_slots'];
        if ($used > $slots) { return ['ok'=>false,'reason'=>'gift_slot_overflow']; }
        if ($old_premium_key !== null) {
            $current = $cart->get_cart()[$old_premium_key] ?? [];
            if ((int)($current['product_id'] ?? 0) === $product_id) {
                return ['ok'=>true,'action'=>'already_selected',
                    'product_id'=>$product_id,'upgrade_minor'=>$price];
            }
        }
        $remove = $old_premium_key ?? ($used >= $slots ? $old_free_key : null);
        if ($used >= $slots && $remove === null) {
            return ['ok'=>false,'reason'=>'cannot_replace_gift'];
        }
        return [
            'ok'=>true,
            'action'=>$remove === null ? 'add' : 'replace',
            'remove_key'=>$remove,
            'product_id'=>$product_id,
            'upgrade_minor'=>$price,
            'cart_item_data'=>[
                'andy_campaign_pack_id'=>self::PACK,
                '_byl_bf2026_gift'=>true,
                '_byl_bf2026_paid_slot'=>true
            ],
            'basis_minor'=>$quote['basis_minor']
        ];
    }

    /**
     * Read-only checkout preflight for future Classic/Store API integration.
     * Reject stale addresses, missing promised random slots, unverified
     * warehouse approval and more than one paid replacement.
     */
    public static function audit_fulfillment($cart, string $shipping_country,
        array $reviewed_prices, array $reviewed_approvals): array {
        $quote = self::quote($cart,$shipping_country);
        if (!$quote['ok']) { return $quote; }
        $slots = $quote['entitlement']['gift_slots'];
        $count = 0; $paid = 0; $seen = [];
        foreach ($cart->get_cart() as $line) {
            if (!self::gift_line($line)) { continue; }
            $id = (int)($line['product_id'] ?? 0);
            if ($id <= 0 || isset($seen[$id]) || (int)($line['quantity'] ?? 0) !== 1
                || !isset($reviewed_prices[$id]) || !is_int($reviewed_prices[$id])) {
                return ['ok'=>false,'reason'=>'invalid_gift_cart_state'];
            }
            $expected_minor = $reviewed_prices[$id];
            $charged = $line['line_total'] ?? null;
            if (!in_array($expected_minor,[0,1000,1500,2000],true)
                || !is_numeric($charged) || !is_finite((float)$charged)
                || (int)round((float)$charged*100) !== $expected_minor) {
                return ['ok'=>false,'reason'=>'gift_charged_price_inconsistent'];
            }
            $seen[$id] = true; ++$count;
            if ($expected_minor > 0) { ++$paid; }
            if (!class_exists('BYL_BF2026_R8_Readiness')) {
                return ['ok'=>false,'reason'=>'approval_engine_unavailable'];
            }
            $inspection=BYL_BF2026_R8_Readiness::inspect(
                [$id=>['upgrade_minor'=>$reviewed_prices[$id]]],$reviewed_approvals
            );
            if (($inspection['rows'][0]['status']??'') !== 'APPROVED'
                || !in_array(strtoupper($shipping_country),
                    $reviewed_approvals[$id]['allowed_countries']??[],true)) {
                return ['ok'=>false,'reason'=>'gift_destination_or_stock_unverified'];
            }
        }
        if ($count > $slots || $paid > 1) {
            return ['ok'=>false,'reason'=>'gift_slots_exceeded'];
        }
        if ($paid > 0 && !$quote['entitlement']['premium_allowed']) {
            return ['ok'=>false,'reason'=>'premium_lost_eligibility'];
        }
        // Mandatory global gift promise: do not authorize checkout while
        // the server has not allocated every destination-shippable slot.
        if ($count !== $slots) {
            return ['ok'=>false,'reason'=>'promised_random_gift_not_allocated'];
        }
        return ['ok'=>true,'slots'=>$slots,'paid_slots'=>$paid,
            'free_slots'=>$count-$paid,'basis_minor'=>$quote['basis_minor']];
    }

    /** For Woo WC_Discounts items. No hook registration in this class. */
    public static function influencer_discount_items(array $items, $coupon,
        array $verified_influencer_ids, array $black_friday_ids): array {
        if (!is_object($coupon) || !method_exists($coupon,'get_id')
            || !in_array((int)$coupon->get_id(), $verified_influencer_ids, true)) {
            return $items;
        }
        return array_values(array_filter($items, static function ($item) use ($black_friday_ids) {
            $line = is_object($item) ? ($item->object ?? null) : null;
            if (!is_array($line)) { return false; }
            return BYL_BF2026_R9_Policy::influencer_line_eligible(
                true, (int)($line['product_id'] ?? 0),
                (int)($line['variation_id'] ?? 0),
                self::gift_line($line), $black_friday_ids
            );
        }));
    }
}
