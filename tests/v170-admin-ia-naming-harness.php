<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$root = dirname( __DIR__ );
$content_admin = str_replace( "\r\n", "\n", file_get_contents( $root . '/admin/class-yby-content-admin.php' ) );
$docs_admin = str_replace( "\r\n", "\n", file_get_contents( $root . '/admin/class-yby-docs-os-admin.php' ) );
$core_menu = str_replace( "\r\n", "\n", file_get_contents( $root . '/admin/class-yby-project-studio.php' ) );
$registry = str_replace( "\r\n", "\n", file_get_contents( $root . '/inc/class-yby-module-registry.php' ) );

$assert( false !== strpos( $core_menu, "__( 'Andy Core', 'yby-core' )" ), 'Andy Core top-level display label missing' );
$assert( false !== strpos( $core_menu, "return 'yby-os';" ), 'Andy Core stable slug changed' );
$assert( false !== strpos( $content_admin, "const MENU_SLUG = 'yby-content';" ), 'Andy Content stable slug missing' );
$assert( false !== strpos( $content_admin, "__( 'Andy Content', 'yby-core' )" ), 'Andy Content top-level display label missing' );
$assert( false !== strpos( $content_admin, "'dashicons-layout',\n\t\t\t21" ), 'Andy Content must stay beside WordPress Pages' );
$assert( false !== strpos( $docs_admin, "const MENU_LABEL = 'Andy Docs';" ), 'Andy Docs product label contract missing' );
$assert( false !== strpos( $docs_admin, "const PAGE_SLUG = 'yby-docs-os';" ), 'Andy Docs stable slug changed' );
$assert( false === strpos( $docs_admin, 'add_menu_page( self::MENU_LABEL' ), 'Andy Docs must not register a top-level menu' );
$assert( false !== strpos( $docs_admin, "\$parent = YBY_Content_Admin::MENU_SLUG" ), 'Andy Docs must resolve Andy Content as its parent' );
$assert( false !== strpos( $docs_admin, "add_submenu_page( \$parent, self::MENU_LABEL, 'Docs'" ), 'Andy Docs must live under Andy Content' );
$assert( false !== strpos( $registry, "'name' => 'Andy Docs'" ), 'Module registry must display Andy Docs' );
$assert( false !== strpos( $registry, "'admin_parent' => 'yby-content'" ), 'Docs content-parent contract missing' );
$assert( false !== strpos( $registry, "'settings' => array( 'page' => 'yby-docs-os' )" ), 'Docs settings URL contract changed' );

$all_php = '';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
	if ( 'php' !== strtolower( $file->getExtension() ) ) { continue; }
	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) { continue; }
	$all_php .= "\n" . file_get_contents( $path );
}
$assert( false === strpos( $all_php, "add_menu_page( 'Andy Template'" ), 'v1.7.0 must not register visible Andy Template menu' );
$assert( false === strpos( $all_php, 'add_menu_page( "Andy Template"' ), 'v1.7.0 must not register visible Andy Template menu' );

echo "PASS v170-admin-ia-naming-harness\n";
