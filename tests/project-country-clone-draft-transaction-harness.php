<?php
/**
 * Isolated Germany draft transaction lifecycle tests. Zero real WP writes.
 */
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );
define( 'YBY_COUNTRY_CLONE_V1_DEV_DRAFT_ENABLED', true );
class WP_Error {
	private $error_code;
	public function __construct( $code, $message = '' ) { $this->error_code = $code; }
	public function get_error_code() { return $this->error_code; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class Fake_Bricks_Helpers {
 public static function generate_new_element_ids( $items ) {
  foreach ( $items as &$item ) { if ( isset( $item['id'] ) ) { $item['id'] = 'cloned-' . $item['id']; } }
  return $items;
 }
}
class_alias( 'Fake_Bricks_Helpers', 'Bricks\\Helpers' );
function wp_slash( $value ) { return $value; }
$GLOBALS['test_env'] = 'local';
$GLOBALS['test_host'] = 'localdev.ybyirrigation.com';
$GLOBALS['test_access'] = true;
$GLOBALS['test_posts'] = array(
	11 => (object) array( 'ID' => 11, 'post_type' => 'yby_project', 'post_status' => 'publish',
		'post_name' => 'tanzania', 'post_content' => '' ),
	292 => (object) array( 'ID' => 292, 'post_type' => 'landing_page', 'post_status' => 'publish',
		'post_name' => 'irrigation-solution', 'post_content' => '<section>Solutions</section>' ),
);
$GLOBALS['test_meta'] = array(
	292 => array(
		'_bricks_page_content_2' => array( array( 'id' => '1', 'name' => 'heading', 'settings' => array( 'text' => 'Solutions' ) ) ),
		'_bricks_page_settings' => array( 'customCss' => '.hero {display:block}' ),
		'yby_thank_you_url' => '/lp/thank-you-irrigation-solution/',
	),
);
$GLOBALS['test_options'] = array();
$GLOBALS['test_writes'] = array();
$GLOBALS['test_next_id'] = 900;
$GLOBALS['fail_on_insert'] = 0;
$GLOBALS['fail_meta_key'] = '';
function absint( $v ) { return abs( (int) $v ); }
function wp_get_environment_type() { return $GLOBALS['test_env']; }
function home_url( $path = '' ) { return 'https://' . $GLOBALS['test_host'] . $path; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function current_user_can( $cap, ...$args ) { return $GLOBALS['test_access']; }
function get_post( $id ) { return $GLOBALS['test_posts'][ $id ] ?? null; }
function get_post_meta( $id, $key = '', $single = false ) { return $GLOBALS['test_meta'][ $id ][ $key ] ?? ''; }
function get_posts( $args ) {
	$out = array();
	foreach ( $GLOBALS['test_posts'] as $post ) {
		if ( $post->post_type !== $args['post_type'] || ! in_array( $post->post_status, (array) $args['post_status'], true ) ) { continue; }
		if ( ( $GLOBALS['test_meta'][ $post->ID ][ $args['meta_key'] ] ?? '' ) === $args['meta_value'] ) { $out[] = $post; }
	}
	return $out;
}
function get_page_by_path( $slug, $object, $type ) {
	foreach ( $GLOBALS['test_posts'] as $post ) {
		if ( $post->post_name === $slug && $post->post_type === $type ) { return $post; }
	}
	return null;
}
function add_option( $key, $value, $deprecated = '', $autoload = 'yes' ) {
	if ( isset( $GLOBALS['test_options'][ $key ] ) ) { return false; }
	$GLOBALS['test_options'][ $key ] = $value;
	return true;
}
function delete_option( $key ) { unset( $GLOBALS['test_options'][ $key ] ); return true; }
function wp_insert_post( $args, $wp_error = false ) {
	$GLOBALS['test_writes'][] = 'insert:' . $args['post_type'];
	if ( $GLOBALS['fail_on_insert'] === count( array_filter( $GLOBALS['test_writes'], static function ( $v ) {
		return 0 === strpos( $v, 'insert:' );
	} ) ) ) { return new WP_Error( 'injected_failure' ); }
	$id = ++$GLOBALS['test_next_id'];
	$GLOBALS['test_posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $args['post_type'],
		'post_status' => $args['post_status'], 'post_title' => $args['post_title'],
		'post_name' => $args['post_name'], 'post_content' => $args['post_content'] ?? '' );
	return $id;
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['test_writes'][] = 'meta:' . $key;
	if ( $GLOBALS['fail_meta_key'] === $key ) { return false; }
	$GLOBALS['test_meta'][ $id ][ $key ] = $value;
	return true;
}
function wp_delete_post( $id, $force = false ) {
	$GLOBALS['test_writes'][] = 'delete:' . $id;
	unset( $GLOBALS['test_posts'][ $id ], $GLOBALS['test_meta'][ $id ] );
	return true;
}
class YBY_Country_Clone_Preflight {
	public static $blocked = '';
	public static function evaluate( $project, $page, $target ) {
		return array( 'source_fingerprint' => 'expected-fingerprint',
			'source_country' => 'Tanzania', 'page_country' => 'Tanzania',
			'checks' => array(
				array( 'code' => 'write_gate', 'passed' => false ),
				array( 'code' => self::$blocked ?: 'bricks_structure', 'passed' => ! self::$blocked ),
			) );
	}
}
class YBY_Germany_Clone_Transform {
	public static function build( $elements, $settings ) {
		if ( 'alert(1);' === ( $settings['customScriptsBodyFooter'] ?? '' ) ) {
			return new WP_Error( 'clone_script_contract' );
		}
		$settings['customScriptsBodyFooter'] = 'const country = "Germany";';
		return array( 'elements' => $elements, 'settings' => $settings, 'counts' => array() );
	}
}
class YBY_Germany_Clone_Bricks_Signer {
	public static function sign( $elements ) {
		if ( 'bricks_signatures' === YBY_Country_Clone_Preflight::$blocked ) { return new WP_Error( 'clone_bricks_signer_denied' ); }
		return array( 'elements' => $elements, 'signatures_verified' => 1 );
	}
}
require_once dirname( __DIR__ ) . '/inc/class-yby-country-clone-draft-service.php';
function must( $ok, $msg ) {
	if ( ! $ok ) { fwrite( STDERR, 'FAIL ' . $msg . "\n" ); exit( 1 ); }
}
function reset_test_state() {
	foreach ( array_keys( $GLOBALS['test_posts'] ) as $id ) {
		if ( $id > 798 ) { unset( $GLOBALS['test_posts'][ $id ], $GLOBALS['test_meta'][ $id ] ); }
	}
	$GLOBALS['test_writes'] = array();
	$GLOBALS['test_next_id'] = 900;
	$GLOBALS['test_options'] = array();
	$GLOBALS['fail_on_insert'] = 0;
	$GLOBALS['fail_meta_key'] = '';
	$GLOBALS['test_env'] = 'local';
	$GLOBALS['test_host'] = 'localdev.ybyirrigation.com';
	$GLOBALS['test_access'] = true;
	YBY_Country_Clone_Preflight::$blocked = '';
}
function create_test() { return YBY_Country_Clone_Draft_Service::create( 11, 292, 'expected-fingerprint' ); }
$GLOBALS['test_host'] = 'ybyirrigation.com';
$r = create_test();
must( is_wp_error( $r ) && 'clone_environment_blocked' === $r->get_error_code(), 'never create production drafts' );
must( empty( $GLOBALS['test_writes'] ), 'production blocked before writes' );

reset_test_state();
$GLOBALS['test_access'] = false;
$r = create_test();
must( is_wp_error( $r ) && 'clone_access_denied' === $r->get_error_code(), 'capability gate' );

reset_test_state();
$r = YBY_Country_Clone_Draft_Service::create( 11, 292, 'wrong-fingerprint' );
must( is_wp_error( $r ) && 'clone_source_changed' === $r->get_error_code(), 'fingerprint gate' );

reset_test_state();
YBY_Country_Clone_Preflight::$blocked = 'bricks_signatures';
$r = create_test();
must( is_wp_error( $r ) && 'clone_bricks_signer_denied' === $r->get_error_code(), 'unsigned Code blocked by native signer' );

reset_test_state();
$GLOBALS['test_meta'][292]['_bricks_page_settings']['customScriptsBodyFooter'] = 'alert(1);';
$r = create_test();
must( is_wp_error( $r ) && 'clone_script_contract' === $r->get_error_code(), 'unreviewed page-level JS blocked by strict transformer' );
unset( $GLOBALS['test_meta'][292]['_bricks_page_settings']['customScriptsBodyFooter'] );

reset_test_state();
$GLOBALS['test_meta'][292]['_bricks_page_settings']['customCss'] = '#brxe-1 { color: red; }';
$r = create_test();
must( is_wp_error( $r ) && 'clone_bricks_css_ids' === $r->get_error_code(), 'element-ID CSS blocked' );
must( empty( $GLOBALS['test_writes'] ), 'CSS guard before any insert' );
$GLOBALS['test_meta'][292]['_bricks_page_settings']['customCss'] = '.hero {display:block}';

reset_test_state();
$GLOBALS['fail_on_insert'] = 2;
$r = create_test();
must( is_wp_error( $r ) && 'clone_page_create_failed' === $r->get_error_code(), 'page creation injected failure' );
must( 2 === count( $GLOBALS['test_posts'] ), 'failed transaction rolled back new project' );
must( in_array( 'delete:901', $GLOBALS['test_writes'], true ), 'compensating project delete' );
must( empty( $GLOBALS['test_options'] ), 'transaction lock released on failure' );

reset_test_state();
$GLOBALS['fail_meta_key'] = 'yby_country';
$r = create_test();
must( is_wp_error( $r ) && 'clone_project_meta_failed' === $r->get_error_code(), 'meta failure handled' );
must( 2 === count( $GLOBALS['test_posts'] ), 'failed meta update rolled back both new drafts' );

reset_test_state();
$r = create_test();
must( is_array( $r ) && 'draft' === $r['status'] && $r['project_id'] !== $r['page_id'],
	'independent Germany draft records created in fake WP only' );
must( 2 === count( array_filter( $GLOBALS['test_posts'], static function ( $p ) {
	return $p->ID > 798;
} ) ), 'two new independent draft objects' );
must( 'Germany' === $GLOBALS['test_meta'][ $r['project_id'] ]['yby_country'], 'project country Germany' );
must( 'Germany' === $GLOBALS['test_meta'][ $r['page_id'] ]['yby_country'], 'page country Germany' );
must( 'cloned-1' === $GLOBALS['test_meta'][ $r['page_id'] ]['_bricks_page_content_2'][0]['id'],
	'new Bricks element ID generated by helper' );
must( '1' === $GLOBALS['test_meta'][292]['_bricks_page_content_2'][0]['id'],
	'source Bricks element ID remains unchanged' );
must( 'const country = "Germany";' === $GLOBALS['test_meta'][ $r['page_id'] ]['_bricks_page_settings']['customScriptsBodyFooter'],
	'only reviewed Germany JS is copied' );
must( empty( $GLOBALS['test_options'] ), 'transaction lock released after success' );
$again = create_test();
must( is_array( $again ) && $again['reused'] && $again['page_id'] === $r['page_id'],
	'idempotent repeated request returns same draft IDs' );

$GLOBALS['test_posts'][ $r['project_id'] ]->post_status = 'publish';
$reuse_published = create_test();
must( is_array( $reuse_published ) && true === $reuse_published['reused'] &&
	'publish' === $reuse_published['project_status'] &&
	$reuse_published['page_id'] === $r['page_id'],
	'published Project still reuses linked draft without duplicate writes' );
must( 2 === count( array_filter( $GLOBALS['test_posts'], static function ( $p ) {
	return $p->ID > 798;
} ) ), 'published Project created no duplicate objects' );

reset_test_state();
echo "PASS project-country-clone-draft-transaction-harness (no real WP writes)\n";
