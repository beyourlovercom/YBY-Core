<?php
/**
 * Email Template OS global design settings.
 *
 * Source-only foundation for Andy Core v1.5.8. No admin hooks are registered
 * by this class until the runtime activation gate is reached.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Governed one-profile-per-site Email VI accessor.
 */
class YBY_Email_Design_Settings {

	/**
	 * Return stored settings merged with approved defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$defaults = YBY_Email_Template_Schema::design_defaults();
		$stored = get_option( YBY_Email_Template_Schema::DESIGN_OPTION, array() );

		return self::sanitize( wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults ) );
	}

	/**
	 * Sanitize a design settings payload.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$defaults = YBY_Email_Template_Schema::design_defaults();
		$colors = array(
			'canvas_color',
			'surface_color',
			'primary_color',
			'primary_text_color',
			'text_color',
			'muted_color',
			'border_color',
		);

		$result = $defaults;

		foreach ( $colors as $key ) {
			$value = sanitize_hex_color( (string) ( $settings[ $key ] ?? '' ) );
			$result[ $key ] = $value ? strtoupper( $value ) : $defaults[ $key ];
		}

		$result['content_width'] = min( 720, max( 480, absint( $settings['content_width'] ?? $defaults['content_width'] ) ) );
		$result['card_radius'] = min( 32, max( 0, absint( $settings['card_radius'] ?? $defaults['card_radius'] ) ) );
		$result['cta_radius'] = min( 32, max( 0, absint( $settings['cta_radius'] ?? $defaults['cta_radius'] ) ) );
		$result['font_stack'] = sanitize_text_field( (string) ( $settings['font_stack'] ?? $defaults['font_stack'] ) );
		$result['logo_url'] = esc_url_raw( (string) ( $settings['logo_url'] ?? '' ) );
		$result['footer_text'] = sanitize_text_field( (string) ( $settings['footer_text'] ?? '' ) );

		return $result;
	}
}
