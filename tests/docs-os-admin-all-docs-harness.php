<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};
$root = dirname( __DIR__ );
$admin = file_get_contents( $root . '/admin/class-yby-docs-os-admin.php' );
$view = file_get_contents( $root . '/admin/views/docs-os-all-docs.php' );
$css = file_get_contents( $root . '/assets/css/yby-docs-os-admin.css' );
$js = file_get_contents( $root . '/assets/js/yby-docs-os-admin.js' );

$assert( false !== strpos( $admin, "const ALL_PAGE_SLUG = 'yby-docs-all'" ), 'All Docs route missing' );
$assert( false !== strpos( $admin, 'render_all_docs_page' ), 'All Docs controller missing' );
$assert( false !== strpos( $admin, 'WP_Query' ), 'All Docs query missing' );
foreach ( array( '全部文档', '全部分类', '全部状态', '标签', 'SEO', '批量操作', '复制链接' ) as $label ) {
	$assert( false !== strpos( $view, $label ), 'missing All Docs UI: ' . $label );
}
$assert( false !== strpos( $view, 'rank_math_seo_score' ), 'Rank Math score bridge missing' );
$assert( false !== strpos( $view, 'doc_ids[]' ), 'bulk selection missing' );
$assert( false !== strpos( $admin, 'handle_all_docs_bulk' ), 'bulk handler missing' );
$assert( false !== strpos( $js, 'data-yby-select-all' ), 'select-all behavior missing' );
$assert( false !== strpos( $css, '.yby-doc-view-tabs' ), 'canonical All Docs tabs CSS missing' );
$assert( false !== strpos( $view, "get_preview_post_link( \$doc )" ), 'draft All Docs preview link missing' );
echo "PASS docs-os-admin-all-docs-harness\n";
