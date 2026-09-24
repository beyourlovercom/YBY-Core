<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
if ( ! defined( 'WP_PLUGIN_DIR' ) ) { define( 'WP_PLUGIN_DIR', dirname( __DIR__ ) ); }
if ( ! defined( 'YBY_CORE_VERSION' ) ) { define( 'YBY_CORE_VERSION', '1.9.0' ); }
$GLOBALS['yby_addon_test_options'] = array( 'active_plugins' => array( 'yby-core.php' ) );

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_addon_test_options'] ) ? $GLOBALS['yby_addon_test_options'][ $key ] : $default; }
function do_action( $hook ) {}
function is_multisite() { return false; }

require_once dirname( __DIR__ ) . '/inc/class-yby-addon-registry.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$assert( YBY_Addon_Registry::VERSION === '1', 'registry version' );
$assert( YBY_Addon_Registry::REGISTER_ACTION === 'andy_core_register_addons', 'registration action contract' );
$assert( YBY_Addon_Registry::register( 'demo-ready', array( 'name' => 'Demo Ready', 'version' => '1.0.0', 'plugin_file' => 'yby-core.php', 'requires_core' => '1.9.0' ) ), 'ready addon register' );
$assert( 'ready' === YBY_Addon_Registry::status( 'demo-ready' )['state'], 'ready addon state' );

YBY_Addon_Registry::register( 'demo-inactive', array( 'name' => 'Demo Inactive', 'plugin_file' => 'readme.txt', 'requires_core' => '1.9.0' ) );
$assert( 'inactive' === YBY_Addon_Registry::status( 'demo-inactive' )['state'], 'installed inactive state' );

YBY_Addon_Registry::register( 'demo-missing', array( 'name' => 'Demo Missing', 'plugin_file' => 'missing/missing.php', 'requires_core' => '1.9.0' ) );
$assert( 'not_installed' === YBY_Addon_Registry::status( 'demo-missing' )['state'], 'not installed state' );

YBY_Addon_Registry::register( 'demo-newer-core', array( 'name' => 'Demo New Core', 'plugin_file' => 'yby-core.php', 'requires_core' => '9.0.0' ) );
$assert( 'incompatible_core' === YBY_Addon_Registry::status( 'demo-newer-core' )['state'], 'core compatibility state' );

YBY_Addon_Registry::register( 'demo-dependency', array( 'name' => 'Demo Dependency', 'plugin_file' => 'yby-core.php', 'requires_plugins' => array( 'missing/missing.php' ) ) );
$status = YBY_Addon_Registry::status( 'demo-dependency' );
$assert( 'dependency_missing' === $status['state'], 'dependency failure state' );
$assert( $status['missing_dependencies'] === array( 'missing/missing.php' ), 'dependency failure detail' );

$assert( 'folder/plugin.php' === YBY_Addon_Registry::normalize_plugin_file( '\\folder\\plugin.php' ), 'plugin file normalization' );
$assert( false === YBY_Addon_Registry::register( '!!!', array() ), 'invalid id rejection' );
$assert( false !== strpos( YBY_Addon_Registry::summary(), 'addons registered' ), 'summary contract' );

echo "PASS v190-addon-registry-harness\n";
