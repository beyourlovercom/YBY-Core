<?php
/**
 * Dev-only Country Clone V1 independent draft transaction.
 *
 * This service is NOT connected to an admin action until the Bricks / country
 * transformation contract has been verified. The feature flag defaults OFF.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Country_Clone_Draft_Service {
	const FLAG = 'YBY_COUNTRY_CLONE_V1_DEV_DRAFT_ENABLED';
	const PAGE_SLUG = 'irrigation-solution-germany';
	const PROJECT_SLUG = 'germany-irrigation-solution';

	/**
	 * Create separate draft posts in authorized Local/Dev only.
	 *
	 * @param int    $source_project_id Existing source Project.
	 * @param int    $source_page_id Existing source Landing Page.
	 * @param string $expected_fingerprint Owner-reviewed immutable source evidence.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function create( $source_project_id, $source_page_id, $expected_fingerprint ) {
		if ( ! defined( self::FLAG ) || true !== constant( self::FLAG ) ) {
			return new WP_Error( 'clone_flag_closed', 'Country Clone draft writes are disabled.' );
		}
		$hostname = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
		if ( ! in_array( $hostname, array( 'localdev.ybyirrigation.com', 'dev.ybyirrigation.com' ), true ) ||
			! in_array( $environment, array( 'local', 'development' ), true ) ) {
			return new WP_Error( 'clone_environment_blocked', 'Only explicitly configured YBY Irrigation Local/Dev may create country drafts.' );
		}

		$source_project_id = absint( $source_project_id );
		$source_page_id = absint( $source_page_id );
		if ( ! current_user_can( 'andy_core_settings_manage' ) || ! current_user_can( 'edit_posts' ) ||
			! current_user_can( 'edit_pages' ) || ! current_user_can( 'edit_post', $source_project_id ) ||
			! current_user_can( 'edit_post', $source_page_id ) ) {
			return new WP_Error( 'clone_access_denied', 'Insufficient Project and Landing Page capabilities.' );
		}

		$audit = YBY_Country_Clone_Preflight::evaluate( $source_project_id, $source_page_id, 'DE' );
		if ( '' === (string) $expected_fingerprint ||
			! hash_equals( (string) $audit['source_fingerprint'], (string) $expected_fingerprint ) ) {
			return new WP_Error( 'clone_source_changed', 'Source fingerprint missing or changed. Repeat read-only preflight.' );
		}
		foreach ( $audit['checks'] as $check ) {
			if ( 'write_gate' === $check['code'] ) { continue; }
			if ( ! $check['passed'] ) {
				return new WP_Error( 'clone_preflight_blocked', 'Country Clone blocked at: ' . $check['code'] );
			}
		}

		$source_page = get_post( $source_page_id );
		if ( ! $source_page || 'landing_page' !== $source_page->post_type ||
			false !== stripos( (string) $source_page->post_content, (string) $audit['source_country'] ) ) {
			return new WP_Error( 'clone_post_content_unreviewed', 'Country references in post content require manual review.' );
		}
		// Only no-Code, no-country-reference Bricks content can currently pass.
		// Never transfer a Code signature, arbitrary postmeta or executable JS.
		$elements = get_post_meta( $source_page_id, '_bricks_page_content_2', true );
		$page_settings = get_post_meta( $source_page_id, '_bricks_page_settings', true );
		$page_settings = is_array( $page_settings ) ? $page_settings : array();
		if ( ! is_array( $elements ) || empty( $elements ) ||
			! empty( $page_settings['customScriptsBodyFooter'] ) ) {
			return new WP_Error( 'clone_bricks_unreviewed', 'Bricks executable scripts or unsupported content require approved transformation and native signing.' );
		}
		$css = array();
		foreach ( array( 'customCss', 'customCss:mobile_portrait' ) as $key ) {
			if ( isset( $page_settings[ $key ] ) && is_string( $page_settings[ $key ] ) ) {
				if ( false !== stripos( $page_settings[ $key ], (string) $audit['source_country'] ) ) {
					return new WP_Error( 'clone_css_country', 'Source Country still appears in Bricks CSS.' );
				}
				$css[ $key ] = $page_settings[ $key ];
			}
		}

		$clone_key = hash( 'sha256', 'country-clone-v1|DE|' . $source_project_id . '|' . $source_page_id );
		$existing = get_posts( array(
			'post_type' => 'yby_project', 'post_status' => 'draft',
			'posts_per_page' => 2, 'meta_key' => 'yby_clone_v1_key', 'meta_value' => $clone_key,
		) );
		if ( ! empty( $existing ) ) {
			$id = (int) $existing[0]->ID;
			$page_id = absint( get_post_meta( $id, 'yby_clone_v1_page_id', true ) );
			$page = $page_id ? get_post( $page_id ) : null;
			if ( $page && 'landing_page' === $page->post_type && 'draft' === $page->post_status ) {
				return array( 'project_id' => $id, 'page_id' => $page_id, 'reused' => true, 'status' => 'draft' );
			}
			return new WP_Error( 'clone_partial_existing', 'An incomplete draft exists; manual review required. Do not duplicate.' );
		}
		if ( get_page_by_path( self::PAGE_SLUG, OBJECT, 'landing_page' ) ||
			get_page_by_path( self::PROJECT_SLUG, OBJECT, 'yby_project' ) ) {
			return new WP_Error( 'clone_slug_collision', 'Germany slug is already in use.' );
		}

		$lock_key = 'yby_clone_v1_' . substr( $clone_key, 0, 24 );
		if ( ! add_option( $lock_key, 'locked', '', false ) ) {
			return new WP_Error( 'clone_locked', 'Country Clone is already in progress or needs manual lock recovery.' );
		}
		$new_project_id = 0;
		$new_page_id = 0;
		$success = false;
		try {
			$new_project_id = wp_insert_post( array(
				'post_type' => 'yby_project', 'post_status' => 'draft',
				'post_title' => 'Germany Irrigation Solution', 'post_name' => self::PROJECT_SLUG,
			), true );
			if ( is_wp_error( $new_project_id ) || ! $new_project_id ) {
				return new WP_Error( 'clone_project_create_failed', 'Unable to create independent Germany Project draft.' );
			}
			$new_project_id = absint( $new_project_id );
			$new_page_id = wp_insert_post( array(
				'post_type' => 'landing_page', 'post_status' => 'draft',
				'post_title' => 'IRRIGATION SOLUTION - Germany', 'post_name' => self::PAGE_SLUG,
				'post_content' => (string) $source_page->post_content,
			), true );
			if ( is_wp_error( $new_page_id ) || ! $new_page_id ) {
				return new WP_Error( 'clone_page_create_failed', 'Unable to create independent Germany Landing draft.' );
			}
			$new_page_id = absint( $new_page_id );
			if ( ! update_post_meta( $new_page_id, '_bricks_page_content_2', $elements ) ||
				( ! empty( $css ) && ! update_post_meta( $new_page_id, '_bricks_page_settings', $css ) ) ) {
				return new WP_Error( 'clone_bricks_copy_failed', 'Bricks draft structure could not be copied.' );
			}
			$project_meta = array(
				'yby_country' => 'Germany',
				'yby_project_name' => 'Germany Irrigation Solution',
				'yby_project_status' => 'draft',
				'yby_landing_url' => '/lp/' . self::PAGE_SLUG . '/',
				'yby_clone_v1_key' => $clone_key,
				'yby_clone_v1_page_id' => $new_page_id,
				'yby_clone_source_project_id' => $source_project_id,
				'yby_clone_source_page_id' => $source_page_id,
			);
			$page_meta = array(
				'yby_country' => 'Germany',
				'yby_page_type' => 'landing_page',
				'yby_profile_id' => 'irrigation-solution-germany',
				'yby_return_page_url' => '/lp/' . self::PAGE_SLUG . '/',
				'yby_clone_v1_project_id' => $new_project_id,
			);
			$source_thank_you = get_post_meta( $source_page_id, 'yby_thank_you_url', true );
			if ( is_string( $source_thank_you ) ) {
				$page_meta['yby_thank_you_url'] = $source_thank_you;
				$project_meta['yby_thank_you_url'] = $source_thank_you;
			}
			foreach ( $project_meta as $key => $value ) {
				if ( ! update_post_meta( $new_project_id, $key, $value ) ) {
					return new WP_Error( 'clone_project_meta_failed', 'Project draft metadata failed.' );
				}
			}
			foreach ( $page_meta as $key => $value ) {
				if ( ! update_post_meta( $new_page_id, $key, $value ) ) {
					return new WP_Error( 'clone_page_meta_failed', 'Landing draft metadata failed.' );
				}
			}
			$success = true;
			return array( 'project_id' => $new_project_id, 'page_id' => $new_page_id,
				'reused' => false, 'status' => 'draft' );
		} finally {
			// Compensation only removes IDs created by THIS invocation; source stays intact.
			if ( ! $success ) {
				if ( is_int( $new_page_id ) && $new_page_id > 0 ) { wp_delete_post( $new_page_id, true ); }
				if ( is_int( $new_project_id ) && $new_project_id > 0 ) { wp_delete_post( $new_project_id, true ); }
			}
			delete_option( $lock_key );
		}
	}
}
