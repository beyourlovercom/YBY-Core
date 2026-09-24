<?php
/**
 * First-party addon registry.
 *
 * Active addons register metadata through the andy_core_register_addons action.
 * Installed-but-inactive addons are discovered from explicit Andy Core Addon headers.
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
		if ( '' === $id || ! is_array( $metadata ) || isset( self::$registered[ $id ] ) ) { return false; }

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

		self::$registered[ $id ] = self::normalize(
			$id,
			$metadata,
			array( 'installed' => true, 'active' => true )
		);
		return true;
	}

	protected static function normalize( $id, $metadata, $runtime = array() ) {
		$requires = $metadata['requires'] ?? array();
		if ( is_string( $requires ) ) {
			$requires = preg_split( '/\s*,\s*/', $requires, -1, PREG_SPLIT_NO_EMPTY );
		}

		return array(
			'id' => sanitize_key( $id ),
			'name' => trim( (string) ( $metadata['name'] ?? $id ) ),
			'version' => sanitize_text_field( (string) ( $metadata['version'] ?? '' ) ),
			'plugin_file' => sanitize_text_field( (string) ( $metadata['plugin_file'] ?? '' ) ),
			'min_core_version' => sanitize_text_field( (string) ( $metadata['min_core_version'] ?? '' ) ),
			'requires' => array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $requires ) ) ) ),
			'admin_url' => esc_url_raw( (string) ( $metadata['admin_url'] ?? '' ) ),
			'description' => sanitize_text_field( (string) ( $metadata['description'] ?? '' ) ),
			'installed' => ! empty( $runtime['installed'] ),
			'active' => ! empty( $runtime['active'] ),
		);
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

	public static function installed_addons() {
		if ( ! function_exists( 'get_plugins' ) && defined( 'ABSPATH' ) ) {
			$plugin_api = ABSPATH . 'wp-admin/includes/plugin.php';
			if ( is_readable( $plugin_api ) ) { require_once $plugin_api; }
		}
		if ( ! function_exists( 'get_plugins' ) ) { return array(); }

		$installed = array();
		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$headers = array();
			if ( function_exists( 'get_file_data' ) && defined( 'WP_PLUGIN_DIR' ) ) {
				$headers = get_file_data(
					WP_PLUGIN_DIR . '/' . $plugin_file,
					array(
						'addon_id' => 'Andy Core Addon ID',
						'min_core_version' => 'Andy Core Min Version',
					),
					'plugin'
				);
			}

			$addon_id = sanitize_key( (string) ( $headers['addon_id'] ?? '' ) );
			if ( '' === $addon_id ) { continue; }

			$requires = $plugin_data['RequiresPlugins'] ?? array();
			$active = function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin_file ) : isset( self::$registered[ $addon_id ] );

			$installed[ $addon_id ] = self::normalize(
				$addon_id,
				array(
					'name' => $plugin_data['Name'] ?? $addon_id,
					'version' => $plugin_data['Version'] ?? '',
					'plugin_file' => $plugin_file,
					'min_core_version' => $headers['min_core_version'] ?? '',
					'requires' => $requires,
					'description' => $plugin_data['Description'] ?? '',
				),
				array( 'installed' => true, 'active' => $active )
			);
		}
		return $installed;
	}

	public static function catalog() {
		$catalog = self::installed_addons();
		foreach ( self::addons() as $id => $addon ) {
			$catalog[ $id ] = array_merge( $catalog[ $id ] ?? array(), $addon, array( 'installed' => true, 'active' => true ) );
		}
		ksort( $catalog );
		return $catalog;
	}

	public static function addon( $id ) {
		$catalog = self::catalog();
		$id = sanitize_key( $id );
		return isset( $catalog[ $id ] ) ? $catalog[ $id ] : array();
	}

	public static function core_compatible( $addon ) {
		if ( empty( $addon['min_core_version'] ) ) { return true; }
		if ( ! defined( 'YBY_CORE_VERSION' ) ) { return false; }
		return version_compare( YBY_CORE_VERSION, $addon['min_core_version'], '>=' );
	}

	public static function requirement_status( $requirement ) {
		$requirement = sanitize_key( $requirement );
		if ( in_array( $requirement, array( 'yby-core', 'andy-core' ), true ) ) {
			return defined( 'YBY_CORE_VERSION' );
		}
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
				'installed' => false,
				'registered' => false,
				'active' => false,
				'compatible' => false,
				'requirements_met' => false,
				'state' => 'not_installed',
			);
		}

		$active = ! empty( $addon['active'] );
		$core_compatible = self::core_compatible( $addon );
		$requirements_met = self::requirements_met( $addon );
		$registered = isset( self::$registered[ sanitize_key( $id ) ] );

		$state = 'inactive';
		if ( $active ) {
			$state = $core_compatible && $requirements_met ? 'ready' : 'blocked';
		}

		return array(
			'installed' => true,
			'registered' => $registered,
			'active' => $active,
			'compatible' => $core_compatible,
			'requirements_met' => $requirements_met,
			'state' => $state,
		);
	}

	public static function system_status() {
		$summary = array();
		foreach ( self::catalog() as $id => $addon ) {
			$status = self::status( $id );
			$summary[ $id ] = array(
				'name' => $addon['name'],
				'version' => $addon['version'],
				'state' => $status['state'],
				'active' => $status['active'],
				'core_compatible' => $status['compatible'],
				'requirements_met' => $status['requirements_met'],
			);
		}
		return $summary;
	}
}
