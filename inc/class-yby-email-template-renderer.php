<?php
/**
 * Email Template OS canonical renderer.
 *
 * Source-only foundation for Andy Core v1.5.8. Preview, test send and runtime
 * must eventually call this same renderer; no hooks are registered here.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders structured template payloads using one Email VI.
 */
class YBY_Email_Template_Renderer {

	/**
	 * Render a structured template.
	 *
	 * @param array<string, mixed> $payload Template payload.
	 * @param array<string, mixed> $context Variable context.
	 * @param array<string, mixed> $design Email VI settings.
	 * @return array<string, mixed>
	 */
	public function render( $payload, $context = array(), $design = array() ) {
		$payload = wp_parse_args( is_array( $payload ) ? $payload : array(), YBY_Email_Template_Schema::payload_defaults() );
		$context = is_array( $context ) ? $context : array();
		$design = YBY_Email_Design_Settings::sanitize( wp_parse_args( is_array( $design ) ? $design : array(), YBY_Email_Template_Schema::design_defaults() ) );
		$diagnostics = array();

		$subject = $this->render_text( $payload['subject'], $context, $diagnostics );
		$preheader = $this->render_text( $payload['preheader'], $context, $diagnostics );
		$heading = $this->render_text( $payload['heading'], $context, $diagnostics );
		$intro = $this->render_rich_text( $payload['intro_copy'], $context, $diagnostics );
		$secondary = $this->render_rich_text( $payload['secondary_copy'], $context, $diagnostics );
		$additional = $this->render_rich_text( $payload['additional_content'], $context, $diagnostics );
		$cta = is_array( $payload['primary_cta'] ) ? $payload['primary_cta'] : array();
		$cta_label = $this->render_text( $cta['label'] ?? '', $context, $diagnostics );
		$cta_url = $this->render_url( $cta['url'] ?? '', $context, $diagnostics );
		$dynamic = $this->render_dynamic_sections( $payload['dynamic_sections'], $context, $diagnostics, $design );

		$diagnostics = array_values( array_unique( $diagnostics ) );
		$valid = empty( $diagnostics );

		return array(
			'valid'       => $valid,
			'diagnostics' => $diagnostics,
			'subject'     => $subject,
			'preheader'   => $preheader,
			'html'        => $this->build_html( $heading, $preheader, $intro, $dynamic, $cta_label, $cta_url, $secondary, $additional, $design ),
			'text'        => $this->build_plain_text( $heading, $intro, $dynamic, $cta_label, $cta_url, $secondary, $additional ),
		);
	}

	/**
	 * Replace variables in a plain-text field.
	 *
	 * Canonical V1 token syntax preserves migration compatibility with legacy
	 * templates: `{customer_name}`, `{order_number}`, `{inquiry.case_id}`, etc.
	 * Unknown tokens make the render invalid and are removed from output so raw
	 * unresolved tokens can never silently reach production mail.
	 *
	 * @param mixed               $value Raw value.
	 * @param array<string,mixed> $context Context.
	 * @param array<int,string>   $diagnostics Diagnostics accumulator.
	 * @return string
	 */
	protected function render_text( $value, $context, &$diagnostics ) {
		$value = (string) $value;
		$value = preg_replace_callback(
			'/\{([a-zA-Z0-9_.:-]+)\}/',
			function ( $matches ) use ( $context, &$diagnostics ) {
				$found = false;
				$resolved = $this->resolve_context_value( $context, $matches[1], $found );

				if ( ! $found ) {
					$diagnostics[] = 'unknown_variable:' . $matches[1];
					return '';
				}

				if ( is_scalar( $resolved ) || null === $resolved ) {
					return sanitize_text_field( (string) $resolved );
				}

				$diagnostics[] = 'non_scalar_variable:' . $matches[1];
				return '';
			},
			$value
		);

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Replace variables in controlled rich text.
	 *
	 * @param mixed               $value Raw value.
	 * @param array<string,mixed> $context Context.
	 * @param array<int,string>   $diagnostics Diagnostics accumulator.
	 * @return string
	 */
	protected function render_rich_text( $value, $context, &$diagnostics ) {
		$rendered = $this->render_text( $value, $context, $diagnostics );
		return nl2br( esc_html( $rendered ) );
	}

	/**
	 * Render a URL token field.
	 *
	 * @param mixed               $value Raw URL.
	 * @param array<string,mixed> $context Context.
	 * @param array<int,string>   $diagnostics Diagnostics accumulator.
	 * @return string
	 */
	protected function render_url( $value, $context, &$diagnostics ) {
		$value = $this->render_text( $value, $context, $diagnostics );
		$url = esc_url_raw( $value );

		if ( '' !== $value && '' === $url ) {
			$diagnostics[] = 'invalid_cta_url';
		}

		return $url;
	}

	/**
	 * Resolve a dot-notated context key.
	 *
	 * Direct flat keys are checked first for legacy compatibility.
	 *
	 * @param array<string,mixed> $context Context.
	 * @param string              $key Variable key.
	 * @param bool                $found Found flag.
	 * @return mixed
	 */
	protected function resolve_context_value( $context, $key, &$found ) {
		if ( array_key_exists( $key, $context ) ) {
			$found = true;
			return $context[ $key ];
		}

		$current = $context;

		foreach ( explode( '.', $key ) as $part ) {
			if ( ! is_array( $current ) || ! array_key_exists( $part, $current ) ) {
				$found = false;
				return null;
			}
			$current = $current[ $part ];
		}

		$found = true;
		return $current;
	}

	/**
	 * Render structured dynamic sections.
	 *
	 * V1 foundation supports safe label/value rows; provider adapters may add
	 * richer governed components later without allowing arbitrary HTML.
	 *
	 * @param mixed               $sections Raw sections.
	 * @param array<string,mixed> $context Context.
	 * @param array<int,string>   $diagnostics Diagnostics accumulator.
	 * @param array<string,mixed> $design Design settings.
	 * @return string
	 */
	protected function render_dynamic_sections( $sections, $context, &$diagnostics, $design ) {
		if ( ! is_array( $sections ) ) {
			return '';
		}

		$rows = '';

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$label = $this->render_text( $section['label'] ?? '', $context, $diagnostics );
			$value = $this->render_text( $section['value'] ?? '', $context, $diagnostics );

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$rows .= '<tr><td style="padding:10px 12px;border-bottom:1px solid ' . esc_attr( $design['border_color'] ) . ';font:600 13px Arial,Helvetica,sans-serif;color:' . esc_attr( $design['muted_color'] ) . ';width:35%;">' . esc_html( $label ) . '</td><td style="padding:10px 12px;border-bottom:1px solid ' . esc_attr( $design['border_color'] ) . ';font:400 14px Arial,Helvetica,sans-serif;color:' . esc_attr( $design['text_color'] ) . ';">' . esc_html( $value ) . '</td></tr>';
		}

		return '' === $rows ? '' : '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 24px;">' . $rows . '</table>';
	}

	/**
	 * Build the canonical email-safe HTML shell.
	 *
	 * @return string
	 */
	protected function build_html( $heading, $preheader, $intro, $dynamic, $cta_label, $cta_url, $secondary, $additional, $design ) {
		$logo = '' !== $design['logo_url'] ? '<img src="' . esc_url( $design['logo_url'] ) . '" alt="" style="display:block;max-width:180px;height:auto;border:0;">' : '';
		$cta = '';
		if ( '' !== $cta_label && '' !== $cta_url ) {
			$cta = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;"><tr><td bgcolor="' . esc_attr( $design['primary_color'] ) . '" style="border-radius:' . absint( $design['cta_radius'] ) . 'px;"><a href="' . esc_url( $cta_url ) . '" style="display:inline-block;padding:13px 22px;font:600 14px Arial,Helvetica,sans-serif;color:' . esc_attr( $design['primary_text_color'] ) . ';text-decoration:none;border-radius:' . absint( $design['cta_radius'] ) . 'px;">' . esc_html( $cta_label ) . '</a></td></tr></table>';
		}

		$footer = '' !== $design['footer_text'] ? '<p style="margin:0;font:400 12px Arial,Helvetica,sans-serif;line-height:1.6;color:' . esc_attr( $design['muted_color'] ) . ';">' . esc_html( $design['footer_text'] ) . '</p>' : '';

		return '<!doctype html><html><body style="margin:0;padding:0;background:' . esc_attr( $design['canvas_color'] ) . ';">'
			. '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . esc_html( $preheader ) . '</div>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . esc_attr( $design['canvas_color'] ) . ';"><tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:' . absint( $design['content_width'] ) . 'px;background:' . esc_attr( $design['surface_color'] ) . ';border:1px solid ' . esc_attr( $design['border_color'] ) . ';border-radius:' . absint( $design['card_radius'] ) . 'px;border-collapse:separate;overflow:hidden;">'
			. '<tr><td style="padding:24px 28px;border-bottom:1px solid ' . esc_attr( $design['border_color'] ) . ';">' . $logo . '</td></tr>'
			. '<tr><td style="padding:30px 28px;">'
			. ( '' !== $heading ? '<h1 style="margin:0 0 18px;font:700 28px/1.25 Arial,Helvetica,sans-serif;color:' . esc_attr( $design['text_color'] ) . ';">' . esc_html( $heading ) . '</h1>' : '' )
			. ( '' !== $intro ? '<div style="margin:0 0 24px;font:400 15px/1.7 Arial,Helvetica,sans-serif;color:' . esc_attr( $design['text_color'] ) . ';">' . $intro . '</div>' : '' )
			. $dynamic . $cta
			. ( '' !== $secondary ? '<div style="margin:0 0 20px;font:400 14px/1.7 Arial,Helvetica,sans-serif;color:' . esc_attr( $design['muted_color'] ) . ';">' . $secondary . '</div>' : '' )
			. ( '' !== $additional ? '<div style="margin:0;font:400 13px/1.7 Arial,Helvetica,sans-serif;color:' . esc_attr( $design['muted_color'] ) . ';">' . $additional . '</div>' : '' )
			. '</td></tr><tr><td style="padding:20px 28px;border-top:1px solid ' . esc_attr( $design['border_color'] ) . ';">' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Build plain-text fallback from the same structured render inputs.
	 *
	 * @return string
	 */
	protected function build_plain_text( $heading, $intro, $dynamic, $cta_label, $cta_url, $secondary, $additional ) {
		$parts = array_filter(
			array(
				$heading,
				wp_strip_all_tags( str_replace( '<br />', "\n", $intro ) ),
				wp_strip_all_tags( $dynamic ),
				'' !== $cta_label && '' !== $cta_url ? $cta_label . ': ' . $cta_url : '',
				wp_strip_all_tags( str_replace( '<br />', "\n", $secondary ) ),
				wp_strip_all_tags( str_replace( '<br />', "\n", $additional ) ),
			),
			static function ( $value ) {
				return '' !== trim( (string) $value );
			}
		);

		return implode( "\n\n", $parts );
	}
}
