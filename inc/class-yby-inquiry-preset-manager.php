<?php
/**
 * Inquiry preset registry manager.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages inquiry preset definitions.
 */
class YBY_Inquiry_Preset_Manager {

	/**
	 * Option name for stored preset definitions.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'yby_inquiry_presets';

	/**
	 * Supported contact validation requirements.
	 *
	 * @var array<int, string>
	 */
	protected static $contact_requirements = array(
		'email',
		'whatsapp',
		'email_or_whatsapp',
		'none',
	);

	/**
	 * Field registry manager.
	 *
	 * @var YBY_Inquiry_Field_Manager
	 */
	protected $field_manager;

	/**
	 * Constructor.
	 *
	 * @param YBY_Inquiry_Field_Manager|null $field_manager Field manager.
	 */
	public function __construct( $field_manager = null ) {
		$this->field_manager = $field_manager instanceof YBY_Inquiry_Field_Manager ? $field_manager : new YBY_Inquiry_Field_Manager();
	}

	/**
	 * Return default preset registry definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function defaults() {
		return $this->sanitize_presets(
			array(
				'irrigation_quick_inquiry' => array(
					'id'               => 'irrigation_quick_inquiry',
					'label'            => 'Irrigation Quick Inquiry',
					'enabled'          => true,
					'version'          => '1.0',
					'fields'           => array(
						'name',
						'email',
						'whatsapp',
						'country',
						'farm_size',
						'message',
					),
					'validation'       => array(
						'contact_requirement' => 'email_or_whatsapp',
					),
					'source_component' => 'inquiry_modal',
				),
				'bottle_wholesale_inquiry' => array(
					'id'               => 'bottle_wholesale_inquiry',
					'label'            => 'Bottle Wholesale Inquiry',
					'enabled'          => true,
					'version'          => '1.0',
					'fields'           => array(
						'name',
						'company',
						'email',
						'whatsapp',
						'country',
						'product_interest',
						'quantity',
						'customization',
						'message',
					),
					'validation'       => array(
						'contact_requirement' => 'email_or_whatsapp',
					),
					'source_component' => 'inquiry_modal',
				),
			)
		);
	}

	/**
	 * Return all runtime presets.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_presets() {
		$stored  = get_option( self::OPTION_KEY, null );
		$presets = is_array( $stored ) ? $stored : $this->defaults();
		$presets = $this->sanitize_presets( $presets );
		$presets = apply_filters( 'yby_inquiry_presets', $presets );

		return $this->sanitize_presets( $presets );
	}

	/**
	 * Return one preset definition by ID.
	 *
	 * @param string $preset_id Preset ID.
	 * @return array<string, mixed>|null
	 */
	public function get_preset( $preset_id ) {
		$preset_id = sanitize_key( $preset_id );
		$presets   = $this->get_presets();

		return isset( $presets[ $preset_id ] ) ? $presets[ $preset_id ] : null;
	}

	/**
	 * Return all enabled presets.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_enabled_presets() {
		return array_filter(
			$this->get_presets(),
			static function ( $preset ) {
				return ! empty( $preset['enabled'] );
			}
		);
	}

	/**
	 * Sanitize preset registry definitions.
	 *
	 * @param mixed $presets Raw presets.
	 * @return array<string, array<string, mixed>>
	 */
	public function sanitize_presets( $presets ) {
		if ( ! is_array( $presets ) ) {
			return array();
		}

		$sanitized         = array();
		$allowed_field_ids = array_keys( $this->field_manager->get_fields() );

		foreach ( $presets as $preset_id => $preset ) {
			if ( is_array( $preset ) && ! isset( $preset['id'] ) ) {
				$preset['id'] = $preset_id;
			}

			$normalized = $this->sanitize_preset( $preset, $allowed_field_ids );

			if ( null === $normalized ) {
				continue;
			}

			$sanitized[ $normalized['id'] ] = $normalized;
		}

		return $sanitized;
	}

	/**
	 * Sanitize one preset definition.
	 *
	 * @param mixed                   $preset Raw preset.
	 * @param array<int, string>|null $allowed_field_ids Allowed field IDs.
	 * @return array<string, mixed>|null
	 */
	public function sanitize_preset( $preset, $allowed_field_ids = null ) {
		if ( ! is_array( $preset ) ) {
			return null;
		}

		$preset_id = isset( $preset['id'] ) ? sanitize_key( $preset['id'] ) : '';

		if ( '' === $preset_id ) {
			return null;
		}

		if ( ! is_array( $allowed_field_ids ) ) {
			$allowed_field_ids = array_keys( $this->field_manager->get_fields() );
		}

		return array(
			'id'               => $preset_id,
			'label'            => isset( $preset['label'] ) ? sanitize_text_field( $preset['label'] ) : '',
			'enabled'          => ! array_key_exists( 'enabled', $preset ) || ! empty( $preset['enabled'] ),
			'version'          => isset( $preset['version'] ) ? sanitize_text_field( $preset['version'] ) : '1.0',
			'fields'           => $this->sanitize_preset_fields( isset( $preset['fields'] ) ? $preset['fields'] : array(), $allowed_field_ids ),
			'validation'       => $this->sanitize_validation( isset( $preset['validation'] ) ? $preset['validation'] : array() ),
			'source_component' => isset( $preset['source_component'] ) ? sanitize_text_field( $preset['source_component'] ) : '',
		);
	}

	/**
	 * Sanitize preset field references.
	 *
	 * @param mixed              $field_ids Raw field IDs.
	 * @param array<int, string> $allowed_field_ids Allowed field IDs.
	 * @return array<int, string>
	 */
	protected function sanitize_preset_fields( $field_ids, $allowed_field_ids ) {
		if ( ! is_array( $field_ids ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $field_ids as $field_id ) {
			$field_id = sanitize_key( $field_id );

			if ( '' === $field_id || in_array( $field_id, $sanitized, true ) ) {
				continue;
			}

			if ( ! in_array( $field_id, $allowed_field_ids, true ) ) {
				continue;
			}

			$sanitized[] = $field_id;
		}

		return $sanitized;
	}

	/**
	 * Sanitize preset validation rules.
	 *
	 * @param mixed $validation Raw validation rules.
	 * @return array<string, string>
	 */
	protected function sanitize_validation( $validation ) {
		if ( ! is_array( $validation ) ) {
			return array();
		}

		$normalized = array();

		if ( isset( $validation['contact_requirement'] ) ) {
			$contact_requirement = sanitize_key( $validation['contact_requirement'] );

			if ( in_array( $contact_requirement, self::$contact_requirements, true ) ) {
				$normalized['contact_requirement'] = $contact_requirement;
			}
		}

		return $normalized;
	}
}
