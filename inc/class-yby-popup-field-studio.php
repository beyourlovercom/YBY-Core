<?php
/**
 * Site-local field library and popup preset contract.
 *
 * No per-site plugin forks: this option belongs to the current WordPress
 * database. Legacy Inquiry OS presets and fields are never rewritten.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Popup_Field_Studio {
	const OPTION = 'yby_popup_field_studio_v2';
	const MAX_FIELDS = 30;
	const MAX_PRESETS = 16;
	const MAX_OPTIONS = 30;

	public static function boot() {
		add_filter( 'yby_inquiry_fields', array( __CLASS__, 'filter_fields' ), 20, 1 );
		add_filter( 'yby_inquiry_presets', array( __CLASS__, 'filter_presets' ), 20, 1 );
	}

	public static function empty_state() {
		return array( 'version' => 2, 'fields' => array(), 'presets' => array() );
	}

	public static function get_state() {
		return self::sanitize( get_option( self::OPTION, array() ) );
	}

	private static function text( $value, $limit = 150 ) {
		return is_scalar( $value ) ? substr( sanitize_text_field( (string) $value ), 0, $limit ) : '';
	}

	private static function field_id( $id ) {
		$id = is_scalar( $id ) ? sanitize_key( (string) $id ) : '';
		return preg_match( '/^site_[a-z0-9_]{3,55}$/', $id ) ? $id : '';
	}

	private static function preset_id( $id ) {
		$id = is_scalar( $id ) ? sanitize_key( (string) $id ) : '';
		return preg_match( '/^site_[a-z0-9_]{3,55}$/', $id ) ? $id : '';
	}

	private static function sanitize_mode( $raw, $field ) {
		$raw = is_array( $raw ) ? $raw : array();
		$enabled = ! empty( $raw['enabled'] );
		return array(
			'enabled'     => $enabled,
			'required'    => $enabled && ! empty( $raw['required'] ),
			'label'       => self::text( isset( $raw['label'] ) ? $raw['label'] : $field['label'], 120 ),
			'placeholder' => self::text( isset( $raw['placeholder'] ) ? $raw['placeholder'] : $field['placeholder'], 180 ),
		);
	}

	/**
	 * Whitelist all stored keys, sizes, field types and IDs. Rejected IDs can
	 * never replace built-in field or preset IDs.
	 */
	public static function sanitize( $raw ) {
		$out = self::empty_state();
		if ( ! is_array( $raw ) ) { return $out; }

		$types = array( 'text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'multiselect', 'checkbox' );
		$custom = isset( $raw['fields'] ) && is_array( $raw['fields'] ) ? $raw['fields'] : array();
		foreach ( array_slice( $custom, 0, self::MAX_FIELDS, true ) as $key => $definition ) {
			if ( ! is_array( $definition ) ) { continue; }
			$id = self::field_id( isset( $definition['id'] ) ? $definition['id'] : $key );
			$type = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : 'text';
			if ( '' === $id || isset( $out['fields'][ $id ] ) || ! in_array( $type, $types, true ) ) { continue; }
			$options = array();
			if ( in_array( $type, array( 'select', 'radio', 'multiselect' ), true ) && isset( $definition['options'] ) && is_array( $definition['options'] ) ) {
				foreach ( array_slice( $definition['options'], 0, self::MAX_OPTIONS ) as $option ) {
					$label = self::text( is_array( $option ) ? ( $option['label'] ?? '' ) : $option, 100 );
					if ( '' === $label ) { continue; }
					$value = self::text( is_array( $option ) ? ( $option['value'] ?? $label ) : $label, 100 );
					if ( '' === $value ) { continue; }
					$options[ $value ] = array( 'value' => $value, 'label' => $label );
				}
			}
			$out['fields'][ $id ] = array(
				'id' => $id,
				'type' => $type,
				'label' => self::text( $definition['label'] ?? $id, 120 ),
				'placeholder' => self::text( $definition['placeholder'] ?? '', 180 ),
				'options' => array_values( $options ),
			);
		}

		$presets = isset( $raw['presets'] ) && is_array( $raw['presets'] ) ? $raw['presets'] : array();
		foreach ( array_slice( $presets, 0, self::MAX_PRESETS, true ) as $key => $preset ) {
			if ( ! is_array( $preset ) ) { continue; }
			$id = self::preset_id( $preset['id'] ?? $key );
			if ( ! $id || isset( $out['presets'][ $id ] ) ) { continue; }
			$layout = isset( $preset['layout'] ) ? sanitize_key( $preset['layout'] ) : 'split_visual';
			if ( ! in_array( $layout, array( 'classic', 'split_visual', 'premium_split' ), true ) ) { $layout = 'split_visual'; }
			$allowed_fields = array();
			if ( class_exists( 'YBY_Inquiry_Field_Manager' ) ) {
				// Only trusted built-in fields plus site-owned fields are selectable.
				$allowed_fields = ( new YBY_Inquiry_Field_Manager() )->defaults();
			}
			$allowed_fields = array_merge( $allowed_fields, $out['fields'] );
			$configs = array();
			$config_in = isset( $preset['configs'] ) && is_array( $preset['configs'] ) ? $preset['configs'] : array();
			foreach ( array_slice( $config_in, 0, 70, true ) as $field_key => $cfg ) {
				$field_key = sanitize_key( $field_key );
				if ( ! isset( $allowed_fields[ $field_key ] ) || ! is_array( $cfg ) ) { continue; }
				$field = $allowed_fields[ $field_key ];
				$configs[ $field_key ] = array(
					'desktop' => self::sanitize_mode( $cfg['desktop'] ?? array(), $field ),
					'mobile'  => self::sanitize_mode( $cfg['mobile'] ?? array(), $field ),
				);
			}
			$profiles = array();
			foreach ( array_slice( is_array( $preset['page_profiles'] ?? null ) ? $preset['page_profiles'] : array(), 0, 12 ) as $profile ) {
				if ( ! is_scalar( $profile ) ) { continue; }
				$profile = sanitize_key( (string) $profile );
				if ( '' !== $profile ) { $profiles[ $profile ] = $profile; }
			}
			$out['presets'][ $id ] = array(
				'id' => $id,
				'label' => self::text( $preset['label'] ?? $id, 120 ),
				'enabled' => ! empty( $preset['enabled'] ),
				'layout' => $layout,
				'title' => self::text( $preset['title'] ?? '', 160 ),
				'subtitle' => self::text( $preset['subtitle'] ?? '', 300 ),
				'image' => is_scalar( $preset['image'] ?? '' ) ? esc_url_raw( (string) $preset['image'] ) : '',
				'page_profiles' => array_values( $profiles ),
				'configs' => $configs,
			);
		}
		return $out;
	}

	public static function has_preset( $id ) {
		$id = self::preset_id( $id );
		$state = self::get_state();
		return '' !== $id && isset( $state['presets'][ $id ] ) && ! empty( $state['presets'][ $id ]['enabled'] );
	}

	public static function get_preset( $id ) {
		$state = self::get_state();
		$id = self::preset_id( $id );
		return $id && ! empty( $state['presets'][ $id ]['enabled'] ) ? $state['presets'][ $id ] : null;
	}

	public static function filter_fields( $fields ) {
		$state = self::get_state();
		if ( ! is_array( $fields ) ) { return $fields; }
		foreach ( $state['fields'] as $id => $f ) {
			if ( isset( $fields[ $id ] ) ) { continue; }
			$fields[ $id ] = array_merge(
				$f,
				array( 'required' => false, 'enabled' => true, 'order' => 200 + count( $fields ), 'autocomplete' => '' )
			);
		}
		return $fields;
	}

	/** Register opt-in site presets using the existing Inquiry OS pipeline. */
	public static function filter_presets( $presets ) {
		if ( ! is_array( $presets ) ) { return $presets; }
		$state = self::get_state();
		foreach ( $state['presets'] as $id => $p ) {
			if ( isset( $presets[ $id ] ) || ! $p['enabled'] ) { continue; }
			$desktop = array();
			$mobile = array();
			foreach ( $p['configs'] as $fid => $modes ) {
				if ( $modes['desktop']['enabled'] ) { $desktop[] = $fid; }
				if ( $modes['mobile']['enabled'] ) { $mobile[] = $fid; }
			}
			$presets[ $id ] = array(
				'id' => $id, 'label' => $p['label'], 'enabled' => true, 'version' => '2.0',
				'fields' => array_values( array_unique( array_merge( $desktop, $mobile ) ) ),
				'mobile_fields' => $mobile,
				'validation' => array( 'contact_requirement' => 'none' ),
				'source_component' => 'inquiry_modal',
				'page_profiles' => $p['page_profiles'],
			);
			// Use desktop-only rendering list for layouts; both variants will
			// still be allowed by the server via the union in fields.
			$presets[ $id ]['_studio_desktop_fields'] = $desktop;
		}
		return $presets;
	}

	public static function desktop_fields( $id ) {
		$p = self::get_preset( $id );
		if ( ! $p ) { return array(); }
		$out = array();
		foreach ( $p['configs'] as $fid => $m ) {
			if ( $m['desktop']['enabled'] ) { $out[] = $fid; }
		}
		return $out;
	}

	public static function render_attributes( $id ) {
		$p = self::get_preset( $id );
		if ( ! $p ) { return array(); }
		$attributes = array(
			'title' => $p['title'], 'subtitle' => $p['subtitle'],
			'image' => $p['image'], 'layout' => $p['layout'],
			'class' => array( 'yby-popup-field-studio' ),
			'field_labels' => array(), 'field_placeholders' => array(),
			'field_required' => array(),
			'mobile_field_labels' => array(), 'mobile_field_placeholders' => array(),
			'mobile_field_required' => array(),
		);
		foreach ( $p['configs'] as $id => $config ) {
			foreach ( array( 'desktop', 'mobile' ) as $device ) {
				$prefix = 'mobile' === $device ? 'mobile_' : '';
				$attributes[ $prefix . 'field_labels' ][ $id ] = $config[ $device ]['label'];
				$attributes[ $prefix . 'field_placeholders' ][ $id ] = $config[ $device ]['placeholder'];
				$attributes[ $prefix . 'field_required' ][ $id ] = $config[ $device ]['required'];
			}
		}
		return $attributes;
	}

	/** For a site-studio form, server-side validation mirrors saved per-device rules. */
	public static function required_errors( $lead ) {
		$p = isset( $lead['source_preset'] ) ? self::get_preset( $lead['source_preset'] ) : null;
		if ( ! $p ) { return array(); }
		$device = isset( $lead['form_device'] ) && 'mobile' === $lead['form_device'] ? 'mobile' : 'desktop';
		$custom = isset( $lead['custom_fields'] ) && is_array( $lead['custom_fields'] ) ? $lead['custom_fields'] : array();
		$errors = array();
		foreach ( $p['configs'] as $id => $cfg ) {
			if ( empty( $cfg[ $device ]['enabled'] ) || empty( $cfg[ $device ]['required'] ) ) { continue; }
			$key = 'message' === $id ? 'project_details' : $id;
			$value = isset( $lead[ $key ] ) ? $lead[ $key ] : ( $custom[ $id ] ?? '' );
			if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
				$errors[ $id ] = __( 'This field is required.', 'yby-core' );
			} elseif ( 'email' === $id && ! is_email( $value ) ) {
				$errors[ $id ] = __( 'Invalid email', 'yby-core' );
			}
		}
		return $errors;
	}
}
