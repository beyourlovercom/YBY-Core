<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$runtime = file_get_contents( $root . '/inc/class-yby-docs-runtime.php' );

$assert( false !== strpos( $runtime, "const CANONICAL_OPTION = 'yby_docs_os_canonical_v1'" ), 'canonical option missing' );
$assert( false !== strpos( $runtime, 'YBY_DOCS_OS_CANONICAL_PRODUCTION_ENABLED' ), 'production fail-close gate missing' );
$assert( false !== strpos( $runtime, 'maybe_render_canonical' ), 'canonical renderer missing' );
$assert( false !== strpos( $core, "'maybe_render_canonical', 2" ), 'canonical hook missing behind Docs OS gate' );
$assert( false === strpos( $runtime, 'add_rewrite_rule' ), 'canonical bridge must preserve existing rewrites' );
$assert( false === strpos( $runtime, 'template_include' ), 'canonical bridge must not replace theme templates' );
$assert( false !== strpos( $runtime, 'json_decode( $entry, true )' ), 'BetterDocs related JSON mapper missing' );
$assert( false !== strpos( $runtime, 'if ( ! $canonical ) { header( \'X-Robots-Tag: noindex' ), 'preview-only robots guard missing' );
$assert( false !== strpos( $runtime, 'if ( ! $this->canonical_render )' ), 'canonical/preview link separation missing' );

echo "PASS docs-os-canonical-harness\n";
