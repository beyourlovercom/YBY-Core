<?php
/**
 * Inquiry lead mapper.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes structured inquiry metadata and custom fields.
 */
class YBY_Inquiry_Lead_Mapper {

	/**
	 * Maximum number of structured fields accepted from clients.
	 *
	 * @var int
	 */
	const MAX_FIELDS = 50;

	/**
	 * Field registry manager.
	 *
	 * @var YBY_Inquiry_Field_Manager
	 */
	protected $field_manager;

	/**
	 * Preset registry manager.
	 *
	 * @var YBY_Inquiry_Preset_Manager
	 */
	protected $preset_manager;

	/**
	 * Constructor.
	 *
	 * @param YBY_Inquiry_Field_Manager|null  $field_manager Field manager.
	 * @param YBY_Inquiry_Preset_Manager|null $preset_manager Preset manager.
	 */
	public function __construct( $field_manager = null, $preset_manager = null ) {
		$this->field_manager  = $field_manager instanceof YBY_Inquiry_Field_Manager ? $field_manager : new YBY_Inquiry_Field_Manager();
		$this->preset_manager = $preset_manager instanceof YBY_Inquiry_Preset_Manager ? $preset_manager : new YBY_Inquiry_Preset_Manager( $this->field_manager );
	}

	/**
	 * Normalize inquiry source metadata.
	 *
	 * @param array<string, mixed> $payload Raw payload.
	 * @return array<string, string>
	 */
	public function normalize_source_metadata( $payload ) {
		$payload = is_array( $payload ) ? $payload : array();

		return array(
			'source_component' => substr( sanitize_key( isset( $payload['source_component'] ) ? $payload['source_component'] : '' ), 0, 100 ),
			'source_preset'    => substr( sanitize_key( isset( $payload['source_preset'] ) ? $payload['source_preset'] : '' ), 0, 100 ),
			'source_page'      => substr( sanitize_text_field( isset( $payload['source_page'] ) ? $payload['source_page'] : '' ), 0, 255 ),
			'form_version'     => $this->sanitize_form_version( isset( $payload['form_version'] ) ? $payload['form_version'] : '' ),
			'page_profile'     => substr( sanitize_key( isset( $payload['page_profile'] ) ? $payload['page_profile'] : '' ), 0, 100 ),
		);
	}

	/**
	 * Validate that a client-selected preset exists and permits its page profile.
	 *
	 * @param string $source_preset Registered preset ID.
	 * @param string $page_profile Registered page profile ID.
	 * @return array<string, string>
	 */
	public function validate_submission_context( $source_preset, $page_profile ) {
		$preset = $this->get_valid_preset( $source_preset );
		if ( ! is_array( $preset ) ) {
			return array( 'source_preset' => __( 'Invalid inquiry preset', 'yby-core' ) );
		}
		if ( ! $this->preset_manager->is_preset_allowed_for_profile( $source_preset, $page_profile ) ) {
			return array( 'page_profile' => __( 'This inquiry preset is not allowed for the page profile', 'yby-core' ) );
		}
		return array();
	}

	/**
	 * Sanitize structured custom fields for a preset.
	 *
	 * @param mixed  $raw_fields Raw submitted fields.
	 * @param string $preset_id Submitted preset ID.
	 * @return array<string, string>
	 */
	public function sanitize_custom_fields( $raw_fields, $preset_id ) {
		$preset = $this->get_valid_preset( $preset_id );

		if ( ! is_array( $raw_fields ) || empty( $preset['fields'] ) ) {
			return array();
		}

		$allowed_field_ids = array_fill_keys( $preset['fields'], true );
		$core_field_ids    = array_fill_keys( $this->get_core_field_ids(), true );
		$custom_fields     = array();
		$count             = 0;

		foreach ( $raw_fields as $raw_field_id => $raw_value ) {
			if ( $count >= self::MAX_FIELDS ) {
				break;
			}

			$field_id = sanitize_key( $raw_field_id );

			if ( '' === $field_id || isset( $core_field_ids[ $field_id ] ) || ! isset( $allowed_field_ids[ $field_id ] ) ) {
				continue;
			}

			$field = $this->field_manager->get_field( $field_id );

			if ( empty( $field ) || empty( $field['enabled'] ) ) {
				continue;
			}

			$sanitized = $this->sanitize_field_value( $raw_value, $field );

			if ( '' === $sanitized ) {
				continue;
			}

			$custom_fields[ $field_id ] = $sanitized;
			$count++;
		}

		return $custom_fields;
	}

	/**
	 * Return the supported core field IDs.
	 *
	 * @return array<int, string>
	 */
	public function get_core_field_ids() {
		return array(
			'name',
			'company',
			'contact',
			'email',
			'whatsapp',
			'country',
			'product_interest',
			'quantity',
			'message',
		);
	}

	/**
	 * Encode custom fields into normalized JSON object form.
	 *
	 * @param array<string, string> $custom_fields Custom fields.
	 * @return string
	 */
	public function build_custom_fields_json( $custom_fields ) {
		$custom_fields = is_array( $custom_fields ) ? $custom_fields : array();

		if ( empty( $custom_fields ) ) {
			return '{}';
		}

		$json = wp_json_encode( (object) $custom_fields );

		return is_string( $json ) && '' !== $json ? $json : '{}';
	}

	/**
	 * Decode normalized custom fields JSON safely.
	 *
	 * @param mixed $json JSON string.
	 * @return array<string, string>
	 */
	public function decode_custom_fields( $json ) {
		if ( ! is_string( $json ) || '' === trim( $json ) ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $decoded as $field_id => $value ) {
			$field_id = sanitize_key( $field_id );

			if ( '' === $field_id || ! is_scalar( $value ) ) {
				continue;
			}

			$normalized[ $field_id ] = sanitize_textarea_field( (string) $value );
		}

		return $normalized;
	}

	/**
	 * Return a valid preset or null when not available for custom fields.
	 *
	 * @param string $preset_id Preset ID.
	 * @return array<string, mixed>|null
	 */
	protected function get_valid_preset( $preset_id ) {
		$preset_id = sanitize_key( $preset_id );

		if ( '' === $preset_id ) {
			return null;
		}

		$preset = $this->preset_manager->get_preset( $preset_id );

		if ( empty( $preset ) || empty( $preset['enabled'] ) ) {
			return null;
		}

		return $preset;
	}

	/**
	 * Sanitize one structured field value by registered field type.
	 *
	 * @param mixed                $raw_value Raw value.
	 * @param array<string, mixed> $field Registered field definition.
	 * @return string
	 */
	protected function sanitize_field_value( $raw_value, $field ) {
		if ( is_array( $raw_value ) || is_object( $raw_value ) || ! isset( $field['type'] ) ) {
			return '';
		}

		$type = sanitize_key( $field['type'] );

		switch ( $type ) {
			case 'textarea':
				return substr( sanitize_textarea_field( (string) $raw_value ), 0, 3000 );

			case 'checkbox':
				return ! empty( $raw_value ) && '0' !== (string) $raw_value ? '1' : '';

			case 'select':
				$value = substr( sanitize_text_field( (string) $raw_value ), 0, 500 );
				return $this->is_allowed_option_value( $value, $field ) ? $value : '';

			case 'email':
				return substr( sanitize_email( (string) $raw_value ), 0, 500 );

			case 'tel':
			case 'text':
			case 'hidden':
			default:
				return substr( sanitize_text_field( (string) $raw_value ), 0, 500 );
		}
	}

	/**
	 * Check whether a select value matches a registered option.
	 *
	 * @param string               $value Submitted value.
	 * @param array<string, mixed> $field Field definition.
	 * @return bool
	 */
	protected function is_allowed_option_value( $value, $field ) {
		if ( '' === $value ) {
			return false;
		}

		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();

		if ( empty( $options ) ) {
			return true;
		}

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) || ! isset( $option['value'] ) ) {
				continue;
			}

			if ( $value === sanitize_text_field( (string) $option['value'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize a form version string.
	 *
	 * @param mixed $value Raw version.
	 * @return string
	 */
	protected function sanitize_form_version( $value ) {
		$value = sanitize_text_field( (string) $value );
		$value = preg_replace( '/[^A-Za-z0-9._-]/', '', $value );

		return substr( (string) $value, 0, 30 );
	}
}
