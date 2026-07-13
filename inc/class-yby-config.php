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
	 * Return default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'whatsapp_number'          => '',
			'catalog_url'              => '',
			'youtube_video_id'         => '',
			'support_email'            => '',
			'crm_webhook_url'          => '',
			'default_country'          => 'Tanzania',
			'default_product_interest' => 'irrigation system solution',
			'thank_you_url'            => '/lp/thank-you-irrigation-solution/',
			'return_page_url'          => '/lp/irrigation-solution/',
			'website_url'              => function_exists( 'home_url' ) ? home_url( '/' ) : '/',
			'enable_tracking'          => true,
			'enable_case_id'           => true,
			'enable_crm_webhook'       => false,
		);
	}

	/**
	 * Return the governed default lead recipient email.
	 *
	 * @return string
	 */
	public static function default_lead_recipient_email() {
		return 'beyourlovercom@gmail.com';
	}

	/**
	 * Get plugin options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options() {
		$options = get_option( YBY_Helpers::option_key(), array() );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return wp_parse_args( $options, self::defaults() );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array<string, mixed> $options Raw options.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $options ) {
		$options  = is_array( $options ) ? $options : array();
		$defaults = self::defaults();

		return array(
			'whatsapp_number'          => sanitize_text_field( $options['whatsapp_number'] ?? $defaults['whatsapp_number'] ),
			'catalog_url'              => esc_url_raw( $options['catalog_url'] ?? $defaults['catalog_url'] ),
			'youtube_video_id'         => sanitize_text_field( $options['youtube_video_id'] ?? $defaults['youtube_video_id'] ),
			'support_email'            => sanitize_email( $options['support_email'] ?? $defaults['support_email'] ),
			'crm_webhook_url'          => esc_url_raw( $options['crm_webhook_url'] ?? $defaults['crm_webhook_url'] ),
			'default_country'          => sanitize_text_field( $options['default_country'] ?? $defaults['default_country'] ),
			'default_product_interest' => sanitize_text_field( $options['default_product_interest'] ?? $defaults['default_product_interest'] ),
			'thank_you_url'            => self::sanitize_path_value( $options['thank_you_url'] ?? $defaults['thank_you_url'] ),
			'return_page_url'          => self::sanitize_path_value( $options['return_page_url'] ?? $defaults['return_page_url'] ),
			'website_url'              => esc_url_raw( $options['website_url'] ?? $defaults['website_url'] ),
			'enable_tracking'          => ! empty( $options['enable_tracking'] ),
			'enable_case_id'           => ! empty( $options['enable_case_id'] ),
			'enable_crm_webhook'       => ! empty( $options['enable_crm_webhook'] ),
		);
	}

	/**
	 * Get option by key.
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

	/**
	 * Return the saved lead recipient email.
	 *
	 * @return string
	 */
	public static function get_lead_recipient_email() {
		$email = get_option( YBY_Helpers::lead_recipient_option_key(), self::default_lead_recipient_email() );

		return self::sanitize_lead_recipient_email( $email, self::default_lead_recipient_email() );
	}

	/**
	 * Sanitize a lead recipient email value.
	 *
	 * @param string $value Raw email value.
	 * @param string $fallback Fallback email.
	 * @return string
	 */
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

	/**
	 * Return frontend-safe runtime config.
	 *
	 * @return array<string, mixed>
	 */
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

	/**
	 * Sanitize a relative URL.
	 *
	 * @param string $value URL value.
	 * @return string
	 */
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

	/**
	 * Sanitize a single email value.
	 *
	 * @param mixed  $value Raw email.
	 * @param string $fallback Fallback email.
	 * @return string
	 */
	public static function sanitize_email_value( $value, $fallback = '' ) {
		$email = sanitize_email( (string) $value );

		if ( is_email( $email ) ) {
			return $email;
		}

		$fallback = sanitize_email( (string) $fallback );

		return is_email( $fallback ) ? $fallback : '';
	}

	/**
	 * Sanitize a comma-separated email list.
	 *
	 * @param mixed $value Raw list.
	 * @return string
	 */
	public static function sanitize_email_list_value( $value ) {
		$emails  = array();
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

	/**
	 * Sanitize reply-to policy.
	 *
	 * @param mixed $value Raw policy.
	 * @return string
	 */
	public static function sanitize_reply_to_policy( $value ) {
		$value = sanitize_text_field( (string) $value );
		$allowed = array( 'auto', 'customer_email_only', 'disabled' );

		return in_array( $value, $allowed, true ) ? $value : 'auto';
	}
}
