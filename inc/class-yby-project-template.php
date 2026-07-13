<?php
/**
 * Project template orchestration runtime.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project Template runtime helper.
 */
class YBY_Project_Template {

	/**
	 * Scalar field map.
	 *
	 * @return array<string, string>
	 */
	protected static function field_map() {
		return array(
			'templateId'   => 'yby_template_id',
			'templateName' => 'yby_template_name',
			'version'      => 'yby_template_version',
			'status'       => 'yby_template_status',
		);
	}

	/**
	 * Page map field map.
	 *
	 * @return array<string, string>
	 */
	protected static function page_map_field_map() {
		return array(
			'landingPage' => 'yby_template_landing_page',
			'thankYouPage' => 'yby_template_thank_you_page',
			'futurePages' => 'yby_template_future_pages',
		);
	}

	/**
	 * Content map field map.
	 *
	 * @return array<string, string>
	 */
	protected static function content_map_field_map() {
		return array(
			'hero' => 'yby_template_content_hero',
			'faq' => 'yby_template_content_faq',
			'caseStudies' => 'yby_template_content_case_studies',
			'products' => 'yby_template_content_products',
			'cta' => 'yby_template_content_cta',
			'footer' => 'yby_template_content_footer',
		);
	}

	/**
	 * Tracking map field map.
	 *
	 * @return array<string, string>
	 */
	protected static function tracking_map_field_map() {
		return array(
			'trackingGroup' => 'yby_template_tracking_group',
			'ga4ContentGroup' => 'yby_template_ga4_content_group',
			'adsConversionGroup' => 'yby_template_ads_conversion_group',
		);
	}

	/**
	 * Asset map field map.
	 *
	 * @return array<string, string>
	 */
	protected static function asset_map_field_map() {
		return array(
			'catalog' => 'yby_template_asset_catalog',
			'video' => 'yby_template_asset_video',
			'downloads' => 'yby_template_asset_downloads',
			'images' => 'yby_template_asset_images',
		);
	}

	/**
	 * Integration map field map.
	 *
	 * @return array<string, string>
	 */
	protected static function integration_map_field_map() {
		return array(
			'crm' => 'yby_template_integration_crm',
			'email' => 'yby_template_integration_email',
			'erp' => 'yby_template_integration_erp',
			'futureAi' => 'yby_template_integration_future_ai',
		);
	}

	/**
	 * Get current template.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_current_template() {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_template() );
		}

		return self::get_template_by_post_id( $post_id );
	}

	/**
	 * Get template by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_template_by_post_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_template() );
		}

		$template = self::get_default_template();

		foreach ( self::field_map() as $runtime_key => $meta_key ) {
			$template[ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		foreach ( self::page_map_field_map() as $runtime_key => $meta_key ) {
			if ( 'futurePages' === $runtime_key ) {
				$template['pageMap'][ $runtime_key ] = self::get_list_meta_value( $post_id, $meta_key );
				continue;
			}

			$template['pageMap'][ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		foreach ( self::content_map_field_map() as $runtime_key => $meta_key ) {
			$template['contentMap'][ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		foreach ( self::tracking_map_field_map() as $runtime_key => $meta_key ) {
			$template['trackingMap'][ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		foreach ( self::asset_map_field_map() as $runtime_key => $meta_key ) {
			if ( in_array( $runtime_key, array( 'downloads', 'images' ), true ) ) {
				$template['assetMap'][ $runtime_key ] = self::get_list_meta_value( $post_id, $meta_key );
				continue;
			}

			$template['assetMap'][ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		foreach ( self::integration_map_field_map() as $runtime_key => $meta_key ) {
			$template['integrationMap'][ $runtime_key ] = self::get_scalar_meta_value( $post_id, $meta_key );
		}

		$content      = YBY_Content::get_content_by_post_id( $post_id );
		$page_profile = YBY_Page_Profile::get_raw_profile_by_post_id( $post_id );

		$template = self::merge_with_project_content( $template, $content );
		$template = self::merge_with_project_profile( $template, $page_profile );
		$template = self::merge_with_global_config( $template );

		return self::normalize( $template );
	}

	/**
	 * Get default template.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_template() {
		return array(
			'templateId' => '',
			'templateName' => 'Irrigation Solution Template',
			'version' => '1.0.0',
			'status' => 'draft',
			'pageMap' => array(
				'landingPage' => '/lp/irrigation-solution/',
				'thankYouPage' => '/lp/thank-you-irrigation-solution/',
				'futurePages' => array(),
			),
			'contentMap' => array(
				'hero' => 'hero',
				'faq' => 'faq',
				'caseStudies' => 'case_studies',
				'products' => 'products',
				'cta' => 'cta',
				'footer' => 'footer_cta',
			),
			'trackingMap' => array(
				'trackingGroup' => '',
				'ga4ContentGroup' => '',
				'adsConversionGroup' => '',
			),
			'assetMap' => array(
				'catalog' => '',
				'video' => '',
				'downloads' => array(),
				'images' => array(),
			),
			'integrationMap' => array(
				'crm' => '',
				'email' => '',
				'erp' => '',
				'futureAi' => '',
			),
		);
	}

	/**
	 * Merge template with project content compatibility values.
	 *
	 * @param array<string, mixed> $template Template object.
	 * @param array<string, mixed> $content Content object.
	 * @return array<string, mixed>
	 */
	public static function merge_with_project_content( $template, $content ) {
		$template = is_array( $template ) ? $template : self::get_default_template();
		$content  = is_array( $content ) ? $content : array();
		$sections = isset( $content['sections'] ) && is_array( $content['sections'] ) ? $content['sections'] : array();

		$map = array(
			'hero' => 'hero',
			'faq' => 'faq',
			'caseStudies' => 'case_studies',
			'products' => 'products',
			'cta' => 'cta',
			'footer' => 'footer_cta',
		);

		foreach ( $map as $template_key => $section_id ) {
			if ( ! empty( $template['contentMap'][ $template_key ] ) ) {
				continue;
			}

			if ( isset( $sections[ $section_id ] ) ) {
				$template['contentMap'][ $template_key ] = (string) $section_id;
			}
		}

		return $template;
	}

	/**
	 * Merge template with project profile compatibility values.
	 *
	 * @param array<string, mixed>  $template Template object.
	 * @param array<string, string> $page_profile Page profile object.
	 * @return array<string, mixed>
	 */
	public static function merge_with_project_profile( $template, $page_profile ) {
		$template     = is_array( $template ) ? $template : self::get_default_template();
		$page_profile = is_array( $page_profile ) ? $page_profile : array();

		if ( empty( $template['trackingMap']['trackingGroup'] ) && ! empty( $page_profile['trackingGroup'] ) ) {
			$template['trackingMap']['trackingGroup'] = (string) $page_profile['trackingGroup'];
		}

		if ( empty( $template['assetMap']['catalog'] ) && ! empty( $page_profile['catalogUrl'] ) ) {
			$template['assetMap']['catalog'] = (string) $page_profile['catalogUrl'];
		}

		if ( empty( $template['assetMap']['video'] ) && ! empty( $page_profile['youtubeVideoId'] ) ) {
			$template['assetMap']['video'] = (string) $page_profile['youtubeVideoId'];
		}

		if ( empty( $template['pageMap']['thankYouPage'] ) && ! empty( $page_profile['thankYouUrl'] ) ) {
			$template['pageMap']['thankYouPage'] = (string) $page_profile['thankYouUrl'];
		}

		return $template;
	}

	/**
	 * Merge template with global config compatibility values.
	 *
	 * @param array<string, mixed> $template Template object.
	 * @return array<string, mixed>
	 */
	public static function merge_with_global_config( $template ) {
		$template = is_array( $template ) ? $template : self::get_default_template();
		$runtime  = YBY_Config::get_runtime_config();

		if ( empty( $template['pageMap']['thankYouPage'] ) && ! empty( $runtime['thankYouUrl'] ) ) {
			$template['pageMap']['thankYouPage'] = (string) $runtime['thankYouUrl'];
		}

		if ( empty( $template['pageMap']['landingPage'] ) && ! empty( $runtime['returnPageUrl'] ) ) {
			$template['pageMap']['landingPage'] = (string) $runtime['returnPageUrl'];
		}

		if ( empty( $template['assetMap']['catalog'] ) && ! empty( $runtime['catalogUrl'] ) ) {
			$template['assetMap']['catalog'] = (string) $runtime['catalogUrl'];
		}

		if ( empty( $template['assetMap']['video'] ) && ! empty( $runtime['youtubeVideoId'] ) ) {
			$template['assetMap']['video'] = (string) $runtime['youtubeVideoId'];
		}

		return $template;
	}

	/**
	 * Normalize template object.
	 *
	 * @param array<string, mixed> $template Raw template.
	 * @return array<string, mixed>
	 */
	public static function normalize( $template ) {
		$template = is_array( $template ) ? $template : array();
		$defaults = self::get_default_template();

		$output = array(
			'templateId' => sanitize_text_field( (string) ( $template['templateId'] ?? $defaults['templateId'] ) ),
			'templateName' => sanitize_text_field( (string) ( $template['templateName'] ?? $defaults['templateName'] ) ),
			'version' => sanitize_text_field( (string) ( $template['version'] ?? $defaults['version'] ) ),
			'status' => sanitize_text_field( (string) ( $template['status'] ?? $defaults['status'] ) ),
			'pageMap' => array(
				'landingPage' => self::sanitize_relative_or_absolute_url( (string) ( $template['pageMap']['landingPage'] ?? $defaults['pageMap']['landingPage'] ) ),
				'thankYouPage' => self::sanitize_relative_or_absolute_url( (string) ( $template['pageMap']['thankYouPage'] ?? $defaults['pageMap']['thankYouPage'] ) ),
				'futurePages' => self::sanitize_list( $template['pageMap']['futurePages'] ?? $defaults['pageMap']['futurePages'], 'path' ),
			),
			'contentMap' => array(
				'hero' => sanitize_text_field( (string) ( $template['contentMap']['hero'] ?? $defaults['contentMap']['hero'] ) ),
				'faq' => sanitize_text_field( (string) ( $template['contentMap']['faq'] ?? $defaults['contentMap']['faq'] ) ),
				'caseStudies' => sanitize_text_field( (string) ( $template['contentMap']['caseStudies'] ?? $defaults['contentMap']['caseStudies'] ) ),
				'products' => sanitize_text_field( (string) ( $template['contentMap']['products'] ?? $defaults['contentMap']['products'] ) ),
				'cta' => sanitize_text_field( (string) ( $template['contentMap']['cta'] ?? $defaults['contentMap']['cta'] ) ),
				'footer' => sanitize_text_field( (string) ( $template['contentMap']['footer'] ?? $defaults['contentMap']['footer'] ) ),
			),
			'trackingMap' => array(
				'trackingGroup' => sanitize_text_field( (string) ( $template['trackingMap']['trackingGroup'] ?? $defaults['trackingMap']['trackingGroup'] ) ),
				'ga4ContentGroup' => sanitize_text_field( (string) ( $template['trackingMap']['ga4ContentGroup'] ?? $defaults['trackingMap']['ga4ContentGroup'] ) ),
				'adsConversionGroup' => sanitize_text_field( (string) ( $template['trackingMap']['adsConversionGroup'] ?? $defaults['trackingMap']['adsConversionGroup'] ) ),
			),
			'assetMap' => array(
				'catalog' => self::sanitize_relative_or_absolute_url( (string) ( $template['assetMap']['catalog'] ?? $defaults['assetMap']['catalog'] ) ),
				'video' => sanitize_text_field( (string) ( $template['assetMap']['video'] ?? $defaults['assetMap']['video'] ) ),
				'downloads' => self::sanitize_list( $template['assetMap']['downloads'] ?? $defaults['assetMap']['downloads'], 'url' ),
				'images' => self::sanitize_list( $template['assetMap']['images'] ?? $defaults['assetMap']['images'], 'url' ),
			),
			'integrationMap' => array(
				'crm' => sanitize_text_field( (string) ( $template['integrationMap']['crm'] ?? $defaults['integrationMap']['crm'] ) ),
				'email' => sanitize_text_field( (string) ( $template['integrationMap']['email'] ?? $defaults['integrationMap']['email'] ) ),
				'erp' => sanitize_text_field( (string) ( $template['integrationMap']['erp'] ?? $defaults['integrationMap']['erp'] ) ),
				'futureAi' => sanitize_text_field( (string) ( $template['integrationMap']['futureAi'] ?? $defaults['integrationMap']['futureAi'] ) ),
			),
		);

		return $output;
	}

	/**
	 * Read scalar ACF or post meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @return string
	 */
	protected static function get_scalar_meta_value( $post_id, $meta_key ) {
		$value = self::get_meta_value( $post_id, $meta_key );

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Read list ACF or post meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @return array<int, string>
	 */
	protected static function get_list_meta_value( $post_id, $meta_key ) {
		$value = self::get_meta_value( $post_id, $meta_key );

		if ( is_array( $value ) ) {
			return self::sanitize_list( $value, 'text' );
		}

		if ( is_scalar( $value ) ) {
			$items = preg_split( '/[\r\n,]+/', (string) $value );
			return self::sanitize_list( is_array( $items ) ? $items : array(), 'text' );
		}

		return array();
	}

	/**
	 * Read ACF or post meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @return mixed
	 */
	protected static function get_meta_value( $post_id, $meta_key ) {
		$value = '';

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $meta_key, $post_id );
		}

		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $post_id, $meta_key, true );
		}

		return $value;
	}

	/**
	 * Sanitize a list of values.
	 *
	 * @param mixed  $values Values.
	 * @param string $mode Sanitization mode.
	 * @return array<int, string>
	 */
	protected static function sanitize_list( $values, $mode ) {
		$values = is_array( $values ) ? $values : array();
		$output = array();

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$item = trim( (string) $value );

			if ( '' === $item ) {
				continue;
			}

			switch ( $mode ) {
				case 'url':
					$item = self::sanitize_relative_or_absolute_url( $item );
					break;
				case 'path':
					$item = self::sanitize_relative_or_absolute_url( $item );
					break;
				default:
					$item = sanitize_text_field( $item );
					break;
			}

			if ( '' !== $item ) {
				$output[] = $item;
			}
		}

		return array_values( array_unique( $output ) );
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
