<?php
/**
 * Trusted site identity profile.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-owned site profile accessor.
 */
class YBY_Site_Profile {

	/**
	 * Return the trusted site profile.
	 *
	 * @return array<string, string>
	 */
	public static function get_profile() {
		$profile = self::sanitize_profile(
			array(
				'site_brand_key'     => YBY_Config::get_site_brand_key(),
				'site_brand_name'    => YBY_Config::get_site_brand_name(),
				'case_id_brand_code' => YBY_Config::get_case_id_brand_code(),
				'website_url'        => YBY_Config::get_website_url(),
			)
		);

		if ( function_exists( 'apply_filters' ) ) {
			$profile = apply_filters( 'yby_site_profile', $profile );
		}

		return self::sanitize_profile( is_array( $profile ) ? $profile : array() );
	}

	/**
	 * Sanitize a profile payload.
	 *
	 * @param array<string, mixed> $profile Raw profile values.
	 * @return array<string, string>
	 */
	public static function sanitize_profile( $profile ) {
		$profile = is_array( $profile ) ? $profile : array();

		return array(
			'site_brand_key'     => YBY_Config::sanitize_site_brand_key( $profile['site_brand_key'] ?? '' ),
			'site_brand_name'    => YBY_Config::sanitize_site_brand_name( $profile['site_brand_name'] ?? '' ),
			'case_id_brand_code' => YBY_Config::sanitize_case_id_brand_code( $profile['case_id_brand_code'] ?? '' ),
			'website_url'        => YBY_Config::sanitize_website_url( $profile['website_url'] ?? '' ),
		);
	}

	/**
	 * Return the stored brand key.
	 *
	 * @return string
	 */
	public static function get_brand_key() {
		$profile = self::get_profile();

		return $profile['site_brand_key'];
	}

	/**
	 * Return the display brand name.
	 *
	 * @return string
	 */
	public static function get_brand_name() {
		$profile = self::get_profile();

		return $profile['site_brand_name'];
	}

	/**
	 * Return the trusted case ID code.
	 *
	 * @return string
	 */
	public static function get_case_id_code() {
		$profile = self::get_profile();

		return $profile['case_id_brand_code'];
	}

	/**
	 * Return the trusted website URL.
	 *
	 * @return string
	 */
	public static function get_website_url() {
		$profile = self::get_profile();

		return $profile['website_url'];
	}
}
