<?php
/**
 * First-party addon registry.
 *
 * Andy Core owns only the generic addon contract. Addons own their business
 * runtime and register through the andy_core_register_addons action.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Addon_Registry {
	const VERSION = '1';
	const REGISTER_ACTION = 'andy_core_register_addons';

	protected static $addons = array();
	protected static $discovered = false;

	public static function register( $id, $args = array() ) {
		$id = sanitize_key( $id );
		if ( '' === $id ) { return false; }
		$args = is_array( $args ) ? $args : array();
		$existing = isset( self::$addons[ $id ] ) ? self::$addons[ $id ] : array();
		$defaults = array(
			'id' => $id,
			'name' => $id,
			'description' => '',
			'version' => '',
			'plugin_file' => '',
			'requires_core' => '',
			'requires_plugins' => array(),
			'settings_url' => '',
			'source' => 'runtime',
		);
		$addon = array_merge( $defaults, $existing, $args );
		$addon['id'] = $id;
		$addon['name'] = trim( (string) $addon['name'] );
		$addon['description'] = trim( (string) $addon['description'] );
		$addon['version'] = trim( (string) $addon['version'] );
		$addon['plugin_file'] = self::normalize_plugin_file( $addon['plugin_file'] );
		$addon['requires_core'] = trim( (string) $addon['requires_core'] );
		$addon['settings_url'] = trim( (string) $addon['settings_url'] );
		$addon['source'] = 'header' === ( $addon['source'] ?? '' ) ? 'header' : 'runtime';
		$addon['requires_plugins'] = self::normalize_plugin_files( $addon['requires_plugins'] ?? array() );
		self::$addons[ $id ] = $addon;
		return true;
	}

	public static function discover() {
		if ( self::$discovered ) { return; }
		self::$discovered = true;
		self::discover_installed_headers();
		do_action( self::REGISTER_ACTION );
	}

	public static function all() {
		self::discover();
		$addons = self::$addons;
		ksort( $addons );
		return $addons;
	}

	public static function get( $id ) {
		self::discover();
		$id = sanitize_key( $id );
		return isset( self::$addons[ $id ] ) ? self::$addons[ $id ] : array();
	}

	public static function status( $id ) {
		$addon = self::get( $id );
		if ( empty( $addon ) ) {
			return array( 'state' => 'unknown', 'installed' => false, 'active' => false, 'core_compatible' => false, 'dependencies_ready' => false, 'missing_dependencies' => array() );
		}
		$plugin_file = $addon['plugin_file'];
		$installed = '' === $plugin_file || ! defined( 'WP_PLUGIN_DIR' ) ? true : is_file( WP_PLUGIN_DIR . '/' . $plugin_file );
		$active = $installed && ( '' === $plugin_file || self::is_plugin_active( $plugin_file ) );
		$core_compatible = '' === $addon['requires_core'] || version_compare( YBY_CORE_VERSION, $addon['requires_core'], '>=' );
		$missing = array();
		foreach ( $addon['requires_plugins'] as $dependency ) {
			if ( ! self::is_plugin_active( $dependency ) ) { $missing[] = $dependency; }
		}
		$dependencies_ready = empty( $missing );

		if ( ! $installed ) { $state = 'not_installed'; }
		elseif ( ! $active ) { $state = 'inactive'; }
		elseif ( ! $core_compatible ) { $state = 'incompatible_core'; }
		elseif ( ! $dependencies_ready ) { $state = 'dependency_missing'; }
		else { $state = 'ready'; }

		return array(
			'state' => $state,
			'installed' => $installed,
			'active' => $active,
			'core_compatible' => $core_compatible,
			'dependencies_ready' => $dependencies_ready,
			'missing_dependencies' => $missing,
		);
	}

	public static function statuses() {
		$result = array();
		foreach ( self::all() as $id => $addon ) { $result[ $id ] = self::status( $id ); }
		return $result;
	}

	public static function summary() {
		$statuses = self::statuses();
		if ( empty( $statuses ) ) { return '0 addons registered'; }
		$ready = 0;
		foreach ( $statuses as $status ) { if ( 'ready' === $status['state'] ) { $ready++; } }
		return sprintf( '%d addons registered / %d ready', count( $statuses ), $ready );
	}

	protected static function discover_installed_headers() {
		if ( ! function_exists( 'get_plugins' ) ) {
			$plugin_api = defined( 'ABSPATH' ) ? ABSPATH . 'wp-admin/includes/plugin.php' : '';
			if ( $plugin_api && is_file( $plugin_api ) ) { require_once $plugin_api; }
		}
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'get_file_data' ) || ! defined( 'WP_PLUGIN_DIR' ) ) { return; }
		foreach ( get_plugins() as $plugin_file => $data ) {
			$path = WP_PLUGIN_DIR . '/' . $plugin_file;
			if ( ! is_file( $path ) ) { continue; }
			$headers = get_file_data(
				$path,
				array(
					'addon_id' => 'Andy Core Addon',
					'requires_core' => 'Requires Andy Core',
				),
				'plugin'
			);
			$id = sanitize_key( $headers['addon_id'] ?? '' );
			if ( '' === $id ) { continue; }
			self::register(
				$id,
				array(
					'name' => $data['Name'] ?? $id,
					'description' => $data['Description'] ?? '',
					'version' => $data['Version'] ?? '',
					'plugin_file' => $plugin_file,
					'requires_core' => $headers['requires_core'] ?? '',
					'source' => 'header',
				)
			);
		}
	}

	protected static function is_plugin_active( $plugin_file ) {
		$plugin_file = self::normalize_plugin_file( $plugin_file );
		if ( '' === $plugin_file ) { return true; }
		$active = get_option( 'active_plugins', array() );
		$active = is_array( $active ) ? array_map( array( __CLASS__, 'normalize_plugin_file' ), $active ) : array();
		if ( in_array( $plugin_file, $active, true ) ) { return true; }
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_site_option' ) ) {
			$network = get_site_option( 'active_sitewide_plugins', array() );
			return is_array( $network ) && array_key_exists( $plugin_file, $network );
		}
		return false;
	}

	protected static function normalize_plugin_files( $files ) {
		$result = array();
		foreach ( (array) $files as $file ) {
			$file = self::normalize_plugin_file( $file );
			if ( '' !== $file && ! in_array( $file, $result, true ) ) { $result[] = $file; }
		}
		return $result;
	}

	public static function normalize_plugin_file( $file ) {
		$file = trim( str_replace( '\\', '/', (string) $file ), '/' );
		return preg_replace( '#[^A-Za-z0-9._\-/]#', '', $file );
	}
}
