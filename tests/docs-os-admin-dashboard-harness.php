<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$admin = file_get_contents( $root . '/admin/class-yby-docs-os-admin.php' );
$view = file_get_contents( $root . '/admin/views/docs-os-dashboard.php' );
$css = file_get_contents( $root . '/assets/css/yby-docs-os-admin.css' );

$assert( false !== strpos( $core, "admin_enqueue_scripts', \$docs_admin, 'enqueue_assets'" ), 'Docs admin asset gate missing' );
$assert( false !== strpos( $admin, 'add_menu_page' ) && false !== strpos( $admin, "const PAGE_SLUG = 'yby-docs-os'" ), 'dedicated Docs OS top-level menu missing' );
foreach ( array( '全部文档', '分类目录', 'Tags', 'FAQ', 'Tutorial', '设置' ) as $label ) { $assert( false !== strpos( $admin, $label ), 'missing Docs OS menu item: ' . $label ); }
foreach ( array( '最近更新的文档', '文档分类分布', '最近 7 天访问趋势', '快速操作', 'R2 资源状态', '系统状态' ) as $label ) { $assert( false !== strpos( $view, $label ), 'missing dashboard section: ' . $label ); }
$assert( false !== strpos( $admin, 'betterdocs_analytics' ), 'BetterDocs analytics bridge missing' );
$assert( false !== strpos( $admin, 'advmo_credentials' ) && false !== strpos( $admin, 'advmo_offloaded' ), 'R2 status bridge missing' );
$assert( false !== strpos( $css, '.yby-docs-dashboard' ) && false !== strpos( $css, '.yby-dashboard-grid' ), 'dashboard canonical CSS missing' );
$assert( false === strpos( $admin, 'wp_delete_post' ) && false === strpos( $admin, 'delete_term' ) && false === strpos( $admin, 'delete_option' ), 'dashboard must remain read-only' );
echo "PASS docs-os-admin-dashboard-harness\n";
