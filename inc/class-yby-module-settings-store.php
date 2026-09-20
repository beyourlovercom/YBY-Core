<?php
/**
 * Versioned option storage seam for Andy Core modules.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Module_Settings_Store {

	public static function contract( $module_id ) {
		$module = YBY_Module_Registry::module( $module_id );
		if ( empty( $module ) || empty( $module['storage'] ) || ! is_array( $module['storage'] ) ) {
			return array();
		}
		return $module['storage'];
	}

	public static function option_key( $module_id ) {
		$contract = self::contract( $module_id );
		return isset( $contract['option_key'] ) ? (string) $contract['option_key'] : '';
	}

	public static function schema_version( $module_id ) {
		$contract = self::contract( $module_id );
		return isset( $contract['schema_version'] ) ? (string) $contract['schema_version'] : '';
	}

	public static function exists( $module_id ) {
		$key = self::option_key( $module_id );
		if ( '' === $key ) { return false; }
		return null !== get_option( $key, null );
	}

	public static function get( $module_id, $defaults = array(), $sanitize_callback = null ) {
		$key = self::option_key( $module_id );
		if ( '' === $key ) { return is_array( $defaults ) ? $defaults : array(); }

		$stored = get_option( $key, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$defaults = is_array( $defaults ) ? $defaults : array();
		$value = wp_parse_args( $stored, $defaults );

		if ( null !== $sanitize_callback ) {
			if ( ! is_callable( $sanitize_callback ) ) { return $defaults; }
			$value = call_user_func( $sanitize_callback, $value );
		}
		return is_array( $value ) ? $value : $defaults;
	}

	public static function save( $module_id, $raw, $sanitize_callback ) {
		$key = self::option_key( $module_id );
		if ( '' === $key || ! is_callable( $sanitize_callback ) ) { return false; }

		$value = call_user_func( $sanitize_callback, $raw );
		if ( ! is_array( $value ) ) { return false; }

		if ( null === get_option( $key, null ) ) {
			return add_option( $key, $value, '', false );
		}
		return update_option( $key, $value, false );
	}
}
