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
			// Visual template and business preset are independent, site-local choices. Legacy defaults are unchanged.
			'inquiry_template' => 'classic', 'inquiry_preset' => 'irrigation_quick_inquiry',
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
		$template_raw = $raw['inquiry_template'] ?? 'classic';
		$template = is_scalar( $template_raw ) ? sanitize_key( (string) $template_raw ) : 'classic';
		$out['inquiry_template'] = in_array( $template, array( 'classic', 'premium_v22', 'oem_original' ), true ) ? $template : 'classic';
		$preset_raw = $raw['inquiry_preset'] ?? 'irrigation_quick_inquiry';
		$preset_id = is_scalar( $preset_raw ) ? sanitize_key( (string) $preset_raw ) : 'irrigation_quick_inquiry';
		$out['inquiry_preset'] = in_array( $preset_id, array( 'irrigation_quick_inquiry', 'bottle_oem_inquiry', 'bottle_wholesale_inquiry' ), true ) ? $preset_id : 'irrigation_quick_inquiry';
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
	/**
	 * Respect Inquiry OS page_profiles for global business presets.
	 * A business-specific preset must not be shown on an unrelated landing
	 * page. No redirect, writes or guessed campaign/page ownership.
	 */
	public static function preset_allowed_on_page( $preset ) {
		$profiles = isset( $preset['page_profiles'] ) && is_array( $preset['page_profiles'] )
			? array_values( array_filter( array_map( 'sanitize_key', $preset['page_profiles'] ) ) )
			: array();
		if ( ! $profiles ) { return true; }
		if ( ! class_exists( 'YBY_Page_Profile' ) ) { return false; }
		$current = YBY_Page_Profile::get_current_profile();
		$id = is_array( $current ) && isset( $current['profileId'] ) && is_scalar( $current['profileId'] )
			? sanitize_key( (string) $current['profileId'] ) : '';
		return '' !== $id && in_array( $id, $profiles, true );
	}

	public static function is_oem_exact_preview() {
        return isset( $_GET['andy_oem_exact_preview'] )
            && '1' === sanitize_text_field( wp_unslash( $_GET['andy_oem_exact_preview'] ) )
            && is_user_logged_in() && current_user_can( 'manage_options' )
            && isset( $_SERVER['HTTP_HOST'] )
            && 'dev.beyourlover.com' === strtolower( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) );
    }

    public function render() {
        $settings = self::sanitize( self::settings() );
        $oem_preview = self::is_oem_exact_preview();
        if ( $oem_preview ) {
            // Deliberate visual-only preview, never persist options or change ads.
            $settings['inquiry_template'] = 'oem_original';
            $settings['inquiry_preset'] = 'bottle_oem_inquiry';
        }
		$preset = $this->manager->get_preset_manager()->get_preset( $settings['inquiry_preset'] );
        if ( $oem_preview && ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) ) {
            $defaults = $this->manager->get_preset_manager()->defaults();
            $preset = isset( $defaults['bottle_oem_inquiry'] ) ? $defaults['bottle_oem_inquiry'] : null;
        }
        if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) { return; }
        if ( $oem_preview ) { $preset['page_profiles'] = array(); }
		$inquiry = '';
		if ( self::preset_allowed_on_page( $preset ) ) {
		$fields = $this->manager->get_field_manager()->get_fields(); $ordered = array();
		foreach ( $preset['fields'] as $id ) { if ( isset( $fields[ $id ] ) && ! empty( $fields[ $id ]['enabled'] ) ) { $ordered[] = $fields[ $id ]; } }
        $exact_oem = 'oem_original' === $settings['inquiry_template'] && 'bottle_oem_inquiry' === $settings['inquiry_preset'];
        $premium = 'premium_v22' === $settings['inquiry_template'];
        if ( $exact_oem ) {
            // Production bottle OEM component attributes; canonical renderer owns the HTML.
            $settings['inquiry_title'] = 'glass-bottle-oem';
            $settings['inquiry_subtitle'] = '';
            $settings['inquiry_image'] = 'https://ybybottle.com/wp-content/uploads/2026/07/hero-custom-spirits-bottle.webp';
        }
		$attributes = array( 'id' => 'yby-global-inquiry-popup', 'title' => $settings['inquiry_title'], 'subtitle' => $settings['inquiry_subtitle'], 'image' => $settings['inquiry_image'], 'class' => array( 'yby-global-popup__legacy-form' ) );
        if ( $exact_oem ) {
            $attributes['submit_label'] = 'Submit Bottle Project';
            $attributes['mobile_submit_label'] = 'Submit Bottle Project';
            $attributes['class'] = array( 'andy-popup-template--oem-original' );
        }
        if ( $premium ) {
            // Rendering only: no alternate submit endpoint, tracking protocol or field schema.
			$attributes['class'][] = 'andy-popup-template--premium-v22';
			$attributes['layout'] = 'premium_split';
			$attributes['mobile_layout'] = 'premium_stack';
		}
		$mobile_fields = array();
        if ( $premium ) {
            // The existing renderer owns mobile field validation; keep the original field registry and JS.
			foreach ( $preset['mobile_fields'] ?? array() as $id ) {
				if ( isset( $fields[ $id ] ) && ! empty( $fields[ $id ]['enabled'] ) ) { $mobile_fields[] = $fields[ $id ]; }
			}
		}
		$inquiry = $this->renderer->render_modal( $preset, $ordered, $attributes, $mobile_fields );
		$inquiry = preg_replace( '/class="yby-inquiry-modal/', 'class="yby-global-popup yby-global-popup--inquiry yby-inquiry-modal', $inquiry, 1 );
		$inquiry = str_replace( 'data-yby-inquiry-modal ', 'data-yby-inquiry-modal data-yby-global-popup="inquiry" ', $inquiry );
        if ( $exact_oem ) {
            $decor = '<div class="andy-oem-brand" aria-hidden="true"><div class="andy-oem-brand__identity"><b class="andy-oem-brand__mark">Y</b> YBY Bottle</div><div class="andy-oem-brand__story"><small>GLASS BOTTLE OEM &amp; CUSTOM DEVELOPMENT</small><h3>Premium Glass Bottles for Global Brands</h3><ul><li>Custom design &amp; mold development</li><li>Distinctive decoration &amp; finishing</li><li>Solutions for global brands</li></ul></div></div>';
            $inquiry = str_replace( '<div class="yby-inquiry-modal__media">', '<div class="yby-inquiry-modal__media">' . $decor, $inquiry );
            $inquiry = str_replace( 'glass-bottle-oem</span>', 'Start Your Bottle Project</span>', $inquiry );
            $inquiry = str_replace( '<form class="yby-inquiry-form"', '<p class="andy-oem-subtitle">Tell us about your bottle requirements and our team will follow up with you.</p><form class="yby-inquiry-form"', $inquiry );
        }
        if ( $oem_preview ) {
            // Visual UAT only. Prevent POST through the browser without changing real form runtime.
            $inquiry = str_replace( 'class="yby-inquiry-form"', 'class="yby-inquiry-form" data-andy-preview-only="1" onsubmit="return false"', $inquiry );
            $inquiry = str_replace( 'type="submit" class="yby-inquiry-form__submit"', 'type="button" class="yby-inquiry-form__submit" disabled', $inquiry );
        }
		}
		// Newsletter rendering stays available even when a page-scoped inquiry
		// is intentionally suppressed on another campaign landing page.
		$theme = class_exists( 'YBY_Brand_Profile' ) ? YBY_Brand_Profile::get_theme_config() : array();
		$image = $settings['subscribe_image'] ?: (string) ( $theme['subscribeImage'] ?? '' );
		$subscribe = '<div id="yby-global-subscribe-popup" class="yby-global-popup yby-global-popup--subscribe" data-yby-global-popup="subscribe" aria-hidden="true" hidden><div class="yby-global-popup__backdrop" data-yby-popup-close></div><div class="yby-global-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="yby-global-subscribe-title" tabindex="-1"><button type="button" class="yby-global-popup__close" data-yby-popup-close aria-label="'.esc_attr__( 'Close popup', 'yby-core' ).'"><span aria-hidden="true">&times;</span></button>'.( $image ? '<div class="yby-global-popup__media"><img src="'.esc_url( $image ).'" alt="" loading="lazy"></div>' : '' ).'<div class="yby-global-popup__content"><h2 id="yby-global-subscribe-title">'.esc_html( $settings['subscribe_title'] ).'</h2><p>'.esc_html( $settings['subscribe_subtitle'] ).'</p><form data-yby-subscribe-contract data-yby-newsletter-enabled="'.( YBY_Newsletter_REST_Controller::subscribe_ready() ? '1' : '0' ).'" data-yby-newsletter-endpoint="'.esc_url( rest_url( 'andy-core/v1/newsletter/' ) ).'" data-yby-newsletter-policy="'.esc_attr( YBY_Newsletter_REST_Controller::policy_version() ).'" novalidate><label>Email<input type="email" name="email" required></label><label class="yby-newsletter-consent"><input type="checkbox" name="marketing_consent" value="1" required><span>'.esc_html__( 'I agree to receive marketing emails. I can unsubscribe at any time.', 'yby-core' ).'</span></label><label class="yby-newsletter-honeypot" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label><button type="submit">Subscribe</button><p role="status" data-yby-subscribe-status>Subscribe submission is not configured yet.</p></form></div></div></div>';
		echo $inquiry . $subscribe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
