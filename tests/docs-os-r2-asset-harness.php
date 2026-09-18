<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$root = dirname( __DIR__ );
$runtime = file_get_contents( $root . '/inc/class-yby-docs-runtime.php' );

$assert( false !== strpos( $runtime, 'resolve_asset_urls' ), 'asset resolver missing' );
$assert( false !== strpos( $runtime, 'advmo_credentials' ), 'R2 configuration lookup missing' );
$assert( false !== strpos( $runtime, 'cloudflare_r2' ), 'Cloudflare R2 provider lookup missing' );
$assert( false !== strpos( $runtime, 'advmo_offloaded' ), 'offloaded guard missing' );
$assert( false !== strpos( $runtime, 'advmo_path' ), 'R2 object path lookup missing' );
$assert( false !== strpos( $runtime, 'wp_get_attachment_metadata' ), 'attachment size mapping missing' );
$assert( false !== strpos( $runtime, 'rawurlencode( $file )' ), 'safe R2 filename encoding missing' );
$assert( false === strpos( $runtime, 'img.beyourlover.com' ), 'site-specific R2 domain must not be hard-coded' );
$assert( false !== strpos( $runtime, '$rendered_content = $this->resolve_asset_urls' ), 'resolver must run at render time' );

echo "PASS docs-os-r2-asset-harness\n";
