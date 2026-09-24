<?php
/**
 * Site-specific Commerce preset provider.
 *
 * @package Andy_Commerce
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Site_Preset {
	public static function current(): array {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$host = is_string( $host ) ? strtolower( $host ) : '';
		$preset = array(
			'id' => 'generic',
			'free_shipping_code' => 'FREESHIP',
			'global_threshold' => 0.00,
			'legacy_option_name' => '',
		);
		if ( 'beyourlover.com' === $host || str_ends_with( $host, '.beyourlover.com' ) ) {
			$preset = array(
				'id' => 'byl',
				'free_shipping_code' => 'BYL49',
				'global_threshold' => 49.00,
				'legacy_option_name' => 'byl_shipping_promotion_settings',
			);
		}
		return apply_filters( 'andy_commerce_site_preset', $preset, $host );
	}
}
