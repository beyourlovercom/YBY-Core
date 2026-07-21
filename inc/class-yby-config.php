<?php
/**
 * Plugin configuration.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Config center.
 */
class YBY_Config {

	public static function defaults() {
		return array(
			'site_brand_key'                     => 'yby_core',
			'site_brand_name'                    => 'YBY',
			'case_id_brand_code'                 => 'CORE',
			'whatsapp_number'                    => '',
			'catalog_url'                        => '',
			'youtube_video_id'                   => '',
			'support_email'                      => '',
			'crm_webhook_url'                    => '',
			'default_country'                    => 'Tanzania',
			'default_product_interest'           => 'irrigation system solution',
			'thank_you_url'                      => '/lp/thank-you-irrigation-solution/',
			'return_page_url'                    => '/lp/irrigation-solution/',
			'website_url'                        => function_exists( 'home_url' ) ? home_url( '/' ) : '/',
			'lead_notification_subject_template' => '[YBY New Lead] {country} | {farm_size} | {crop} | {case_id}',
			'email_company_name'                 => 'YBY Irrigation',
			'email_company_website'              => 'https://ybyirrigation.com/',
			'email_company_phone'                => '',
			'email_company_whatsapp'             => '',
			'email_footer_copyright'             => '© YBY Irrigation. All rights reserved.',
			'email_logo_url'                     => '',
			'email_reverse_logo_url'             => '',
			'enable_tracking'                    => true,
			'enable_case_id'                     => true,
			'enable_crm_webhook'                 => false,
		);
	}

	public static function default_lead_recipient_email() {
		return 'beyourlovercom@gmail.com';
	}

	public static function get_options() {
		$options = get_option( YBY_Helpers::option_key(), array() );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return wp_parse_args( $options, self::defaults() );
	}

	public static function sanitize( $options ) {
		$options  = is_array( $options ) ? $options : array();
		$defaults = self::defaults();

		return array(
			'site_brand_key'                     => self::sanitize_site_brand_key( $options['site_brand_key'] ?? $defaults['site_brand_key'] ),
			'site_brand_name'                    => self::sanitize_site_brand_name( $options['site_brand_name'] ?? $defaults['site_brand_name'] ),
			'case_id_brand_code'                 => self::sanitize_case_id_brand_code( $options['case_id_brand_code'] ?? $defaults['case_id_brand_code'] ),
			'whatsapp_number'                    => sanitize_text_field( $options['whatsapp_number'] ?? $defaults['whatsapp_number'] ),
			'catalog_url'                        => esc_url_raw( $options['catalog_url'] ?? $defaults['catalog_url'] ),
			'youtube_video_id'                   => sanitize_text_field( $options['youtube_video_id'] ?? $defaults['youtube_video_id'] ),
			'support_email'                      => sanitize_email( $options['support_email'] ?? $defaults['support_email'] ),
			'crm_webhook_url'                    => esc_url_raw( $options['crm_webhook_url'] ?? $defaults['crm_webhook_url'] ),
			'default_country'                    => sanitize_text_field( $options['default_country'] ?? $defaults['default_country'] ),
			'default_product_interest'           => sanitize_text_field( $options['default_product_interest'] ?? $defaults['default_product_interest'] ),
			'thank_you_url'                      => self::sanitize_path_value( $options['thank_you_url'] ?? $defaults['thank_you_url'] ),
			'return_page_url'                    => self::sanitize_path_value( $options['return_page_url'] ?? $defaults['return_page_url'] ),
			'website_url'                        => self::sanitize_website_url( $options['website_url'] ?? $defaults['website_url'] ),
			'lead_notification_subject_template' => self::sanitize_subject_template( $options['lead_notification_subject_template'] ?? $defaults['lead_notification_subject_template'] ),
			'email_company_name'                 => sanitize_text_field( $options['email_company_name'] ?? $defaults['email_company_name'] ),
			'email_company_website'              => esc_url_raw( $options['email_company_website'] ?? $defaults['email_company_website'] ),
			'email_company_phone'                => self::sanitize_display_text( $options['email_company_phone'] ?? $defaults['email_company_phone'] ),
			'email_company_whatsapp'             => self::sanitize_display_text( $options['email_company_whatsapp'] ?? $defaults['email_company_whatsapp'] ),
			'email_footer_copyright'             => self::sanitize_display_text( $options['email_footer_copyright'] ?? $defaults['email_footer_copyright'] ),
			'email_logo_url'                     => esc_url_raw( $options['email_logo_url'] ?? $defaults['email_logo_url'] ),
			'email_reverse_logo_url'             => esc_url_raw( $options['email_reverse_logo_url'] ?? $defaults['email_reverse_logo_url'] ),
			'enable_tracking'                    => ! empty( $options['enable_tracking'] ),
			'enable_case_id'                     => ! empty( $options['enable_case_id'] ),
			'enable_crm_webhook'                 => ! empty( $options['enable_crm_webhook'] ),
		);
	}

	public static function get( $key ) {
		$options = self::get_options();

		return $options[ $key ] ?? null;
	}

	public static function get_whatsapp_number() {
		return (string) self::get( 'whatsapp_number' );
	}

	public static function get_site_brand_key() {
		return (string) self::get( 'site_brand_key' );
	}

	public static function get_site_brand_name() {
		return (string) self::get( 'site_brand_name' );
	}

	public static function get_case_id_brand_code() {
		return (string) self::get( 'case_id_brand_code' );
	}

	public static function get_catalog_url() {
		return (string) self::get( 'catalog_url' );
	}

	public static function get_youtube_video_id() {
		return (string) self::get( 'youtube_video_id' );
	}

	public static function get_support_email() {
		return (string) self::get( 'support_email' );
	}

	public static function get_crm_webhook_url() {
		return (string) self::get( 'crm_webhook_url' );
	}

	public static function get_default_country() {
		return (string) self::get( 'default_country' );
	}

	public static function get_default_product_interest() {
		return (string) self::get( 'default_product_interest' );
	}

	public static function get_thank_you_url() {
		return (string) self::get( 'thank_you_url' );
	}

	public static function get_return_page_url() {
		return (string) self::get( 'return_page_url' );
	}

	public static function get_website_url() {
		return (string) self::get( 'website_url' );
	}

	public static function get_lead_notification_subject_template() {
		return (string) self::get( 'lead_notification_subject_template' );
	}

	public static function get_email_company_name() {
		return (string) self::get( 'email_company_name' );
	}

	public static function get_email_company_website() {
		return (string) self::get( 'email_company_website' );
	}

	public static function get_email_company_phone() {
		return (string) self::get( 'email_company_phone' );
	}

	public static function get_email_company_whatsapp() {
		return (string) self::get( 'email_company_whatsapp' );
	}

	public static function get_email_footer_copyright() {
		return (string) self::get( 'email_footer_copyright' );
	}

	public static function get_email_logo_url() {
		$url = (string) self::get( 'email_logo_url' );

		if ( '' !== $url ) {
			return $url;
		}

		return class_exists( 'YBY_Brand_OS' ) ? (string) YBY_Brand_OS::get_logo_default() : '';
	}

	public static function get_email_reverse_logo_url() {
		$url = (string) self::get( 'email_reverse_logo_url' );

		if ( '' !== $url ) {
			return $url;
		}

		return class_exists( 'YBY_Brand_OS' ) ? (string) YBY_Brand_OS::get_logo_white() : '';
	}

	public static function get_lead_notification_primary_recipient_email() {
		return self::sanitize_email_value(
			get_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), 'sale@yby-irrigation.com' ),
			'sale@yby-irrigation.com'
		);
	}

	public static function get_lead_notification_cc_recipient_emails() {
		return self::sanitize_email_list_value(
			get_option( YBY_Helpers::lead_notification_cc_recipient_option_key(), 'yishitongshop@gmail.com' )
		);
	}

	public static function get_lead_notification_bcc_recipient_emails() {
		return self::sanitize_email_list_value(
			get_option( YBY_Helpers::lead_notification_bcc_recipient_option_key(), '' )
		);
	}

	public static function get_lead_notification_reply_to_policy() {
		return self::sanitize_reply_to_policy(
			get_option( YBY_Helpers::lead_notification_reply_to_policy_option_key(), 'auto' )
		);
	}

	public static function is_tracking_enabled() {
		return (bool) self::get( 'enable_tracking' );
	}

	public static function is_case_id_enabled() {
		return (bool) self::get( 'enable_case_id' );
	}

	public static function is_crm_webhook_enabled() {
		return (bool) self::get( 'enable_crm_webhook' );
	}

	public static function get_lead_recipient_email() {
		$email = get_option( YBY_Helpers::lead_recipient_option_key(), self::default_lead_recipient_email() );

		return self::sanitize_lead_recipient_email( $email, self::default_lead_recipient_email() );
	}

	public static function sanitize_lead_recipient_email( $value, $fallback = '' ) {
		$value = sanitize_email( (string) $value );

		if ( is_email( $value ) ) {
			return $value;
		}

		$fallback = sanitize_email( (string) $fallback );

		if ( is_email( $fallback ) ) {
			return $fallback;
		}

		return self::default_lead_recipient_email();
	}

	public static function get_runtime_config() {
		return array(
			'whatsappNumber'         => self::get_whatsapp_number(),
			'catalogUrl'             => self::get_catalog_url(),
			'youtubeVideoId'         => self::get_youtube_video_id(),
			'supportEmail'           => self::get_support_email(),
			'crmWebhookUrl'          => self::get_crm_webhook_url(),
			'defaultCountry'         => self::get_default_country(),
			'defaultProductInterest' => self::get_default_product_interest(),
			'enableTracking'         => self::is_tracking_enabled(),
			'enableCaseId'           => self::is_case_id_enabled(),
			'enableCrmWebhook'       => self::is_crm_webhook_enabled(),
			'thankYouUrl'            => self::get_thank_you_url(),
			'returnPageUrl'          => self::get_return_page_url(),
			'websiteUrl'             => self::get_website_url(),
		);
	}

	public static function sanitize_path_value( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '/';
		}

		if ( 0 !== strpos( $value, '/' ) ) {
			$value = '/' . ltrim( $value, '/' );
		}

		return $value;
	}

	public static function sanitize_site_brand_key( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9_-]/', '', $value );
		$value = substr( (string) $value, 0, 50 );

		return '' !== $value ? $value : 'yby_core';
	}

	public static function sanitize_site_brand_name( $value ) {
		$value = self::sanitize_display_text( $value );
		$value = substr( $value, 0, 100 );

		return '' !== $value ? $value : 'YBY';
	}

	public static function sanitize_case_id_brand_code( $value ) {
		$value = strtoupper( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^A-Z0-9]/', '', $value );

		if ( ! is_string( $value ) ) {
			return 'CORE';
		}

		if ( strlen( $value ) < 2 || strlen( $value ) > 8 ) {
			return 'CORE';
		}

		return $value;
	}

	public static function sanitize_website_url( $value ) {
		$value = esc_url_raw( (string) $value );

		if ( self::is_absolute_url( $value ) ) {
			return $value;
		}

		$fallback = function_exists( 'home_url' ) ? home_url( '/' ) : '/';

		return self::is_absolute_url( $fallback ) ? $fallback : '/';
	}

	public static function sanitize_email_value( $value, $fallback = '' ) {
		$email = sanitize_email( (string) $value );

		if ( is_email( $email ) ) {
			return $email;
		}

		$fallback = sanitize_email( (string) $fallback );

		return is_email( $fallback ) ? $fallback : '';
	}

	public static function sanitize_email_list_value( $value ) {
		$emails = array();
		$pieces  = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$pieces  = is_array( $pieces ) ? $pieces : array();

		foreach ( $pieces as $piece ) {
			$email = sanitize_email( $piece );

			if ( is_email( $email ) ) {
				$emails[ strtolower( $email ) ] = $email;
			}
		}

		return implode( ', ', array_values( $emails ) );
	}

	public static function sanitize_reply_to_policy( $value ) {
		$value   = sanitize_text_field( (string) $value );
		$allowed = array( 'auto', 'customer_email_only', 'disabled' );

		return in_array( $value, $allowed, true ) ? $value : 'auto';
	}

	public static function sanitize_subject_template( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = str_replace( array( "\r", "\n", "\t" ), ' ', $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/', '', $value );

		return trim( preg_replace( '/\s+/', ' ', (string) $value ) );
	}

	public static function sanitize_display_text( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = str_replace( array( "\r", "\n" ), ' ', $value );

		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}

	protected static function is_absolute_url( $value ) {
		$url = (string) $value;

		return '' !== $url && (bool) preg_match( '#^https?://#i', $url );
	}
}
