<?php
/**
 * Read-only Country Clone V1 preflight. No post, meta, option or Bricks writes.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Country_Clone_Preflight {
	/** @var array<string,string> */
	const COUNTRIES = array( 'DE' => 'Germany' );

	/**
	 * Inspect source Project and Landing Page without changing either.
	 *
	 * @param int    $project_id Source yby_project post ID.
	 * @param int    $landing_id Source landing_page post ID.
	 * @param string $target_iso ISO-3166 alpha-2 country code.
	 * @return array<string,mixed>
	 */
	public static function evaluate( $project_id, $landing_id, $target_iso = 'DE' ) {
		$project_id = absint( $project_id );
		$landing_id = absint( $landing_id );
		$target_iso = strtoupper( sanitize_text_field( (string) $target_iso ) );
		$result = array(
			'allowed' => false,
			'project_id' => $project_id,
			'landing_id' => $landing_id,
			'target_country' => self::COUNTRIES[ $target_iso ] ?? '',
			'target_iso' => $target_iso,
			'checks' => array(),
			'bricks_keys' => array(),
			'bricks_elements' => 0,
			'bricks_code_elements' => 0,
			'country_mentions' => 0,
			'source_country' => '',
			'page_country' => '',
			'source_fingerprint' => '',
			'transform_plan' => array(),
			'page_script_country_mentions' => 0,
			'page_script_scenario_mentions' => 0,
			'country_selector_groups' => 0,
			'target_country_buttons' => 0,
			'write_enabled' => false,
		);

		if ( ! current_user_can( 'andy_core_settings_manage' ) ||
			! current_user_can( 'edit_post', $project_id ) ||
			! current_user_can( 'edit_post', $landing_id ) ||
			! current_user_can( 'edit_pages' ) ) {
			self::check( $result, false, 'permission', 'Missing Project Studio / source post edit permission.' );
			return $result;
		}

		if ( ! isset( self::COUNTRIES[ $target_iso ] ) ) {
			self::check( $result, false, 'target_country', 'Country Clone V1 only supports Germany (DE).' );
			return $result;
		}

		$project = $project_id ? get_post( $project_id ) : null;
		$page = $landing_id ? get_post( $landing_id ) : null;
		$project_ok = $project && 'yby_project' === $project->post_type;
		$page_ok = $page && 'landing_page' === $page->post_type;
		self::check( $result, $project_ok, 'source_project', 'Source must be an existing yby_project.' );
		self::check( $result, $page_ok, 'source_landing', 'Source must be an existing landing_page; never use normal page or yby_landing_page.' );
		if ( ! $project_ok || ! $page_ok ) { return $result; }

		$result['source_country'] = YBY_Project_CPT::get_field_value( $project_id, array( 'country', 'yby_country' ) );
		$page_country = get_post_meta( $landing_id, 'yby_country', true );
		$result['page_country'] = is_scalar( $page_country ) ? trim( (string) $page_country ) : '';
		self::check( $result, '' !== $result['source_country'], 'source_country', 'Source Project Country is empty. Do not infer from its title or the site global default.' );
		self::check( $result, '' !== $result['page_country'], 'page_country', 'Source Landing Page raw yby_country is empty. Frontend fallback does not prove source meta.' );
		if ( '' !== $result['source_country'] && '' !== $result['page_country'] ) {
			self::check( $result, 0 === strcasecmp( $result['source_country'], $result['page_country'] ), 'country_consistency', 'Project and page Country must agree.' );
		}
		self::check( $result, $project_id !== $landing_id, 'independent_ids', 'Project and Landing Page may not share an ID.' );
		self::check( $result, 'trash' !== $project->post_status && 'trash' !== $page->post_status, 'source_status', 'Source must not be trashed.' );

		$meta = get_post_meta( $landing_id );
		$meta = is_array( $meta ) ? $meta : array();
		$bricks_keys = array_values( array_filter( array_keys( $meta ), static function ( $key ) {
			return 0 === strpos( (string) $key, '_bricks_' );
		} ) );
		sort( $bricks_keys );
		$result['bricks_keys'] = $bricks_keys;
		$source_data = array();
		foreach ( array( '_bricks_page_content_2', '_bricks_page_content' ) as $key ) {
			if ( array_key_exists( $key, $meta ) ) {
				$source_data = get_post_meta( $landing_id, $key, true );
				break;
			}
		}
		$source_data = is_array( $source_data ) ? $source_data : array();
		// Page-level Custom Code is not part of _bricks_page_content_2.
		// Inspect both layers. Never expose the script body in the admin UI.
		$page_settings = get_post_meta( $landing_id, '_bricks_page_settings', true );
		$page_settings = is_array( $page_settings ) ? $page_settings : array();
		$page_script = isset( $page_settings['customScriptsBodyFooter'] )
			&& is_string( $page_settings['customScriptsBodyFooter'] )
			? $page_settings['customScriptsBodyFooter'] : '';
		$source_country = '' !== $result['source_country']
			? $result['source_country'] : $result['page_country'];
		$result['page_script_country_mentions'] = '' !== $source_country
			? substr_count( strtolower( $page_script ), strtolower( $source_country ) ) : 0;
		$result['page_script_scenario_mentions'] = substr_count( $page_script, 'PROJECT_SCENARIOS' );
		$target_button_pattern = '~data-yby-country=["\\x27]' . preg_quote( self::COUNTRIES[ $target_iso ], '~' ) . '["\\x27]~i';
		foreach ( $source_data as $node ) {
			if ( ! is_array( $node ) || 'code' !== ( $node['name'] ?? '' ) ||
				! is_string( $node['settings']['code'] ?? null ) ) { continue; }
			$source_code = $node['settings']['code'];
			if ( false !== strpos( $source_code, 'data-yby-country=' ) ) {
				++$result['country_selector_groups'];
				$result['target_country_buttons'] += preg_match_all( $target_button_pattern, $source_code );
			}
		}
		$result['bricks_elements'] = count( $source_data );
		$result['bricks_code_elements'] = self::count_code_elements( $source_data );
		$result['transform_plan'] = YBY_Country_Transform_Registry::preview(
			$source_data, '' !== $result['source_country'] ? $result['source_country'] : $result['page_country'] );
		$result['country_mentions'] = self::count_country_mentions( $source_data,
			'' !== $result['source_country'] ? $result['source_country'] : $result['page_country'] );
		self::check( $result, ! empty( $source_data ), 'bricks_structure', 'No supported Bricks content meta structure verified. Stop instead of guessing keys.' );
		self::check( $result, 0 === $result['page_script_country_mentions'], 'page_script_country',
			'Page-level Bricks customScriptsBodyFooter still references source Country. Inspect and map independently from Code elements.' );
		self::check( $result, 0 === $result['page_script_scenario_mentions'], 'page_script_scenarios',
			'Page-level PROJECT_SCENARIOS must be reviewed for target-country fallback and truthful case examples.' );
		self::check( $result, 0 === $result['country_selector_groups'] ||
			$result['target_country_buttons'] >= $result['country_selector_groups'], 'target_country_buttons',
			'Each country selector group must explicitly support target Germany; do not silently relabel Tanzania.' );
		self::check( $result, 0 === $result['bricks_code_elements'], 'bricks_signatures', 'Bricks Code elements require authorized human review and native valid signing. No copied/stale signatures.' );
		self::check( $result, 0 === ( $result['transform_plan']['unclassified_code_refs'] ?? 0 ), 'unclassified_code_refs', 'Unrecognized Country references require manual classification before transform.' );
		self::check( $result, 0 === $result['country_mentions'], 'country_nodes', 'Country-specific Bricks content requires reviewed node-by-node transform; no global string replacement.' );
		self::check( $result, false, 'write_gate', 'Germany draft creation is disabled in M1 preflight until M0 private schema and authorized Bricks signing are verified.' );

		$result['source_fingerprint'] = hash( 'sha256', implode( '|', array(
			$project_id, $project->post_modified_gmt, $landing_id, $page->post_modified_gmt,
			$result['source_country'], $result['page_country'],
			serialize( $source_data ),
			serialize( $page_settings ),
			(string) ( $page->post_content ?? '' ),
			serialize( get_post_meta( $project_id ) ),
			serialize( $meta ),
			(string) ( $project->post_status ?? '' ),
			(string) ( $page->post_status ?? '' ),
		) ) );
		$result['allowed'] = 0 === count( array_filter( $result['checks'], static function ( $check ) {
			return ! $check['passed'];
		} ) );
		return $result;
	}

	/** @param array<string,mixed> $result Mutable diagnostic result. */
	private static function check( &$result, $passed, $code, $message ) {
		$result['checks'][] = array( 'passed' => (bool) $passed, 'code' => $code, 'message' => $message );
	}

	/** @param mixed $nodes Bricks element array. @return int */
	private static function count_code_elements( $nodes ) {
		if ( ! is_array( $nodes ) ) { return 0; }
		$count = 0;
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) { continue; }
			if ( isset( $node['name'] ) && in_array( (string) $node['name'], array( 'code', 'svg' ), true ) ) { ++$count; }
			if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
				$count += self::count_code_elements( $node['children'] );
			}
		}
		return $count;
	}

	/** @param mixed $nodes Bricks elements. @param string $country Raw source country. @return int */
	private static function count_country_mentions( $nodes, $country ) {
		if ( '' === $country || ! is_array( $nodes ) ) { return 0; }
		$json = wp_json_encode( $nodes );
		return is_string( $json ) ? substr_count( strtolower( $json ), strtolower( $country ) ) : 0;
	}
}
