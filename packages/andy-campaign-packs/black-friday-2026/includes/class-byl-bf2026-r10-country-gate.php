<?php
/**
 * US-only clearance protection. Pure checkout decision; no hooks registered.
 * The existing WWW landing claims sale pricing is US shipping only.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Country_Gate {
    public static function inspect($cart, string $shipping_country, array $ledger): array {
        if (($ledger['state'] ?? '') !== 'active') {
            return ['ok'=>true,'reason'=>'campaign_not_active','affected_ids'=>[]];
        }
        if (!is_object($cart) || !method_exists($cart,'get_cart')) {
            return ['ok'=>false,'reason'=>'missing_cart','affected_ids'=>[]];
        }
        $affected=[];
        foreach ($cart->get_cart() as $line) {
            if (!is_array($line)) {
                return ['ok'=>false,'reason'=>'invalid_cart_item','affected_ids'=>[]];
            }
            // Worldwide free gifts have their own destination/warehouse checks.
            if (($line['andy_campaign_pack_id']??'')==='byl-black-friday-2026'
                && !empty($line['_byl_bf2026_gift'])) {continue;}
            foreach ([(int)($line['product_id']??0),(int)($line['variation_id']??0)] as $id) {
                if ($id>0 && isset($ledger['entries'][$id])
                    && ($ledger['entries'][$id]['phase']??'')==='applied') {
                    $affected[$id]=$id;
                }
            }
        }
        if (!$affected) {
            return ['ok'=>true,'reason'=>'no_campaign_clearance_items','affected_ids'=>[]];
        }
        $country=strtoupper(trim($shipping_country));
        if ($country!=='US') {
            return ['ok'=>false,
                'reason'=>$country===''?'shipping_country_required':'clearance_us_shipping_only',
                'affected_ids'=>array_values($affected)];
        }
        return ['ok'=>true,'reason'=>'us_shipping_accepted',
            'affected_ids'=>array_values($affected)];
    }
}
