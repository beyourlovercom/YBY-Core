<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};
$root = dirname( __DIR__ );
$admin = file_get_contents( $root . '/admin/class-yby-docs-os-admin.php' );
$editor = file_get_contents( $root . '/admin/views/docs-os-editor.php' );
$directory = file_get_contents( $root . '/admin/views/docs-os-directory.php' );
$settings = file_get_contents( $root . '/admin/views/docs-os-settings.php' );
$runtime = file_get_contents( $root . '/inc/class-yby-docs-runtime.php' );
$js = file_get_contents( $root . '/assets/js/yby-docs-os-admin.js' );
foreach ( array( 'EDITOR_PAGE_SLUG', 'DIRECTORY_PAGE_SLUG', 'SETTINGS_PAGE_SLUG', 'render_editor_page', 'render_directory_page', 'render_settings_page' ) as $needle ) {
	$assert( false !== strpos( $admin, $needle ), 'missing admin workbench contract: ' . $needle );
}
foreach ( array( '可视化编辑', 'HTML 源码', '预览', '特色图片（R2）', 'Related Docs', 'SEO 设置' ) as $label ) {
	$assert( false !== strpos( $editor, $label ), 'missing Editor UI: ' . $label );
}
$assert( false !== strpos( $admin, 'rank_math_title' ) && false !== strpos( $admin, 'rank_math_description' ), 'Rank Math editor bridge missing' );
$assert( false !== strpos( $admin, 'sanitize_editor_html' ) && false !== strpos( $admin, 'wp_kses' ), 'HTML save contract missing' );
foreach ( array( '分类结构', '拖拽排序', '分类封面图', '前台预览' ) as $label ) {
	$assert( false !== strpos( $directory, $label ), 'missing Directory UI: ' . $label );
}
$assert( false !== strpos( $admin, 'doc_category_order' ), 'BetterDocs category order bridge missing' );
$assert( false !== strpos( $js, 'data-yby-category-tree' ) && false !== strpos( $js, 'dragstart' ), 'Directory drag behavior missing' );
foreach ( array( '基础设置', '目录设置', 'R2 设置', 'SEO & 结构化数据', '显示设置', '高级' ) as $label ) {
	$assert( false !== strpos( $settings, $label ), 'missing Settings tab: ' . $label );
}
$assert( false !== strpos( $runtime, 'yby_docs_os_settings_v1' ), 'Runtime settings bridge missing' );
foreach ( array( 'show_search', 'show_categories', 'show_recent', 'show_toc', 'show_related', 'schema_enabled' ) as $key ) {
	$assert( false !== strpos( $runtime, $key ), 'Runtime setting not wired: ' . $key );
}
$assert( false === strpos( $admin, 'wp_delete_post' ), 'Admin workbench must not delete Docs posts' );
$assert( false === strpos( $admin, 'delete_term(' ), 'Admin workbench must not delete category terms' );
$assert( false === strpos( $admin, 'delete_option' ), 'Admin workbench must not delete options' );
$assert( false !== strpos( $admin, "term_exists( \$tag_name, 'doc_tag' )" ) && false !== strpos( $admin, "wp_insert_term( \$tag_name, 'doc_tag' )" ), 'hierarchical doc_tag name-to-term-ID bridge missing' );
$assert( false !== strpos( $admin, 'array_values( array_unique( $tag_ids ) )' ), 'hierarchical doc_tag ID save contract missing' );
$assert( false !== strpos( $admin, 'get_preview_post_link( $post )' ), 'draft Editor preview link missing' );
$assert( false !== strpos( $runtime, "is_preview() && current_user_can( 'edit_post'" ), 'draft preview permission gate missing' );
$assert( false !== strpos( $runtime, "'publish' !== \$post->post_status && ! \$allow_unpublished" ), 'draft preview runtime fail-close missing' );
echo "PASS docs-os-admin-workbench-harness\n";
