<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_top_menus'] = array();
$GLOBALS['yby_submenus'] = array();

function __( $text, $domain = null ) { return $text; }
function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = null, $icon_url = '', $position = null ) {
	$GLOBALS['yby_top_menus'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'icon_url', 'position' );
	return 'toplevel_page_' . $menu_slug;
}
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null, $position = null ) {
	$GLOBALS['yby_submenus'][] = compact( 'parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'position' );
	return $parent_slug ? $parent_slug . '_page_' . $menu_slug : 'hidden_page_' . $menu_slug;
}

require_once dirname( __DIR__ ) . '/admin/class-yby-content-admin.php';
require_once dirname( __DIR__ ) . '/admin/class-yby-docs-os-admin.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-landing-page-cpt.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$content = new YBY_Content_Admin();
$docs = new YBY_Docs_OS_Admin();
$landing = new YBY_Landing_Page_CPT();
$content->add_admin_menu();
$docs->add_admin_menu();
$landing->add_admin_menu();

$assert( 1 === count( $GLOBALS['yby_top_menus'] ), 'Content IA must register exactly one top-level menu in this harness' );
$top = $GLOBALS['yby_top_menus'][0];
$assert( 'Andy Content' === $top['menu_title'], 'top-level menu must be Andy Content' );
$assert( 'yby-content' === $top['menu_slug'], 'Andy Content slug mismatch' );
$assert( 21 === $top['position'], 'Andy Content must sit beside WordPress Pages' );

$visible = array_values( array_filter( $GLOBALS['yby_submenus'], static function ( $item ) {
	return 'yby-content' === $item['parent_slug'];
} ) );
$assert( 2 === count( $visible ), 'Andy Content should expose Overview and Docs without duplicating the first-class Landing Page workbench' );
$assert( 'yby-content' === $visible[0]['menu_slug'] && 'Overview' === $visible[0]['menu_title'], 'Andy Content Overview missing' );
$assert( 'yby-docs-os' === $visible[1]['menu_slug'] && 'Docs' === $visible[1]['menu_title'], 'Docs must be a child of Andy Content' );

foreach ( $GLOBALS['yby_top_menus'] as $menu ) {
	$assert( 'Andy Docs' !== $menu['menu_title'], 'Andy Docs must not be top-level' );
	$assert( false === stripos( $menu['menu_title'], 'Template' ), 'Template must not be top-level before implementation' );
	$assert( false === stripos( $menu['menu_title'], 'Landing' ), 'Andy Core must not register a duplicate Landing Page top-level menu in the Content shell' );
}
foreach ( $visible as $menu ) {
	$assert( false === stripos( $menu['menu_title'], 'Template' ), 'Template must not appear as empty Content submenu' );
}

$hidden_slugs = array_values( array_map( static function ( $item ) { return $item['menu_slug']; }, array_filter( $GLOBALS['yby_submenus'], static function ( $item ) {
	return null === $item['parent_slug'];
} ) ) );
foreach ( array( 'yby-docs-all', 'yby-docs-directory', 'yby-docs-settings', 'yby-docs-editor', 'yby-docs-faq', 'yby-docs-tutorial' ) as $slug ) {
	$assert( in_array( $slug, $hidden_slugs, true ), 'Docs stable hidden route missing: ' . $slug );
}

echo "PASS v170-content-ia-harness\n";
