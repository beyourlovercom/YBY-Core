<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['yby_registered_post_types'] = array();
$GLOBALS['yby_submenus'] = array();
$GLOBALS['yby_options'] = array();
$GLOBALS['yby_flushes'] = 0;
$GLOBALS['yby_existing_post_types'] = array();

function __( $text, $domain = null ) { return $text; }
function post_type_exists( $post_type ) { return ! empty( $GLOBALS['yby_existing_post_types'][ $post_type ] ); }
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

$assert( isset( $GLOBALS['yby_registered_post_types']['landing_page'] ), 'Canonical Landing Page fallback registration missing' );
$args = $GLOBALS['yby_registered_post_types']['landing_page'];

$assert( 'landing_page' === YBY_Landing_Page_CPT::POST_TYPE, 'Andy Core must adopt the canonical Landing Page post type' );
$assert( 'lp' === YBY_Landing_Page_CPT::REWRITE_SLUG, 'Landing Page public rewrite slug must remain /lp/' );
$assert( true === $args['public'] && true === $args['publicly_queryable'], 'Landing Pages must be public and queryable' );
$assert( true === $args['show_ui'], 'Landing Pages must have WordPress admin UI' );
$assert( true === $args['show_in_menu'], 'Canonical Landing Page fallback must remain a first-class top-level menu' );
$assert( 5 === $args['menu_position'] && 'dashicons-megaphone' === $args['menu_icon'], 'Canonical Landing Page fallback menu contract mismatch' );
$assert( true === $args['show_in_rest'], 'Landing Pages must support the block editor / REST' );
$assert( false === $args['has_archive'], 'Landing Pages must not create an archive route' );
$assert( true === $args['exclude_from_search'], 'Landing Pages must stay out of normal site search' );
$assert( 'page' === $args['capability_type'] && true === $args['map_meta_cap'], 'Landing Pages must use page-like capabilities' );
$assert( 'lp' === $args['rewrite']['slug'] && false === $args['rewrite']['with_front'], 'Landing Page rewrite contract must be /lp/{slug}' );
foreach ( array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes' ) as $support ) {
	$assert( in_array( $support, $args['supports'], true ), 'Landing Page support missing: ' . $support );
}

$cpt->add_admin_menu();
$assert( 0 === count( $GLOBALS['yby_submenus'] ), 'Andy Core must not duplicate canonical Landing Page under Andy Content' );

$GLOBALS['yby_existing_post_types']['landing_page'] = true;
$registered_before_adopt = count( $GLOBALS['yby_registered_post_types'] );
$cpt->register();
$assert( $registered_before_adopt === count( $GLOBALS['yby_registered_post_types'] ), 'Existing canonical Landing Page CPT must be adopted without re-registration' );

$cpt->maybe_flush_rewrite_rules();
$assert( 1 === $GLOBALS['yby_flushes'], 'Landing Page rewrite must flush exactly once for a new rewrite version' );
$assert( '1' === $GLOBALS['yby_options'][ YBY_Landing_Page_CPT::REWRITE_OPTION ], 'Landing Page rewrite version marker missing' );
$cpt->maybe_flush_rewrite_rules();
$assert( 1 === $GLOBALS['yby_flushes'], 'Landing Page rewrite flush must be idempotent' );

echo "PASS v170-landing-page-cpt-harness\n";
