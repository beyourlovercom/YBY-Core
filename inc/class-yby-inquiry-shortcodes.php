<?php
/**
 * Inquiry shortcode registration.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders inquiry shortcodes.
 */
class YBY_Inquiry_Shortcodes {

	/**
	 * Inquiry manager.
	 *
	 * @var YBY_Inquiry_Manager
	 */
	protected $inquiry_manager;

	/**
	 * Inquiry renderer.
	 *
	 * @var YBY_Inquiry_Renderer
	 */
	protected $renderer;

	/**
	 * Used modal ID counts for deterministic suffixing.
	 *
	 * @var array<string, int>
	 */
	protected static $modal_id_counts = array();

	/**
	 * Deferred modal markup collected during shortcode rendering.
	 *
	 * @var array<string, string>
	 */
	protected static $deferred_modals = array();

	/**
	 * Request-level cache of transformed content by stable signature.
	 *
	 * @var array<string, string>
	 */
	protected static $content_render_cache = array();

	/**
	 * Constructor.
	 *
	 * @param YBY_Inquiry_Manager  $inquiry_manager Inquiry manager.
	 * @param YBY_Inquiry_Renderer $renderer Inquiry renderer.
	 */
	public function __construct( $inquiry_manager, $renderer ) {
		$this->inquiry_manager = $inquiry_manager;
		$this->renderer        = $renderer;
	}

	/**
	 * Register inquiry shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode(
			'yby_inquiry_modal',
			array( $this, 'render_modal_shortcode' )
		);
		add_shortcode( 'yby_inquiry', array( $this, 'render_inline_shortcode' ) );

		add_shortcode(
			'yby_sticky_cta',
			array( $this, 'render_sticky_cta_shortcode' )
		);
	}
	public function render_inline_shortcode( $attributes = array(), $content = null, $tag = '' ) {
		unset( $content, $tag );
		$attributes = $this->sanitize_shortcode_attributes( $attributes );
		if ( '' === $attributes['preset'] ) { $attributes['preset'] = $this->get_default_modal_preset( $attributes ); }
		$preset = $this->inquiry_manager->get_preset_manager()->get_preset( $attributes['preset'] );
		if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) { return ''; }
		$fields = $this->resolve_preset_fields( $preset );
		$attributes['id'] = $this->generate_unique_modal_id( '' !== $attributes['id'] ? $attributes['id'] : 'yby-inquiry-' . $preset['id'] );
		return $this->renderer->render_inline( $preset, $fields, $attributes );
	}

	/**
	 * Render the inquiry modal shortcode.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 * @param string|null          $content Shortcode content.
	 * @param string               $tag Shortcode tag.
	 * @return string
	 */
	public function render_modal_shortcode( $attributes = array(), $content = null, $tag = '' ) {
		unset( $content, $tag );

		$attributes = $this->sanitize_shortcode_attributes( $attributes );

		if ( '' === $attributes['preset'] ) {
			$attributes['preset'] = $this->get_default_modal_preset( $attributes );
		}

		$preset_manager = $this->inquiry_manager->get_preset_manager();
		$preset         = $preset_manager->get_preset( $attributes['preset'] );

		if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) {
			$this->maybe_doing_it_wrong( 'The requested inquiry preset is missing or disabled.' );
			return '';
		}

		$fields = ! empty( $attributes['desktop_fields'] )
			? $this->resolve_field_ids( $attributes['desktop_fields'] )
			: $this->resolve_preset_fields( $preset );
		$mobile_fields = ! empty( $attributes['mobile_fields'] )
			? $this->resolve_field_ids( $attributes['mobile_fields'] )
			: $this->resolve_preset_fields( $preset, 'mobile_fields' );

		if ( empty( $fields ) ) {
			return '';
		}

		$attributes['id'] = $this->generate_unique_modal_id(
			'' !== $attributes['id'] ? $attributes['id'] : 'yby-inquiry-modal-' . $preset['id']
		);

		$modal_markup = $this->renderer->render_modal( $preset, $fields, $attributes, $mobile_fields );

		if ( '' === $modal_markup ) {
			return '';
		}

		self::$deferred_modals[ $attributes['id'] ] = $modal_markup;

		return '<!-- yby_inquiry_modal:' . esc_html( $attributes['id'] ) . ' -->';
	}

	/**
	 * Render the shared sticky CTA shortcode.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 * @param string|null          $content Shortcode content.
	 * @param string               $tag Shortcode tag.
	 * @return string
	 */
	public function render_sticky_cta_shortcode( $attributes = array(), $content = null, $tag = '' ) {
		unset( $content, $tag );

		$attributes = $this->sanitize_sticky_cta_attributes( $attributes );
		$classes    = array_merge( array( 'yby-sticky-cta' ), $attributes['class'] );

		$output  = '<div id="' . esc_attr( $attributes['id'] ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-yby-sticky-cta';
		$output .= '' !== $attributes['profile'] ? ' data-yby-page-profile="' . esc_attr( $attributes['profile'] ) . '"' : '';
		$output .= '>';
		$output .= '<a class="yby-sticky-cta__button" href="' . esc_url( $attributes['href'] ) . '" data-yby-inquiry-trigger data-yby-source="' . esc_attr( $attributes['source'] ) . '"';
		$output .= '' !== $attributes['modal_id'] ? ' data-yby-modal-open="' . esc_attr( $attributes['modal_id'] ) . '"' : '';
		$output .= '' !== $attributes['profile'] ? ' data-yby-page-profile="' . esc_attr( $attributes['profile'] ) . '"' : '';
		$output .= '>';
		$output .= esc_html( $attributes['label'] );
		$output .= '</a>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Print deferred modal markup in the footer.
	 *
	 * @return void
	 */
	public function render_deferred_modals() {
		if ( empty( self::$deferred_modals ) ) {
			return;
		}

		foreach ( self::$deferred_modals as $modal_markup ) {
			echo $modal_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Capture inquiry modals before other content filters strip the shortcode markup.
	 *
	 * @param string $content Raw content.
	 * @return string
	 */
	public function capture_modal_shortcodes_in_content( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '[yby_inquiry_modal' ) ) {
			return $content;
		}

		$signature = $this->build_content_signature( $content );

		if ( isset( self::$content_render_cache[ $signature ] ) ) {
			return self::$content_render_cache[ $signature ];
		}

		$pattern = get_shortcode_regex( array( 'yby_inquiry_modal' ) );

		if ( empty( $pattern ) ) {
			return $content;
		}

		$transformed = preg_replace_callback(
			'/' . $pattern . '/',
			static function ( $matches ) {
				$shortcode_markup = isset( $matches[0] ) ? $matches[0] : '';

				return '' !== $shortcode_markup ? do_shortcode( $shortcode_markup ) : '';
			},
			$content
		);

		if ( is_string( $transformed ) ) {
			self::$content_render_cache[ $signature ] = $transformed;
			return $transformed;
		}

		return $content;
	}

	/**
	 * Reset request-level static state.
	 *
	 * @return void
	 */
	public static function reset_request_state() {
		self::$modal_id_counts      = array();
		self::$deferred_modals      = array();
		self::$content_render_cache = array();
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * @param array<string, mixed> $attributes Raw attributes.
	 * @return array<string, mixed>
	 */
	protected function sanitize_shortcode_attributes( $attributes ) {
		$attributes = shortcode_atts(
			array(
				'id'                        => '',
				'preset'                    => '',
				'layout'                    => '',
				'mobile_layout'             => '',
				'desktop_fields'            => '',
				'mobile_fields'             => '',
				'field_labels'              => '',
				'field_placeholders'        => '',
				'mobile_field_labels'       => '',
				'mobile_field_placeholders' => '',
				'title'                     => '',
				'mobile_title'              => '',
				'subtitle'                  => '',
				'mobile_subtitle'           => '',
				'eyebrow'                   => '',
				'brand_mark'                => '',
				'brand_name'                => '',
				'brand_tagline'             => '',
				'privacy_note'              => '',
				'submit_label'              => '',
				'mobile_submit_label'       => '',
				'image'                     => '',
				'image_alt'                 => '',
				'class'                     => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'yby_inquiry_modal'
		);

		return array(
			'id'                        => $this->sanitize_dom_id( $attributes['id'] ),
			'preset'                    => sanitize_key( $attributes['preset'] ),
			'layout'                    => sanitize_key( $attributes['layout'] ),
			'mobile_layout'             => sanitize_key( $attributes['mobile_layout'] ),
			'desktop_fields'            => $this->sanitize_field_list( $attributes['desktop_fields'] ),
			'mobile_fields'             => $this->sanitize_field_list( $attributes['mobile_fields'] ),
			'field_labels'              => $this->sanitize_field_map( $attributes['field_labels'] ),
			'field_placeholders'        => $this->sanitize_field_map( $attributes['field_placeholders'] ),
			'mobile_field_labels'       => $this->sanitize_field_map( $attributes['mobile_field_labels'] ),
			'mobile_field_placeholders' => $this->sanitize_field_map( $attributes['mobile_field_placeholders'] ),
			'title'                     => sanitize_text_field( $attributes['title'] ),
			'mobile_title'              => sanitize_text_field( $attributes['mobile_title'] ),
			'subtitle'                  => sanitize_text_field( $attributes['subtitle'] ),
			'mobile_subtitle'           => sanitize_text_field( $attributes['mobile_subtitle'] ),
			'eyebrow'                   => sanitize_text_field( $attributes['eyebrow'] ),
			'brand_mark'                => sanitize_text_field( $attributes['brand_mark'] ),
			'brand_name'                => sanitize_text_field( $attributes['brand_name'] ),
			'brand_tagline'             => sanitize_text_field( $attributes['brand_tagline'] ),
			'privacy_note'              => sanitize_text_field( $attributes['privacy_note'] ),
			'submit_label'              => sanitize_text_field( $attributes['submit_label'] ),
			'mobile_submit_label'       => sanitize_text_field( $attributes['mobile_submit_label'] ),
			'image'                     => esc_url_raw( $attributes['image'] ),
			'image_alt'                 => sanitize_text_field( $attributes['image_alt'] ),
			'class'                     => $this->sanitize_class_tokens( $attributes['class'] ),
		);
	}

	/**
	 * Sanitize sticky CTA shortcode attributes.
	 *
	 * @param array<string, mixed> $attributes Raw attributes.
	 * @return array<string, mixed>
	 */
	protected function sanitize_sticky_cta_attributes( $attributes ) {
		$attributes = shortcode_atts(
			array(
				'id'       => 'yby-sticky-cta',
				'label'    => 'Get Quote',
				'href'     => '#yby-inquiry',
				'modal_id' => '',
				'source'   => 'site_global_sticky_cta',
				'profile'  => '',
				'class'    => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'yby_sticky_cta'
		);

		$source = str_replace( '-', '_', sanitize_key( $attributes['source'] ) );
		$source = '' !== $source ? $source : 'site_global_sticky_cta';
		$id     = $this->sanitize_dom_id( $attributes['id'] );
		$label  = sanitize_text_field( $attributes['label'] );

		return array(
			'id'       => '' !== $id ? $id : 'yby-sticky-cta',
			'label'    => '' !== $label ? $label : 'Get Quote',
			'href'     => $this->sanitize_anchor_or_url( $attributes['href'] ),
			'modal_id' => $this->sanitize_dom_id( $attributes['modal_id'] ),
			'source'   => $source,
			'profile'  => str_replace( '-', '_', sanitize_key( $attributes['profile'] ) ),
			'class'    => $this->sanitize_class_tokens( $attributes['class'] ),
		);
	}

	/**
	 * Return the default modal preset ID.
	 *
	 * @param array<string, mixed> $attributes Sanitized shortcode attributes.
	 * @return string
	 */
	protected function get_default_modal_preset( $attributes ) {
		$default = 'irrigation_quick_inquiry';

		if ( function_exists( 'apply_filters' ) ) {
			$default = apply_filters( 'yby_inquiry_default_preset', $default, $attributes );
		}

		return sanitize_key( $default );
	}

	/**
	 * Sanitize a local anchor or URL.
	 *
	 * @param string $value Raw URL.
	 * @return string
	 */
	protected function sanitize_anchor_or_url( $value ) {
		$value = trim( (string) $value );

		if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_-]*$/', $value ) ) {
			return $value;
		}

		$url = esc_url_raw( $value );

		return '' !== $url ? $url : '#yby-inquiry';
	}

	/**
	 * Sanitize a comma/pipe/space separated field ID list.
	 *
	 * @param string|array $value Raw field list.
	 * @return array<int, string>
	 */
	protected function sanitize_field_list( $value ) {
		$tokens = is_array( $value ) ? $value : preg_split( '/[\s,|]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$tokens = is_array( $tokens ) ? $tokens : array();
		$result = array();
		foreach ( $tokens as $token ) {
			$token = sanitize_key( $token );
			if ( '' !== $token && ! in_array( $token, $result, true ) ) {
				$result[] = $token;
			}
		}
		return $result;
	}

	/**
	 * Sanitize page-owned field presentation overrides using key=value|key=value syntax.
	 *
	 * @param string|array $value Raw map.
	 * @return array<string, string>
	 */
	protected function sanitize_field_map( $value ) {
		if ( is_array( $value ) ) {
			$pairs = $value;
		} else {
			$pairs = array();
			foreach ( preg_split( '/\|+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY ) ?: array() as $chunk ) {
				$parts = explode( '=', $chunk, 2 );
				if ( 2 === count( $parts ) ) {
					$pairs[ $parts[0] ] = $parts[1];
				}
			}
		}
		$result = array();
		foreach ( $pairs as $field_id => $text ) {
			$field_id = sanitize_key( $field_id );
			$text = sanitize_text_field( $text );
			if ( '' !== $field_id && '' !== $text ) {
				$result[ $field_id ] = $text;
			}
		}
		return $result;
	}

	/**
	 * Resolve explicit page-owned field IDs against the global field registry.
	 *
	 * @param array<int, string> $field_ids Field IDs.
	 * @return array<int, array<string, mixed>>
	 */
	protected function resolve_field_ids( $field_ids ) {
		$field_manager = $this->inquiry_manager->get_field_manager();
		$all_fields    = $field_manager->get_fields();
		$resolved      = array();
		foreach ( is_array( $field_ids ) ? $field_ids : array() as $field_id ) {
			$field_id = sanitize_key( $field_id );
			if ( '' === $field_id || ! isset( $all_fields[ $field_id ] ) ) {
				continue;
			}
			$field = $all_fields[ $field_id ];
			if ( empty( $field['enabled'] ) || empty( $field['type'] ) ) {
				continue;
			}
			$resolved[] = $field;
		}
		return $resolved;
	}

	/**
	 * Resolve preset fields in preset order.
	 *
	 * @param array<string, mixed> $preset Preset definition.
	 * @return array<int, array<string, mixed>>
	 */
	protected function resolve_preset_fields( $preset, $key = 'fields' ) {
		$field_ids = isset( $preset[ $key ] ) && is_array( $preset[ $key ] ) ? $preset[ $key ] : array();
		return $this->resolve_field_ids( $field_ids );
	}

	/**
	 * Sanitize a DOM ID token.
	 *
	 * @param string $value Raw ID value.
	 * @return string
	 */
	protected function sanitize_dom_id( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$parts = preg_split( '/[^A-Za-z0-9_-]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		$parts = is_array( $parts ) ? $parts : array();

		return implode( '-', array_map( array( $this, 'sanitize_single_html_class' ), $parts ) );
	}

	/**
	 * Sanitize class tokens.
	 *
	 * @param string $value Raw class string.
	 * @return array<int, string>
	 */
	protected function sanitize_class_tokens( $value ) {
		$tokens    = preg_split( '/\s+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		$tokens    = is_array( $tokens ) ? $tokens : array();
		$sanitized = array();

		foreach ( $tokens as $token ) {
			$token = $this->sanitize_single_html_class( $token );

			if ( '' === $token || in_array( $token, $sanitized, true ) ) {
				continue;
			}

			$sanitized[] = $token;
		}

		return $sanitized;
	}

	/**
	 * Sanitize one class-safe token.
	 *
	 * @param string $value Raw token.
	 * @return string
	 */
	protected function sanitize_single_html_class( $value ) {
		if ( function_exists( 'sanitize_html_class' ) ) {
			return sanitize_html_class( (string) $value );
		}

		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
	}

	/**
	 * Generate a deterministic unique modal ID.
	 *
	 * @param string $base_id Base modal ID.
	 * @return string
	 */
	protected function generate_unique_modal_id( $base_id ) {
		$base_id = $this->sanitize_dom_id( $base_id );
		$base_id = '' !== $base_id ? $base_id : 'yby-inquiry-modal';

		if ( ! isset( self::$modal_id_counts[ $base_id ] ) ) {
			self::$modal_id_counts[ $base_id ] = 1;
			return $base_id;
		}

		self::$modal_id_counts[ $base_id ]++;

		return $base_id . '-' . self::$modal_id_counts[ $base_id ];
	}

	/**
	 * Build a stable request-level content signature.
	 *
	 * @param string $content Raw content.
	 * @return string
	 */
	protected function build_content_signature( $content ) {
		$post_id = 0;

		if ( isset( $GLOBALS['post']->ID ) ) {
			$post_id = (int) $GLOBALS['post']->ID;
		} elseif ( function_exists( 'get_the_ID' ) ) {
			$post_id = (int) get_the_ID();
		}

		return $post_id . ':' . md5( $content );
	}

	/**
	 * Emit a safe developer notice for invalid shortcode usage.
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	protected function maybe_doing_it_wrong( $message ) {
		if ( function_exists( '_doing_it_wrong' ) ) {
			_doing_it_wrong( __METHOD__, esc_html( $message ), '1.3.0-dev' );
		}
	}
}
