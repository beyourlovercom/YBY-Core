<?php
/**
 * Country Clone V1 Germany read-only preflight regression harness.
 *
 * Run: php tests/project-country-clone-preflight-harness.php
 */
define( 'ABSPATH', __DIR__ );
$GLOBALS['clone_posts'] = array(
	11 => (object) array( 'ID' => 11, 'post_type' => 'yby_project', 'post_status' => 'publish', 'post_modified_gmt' => '2026-10-14 12:00:00' ),
	292 => (object) array( 'ID' => 292, 'post_type' => 'landing_page', 'post_status' => 'publish', 'post_modified_gmt' => '2026-10-14 12:00:00' ),
	798 => (object) array( 'ID' => 798, 'post_type' => 'landing_page', 'post_status' => 'draft', 'post_modified_gmt' => '2026-10-14 12:00:00' ),
);
$GLOBALS['clone_meta'] = array(
	11 => array( 'yby_country' => 'Tanzania' ),
	292 => array(
		'yby_country' => 'Tanzania',
		'_bricks_page_content_2' => array(
			array( 'id' => 'title', 'name' => 'heading', 'settings' => array( 'text' => 'Engineering' ) ),
			array( 'id' => 'form', 'name' => 'form', 'settings' => array( 'field' => 'email' ) ),
		),
	),
	798 => array( 'yby_country' => 'United States' ),
);
$GLOBALS['clone_permission'] = true;
$GLOBALS['clone_writes'] = 0;
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function current_user_can( $cap, ...$args ) { return $GLOBALS['clone_permission']; }
function get_post( $id ) { return $GLOBALS['clone_posts'][ $id ] ?? null; }
function get_post_meta( $id, $key = '', $single = false ) {
	$data = $GLOBALS['clone_meta'][ $id ] ?? array();
	if ( '' !== $key ) { return $data[ $key ] ?? ''; }
	$all = array();
	foreach ( $data as $k => $v ) { $all[ $k ] = array( serialize( $v ) ); }
	return $all;
}
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_insert_post( $args ) { ++$GLOBALS['clone_writes']; throw new Exception( 'Unexpected write.' ); }
function update_post_meta( $id, $key, $value ) { ++$GLOBALS['clone_writes']; throw new Exception( 'Unexpected write.' ); }
class YBY_Project_CPT {
	public static function get_field_value( $id, $aliases ) {
		foreach ( $aliases as $key ) {
			$value = get_post_meta( $id, $key, true );
			if ( is_scalar( $value ) && '' !== trim( $value ) ) { return (string) $value; }
		}
		return '';
	}
}
require_once dirname( __DIR__ ) . '/inc/class-yby-country-transform-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-country-clone-preflight.php';
function verify( $passed, $message ) {
	if ( ! $passed ) { fwrite( STDERR, 'FAIL ' . $message . "\n" ); exit( 1 ); }
}
function code_exists( $result, $code, $passed = null ) {
	foreach ( $result['checks'] as $check ) {
		if ( $check['code'] === $code && ( null === $passed || $check['passed'] === $passed ) ) { return true; }
	}
	return false;
}
$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( false === $r['allowed'] && false === $r['write_enabled'], 'writes fail closed regardless of clean source' );
verify( code_exists( $r, 'source_project', true ), 'source project recognized' );
verify( code_exists( $r, 'bricks_structure', true ), 'Bricks content recognized' );
verify( code_exists( $r, 'write_gate', false ), 'write gate remains closed' );
verify( 2 === $r['bricks_elements'] && 0 === $r['bricks_code_elements'], 'element counts' );
verify( 64 === strlen( $r['source_fingerprint'] ), 'source fingerprint exists' );

$GLOBALS['clone_meta'][11]['yby_country'] = '';
$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( code_exists( $r, 'source_country', false ), 'empty Project country cannot use title/fallback' );
$GLOBALS['clone_meta'][11]['yby_country'] = 'Tanzania';

$GLOBALS['clone_meta'][292]['yby_country'] = 'Kenya';
$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( code_exists( $r, 'country_consistency', false ), 'source country mismatch blocked' );
$GLOBALS['clone_meta'][292]['yby_country'] = 'Tanzania';

$GLOBALS['clone_meta'][292]['_bricks_page_content_2'][] = array(
	'id' => 'code', 'name' => 'code', 'settings' => array( 'code' => 'const defaultCountry="Tanzania";' ),
);
$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( 1 === $r['bricks_code_elements'], 'Bricks Code counted' );
verify( code_exists( $r, 'bricks_signatures', false ), 'Bricks Code requires legal signing' );
verify( code_exists( $r, 'country_nodes', false ), 'country JS replacement not allowed' );
$GLOBALS['clone_meta'][292]['_bricks_page_content_2'] = array();

$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( code_exists( $r, 'bricks_structure', false ), 'missing Bricks content blocked' );

$r = YBY_Country_Clone_Preflight::evaluate( 11, 798, 'DE' );
verify( code_exists( $r, 'page_country', true ), 'US reference visible for comparison' );
verify( code_exists( $r, 'country_consistency', false ), 'US 798 not quietly treated as Tanzania' );

$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'FR' );
verify( code_exists( $r, 'target_country', false ), 'unsupported country rejected' );

$GLOBALS['clone_permission'] = false;
$r = YBY_Country_Clone_Preflight::evaluate( 11, 292, 'DE' );
verify( code_exists( $r, 'permission', false ), 'permission fail-closed' );

verify( 0 === $GLOBALS['clone_writes'], 'ZERO WordPress writes' );
echo "PASS project-country-clone-preflight-harness (10 scenarios, zero writes)\n";
