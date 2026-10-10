<?php
/** M2.8: Newsletter-only HMAC scope, expiry and regression (synthetic only). */
define( 'ABSPATH', __DIR__ );
define( 'WP_CLI', true );
define( 'AUTH_KEY', 'synthetic-auth' );
define( 'SECURE_AUTH_KEY', 'synthetic-secure-auth' );
define( 'LOGGED_IN_KEY', 'synthetic-logged-in' );
define( 'NONCE_KEY', 'synthetic-nonce' );
$options = array(); $autoload = array(); $transients = array(); $https = true; $transient_writable = true; $delete_writable = true;
function get_option( $k, $default = false ) { global $options; return $options[ $k ] ?? $default; }
function add_option( $k, $v, $deprecated = '', $auto = true ) { global $options, $autoload; if ( isset( $options[ $k ] ) ) { return false; } $options[ $k ] = $v; $autoload[ $k ] = $auto; return true; }
function update_option( $k, $v, $auto = null ) { global $options; $options[ $k ] = $v; return true; }
function delete_option( $k ) { global $options, $delete_writable; if ( ! $delete_writable ) { return false; } unset( $options[ $k ] ); return true; }
function get_transient( $k ) { global $transients; return $transients[ $k ] ?? false; }
function set_transient( $k, $v, $ttl ) { global $transients, $transient_writable; if ( ! $transient_writable ) { return false; } $transients[ $k ] = $v; return true; }
function is_ssl() { global $https; return $https; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
class WP_Error {
	public $code; public $message; public $data;
	public function __construct( $c, $m = '', $d = array() ) { $this->code = $c; $this->message = $m; $this->data = $d; }
	public function get_error_code() { return $this->code; }
	public function get_error_data() { return $this->data; }
}
class Newsletter_Read_Request {
	public $headers = array(); public $method = 'GET'; public $body = '';
	public $route = '/andy-core/v1/erp/snapshot/subscribers';
	public function get_header( $name ) { return $this->headers[ $name ] ?? ''; }
	public function get_method() { return $this->method; }
	public function get_body() { return $this->body; }
	public function get_route() { return $this->route; }
}
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';
function nassert( $test, $why ) { if ( ! $test ) { fwrite( STDERR, "M2_8_FAIL: {$why}\n" ); exit( 1 ); } }
function ndeny( $actual, $expected, $why ) {
	nassert( $actual instanceof WP_Error && $expected === $actual->code, $why );
	nassert( (int) ( $actual->data['status'] ?? 0 ) >= 400, $why . ' must have HTTP error status' );
}
function req( $path, $secret, $nonce, $method = 'GET' ) {
	$_SERVER['REQUEST_URI'] = $path;
	$r = new Newsletter_Read_Request();
	$r->method = $method;
	$t = (string) time();
	$r->headers = array(
		'X-YBY-Key-Id' => 'newsletter-only-key',
		'X-YBY-Connection-Key' => 'newsletter-only.example.test',
		'X-YBY-Signature-Version' => 'v1',
		'X-YBY-Timestamp' => $t,
		'X-YBY-Nonce' => $nonce,
		'X-YBY-Signature' => YBY_Connector::sign( $method, $path, $t, $nonce, 'newsletter-only.example.test', '', '', $secret ),
	);
	return $r;
}
$path = '/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1';
$pre = req( $path, 'synthetic-unconfigured', 'nonce-before-001' );
ndeny( YBY_Connector::authenticate( $pre ), 'AUTH_INVALID', 'Default-off scoped identity must be denied' );
nassert( false === get_option( YBY_Connector_Newsletter_Readonly::OPTION, false ), 'Provisioning must not be automatic' );
nassert( false === YBY_Connector_Newsletter_Readonly::provision( 'newsletter-only.example.test', 'nl', 901 ), 'TTL over 15 minutes forbidden' );
nassert( false === YBY_Connector_Newsletter_Readonly::provision( 'newsletter-only.example.test', 'nl', 0 ), 'Zero TTL forbidden' );
nassert( false === YBY_Connector_Newsletter_Readonly::provision( 'bad key', 'nl', 30 ), 'Unsafe identity forbidden' );

$secret = YBY_Connector_Newsletter_Readonly::provision( 'newsletter-only.example.test', 'newsletter-only-key', 600 );
nassert( is_string( $secret ) && strlen( $secret ) >= 64, 'CLI must generate one-use scoped secret' );
nassert( false === $autoload[ YBY_Connector_Newsletter_Readonly::OPTION ] && false === $autoload[ YBY_Connector_Newsletter_Readonly::SECRET_OPTION ], 'Scoped config and secret must be nonautoload' );
nassert( false === strpos( json_encode( get_option( YBY_Connector_Newsletter_Readonly::SECRET_OPTION ) ), $secret ), 'Scoped secret must not be stored in plaintext' );
nassert( false === get_option( YBY_Connector::OPTION, false ) && false === get_option( YBY_Connector::SECRET_OPTION, false ), 'General connector must stay entirely OFF' );
nassert( false === YBY_Connector_Newsletter_Readonly::provision( 'newsletter-only.example.test', 'key-other', 60 ), 'Existing scoped key cannot be silently replaced' );

$ok = req( $path, $secret, 'nonce-positive-001' );
nassert( true === YBY_Connector::authenticate( $ok ), 'Valid native GET should authenticate while general Connector is OFF' );
ndeny( YBY_Connector::authenticate( $ok ), 'REPLAY_DETECTED', 'Replayed nonce must fail' );
$limited = req( str_replace( 'limit=1', 'limit=100', $path ), $secret, 'nonce-limit-001' );
nassert( true === YBY_Connector::authenticate( $limited ), 'Bounded 100-row native GET should authenticate' );

$rejected_paths = array(
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?limit=1',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=elementor_signup&limit=1',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=0',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=101',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=01',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1&cursor=X',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1&updated_after=2026-10-10T01:00:00Z',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1&source=elementor_signup',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?limit=1&source=andy_core_newsletter',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter%26limit%3D1',
	'/wp-json/andy-core/v1/erp/snapshot/subscribers?source=andy_core_newsletter&limit=1#extra',
	'/wp-json/andy-core/v1/erp/snapshot/affiliates?source=andy_core_newsletter&limit=1',
	'/wp-json/andy-core/v1/erp/snapshot/coupons?source=andy_core_newsletter&limit=1',
	'/wp-json/andy-core/v1/erp/snapshot/email-templates?source=andy_core_newsletter&limit=1',
	'/wp-json/andy-core/v1/erp/health',
);
foreach ( $rejected_paths as $n => $url ) {
	$r = req( $url, $secret, 'deny-path-' . str_pad( (string) $n, 6, '0', STR_PAD_LEFT ) );
	if ( false !== strpos( $url, '/snapshot/affiliates' ) ) { $r->route = '/andy-core/v1/erp/snapshot/affiliates'; }
	if ( false !== strpos( $url, '/snapshot/coupons' ) ) { $r->route = '/andy-core/v1/erp/snapshot/coupons'; }
	if ( false !== strpos( $url, '/snapshot/email-templates' ) ) { $r->route = '/andy-core/v1/erp/snapshot/email-templates'; }
	if ( false !== strpos( $url, '/health' ) ) { $r->route = '/andy-core/v1/erp/health'; }
	ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Scoped key must deny path #' . $n );
}
$mutations = array(
	'/wp-json/andy-core/v1/erp/affiliates/provision',
	'/wp-json/andy-core/v1/erp/coupons/provision',
	'/wp-json/andy-core/v1/erp/payouts/complete',
	'/wp-json/andy-core/v1/erp/wordpress/users/1/password',
	'/wp-json/andy-core/v1/erp/content/publish',
	'/wp-json/andy-core/v1/erp/content/preview',
);
foreach ( $mutations as $n => $url ) {
	$r = req( $url, $secret, 'deny-post-' . str_pad( (string) $n, 6, '0', STR_PAD_LEFT ), 'POST' );
	$r->route = substr( $url, strlen( '/wp-json' ) );
	ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Scoped key must deny all POST paths' );
}
$r = req( $path, $secret, 'deny-body-0001' ); $r->body = '{}';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Body is prohibited' );
$r = req( $path, $secret, 'deny-idempotent' ); $r->headers['X-YBY-Idempotency-Key'] = 'side-effect';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Idempotency slot prohibited' );
$r = req( $path, $secret, 'deny-routing-01' ); $r->route = '/andy-core/v1/erp/content/publish';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Actual REST route must match' );
$r = req( $path, $secret, 'deny-incorrect-signature' ); $r->headers['X-YBY-Signature'] = str_repeat( '0', 64 );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Bad HMAC must fail' );
$r = req( $path, $secret, 'deny-version-001' ); $r->headers['X-YBY-Signature-Version'] = 'v2';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Bad HMAC version must fail' );
$r = req( $path, $secret, 'deny-stale-0001' ); $r->headers['X-YBY-Timestamp'] = (string) ( time() - 301 );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Expired request timestamp must fail' );
$r = req( $path, $secret, 'deny-wrong-key-id' ); $r->headers['X-YBY-Connection-Key'] = 'unrelated.test';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Wrong connection identity must fail' );
$https = false; $r = req( $path, $secret, 'deny-insecure-01' );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'TLS must be enforced' ); $https = true;

$stored = $options[ YBY_Connector_Newsletter_Readonly::SECRET_OPTION ];
$options[ YBY_Connector_Newsletter_Readonly::SECRET_OPTION ]['mac'] = str_repeat( '0', 64 );
$r = req( $path, $secret, 'deny-corrupt-001' );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Corrupt encrypted secret must fail' );
$options[ YBY_Connector_Newsletter_Readonly::SECRET_OPTION ] = $stored;
$options[ YBY_Connector_Newsletter_Readonly::OPTION ]['expires_at'] = time() - 1;
$r = req( $path, $secret, 'deny-expired-001' );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Expired issued credential must fail' );
$options[ YBY_Connector_Newsletter_Readonly::OPTION ]['expires_at'] = time() + 600;

$transients = array();
$transient_writable = false;
$r = req( $path, $secret, 'nonce-store-failure' );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_UNAVAILABLE', 'Failed replay-store write must fail closed' );
$transient_writable = true;
$transients = array();
for ( $n = 0; $n < YBY_Connector::RATE_LIMIT_MAX_REQUESTS; $n++ ) {
	$r = req( $path, $secret, 'nonce-rate-' . str_pad( (string) $n, 8, '0', STR_PAD_LEFT ) );
	nassert( true === YBY_Connector::authenticate( $r ), 'Scoped rate window should admit bounded calls' );
}
$r = req( $path, $secret, 'nonce-rate-final-001' );
ndeny( YBY_Connector::authenticate( $r ), 'RATE_LIMITED', 'Rate cap must reject overflow' );

// General identity remains backwards-compatible and cannot be used to evade
// the read-only key scope. An intentional key-ID collision is rejected.
nassert( false === YBY_Connector_Newsletter_Readonly::provision( 'other.test', 'other', 60 ), 'Replacement must remain impossible' );
YBY_Connector::save( array( 'enabled' => true, 'connection_key' => 'general.example.test', 'key_id' => 'general-key' ) );
$general_secret = YBY_Connector::generate_secret();
$r = req( '/wp-json/andy-core/v1/erp/health', $general_secret, 'general-key-0001' );
$r->route = '/andy-core/v1/erp/health';
$r->headers['X-YBY-Key-Id'] = 'general-key';
$r->headers['X-YBY-Connection-Key'] = 'general.example.test';
$r->headers['X-YBY-Signature'] = YBY_Connector::sign( 'GET', $_SERVER['REQUEST_URI'],
	$r->headers['X-YBY-Timestamp'], $r->headers['X-YBY-Nonce'], 'general.example.test', '', '', $general_secret );
nassert( true === YBY_Connector::authenticate( $r ), 'Existing general Connector HMAC must remain compatible' );
$r = req( '/wp-json/andy-core/v1/erp/health', $secret, 'deny-crosskey-001' );
$r->route = '/andy-core/v1/erp/health';
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_FORBIDDEN', 'Scoped identity cannot fall through to general Connector' );

$delete_writable = false;
nassert( false === YBY_Connector_Newsletter_Readonly::revoke(),
	'Failed option delete cannot be reported as successful revocation' );
nassert( false !== get_option( YBY_Connector_Newsletter_Readonly::OPTION, false ),
	'Failed revocation preserves outstanding scope until retry or expiry' );
$delete_writable = true;
nassert( true === YBY_Connector_Newsletter_Readonly::revoke(), 'Trusted CLI revocation must succeed' );
nassert( false === get_option( YBY_Connector_Newsletter_Readonly::OPTION, false ) &&
	false === get_option( YBY_Connector_Newsletter_Readonly::SECRET_OPTION, false ), 'Both scoped credentials must be revoked' );
$r = req( $path, $secret, 'deny-revoked-001' );
ndeny( YBY_Connector::authenticate( $r ), 'AUTH_INVALID', 'Revoked key must fail closed' );
echo "Newsletter M2.8 readonly Connector security harness PASS.\n";
