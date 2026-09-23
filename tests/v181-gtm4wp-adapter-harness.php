<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = null ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
class YBY_Helpers { public static function admin_page_slug() { return 'yby-core'; } }
require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-settings-store.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-module.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-gtm4wp-adapter.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
YBY_Analytics_Module::register_module();
$cfg = YBY_Analytics_GTM4WP_Adapter::runtime_config();
$assert( false === $cfg['enabled'], 'adapter must default disabled with module' );
$assert( 'gtm4wp' === $cfg['provider'], 'provider identity' );
$assert( false === $cfg['provider_ready'], 'provider must be absent before GTM4WP constant' );
$assert( '' === $cfg['provider_version'], 'provider version absent' );
$assert( 'dataLayer' === $cfg['data_layer_name'], 'default layer' );
$assert( false === $cfg['injects_container'], 'Andy Core must not inject GTM container' );
$assert( false === $cfg['owns_woocommerce_ecommerce'], 'Andy Core must not own Woo ecommerce provider events' );
define( 'GTM4WP_VERSION', '2.0.2' );
$cfg = YBY_Analytics_GTM4WP_Adapter::runtime_config();
$assert( true === $cfg['provider_ready'], 'provider constant detection' );
$assert( '2.0.2' === $cfg['provider_version'], 'provider version detection' );
YBY_Module_Registry::save( array( 'analytics' ) );
YBY_Analytics_Module::save_settings( array( 'data_layer_name' => 'customLayer' ) );
$cfg = YBY_Analytics_GTM4WP_Adapter::runtime_config();
$assert( true === $cfg['enabled'], 'enabled adapter' );
$assert( 'customLayer' === $cfg['data_layer_name'], 'configured data layer' );
$js = file_get_contents( dirname( __DIR__ ) . '/public/assets/js/yby-core-public.js' );
$assert( false !== strpos( $js, 'analytics.data_layer_name' ), 'frontend provider layer projection missing' );
$assert( false !== strpos( $js, 'window[layerName].push(payload)' ), 'single provider push seam missing' );
echo "PASS v181-gtm4wp-adapter-harness\n";
