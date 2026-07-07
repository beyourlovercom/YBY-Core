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
			'enable_tracking'          => true,
			'enable_case_id'           => true,
			'enable_crm_webhook'       => false,
		);
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

	public static function is_tracking_enabled() {
		return (bool) self::get( 'enable_tracking' );
	}

	public static function is_case_id_enabled() {
		return (bool) self::get( 'enable_case_id' );
	}

	public static function is_crm_webhook_enabled() {
		return (bool) self::get( 'enable_crm_webhook' );
	}
}
