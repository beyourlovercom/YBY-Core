<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
if ( ! defined( 'WP_PLUGIN_DIR' ) ) { define( 'WP_PLUGIN_DIR', '/plugins' ); }
define( 'YBY_CORE_VERSION', '1.9.0' );
$GLOBALS['hooks'] = array();
$GLOBALS['active_plugins'] = array();
$GLOBALS['installed_plugins'] = array(
	'andy-commerce/andy-commerce.php' => array(
		'Name' => 'Andy Commerce',
		'Version' => '1.0.0',
		'Description' => 'Commerce addon',
		'RequiresPlugins' => 'yby-core,woocommerce',
	),
);
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function esc_url_raw( $value ) { return (string) $value; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function add_action( $hook, $callback ) { $GLOBALS['hooks'][ $hook ][] = $callback; }
function do_action( $hook ) { foreach ( $GLOBALS['hooks'][ $hook ] ?? array() as $callback ) { call_user_func( $callback ); } }
function apply_filters( $hook, $value, ...$args ) { return $value; }
function get_plugins() { return $GLOBALS['installed_plugins']; }
function get_file_data( $file, $headers, $context = '' ) {
	return array( 'addon_id' => 'andy_commerce', 'min_core_version' => '1.9.0' );
}
function is_plugin_active( $plugin_file ) { return in_array( $plugin_file, $GLOBALS['active_plugins'], true ); }

class WooCommerce {}

require_once dirname( __DIR__ ) . '/inc/class-yby-addon-registry.php';

$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$catalog = YBY_Addon_Registry::catalog();
$assert( isset( $catalog['andy_commerce'] ), 'installed addon discovery must find Andy Commerce' );
$assert( YBY_Addon_Registry::status( 'andy_commerce' )['state'] === 'inactive', 'installed inactive addon must report inactive' );

add_action( 'andy_core_register_addons', static function () {
	YBY_Addon_Registry::register(
		'andy_commerce',
		array(
			'name' => 'Andy Commerce',
			'version' => '1.0.0',
			'plugin_file' => 'andy-commerce/andy-commerce.php',
			'min_core_version' => '1.9.0',
			'requires' => array( 'woocommerce' ),
			'admin_url' => 'admin.php?page=andy-commerce',
		)
	);
} );
YBY_Addon_Registry::reset_for_tests();
YBY_Addon_Registry::discover();

$addons = YBY_Addon_Registry::addons();
$assert( isset( $addons['andy_commerce'] ), 'active addon discovery must register Andy Commerce' );
$assert( YBY_Addon_Registry::status( 'andy_commerce' )['state'] === 'ready', 'compatible Core + Woo must be ready' );
$assert( YBY_Addon_Registry::core_compatible( array( 'min_core_version' => '1.9.1' ) ) === false, 'higher Core requirement must block compatibility' );
$assert( YBY_Addon_Registry::status( 'unknown' )['state'] === 'not_installed', 'unknown addon must fail closed' );

$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$admin = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-admin.php' );
$view = file_get_contents( dirname( __DIR__ ) . '/admin/views/addons-page.php' );
$assert( false !== strpos( $core, 'class-yby-addon-registry.php' ), 'Core bootstrap must load addon registry' );
$assert( false !== strpos( $core, "YBY_Addon_Registry', 'discover" ), 'Core must discover addons on plugins_loaded' );
$assert( false !== strpos( $admin, "'addons' === \$tab" ), 'Addons settings tab routing missing' );
$assert( false !== strpos( $view, 'Core-only / B2B' ), 'Core-only empty state must be explicit' );
$assert( false !== strpos( $view, 'Inactive' ), 'inactive installed state must be visible' );

echo "PASS addon-registry-harness\n";
