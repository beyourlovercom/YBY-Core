<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_test_options'] = array(
	'yby_core_enabled_modules_v1' => array( 'future_module_probe', 'blocked_probe', 'unsafe_asset_probe' ),
);
$GLOBALS['yby_test_hooks'] = array();
$GLOBALS['yby_test_updates'] = 0;
$GLOBALS['yby_frontend_asset_allowed'] = false;

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_updates']++; $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['yby_test_hooks'][ $hook ][ (int) $priority ][] = array( $callback, (int) $accepted_args );
}
function do_action( $hook, ...$args ) {
	if ( empty( $GLOBALS['yby_test_hooks'][ $hook ] ) ) { return; }
	ksort( $GLOBALS['yby_test_hooks'][ $hook ] );
	foreach ( $GLOBALS['yby_test_hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
		}
	}
}

class YBY_Future_Runtime_Probe {
	public static $boot = 0;
	public static $menu = 0;
	public static $settings = 0;
	public static $admin_assets = 0;
	public static $frontend_assets = 0;
	public static function boot( $module ) { self::$boot++; }
	public static function menu( $module ) { self::$menu++; }
	public static function settings( $module ) { self::$settings++; }
	public static function admin_asset_condition( $hook_suffix, $module ) { return 'toplevel_page_future-module-probe' === $hook_suffix; }
	public static function enqueue_admin_assets( $hook_suffix, $module ) { self::$admin_assets++; }
	public static function frontend_asset_condition( $module ) { return ! empty( $GLOBALS['yby_frontend_asset_allowed'] ); }
	public static function enqueue_frontend_assets( $module ) { self::$frontend_assets++; }
}

class YBY_Blocked_Runtime_Probe {
	public static $boot = 0;
	public static function boot( $module ) { self::$boot++; }
}

class YBY_Disabled_Runtime_Probe {
	public static $boot = 0;
	public static function boot( $module ) { self::$boot++; }
}

require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-runtime.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

add_action(
	'andy_core_register_modules',
	static function () {
		YBY_Module_Registry::register(
			'future_module_probe',
			array(
				'name' => 'Andy Future Probe',
				'default_enabled' => false,
				'boot' => array( 'YBY_Future_Runtime_Probe', 'boot' ),
				'admin_menu' => array( 'YBY_Future_Runtime_Probe', 'menu' ),
				'admin_menu_priority' => 37,
				'settings_register' => array( 'YBY_Future_Runtime_Probe', 'settings' ),
				'settings_priority' => 17,
				'assets' => array(
					'admin' => array(
						'enqueue' => array( 'YBY_Future_Runtime_Probe', 'enqueue_admin_assets' ),
						'condition' => array( 'YBY_Future_Runtime_Probe', 'admin_asset_condition' ),
						'priority' => 23,
					),
					'frontend' => array(
						'enqueue' => array( 'YBY_Future_Runtime_Probe', 'enqueue_frontend_assets' ),
						'condition' => array( 'YBY_Future_Runtime_Probe', 'frontend_asset_condition' ),
						'priority' => 24,
					),
				),
				'dependencies' => array( 'core_runtime' ),
			)
		);
		YBY_Module_Registry::register(
			'blocked_probe',
			array(
				'name' => 'Blocked Probe',
				'default_enabled' => false,
				'boot' => array( 'YBY_Blocked_Runtime_Probe', 'boot' ),
				'assets' => array(
					'admin' => array(
						'enqueue' => array( 'YBY_Future_Runtime_Probe', 'enqueue_admin_assets' ),
						'condition' => array( 'YBY_Future_Runtime_Probe', 'admin_asset_condition' ),
						'priority' => 23,
					),
				),
				'dependencies' => array( 'missing_module' ),
			)
		);
		YBY_Module_Registry::register(
			'disabled_probe',
			array(
				'name' => 'Disabled Probe',
				'default_enabled' => false,
				'boot' => array( 'YBY_Disabled_Runtime_Probe', 'boot' ),
				'assets' => array(
					'admin' => array(
						'enqueue' => array( 'YBY_Future_Runtime_Probe', 'enqueue_admin_assets' ),
						'condition' => array( 'YBY_Future_Runtime_Probe', 'admin_asset_condition' ),
						'priority' => 23,
					),
				),
			)
		);
		YBY_Module_Registry::register(
			'unsafe_asset_probe',
			array(
				'name' => 'Unsafe Asset Probe',
				'default_enabled' => false,
				'assets' => array(
					'admin' => array(
						'enqueue' => array( 'YBY_Future_Runtime_Probe', 'enqueue_admin_assets' ),
						'priority' => 23,
					),
				),
				'dependencies' => array( 'core_runtime' ),
			)
		);
	},
	10,
	0
);

$runtime = new YBY_Module_Runtime();
$runtime->discover_and_boot();

$assert( 1 === YBY_Future_Runtime_Probe::$boot, 'enabled extension must boot exactly once' );
$assert( 0 === YBY_Blocked_Runtime_Probe::$boot, 'missing dependency must fail closed' );
$assert( 0 === YBY_Disabled_Runtime_Probe::$boot, 'disabled extension must not boot' );
$assert( YBY_Module_Registry::dependencies_met( 'future_module_probe' ), 'foundation dependency should resolve' );
$assert( ! YBY_Module_Registry::dependencies_met( 'blocked_probe' ), 'unknown dependency should block module' );
$assert( isset( $GLOBALS['yby_test_hooks']['admin_menu'][37] ), 'admin menu priority seam missing' );
$assert( isset( $GLOBALS['yby_test_hooks']['admin_init'][17] ), 'settings priority seam missing' );
$assert( isset( $GLOBALS['yby_test_hooks']['admin_enqueue_scripts'][23] ), 'conditional admin asset hook missing' );
$assert( isset( $GLOBALS['yby_test_hooks']['wp_enqueue_scripts'][24] ), 'conditional frontend asset hook missing' );

do_action( 'admin_menu' );
do_action( 'admin_init' );
$assert( 1 === YBY_Future_Runtime_Probe::$menu, 'enabled extension menu callback must run' );
$assert( 1 === YBY_Future_Runtime_Probe::$settings, 'enabled extension settings callback must run' );

do_action( 'admin_enqueue_scripts', 'plugins.php' );
$assert( 0 === YBY_Future_Runtime_Probe::$admin_assets, 'admin assets must not load when condition is false' );
do_action( 'admin_enqueue_scripts', 'toplevel_page_future-module-probe' );
$assert( 1 === YBY_Future_Runtime_Probe::$admin_assets, 'admin assets must load exactly when condition matches' );

do_action( 'wp_enqueue_scripts' );
$assert( 0 === YBY_Future_Runtime_Probe::$frontend_assets, 'frontend assets must not load when condition is false' );
$GLOBALS['yby_frontend_asset_allowed'] = true;
do_action( 'wp_enqueue_scripts' );
$assert( 1 === YBY_Future_Runtime_Probe::$frontend_assets, 'frontend assets must load when condition becomes true' );

$runtime->discover_and_boot();
do_action( 'admin_menu' );
do_action( 'admin_init' );
$assert( 1 === YBY_Future_Runtime_Probe::$boot, 'repeated discovery must not double boot extension' );
$assert( 2 === YBY_Future_Runtime_Probe::$menu, 'existing WordPress admin_menu hook should run once per action dispatch' );
$assert( 2 === YBY_Future_Runtime_Probe::$settings, 'existing WordPress admin_init hook should run once per action dispatch' );
$assert( 0 === $GLOBALS['yby_test_updates'], 'module runtime must not write settings or business data during boot' );
$assert( 1 === count( $GLOBALS['yby_test_hooks']['admin_enqueue_scripts'][23] ), 'repeated discovery must not duplicate admin asset hooks' );
$assert( 1 === count( $GLOBALS['yby_test_hooks']['wp_enqueue_scripts'][24] ), 'repeated discovery must not duplicate frontend asset hooks' );

echo "PASS v170-module-runtime-integration-harness\n";
