<?php
/**
 * Page profile engine.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page-level profile helper.
 */
class YBY_Page_Profile {

	/**
	 * Supported profile field map.
	 *
	 * @return array<string, string>
	 */
	protected static function field_map() {
		return array(
			'profileId'        => 'yby_profile_id',
			'pageType'         => 'yby_page_type',
			'productInterest'  => 'yby_product_interest',
			'country'          => 'yby_country',
			'catalogUrl'       => 'yby_catalog_url',
			'youtubeVideoId'   => 'yby_youtube_video_id',
			'thankYouUrl'      => 'yby_thank_you_url',
			'returnPageUrl'    => 'yby_return_page_url',
			'whatsappMessage'  => 'yby_whatsapp_message',
			'crmPipeline'      => 'yby_crm_pipeline',
			'trackingGroup'    => 'yby_tracking_group',
		);
	}

	/**
	 * Get current queried page profile.
	 *
	 * @return array<string, string>
	 */
	public static function get_current_profile() {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return self::merge_with_global_config( array() );
		}

		return self::get_profile_by_post_id( $post_id );
	}

	/**
	 * Get page profile by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string>
	 */
	public static function get_profile_by_post_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return self::merge_with_global_config( array() );
		}

		$profile = self::get_raw_profile_by_post_id( $post_id );

		return self::merge_with_global_config( $profile );
	}

	/**
	 * Get raw page profile by post ID before global merge.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string>
	 */
	public static function get_raw_profile_by_post_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return self::get_default_profile();
		}

		$profile = array();

		foreach ( self::field_map() as $runtime_key => $meta_key ) {
			$profile[ $runtime_key ] = self::get_profile_value( $post_id, $meta_key, $runtime_key );
		}

		return wp_parse_args( $profile, self::get_default_profile() );
	}

	/**
	 * Return default profile values.
	 *
	 * @return array<string, string>
	 */
	public static function get_default_profile() {
		return array(
			'profileId'       => '',
			'pageType'        => '',
			'productInterest' => YBY_Config::get_default_product_interest(),
			'country'         => YBY_Config::get_default_country(),
			'catalogUrl'      => '',
			'youtubeVideoId'  => '',
			'thankYouUrl'     => '/lp/thank-you-irrigation-solution/',
			'returnPageUrl'   => '/lp/irrigation-solution/',
			'whatsappMessage' => '',
			'crmPipeline'     => '',
			'trackingGroup'   => '',
		);
	}

	/**
	 * Merge page profile with global config and defaults.
	 *
	 * Priority:
	 * 1. Page Profile
	 * 2. Global Config
	 * 3. System Defaults
	 *
	 * @param array<string, string> $profile Page profile.
	 * @return array<string, string>
	 */
	public static function merge_with_global_config( $profile ) {
		$defaults = self::get_default_profile();
		$runtime  = YBY_Config::get_runtime_config();

		$global_map = array(
			'catalogUrl'      => $runtime['catalogUrl'] ?? '',
			'youtubeVideoId'  => $runtime['youtubeVideoId'] ?? '',
			'thankYouUrl'     => $runtime['thankYouUrl'] ?? '',
			'returnPageUrl'   => $runtime['returnPageUrl'] ?? '',
			'productInterest' => $runtime['defaultProductInterest'] ?? '',
			'country'         => $runtime['defaultCountry'] ?? '',
		);

		$profile = is_array( $profile ) ? $profile : array();
		$merged  = $defaults;

		foreach ( $merged as $key => $default_value ) {
			$page_value   = isset( $profile[ $key ] ) ? (string) $profile[ $key ] : '';
			$global_value = isset( $global_map[ $key ] ) ? (string) $global_map[ $key ] : '';

			if ( '' !== $page_value ) {
				$merged[ $key ] = $page_value;
			} elseif ( '' !== $global_value ) {
				$merged[ $key ] = $global_value;
			} else {
				$merged[ $key ] = (string) $default_value;
			}
		}

		return $merged;
	}

	/**
	 * Get a raw profile field value from ACF or post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $runtime_key Runtime key.
	 * @return string
	 */
	protected static function get_profile_value( $post_id, $meta_key, $runtime_key ) {
		$value = '';

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $meta_key, $post_id );
		}

		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $post_id, $meta_key, true );
		}

		return self::sanitize_profile_value( $runtime_key, $value );
	}

	/**
	 * Sanitize profile value by runtime key.
	 *
	 * @param string $runtime_key Runtime key.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	protected static function sanitize_profile_value( $runtime_key, $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		switch ( $runtime_key ) {
			case 'catalogUrl':
				return esc_url_raw( $value );
			case 'thankYouUrl':
			case 'returnPageUrl':
				return self::sanitize_relative_or_absolute_url( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize a relative or absolute URL.
	 *
	 * @param string $value URL value.
	 * @return string
	 */
	protected static function sanitize_relative_or_absolute_url( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $value ) ) {
			return esc_url_raw( $value );
		}

		return YBY_Config::sanitize_path_value( $value );
	}
}
