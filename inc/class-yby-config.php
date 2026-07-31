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

	/**
	 * Return shared option defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'site_brand_key'                     => 'yby_core',
			'site_brand_name'                    => 'YBY',
			'case_id_brand_code'                 => 'CORE',
			'website_url'                        => self::default_home_url(),
			'brand_primary_color'                => '#1F2937',
			'brand_primary_text_color'           => '#FFFFFF',
			'brand_secondary_color'              => '#374151',
			'brand_surface_color'                => '#FFFFFF',
			'brand_text_color'                   => '#111827',
			'brand_muted_text_color'             => '#6B7280',
			'brand_border_color'                 => '#D1D5DB',
			'whatsapp_number'                    => '',
			'catalog_url'                        => '',
			'youtube_video_id'                   => '',
			'support_email'                      => '',
			'crm_webhook_url'                    => '',
			'default_country'                    => '',
			'default_product_interest'           => '',
			'thank_you_url'                      => '/',
			'return_page_url'                    => '/',
			'whatsapp_message_template'          => '',
			'lead_notification_subject_template' => '[New Inquiry] {country} | {product_interest} | {case_id}',
			'inquiry_email_title'                => 'Website Inquiry',
			'email_company_name'                 => '',
			'email_company_website'              => '',
			'email_company_phone'                => '',
			'email_company_whatsapp'             => '',
			'email_footer_copyright'             => '',
			'email_logo_url'                     => '',
			'email_reverse_logo_url'             => '',
			'enable_tracking'                    => true,
			'enable_case_id'                     => true,
			'enable_crm_webhook'                 => false,
		);
	}

	/**
	 * Return the legacy lead recipient fallback.
	 *
	 * @return string
	 */
	public static function default_lead_recipient_email() {
		return self::get_admin_email_fallback();
	}

	/**
	 * Return sanitized plugin options merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options() {
		$options = get_option( YBY_Helpers::option_key(), array() );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return self::sanitize( wp_parse_args( $options, self::defaults() ) );
	}

	/**
	 * Sanitize settings before persistence.
	 *
	 * @param array<string, mixed> $options Raw options.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $options ) {
		$options  = is_array( $options ) ? $options : array();
		$defaults = self::defaults();

		return array(
			'site_brand_key'                     => self::sanitize_site_brand_key( $options['site_brand_key'] ?? $defaults['site_brand_key'] ),
			'site_brand_name'                    => self::sanitize_site_brand_name( $options['site_brand_name'] ?? $defaults['site_brand_name'] ),
			'case_id_brand_code'                 => self::sanitize_case_id_brand_code( $options['case_id_brand_code'] ?? $defaults['case_id_brand_code'] ),
			'website_url'                        => self::sanitize_website_url( $options['website_url'] ?? $defaults['website_url'] ),
			'brand_primary_color'                => self::sanitize_hex_color_value( $options['brand_primary_color'] ?? $defaults['brand_primary_color'], $defaults['brand_primary_color'] ),
			'brand_primary_text_color'           => self::sanitize_hex_color_value( $options['brand_primary_text_color'] ?? $defaults['brand_primary_text_color'], $defaults['brand_primary_text_color'] ),
			'brand_secondary_color'              => self::sanitize_hex_color_value( $options['brand_secondary_color'] ?? $defaults['brand_secondary_color'], $defaults['brand_secondary_color'] ),
			'brand_surface_color'                => self::sanitize_hex_color_value( $options['brand_surface_color'] ?? $defaults['brand_surface_color'], $defaults['brand_surface_color'] ),
			'brand_text_color'                   => self::sanitize_hex_color_value( $options['brand_text_color'] ?? $defaults['brand_text_color'], $defaults['brand_text_color'] ),
			'brand_muted_text_color'             => self::sanitize_hex_color_value( $options['brand_muted_text_color'] ?? $defaults['brand_muted_text_color'], $defaults['brand_muted_text_color'] ),
			'brand_border_color'                 => self::sanitize_hex_color_value( $options['brand_border_color'] ?? $defaults['brand_border_color'], $defaults['brand_border_color'] ),
			'whatsapp_number'                    => self::sanitize_display_text( $options['whatsapp_number'] ?? $defaults['whatsapp_number'] ),
			'catalog_url'                        => self::sanitize_absolute_url_value( $options['catalog_url'] ?? $defaults['catalog_url'] ),
			'youtube_video_id'                   => self::sanitize_video_id( $options['youtube_video_id'] ?? $defaults['youtube_video_id'] ),
			'support_email'                      => self::sanitize_email_value( $options['support_email'] ?? $defaults['support_email'] ),
			'crm_webhook_url'                    => self::sanitize_absolute_url_value( $options['crm_webhook_url'] ?? $defaults['crm_webhook_url'] ),
			'default_country'                    => self::sanitize_display_text( $options['default_country'] ?? $defaults['default_country'] ),
			'default_product_interest'           => self::sanitize_display_text( $options['default_product_interest'] ?? $defaults['default_product_interest'] ),
			'thank_you_url'                      => self::sanitize_path_or_absolute_url_value( $options['thank_you_url'] ?? $defaults['thank_you_url'], '/' ),
			'return_page_url'                    => self::sanitize_path_or_absolute_url_value( $options['return_page_url'] ?? $defaults['return_page_url'], '/' ),
			'whatsapp_message_template'          => self::sanitize_whatsapp_message_template( $options['whatsapp_message_template'] ?? $defaults['whatsapp_message_template'] ),
			'lead_notification_subject_template' => self::sanitize_subject_template( $options['lead_notification_subject_template'] ?? $defaults['lead_notification_subject_template'] ),
			'inquiry_email_title'                => self::sanitize_display_text( $options['inquiry_email_title'] ?? $defaults['inquiry_email_title'] ),
			'email_company_name'                 => self::sanitize_display_text( $options['email_company_name'] ?? $defaults['email_company_name'] ),
			'email_company_website'              => self::sanitize_absolute_url_value( $options['email_company_website'] ?? $defaults['email_company_website'] ),
			'email_company_phone'                => self::sanitize_display_text( $options['email_company_phone'] ?? $defaults['email_company_phone'] ),
			'email_company_whatsapp'             => self::sanitize_display_text( $options['email_company_whatsapp'] ?? $defaults['email_company_whatsapp'] ),
			'email_footer_copyright'             => self::sanitize_display_text( $options['email_footer_copyright'] ?? $defaults['email_footer_copyright'] ),
			'email_logo_url'                     => self::sanitize_absolute_url_value( $options['email_logo_url'] ?? $defaults['email_logo_url'] ),
			'email_reverse_logo_url'             => self::sanitize_absolute_url_value( $options['email_reverse_logo_url'] ?? $defaults['email_reverse_logo_url'] ),
			'enable_tracking'                    => ! empty( $options['enable_tracking'] ),
			'enable_case_id'                     => ! empty( $options['enable_case_id'] ),
			'enable_crm_webhook'                 => ! empty( $options['enable_crm_webhook'] ),
		);
	}

	/**
	 * Read a single option value.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
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

	public static function get_website_url() {
		return (string) self::get( 'website_url' );
	}

	public static function get_brand_primary_color() {
		return (string) self::get( 'brand_primary_color' );
	}

	public static function get_brand_primary_text_color() {
		return (string) self::get( 'brand_primary_text_color' );
	}

	public static function get_brand_secondary_color() {
		return (string) self::get( 'brand_secondary_color' );
	}

	public static function get_brand_surface_color() {
		return (string) self::get( 'brand_surface_color' );
	}

	public static function get_brand_text_color() {
		return (string) self::get( 'brand_text_color' );
	}

	public static function get_brand_muted_text_color() {
		return (string) self::get( 'brand_muted_text_color' );
	}

	public static function get_brand_border_color() {
		return (string) self::get( 'brand_border_color' );
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

	public static function get_whatsapp_message_template() {
		return (string) self::get( 'whatsapp_message_template' );
	}

	public static function get_lead_notification_subject_template() {
		return (string) self::get( 'lead_notification_subject_template' );
	}

	public static function get_inquiry_email_title() {
		return (string) self::get( 'inquiry_email_title' );
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

	/**
	 * Return the primary notification recipient with neutral fallback behavior.
	 *
	 * @return string
	 */
	public static function get_lead_notification_primary_recipient_email() {
		$stored = get_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), '' );

		return self::sanitize_email_value( $stored, self::get_admin_email_fallback() );
	}

	public static function get_lead_notification_cc_recipient_emails() {
		return self::sanitize_email_list_value(
			get_option( YBY_Helpers::lead_notification_cc_recipient_option_key(), '' )
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
		return self::sanitize_email_value( $value, $fallback );
	}

	/**
	 * Return safe public runtime config.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_runtime_config() {
		$site_profile  = class_exists( 'YBY_Site_Profile' ) ? YBY_Site_Profile::get_profile() : array();
		$brand_profile = class_exists( 'YBY_Brand_Profile' ) ? YBY_Brand_Profile::get_profile() : array();

		return array(
			'siteBrandKey'          => isset( $site_profile['site_brand_key'] ) ? (string) $site_profile['site_brand_key'] : self::sanitize_site_brand_key( self::get_site_brand_key() ),
			'siteBrandName'         => isset( $brand_profile['site_brand_name'] ) ? (string) $brand_profile['site_brand_name'] : self::sanitize_site_brand_name( self::get_site_brand_name() ),
			'caseIdBrandCode'       => isset( $site_profile['case_id_brand_code'] ) ? (string) $site_profile['case_id_brand_code'] : self::sanitize_case_id_brand_code( self::get_case_id_brand_code() ),
			'websiteUrl'            => isset( $brand_profile['website_url'] ) ? (string) $brand_profile['website_url'] : self::sanitize_website_url( self::get_website_url() ),
			'whatsappNumber'        => isset( $brand_profile['whatsapp_number'] ) ? (string) $brand_profile['whatsapp_number'] : self::get_whatsapp_number(),
			'catalogUrl'            => isset( $brand_profile['catalog_url'] ) ? (string) $brand_profile['catalog_url'] : self::get_catalog_url(),
			'youtubeVideoId'        => isset( $brand_profile['youtube_video_id'] ) ? (string) $brand_profile['youtube_video_id'] : self::get_youtube_video_id(),
			'supportEmail'          => isset( $brand_profile['support_email'] ) ? (string) $brand_profile['support_email'] : self::get_support_email(),
			'defaultCountry'        => isset( $brand_profile['default_country'] ) ? (string) $brand_profile['default_country'] : self::get_default_country(),
			'defaultProductInterest'=> isset( $brand_profile['default_product_interest'] ) ? (string) $brand_profile['default_product_interest'] : self::get_default_product_interest(),
			'thankYouUrl'           => isset( $brand_profile['thank_you_url'] ) ? (string) $brand_profile['thank_you_url'] : self::get_thank_you_url(),
			'returnPageUrl'         => isset( $brand_profile['return_page_url'] ) ? (string) $brand_profile['return_page_url'] : self::get_return_page_url(),
			'whatsappMessageTemplate' => isset( $brand_profile['whatsapp_message_template'] ) ? (string) $brand_profile['whatsapp_message_template'] : self::get_whatsapp_message_template(),
			'enableTracking'        => self::is_tracking_enabled(),
			'enableCaseId'          => self::is_case_id_enabled(),
			'enableCrmWebhook'      => self::is_crm_webhook_enabled(),
		);
	}

	public static function sanitize_path_value( $value ) {
		return self::sanitize_path_or_absolute_url_value( $value, '/' );
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

		if ( ! is_string( $value ) || strlen( $value ) < 2 || strlen( $value ) > 8 ) {
			return 'CORE';
		}

		return $value;
	}

	public static function sanitize_website_url( $value ) {
		$value = self::sanitize_absolute_url_value( $value );

		if ( '' !== $value ) {
			return $value;
		}

		return self::default_home_url();
	}

	public static function sanitize_hex_color_value( $value, $fallback = '#1F2937' ) {
		$fallback = strtoupper( (string) $fallback );
		$fallback = preg_match( '/^#[A-F0-9]{6}$/', $fallback ) ? $fallback : '#1F2937';
		$value    = strtoupper( trim( (string) $value ) );

		if ( '' === $value ) {
			return $fallback;
		}

		if ( '#' !== substr( $value, 0, 1 ) ) {
			$value = '#' . $value;
		}

		return preg_match( '/^#[A-F0-9]{6}$/', $value ) ? $value : $fallback;
	}

	public static function sanitize_absolute_url_value( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value || preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
			return '';
		}

		$url = esc_url_raw( $value );

		return self::is_valid_absolute_url( $url ) ? $url : '';
	}

	public static function sanitize_path_or_absolute_url_value( $value, $fallback = '/' ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return (string) $fallback;
		}

		$absolute = self::sanitize_absolute_url_value( $value );

		if ( '' !== $absolute ) {
			return $absolute;
		}

		$path = self::sanitize_relative_path_value( $value );

		return '' !== $path ? $path : (string) $fallback;
	}

	public static function sanitize_relative_path_value( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
			return '';
		}

		if ( '/' !== substr( $value, 0, 1 ) ) {
			return '';
		}

		if ( preg_match( '#^/[\\\\/]#', $value ) ) {
			return '';
		}

		$parsed = self::parse_url_value( $value );

		if ( false === $parsed || ! is_array( $parsed ) ) {
			return '';
		}

		foreach ( array( 'scheme', 'host', 'user', 'pass', 'port' ) as $forbidden_key ) {
			if ( isset( $parsed[ $forbidden_key ] ) && '' !== (string) $parsed[ $forbidden_key ] ) {
				return '';
			}
		}

		$path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '';

		if ( '' === $path || '/' !== substr( $path, 0, 1 ) ) {
			return '';
		}

		if ( preg_match( '#^/[\\\\/]#', $path ) ) {
			return '';
		}

		$normalized = $path;

		if ( isset( $parsed['query'] ) && '' !== (string) $parsed['query'] ) {
			$normalized .= '?' . $parsed['query'];
		}

		if ( isset( $parsed['fragment'] ) && '' !== (string) $parsed['fragment'] ) {
			$normalized .= '#' . $parsed['fragment'];
		}

		return $normalized;
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
		$pieces = preg_split( '/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$pieces = is_array( $pieces ) ? $pieces : array();

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
		$value = self::sanitize_display_text( $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/', '', $value );

		return '' !== $value ? $value : '[New Inquiry] {country} | {product_interest} | {case_id}';
	}

	public static function sanitize_whatsapp_message_template( $value ) {
		$value = (string) $value;
		$value = str_replace( array( '\\r\\n', '\\n', '\\r' ), "\n", $value );
		$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
		$value = strip_tags( $value );
		$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value );
		$value = preg_replace( "/[ \t]+\n/", "\n", $value );
		$value = trim( $value );
		$value = preg_replace( "/\n{3,}/", "\n\n", $value );
		$value = substr( (string) $value, 0, 4000 );

		return trim( (string) $value );
	}

	public static function sanitize_display_text( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = str_replace( array( "\r", "\n" ), ' ', $value );

		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}

	public static function sanitize_video_id( $value ) {
		$value = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );

		return substr( (string) $value, 0, 32 );
	}

	protected static function is_absolute_url( $value ) {
		return self::is_valid_absolute_url( (string) $value );
	}

	/**
	 * Determine whether an absolute URL has a safe HTTP(S) structure.
	 *
	 * @param string $value URL value.
	 * @return bool
	 */
	protected static function is_valid_absolute_url( $value ) {
		$url = (string) $value;

		if ( '' === $url || preg_match( '/[\x00-\x1F\x7F]/', $url ) ) {
			return false;
		}

		$parsed = self::parse_url_value( $url );

		if ( false === $parsed || ! is_array( $parsed ) ) {
			return false;
		}

		$scheme = isset( $parsed['scheme'] ) ? strtolower( (string) $parsed['scheme'] ) : '';
		$host   = isset( $parsed['host'] ) ? (string) $parsed['host'] : '';

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host ) {
			return false;
		}

		if ( array_key_exists( 'user', $parsed ) || array_key_exists( 'pass', $parsed ) ) {
			return false;
		}

		if ( isset( $parsed['port'] ) ) {
			$port = (int) $parsed['port'];

			if ( $port < 1 || $port > 65535 ) {
				return false;
			}
		}

		return self::is_valid_url_host( $host );
	}

	/**
	 * Determine whether a URL host is a domain, IP address, or localhost.
	 *
	 * @param string $host URL host.
	 * @return bool
	 */
	protected static function is_valid_url_host( $host ) {
		$host = (string) $host;

		if ( '' === $host || preg_match( '/[\s\x00-\x1F\x7F\/\\\\]/', $host ) ) {
			return false;
		}

		if ( '[' === substr( $host, 0, 1 ) && ']' === substr( $host, -1 ) ) {
			$host = substr( $host, 1, -1 );
		}

		if ( '' === $host || '.' === $host || '..' === $host || false !== strpos( $host, '..' ) ) {
			return false;
		}

		if ( 'localhost' === strtolower( $host ) ) {
			return true;
		}

		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return false !== filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}

	protected static function parse_url_value( $value ) {
		if ( function_exists( 'wp_parse_url' ) ) {
			return wp_parse_url( (string) $value );
		}

		return parse_url( (string) $value );
	}

	protected static function default_home_url() {
		$fallback = function_exists( 'home_url' ) ? home_url( '/' ) : '/';

		return self::is_absolute_url( $fallback ) ? (string) $fallback : '/';
	}

	protected static function get_admin_email_fallback() {
		$admin_email = get_option( 'admin_email', '' );

		return self::sanitize_email_value( $admin_email );
	}
}
