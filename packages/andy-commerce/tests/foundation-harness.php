<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['hooks'] = array();
$GLOBALS['notices'] = array();

function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://example.com/wp-content/plugins/andy-commerce/'; }
function plugin_basename( $file ) { return 'andy-commerce/andy-commerce.php'; }
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['hooks'][ $hook ][ $priority ][] = $callback; }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function current_user_can( $capability ) { return true; }
function esc_html__( $value ) { return $value; }
function esc_html( $value ) { return $value; }
function __ ( $value ) { return $value; }
function wp_die( $message ) { throw new Exception( $message ); }
function is_admin() { return true; }

define( 'YBY_CORE_VERSION', '1.9.0' );

class YBY_Addon_Registry {
	public static $registered = array();
	public static function register( $id, $metadata ) { self::$registered[ $id ] = $metadata; return true; }
}
class WooCommerce {}

require dirname( __DIR__ ) . '/andy-commerce.php';

$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$status = andy_commerce_dependency_status();
$assert( $status['ready'] === true, 'compatible Core + Woo must be ready' );
$assert( andy_commerce_register_with_core() === true, 'addon registration must succeed' );
$assert( isset( YBY_Addon_Registry::$registered['andy_commerce'] ), 'Andy Commerce registration missing' );
$assert( YBY_Addon_Registry::$registered['andy_commerce']['min_core_version'] === '1.9.0', 'minimum Core contract mismatch' );
$assert( YBY_Addon_Registry::$registered['andy_commerce']['requires'] === array( 'woocommerce' ), 'Woo requirement mismatch' );

$source = file_get_contents( dirname( __DIR__ ) . '/andy-commerce.php' );
$assert( false !== strpos( $source, 'Requires Plugins:  yby-core,woocommerce' ), 'WordPress dependency metadata missing' );
$assert( false !== strpos( $source, "add_action( 'plugins_loaded', 'andy_commerce_bootstrap', 30 )" ), 'safe deferred bootstrap missing' );

echo "PASS andy-commerce-foundation-harness\n";
