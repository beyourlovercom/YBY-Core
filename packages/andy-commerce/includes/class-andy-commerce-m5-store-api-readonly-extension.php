<?php
/**
 * Andy Commerce M5 — Dev-only Woo Store API read-only extension.
 * Data is deliberately HIDDEN while BYL49 uses legacy pre-discount basis.
 * No coupons, shipping filters, update callbacks, cart/session mutation.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M5_Store_API_Readonly_Extension {
    public const NAMESPACE_ID = 'andy_commerce_marketing_v1';

    public static function enabled(): bool {
        return defined('ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED')
            && ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED === true
            && defined('WP_ENVIRONMENT_TYPE')
            && in_array(WP_ENVIRONMENT_TYPE, ['development','staging'], true)
            && parse_url(home_url(), PHP_URL_HOST) === 'dev.beyourlover.com';
    }

    public static function boot(): void {
        if (!self::enabled()) { return; }
        if (did_action('woocommerce_blocks_loaded')) {
            self::register();
        } else {
            add_action('woocommerce_blocks_loaded', [self::class, 'register'], 20);
        }
    }

    public static function register(): void {
        if (!self::enabled() || !function_exists('woocommerce_store_api_register_endpoint_data')) { return; }
        woocommerce_store_api_register_endpoint_data([
            'endpoint' => 'cart',
            'namespace' => self::NAMESPACE_ID,
            'data_callback' => [self::class, 'data'],
            'schema_callback' => [self::class, 'schema'],
            'schema_type' => ARRAY_A,
        ]);
    }

    /**
     * Read only the *server accepted* Woo cart and chosen shipping method.
     * No calculate_shipping/cart mutations, no gifts, no synthetic free award.
     */
    public static function data(): array {
        $hidden = [
            'schema_version'=>1,'state'=>'hidden','show_progress'=>false,
            'gift_enabled'=>false,'claim_free_shipping'=>false,
            'reason'=>'v2_canary_not_authoritative','authority'=>'woocommerce',
            'remaining_minor'=>null,'qualified_minor'=>null,
            'threshold_minor'=>null,'percent'=>null,
        ];
        if (!self::enabled() || !class_exists('Andy_Commerce_M4_V2_Dev_Canary')
            || !Andy_Commerce_M4_V2_Dev_Canary::enabled()
            || !class_exists('Andy_Commerce_Shipping_Promotion_Policy')
            || !Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active()
            || has_filter('woocommerce_shipping_free_shipping_is_available',
                [Andy_Commerce_M4_V2_Dev_Canary::class,'filter_free_shipping'])!==10
            || !function_exists('WC') || !WC() || !WC()->cart
            || !method_exists(WC()->cart,'get_cart')
            || !WC()->shipping() || !method_exists(WC()->shipping(),'get_packages')
            || !class_exists('WC_Shipping_Zones')) {
            return $hidden;
        }
        $packages=(array)WC()->shipping()->get_packages();
        if (count($packages)!==1 || !isset($packages[0])
            || !is_array($packages[0]) || !isset($packages[0]['rates'])
            || !is_array($packages[0]['rates'])) return $hidden;
        $package=$packages[0];
        $zone=WC_Shipping_Zones::get_zone_matching_package($package);
        if (!is_object($zone) || !method_exists($zone,'get_id')) return $hidden;
        $threshold=Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold((int)$zone->get_id());
        $unit=function_exists('wc_get_price_decimals')?(int)wc_get_price_decimals():2;
        if ($threshold===null || $unit<0 || $unit>4 || (float)$threshold<=0) return $hidden;
        $cartLines=(array)WC()->cart->get_cart();
        $minor=Andy_Commerce_M4_V2_Dev_Canary::amount_minor($cartLines,$unit);
        if ($minor===null) return $hidden;
        $chosen=WC()->session && method_exists(WC()->session,'get')
            ? WC()->session->get('chosen_shipping_methods',[]) : null;
        if (!is_array($chosen) || count($chosen)!==1 || !isset($chosen[0])
            || !is_string($chosen[0]) || !isset($package['rates'][$chosen[0]])) return $hidden;
        $rate=$package['rates'][$chosen[0]];
        if (!is_object($rate) || !method_exists($rate,'get_id')
            || (string)$rate->get_id()!==$chosen[0]) return $hidden;
        $projection=Andy_Commerce_M5_Progress_Projection::project([
            'effective_policy_basis'=>Andy_Commerce_M4_V2_Dev_Canary::TARGET_BASIS,
            'source'=>'woocommerce_selected_shipping_rate',
            'woocommerce_rate_confirmed'=>true,
            'selected_rate_id'=>$chosen[0],
            'discounted_merchandise_minor'=>$minor,
            'threshold_minor'=>(int)round((float)$threshold*(10**$unit)),
        ]);
        if (empty($projection['show_progress'])) return array_replace($hidden,['reason'=>(string)($projection['reason']??'invalid_woo_source')]);
        return $projection;
    }

    public static function schema(): array {
        $field = static function(string $type,string $description):array {
            return ['description'=>$description,'type'=>$type,'readonly'=>true];
        };
        return [
            'schema_version'=>$field('integer','Read-only Marketing schema version'),
            'state'=>$field('string','Hidden until migration/real shipping truth'),
            'show_progress'=>$field('boolean','False while legacy BYL49 active'),
            'gift_enabled'=>$field('boolean','Gifts remain OFF'),
            'claim_free_shipping'=>$field('boolean','Never fabricates Woo shipping'),
            'reason'=>$field('string','Fail-closed reason'),
            'authority'=>$field('string','WooCommerce is source of truth'),
            'remaining_minor'=>$field('integer','Minor currency units remaining'),
            'qualified_minor'=>$field('integer','Server Woo discounted merchandise minor units'),
            'threshold_minor'=>$field('integer','Matching shipping zone threshold in minor units'),
            'percent'=>$field('integer','Read-only progress percent'),
        ];
    }
}
