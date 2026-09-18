<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$runtime = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-docs-runtime.php' );

$assert( false !== strpos( $runtime, "apply_filters( 'yby_docs_os_contract'" ), 'contract filter missing' );
$assert( false !== strpos( $runtime, "'post_type' => 'docs'" ), 'default post type missing' );
$assert( false !== strpos( $runtime, "'taxonomy' => 'doc_category'" ), 'default taxonomy missing' );
$assert( false !== strpos( $runtime, "'base_path' => 'docs'" ), 'default base path missing' );
$assert( false !== strpos( $runtime, "'related_meta_key' => '_betterdocs_related_articles'" ), 'compatibility adapter default missing' );
$assert( false !== strpos( $runtime, '$this->post_type()' ), 'post type resolver missing' );
$assert( false !== strpos( $runtime, '$this->taxonomy()' ), 'taxonomy resolver missing' );
$assert( false !== strpos( $runtime, '$this->related_meta_key()' ), 'related meta resolver missing' );
$assert( false === strpos( $runtime, 'img.beyourlover.com' ), 'site-specific asset domain must not be hard-coded' );
$assert( false === strpos( $runtime, 'localdev.beyourlover.com' ), 'site-specific local domain must not be hard-coded' );

echo "PASS docs-os-reusable-contract-harness\n";
