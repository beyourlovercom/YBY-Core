<?php
/**
 * First-party addon registry.
 *
 * Addons register metadata through the andy_core_register_addons action.
 * Core owns dependency/status conventions only; addon business logic stays out of Core.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Addon_Registry {
	const VERSION = '1';

	protected static $registered = array();
	protected static $discovered = false;

	public static function register( $id, $metadata ) {
		$id = sanitize_key( $id );
		if ( '' === $id || ! is_array( $metadata ) || isset( self::$registered[ $id ] ) ) {
			return false;
		}

		$metadata = wp_parse_args(
			$metadata,
			array(
				'name' => $id,
				'version' => '',
				'plugin_file' => '',
				'min_core_version' => '',
				'requires' => array(),
				'admin_url' => '',
				'description' => '',
			)
		);

		$name = trim( (string) $metadata['name'] );
		if ( '' === $name ) { return false; }

		self::$registered[ $id ] = array(
			'id' => $id,
			'name' => $name,
			'version' => sanitize_text_field( (string) $metadata['version'] ),
			'plugin_file' => sanitize_text_field( (string) $metadata['plugin_file'] ),
			'min_core_version' => sanitize_text_field( (string) $metadata['min_core_version'] ),
			'requires' => array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $metadata['requires'] ) ) ) ),
			'admin_url' => esc_url_raw( (string) $metadata['admin_url'] ),
			'description' => sanitize_text_field( (string) $metadata['description'] ),
		);
		return true;
	}

	public static function discover() {
		if ( self::$discovered ) { return; }
		self::$discovered = true;
		do_action( 'andy_core_register_addons' );
	}

	public static function reset_for_tests() {
		self::$registered = array();
		self::$discovered = false;
	}

	public static function addons() {
		self::discover();
		return self::$registered;
	}

	public static function addon( $id ) {
		$addons = self::addons();
		$id = sanitize_key( $id );
		return isset( $addons[ $id ] ) ? $addons[ $id ] : array();
	}

	public static function core_compatible( $addon ) {
		if ( empty( $addon['min_core_version'] ) ) { return true; }
		if ( ! defined( 'YBY_CORE_VERSION' ) ) { return false; }
		return version_compare( YBY_CORE_VERSION, $addon['min_core_version'], '>=' );
	}

	public static function requirement_status( $requirement ) {
		$requirement = sanitize_key( $requirement );
		if ( 'woocommerce' === $requirement ) {
			return class_exists( 'WooCommerce' );
		}
		return (bool) apply_filters( 'andy_core_addon_requirement_status', false, $requirement );
	}

	public static function requirements_met( $addon ) {
		foreach ( (array) ( $addon['requires'] ?? array() ) as $requirement ) {
			if ( ! self::requirement_status( $requirement ) ) { return false; }
		}
		return true;
	}

	public static function status( $id ) {
		$addon = self::addon( $id );
		if ( empty( $addon ) ) {
			return array(
				'registered' => false,
				'active' => false,
				'compatible' => false,
				'requirements_met' => false,
				'state' => 'not_registered',
			);
		}

		$core_compatible = self::core_compatible( $addon );
		$requirements_met = self::requirements_met( $addon );
		return array(
			'registered' => true,
			'active' => true,
			'compatible' => $core_compatible,
			'requirements_met' => $requirements_met,
			'state' => $core_compatible && $requirements_met ? 'ready' : 'blocked',
		);
	}

	public static function system_status() {
		$summary = array();
		foreach ( self::addons() as $id => $addon ) {
			$status = self::status( $id );
			$summary[ $id ] = array(
				'name' => $addon['name'],
				'version' => $addon['version'],
				'state' => $status['state'],
				'core_compatible' => $status['compatible'],
				'requirements_met' => $status['requirements_met'],
			);
		}
		return $summary;
	}
}
