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
		$source_project = get_post( $source_project_id );
		$source_page = get_post( $source_page_id );
		$source_country = $audit['page_country'] ?? '';
		// The LOCAL legacy Project #518 has no Country meta. Its known
		// Tanzania post/title and landing #292 are explicitly verified in
		// the isolated LocalWP database. No Production or Dev exception.
		$local_legacy_anchor = 'localdev.ybyirrigation.com' === $hostname &&
			518 === $source_project_id && 292 === $source_page_id &&
			$source_project && 'tanzania' === strtolower( trim( (string) $source_project->post_title ) ) &&
			$source_page && 'landing_page' === $source_page->post_type &&
			'Tanzania' === $source_country;
		$remediable = array(
			'bricks_signatures', 'country_nodes', 'unclassified_code_refs',
			'page_script_country', 'page_script_scenarios', 'target_country_buttons',
		);
		foreach ( $audit['checks'] as $check ) {
			if ( 'write_gate' === $check['code'] ) { continue; }
			if ( 'source_country' === $check['code'] && $local_legacy_anchor ) { continue; }
			if ( ! $check['passed'] && ! in_array( $check['code'], $remediable, true ) ) {
				return new WP_Error( 'clone_preflight_blocked', 'Country Clone blocked at: ' . $check['code'] );
			}
		}

		if ( ! $source_page || 'landing_page' !== $source_page->post_type ||
			'' === $source_country ||
			false !== stripos( (string) $source_page->post_content, $source_country ) ) {
			return new WP_Error( 'clone_post_content_unreviewed', 'Country references in post content require manual review.' );
		}
		// Explicitly transform reviewed Germany Bricks nodes and page-level
		// scripts before any database write. Old Code signatures are stripped
		// and only the native Bricks signer can authorize the resulting code.
		$elements = get_post_meta( $source_page_id, '_bricks_page_content_2', true );
		$page_settings = get_post_meta( $source_page_id, '_bricks_page_settings', true );
		$page_settings = is_array( $page_settings ) ? $page_settings : array();
		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return new WP_Error( 'clone_bricks_unreviewed', 'Bricks content structure is unsupported.' );
		}
		$transformed = YBY_Germany_Clone_Transform::build( $elements, $page_settings );
		if ( is_wp_error( $transformed ) ) { return $transformed; }
		$page_settings = $transformed['settings'];
		if ( ! class_exists( '\\Bricks\\Helpers' ) ||
			! method_exists( '\\Bricks\\Helpers', 'generate_new_element_ids' ) ) {
			return new WP_Error( 'clone_bricks_helper_unavailable', 'Bricks native element-ID generator is required.' );
		}
		$new_elements = \Bricks\Helpers::generate_new_element_ids( $transformed['elements'] );
		if ( ! is_array( $new_elements ) || count( $new_elements ) !== count( $elements ) ||
			serialize( $elements ) === serialize( $new_elements ) ) {
			return new WP_Error( 'clone_bricks_ids_unmodified', 'Bricks elements must receive independent new IDs.' );
		}
		$signed = YBY_Germany_Clone_Bricks_Signer::sign( $new_elements );
		if ( is_wp_error( $signed ) ) { return $signed; }
		$new_elements = $signed['elements'];
		$css = array();
		foreach ( array( 'customCss', 'customCss:mobile_portrait' ) as $key ) {
			if ( isset( $page_settings[ $key ] ) && is_string( $page_settings[ $key ] ) ) {
				if ( false !== stripos( $page_settings[ $key ], (string) $audit['source_country'] ) ) {
					return new WP_Error( 'clone_css_country', 'Source Country still appears in Bricks CSS.' );
				}
				if ( preg_match( '~[.#]brxe-[a-z0-9_-]+~i', $page_settings[ $key ] ) ) {
					return new WP_Error( 'clone_bricks_css_ids', 'Custom Bricks CSS uses element IDs that require verified remapping.' );
				}
				$css[ $key ] = $page_settings[ $key ];
			}
		}

		// The page-level scenario script is reviewed and scoped to this
		// Germany draft. Only explicit known page settings are replicated.
		$css['customScriptsBodyFooter'] = $page_settings['customScriptsBodyFooter'];
		$source_thank_you = get_post_meta( $source_page_id, 'yby_thank_you_url', true );
		if ( ! is_string( $source_thank_you ) || ! preg_match( '#^/lp/[a-z0-9/-]+/$#i', $source_thank_you ) ) {
			return new WP_Error( 'clone_thank_you_unverified', 'Thank You path must be verified and stay relative on Local/Dev.' );
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
			if ( $page && 'landing_page' === $page->post_type && 'draft' === $page->post_status &&
				$id === absint( get_post_meta( $page_id, 'yby_clone_v1_project_id', true ) ) ) {
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
			if ( ! update_post_meta( $new_page_id, '_bricks_page_content_2', wp_slash( $new_elements ) ) ||
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
			$page_meta['yby_thank_you_url'] = $source_thank_you;
			$project_meta['yby_thank_you_url'] = $source_thank_you;
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
		} catch ( Throwable $error ) {
			return new WP_Error( 'clone_transaction_exception', 'Draft transaction failed and compensation was attempted. Check logs before retry.' );
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
