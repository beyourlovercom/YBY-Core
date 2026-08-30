<?php
/** Focused M3.1 WordPress Subscriber Snapshot harness. */
define( 'ABSPATH', __DIR__ );
class WP_Error {
	public $code; public $message; public $data;
	public function __construct( $code, $message = '', $data = array() ) {
		$this->code = $code; $this->message = $message; $this->data = $data;
	}
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function subscriber_assert( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}
class YBY_Connector {
	public static $connection_key = 'beyourlover.com';
	public static function timestamp( $value ) {
		$time = strtotime( $value ); return false === $time ? false : $time;
	}
	public static function get_options() { return array( 'connection_key' => self::$connection_key ); }
}

class Subscriber_Fake_DB {
	public $prefix = 'wp_'; public $last_error = '';
	public $rows = array(); public $prepared_sql = ''; public $prepared_args = array();
	public $missing_schema = false;
	public function get_col( $sql, $column = 0 ) {
		if ( $this->missing_schema ) { return array( 'id' ); }
		if ( false !== strpos( $sql, 'e_submissions_values' ) ) {
			return array( 'id', 'submission_id', 'key', 'value' );
		}
		return array( 'id', 'form_name', 'created_at_gmt' );
	}
	public function prepare( $sql, ...$args ) {
		$this->prepared_sql = $sql; $this->prepared_args = $args; return $sql;
	}
	public function get_results( $sql ) { return $this->rows; }
}
require_once dirname( __DIR__ ) . '/inc/class-yby-subscriber-snapshot.php';

$wpdb = new Subscriber_Fake_DB();
$wpdb->rows = array(
	(object) array( 'email_normalized' => ' User@Example.COM ', 'first_signup_gmt' => '2025-01-02 03:04:05', 'latest_signup_gmt' => '2025-05-06 07:08:09' ),
	(object) array( 'email_normalized' => 'z@example.com', 'first_signup_gmt' => '2025-02-02 03:04:05', 'latest_signup_gmt' => '2025-06-06 07:08:09' ),
);
$query = array( 'limit' => 1, 'offset' => 0, 'cursor_key' => null, 'updated_after' => null );
$result = YBY_Subscriber_Snapshot::snapshot( $query );
subscriber_assert( ! is_wp_error( $result ), 'Subscriber snapshot should succeed.' );
subscriber_assert( 1 === count( $result['items'] ), 'limit must bound subscriber rows.' );
$item = $result['items'][0];
subscriber_assert( 'user@example.com' === $item['email'], 'Email must be normalized.' );
subscriber_assert( is_string( $item['external_subscription_id'] ) && 0 === strpos( $item['external_subscription_id'], 'elementor_signup:' ), 'External subscription ID must be non-empty and prefixed.' );
subscriber_assert( 'elementor_signup:' . hash( 'sha256', "beyourlover.com\nuser@example.com" ) === $item['external_subscription_id'], 'External subscription ID must use the exact deterministic formula.' );
subscriber_assert( false === strpos( $item['external_subscription_id'], 'user@example.com' ), 'External subscription ID must not embed the plaintext email.' );
subscriber_assert( 'subscribed' === $item['status'], 'Status must be subscribed.' );
subscriber_assert( 'wordpress_elementor_signup' === $item['consent_source'], 'Consent source must be stable.' );
subscriber_assert( 'beyourlover.com' === $item['source_site'], 'Source site must use Connector connection key.' );
subscriber_assert( 'wordpress' === $item['provider'], 'Provider must be WordPress.' );
subscriber_assert( '2025-01-02T03:04:05+00:00' === $item['subscribed_at'], 'First signup must map to subscribed_at.' );
subscriber_assert( '2025-05-06T07:08:09+00:00' === $item['status_updated_at'], 'Latest signup must map to status_updated_at.' );
subscriber_assert( is_string( $result['next_cursor'] ) && '' !== $result['next_cursor'], 'More rows must return cursor.' );
$decoded = json_decode( base64_decode( strtr( $result['next_cursor'], '-_', '+/' ) ), true );
subscriber_assert( 'subscribers' === $decoded['resource'], 'Cursor must be bound to subscribers.' );
subscriber_assert( 'user@example.com' === $decoded['email'], 'Cursor must use normalized email keyset.' );

$sql = $wpdb->prepared_sql;
subscriber_assert( false !== strpos( $sql, "LOWER(TRIM(s.form_name)) IN ('singup','signup')" ), 'Only signup/Singup forms may be selected.' );
subscriber_assert( false === strpos( $sql, 'New Form' ), 'New Form must never be selected.' );
subscriber_assert( false !== strpos( $sql, 'GROUP BY email_normalized' ), 'Duplicate emails must be grouped.' );
subscriber_assert( false !== strpos( $sql, 'MIN(s.created_at_gmt)' ), 'First signup must use submission creation time.' );
subscriber_assert( false !== strpos( $sql, 'MAX(s.created_at_gmt)' ), 'Latest signup must use submission creation time.' );
$wpdb->rows = array();
$query['updated_after'] = '2026-01-02T03:04:05Z';
YBY_Subscriber_Snapshot::snapshot( $query );
subscriber_assert( false !== strpos( $wpdb->prepared_sql, 'HAVING latest_signup_gmt > %s' ), 'updated_after must use latest signup time.' );
subscriber_assert( in_array( '2026-01-02 03:04:05', $wpdb->prepared_args, true ), 'updated_after must be passed as UTC provider time.' );

$query['updated_after'] = null;
$query['cursor_key'] = 'user@example.com';
YBY_Subscriber_Snapshot::snapshot( $query );
subscriber_assert( false !== strpos( $wpdb->prepared_sql, 'LOWER(TRIM(v.value)) > %s' ), 'Cursor must use normalized-email keyset pagination.' );
subscriber_assert( in_array( 'user@example.com', $wpdb->prepared_args, true ), 'Cursor key must be a prepared value.' );
$wpdb->rows = array(
	(object) array( 'email_normalized' => ' User@Example.COM ', 'first_signup_gmt' => '2025-01-02 03:04:05', 'latest_signup_gmt' => '2025-05-06 07:08:09' ),
	(object) array( 'email_normalized' => 'z@example.com', 'first_signup_gmt' => '2025-02-02 03:04:05', 'latest_signup_gmt' => '2025-06-06 07:08:09' ),
);
$stable = YBY_Subscriber_Snapshot::snapshot( array( 'limit' => 1, 'offset' => 0, 'cursor_key' => null, 'updated_after' => null ) );
subscriber_assert( $stable['items'][0]['external_subscription_id'] === $item['external_subscription_id'], 'Repeated snapshot rows must retain the same external subscription ID.' );
$cursor_stable = YBY_Subscriber_Snapshot::snapshot( array( 'limit' => 1, 'offset' => 0, 'cursor_key' => 'user@example.com', 'updated_after' => null ) );
subscriber_assert( $cursor_stable['items'][0]['external_subscription_id'] === $item['external_subscription_id'], 'Cursor pagination must not change the external subscription ID.' );
$updated_stable = YBY_Subscriber_Snapshot::snapshot( array( 'limit' => 1, 'offset' => 0, 'cursor_key' => null, 'updated_after' => '2026-01-02T03:04:05Z' ) );
subscriber_assert( $updated_stable['items'][0]['external_subscription_id'] === $item['external_subscription_id'], 'updated_after filtering must not change the external subscription ID.' );
YBY_Connector::$connection_key = 'another-site.example';
$different_site = YBY_Subscriber_Snapshot::snapshot( array( 'limit' => 1, 'offset' => 0, 'cursor_key' => null, 'updated_after' => null ) );
subscriber_assert( $different_site['items'][0]['external_subscription_id'] !== $item['external_subscription_id'], 'Different connection keys must produce different external subscription IDs.' );
YBY_Connector::$connection_key = 'beyourlover.com';
$wpdb->missing_schema = true;
$missing = YBY_Subscriber_Snapshot::snapshot( $query );
subscriber_assert( is_wp_error( $missing ) && 'PROVIDER_UNAVAILABLE' === $missing->code, 'Missing subscriber schema must fail gracefully.' );

$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-subscriber-snapshot.php' );
subscriber_assert( ! preg_match( '/\b(?:INSERT|UPDATE|DELETE|REPLACE)\b\s+/i', $source ), 'Subscriber adapter must remain SELECT-only.' );
subscriber_assert( false === strpos( $source, 'SECRET_OPTION' ), 'Subscriber adapter must not expose Connector secrets.' );
echo "Connector M3.1 subscriber snapshot harness passed.\n";
