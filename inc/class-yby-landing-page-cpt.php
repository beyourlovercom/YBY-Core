<?php
/**
 * Canonical Landing Page content type.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Landing_Page_CPT {
	const POST_TYPE = 'landing_page';
	const REWRITE_SLUG = 'lp';
	const REWRITE_VERSION = '1';
	const REWRITE_OPTION = 'yby_landing_page_rewrite_version';

	public function register() {
		if ( post_type_exists( self::POST_TYPE ) ) { return; }
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name' => __( 'Landing Pages', 'yby-core' ),
					'singular_name' => __( 'Landing Page', 'yby-core' ),
					'add_new' => __( 'Add New', 'yby-core' ),
					'add_new_item' => __( 'Add New Landing Page', 'yby-core' ),
					'edit_item' => __( 'Edit Landing Page', 'yby-core' ),
					'new_item' => __( 'New Landing Page', 'yby-core' ),
					'view_item' => __( 'View Landing Page', 'yby-core' ),
					'view_items' => __( 'View Landing Pages', 'yby-core' ),
					'search_items' => __( 'Search Landing Pages', 'yby-core' ),
					'not_found' => __( 'No Landing Pages found.', 'yby-core' ),
					'not_found_in_trash' => __( 'No Landing Pages found in Trash.', 'yby-core' ),
					'all_items' => __( 'Landing Pages', 'yby-core' ),
					'archives' => __( 'Landing Page Archives', 'yby-core' ),
					'attributes' => __( 'Landing Page Attributes', 'yby-core' ),
					'featured_image' => __( 'Landing Page Image', 'yby-core' ),
					'set_featured_image' => __( 'Set landing page image', 'yby-core' ),
					'remove_featured_image' => __( 'Remove landing page image', 'yby-core' ),
					'use_featured_image' => __( 'Use as landing page image', 'yby-core' ),
					'menu_name' => __( 'Landing Pages', 'yby-core' ),
				),
				'public' => true,
				'publicly_queryable' => true,
				'exclude_from_search' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_admin_bar' => true,
				'show_in_nav_menus' => false,
				'show_in_rest' => true,
				'menu_icon' => 'dashicons-megaphone',
				'menu_position' => 5,
				'capability_type' => 'page',
				'map_meta_cap' => true,
				'hierarchical' => false,
				'has_archive' => false,
				'query_var' => self::POST_TYPE,
				'rewrite' => array(
					'slug' => self::REWRITE_SLUG,
					'with_front' => false,
					'feeds' => false,
					'pages' => false,
				),
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
					'excerpt',
					'revisions',
					'custom-fields',
					'page-attributes',
				),
			)
		);
	}

	public function add_admin_menu() {
		// Canonical Landing Page remains a first-class top-level B2B workbench.
	}

	public function filter_parent_file( $parent_file ) { return $parent_file; }

	public function filter_submenu_file( $submenu_file ) { return $submenu_file; }

	public function maybe_flush_rewrite_rules() {
		if ( self::REWRITE_VERSION === (string) get_option( self::REWRITE_OPTION, '' ) ) { return; }
		$this->register();
		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, self::REWRITE_VERSION, false );
	}

	protected function is_landing_page_screen() {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( self::POST_TYPE === $post_type ) { return true; }

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		return $post_id > 0 && self::POST_TYPE === get_post_type( $post_id );
	}
}
