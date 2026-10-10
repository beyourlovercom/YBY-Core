<?php
/**
 * Newsletter M2.5 — live WordPress REST HMAC handler + disposable MySQL.
 * GitHub Actions ephemeral WP only; direct synthetic SQL seed, never actual email.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 8 ); }
if ( ! defined( 'YBY_NEWSLETTER_SCHEMA_APPLY' ) ) { define( 'YBY_NEWSLETTER_SCHEMA_APPLY', true ); }
global $wpdb;
function m2_mysql_assert( $condition, $label ) {
    if ( ! $condition ) { fwrite( STDERR, 'M2_MYSQL_FAIL: ' . $label . "\n" ); exit( 1 ); }
    echo 'M2_MYSQL_PASS ' . $label . "\n";
}
m2_mysql_assert( YBY_Newsletter_Store::exists() || YBY_Newsletter_Store::install_schema(), 'Explicit isolated schema exists' );
$site = YBY_Newsletter_Store::site_key();
m2_mysql_assert( 'newsletter-validation.test' === $site, 'Isolated WP fixture domain only' );
$table = YBY_Newsletter_Store::table_name();
$when = '2035-10-10 10:10:10';
foreach ( range( 1, 111 ) as $i ) {
    $email = 'synthetic-native-' . str_pad( (string) $i, 3, '0', STR_PAD_LEFT ) . '@example.test';
    $status = array( 'pending', 'subscribed', 'unsubscribed', 'suppressed' )[ $i % 4 ];
    $row = array(
        'email' => $email,
        'email_hash' => hash( 'sha256', $email ),
        'external_subscription_id' => YBY_Newsletter_Consent::external_id( $site, $email ),
        'site_key' => $site,
        'status' => $status,
        'consent_source' => 'andy_core_newsletter_form',
        'consent_policy' => 'newsletter-v1',
        'form_source' => 'synthetic-m2',
        'source_page' => '/synthetic/',
        'locale' => 'en-US',
        'submitted_at' => $when,
        'subscribed_at' => 'subscribed' === $status ? $when : null,
        'unsubscribed_at' => in_array( $status, array( 'unsubscribed', 'suppressed' ), true ) ? $when : null,
        'status_updated_at' => $when,
        'confirm_hash' => null,
        'confirm_expires_at' => null,
        'unsubscribe_hash' => hash( 'sha256', 'synthetic-unsubscribe-' . $i ),
        'created_at' => $when,
        'updated_at' => $when,
    );
    m2_mysql_assert( 1 === $wpdb->insert( $table, $row ), 'Seed anonymous synthetic row ' . $i );
}

m2_mysql_assert( YBY_Connector::save( array( 'enabled' => true, 'connection_key' => 'beyourlover.com', 'key_id' => 'ci-only' ) ),
    'Synthetic signed-connector test options' );
$secret = YBY_Connector::generate_secret();
m2_mysql_assert( is_string( $secret ) && strlen( $secret ) >= 32, 'Test HMAC key generated and stored encrypted' );
$routes = rest_get_server()->get_routes();
m2_mysql_assert( isset( $routes['/andy-core/v1/erp/snapshot/subscribers'] ), 'Actual authenticated subscriber REST route registered' );

$old_uri = $_SERVER['REQUEST_URI'] ?? null;
$old_https = $_SERVER['HTTPS'] ?? null;
$_SERVER['HTTPS'] = 'on'; // CLI-only HTTPS fixture, no public HTTP route or remote traffic.
$counter = 0;
function m2_signed_request( $params, $secret, $signature_override = null, $same_nonce = null ) {
    global $counter;
    $counter++;
    $url = '/wp-json/andy-core/v1/erp/snapshot/subscribers';
    $path = $url . ( $params ? '?' . http_build_query( $params ) : '' );
    $_SERVER['REQUEST_URI'] = $path;
    $req = new WP_REST_Request( 'GET', '/andy-core/v1/erp/snapshot/subscribers' );
    $req->set_query_params( $params );
    $timestamp = (string) time();
    $nonce = $same_nonce ?: 'ci-m2-nonce-' . str_pad( (string) $counter, 10, '0', STR_PAD_LEFT );
    $sig = YBY_Connector::sign( 'GET', $path, $timestamp, $nonce, 'beyourlover.com', '', '', $secret );
    foreach ( array(
        'X-YBY-Connection-Key' => 'beyourlover.com',
        'X-YBY-Key-Id' => 'ci-only',
        'X-YBY-Timestamp' => $timestamp,
        'X-YBY-Nonce' => $nonce,
        'X-YBY-Signature-Version' => 'v1',
        'X-YBY-Signature' => null === $signature_override ? $sig : $signature_override,
    ) as $k => $v ) { $req->set_header( $k, $v ); }
    return YBY_Connector::dispatch_snapshot( $req );
}
$base = array( 'source' => 'andy_core_newsletter', 'limit' => 100, 'updated_after' => '2035-10-10T10:10:10Z' );
$first = m2_signed_request( $base, $secret );
m2_mysql_assert( is_array( $first ) && true === $first['ok'] && count( $first['data']['items'] ) === 100,
    'Full signed native page 100/111, same-second boundary inclusive' );
m2_mysql_assert( $first['data']['source_site'] === $site, 'Source site from WP home_url, not ERP connection key' );
m2_mysql_assert( is_string( $first['data']['next_cursor'] ), 'Opaque signed cursor returned' );
$fields = array_keys( $first['data']['items'][0] );
m2_mysql_assert( ! in_array( 'confirm_hash', $fields, true ) && ! in_array( 'unsubscribe_hash', $fields, true ) &&
    ! in_array( 'consent_policy', $fields, true ), 'No private consent credential or policy internals exposed' );
$next = m2_signed_request( array_merge( $base, array( 'cursor' => $first['data']['next_cursor'] ) ), $secret );
m2_mysql_assert( is_array( $next ) && count( $next['data']['items'] ) === 11 && null === $next['data']['next_cursor'],
    'Tie-break SQL correctly returns remaining 11 equal-second rows' );
$ids = array_merge( array_column( $first['data']['items'], 'external_subscription_id' ),
    array_column( $next['data']['items'], 'external_subscription_id' ) );
m2_mysql_assert( count( $ids ) === count( array_unique( $ids ) ), 'No duplicate/collision between HMAC pages' );
$replayed = m2_signed_request( $base, $secret );
m2_mysql_assert( $replayed['data']['items'] === $first['data']['items'], 'Read-only snapshot repeat is idempotent' );
$old_style = m2_signed_request( array( 'limit' => 100 ), $secret );
m2_mysql_assert( $old_style instanceof WP_REST_Response && 503 === $old_style->get_status() &&
    ( $old_style->get_data()['code'] ?? '' ) === 'PROVIDER_UNAVAILABLE',
    'Legacy Elementor absent table returns existing 503 instead of native list' );
$bad_source = m2_signed_request( array( 'source' => 'untrusted', 'limit' => 1 ), $secret );
m2_mysql_assert( $bad_source instanceof WP_REST_Response && 400 === $bad_source->get_status(), 'Invalid source denied' );
$bad_mac = m2_signed_request( $base, $secret, 'f00bad' );
m2_mysql_assert( $bad_mac instanceof WP_REST_Response && 401 === $bad_mac->get_status(), 'Invalid signature denied before data' );
$nonce = 'ci-m2-replay-123456789';
$valid = m2_signed_request( $base, $secret, null, $nonce );
$replay = m2_signed_request( $base, $secret, null, $nonce );
m2_mysql_assert( is_array( $valid ) && $replay instanceof WP_REST_Response &&
    409 === $replay->get_status(), 'HMAC nonce replay rejected' );
$cross_window = m2_signed_request( array_merge( $base, array(
    'updated_after' => '2035-10-10T10:10:11Z', 'cursor' => $first['data']['next_cursor'],
) ), $secret );
m2_mysql_assert( $cross_window instanceof WP_REST_Response && 400 === $cross_window->get_status(),
    'HMAC-authenticated cursor cannot switch updated_after window' );

/**
 * M2.8 scoped-key integration in actual disposable WordPress/MySQL.
 * The old general Connector remains enabled in this fixture: the scoped key
 * must be denied for every non-native route even when general Connector is ON.
 */
$readonly_secret = YBY_Connector_Newsletter_Readonly::provision(
	'm2-only-ci.example.test', 'ci-newsletter-only', 600
);
m2_mysql_assert( is_string( $readonly_secret ) && strlen( $readonly_secret ) >= 64,
	'M2.8 scoped identity provisioned only in disposable WP-CLI' );
m2_mysql_assert( get_option( YBY_Connector_Newsletter_Readonly::OPTION )['expires_at'] > time(),
	'M2.8 scoped identity has finite valid expiry' );

function m28_scoped_request( $path, $secret, $method = 'GET', $route = '/andy-core/v1/erp/snapshot/subscribers', $nonce = null ) {
	static $serial = 0;
	++$serial;
	$_SERVER['REQUEST_URI'] = $path;
	$query = parse_url( $path, PHP_URL_QUERY );
	$params = array();
	if ( is_string( $query ) ) { parse_str( $query, $params ); }
	$req = new WP_REST_Request( $method, $route );
	$req->set_query_params( $params );
	$ts = (string) time();
	$nonce = $nonce ?: 'ci-m28-once-' . str_pad( (string) $serial, 10, '0', STR_PAD_LEFT );
	$headers = array(
		'X-YBY-Key-Id' => 'ci-newsletter-only',
		'X-YBY-Connection-Key' => 'm2-only-ci.example.test',
		'X-YBY-Timestamp' => $ts,
		'X-YBY-Nonce' => $nonce,
		'X-YBY-Signature-Version' => 'v1',
		'X-YBY-Signature' => YBY_Connector::sign(
			$method, $path, $ts, $nonce, 'm2-only-ci.example.test', '', '', $secret
		),
	);
	foreach ( $headers as $key => $value ) { $req->set_header( $key, $value ); }
	return $req;
}
$scoped_path = '/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1';
$scoped_positive = YBY_Connector::dispatch_snapshot( m28_scoped_request( $scoped_path, $readonly_secret ) );
m2_mysql_assert( is_array( $scoped_positive ) && true === ( $scoped_positive['ok'] ?? false ) &&
	count( $scoped_positive['data']['items'] ?? array() ) === 1 &&
	'newsletter-validation.test' === ( $scoped_positive['data']['source_site'] ?? '' ),
	'M2.8 real WordPress REST handler accepts scoped native-only HMAC GET' );
$scoped_replay = m28_scoped_request( $scoped_path, $readonly_secret, 'GET',
	'/andy-core/v1/erp/snapshot/subscribers', 'ci-m28-repeat-123456789' );
$scoped_first = YBY_Connector::dispatch_snapshot( $scoped_replay );
$scoped_again = YBY_Connector::dispatch_snapshot( $scoped_replay );
m2_mysql_assert( is_array( $scoped_first ) && $scoped_again instanceof WP_REST_Response &&
	409 === $scoped_again->get_status(), 'M2.8 replay is denied by real REST dispatch' );

$legacy = YBY_Connector::dispatch_snapshot( m28_scoped_request(
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?limit=1', $readonly_secret
) );
m2_mysql_assert( $legacy instanceof WP_REST_Response && 403 === $legacy->get_status(),
	'M2.8 scoped HMAC cannot read legacy Elementor source' );
$other = YBY_Connector::dispatch_snapshot( m28_scoped_request(
	'/wp-json/andy-core/v1/erp/snapshot/affiliates?limit=1', $readonly_secret,
	'GET', '/andy-core/v1/erp/snapshot/affiliates'
) );
m2_mysql_assert( $other instanceof WP_REST_Response && 403 === $other->get_status(),
	'M2.8 scoped HMAC cannot read unrelated snapshots' );
$write = YBY_Connector::dispatch_content_publish( m28_scoped_request(
	'/wp-json/andy-core/v1/erp/content/publish', $readonly_secret,
	'POST', '/andy-core/v1/erp/content/publish'
) );
m2_mysql_assert( $write instanceof WP_REST_Response && 403 === $write->get_status(),
	'M2.8 scoped HMAC cannot call Content Publish even with general Connector active' );

$scoped_options = get_option( YBY_Connector_Newsletter_Readonly::OPTION );
$scoped_options['expires_at'] = time() - 1;
update_option( YBY_Connector_Newsletter_Readonly::OPTION, $scoped_options, false );
$expired = YBY_Connector::dispatch_snapshot( m28_scoped_request( $scoped_path, $readonly_secret ) );
m2_mysql_assert( $expired instanceof WP_REST_Response && 401 === $expired->get_status(),
	'M2.8 expired scoped HMAC is denied in real WordPress REST dispatch' );
m2_mysql_assert( true === YBY_Connector_Newsletter_Readonly::revoke() &&
	false === get_option( YBY_Connector_Newsletter_Readonly::OPTION, false ) &&
	false === get_option( YBY_Connector_Newsletter_Readonly::SECRET_OPTION, false ),
	'M2.8 scoped key and encrypted secret revoked in disposable WP' );

if ( null === $old_uri ) { unset( $_SERVER['REQUEST_URI'] ); } else { $_SERVER['REQUEST_URI'] = $old_uri; }
if ( null === $old_https ) { unset( $_SERVER['HTTPS'] ); } else { $_SERVER['HTTPS'] = $old_https; }
echo "NEWSLETTER_M2_WP_MYSQL_HMAC_PASS 111 synthetic records, no actual email\n";
