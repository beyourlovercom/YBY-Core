<?php
/**
 * Brand presentation profile.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Governed brand presentation accessor.
 */
class YBY_Brand_Profile {

	/**
	 * Return the presentation profile.
	 *
	 * @return array<string, string>
	 */
	public static function get_profile() {
		$site_profile = YBY_Site_Profile::get_profile();
		$profile      = self::sanitize_profile(
			array(
				'site_brand_key'                     => $site_profile['site_brand_key'],
				'site_brand_name'                    => $site_profile['site_brand_name'],
				'case_id_brand_code'                 => $site_profile['case_id_brand_code'],
				'website_url'                        => $site_profile['website_url'],
				'brand_primary_color'                => YBY_Config::get_brand_primary_color(),
				'brand_primary_text_color'           => YBY_Config::get_brand_primary_text_color(),
				'brand_secondary_color'              => YBY_Config::get_brand_secondary_color(),
				'brand_surface_color'                => YBY_Config::get_brand_surface_color(),
				'brand_text_color'                   => YBY_Config::get_brand_text_color(),
				'brand_muted_text_color'             => YBY_Config::get_brand_muted_text_color(),
				'brand_border_color'                 => YBY_Config::get_brand_border_color(),
				'whatsapp_number'                    => YBY_Config::get_whatsapp_number(),
				'catalog_url'                        => YBY_Config::get_catalog_url(),
				'youtube_video_id'                   => YBY_Config::get_youtube_video_id(),
				'support_email'                      => YBY_Config::get_support_email(),
				'default_country'                    => YBY_Config::get_default_country(),
				'default_product_interest'           => YBY_Config::get_default_product_interest(),
				'thank_you_url'                      => YBY_Config::get_thank_you_url(),
				'return_page_url'                    => YBY_Config::get_return_page_url(),
				'whatsapp_message_template'          => YBY_Config::get_whatsapp_message_template(),
				'lead_notification_subject_template' => YBY_Config::get_lead_notification_subject_template(),
				'inquiry_email_title'                => YBY_Config::get_inquiry_email_title(),
				'email_company_name'                 => YBY_Config::get_email_company_name(),
				'email_company_website'              => YBY_Config::get_email_company_website(),
				'email_company_phone'                => YBY_Config::get_email_company_phone(),
				'email_company_whatsapp'             => YBY_Config::get_email_company_whatsapp(),
				'email_footer_copyright'             => YBY_Config::get_email_footer_copyright(),
				'email_logo_url'                     => YBY_Config::get_email_logo_url(),
				'email_reverse_logo_url'             => YBY_Config::get_email_reverse_logo_url(),
			)
		);

		if ( function_exists( 'apply_filters' ) ) {
			$profile = apply_filters( 'yby_brand_profile', $profile );
		}

		$profile = self::sanitize_profile( is_array( $profile ) ? $profile : array() );

		$profile['site_brand_key']     = $site_profile['site_brand_key'];
		$profile['site_brand_name']    = $site_profile['site_brand_name'];
		$profile['case_id_brand_code'] = $site_profile['case_id_brand_code'];
		$profile['website_url']        = $site_profile['website_url'];

		return $profile;
	}

	/**
	 * Sanitize a raw presentation profile.
	 *
	 * @param array<string, mixed> $profile Raw values.
	 * @return array<string, string>
	 */
	public static function sanitize_profile( $profile ) {
		$profile  = is_array( $profile ) ? $profile : array();
		$defaults = YBY_Config::defaults();

		return array(
			'site_brand_key'                     => YBY_Config::sanitize_site_brand_key( $profile['site_brand_key'] ?? $defaults['site_brand_key'] ),
			'site_brand_name'                    => YBY_Config::sanitize_site_brand_name( $profile['site_brand_name'] ?? $defaults['site_brand_name'] ),
			'case_id_brand_code'                 => YBY_Config::sanitize_case_id_brand_code( $profile['case_id_brand_code'] ?? $defaults['case_id_brand_code'] ),
			'website_url'                        => YBY_Config::sanitize_website_url( $profile['website_url'] ?? $defaults['website_url'] ),
			'brand_primary_color'                => YBY_Config::sanitize_hex_color_value( $profile['brand_primary_color'] ?? $defaults['brand_primary_color'], $defaults['brand_primary_color'] ),
			'brand_primary_text_color'           => YBY_Config::sanitize_hex_color_value( $profile['brand_primary_text_color'] ?? $defaults['brand_primary_text_color'], $defaults['brand_primary_text_color'] ),
			'brand_secondary_color'              => YBY_Config::sanitize_hex_color_value( $profile['brand_secondary_color'] ?? $defaults['brand_secondary_color'], $defaults['brand_secondary_color'] ),
			'brand_surface_color'                => YBY_Config::sanitize_hex_color_value( $profile['brand_surface_color'] ?? $defaults['brand_surface_color'], $defaults['brand_surface_color'] ),
			'brand_text_color'                   => YBY_Config::sanitize_hex_color_value( $profile['brand_text_color'] ?? $defaults['brand_text_color'], $defaults['brand_text_color'] ),
			'brand_muted_text_color'             => YBY_Config::sanitize_hex_color_value( $profile['brand_muted_text_color'] ?? $defaults['brand_muted_text_color'], $defaults['brand_muted_text_color'] ),
			'brand_border_color'                 => YBY_Config::sanitize_hex_color_value( $profile['brand_border_color'] ?? $defaults['brand_border_color'], $defaults['brand_border_color'] ),
			'whatsapp_number'                    => YBY_Config::sanitize_display_text( $profile['whatsapp_number'] ?? $defaults['whatsapp_number'] ),
			'catalog_url'                        => YBY_Config::sanitize_absolute_url_value( $profile['catalog_url'] ?? $defaults['catalog_url'] ),
			'youtube_video_id'                   => YBY_Config::sanitize_video_id( $profile['youtube_video_id'] ?? $defaults['youtube_video_id'] ),
			'support_email'                      => YBY_Config::sanitize_email_value( $profile['support_email'] ?? $defaults['support_email'] ),
			'default_country'                    => YBY_Config::sanitize_display_text( $profile['default_country'] ?? $defaults['default_country'] ),
			'default_product_interest'           => YBY_Config::sanitize_display_text( $profile['default_product_interest'] ?? $defaults['default_product_interest'] ),
			'thank_you_url'                      => YBY_Config::sanitize_path_or_absolute_url_value( $profile['thank_you_url'] ?? $defaults['thank_you_url'], '/' ),
			'return_page_url'                    => YBY_Config::sanitize_path_or_absolute_url_value( $profile['return_page_url'] ?? $defaults['return_page_url'], '/' ),
			'whatsapp_message_template'          => YBY_Config::sanitize_whatsapp_message_template( $profile['whatsapp_message_template'] ?? $defaults['whatsapp_message_template'] ),
			'lead_notification_subject_template' => YBY_Config::sanitize_subject_template( $profile['lead_notification_subject_template'] ?? $defaults['lead_notification_subject_template'] ),
			'inquiry_email_title'                => self::sanitize_inquiry_email_title( $profile['inquiry_email_title'] ?? $defaults['inquiry_email_title'] ),
			'email_company_name'                 => YBY_Config::sanitize_display_text( $profile['email_company_name'] ?? $defaults['email_company_name'] ),
			'email_company_website'              => YBY_Config::sanitize_absolute_url_value( $profile['email_company_website'] ?? $defaults['email_company_website'] ),
			'email_company_phone'                => YBY_Config::sanitize_display_text( $profile['email_company_phone'] ?? $defaults['email_company_phone'] ),
			'email_company_whatsapp'             => YBY_Config::sanitize_display_text( $profile['email_company_whatsapp'] ?? $defaults['email_company_whatsapp'] ),
			'email_footer_copyright'             => YBY_Config::sanitize_display_text( $profile['email_footer_copyright'] ?? $defaults['email_footer_copyright'] ),
			'email_logo_url'                     => YBY_Config::sanitize_absolute_url_value( $profile['email_logo_url'] ?? $defaults['email_logo_url'] ),
			'email_reverse_logo_url'             => YBY_Config::sanitize_absolute_url_value( $profile['email_reverse_logo_url'] ?? $defaults['email_reverse_logo_url'] ),
		);
	}

	public static function get_brand_name() {
		$profile = self::get_profile();

		return '' !== $profile['email_company_name'] ? $profile['email_company_name'] : $profile['site_brand_name'];
	}

	public static function get_website_url() {
		$profile = self::get_profile();

		return '' !== $profile['email_company_website'] ? $profile['email_company_website'] : $profile['website_url'];
	}

	public static function get_primary_color() {
		return self::get_profile()['brand_primary_color'];
	}

	public static function get_primary_text_color() {
		return self::get_profile()['brand_primary_text_color'];
	}

	public static function get_secondary_color() {
		return self::get_profile()['brand_secondary_color'];
	}

	public static function get_surface_color() {
		return self::get_profile()['brand_surface_color'];
	}

	public static function get_text_color() {
		return self::get_profile()['brand_text_color'];
	}

	public static function get_muted_text_color() {
		return self::get_profile()['brand_muted_text_color'];
	}

	public static function get_border_color() {
		return self::get_profile()['brand_border_color'];
	}

	public static function get_logo_url() {
		return self::get_profile()['email_logo_url'];
	}

	public static function get_reverse_logo_url() {
		return self::get_profile()['email_reverse_logo_url'];
	}

	public static function get_phone() {
		return self::get_profile()['email_company_phone'];
	}

	public static function get_whatsapp() {
		$profile = self::get_profile();

		return '' !== $profile['email_company_whatsapp'] ? $profile['email_company_whatsapp'] : $profile['whatsapp_number'];
	}

	public static function get_support_email() {
		return self::get_profile()['support_email'];
	}

	public static function get_footer_copyright() {
		$profile = self::get_profile();

		if ( '' !== $profile['email_footer_copyright'] ) {
			return $profile['email_footer_copyright'];
		}

		return '© ' . gmdate( 'Y' ) . ' ' . self::get_brand_name() . '. All rights reserved.';
	}

	public static function get_inquiry_email_title() {
		return self::get_profile()['inquiry_email_title'];
	}

	public static function get_subject_template() {
		return self::get_profile()['lead_notification_subject_template'];
	}

	public static function get_thank_you_url() {
		return self::get_profile()['thank_you_url'];
	}

	public static function get_return_page_url() {
		return self::get_profile()['return_page_url'];
	}

	public static function get_catalog_url() {
		return self::get_profile()['catalog_url'];
	}

	public static function get_youtube_video_id() {
		return self::get_profile()['youtube_video_id'];
	}

	public static function get_default_country() {
		return self::get_profile()['default_country'];
	}

	public static function get_default_product_interest() {
		return self::get_profile()['default_product_interest'];
	}

	public static function get_whatsapp_message_template() {
		return self::get_profile()['whatsapp_message_template'];
	}

	protected static function sanitize_inquiry_email_title( $value ) {
		$value = YBY_Config::sanitize_display_text( $value );

		return '' !== $value ? $value : 'Website Inquiry';
	}
}
