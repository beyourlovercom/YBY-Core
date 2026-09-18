<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$runtime = file_get_contents( $root . '/inc/class-yby-docs-runtime.php' );
$css = file_get_contents( $root . '/public/css/yby-docs-os.css' );
$js = file_get_contents( $root . '/public/js/yby-docs-os.js' );
$assert( false !== strpos( $core, "is_enabled( 'docs_os' )" ), 'Docs OS registry gate missing' );
$assert( preg_match( '/if \( \$docs_enabled \).*?YBY_Docs_Runtime.*?query_vars.*?wp_enqueue_scripts.*?template_redirect/s', $core ), 'Docs runtime hooks must stay behind Docs OS gate' );
foreach ( array( 'home', 'category', 'document', 'faq', 'tutorial' ) as $surface ) { $assert( false !== strpos( $runtime, "'{$surface}'" ), 'missing surface: ' . $surface ); }
$assert( false === strpos( $runtime, 'add_rewrite_rule' ) && false === strpos( $runtime, 'template_include' ), 'runtime must preserve existing WordPress routing' );
$assert( false !== strpos( $runtime, "'post_type' => 'docs'" ) && false !== strpos( $runtime, "'taxonomy' => 'doc_category'" ), 'default Docs contract must preserve existing data model' );
$assert( false !== strpos( $runtime, "apply_filters( 'yby_docs_os_contract'" ), 'reusable Docs contract filter missing' );
$assert( false !== strpos( $runtime, '$this->post_type()' ) && false !== strpos( $runtime, '$this->taxonomy()' ), 'runtime must resolve post type/taxonomy through contract' );
$assert( false !== strpos( $runtime, "'s' =>") || false !== strpos( $runtime, "['s']" ), 'server-side search contract missing' );
$assert( false !== strpos( $runtime, '_betterdocs_related_articles' ) && false !== strpos( $runtime, 'related_meta_key' ), 'Related Docs compatibility adapter missing' );
$assert( false !== strpos( $runtime, 'TechArticle' ) && false !== strpos( $runtime, 'application/ld+json' ), 'preview schema missing' );
$assert( false !== strpos( $runtime, 'X-Robots-Tag: noindex' ), 'shadow preview must be noindex' );
$assert( false !== strpos( $runtime, "'local' === wp_get_environment_type()" ), 'local preview safety contract missing' );
$assert( false !== strpos( $runtime, 'data-yby-docs-search' ) && false !== strpos( $runtime, 'data-yby-docs-toc' ), 'Search/TOC surfaces missing' );
$assert( false !== strpos( $css, '@media(max-width:800px)' ) && false !== strpos( $css, '@media(max-width:520px)' ), 'responsive breakpoints missing' );
$assert( false !== strpos( $js, 'buildToc') && false !== strpos( $js, 'bindSearch' ), 'runtime JS behaviors missing' );
echo "PASS docs-os-runtime-harness\n";
