<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function admin_url( $path = '' ) { return 'https://example.com/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$modules = YBY_Module_Registry::modules();
$assert( YBY_Module_Registry::VERSION === '2', 'registry version' );
$assert( YBY_Module_Registry::OPTION_KEY === 'yby_core_enabled_modules_v1', 'registry v2 must preserve existing option storage key' );
$assert( count( YBY_Module_Registry::foundation() ) >= 6, 'foundation must be explicit and locked' );
foreach ( array( 'inquiry_os', 'email_os', 'project_studio', 'social_login', 'connector', 'docs_os' ) as $id ) { $assert( isset( $modules[ $id ] ), 'missing module: ' . $id ); }
$defaults = YBY_Module_Registry::enabled_modules();
foreach ( array( 'inquiry_os', 'email_os', 'project_studio', 'social_login', 'connector' ) as $id ) { $assert( in_array( $id, $defaults, true ), 'existing site module must default ON: ' . $id ); }
$assert( ! in_array( 'docs_os', $defaults, true ), 'Docs OS must default OFF for existing sites' );
$saved = YBY_Module_Registry::sanitize_enabled_modules( array( 'email_os', 'connector', 'docs_os', 'evil_module', 'email_os' ) );
$assert( $saved === array( 'email_os', 'connector', 'docs_os' ), 'save sanitizer must accept ready Docs OS while rejecting unknown and duplicate modules' );
YBY_Module_Registry::save( array( 'email_os' ) );
$assert( YBY_Module_Registry::enabled_modules() === array( 'email_os' ), 'explicit site selection must override defaults' );
$assert( YBY_Module_Registry::is_enabled( 'email_os' ), 'enabled module lookup' );
$assert( ! YBY_Module_Registry::is_enabled( 'inquiry_os' ), 'disabled module lookup' );
$assert( false !== strpos( YBY_Module_Registry::settings_url( 'connector' ), 'tab=wp-api' ), 'connector settings URL' );
$assert( false !== strpos( YBY_Module_Registry::settings_url( 'docs_os' ), 'page=yby-docs-os' ), 'ready Docs OS must expose settings URL' );
$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$admin = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-admin.php' );
$view = file_get_contents( dirname( __DIR__ ) . '/admin/views/modules-page.php' );
$assert( false !== strpos( $core, 'class-yby-module-registry.php' ), 'registry bootstrap missing' );
$assert( false !== strpos( $admin, "'modules' === \$tab" ), 'Modules settings tab routing missing' );
$assert( false !== strpos( $view, 'yby-modules-table' ) && false !== strpos( $view, 'Foundation' ) && false !== strpos( $view, 'Feature' ), 'compact module table contract missing' );
$assert( false !== strpos( $view, '功能说明' ) && false !== strpos( $view, '运行状态' ) && false !== strpos( $view, '启用' ), 'module table columns missing' );
$assert( false !== strpos( $view, 'disabled( $is_planned )' ), 'planned modules must remain non-toggleable' );
echo "PASS module-registry-harness\n";