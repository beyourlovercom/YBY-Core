<?php
/**
 * Generic checkout and shipping promotion policy.
 *
 * @package Andy_Commerce
 */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Shipping_Promotion_Policy {
	public const OPTION_NAME = 'andy_commerce_shipping_promotion_settings_v1';
	public const SETTINGS_GROUP = 'andy_commerce_checkout_shipping';
	public const SCHEMA_VERSION = 1;
	public const ELIGIBILITY_BASIS = 'pre_discount_merchandise_subtotal';

	public static function boot(): void {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_init', array( self::class, 'maybe_migrate_legacy_settings' ), 5 );
	}

	public static function preset(): array { return Andy_Commerce_Site_Preset::current(); }

	public static function defaults(bool $infer_woo_thresholds = true): array {
		$preset = self::preset();
		$settings = array(
			'schema_version' => self::SCHEMA_VERSION,
			'mode' => 'automatic',
			'free_shipping_code' => (string) $preset['free_shipping_code'],
			'global_threshold' => (float) $preset['global_threshold'],
			'eligibility_basis' => self::ELIGIBILITY_BASIS,
			'zone_overrides' => array(),
		);
		foreach ( self::zone_catalog() as $zone_id => $zone ) {
			$woo_threshold = $zone['woo_min_amount'];
			$settings['zone_overrides'][(string) $zone_id] = array(
				'mode' => $infer_woo_thresholds && null !== $woo_threshold ? 'override' : 'global',
				'threshold' => null !== $woo_threshold ? $woo_threshold : $settings['global_threshold'],
			);
		}
		return $settings;
	}

	public static function maybe_migrate_legacy_settings(): bool {
		if ( is_array( get_option( self::OPTION_NAME, null ) ) ) { return false; }
		$preset = self::preset();
		$legacy_option = (string) ( $preset['legacy_option_name'] ?? '' );
		if ( '' === $legacy_option ) { return false; }
		$legacy = get_option( $legacy_option, null );
		if ( ! is_array( $legacy ) || ! self::legacy_settings_valid( $legacy ) ) { return false; }
		return update_option( self::OPTION_NAME, self::sanitize_settings( $legacy ), false );
	}

	private static function legacy_settings_valid(array $saved): bool {
		if ( (int) ( $saved['schema_version'] ?? 0 ) !== self::SCHEMA_VERSION ) { return false; }
		if ( ! in_array( $saved['mode'] ?? null, array( 'automatic', 'require_code' ), true ) ) { return false; }
		if ( ! self::is_valid_code( $saved['free_shipping_code'] ?? null ) ) { return false; }
		if ( ! self::is_valid_number( $saved['global_threshold'] ?? null ) || (float) $saved['global_threshold'] < 0 ) { return false; }
		if ( ( $saved['eligibility_basis'] ?? null ) !== self::ELIGIBILITY_BASIS ) { return false; }
		if ( ! isset( $saved['zone_overrides'] ) || ! is_array( $saved['zone_overrides'] ) ) { return false; }
		foreach ( $saved['zone_overrides'] as $zone_id => $config ) {
			if ( ! is_string( $zone_id ) && ! is_int( $zone_id ) ) { return false; }
			if ( ! preg_match( '/^\\d+$/', (string) $zone_id ) || ! is_array( $config ) ) { return false; }
			if ( ! in_array( $config['mode'] ?? null, array( 'global', 'override' ), true ) ) { return false; }
			if ( ! self::is_valid_number( $config['threshold'] ?? null ) || (float) $config['threshold'] < 0 ) { return false; }
		}
		return true;
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, null );
		$settings = self::defaults( true );
		if ( is_array( $saved ) ) {
			$settings = self::sanitize_settings( $saved );
		}
		return $settings;
	}

	public static function has_persisted_settings(): bool {
		$saved = get_option( self::OPTION_NAME, null );
		return is_array( $saved ) && self::legacy_settings_valid( $saved );
	}

	public static function is_runtime_active(): bool { return self::has_persisted_settings(); }

	public static function get_persisted_settings(): ?array {
		if ( ! self::has_persisted_settings() ) { return null; }
		$settings = get_option( self::OPTION_NAME, null );
		$settings['free_shipping_code'] = self::normalize_code( $settings['free_shipping_code'] );
		return $settings;
	}

	public static function normalize_code(string $code): string {
		return function_exists( 'wc_format_coupon_code' ) ? wc_format_coupon_code( $code ) : strtoupper( $code );
	}

	private static function is_valid_code($code): bool {
		return is_string( $code ) && '' !== $code && 1 === preg_match( '/^[A-Z0-9_-]{1,50}$/', $code );
	}

	private static function is_valid_number($value): bool {
		return ! is_bool( $value ) && is_numeric( $value ) && is_finite( (float) $value ) && (float) $value <= 999999.99;
	}

	public static function get_zone_config(int $zone_id): array {
		$settings = self::get_settings();
		$key = (string) absint( $zone_id );
		$config = $settings['zone_overrides'][$key] ?? array( 'mode' => 'global', 'threshold' => $settings['global_threshold'] );
		$catalog = self::zone_catalog();
		return array(
			'zone_id' => absint( $zone_id ),
			'name' => $catalog[$key]['name'] ?? ( 0 === $zone_id ? 'Rest of world' : 'Zone ' . absint( $zone_id ) ),
			'mode' => $config['mode'],
			'threshold' => (float) $config['threshold'],
			'effective_threshold' => 'override' === $config['mode'] ? (float) $config['threshold'] : (float) $settings['global_threshold'],
			'eligibility_basis' => self::ELIGIBILITY_BASIS,
		);
	}

	public static function get_persisted_zone_effective_threshold(int $zone_id): ?float {
		$settings = self::get_persisted_settings();
		if ( null === $settings ) { return null; }
		$config = $settings['zone_overrides'][(string) absint( $zone_id )] ?? null;
		return is_array( $config ) && 'override' === ( $config['mode'] ?? '' )
			? (float) $config['threshold']
			: (float) $settings['global_threshold'];
	}

	public static function sanitize_settings($input): array {
		$input = is_array( $input ) ? $input : array();
		$current = self::defaults( true );
		$clean = array(
			'schema_version' => self::SCHEMA_VERSION,
			'mode' => self::valid_mode( $input['mode'] ?? $current['mode'] ),
			'free_shipping_code' => self::clean_code( $input['free_shipping_code'] ?? $current['free_shipping_code'] ),
			'global_threshold' => self::clean_threshold( $input['global_threshold'] ?? $current['global_threshold'], $current['global_threshold'] ),
			'eligibility_basis' => self::ELIGIBILITY_BASIS,
			'zone_overrides' => array(),
		);
		$posted_zones = isset( $input['zone_overrides'] ) && is_array( $input['zone_overrides'] ) ? $input['zone_overrides'] : array();
		foreach ( self::zone_catalog() as $zone_id => $zone ) {
			$key = (string) $zone_id;
			$posted = isset( $posted_zones[$key] ) && is_array( $posted_zones[$key] ) ? $posted_zones[$key] : ( $current['zone_overrides'][$key] ?? array() );
			$fallback = $current['zone_overrides'][$key]['threshold'] ?? ( $zone['woo_min_amount'] ?? $clean['global_threshold'] );
			$clean['zone_overrides'][$key] = array(
				'mode' => 'override' === ( $posted['mode'] ?? '' ) ? 'override' : 'global',
				'threshold' => self::clean_threshold( $posted['threshold'] ?? $fallback, (float) $fallback ),
			);
		}
		return $clean;
	}

	public static function register_settings(): void {
		register_setting( self::SETTINGS_GROUP, self::OPTION_NAME, array(
			'type' => 'array',
			'sanitize_callback' => array( self::class, 'sanitize_settings' ),
		) );
		add_filter( 'option_page_capability_' . self::SETTINGS_GROUP, array( self::class, 'option_page_capability' ) );
	}

	public static function option_page_capability(string $capability): string { return 'manage_woocommerce'; }

	private static function valid_mode($mode): string { return 'require_code' === $mode ? 'require_code' : 'automatic'; }

	private static function clean_code($code): string {
		$preset = self::preset();
		$code = strtoupper( sanitize_text_field( (string) $code ) );
		return preg_match( '/^[A-Z0-9_-]{1,50}$/', $code ) ? $code : (string) $preset['free_shipping_code'];
	}

	private static function clean_threshold($threshold, float $fallback): float {
		if ( ! is_numeric( $threshold ) || ! is_finite( (float) $threshold ) ) { return $fallback; }
		return round( max( 0.0, min( 999999.99, (float) $threshold ) ), 2 );
	}

	public static function zone_catalog(): array {
		$catalog = array();
		if ( class_exists( 'WC_Shipping_Zones' ) && method_exists( 'WC_Shipping_Zones', 'get_zones' ) ) {
			foreach ( (array) WC_Shipping_Zones::get_zones() as $zone ) {
				if ( ! is_array( $zone ) || ! isset( $zone['zone_id'] ) ) { continue; }
				$zone_id = absint( $zone['zone_id'] );
				$catalog[(string) $zone_id] = array(
					'name' => (string) ( $zone['zone_name'] ?? 'Zone ' . $zone_id ),
					'woo_min_amount' => self::free_shipping_min_amount( $zone['shipping_methods'] ?? array() ),
				);
			}
		}
		$rest = array( 'name' => 'Rest of world', 'woo_min_amount' => null );
		if ( class_exists( 'WC_Shipping_Zones' ) && method_exists( 'WC_Shipping_Zones', 'get_zone' ) ) {
			$zone = WC_Shipping_Zones::get_zone( 0 );
			if ( is_object( $zone ) && method_exists( $zone, 'get_shipping_methods' ) ) {
				$rest['woo_min_amount'] = self::free_shipping_min_amount( $zone->get_shipping_methods() );
			}
		}
		$catalog['0'] = $rest;
		ksort( $catalog, SORT_NUMERIC );
		return $catalog;
	}

	private static function free_shipping_min_amount($methods): ?float {
		foreach ( (array) $methods as $method ) {
			if ( ! is_object( $method ) ) { continue; }
			$method_id = method_exists( $method, 'get_method_id' ) ? $method->get_method_id() : ( $method->id ?? ( $method->method_id ?? '' ) );
			if ( 'free_shipping' !== $method_id ) { continue; }
			$instance_id = method_exists( $method, 'get_instance_id' ) ? $method->get_instance_id() : absint( $method->instance_id ?? 0 );
			$settings = method_exists( $method, 'get_instance_option' ) ? array( 'min_amount' => $method->get_instance_option( 'min_amount', '' ) ) : array();
			if ( '' === $settings['min_amount'] && function_exists( 'get_option' ) ) {
				$settings = get_option( 'woocommerce_free_shipping_' . $instance_id . '_settings', array() );
			}
			if ( is_array( $settings ) && isset( $settings['min_amount'] ) && is_numeric( $settings['min_amount'] ) ) {
				return self::clean_threshold( $settings['min_amount'], 0.0 );
			}
		}
		return null;
	}
}
