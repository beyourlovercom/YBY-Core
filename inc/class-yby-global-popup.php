<?php
/** Core-owned global popup renderer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Global_Popup {
	protected $manager;
	protected $renderer;
	const OPTION = 'yby_core_popup_settings';

	public function __construct( $manager, $renderer ) { $this->manager = $manager; $this->renderer = $renderer; }

	public static function defaults() {
		return array(
			'inquiry_title' => '', 'inquiry_subtitle' => '', 'inquiry_image' => '',
			'subscribe_title' => 'Stay in the loop', 'subscribe_subtitle' => 'Get useful updates from us.', 'subscribe_image' => '',
			'inquiry_custom_css' => '', 'subscribe_custom_css' => '',
			'inquiry_shortcode_custom_css' => '', 'subscribe_shortcode_custom_css' => '',
			'subscribe_shortcode_show_title' => 0, 'subscribe_shortcode_show_subtitle' => 0, 'subscribe_shortcode_show_image' => 0,
		);
	}
	public static function settings() { return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() ); }
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array(); $out = array();
		foreach ( array( 'inquiry_title','inquiry_subtitle','subscribe_title','subscribe_subtitle' ) as $key ) { $out[ $key ] = sanitize_text_field( $raw[ $key ] ?? '' ); }
		foreach ( array( 'inquiry_image','subscribe_image' ) as $key ) { $out[ $key ] = esc_url_raw( $raw[ $key ] ?? '' ); }
		foreach ( array( 'subscribe_shortcode_show_title', 'subscribe_shortcode_show_subtitle', 'subscribe_shortcode_show_image' ) as $key ) { $out[ $key ] = empty( $raw[ $key ] ) ? 0 : 1; }
		foreach ( array( 'inquiry_custom_css', 'subscribe_custom_css', 'inquiry_shortcode_custom_css', 'subscribe_shortcode_custom_css' ) as $key ) { $out[ $key ] = self::sanitize_custom_css( $raw[ $key ] ?? '' ); }
		return wp_parse_args( $out, self::defaults() );
	}
	public static function sanitize_custom_css( $css ) {
		$css = is_scalar( $css ) ? (string) $css : '';
		$css = preg_replace( '/<\/?style\b[^>]*>/i', '', $css );
		$css = preg_replace( '/(?:expression\s*\(|-moz-binding\s*:|behavior\s*:|(?:java|vb)script\s*:)/i', '', $css );
		return is_string( $css ) ? $css : '';
	}
	public function render() {
		$settings = self::sanitize( self::settings() );
		$preset_id = 'irrigation_quick_inquiry';
		if ( function_exists( 'apply_filters' ) ) {
			$preset_id = apply_filters( 'yby_inquiry_default_preset', $preset_id, array( 'context' => 'global_popup' ) );
		}
		$preset_id = sanitize_key( $preset_id );
		$preset    = $this->manager->get_preset_manager()->get_preset( $preset_id );
		if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) { return; }
		$fields = $this->manager->get_field_manager()->get_fields(); $ordered = array();
		foreach ( $preset['fields'] as $id ) { if ( isset( $fields[ $id ] ) && ! empty( $fields[ $id ]['enabled'] ) ) { $ordered[] = $fields[ $id ]; } }
		$inquiry = $this->renderer->render_modal( $preset, $ordered, array( 'id' => 'yby-global-inquiry-popup', 'title' => $settings['inquiry_title'], 'subtitle' => $settings['inquiry_subtitle'], 'image' => $settings['inquiry_image'], 'class' => array( 'yby-global-popup__legacy-form' ) ) );
		$inquiry = preg_replace( '/class="yby-inquiry-modal/', 'class="yby-global-popup yby-global-popup--inquiry yby-inquiry-modal', $inquiry, 1 );
		$inquiry = str_replace( 'data-yby-inquiry-modal ', 'data-yby-inquiry-modal data-yby-global-popup="inquiry" ', $inquiry );
		$theme = class_exists( 'YBY_Brand_Profile' ) ? YBY_Brand_Profile::get_theme_config() : array();
		$image = $settings['subscribe_image'] ?: (string) ( $theme['subscribeImage'] ?? '' );
		$subscribe = '<div id="yby-global-subscribe-popup" class="yby-global-popup yby-global-popup--subscribe" data-yby-global-popup="subscribe" aria-hidden="true" hidden><div class="yby-global-popup__backdrop" data-yby-popup-close></div><div class="yby-global-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="yby-global-subscribe-title" tabindex="-1"><button type="button" class="yby-global-popup__close" data-yby-popup-close aria-label="'.esc_attr__( 'Close popup', 'yby-core' ).'"><span aria-hidden="true">&times;</span></button>'.( $image ? '<div class="yby-global-popup__media"><img src="'.esc_url( $image ).'" alt="" loading="lazy"></div>' : '' ).'<div class="yby-global-popup__content"><h2 id="yby-global-subscribe-title">'.esc_html( $settings['subscribe_title'] ).'</h2><p>'.esc_html( $settings['subscribe_subtitle'] ).'</p><form data-yby-subscribe-contract novalidate><label>Email<input type="email" name="email" required></label><button type="submit">Subscribe</button><p role="status" data-yby-subscribe-status>Subscribe submission is not configured yet.</p></form></div></div></div>';
		echo $inquiry . $subscribe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
