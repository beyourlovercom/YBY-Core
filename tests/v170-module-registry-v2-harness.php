<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_test_options'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }

class YBY_Future_Module_Probe {
	public static function boot() {}
	public static function menu() {}
	public static function settings() {}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$assert( '2' === YBY_Module_Registry::VERSION, 'registry contract must be v2' );
$assert( 'yby_core_enabled_modules_v1' === YBY_Module_Registry::OPTION_KEY, 'v2 must preserve existing option key' );
$assert( false === YBY_Module_Registry::register( 'docs_os', array( 'name' => 'Override' ) ), 'core module override must be refused' );

$registered = YBY_Module_Registry::register(
	'future_module_probe',
	array(
		'name' => 'Andy Future Probe',
		'description' => 'Dummy adapter proving future module registration.',
		'version' => '0.1.0',
		'schema_version' => '1',
		'default_enabled' => false,
		'status' => 'ready',
		'capability' => 'manage_options',
		'admin_parent' => 'yby-content',
		'bootstrap_class' => 'YBY_Future_Module_Probe',
		'boot' => array( 'YBY_Future_Module_Probe', 'boot' ),
		'admin_menu' => array( 'YBY_Future_Module_Probe', 'menu' ),
		'settings' => array( 'page' => 'future-module-probe', 'tab' => 'general' ),
		'settings_register' => array( 'YBY_Future_Module_Probe', 'settings' ),
		'assets' => array(
			'admin' => array( 'future-probe-admin' ),
			'frontend' => array( 'future-probe-frontend' ),
		),
		'dependencies' => array( 'core_runtime' ),
	)
);

$assert( true === $registered, 'future adapter registration failed' );
$assert( false === YBY_Module_Registry::register( 'future_module_probe', array( 'name' => 'Duplicate' ) ), 'duplicate module registration must be refused' );

$module = YBY_Module_Registry::module( 'future_module_probe' );
foreach ( array( 'id', 'name', 'label', 'description', 'version', 'schema_version', 'default_enabled', 'default', 'status', 'capability', 'admin_parent', 'bootstrap_class', 'boot', 'admin_menu', 'settings', 'settings_register', 'assets', 'dependencies' ) as $key ) {
	$assert( array_key_exists( $key, $module ), 'normalized metadata key missing: ' . $key );
}

$assert( 'future_module_probe' === $module['id'], 'normalized module id mismatch' );
$assert( 'Andy Future Probe' === $module['name'] && 'Andy Future Probe' === $module['label'], 'name/legacy label bridge mismatch' );
$assert( false === $module['default_enabled'] && false === $module['default'], 'default bridge mismatch' );
$assert( 'yby-content' === $module['admin_parent'], 'content parent metadata mismatch' );
$assert( is_callable( $module['boot'] ), 'boot callback seam missing' );
$assert( is_callable( $module['admin_menu'] ), 'admin menu callback seam missing' );
$assert( is_callable( $module['settings_register'] ), 'settings callback seam missing' );
$assert( array( 'core_runtime' ) === $module['dependencies'], 'dependency metadata mismatch' );
$assert( array( 'future-probe-admin' ) === $module['assets']['admin'], 'admin asset declaration missing' );
$assert( array( 'future-probe-frontend' ) === $module['assets']['frontend'], 'frontend asset declaration missing' );
$assert( false !== strpos( YBY_Module_Registry::settings_url( 'future_module_probe' ), 'page=future-module-probe' ), 'future settings route missing' );

$defaults = YBY_Module_Registry::defaults();
$assert( ! in_array( 'future_module_probe', $defaults, true ), 'future module must respect default OFF' );

YBY_Module_Registry::save( array( 'email_os', 'future_module_probe' ) );
$assert( YBY_Module_Registry::enabled_modules() === array( 'email_os', 'future_module_probe' ), 'registered module must participate in existing option storage without migration' );
$assert( YBY_Module_Registry::is_enabled( 'future_module_probe' ), 'registered module toggle lookup failed' );

echo "PASS v170-module-registry-v2-harness\n";
