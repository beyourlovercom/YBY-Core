<?php
/** Focused C7B ERP Content publishing contract harness. */
define( 'ABSPATH', __DIR__ );

$posts = array();
$post_meta = array();
$next_post_id = 0;
$fail_meta_readback = false;
$write_counts = array( 'insert' => 0, 'update' => 0, 'delete' => 0, 'meta' => 0 );

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function absint( $value ) { return abs( (int) $value ); }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function wp_strip_all_tags( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_kses_post( $html ) {
	$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $html );
	return strip_tags( $html, '<h2><h3><h4><h5><h6><p><ul><ol><li><strong><em><a><blockquote>' );
}
function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	return trim( $value, '-' );
}
function get_posts( $args ) {
	global $posts, $post_meta;
	$ids = array();
	foreach ( $posts as $id => $post ) {
		$match = true;
		foreach ( (array) ( $args['meta_query'] ?? array() ) as $condition ) {
			if ( ! is_array( $condition ) || ! isset( $condition['key'] ) ) { continue; }
			if ( (string) ( $post_meta[ $id ][ $condition['key'] ] ?? '' ) !== (string) ( $condition['value'] ?? '' ) ) {
				$match = false;
				break;
			}
		}
		if ( $match ) { $ids[] = (int) $id; }
	}
	return array_slice( $ids, 0, (int) ( $args['posts_per_page'] ?? 3 ) );
}
function wp_insert_post( $postarr, $wp_error = false ) {
	global $posts, $next_post_id, $write_counts;
	$id = ++$next_post_id;
	$posts[ $id ] = (object) array(
		'ID' => $id,
		'post_type' => (string) $postarr['post_type'],
		'post_status' => (string) $postarr['post_status'],
		'post_title' => (string) $postarr['post_title'],
		'post_content' => (string) $postarr['post_content'],
		'post_name' => (string) ( $postarr['post_name'] ?? '' ),
	);
	++$write_counts['insert'];
	return $id;
}
function wp_update_post( $postarr, $wp_error = false ) {
	global $posts, $write_counts;
	$id = (int) ( $postarr['ID'] ?? 0 );
	if ( ! isset( $posts[ $id ] ) ) { return new WP_Error( 'missing', 'Missing post.' ); }
	foreach ( array( 'post_type', 'post_status', 'post_title', 'post_content', 'post_name' ) as $key ) {
		if ( array_key_exists( $key, $postarr ) ) { $posts[ $id ]->{$key} = (string) $postarr[ $key ]; }
	}
	++$write_counts['update'];
	return $id;
}
function update_post_meta( $post_id, $key, $value ) {
	global $post_meta, $write_counts;
	$post_meta[ (int) $post_id ][ (string) $key ] = $value;
	++$write_counts['meta'];
	return true;
}
function get_post_meta( $post_id, $key, $single = false ) {
	global $post_meta, $fail_meta_readback;
	if ( $fail_meta_readback && '_yby_erp_preview_hash' === (string) $key ) {
		return 'forced-mismatch';
	}
	return $post_meta[ (int) $post_id ][ (string) $key ] ?? '';
}
function delete_post_meta( $post_id, $key ) {
	global $post_meta, $write_counts;
	unset( $post_meta[ (int) $post_id ][ (string) $key ] );
	++$write_counts['meta'];
	return true;
}
function wp_delete_post( $post_id, $force_delete = false ) {
	global $posts, $post_meta, $write_counts;
	unset( $posts[ (int) $post_id ], $post_meta[ (int) $post_id ] );
	++$write_counts['delete'];
	return true;
}
function get_post( $post_id ) {
	global $posts;
	return $posts[ (int) $post_id ] ?? null;
}
function get_permalink( $post_id ) {
	$post = get_post( $post_id );
	return $post ? 'https://example.test/blog/' . $post->post_name . '/' : false;
}

require_once dirname( __DIR__ ) . '/inc/class-yby-content-erp-contract.php';

function content_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function content_payload() {
	return array(
		'erp_article_id' => 77,
		'layout_snapshot_id' => 9,
		'preview_hash' => str_repeat( 'a', 64 ),
		'target_site' => 'example.test',
		'content_type' => 'blog',
		'title' => 'Irrigation Guide',
		'html' => '<h2>Selection</h2><script>alert(1)</script><p>Choose from verified project requirements.</p>',
		'canonical_path' => '/blog/irrigation-guide/',
		'seo' => array( 'primary_keyword' => 'irrigation guide', 'search_intent' => 'informational', 'indexing' => 'noindex' ),
		'requested_status' => 'draft',
	);
}

$input = YBY_Content_ERP_Contract::validate( content_payload(), 'ybyirrigation.com' );
content_assert( is_array( $input ), 'Valid article payload must pass.' );
content_assert( false === strpos( $input['html'], '<script' ), 'Unsafe script markup must be removed before provider write.' );
content_assert( 'post' === $input['post_type'] && 'draft' === $input['requested_status'], 'V1 must remain bounded to WordPress posts and explicit draft/publish states.' );
content_assert( 'noindex' === $input['seo']['indexing'], 'Draft content must carry explicit noindex intent.' );

$writes_before = $write_counts;
$preview = YBY_Content_ERP_Contract::preview( $input );
content_assert( false === $preview['write_performed'], 'Preview contract must be explicitly read-only.' );
content_assert( $writes_before === $write_counts, 'Preview must perform zero WordPress writes.' );
content_assert( 'content-publish-v1' === $preview['resource_contract_version'], 'Preview must expose the resource contract version.' );

$bad_site = content_payload();
$bad_site['target_site'] = 'wrong.example.test';
$bad_site = YBY_Content_ERP_Contract::validate( $bad_site, 'ybyirrigation.com' );
content_assert( is_wp_error( $bad_site ) && 'TARGET_SITE_MISMATCH' === $bad_site->get_error_code(), 'Target-site mismatch must fail closed.' );

$product = content_payload();
$product['content_type'] = 'product';
$product = YBY_Content_ERP_Contract::validate( $product, 'ybyirrigation.com' );
content_assert( is_wp_error( $product ) && 'CONTENT_TYPE_UNSUPPORTED' === $product->get_error_code(), 'Product content must not enter the article publishing endpoint.' );

$unsafe_path = content_payload();
$unsafe_path['canonical_path'] = 'https://evil.example/path';
$unsafe_path = YBY_Content_ERP_Contract::validate( $unsafe_path, 'ybyirrigation.com' );
content_assert( is_wp_error( $unsafe_path ) && 'VALIDATION_FAILED' === $unsafe_path->get_error_code(), 'Canonical path must be a local path only.' );

$bad_indexing = content_payload();
$bad_indexing['seo']['indexing'] = 'index';
$bad_indexing = YBY_Content_ERP_Contract::validate( $bad_indexing, 'ybyirrigation.com' );
content_assert( is_wp_error( $bad_indexing ) && 'INDEXING_STATUS_MISMATCH' === $bad_indexing->get_error_code(), 'Draft with index intent must fail closed.' );

$first = YBY_Content_ERP_Contract::publish( $input );
content_assert( is_array( $first ) && 1 === $first['post_id'] && true === $first['created'], 'First publish must create exactly one bound WordPress post.' );
content_assert( 1 === $write_counts['insert'] && 0 === $write_counts['update'], 'First publish must create rather than update.' );
content_assert( '77' === (string) get_post_meta( 1, '_yby_erp_article_id', true ), 'ERP article binding must be persisted.' );
content_assert( str_repeat( 'a', 64 ) === get_post_meta( 1, '_yby_erp_preview_hash', true ), 'Preview hash must be persisted for audit.' );
content_assert( true === $first['canonical_path_match'], 'Read-back permalink must report canonical path agreement.' );
content_assert( 'noindex' === $first['indexing'], 'Draft provider response must confirm noindex.' );
content_assert( 'noindex' === get_post_meta( 1, '_yby_content_indexing', true ), 'ERP indexing audit metadata must persist noindex.' );
content_assert( in_array( 'noindex', (array) get_post_meta( 1, 'rank_math_robots', true ), true ), 'Draft must persist Rank Math noindex.' );

$second_payload = content_payload();
$second_payload['layout_snapshot_id'] = 10;
$second_payload['preview_hash'] = str_repeat( 'b', 64 );
$second_payload['title'] = 'Irrigation Guide Revised';
$second_payload['requested_status'] = 'publish';
$second_payload['seo']['indexing'] = 'index';
$second = YBY_Content_ERP_Contract::validate( $second_payload, 'ybyirrigation.com' );
$second = YBY_Content_ERP_Contract::publish( $second );
content_assert( is_array( $second ) && 1 === $second['post_id'] && false === $second['created'], 'Later layout must update the same bound WordPress post.' );
content_assert( 1 === $write_counts['insert'] && 1 === $write_counts['update'] && 1 === count( $posts ), 'Re-publish must never create a duplicate article.' );
content_assert( 'publish' === get_post( 1 )->post_status && 'Irrigation Guide Revised' === get_post( 1 )->post_title, 'Update must read back the requested status and title.' );
content_assert( 'index' === $second['indexing'], 'Published provider response must confirm index.' );
content_assert( 'index' === get_post_meta( 1, '_yby_content_indexing', true ), 'ERP indexing audit metadata must persist index.' );
content_assert( ! in_array( 'noindex', (array) get_post_meta( 1, 'rank_math_robots', true ), true ), 'Publish must clear the Rank Math noindex override.' );

$failed_payload = content_payload();
$failed_payload['erp_article_id'] = 88;
$failed_payload['layout_snapshot_id'] = 11;
$failed_payload['preview_hash'] = str_repeat( 'c', 64 );
$failed_payload['canonical_path'] = '/blog/readback-failure/';
$fail_meta_readback = true;
$failed = YBY_Content_ERP_Contract::publish( YBY_Content_ERP_Contract::validate( $failed_payload, 'ybyirrigation.com' ) );
$fail_meta_readback = false;
content_assert( is_wp_error( $failed ) && 'PROVIDER_SYNC_FAILED' === $failed->get_error_code(), 'Binding metadata mismatch must fail provider sync.' );
content_assert( 1 === $write_counts['delete'] && 1 === count( $posts ), 'Newly created half-bound post must be safely compensated.' );

$posts[2] = clone $posts[1];
$posts[2]->ID = 2;
$post_meta[2] = $post_meta[1];
$conflict = YBY_Content_ERP_Contract::publish( YBY_Content_ERP_Contract::validate( $second_payload, 'ybyirrigation.com' ) );
content_assert( is_wp_error( $conflict ) && 'CONTENT_BINDING_CONFLICT' === $conflict->get_error_code(), 'Ambiguous multiple bindings must fail closed.' );

$connector_source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector.php' );
content_assert( false !== strpos( $connector_source, "'/content/preview'" ) && false !== strpos( $connector_source, "'/content/publish'" ), 'Connector must register the two bounded Content routes.' );
content_assert( false !== strpos( $connector_source, "'content.publish'" ) && false !== strpos( $connector_source, 'YBY_Connector_Idempotency::begin' ), 'Content publish must reuse durable Connector idempotency.' );
content_assert( false !== strpos( $connector_source, "'post_id'" ), 'Connector audit must allow WordPress post identity.' );

echo "Content ERP publishing harness passed.\n";
