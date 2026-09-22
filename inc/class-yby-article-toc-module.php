<?php
/**
 * Article TOC V1 feature module.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Article_TOC_Module {
	const MODULE_ID = 'article_toc';
	const VERSION = '1.0.0';

	public static function register_module() {
		return YBY_Module_Registry::register(
			self::MODULE_ID,
			array(
				'name' => '文章目录',
				'description' => '自动为博客文章生成 Inline Summary、桌面悬浮目录、H2 锚点与 Scroll Spy。',
				'version' => self::VERSION,
				'schema_version' => '1',
				'default_enabled' => false,
				'status' => 'ready',
				'capability' => 'andy_core_settings_manage',
				'boot' => array( __CLASS__, 'boot' ),
				'settings' => array(
					'page' => YBY_Helpers::admin_page_slug(),
					'tab' => 'article-toc',
				),
				'storage' => array(
					'option_key' => 'yby_article_toc_settings_v1',
					'schema_version' => '1',
				),
				'assets' => array(
					'frontend' => array(
						'enqueue' => array( __CLASS__, 'enqueue_assets' ),
						'condition' => array( __CLASS__, 'should_enqueue_assets' ),
						'priority' => 18,
						'handles' => array( 'andy-article-toc' ),
					),
				),
				'dependencies' => array( 'core_runtime', 'module_registry' ),
			)
		);
	}

	public static function boot( $module ) {
		unset( $module );
	}

	public static function defaults() {
		return array(
			'post_types' => array( 'post' ),
			'inline_title' => 'Summary',
			'min_h2_count' => 2,
			'floating_breakpoint' => 1200,
		);
	}

	public static function allowed_post_types() {
		/**
		 * V1 is intentionally Posts-only. The filter keeps the adapter shape
		 * extensible without silently enabling other content types.
		 */
		$allowed = apply_filters( 'andy_article_toc_allowed_post_types', array( 'post' ) );
		$allowed = is_array( $allowed ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $allowed ) ) ) ) : array( 'post' );
		return ! empty( $allowed ) ? $allowed : array( 'post' );
	}

	public static function sanitize_settings( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$allowed = self::allowed_post_types();
		$post_types = isset( $raw['post_types'] ) && is_array( $raw['post_types'] )
			? array_values( array_intersect( $allowed, array_values( array_unique( array_map( 'sanitize_key', $raw['post_types'] ) ) ) ) )
			: array();

		$title = isset( $raw['inline_title'] ) ? sanitize_text_field( $raw['inline_title'] ) : '';
		if ( '' === $title ) { $title = 'Summary'; }

		$min_h2 = isset( $raw['min_h2_count'] ) ? absint( $raw['min_h2_count'] ) : 2;
		$min_h2 = min( 12, max( 2, $min_h2 ) );

		$breakpoint = isset( $raw['floating_breakpoint'] ) ? absint( $raw['floating_breakpoint'] ) : 1200;
		$breakpoint = min( 1920, max( 900, $breakpoint ) );

		return array(
			'post_types' => $post_types,
			'inline_title' => $title,
			'min_h2_count' => $min_h2,
			'floating_breakpoint' => $breakpoint,
		);
	}

	public static function get_settings() {
		return YBY_Module_Settings_Store::get(
			self::MODULE_ID,
			self::defaults(),
			array( __CLASS__, 'sanitize_settings' )
		);
	}

	public static function save_settings( $raw ) {
		return YBY_Module_Settings_Store::save(
			self::MODULE_ID,
			$raw,
			array( __CLASS__, 'sanitize_settings' )
		);
	}

	public static function should_enqueue_assets( $module ) {
		unset( $module );

		if ( is_admin() || is_feed() || is_embed() ) { return false; }

		$settings = self::get_settings();
		$post_types = isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ? $settings['post_types'] : array();
		if ( empty( $post_types ) || ! is_singular( $post_types ) ) { return false; }

		$post_type = get_post_type();
		return is_string( $post_type ) && in_array( $post_type, $post_types, true );
	}

	public static function enqueue_assets( $module ) {
		unset( $module );
		$settings = self::get_settings();

		wp_enqueue_style(
			'andy-article-toc',
			YBY_CORE_PLUGIN_URL . 'public/css/yby-article-toc.css',
			array(),
			YBY_CORE_VERSION
		);

		wp_enqueue_script(
			'andy-article-toc',
			YBY_CORE_PLUGIN_URL . 'public/js/yby-article-toc.js',
			array(),
			YBY_CORE_VERSION,
			true
		);

		wp_localize_script(
			'andy-article-toc',
			'AndyArticleTOCConfig',
			array(
				'title' => $settings['inline_title'],
				'minH2Count' => (int) $settings['min_h2_count'],
				'floatingBreakpoint' => (int) $settings['floating_breakpoint'],
				'labels' => array(
					'inline' => __( 'Article summary', 'yby-core' ),
					'floating' => __( 'Article contents', 'yby-core' ),
				),
			)
		);
	}
}
