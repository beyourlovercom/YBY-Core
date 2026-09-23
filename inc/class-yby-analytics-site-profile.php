<?php
/**
 * Analytics Site Profile V1.
 *
 * Read-only projection over existing trusted site identity and project/page
 * tracking context. It does not migrate or rewrite legacy metadata.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Analytics_Site_Profile {
	const VERSION = '1';

	public static function defaults() {
		return array(
			'profile_version' => self::VERSION,
			'site_brand_key' => '',
			'site_brand_name' => '',
			'website_host' => '',
			'tracking_group' => '',
			'ga4_content_group' => '',
			'ads_conversion_group' => '',
		);
	}

	public static function current( $project = null ) {
		if ( null === $project ) {
			$project = class_exists( 'YBY_Project' ) ? YBY_Project::get_current_project() : array();
		}
		$project = is_array( $project ) ? $project : array();
		$site = class_exists( 'YBY_Site_Profile' ) ? YBY_Site_Profile::get_profile() : array();

		$website_url = isset( $site['website_url'] ) ? (string) $site['website_url'] : '';
		$host = '';
		if ( '' !== $website_url ) {
			$parsed = function_exists( 'wp_parse_url' ) ? wp_parse_url( $website_url, PHP_URL_HOST ) : parse_url( $website_url, PHP_URL_HOST );
			$host = is_string( $parsed ) ? strtolower( $parsed ) : '';
		}

		return self::sanitize(
			array(
				'profile_version' => self::VERSION,
				'site_brand_key' => $site['site_brand_key'] ?? '',
				'site_brand_name' => $site['site_brand_name'] ?? '',
				'website_host' => $host,
				'tracking_group' => $project['trackingGroup'] ?? '',
				'ga4_content_group' => $project['ga4ContentGroup'] ?? '',
				'ads_conversion_group' => $project['adsConversionGroup'] ?? '',
			)
		);
	}

	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$clean = self::defaults();
		$clean['site_brand_key'] = sanitize_key( $raw['site_brand_key'] ?? '' );
		$clean['site_brand_name'] = sanitize_text_field( $raw['site_brand_name'] ?? '' );
		$host = strtolower( trim( (string) ( $raw['website_host'] ?? '' ) ) );
		$clean['website_host'] = preg_match( '/^[a-z0-9.-]{1,253}$/', $host ) ? $host : '';
		foreach ( array( 'tracking_group', 'ga4_content_group', 'ads_conversion_group' ) as $key ) {
			$value = sanitize_text_field( $raw[ $key ] ?? '' );
			$clean[ $key ] = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 80 ) : substr( $value, 0, 80 );
		}
		return $clean;
	}
}
