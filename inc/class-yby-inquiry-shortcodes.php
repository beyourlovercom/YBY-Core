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
			$this->maybe_doing_it_wrong( 'Preset is required for [yby_inquiry_modal].' );
			return '';
		}

		$preset_manager = $this->inquiry_manager->get_preset_manager();
		$preset         = $preset_manager->get_preset( $attributes['preset'] );

		if ( ! is_array( $preset ) || empty( $preset['enabled'] ) ) {
			$this->maybe_doing_it_wrong( 'The requested inquiry preset is missing or disabled.' );
			return '';
		}

		$fields = $this->resolve_preset_fields( $preset );

		if ( empty( $fields ) ) {
			return '';
		}

		$attributes['id'] = $this->generate_unique_modal_id(
			'' !== $attributes['id'] ? $attributes['id'] : 'yby-inquiry-modal-' . $preset['id']
		);

		$modal_markup = $this->renderer->render_modal( $preset, $fields, $attributes );

		if ( '' === $modal_markup ) {
			return '';
		}

		self::$deferred_modals[ $attributes['id'] ] = $modal_markup;

		return '<!-- yby_inquiry_modal:' . esc_html( $attributes['id'] ) . ' -->';
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
	 * Collect modal shortcodes from the queried post content when themes skip shortcode execution.
	 *
	 * @return void
	 */
	public function collect_from_queried_content() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post || empty( $post->post_content ) || false === strpos( $post->post_content, '[yby_inquiry_modal' ) ) {
			return;
		}

		$pattern = get_shortcode_regex( array( 'yby_inquiry_modal' ) );

		if ( empty( $pattern ) || ! preg_match_all( '/' . $pattern . '/', $post->post_content, $matches ) ) {
			return;
		}

		foreach ( isset( $matches[0] ) && is_array( $matches[0] ) ? $matches[0] : array() as $shortcode_markup ) {
			do_shortcode( $shortcode_markup );
		}
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
				'id'           => '',
				'preset'       => '',
				'title'        => '',
				'submit_label' => '',
				'image'        => '',
				'class'        => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'yby_inquiry_modal'
		);

		return array(
			'id'           => $this->sanitize_dom_id( $attributes['id'] ),
			'preset'       => sanitize_key( $attributes['preset'] ),
			'title'        => sanitize_text_field( $attributes['title'] ),
			'submit_label' => sanitize_text_field( $attributes['submit_label'] ),
			'image'        => esc_url_raw( $attributes['image'] ),
			'class'        => $this->sanitize_class_tokens( $attributes['class'] ),
		);
	}

	/**
	 * Resolve preset fields in preset order.
	 *
	 * @param array<string, mixed> $preset Preset definition.
	 * @return array<int, array<string, mixed>>
	 */
	protected function resolve_preset_fields( $preset ) {
		$field_manager = $this->inquiry_manager->get_field_manager();
		$all_fields    = $field_manager->get_fields();
		$resolved      = array();

		foreach ( isset( $preset['fields'] ) && is_array( $preset['fields'] ) ? $preset['fields'] : array() as $field_id ) {
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
