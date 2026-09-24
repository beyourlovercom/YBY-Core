<?php
/**
 * Generic WooCommerce runtime adapter for shipping promotion policy.
 *
 * @package Andy_Commerce
 */
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Shipping_Promotion_Runtime {
	public static function boot(): void {
		add_action( 'woocommerce_blocks_loaded', array( self::class, 'register_store_api_endpoint_data' ) );
		add_filter( 'woocommerce_shipping_free_shipping_is_available', array( self::class, 'filter_free_shipping' ), 10, 3 );
		add_filter( 'woocommerce_get_shop_coupon_data', array( self::class, 'filter_virtual_coupon' ), 10, 3 );
		add_filter( 'woocommerce_coupon_get_individual_use', array( self::class, 'force_individual_use' ), 10, 2 );
		add_filter( 'woocommerce_apply_with_individual_use_coupon', array( self::class, 'allow_store_api_individual_use_coupon' ), 10, 4 );
		add_action( 'woocommerce_cart_loaded_from_session', array( self::class, 'normalize_stale_coupons' ), 20, 1 );
	}

	public static function register_store_api_endpoint_data(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) || ! class_exists( 'Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema' ) ) { return; }
		woocommerce_store_api_register_endpoint_data( array(
			'endpoint' => 'cart',
			'namespace' => 'andy-commerce-shipping-promotion',
			'data_callback' => array( self::class, 'get_cart_presentation_state' ),
			'schema_callback' => array( self::class, 'get_cart_presentation_schema' ),
		) );
	}

	public static function get_cart_presentation_schema(): array {
		return array(
			'active' => array( 'description' => 'Whether the persisted Commerce promotion is active.', 'type' => 'boolean' ),
			'mode' => array( 'description' => 'Configured promotion mode.', 'type' => 'string' ),
			'policy_code' => array( 'description' => 'Configured free-shipping code.', 'type' => 'string' ),
			'zone_id' => array( 'description' => 'Matching WooCommerce shipping zone ID.', 'type' => 'integer' ),
			'threshold' => array( 'description' => 'Effective promotion threshold.', 'type' => 'number' ),
			'subtotal' => array( 'description' => 'Displayed pre-discount merchandise subtotal.', 'type' => 'number' ),
			'remaining' => array( 'description' => 'Amount remaining before the threshold.', 'type' => 'number' ),
			'eligible' => array( 'description' => 'Whether the displayed subtotal reaches the threshold.', 'type' => 'boolean' ),
			'unlocked' => array( 'description' => 'Whether the promotion currently unlocks free shipping.', 'type' => 'boolean' ),
			'applied_code' => array( 'description' => 'First applied coupon code, if any.', 'type' => 'string' ),
			'has_code' => array( 'description' => 'Whether a coupon is applied.', 'type' => 'boolean' ),
			'policy_code_applied' => array( 'description' => 'Whether the policy code is the first applied coupon.', 'type' => 'boolean' ),
			'state' => array( 'description' => 'Promotion state.', 'type' => 'string' ),
		);
	}

	public static function get_cart_presentation_state(): array {
		$empty = array(
			'active' => false, 'mode' => '', 'policy_code' => '', 'zone_id' => 0,
			'threshold' => 0.0, 'subtotal' => 0.0, 'remaining' => 0.0,
			'eligible' => false, 'unlocked' => false, 'applied_code' => '',
			'has_code' => false, 'policy_code_applied' => false, 'state' => 'inactive',
		);
		$settings = Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings();
		if ( null === $settings || ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) { return $empty; }
		$cart = WC()->cart;
		if ( ! method_exists( $cart, 'get_displayed_subtotal' ) ) { return $empty; }
		$packages = array();
		if ( WC()->shipping() && method_exists( WC()->shipping(), 'get_packages' ) ) {
			$packages = (array) WC()->shipping()->get_packages();
		}
		if ( ! $packages && method_exists( $cart, 'get_shipping_packages' ) ) { $packages = (array) $cart->get_shipping_packages(); }
		$package = $packages[0] ?? null;
		if ( ! is_array( $package ) || ! class_exists( 'WC_Shipping_Zones' ) ) { return $empty; }
		$zone = WC_Shipping_Zones::get_zone_matching_package( $package );
		$zone_id = is_object( $zone ) && method_exists( $zone, 'get_id' ) ? (int) $zone->get_id() : 0;
		$threshold = Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold( $zone_id );
		if ( null === $threshold ) { return $empty; }
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		$subtotal = round( (float) $cart->get_displayed_subtotal(), (int) $decimals );
		$threshold = round( (float) $threshold, (int) $decimals );
		$eligible = $subtotal >= $threshold;
		$applied = method_exists( $cart, 'get_applied_coupons' ) ? array_values( (array) $cart->get_applied_coupons() ) : array();
		$applied_code = isset( $applied[0] ) ? (string) $applied[0] : '';
		$policy_code = Andy_Commerce_Shipping_Promotion_Policy::normalize_code( (string) $settings['free_shipping_code'] );
		$policy_code_applied = '' !== $applied_code && Andy_Commerce_Shipping_Promotion_Policy::normalize_code( $applied_code ) === $policy_code;
		$mode = (string) $settings['mode'];
		$unlocked = $eligible && ( 'require_code' !== $mode || $policy_code_applied );
		if ( ! $eligible ) { $state = ( 'require_code' === $mode && $policy_code_applied ) ? 'require_code_applied' : 'below_threshold'; }
		elseif ( 'require_code' !== $mode ) { $state = '' !== $applied_code ? 'automatic_code' : 'automatic_unlocked'; }
		elseif ( $policy_code_applied ) { $state = 'require_code_unlocked'; }
		elseif ( '' !== $applied_code ) { $state = 'require_code_other'; }
		else { $state = 'require_code_available'; }
		return array(
			'active' => true, 'mode' => $mode, 'policy_code' => $policy_code, 'zone_id' => $zone_id,
			'threshold' => $threshold, 'subtotal' => $subtotal, 'remaining' => max( 0.0, round( $threshold - $subtotal, (int) $decimals ) ),
			'eligible' => $eligible, 'unlocked' => $unlocked, 'applied_code' => $applied_code,
			'has_code' => '' !== $applied_code, 'policy_code_applied' => $policy_code_applied, 'state' => $state,
		);
	}

	public static function filter_free_shipping($is_available, $package, $shipping_method) {
		if ( ! Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() ) { return $is_available; }
		$cart = function_exists( 'WC' ) ? WC()->cart : null;
		if ( ! $cart || ! class_exists( 'WC_Shipping_Zones' ) ) { return $is_available; }
		$zone = WC_Shipping_Zones::get_zone_matching_package( (array) $package );
		$zone_id = is_object( $zone ) && method_exists( $zone, 'get_id' ) ? (int) $zone->get_id() : 0;
		$threshold = Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold( $zone_id );
		if ( null === $threshold || ! method_exists( $cart, 'get_displayed_subtotal' ) ) { return $is_available; }
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		$eligible = round( (float) $cart->get_displayed_subtotal(), (int) $decimals );
		if ( $eligible < $threshold ) { return false; }
		$settings = Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings();
		if ( 'require_code' !== ( $settings['mode'] ?? '' ) ) { return true; }
		$policy_code = Andy_Commerce_Shipping_Promotion_Policy::normalize_code( (string) $settings['free_shipping_code'] );
		foreach ( (array) $cart->get_applied_coupons() as $code ) {
			if ( Andy_Commerce_Shipping_Promotion_Policy::normalize_code( (string) $code ) === $policy_code ) { return true; }
		}
		return false;
	}

	public static function filter_virtual_coupon($coupon_data, $code, $coupon) {
		if ( false !== $coupon_data || ! Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() || ! self::is_customer_context() ) { return $coupon_data; }
		$settings = Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings();
		$policy_code = Andy_Commerce_Shipping_Promotion_Policy::normalize_code( (string) $settings['free_shipping_code'] );
		if ( Andy_Commerce_Shipping_Promotion_Policy::normalize_code( (string) $code ) !== $policy_code ) { return $coupon_data; }
		return array(
			'code' => $policy_code,
			'discount_type' => 'fixed_cart',
			'amount' => 0,
			'virtual' => true,
			'free_shipping' => false,
			'individual_use' => true,
		);
	}

	public static function force_individual_use($individual_use, $coupon) {
		return Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() && self::is_customer_context() ? true : $individual_use;
	}

	public static function allow_store_api_individual_use_coupon($allow, $coupon, $existing_individual_use_coupons, $cart) {
		return Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() && self::is_customer_context() ? true : $allow;
	}

	public static function normalize_stale_coupons($cart): void {
		if ( ! Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active() || ! self::is_customer_context() || ! is_object( $cart ) || ! method_exists( $cart, 'get_applied_coupons' ) ) { return; }
		$applied = array_values( (array) $cart->get_applied_coupons() );
		foreach ( array_slice( $applied, 1 ) as $code ) { $cart->remove_coupon( $code ); }
	}

	private static function is_customer_context(): bool {
		return ! function_exists( 'is_admin' ) || ! is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() );
	}
}
