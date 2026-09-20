<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_options'] = array(
	'yby_docs_os_settings_v1' => array(
		'site_title' => 'Existing Help Center',
		'show_search' => 0,
	),
);
$GLOBALS['yby_writes'] = array();

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_options'] ) ? $GLOBALS['yby_options'][ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = true ) {
	if ( array_key_exists( $key, $GLOBALS['yby_options'] ) ) { return false; }
	$GLOBALS['yby_options'][ $key ] = $value;
	$GLOBALS['yby_writes'][] = array( 'add', $key, $value, $autoload );
	return true;
}
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['yby_options'][ $key ] = $value;
	$GLOBALS['yby_writes'][] = array( 'update', $key, $value, $autoload );
	return true;
}
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }

require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-settings-store.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$assert( 'yby_docs_os_settings_v1' === YBY_Module_Settings_Store::option_key( 'docs_os' ), 'Docs existing option key must remain unchanged' );
$assert( '1' === YBY_Module_Settings_Store::schema_version( 'docs_os' ), 'Docs storage schema version mismatch' );

$writes_before = count( $GLOBALS['yby_writes'] );
$docs = YBY_Module_Settings_Store::get(
	'docs_os',
	array(
		'site_title' => 'Help Center',
		'show_search' => 1,
		'show_toc' => 1,
	)
);
$assert( 'Existing Help Center' === $docs['site_title'], 'Store must read existing Docs settings without migration' );
$assert( 0 === $docs['show_search'], 'Store must preserve existing Docs value over default' );
$assert( 1 === $docs['show_toc'], 'Store must merge missing Docs defaults' );
$assert( $writes_before === count( $GLOBALS['yby_writes'] ), 'Store get must be read-only' );

YBY_Module_Registry::register(
	'future_storage_probe',
	array(
		'name' => 'Future Storage Probe',
		'default_enabled' => false,
		'status' => 'ready',
		'schema_version' => '2',
		'storage' => array( 'schema_version' => '2' ),
	)
);

$assert( 'yby_future_storage_probe_settings_v2' === YBY_Module_Settings_Store::option_key( 'future_storage_probe' ), 'Versioned option key generation mismatch' );
$assert( '2' === YBY_Module_Settings_Store::schema_version( 'future_storage_probe' ), 'Future storage schema version mismatch' );
$assert( ! YBY_Module_Settings_Store::exists( 'future_storage_probe' ), 'Future probe should not exist before save' );

$sanitize = static function ( $raw ) {
	$raw = is_array( $raw ) ? $raw : array();
	return array(
		'enabled' => ! empty( $raw['enabled'] ) ? 1 : 0,
		'label' => isset( $raw['label'] ) ? trim( (string) $raw['label'] ) : '',
	);
};

$assert( false === YBY_Module_Settings_Store::save( 'future_storage_probe', array( 'enabled' => 1 ), null ), 'Store must refuse unsanitized writes' );
$assert( YBY_Module_Settings_Store::save( 'future_storage_probe', array( 'enabled' => true, 'label' => '  Probe  ' ), $sanitize ), 'First versioned save failed' );
$assert( YBY_Module_Settings_Store::exists( 'future_storage_probe' ), 'Saved future settings should exist' );

$first_write = end( $GLOBALS['yby_writes'] );
$assert( 'add' === $first_write[0], 'First settings write must use add_option' );
$assert( false === $first_write[3], 'New module settings must use autoload=false' );
$assert( array( 'enabled' => 1, 'label' => 'Probe' ) === $GLOBALS['yby_options']['yby_future_storage_probe_settings_v2'], 'Sanitized stored payload mismatch' );

$assert( YBY_Module_Settings_Store::save( 'future_storage_probe', array( 'enabled' => false, 'label' => 'Updated' ), $sanitize ), 'Versioned update failed' );
$last_write = end( $GLOBALS['yby_writes'] );
$assert( 'update' === $last_write[0], 'Existing settings write must use update_option' );
$assert( false === $last_write[3], 'Updated module settings must keep autoload=false' );
$assert( array( 'enabled' => 0, 'label' => 'Updated' ) === $GLOBALS['yby_options']['yby_future_storage_probe_settings_v2'], 'Updated sanitized payload mismatch' );

$assert( ! method_exists( 'YBY_Module_Settings_Store', 'delete' ), 'Foundation store must not expose destructive delete API' );

echo "PASS v170-module-settings-store-harness\n";
