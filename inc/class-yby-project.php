<?php
/**
 * Project engine.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical marketing project object.
 */
class YBY_Project {

	/**
	 * Supported project field map.
	 *
	 * @return array<string, string>
	 */
	protected static function field_map() {
		return array(
			'projectId'          => 'yby_project_id',
			'projectName'        => 'yby_project_name',
			'projectType'        => 'yby_project_type',
			'projectStatus'      => 'yby_project_status',
			'country'            => 'yby_country',
			'language'           => 'yby_language',
			'region'             => 'yby_region',
			'targetMarket'       => 'yby_target_market',
			'productInterest'    => 'yby_product_interest',
			'productCategory'    => 'yby_product_category',
			'productLine'        => 'yby_product_line',
			'catalogUrl'         => 'yby_catalog_url',
			'youtubeVideoId'     => 'yby_youtube_video_id',
			'caseStudyUrl'       => 'yby_case_study_url',
			'landingUrl'         => 'yby_landing_url',
			'thankYouUrl'        => 'yby_thank_you_url',
			'returnPageUrl'      => 'yby_return_page_url',
			'whatsappMessage'    => 'yby_whatsapp_message',
			'emailSubject'       => 'yby_email_subject',
			'emailIntro'         => 'yby_email_intro',
			'trackingGroup'      => 'yby_tracking_group',
			'ga4ContentGroup'    => 'yby_ga4_content_group',
			'adsConversionGroup' => 'yby_ads_conversion_group',
			'crmPipeline'        => 'yby_crm_pipeline',
			'crmOwner'           => 'yby_crm_owner',
			'leadPriority'       => 'yby_lead_priority',
		);
	}

	/**
	 * Get current queried project.
	 *
	 * @return array<string, string>
	 */
	public static function get_current_project() {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_project() );
		}

		return self::get_project_by_post_id( $post_id );
	}

	/**
	 * Build project from post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string>
	 */
	public static function get_project_by_post_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_project() );
		}

		$project = self::get_default_project();

		foreach ( self::field_map() as $runtime_key => $meta_key ) {
			$project[ $runtime_key ] = self::get_project_value( $post_id, $meta_key, $runtime_key );
		}

		$page_profile = YBY_Page_Profile::get_raw_profile_by_post_id( $post_id );

		$project = self::merge_with_page_profile( $project, $page_profile );
		$project = self::merge_with_global_config( $project );

		return self::normalize( $project );
	}

	/**
	 * Default project structure.
	 *
	 * @return array<string, string>
	 */
	public static function get_default_project() {
		return array(
			'projectId'          => '',
			'projectName'        => '',
			'projectType'        => '',
			'projectStatus'      => '',
			'country'            => '',
			'language'           => '',
			'region'             => '',
			'targetMarket'       => '',
			'productInterest'    => '',
			'productCategory'    => '',
			'productLine'        => '',
			'catalogUrl'         => '',
			'youtubeVideoId'     => '',
			'caseStudyUrl'       => '',
			'landingUrl'         => '',
			'thankYouUrl'        => '/lp/thank-you-irrigation-solution/',
			'returnPageUrl'      => '/lp/irrigation-solution/',
			'whatsappMessage'    => '',
			'emailSubject'       => '',
			'emailIntro'         => '',
			'trackingGroup'      => '',
			'ga4ContentGroup'    => '',
			'adsConversionGroup' => '',
			'crmPipeline'        => '',
			'crmOwner'           => '',
			'leadPriority'       => '',
		);
	}

	/**
	 * Merge project with page profile compatibility values.
	 *
	 * @param array<string, string> $project Project object.
	 * @param array<string, string> $page_profile Page profile object.
	 * @return array<string, string>
	 */
	public static function merge_with_page_profile( $project, $page_profile ) {
		$project      = is_array( $project ) ? $project : self::get_default_project();
		$page_profile = is_array( $page_profile ) ? $page_profile : array();

		$map = array(
			'productInterest' => 'productInterest',
			'country'         => 'country',
			'catalogUrl'      => 'catalogUrl',
			'youtubeVideoId'  => 'youtubeVideoId',
			'thankYouUrl'     => 'thankYouUrl',
			'returnPageUrl'   => 'returnPageUrl',
			'whatsappMessage' => 'whatsappMessage',
			'crmPipeline'     => 'crmPipeline',
			'trackingGroup'   => 'trackingGroup',
		);

		foreach ( $map as $project_key => $profile_key ) {
			if ( empty( $project[ $project_key ] ) && ! empty( $page_profile[ $profile_key ] ) ) {
				$project[ $project_key ] = (string) $page_profile[ $profile_key ];
			}
		}

		return $project;
	}

	/**
	 * Merge project with global config.
	 *
	 * @param array<string, string> $project Project object.
	 * @return array<string, string>
	 */
	public static function merge_with_global_config( $project ) {
		$project = is_array( $project ) ? $project : self::get_default_project();
		$runtime = YBY_Config::get_runtime_config();

		$map = array(
			'country'         => $runtime['defaultCountry'] ?? '',
			'productInterest' => $runtime['defaultProductInterest'] ?? '',
			'catalogUrl'      => $runtime['catalogUrl'] ?? '',
			'youtubeVideoId'  => $runtime['youtubeVideoId'] ?? '',
			'thankYouUrl'     => $runtime['thankYouUrl'] ?? '',
			'returnPageUrl'   => $runtime['returnPageUrl'] ?? '',
		);

		foreach ( $map as $key => $value ) {
			if ( empty( $project[ $key ] ) && '' !== (string) $value ) {
				$project[ $key ] = (string) $value;
			}
		}

		return self::normalize( $project );
	}

	/**
	 * Normalize final project object.
	 *
	 * @param array<string, string> $project Raw project object.
	 * @return array<string, string>
	 */
	public static function normalize( $project ) {
		$project  = is_array( $project ) ? $project : array();
		$defaults = self::get_default_project();
		$output   = array();

		foreach ( $defaults as $key => $default_value ) {
			$value = isset( $project[ $key ] ) ? $project[ $key ] : $default_value;
			$value = is_scalar( $value ) ? (string) $value : '';

			switch ( $key ) {
				case 'catalogUrl':
				case 'caseStudyUrl':
					$output[ $key ] = esc_url_raw( $value );
					break;
				case 'landingUrl':
				case 'thankYouUrl':
				case 'returnPageUrl':
					$output[ $key ] = self::sanitize_relative_or_absolute_url( $value );
					break;
				default:
					$output[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $output;
	}

	/**
	 * Get project field from ACF or post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $runtime_key Runtime key.
	 * @return string
	 */
	protected static function get_project_value( $post_id, $meta_key, $runtime_key ) {
		$value = '';

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $meta_key, $post_id );
		}

		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $post_id, $meta_key, true );
		}

		return self::normalize(
			array(
				$runtime_key => is_scalar( $value ) ? (string) $value : '',
			)
		)[ $runtime_key ];
	}

	/**
	 * Sanitize relative or absolute URL.
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
