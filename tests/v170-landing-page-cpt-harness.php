<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_registered_post_types'] = array();
$GLOBALS['yby_submenus'] = array();
$GLOBALS['yby_options'] = array();
$GLOBALS['yby_flushes'] = 0;

function __( $text, $domain = null ) { return $text; }
function register_post_type( $post_type, $args ) { $GLOBALS['yby_registered_post_types'][ $post_type ] = $args; return (object) $args; }
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null, $position = null ) {
	$GLOBALS['yby_submenus'][] = compact( 'parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'position' );
	return $parent_slug . '_page_' . preg_replace( '/[^a-z0-9_\-]/', '-', strtolower( $menu_slug ) );
}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function get_post_type( $post_id ) { return 0; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_options'] ) ? $GLOBALS['yby_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['yby_options'][ $key ] = $value; return true; }
function flush_rewrite_rules( $hard = true ) { $GLOBALS['yby_flushes']++; }

class YBY_Content_Admin {
	const MENU_SLUG = 'yby-content';
}

require_once dirname( __DIR__ ) . '/inc/class-yby-landing-page-cpt.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$cpt = new YBY_Landing_Page_CPT();
$cpt->register();

$assert( isset( $GLOBALS['yby_registered_post_types']['yby_landing_page'] ), 'Landing Page CPT registration missing' );
$args = $GLOBALS['yby_registered_post_types']['yby_landing_page'];

$assert( 'yby_landing_page' === YBY_Landing_Page_CPT::POST_TYPE, 'Landing Page internal post type must be namespaced' );
$assert( 'lp' === YBY_Landing_Page_CPT::REWRITE_SLUG, 'Landing Page public rewrite slug must remain /lp/' );
$assert( true === $args['public'] && true === $args['publicly_queryable'], 'Landing Pages must be public and queryable' );
$assert( true === $args['show_ui'], 'Landing Pages must have WordPress admin UI' );
$assert( false === $args['show_in_menu'], 'Landing CPT must not create its own top-level menu' );
$assert( true === $args['show_in_rest'], 'Landing Pages must support the block editor / REST' );
$assert( false === $args['has_archive'], 'Landing Pages must not create an archive route' );
$assert( true === $args['exclude_from_search'], 'Landing Pages must stay out of normal site search' );
$assert( 'page' === $args['capability_type'] && true === $args['map_meta_cap'], 'Landing Pages must use page-like capabilities' );
$assert( 'lp' === $args['rewrite']['slug'] && false === $args['rewrite']['with_front'], 'Landing Page rewrite contract must be /lp/{slug}' );
foreach ( array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ) as $support ) {
	$assert( in_array( $support, $args['supports'], true ), 'Landing Page support missing: ' . $support );
}

$cpt->add_admin_menu();
$assert( 1 === count( $GLOBALS['yby_submenus'] ), 'Landing Page admin must register exactly one visible submenu entry' );
$menu = $GLOBALS['yby_submenus'][0];
$assert( 'yby-content' === $menu['parent_slug'], 'Landing Pages must live under Andy Content' );
$assert( 'Landing Pages' === $menu['menu_title'], 'Landing Pages menu label mismatch' );
$assert( 'edit_pages' === $menu['capability'], 'Landing Pages menu capability mismatch' );
$assert( 'edit.php?post_type=yby_landing_page' === $menu['menu_slug'], 'Landing Pages list route mismatch' );

$cpt->maybe_flush_rewrite_rules();
$assert( 1 === $GLOBALS['yby_flushes'], 'Landing Page rewrite must flush exactly once for a new rewrite version' );
$assert( '1' === $GLOBALS['yby_options'][ YBY_Landing_Page_CPT::REWRITE_OPTION ], 'Landing Page rewrite version marker missing' );
$cpt->maybe_flush_rewrite_rules();
$assert( 1 === $GLOBALS['yby_flushes'], 'Landing Page rewrite flush must be idempotent' );

echo "PASS v170-landing-page-cpt-harness\n";
