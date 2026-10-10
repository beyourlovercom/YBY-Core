<?php
/** M2 native snapshot no-WordPress/no-network connector contract harness. */
define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_salt( $scheme = 'auth' ) { return 'only-synthetic-ci-cursor-secret'; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message = '', $data = array() ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
function m2_native_assert( $yes, $why ) {
    if ( ! $yes ) { fwrite( STDERR, 'M2_NATIVE_FAIL: ' . $why . "\n" ); exit( 1 ); }
}
class YBY_Newsletter_Store {
    public static $present = true;
    public static function exists() { return self::$present; }
    public static function site_key() { return 'www.beyourlover.com'; }
    public static function table_name() { return 'wp_yby_newsletter_subscriptions'; }
}
class Native_M2_Request {
    private $params;
    public function __construct( $params = array() ) { $this->params = $params; }
    public function get_param( $key ) { return $this->params[ $key ] ?? null; }
}
class Native_M2_DB {
    public $last_error = '';
    public $rows = array();
    public $sql = '';
    public $args = array();
    public $broken = false;
    public function prepare( $sql, ...$args ) {
        $this->sql = $sql; $this->args = $args;
        return $sql;
    }
    public function get_results( $sql, $format = null ) {
        if ( $this->broken ) { $this->last_error = 'Synthetic source error'; return false; }
        $data = array_values( array_filter( $this->rows, function ( $r ) {
            if ( $r['site_key'] !== $this->args[0] ) { return false; }
            $i = 1;
            if ( false !== strpos( $this->sql, 'status_updated_at >= %s' ) ) {
                if ( strcmp( $r['status_updated_at'], $this->args[$i++] ) < 0 ) { return false; }
            }
            if ( false !== strpos( $this->sql, 'id > %d' ) ) {
                $changed = $this->args[$i++];
                $i++; // repeated timestamp arg
                $id = $this->args[$i];
                if ( strcmp( $r['status_updated_at'], $changed ) < 0 ||
                    ( $r['status_updated_at'] === $changed && $r['id'] <= $id ) ) { return false; }
            }
            return true;
        } ) );
        usort( $data, function ( $a, $b ) {
            return strcmp( $a['status_updated_at'], $b['status_updated_at'] ) ?: ( $a['id'] <=> $b['id'] );
        } );
        return array_slice( $data, 0, end( $this->args ) );
    }
}
require_once dirname( __DIR__ ) . '/inc/class-yby-newsletter-consent.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';
function native_row( $id, $time, $status = 'pending', $site = 'www.beyourlover.com' ) {
    $email = 'subscriber' . $id . '@example.test';
    return array(
        'id' => $id, 'site_key' => $site, 'email' => $email,
        'external_subscription_id' => YBY_Newsletter_Consent::external_id( $site, $email ),
        'consent_source' => 'andy_core_newsletter_form',
        'status' => $status, 'status_updated_at' => $time,
        'subscribed_at' => 'subscribed' === $status ? $time : null,
        'unsubscribed_at' => 'unsubscribed' === $status ? $time : null,
        'confirm_hash' => 'MUST_NEVER_BE_EXPOSED', 'unsubscribe_hash' => 'SECRET',
    );
}
$wpdb = new Native_M2_DB();
$wpdb->rows = array(
    native_row( 1, '2026-10-10 05:00:00', 'pending' ),
    native_row( 2, '2026-10-10 05:00:00', 'subscribed' ),
    native_row( 3, '2026-10-10 05:00:01', 'unsubscribed' ),
    native_row( 4, '2026-10-10 05:00:01', 'suppressed' ),
    native_row( 5, '2026-10-10 05:00:02', 'pending', 'other.test' ),
);
$base = array( 'source' => 'andy_core_newsletter', 'limit' => 1, 'updated_after' => '2026-10-10T05:00:00Z' );
$seen = array();
$cursor = null;
do {
    $params = $base;
    if ( null !== $cursor ) { $params['cursor'] = $cursor; }
    $page = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( $params ) );
    m2_native_assert( ! is_wp_error( $page ), 'Native page should succeed.' );
    m2_native_assert( count( $page['items'] ) <= 1, 'Native page must honor requested limit.' );
    m2_native_assert( $page['source_site'] === 'www.beyourlover.com', 'Native data site must use WordPress site_key.' );
    foreach ( $page['items'] as $item ) {
        m2_native_assert( ! isset( $item['confirm_hash'], $item['unsubscribe_hash'] ), 'Secrets must not be projected.' );
        m2_native_assert( ! array_key_exists( 'confirm_hash', $item ) && ! array_key_exists( 'unsubscribe_hash', $item ), 'No private hashes.' );
        m2_native_assert( $item['provider'] === 'wordpress', 'Provider and transport contract.' );
        $seen[] = $item['email'];
    }
    $cursor = $page['next_cursor'];
} while ( null !== $cursor );
m2_native_assert( 4 === count( $seen ) && 4 === count( array_unique( $seen ) ), 'Tie-break: all four records, no duplicate, no missed same-second row.' );
m2_native_assert( false !== strpos( $wpdb->sql, 'ORDER BY status_updated_at ASC, id ASC' ), 'SQL must order by UTC time then stable ID.' );
m2_native_assert( false === strpos( $wpdb->sql, 'confirm_hash' ) && false === strpos( $wpdb->sql, 'SELECT *' ), 'SQL must select only explicit allowlist.' );
m2_native_assert( $wpdb->args[0] === 'www.beyourlover.com', 'Native query must be site scoped.' );

$first = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( $base ) );
$malformed = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array_merge( $base, array( 'cursor' => 'forged' ) ) ) );
m2_native_assert( is_wp_error( $malformed ) && $malformed->code === 'VALIDATION_FAILED', 'Malformed cursor denied.' );
$cursor = $first['next_cursor'];
$decoded = json_decode( base64_decode( strtr( explode( '.', $cursor )[0], '-_', '+/' ) ), true );
m2_native_assert( $decoded['source'] === 'andy_core_newsletter' && $decoded['after'] === '2026-10-10 05:00:00', 'Cursor binds source and time window.' );
$split = explode( '.', $cursor ); $split[0][0] = 'X';
$forged = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array_merge( $base, array( 'cursor' => implode( '.', $split ) ) ) ) );
m2_native_assert( is_wp_error( $forged ) && $forged->code === 'VALIDATION_FAILED', 'HMAC tamper denied.' );
$shifted = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array(
    'source' => 'andy_core_newsletter', 'limit' => 1, 'cursor' => $cursor, 'updated_after' => '2026-10-10T05:00:01Z',
) ) );
m2_native_assert( is_wp_error( $shifted ) && $shifted->code === 'VALIDATION_FAILED', 'Cursor cannot switch boundary window.' );

$legacy_cursor = rtrim( strtr( base64_encode( json_encode( array( 'resource' => 'subscribers', 'email' => 'a@example.test' ) ) ), '+/', '-_' ), '=' );
$wrong_cursor = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array(
    'source' => 'andy_core_newsletter', 'cursor' => $legacy_cursor, 'limit' => 10,
) ) );
m2_native_assert( is_wp_error( $wrong_cursor ) && $wrong_cursor->code === 'VALIDATION_FAILED', 'Legacy cursor cannot be used for native source.' );
$unknown = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array( 'source' => 'rogue', 'limit' => 1 ) ) );
m2_native_assert( is_wp_error( $unknown ) && $unknown->code === 'VALIDATION_FAILED', 'Unsupported source fail closed.' );
$foreign = YBY_Connector::snapshot( 'coupons', new Native_M2_Request( array( 'source' => 'andy_core_newsletter' ) ) );
m2_native_assert( is_wp_error( $foreign ) && $foreign->code === 'VALIDATION_FAILED', 'Native source must be subscriber-only.' );
$wpdb->rows = array();
for ( $id = 1; $id <= 111; $id++ ) { $wpdb->rows[] = native_row( $id, '2026-10-10 05:10:00' ); }
$large = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array( 'source' => 'andy_core_newsletter', 'limit' => 100 ) ) );
m2_native_assert( count( $large['items'] ) === 100 && is_string( $large['next_cursor'] ), '100-row page cap and next_cursor.' );
$next = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array(
    'source' => 'andy_core_newsletter', 'limit' => 100, 'cursor' => $large['next_cursor'],
) ) );
m2_native_assert( count( $next['items'] ) === 11 && null === $next['next_cursor'], 'Last eleven same-second rows complete without loss.' );
$wpdb->broken = true;
$broken = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array( 'source' => 'andy_core_newsletter', 'limit' => 10 ) ) );
m2_native_assert( is_wp_error( $broken ) && $broken->code === 'PROVIDER_UNAVAILABLE', 'DB error returns retryable 503, not successful empty batch.' );
$wpdb->broken = false; YBY_Newsletter_Store::$present = false;
$missing = YBY_Connector::snapshot( 'subscribers', new Native_M2_Request( array( 'source' => 'andy_core_newsletter', 'limit' => 10 ) ) );
m2_native_assert( is_wp_error( $missing ) && $missing->code === 'PROVIDER_UNAVAILABLE', 'Missing table returns provider unavailable.' );
echo "NEWSLETTER_M2_NATIVE_SNAPSHOT_HARNESS_PASS\n";
