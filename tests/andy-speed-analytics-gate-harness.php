<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options'] = array();
$GLOBALS['yby_test_filters'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][$key] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = null ) { if ( array_key_exists( $key, $GLOBALS['yby_test_options'] ) ) { return false; } $GLOBALS['yby_test_options'][$key] = $value; return true; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_options'][$key] = $value; return true; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function is_admin() { return ! empty( $GLOBALS['yby_is_admin'] ); }
function apply_filters( $name, $value ) { return array_key_exists( $name, $GLOBALS['yby_test_filters'] ) ? $GLOBALS['yby_test_filters'][$name] : $value; }
class YBY_Helpers { public static function admin_page_slug() { return 'yby-core'; } }
require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-settings-store.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-module.php';
YBY_Analytics_Module::register_module();
$assert = static function ( $ok, $what ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$what}\\n" ); exit( 1 ); } };
$module = array();
$assert( YBY_Analytics_Module::should_enqueue_diagnostics( $module ) === true, 'legacy frontend behavior preserved' );
$GLOBALS['yby_test_filters']['andy_core_analytics_debug_only'] = true;
$assert( YBY_Analytics_Module::should_enqueue_diagnostics( $module ) === false, 'opt-in + debug OFF skips inspector' );
YBY_Analytics_Module::save_settings( array( 'debug' => true ) );
$assert( YBY_Analytics_Module::should_enqueue_diagnostics( $module ) === true, 'opt-in + debug ON keeps inspector' );
$GLOBALS['yby_test_filters']['andy_core_analytics_debug_only'] = false;
YBY_Analytics_Module::save_settings( array( 'debug' => false ) );
$assert( YBY_Analytics_Module::should_enqueue_diagnostics( $module ) === true, 'kill switch restoring default' );
$GLOBALS['yby_is_admin'] = true;
$assert( YBY_Analytics_Module::should_enqueue_diagnostics( $module ) === false, 'admin never enqueues frontend inspector' );
echo "PASS Andy Speed Core analytics diagnostics debug-only opt-in gate\\n";
