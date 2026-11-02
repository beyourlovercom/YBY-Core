<?php
/**
 * R9-B pure policy contract. Not registered into Woo checkout or Campaign runtime.
 * All monetary inputs are trusted integer source-USD cents after native coupons.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R9_Policy {
    public const PREMIUM_TIERS = [1000,1500,2000];
    public static function eligibility(int $merchandise_minor, string $shipping_country): array {
        $valid = $merchandise_minor >= 0 && $merchandise_minor <= 1000000000;
        $country = strtoupper(trim($shipping_country));
        $valid_country = preg_match('/^[A-Z]{2}$/D', $country) === 1;
        $slots = !$valid || $merchandise_minor === 0 ? 0 : ($merchandise_minor >= 19900 ? 2 : 1);
        $premium = $slots > 0 && $valid_country && $country === 'US' && $merchandise_minor >= 9900;
        return [
            'valid' => $valid,
            'gift_slots' => $slots,
            'free_gift_country_pending' => !$valid_country,
            'premium_allowed' => $premium,
            'premium_max_slots' => $premium ? 1 : 0,
            'premium_progress_minor' => $valid ? max(0,9900 - $merchandise_minor) : 9900,
            'premium_never_counts_for_threshold' => true,
        ];
    }

    public static function verify_premium(int $product_id, int $claimed_minor, array $verified_manifest, array $entitlement): array {
        if (empty($entitlement['premium_allowed']) || ($entitlement['premium_max_slots'] ?? 0) !== 1) {
            return ['ok'=>false,'reason'=>'not_unlocked'];
        }
        $server_price = $verified_manifest[$product_id] ?? null;
        if ($product_id <= 0 || !is_int($server_price) || !in_array($server_price,self::PREMIUM_TIERS,true)
            || $server_price !== $claimed_minor) {
            return ['ok'=>false,'reason'=>'invalid_server_price_or_sku'];
        }
        return ['ok'=>true,'product_id'=>$product_id,'upgrade_minor'=>$server_price,'replaces_random_slot'=>1];
    }

    public static function influencer_line_eligible(
        bool $verified_influencer_coupon,
        int $product_id,
        int $variation_id,
        bool $is_promo_gift,
        array $black_friday_product_ids
    ): bool {
        if ($is_promo_gift) { return false; }
        if (!$verified_influencer_coupon) { return true; }
        if ($product_id <= 0 || $variation_id < 0) { return false; }
        return !in_array($product_id,$black_friday_product_ids,true)
            && ($variation_id === 0 || !in_array($variation_id,$black_friday_product_ids,true));
    }

    /**
     * Country-filtered, warehouse-approved list is supplied by the caller.
     * Existing assignments remain stable across retries. A new selection is
     * deterministic for a secret server session seed, not based on product URLs.
     * No stock, product, cart or order is changed here.
     */
    public static function random_plan(array $allowed_free_product_ids, int $needed, string $session_seed, array $existing=[]): array {
        if ($needed < 0 || $needed > 2 || strlen($session_seed) < 16) {
            return ['ok'=>false,'reason'=>'invalid_request'];
        }
        $ids = [];
        foreach ($allowed_free_product_ids as $id) {
            if (!is_int($id) || $id < 1) { return ['ok'=>false,'reason'=>'invalid_approved_pool']; }
            $ids[$id] = $id;
        }
        $pool = array_values($ids);
        sort($pool, SORT_NUMERIC);
        if (count($pool) < $needed) { return ['ok'=>false,'reason'=>'insufficient_destination_stocked_choices']; }
        if (count($existing) === $needed && count(array_unique($existing)) === $needed) {
            $stable = true;
            foreach ($existing as $id) {
                if (!is_int($id) || !in_array($id,$pool,true)) { $stable=false; break; }
            }
            if ($stable) { return ['ok'=>true,'ids'=>$existing,'reused'=>true]; }
        }
        $rank = [];
        foreach ($pool as $id) {
            $rank[] = ['id'=>$id,'score'=>hash_hmac('sha256','bf2026-gift:'.$id,$session_seed)];
        }
        usort($rank,static fn($a,$b)=>strcmp($a['score'],$b['score']) ?: ($a['id'] <=> $b['id']));
        return ['ok'=>true,'ids'=>array_map(static fn($v)=>$v['id'],array_slice($rank,0,$needed)),'reused'=>false];
    }
}