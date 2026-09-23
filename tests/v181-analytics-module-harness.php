<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = null ) { if ( array_key_exists( $key, $GLOBALS['yby_test_options'] ) ) { return false; } $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
class YBY_Helpers { public static function admin_page_slug() { return 'yby-core'; } }
require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-settings-store.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-module.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$assert( YBY_Analytics_Module::register_module(), 'analytics module registration' );
$module = YBY_Module_Registry::module( 'analytics' );
$assert( 'Analytics / GTM' === $module['name'], 'module name' );
$assert( false === $module['default_enabled'], 'analytics must default OFF' );
$assert( 'yby_analytics_settings_v1' === $module['storage']['option_key'], 'versioned option key' );
$assert( '1' === $module['storage']['schema_version'], 'schema version' );
$assert( in_array( 'andy-analytics-diagnostics', $module['assets']['frontend']['handles'], true ), 'diagnostic asset handle' );
$assert( ! in_array( 'analytics', YBY_Module_Registry::enabled_modules(), true ), 'analytics must not silently enable' );
$defaults = YBY_Analytics_Module::get_settings();
$assert( 'gtm4wp' === $defaults['provider'], 'default provider' );
$assert( 'dataLayer' === $defaults['data_layer_name'], 'default dataLayer' );
$assert( 'auto' === $defaults['site_profile'], 'default site profile' );
$assert( 'respect_existing' === $defaults['consent_mode'], 'default consent mode' );
$assert( false === $defaults['debug'], 'default debug' );
$clean = YBY_Analytics_Module::sanitize_settings( array( 'provider' => 'evil', 'data_layer_name' => 'bad-name!', 'site_profile' => 'manual', 'consent_mode' => 'override', 'debug' => 1 ) );
$assert( 'gtm4wp' === $clean['provider'], 'provider fail closed' );
$assert( 'dataLayer' === $clean['data_layer_name'], 'data layer fail closed' );
$assert( 'auto' === $clean['site_profile'], 'site profile fail closed' );
$assert( 'respect_existing' === $clean['consent_mode'], 'consent mode fail closed' );
$assert( true === $clean['debug'], 'debug normalization' );
$assert( YBY_Analytics_Module::save_settings( array( 'data_layer_name' => 'customLayer', 'debug' => true ) ), 'save settings' );
$saved = YBY_Analytics_Module::get_settings();
$assert( 'customLayer' === $saved['data_layer_name'], 'saved data layer' );
$assert( true === $saved['debug'], 'saved debug' );
$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$assert( false !== strpos( $core, 'class-yby-analytics-module.php' ), 'analytics bootstrap missing' );
$assert( false !== strpos( $core, "'YBY_Analytics_Module', 'register_module'" ), 'analytics registration hook missing' );
echo "PASS v181-analytics-module-harness\n";
