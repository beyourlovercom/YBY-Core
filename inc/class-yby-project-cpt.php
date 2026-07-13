<?php
/**
 * Project Studio Custom Post Type.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Project CPT helper.
 */
class YBY_Project_CPT {

	/**
	 * Post type slug.
	 *
	 * @return string
	 */
	public static function post_type() {
		return 'yby_project';
	}

	/**
	 * Register the Project CPT.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type(
			self::post_type(),
			array(
				'labels'          => array(
					'name'          => __( 'YBY Projects', 'yby-core' ),
					'singular_name' => __( 'YBY Project', 'yby-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/**
	 * Return overview field definition list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function overview_fields() {
		return array(
			'project_id'       => array(
				'label'   => __( 'Project ID', 'yby-core' ),
				'aliases' => array( 'project_id', 'yby_project_id' ),
			),
			'project_name'     => array(
				'label'   => __( 'Project Name', 'yby-core' ),
				'aliases' => array( 'project_name', 'yby_project_name' ),
			),
			'project_slug'     => array(
				'label'   => __( 'Project Slug', 'yby-core' ),
				'aliases' => array( 'project_slug' ),
			),
			'project_status'   => array(
				'label'   => __( 'Project Status', 'yby-core' ),
				'aliases' => array( 'project_status', 'yby_project_status' ),
			),
			'country'          => array(
				'label'   => __( 'Country', 'yby-core' ),
				'aliases' => array( 'country', 'yby_country' ),
			),
			'language'         => array(
				'label'   => __( 'Language', 'yby-core' ),
				'aliases' => array( 'language', 'yby_language' ),
			),
			'currency'         => array(
				'label'   => __( 'Currency', 'yby-core' ),
				'aliases' => array( 'currency', 'yby_currency' ),
			),
			'product_interest' => array(
				'label'   => __( 'Product Interest', 'yby-core' ),
				'aliases' => array( 'product_interest', 'yby_product_interest' ),
			),
			'tracking_group'   => array(
				'label'   => __( 'Tracking Group', 'yby-core' ),
				'aliases' => array( 'tracking_group', 'yby_tracking_group' ),
			),
			'landing_url'      => array(
				'label'   => __( 'Landing URL', 'yby-core' ),
				'aliases' => array( 'landing_url', 'yby_landing_url' ),
			),
			'thank_you_url'    => array(
				'label'   => __( 'Thank You URL', 'yby-core' ),
				'aliases' => array( 'thank_you_url', 'yby_thank_you_url' ),
			),
			'catalog_url'      => array(
				'label'   => __( 'Catalog URL', 'yby-core' ),
				'aliases' => array( 'catalog_url', 'yby_catalog_url' ),
			),
			'youtube_video_id' => array(
				'label'   => __( 'YouTube Video ID', 'yby-core' ),
				'aliases' => array( 'youtube_video_id', 'yby_youtube_video_id' ),
			),
			'crm_pipeline'     => array(
				'label'   => __( 'CRM Pipeline', 'yby-core' ),
				'aliases' => array( 'crm_pipeline', 'yby_crm_pipeline' ),
			),
		);
	}

	/**
	 * Get project overview data.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string>
	 */
	public static function get_overview_data( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );
		$data    = array();

		foreach ( self::overview_fields() as $field_key => $field_config ) {
			$data[ $field_key ] = self::get_field_value( $post_id, $field_config['aliases'] );
		}

		if ( empty( $data['project_name'] ) && $post ) {
			$data['project_name'] = (string) $post->post_title;
		}

		if ( empty( $data['project_slug'] ) && $post ) {
			$data['project_slug'] = (string) $post->post_name;
		}

		return $data;
	}

	/**
	 * Get ACF-compatible field value by alias list.
	 *
	 * @param int              $post_id Post ID.
	 * @param array<int,string> $aliases Alias list.
	 * @return string
	 */
	public static function get_field_value( $post_id, $aliases ) {
		$aliases = is_array( $aliases ) ? $aliases : array();

		foreach ( $aliases as $alias ) {
			$value = '';

			if ( function_exists( 'get_field' ) ) {
				$value = get_field( $alias, $post_id );
			}

			if ( '' === $value || null === $value || false === $value ) {
				$value = get_post_meta( $post_id, $alias, true );
			}

			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return (string) $value;
			}
		}

		return '';
	}
}
