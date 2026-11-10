<?php
/**
 * US-only clearance protection. Pure checkout decision; no hooks registered.
 * The existing WWW landing claims sale pricing is US shipping only.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Country_Gate {
    private static function checkout_error($cart, string $country, $errors): void {
        if (!is_object($errors) || !method_exists($errors,'add')) {return;}
        $result=self::inspect($cart,$country,BYL_BF2026_R10_Sale_Manager::ledger());
        if (!$result['ok']) {
            $errors->add('byl_bf2026_sale_us_only',
                'Black Friday clearance prices are available for U.S. shipping addresses only. '
                .'Please remove these clearance items to continue with an international order.');
        }
    }

    public static function validate_classic($posted, $errors): void {
        if (!function_exists('WC') || !WC() || !WC()->cart) {return;}
        $country=is_array($posted) && is_string($posted['shipping_country']??null)
            ? $posted['shipping_country'] : '';
        self::checkout_error(WC()->cart,$country,$errors);
    }

    public static function validate_store_api($order, $errors): void {
        if (!function_exists('WC') || !WC() || !WC()->cart) {return;}
        $country=is_object($order) && method_exists($order,'get_shipping_country')
            ? (string)$order->get_shipping_country() : '';
        self::checkout_error(WC()->cart,$country,$errors);
    }

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
