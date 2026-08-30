<?php
/** Focused M4 durable idempotency/audit foundation harness. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function absint( $value ) { return abs( (int) $value ); }
class WP_Error { public $code; public $message; public $data; public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; } }
class M4_WPDB {
	public $prefix = 'wp_'; public $insert_id = 0; public $rows = array(); public $audit = array();
	public function get_charset_collate() { return ''; }
	public function prepare( $query, ...$args ) { foreach ( $args as $arg ) { $query = preg_replace( '/%[sd]/', (string) $arg, $query, 1 ); } return $query; }
	public function insert( $table, $data, $formats = array() ) { if ( false !== strpos( $table, 'idempotency' ) ) { foreach ( $this->rows as $row ) { if ( $row['mutation_identity_hash'] === $data['mutation_identity_hash'] ) { return false; } } $data['id'] = ++$this->insert_id; $this->rows[] = $data; return 1; } $data['id'] = ++$this->insert_id; $this->audit[] = $data; return 1; }
	public function get_row( $query, $format = null ) { if ( preg_match( '/mutation_identity_hash = ([^ ]+)/', $query, $match ) ) { foreach ( $this->rows as $row ) { if ( $row['mutation_identity_hash'] === trim( $match[1], "'\"" ) ) { return $row; } } } if ( preg_match( '/WHERE id = (\d+)/', $query, $match ) ) { foreach ( $this->rows as $row ) { if ( (int) $row['id'] === (int) $match[1] ) { return $row; } } } return null; }
	public function query( $query ) { if ( false !== strpos( $query, 'DELETE FROM' ) ) { $before = count( $this->rows ); $this->rows = array_values( array_filter( $this->rows, static function ( $row ) { return empty( $row['expires_at'] ) || $row['expires_at'] > gmdate( 'Y-m-d H:i:s' ); } ) ); return $before - count( $this->rows ); } if ( preg_match( "/WHERE id = (\d+).*state = 'failed'/", $query, $match ) ) { foreach ( $this->rows as &$row ) { if ( (int) $row['id'] === (int) $match[1] && 'failed' === $row['state'] && ! empty( $row['retryable'] ) ) { $row['state'] = 'processing'; return 1; } } } return 0; }
	public function update( $table, $data, $where, $formats = array(), $where_formats = array() ) { foreach ( $this->rows as &$row ) { if ( (int) $row['id'] === (int) $where['id'] && $row['state'] === $where['state'] ) { $row = array_merge( $row, $data ); return 1; } } return 0; }
	public function get_results( $query, $format = null ) { return $this->audit; }
}
$wpdb = new M4_WPDB();
require_once dirname( __DIR__ ) . '/inc/class-yby-database.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-connector-idempotency.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-connector-audit.php';
function m4_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }
$first = YBY_Connector_Idempotency::begin( 'site-a', 'affiliate.provision', 'plain-secret-looking-key', array( 'affiliate_id' => 7, 'email' => 'safe@example.test' ) );
m4_assert( 'acquired' === $first['status'], 'First acquire must create processing record.' );
m4_assert( YBY_Connector_Idempotency::fingerprint( array( 'value' => 1 ) ) !== YBY_Connector_Idempotency::fingerprint( array( 'value' => '1' ) ), 'Fingerprint must distinguish integers and strings.' );
m4_assert( YBY_Connector_Idempotency::fingerprint( array( 'value' => true ) ) !== YBY_Connector_Idempotency::fingerprint( array( 'value' => 'true' ) ), 'Fingerprint must distinguish booleans and strings.' );
m4_assert( YBY_Connector_Idempotency::fingerprint( array( 'value' => null ) ) !== YBY_Connector_Idempotency::fingerprint( array( 'value' => 'null' ) ), 'Fingerprint must distinguish null and strings.' );
m4_assert( YBY_Connector_Idempotency::fingerprint( array( 'a' => 1, 'b' => 2 ) ) === YBY_Connector_Idempotency::fingerprint( array( 'b' => 2, 'a' => 1 ) ), 'Fingerprint must be stable across associative key order.' );
m4_assert( false === strpos( serialize( $wpdb->rows[0] ), 'plain-secret-looking-key' ), 'Plaintext idempotency key must not be stored.' );
m4_assert( 64 === strlen( $wpdb->rows[0]['idempotency_key_hash'] ), 'Stored idempotency key must be a SHA256 hash.' );
m4_assert( 'IDEMPOTENCY_IN_PROGRESS' === YBY_Connector_Idempotency::begin( 'site-a', 'affiliate.provision', 'plain-secret-looking-key', array( 'affiliate_id' => 7, 'email' => 'safe@example.test' ) )->code, 'Concurrent duplicate must fail closed behind the unique identity.' );
m4_assert( true === YBY_Connector_Idempotency::succeed( $first['id'], array( 'affiliate_id' => 99, 'status' => 'active', 'shared_secret' => 'must-not-persist' ) ), 'Success completion must persist a compact safe result.' );
$replay = YBY_Connector_Idempotency::begin( 'site-a', 'affiliate.provision', 'plain-secret-looking-key', array( 'email' => 'safe@example.test', 'affiliate_id' => 7 ) );
m4_assert( 'replay' === $replay['status'] && 99 === $replay['record']['result']['affiliate_id'], 'Exact retry must replay the same logical result.' );
m4_assert( false === strpos( serialize( $wpdb->rows[0] ), 'must-not-persist' ), 'Sensitive result values must not persist.' );
m4_assert( false !== strpos( $wpdb->rows[0]['safe_result'], 'affiliate_id' ) && false !== strpos( $wpdb->rows[0]['safe_result'], 'status' ) && false === strpos( $wpdb->rows[0]['safe_result'], 'email' ), 'Safe result must use the bounded explicit whitelist.' );
$conflict = YBY_Connector_Idempotency::begin( 'site-a', 'affiliate.provision', 'plain-secret-looking-key', array( 'affiliate_id' => 8 ) );
m4_assert( $conflict instanceof WP_Error && 'IDEMPOTENCY_CONFLICT' === $conflict->code, 'Changed fingerprint must conflict.' );
m4_assert( 'acquired' === YBY_Connector_Idempotency::begin( 'site-b', 'affiliate.provision', 'plain-secret-looking-key', array( 'affiliate_id' => 8 ) )['status'], 'Connection scope must isolate identity.' );
m4_assert( 'acquired' === YBY_Connector_Idempotency::begin( 'site-a', 'coupon.provision', 'plain-secret-looking-key', array( 'affiliate_id' => 8 ) )['status'], 'Action scope must isolate identity.' );
m4_assert( 'IDEMPOTENCY_INVALID' === YBY_Connector_Idempotency::begin( '', 'coupon.provision', 'invalid-key', array() )->code, 'Empty connection scope must fail closed.' );
m4_assert( 'IDEMPOTENCY_INVALID' === YBY_Connector_Idempotency::begin( str_repeat( 'x', 129 ), 'coupon.provision', 'invalid-key-2', array() )->code, 'Overlong connection scope must fail closed.' );
m4_assert( 'IDEMPOTENCY_INVALID' === YBY_Connector_Idempotency::begin( 'site-a', str_repeat( 'x', 129 ), 'invalid-key-3', array() )->code, 'Overlong action scope must fail closed.' );
m4_assert( 'IDEMPOTENCY_INVALID' === YBY_Connector_Idempotency::begin( 'site-a', 'coupon.provision', str_repeat( 'x', 256 ), array() )->code, 'Overlong idempotency key must fail closed.' );
$retry = YBY_Connector_Idempotency::begin( 'retry-site', 'payout.complete', 'retry-key', array( 'payout_id' => 1 ) );
m4_assert( true === YBY_Connector_Idempotency::fail( $retry['id'], 'PROVIDER_TIMEOUT', true ), 'Retryable failure must complete.' );
m4_assert( 'acquired' === YBY_Connector_Idempotency::begin( 'retry-site', 'payout.complete', 'retry-key', array( 'payout_id' => 1 ) )['status'], 'Retryable failure must reacquire the same row.' );
$failed = YBY_Connector_Idempotency::begin( 'fail-site', 'coupon.provision', 'fail-key', array( 'coupon' => 'A' ) );
YBY_Connector_Idempotency::fail( $failed['id'], 'VALIDATION_FAILED', false );
$deterministic = YBY_Connector_Idempotency::begin( 'fail-site', 'coupon.provision', 'fail-key', array( 'coupon' => 'A' ) );
m4_assert( 'failed' === $deterministic['status'] && 'VALIDATION_FAILED' === $deterministic['record']['failure_code'], 'Non-retryable failure must be deterministic.' );
$audit_id = YBY_Connector_Audit::record( array( 'request_id' => 'req_m4_1', 'key_id' => 'erp-primary', 'connection_key' => 'site-a', 'endpoint_action' => '/affiliates/provision', 'idempotency_key_hash' => YBY_Connector_Idempotency::key_hash( 'audit-key' ), 'target_provider_ids' => array( 'affiliate_id' => array( 99 ), 'secret' => array( 'never' ) ), 'result_code' => 'OK', 'success' => true ) );
m4_assert( $audit_id > 0 && 'ERP trusted system' === $wpdb->audit[0]['actor'], 'Audit must record the trusted actor and safe fields.' );
m4_assert( false === strpos( serialize( $wpdb->audit[0] ), 'audit-key' ) && false === strpos( serialize( $wpdb->audit[0] ), 'never' ), 'Audit must not store plaintext keys or unsafe target fields.' );
m4_assert( false === strpos( $wpdb->audit[0]['target_provider_ids'], 'email' ), 'Audit target IDs must use the explicit target allowlist.' );
$wpdb->rows[0]['expires_at'] = '2000-01-01 00:00:00';
m4_assert( 1 === YBY_Connector_Idempotency::cleanup( 1 ), 'Cleanup must remove expired records.' );
$idempotency_source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector-idempotency.php' );
$connector_source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector.php' );
$routes = substr( $connector_source, strpos( $connector_source, 'public static function register_routes' ), strpos( $connector_source, 'public static function dispatch_health' ) - strpos( $connector_source, 'public static function register_routes' ) );
m4_assert( false !== strpos( $idempotency_source, 'min( 1000' ), 'Cleanup must retain a hard upper bound.' );
m4_assert( false !== strpos( $routes, "methods' => 'POST'" ), 'M5 mutation routes are registered after the M4 foundation.' );
foreach ( array( 'wp_remote_', 'wp_insert_post', 'wp_update_post', 'wc_create_coupon' ) as $mutation ) { m4_assert( false === strpos( $connector_source, $mutation ), 'M4/M5 connector must not perform out-of-scope mutation: ' . $mutation ); }
$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-database.php' );
m4_assert( false !== strpos( $source, 'UNIQUE KEY mutation_identity' ) && substr_count( $source, 'dbDelta(' ) >= 4, 'Migration must use a unique DB key and idempotent dbDelta.' );
echo "Connector M4 idempotency/audit harness passed.\n";
