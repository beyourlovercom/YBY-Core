<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$root = dirname( __DIR__ );
$docs_admin = file_get_contents( $root . '/admin/class-yby-docs-os-admin.php' );
$core_menu = file_get_contents( $root . '/admin/class-yby-project-studio.php' );
$registry = file_get_contents( $root . '/inc/class-yby-module-registry.php' );

$assert( false !== strpos( $core_menu, "__( 'Andy Core', 'yby-core' )" ), 'Andy Core top-level display label missing' );
$assert( false !== strpos( $core_menu, "return 'yby-os';" ), 'Andy Core stable slug changed' );
$assert( false !== strpos( $docs_admin, "const MENU_LABEL = 'Andy Docs';" ), 'Andy Docs display label contract missing' );
$assert( false !== strpos( $docs_admin, "const PAGE_SLUG = 'yby-docs-os';" ), 'Andy Docs stable slug changed' );
$assert( false !== strpos( $docs_admin, "add_menu_page( self::MENU_LABEL, self::MENU_LABEL, 'andy_core_settings_manage', self::PAGE_SLUG" ), 'Andy Docs menu/capability contract changed' );
$assert( false !== strpos( $registry, "'docs_os' => array('label'=>'Andy Docs'" ), 'Module registry must display Andy Docs' );
$assert( false !== strpos( $registry, "'settings'=>array('page'=>'yby-docs-os')" ), 'Docs settings URL contract changed' );

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
