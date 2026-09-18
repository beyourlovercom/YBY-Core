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
$assert( isset( $modules['docs_os'] ), 'Docs OS missing from registry' );
$assert( 'ready' === $modules['docs_os']['status'], 'Docs OS must be ready' );
$assert( false === $modules['docs_os']['default'], 'Docs OS must default OFF' );
$assert( ! YBY_Module_Registry::is_enabled( 'docs_os' ), 'Docs OS must initially be OFF' );
YBY_Module_Registry::save( array( 'docs_os' ) );
$assert( YBY_Module_Registry::is_enabled( 'docs_os' ), 'Docs OS must be toggleable ON' );
YBY_Module_Registry::save( array() );
$assert( ! YBY_Module_Registry::is_enabled( 'docs_os' ), 'Docs OS must be toggleable OFF again' );
$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$shell = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-docs-os-admin.php' );
$assert( false !== strpos( $core, "YBY_Module_Registry::is_enabled( 'docs_os' )" ), 'Docs OS Boot Gate missing' );
$assert( false !== strpos( $shell, 'Existing docs, taxonomy, metadata and media are retained' ), 'Data retention contract missing from shell' );
$assert( false === strpos( $shell, 'wp_delete_post' ) && false === strpos( $shell, 'delete_term' ) && false === strpos( $shell, 'delete_option' ), 'Shell must not contain destructive data operations' );
echo "PASS docs-os-module-shell-harness\n";
