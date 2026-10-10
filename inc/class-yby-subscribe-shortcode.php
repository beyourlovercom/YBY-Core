<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class YBY_Subscribe_Shortcode {
	public function register() {
		add_shortcode( 'yby_subscribe', array( $this, 'render' ) );
	}

	public function render( $attributes = array() ) {
		$settings = YBY_Global_Popup::sanitize( YBY_Global_Popup::settings() );
		$a = shortcode_atts( array( 'title' => $settings['subscribe_title'], 'subtitle' => $settings['subscribe_subtitle'], 'button_label' => 'SUBSCRIBE', 'image' => $settings['subscribe_image'], 'class' => '' ), is_array( $attributes ) ? $attributes : array(), 'yby_subscribe' );
		$title = sanitize_text_field( $a['title'] ); $subtitle = sanitize_text_field( $a['subtitle'] ); $button = sanitize_text_field( $a['button_label'] );
		$image = esc_url_raw( $a['image'] ); $classes = array( 'yby-shortcode-subscribe' ); foreach ( preg_split( '/\s+/', (string) $a['class'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) { $c = sanitize_html_class( $c ); if ( '' !== $c ) { $classes[] = $c; } }
		$out = '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" data-yby-subscribe-inline>';
		if ( ! empty( $settings['subscribe_shortcode_show_image'] ) && '' !== $image ) { $out .= '<img class="yby-shortcode-subscribe__image" src="' . esc_url( $image ) . '" alt="" loading="lazy">'; }
		if ( ! empty( $settings['subscribe_shortcode_show_title'] ) ) { $out .= '<h2 class="yby-shortcode-subscribe__title">' . esc_html( $title ) . '</h2>'; }
		if ( ! empty( $settings['subscribe_shortcode_show_subtitle'] ) ) { $out .= '<p class="yby-shortcode-subscribe__subtitle">' . esc_html( $subtitle ) . '</p>'; }
		$out .= '<form data-yby-subscribe-contract data-yby-newsletter-enabled="' . ( YBY_Newsletter_REST_Controller::enabled() && YBY_Newsletter_REST_Controller::outbound_enabled() ? '1' : '0' ) . '" data-yby-newsletter-endpoint="' . esc_url( rest_url( 'andy-core/v1/newsletter/' ) ) . '" novalidate><input type="email" name="email" required placeholder="EMAIL ADDRESS" aria-label="EMAIL ADDRESS"><label class="yby-newsletter-consent"><input type="checkbox" name="marketing_consent" value="1" required><span>' . esc_html__( 'I agree to receive marketing emails. I can unsubscribe at any time.', 'yby-core' ) . '</span></label><label class="yby-newsletter-honeypot" aria-hidden="true" tabindex="-1">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label><button type="submit">' . esc_html( $button ) . '</button><p role="status" data-yby-subscribe-status aria-live="polite"></p></form></div>';
		return $out;
	}
}
