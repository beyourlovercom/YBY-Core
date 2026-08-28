<?php
/** Core-owned persistent inquiry entry point. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Global_Inquiry_Dock {
	const OPTION = 'yby_core_global_inquiry_dock';

	public static function defaults() {
		return array( 'enabled' => 0, 'mode' => 'single', 'display_timing' => 'after_first_screen', 'label' => 'Inquiry', 'whatsapp_label' => 'WhatsApp', 'whatsapp_background_color' => '#25d366', 'whatsapp_text_color' => '#ffffff', 'inquiry_background_color' => '', 'inquiry_text_color' => '', 'position' => 'bottom-right', 'desktop' => 1, 'mobile' => 1, 'custom_css' => '' );
	}
	public static function settings() { return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() ); }
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = self::defaults();
		$out['enabled'] = empty( $raw['enabled'] ) ? 0 : 1;
		$out['mode'] = in_array( $raw['mode'] ?? '', array( 'single', 'dual' ), true ) ? $raw['mode'] : $out['mode'];
		$out['display_timing'] = in_array( $raw['display_timing'] ?? '', array( 'after_first_screen', 'immediate' ), true ) ? $raw['display_timing'] : $out['display_timing'];
		$out['label'] = sanitize_text_field( $raw['label'] ?? $out['label'] );
		$out['label'] = '' === $out['label'] ? 'Inquiry' : $out['label'];
		$out['whatsapp_label'] = sanitize_text_field( $raw['whatsapp_label'] ?? $out['whatsapp_label'] );
		$out['whatsapp_label'] = '' === $out['whatsapp_label'] ? 'WhatsApp' : $out['whatsapp_label'];
		$out['whatsapp_background_color'] = sanitize_hex_color( $raw['whatsapp_background_color'] ?? $out['whatsapp_background_color'] ) ?: '#25d366';
		$out['whatsapp_text_color'] = sanitize_hex_color( $raw['whatsapp_text_color'] ?? $out['whatsapp_text_color'] ) ?: '#ffffff';
		$out['inquiry_background_color'] = sanitize_hex_color( $raw['inquiry_background_color'] ?? '' ) ?: '';
		$out['inquiry_text_color'] = sanitize_hex_color( $raw['inquiry_text_color'] ?? '' ) ?: '';
		$out['position'] = in_array( $raw['position'] ?? '', array( 'bottom-right', 'bottom-left', 'bottom-center' ), true ) ? $raw['position'] : $out['position'];
		$out['desktop'] = empty( $raw['desktop'] ) ? 0 : 1;
		$out['mobile'] = empty( $raw['mobile'] ) ? 0 : 1;
		$out['custom_css'] = YBY_Global_Popup::sanitize_custom_css( $raw['custom_css'] ?? '' );
		return $out;
	}
	public static function build_color_css( $settings = null ) {
		$s = self::sanitize( is_array( $settings ) ? $settings : self::settings() );
		$vars = array( '--yby-dock-whatsapp-bg' => $s['whatsapp_background_color'], '--yby-dock-whatsapp-text' => $s['whatsapp_text_color'] );
		if ( '' !== $s['inquiry_background_color'] ) { $vars['--yby-dock-inquiry-bg'] = $s['inquiry_background_color']; }
		if ( '' !== $s['inquiry_text_color'] ) { $vars['--yby-dock-inquiry-text'] = $s['inquiry_text_color']; }
		$declarations = array();
		foreach ( $vars as $name => $value ) { $declarations[] = $name . ':' . $value; }
		return '.yby-global-inquiry-dock{' . implode( ';', $declarations ) . ';}';
	}
	public static function render_markup( $preview = false, $mode_override = null ) {
		$s = self::sanitize( self::settings() );
		if ( in_array( $mode_override, array( 'single', 'dual' ), true ) ) { $s['mode'] = $mode_override; }
		$frontend_preview = isset( $_GET['yby_inquiry_dock_preview'] ) && '1' === $_GET['yby_inquiry_dock_preview'] && current_user_can( 'andy_core_settings_manage' );
		if ( ! $s['enabled'] && ! $preview && ! $frontend_preview ) { return ''; }
		$classes = 'yby-global-inquiry-dock yby-global-inquiry-dock--' . esc_attr( $s['position'] ) . ' yby-global-inquiry-dock--mode-' . esc_attr( $s['mode'] ) . ' yby-global-inquiry-dock--timing-' . esc_attr( $s['display_timing'] );
		if ( $preview ) { $classes .= ' yby-global-inquiry-dock--admin-preview'; }
		if ( ! $s['desktop'] ) { $classes .= ' yby-global-inquiry-dock--desktop-hidden'; }
		if ( ! $s['mobile'] ) { $classes .= ' yby-global-inquiry-dock--mobile-hidden'; }
		$mail_icon = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 5.75h18v12.5H3z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
		$wa_icon = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3.2a8.4 8.4 0 0 0-7.3 12.6L3.5 20.5l4.8-1.2A8.4 8.4 0 1 0 12 3.2Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.1 8.3c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.8 1.9c.1.3.1.5-.1.7l-.6.8c-.2.2-.1.4 0 .6.7 1.2 1.7 2.1 3 2.7.2.1.4.1.6-.1l.9-1.1c.2-.2.4-.3.7-.2l1.9.9c.3.1.4.3.4.5 0 .4-.2 1.3-.8 1.7-.5.4-1.3.7-2.2.5-1.2-.2-2.8-.8-4.5-2.3-1.4-1.3-2.5-3-2.8-4.3-.2-.9 0-1.7.3-2.3Z" fill="currentColor"/></svg>';
		$inquiry = '<button type="button" class="yby-global-inquiry-dock__button yby-global-inquiry-dock__button--inquiry" data-yby-popup-open="inquiry" data-yby-modal-open="yby-global-inquiry-popup" data-yby-source="floating_inquiry_dock_inquiry" data-yby-trigger-source="floating_inquiry_dock_inquiry" data-yby-source-page="" aria-label="' . esc_attr( $s['label'] ) . '">' . $mail_icon . '<span>' . esc_html( $s['label'] ) . '</span></button>';
		if ( 'dual' === $s['mode'] ) {
			$whatsapp = '<a class="yby-global-inquiry-dock__button yby-global-inquiry-dock__button--whatsapp" data-yby-whatsapp-link data-yby-source="floating_inquiry_dock_whatsapp" data-yby-trigger-source="floating_inquiry_dock_whatsapp" href="#" target="_blank" rel="noopener" aria-label="' . esc_attr( $s['whatsapp_label'] ) . '">' . $wa_icon . '<span>' . esc_html( $s['whatsapp_label'] ) . '</span></a>';
			return '<div class="' . $classes . '" data-yby-source="global_inquiry_dock" role="region" aria-label="' . esc_attr__( 'Global inquiry', 'yby-core' ) . '"><div class="yby-global-inquiry-dock__actions">' . $whatsapp . $inquiry . '</div></div>';
		}
		return '<div class="' . $classes . '" data-yby-source="global_inquiry_dock" role="region" aria-label="' . esc_attr__( 'Global inquiry', 'yby-core' ) . '">' . $inquiry . '</div>';
	}
	public function render() { echo self::render_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
