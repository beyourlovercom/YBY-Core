<?php
/** Subscribe popup settings and renderer; Inquiry remains owned by YBY_Global_Popup_Dock. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Global_Popup {
	const OPTION = 'yby_core_popup_settings';

	public static function defaults() { return array( 'inquiry_title' => '', 'inquiry_subtitle' => '', 'inquiry_image' => '', 'subscribe_title' => 'Stay in the loop', 'subscribe_subtitle' => 'Get useful updates from us.', 'subscribe_image' => '' ); }
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array(); $out = array();
		foreach ( array( 'inquiry_title', 'inquiry_subtitle', 'subscribe_title', 'subscribe_subtitle' ) as $key ) { $out[ $key ] = sanitize_text_field( $raw[ $key ] ?? '' ); }
		foreach ( array( 'inquiry_image', 'subscribe_image' ) as $key ) { $out[ $key ] = esc_url_raw( $raw[ $key ] ?? '' ); }
		return wp_parse_args( $out, self::defaults() );
	}
	public static function settings() { return self::sanitize( get_option( self::OPTION, array() ) ); }
	public static function render() {
		$settings = self::settings(); $theme = class_exists( 'YBY_Brand_Profile' ) ? YBY_Brand_Profile::get_theme_config() : array(); $image = $settings['subscribe_image'] ?: (string) ( $theme['subscribeImage'] ?? '' );
		echo '<div id="yby-global-subscribe-popup" class="yby-global-popup yby-global-popup--subscribe" data-yby-global-popup="subscribe" aria-hidden="true" hidden><div class="yby-global-popup__backdrop" data-yby-popup-close></div><div class="yby-global-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="yby-global-subscribe-title" tabindex="-1"><button type="button" class="yby-global-popup__close" data-yby-popup-close aria-label="' . esc_attr__( 'Close popup', 'yby-core' ) . '"><span aria-hidden="true">&times;</span></button>' . ( $image ? '<div class="yby-global-popup__media"><img src="' . esc_url( $image ) . '" alt="" loading="lazy"></div>' : '' ) . '<div class="yby-global-popup__content"><h2 id="yby-global-subscribe-title">' . esc_html( $settings['subscribe_title'] ) . '</h2><p>' . esc_html( $settings['subscribe_subtitle'] ) . '</p><form data-yby-subscribe-contract novalidate><label>' . esc_html__( 'Email', 'yby-core' ) . '<input type="email" name="email" required></label><button type="submit">' . esc_html__( 'Subscribe', 'yby-core' ) . '</button><p role="status" data-yby-subscribe-status>' . esc_html__( 'Subscribe submission is not configured yet.', 'yby-core' ) . '</p></form></div></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
