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

    /** Never expose unverified customer cart totals or suggest shipping was awarded. */
    public static function data(): array {
        $basis = self::enabled() && class_exists('Andy_Commerce_Shipping_Promotion_Policy')
            ? Andy_Commerce_Shipping_Promotion_Policy::ELIGIBILITY_BASIS : '';
        $state = Andy_Commerce_M5_Progress_Projection::project([
            'effective_policy_basis' => $basis,
            'woocommerce_rate_confirmed' => false,
            'source' => 'not_yet_wired',
        ]);
        return [
            'schema_version' => 1,
            'state' => 'hidden',
            'show_progress' => false,
            'gift_enabled' => false,
            'claim_free_shipping' => false,
            'reason' => (string)($state['reason'] ?? 'read_only_bridge_disabled'),
            'authority' => 'woocommerce',
        ];
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
        ];
    }
}
