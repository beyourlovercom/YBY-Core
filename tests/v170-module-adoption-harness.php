<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_test_options'] = array(
	'yby_core_enabled_modules_v1' => array( 'inquiry_os', 'email_os', 'project_studio', 'docs_os' ),
);
$GLOBALS['yby_test_writes'] = array();

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['yby_test_options'][ $key ] = $value;
	$GLOBALS['yby_test_writes'][] = array( $key, $value, $autoload );
	return true;
}
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }

require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$before = $GLOBALS['yby_test_options']['yby_core_enabled_modules_v1'];
$assert( ! in_array( 'landing_pages', $before, true ), 'pre-v1.7 fixture must not already contain Landing Pages' );
$assert( ! in_array( 'social_login', $before, true ) && ! in_array( 'connector', $before, true ), 'fixture must preserve explicitly disabled legacy modules' );

$changed = YBY_Module_Registry::adopt_default_modules_once( 'v170_landing_pages', array( 'landing_pages' ) );
$assert( true === $changed, 'first adoption must run' );

$after = $GLOBALS['yby_test_options']['yby_core_enabled_modules_v1'];
$assert( array( 'inquiry_os', 'email_os', 'project_studio', 'docs_os', 'landing_pages' ) === $after, 'adoption must append Landing Pages without changing existing module choices' );
$assert( ! in_array( 'social_login', $after, true ) && ! in_array( 'connector', $after, true ), 'adoption must not re-enable previously disabled modules' );
$assert( array( 'v170_landing_pages' ) === $GLOBALS['yby_test_options'][ YBY_Module_Registry::ADOPTION_OPTION ], 'adoption marker missing' );

$write_count = count( $GLOBALS['yby_test_writes'] );
$changed_again = YBY_Module_Registry::adopt_default_modules_once( 'v170_landing_pages', array( 'landing_pages' ) );
$assert( false === $changed_again, 'adoption must be idempotent after marker' );
$assert( $write_count === count( $GLOBALS['yby_test_writes'] ), 'idempotent adoption must perform zero writes' );

YBY_Module_Registry::save( array( 'inquiry_os', 'email_os', 'project_studio', 'docs_os' ) );
$assert( ! YBY_Module_Registry::is_enabled( 'landing_pages' ), 'owner must be able to disable Landing Pages after adoption' );
$writes_after_disable = count( $GLOBALS['yby_test_writes'] );
YBY_Module_Registry::adopt_default_modules_once( 'v170_landing_pages', array( 'landing_pages' ) );
$assert( ! YBY_Module_Registry::is_enabled( 'landing_pages' ), 'completed adoption must never force-reenable Landing Pages' );
$assert( $writes_after_disable === count( $GLOBALS['yby_test_writes'] ), 'completed adoption must remain write-free after owner disable' );

$GLOBALS['yby_test_options'] = array();
$GLOBALS['yby_test_writes'] = array();
$assert( true === YBY_Module_Registry::adopt_default_modules_once( 'v170_landing_pages', array( 'landing_pages' ) ), 'fresh-install adoption marker should be written' );
$assert( ! array_key_exists( YBY_Module_Registry::OPTION_KEY, $GLOBALS['yby_test_options'] ), 'fresh installs should continue to use registry defaults instead of materializing an enabled-module list' );
$assert( in_array( 'landing_pages', YBY_Module_Registry::enabled_modules(), true ), 'fresh install defaults must include Landing Pages' );

echo "PASS v170-module-adoption-harness\n";
