<?php
/**
 * Inquiry field registry manager.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages inquiry field definitions.
 */
class YBY_Inquiry_Field_Manager {

	/**
	 * Option name for stored field definitions.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'yby_inquiry_fields';

	/**
	 * Supported field types.
	 *
	 * @var array<int, string>
	 */
	protected static $supported_types = array(
		'text',
		'email',
		'tel',
		'textarea',
		'select',
		'checkbox',
		'hidden',
	);

	/**
	 * Return default field registry definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function defaults() {
		return $this->sanitize_fields(
			array(
				'name'             => array(
					'id'           => 'name',
					'type'         => 'text',
					'label'        => 'Name',
					'required'     => true,
					'enabled'      => true,
					'order'        => 10,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => 'name',
				),
				'company'          => array(
					'id'           => 'company',
					'type'         => 'text',
					'label'        => 'Company',
					'required'     => false,
					'enabled'      => true,
					'order'        => 20,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => 'organization',
				),
				'email'            => array(
					'id'           => 'email',
					'type'         => 'email',
					'label'        => 'Email',
					'required'     => false,
					'enabled'      => true,
					'order'        => 30,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => 'email',
				),
				'whatsapp'         => array(
					'id'           => 'whatsapp',
					'type'         => 'tel',
					'label'        => 'WhatsApp',
					'required'     => false,
					'enabled'      => true,
					'order'        => 40,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => 'tel',
				),
				'contact'          => array(
					'id'           => 'contact',
					'type'         => 'text',
					'label'        => 'Email / WhatsApp',
					'required'     => false,
					'enabled'      => true,
					'order'        => 45,
					'options'      => array(),
					'placeholder'  => 'Email or WhatsApp',
					'autocomplete' => '',
				),
				'country'          => array(
					'id'           => 'country',
					'type'         => 'text',
					'label'        => 'Country',
					'required'     => false,
					'enabled'      => true,
					'order'        => 50,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => 'country-name',
				),
				'product_interest' => array(
					'id'           => 'product_interest',
					'type'         => 'text',
					'label'        => 'Product Interest',
					'required'     => false,
					'enabled'      => true,
					'order'        => 60,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => '',
				),
				'quantity'         => array(
					'id'           => 'quantity',
					'type'         => 'text',
					'label'        => 'Quantity',
					'required'     => false,
					'enabled'      => true,
					'order'        => 70,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => '',
				),
				'farm_size'        => array(
					'id'           => 'farm_size',
					'type'         => 'text',
					'label'        => 'Farm Size',
					'required'     => false,
					'enabled'      => true,
					'order'        => 80,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => '',
				),
				'customization'    => array(
					'id'           => 'customization',
					'type'         => 'textarea',
					'label'        => 'Customization Requirements',
					'required'     => false,
					'enabled'      => true,
					'order'        => 90,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => '',
				),
				'project_path'     => array(
					'id' => 'project_path', 'type' => 'select', 'label' => 'Project Path', 'required' => false, 'enabled' => true, 'order' => 95,
					'options' => array( array( 'value' => 'existing_bottle_customization', 'label' => 'Customize an existing bottle' ), array( 'value' => 'new_custom_mold', 'label' => 'Develop a new custom mold' ) ), 'placeholder' => '', 'autocomplete' => '',
				),
				'spirit_type'      => array( 'id' => 'spirit_type', 'type' => 'text', 'label' => 'Spirit Type', 'required' => false, 'enabled' => true, 'order' => 96, 'options' => array(), 'placeholder' => '', 'autocomplete' => '' ),
				'selected_components' => array( 'id' => 'selected_components', 'type' => 'textarea', 'label' => 'Selected Components', 'required' => false, 'enabled' => true, 'order' => 97, 'options' => array(), 'placeholder' => '', 'autocomplete' => '' ),
				'estimated_quantity' => array( 'id' => 'estimated_quantity', 'type' => 'text', 'label' => 'Estimated Quantity', 'required' => false, 'enabled' => true, 'order' => 98, 'options' => array(), 'placeholder' => '', 'autocomplete' => '' ),
				'target_launch_date' => array( 'id' => 'target_launch_date', 'type' => 'text', 'label' => 'Target Launch Date', 'required' => false, 'enabled' => true, 'order' => 99, 'options' => array(), 'placeholder' => '', 'autocomplete' => '' ),
				'selected_model' => array( 'id' => 'selected_model', 'type' => 'text', 'label' => 'Selected Model', 'required' => false, 'enabled' => true, 'order' => 100, 'options' => array(), 'placeholder' => '', 'autocomplete' => '' ),
				'message'          => array(
					'id'           => 'message',
					'type'         => 'textarea',
					'label'        => 'Project Details',
					'required'     => false,
					'enabled'      => true,
					'order'        => 110,
					'options'      => array(),
					'placeholder'  => '',
					'autocomplete' => '',
				),
			)
		);
	}

	/**
	 * Return all runtime fields.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_fields() {
		$stored   = get_option( self::OPTION_KEY, null );
		$defaults = $this->defaults();
		$fields   = is_array( $stored ) ? $stored : $defaults;

		if ( is_array( $stored ) && ! isset( $fields['contact'] ) && isset( $defaults['contact'] ) ) {
			$fields['contact'] = $defaults['contact'];
		}
		$fields = $this->sanitize_fields( $fields );
		$fields = apply_filters( 'yby_inquiry_fields', $fields );

		return $this->sanitize_fields( $fields );
	}

	/**
	 * Return one field definition by ID.
	 *
	 * @param string $field_id Field ID.
	 * @return array<string, mixed>|null
	 */
	public function get_field( $field_id ) {
		$field_id = sanitize_key( $field_id );
		$fields   = $this->get_fields();

		return isset( $fields[ $field_id ] ) ? $fields[ $field_id ] : null;
	}

	/**
	 * Return all enabled field definitions ordered by `order`.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_enabled_fields() {
		$fields = array_filter(
			$this->get_fields(),
			static function ( $field ) {
				return ! empty( $field['enabled'] );
			}
		);

		uasort( $fields, array( $this, 'compare_fields' ) );

		return $fields;
	}

	/**
	 * Sanitize a field registry payload.
	 *
	 * @param mixed $fields Raw fields payload.
	 * @return array<string, array<string, mixed>>
	 */
	public function sanitize_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $fields as $field_id => $field ) {
			if ( is_array( $field ) && ! isset( $field['id'] ) ) {
				$field['id'] = $field_id;
			}

			$normalized = $this->sanitize_field( $field );

			if ( null === $normalized ) {
				continue;
			}

			$sanitized[ $normalized['id'] ] = $normalized;
		}

		uasort( $sanitized, array( $this, 'compare_fields' ) );

		return $sanitized;
	}

	/**
	 * Sanitize one field definition.
	 *
	 * @param mixed $field Raw field definition.
	 * @return array<string, mixed>|null
	 */
	public function sanitize_field( $field ) {
		if ( ! is_array( $field ) ) {
			return null;
		}

		$field_id = isset( $field['id'] ) ? sanitize_key( $field['id'] ) : '';
		$type     = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : '';

		if ( '' === $field_id || '' === $type || ! in_array( $type, self::$supported_types, true ) ) {
			return null;
		}

		return array(
			'id'           => $field_id,
			'type'         => $type,
			'label'        => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
			'required'     => ! empty( $field['required'] ),
			'enabled'      => ! array_key_exists( 'enabled', $field ) || ! empty( $field['enabled'] ),
			'order'        => $this->sanitize_non_negative_int( isset( $field['order'] ) ? $field['order'] : 0 ),
			'options'      => $this->sanitize_options( isset( $field['options'] ) ? $field['options'] : array() ),
			'placeholder'  => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
			'autocomplete' => $this->sanitize_autocomplete( isset( $field['autocomplete'] ) ? $field['autocomplete'] : '' ),
		);
	}

	/**
	 * Compare field definitions for stable sorting.
	 *
	 * @param array<string, mixed> $left Left field.
	 * @param array<string, mixed> $right Right field.
	 * @return int
	 */
	protected function compare_fields( $left, $right ) {
		$order_compare = (int) $left['order'] - (int) $right['order'];

		if ( 0 !== $order_compare ) {
			return $order_compare;
		}

		return strcmp( (string) $left['id'], (string) $right['id'] );
	}

	/**
	 * Sanitize select or checkbox options.
	 *
	 * @param mixed $options Raw options.
	 * @return array<int, array<string, string>>
	 */
	protected function sanitize_options( $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $options as $value => $option ) {
			$normalized = null;

			if ( is_array( $option ) ) {
				$normalized = array(
					'value' => sanitize_text_field( isset( $option['value'] ) ? $option['value'] : $value ),
					'label' => sanitize_text_field( isset( $option['label'] ) ? $option['label'] : '' ),
				);
			} elseif ( is_scalar( $option ) ) {
				$normalized = array(
					'value' => sanitize_text_field( (string) $value ),
					'label' => sanitize_text_field( (string) $option ),
				);
			}

			if ( empty( $normalized['value'] ) && empty( $normalized['label'] ) ) {
				continue;
			}

			$sanitized[] = $normalized;
		}

		return array_values( $sanitized );
	}

	/**
	 * Sanitize autocomplete attribute values.
	 *
	 * @param mixed $autocomplete Raw autocomplete value.
	 * @return string
	 */
	protected function sanitize_autocomplete( $autocomplete ) {
		$autocomplete = sanitize_text_field( (string) $autocomplete );
		$autocomplete = preg_replace( '/[^a-zA-Z0-9_\-\s]/', '', $autocomplete );

		return trim( preg_replace( '/\s+/', ' ', (string) $autocomplete ) );
	}

	/**
	 * Normalize non-negative integer values.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected function sanitize_non_negative_int( $value ) {
		return max( 0, absint( $value ) );
	}
}
