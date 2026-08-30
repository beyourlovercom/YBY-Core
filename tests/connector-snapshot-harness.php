<?php
/** Focused M3 read-only Provider Snapshot contract harness. */
define( 'ABSPATH', __DIR__ );
$options = array(); $routes = array();
function get_option( $key, $default = false ) { global $options; return array_key_exists( $key, $options ) ? $options[ $key ] : $default; }
function get_bloginfo( $show = '' ) { return '7.1'; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function is_ssl() { return true; }
function get_transient( $key ) { return false; }
function set_transient( $key, $value, $ttl ) { return true; }
function register_rest_route( $namespace, $route, $args ) { global $routes; $routes[ $namespace . $route ] = $args; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_json_encode( $value ) { return json_encode( $value ); }
class WP_Error { public $code; public $message; public $data; public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; } public function get_error_code() { return $this->code; } public function get_error_message() { return $this->message; } public function get_error_data() { return $this->data; } }
class Snapshot_Request { public $params; public $route; public function __construct( $route, $params = array() ) { $this->route = $route; $this->params = $params; } public function get_param( $key ) { return $this->params[ $key ] ?? null; } public function get_route() { return $this->route; } }
function snapshot_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } }
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';

YBY_Connector::register_routes();
snapshot_assert( 10 === count( $routes ), 'M3.1 GET routes plus two M5 and two M6 mutation routes must be registered.' );
foreach ( array( '/health', '/snapshot/affiliates', '/snapshot/coupons', '/snapshot/referrals', '/snapshot/payouts', '/snapshot/subscribers' ) as $route ) { snapshot_assert( isset( $routes[ YBY_Connector::REST_NAMESPACE . $route ] ), 'Required GET route is missing: ' . $route ); snapshot_assert( 'GET' === $routes[ YBY_Connector::REST_NAMESPACE . $route ]['methods'], 'Snapshot route must be GET-only: ' . $route ); }

$invalid = YBY_Connector::snapshot( 'affiliates', new Snapshot_Request( '/andy-core/v1/erp/snapshot/affiliates', array( 'limit' => 101 ) ) );
snapshot_assert( is_wp_error( $invalid ) && 'VALIDATION_FAILED' === $invalid->code, 'Out-of-bounds limit must fail validation.' );
$foreign_cursor = rtrim( strtr( base64_encode( json_encode( array( 'resource' => 'coupons', 'offset' => 10 ) ) ), '+/', '-_' ), '=' );
$bad_cursor = YBY_Connector::snapshot( 'affiliates', new Snapshot_Request( '/andy-core/v1/erp/snapshot/affiliates', array( 'cursor' => $foreign_cursor ) ) );
snapshot_assert( is_wp_error( $bad_cursor ) && 'VALIDATION_FAILED' === $bad_cursor->code, 'Cursor must be bound to its snapshot resource.' );
$bad_date = YBY_Connector::snapshot( 'affiliates', new Snapshot_Request( '/andy-core/v1/erp/snapshot/affiliates', array( 'updated_after' => 'tomorrow' ) ) );
snapshot_assert( is_wp_error( $bad_date ) && 'VALIDATION_FAILED' === $bad_date->code, 'Invalid updated_after must fail validation.' );
$absent = YBY_Connector::snapshot( 'affiliates', new Snapshot_Request( '/andy-core/v1/erp/snapshot/affiliates' ) );
snapshot_assert( is_wp_error( $absent ) && 'PROVIDER_UNAVAILABLE' === $absent->code, 'AffiliateWP absence must be graceful.' );
$absent = YBY_Connector::snapshot( 'coupons', new Snapshot_Request( '/andy-core/v1/erp/snapshot/coupons' ) );
snapshot_assert( is_wp_error( $absent ) && 'PROVIDER_UNAVAILABLE' === $absent->code, 'WooCommerce absence must be graceful.' );
$absent = YBY_Connector::snapshot( 'subscribers', new Snapshot_Request( '/andy-core/v1/erp/snapshot/subscribers' ) );
snapshot_assert( is_wp_error( $absent ) && 'PROVIDER_UNAVAILABLE' === $absent->code, 'Subscriber tables absence must be graceful.' );

$statuses = YBY_Connector::endpoint_statuses();
snapshot_assert( 11 === count( $statuses ) && 'GET' === $statuses['subscriber_snapshot']['method'], 'M3.1 must expose exactly eleven contract rows including subscribers.' );
foreach ( array( 'affiliate_provision', 'affiliate_status' ) as $name ) { snapshot_assert( false === $statuses[ $name ]['available'] && 'Provider Missing' === $statuses[ $name ]['status'], 'M5 affiliate mutations must report provider absence.' ); }
snapshot_assert( 'Not Available' === $statuses['payout_complete']['status'], 'Payout completion must remain unregistered and Not Available.' );
$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector.php' );
$snapshot_source = substr( $source, strpos( $source, 'public static function snapshot' ), strpos( $source, 'private static function provision_coupon' ) - strpos( $source, 'public static function snapshot' ) );
snapshot_assert( false === strpos( $snapshot_source, 'affwp_add_' ) && false === strpos( $snapshot_source, '->save(' ), 'Snapshot implementation must not contain provider mutation calls.' );
$route_block = substr( $source, strpos( $source, 'public static function register_routes' ), strpos( $source, 'public static function dispatch_health' ) - strpos( $source, 'public static function register_routes' ) );
snapshot_assert( 4 === substr_count( $route_block, "'POST'" ), 'Exactly four POST routes must be registered.' );
snapshot_assert( false === strpos( substr( $source, strpos( $source, 'public static function snapshot' ) ), 'SECRET_OPTION' ), 'Snapshot implementation must not expose connector secrets.' );
foreach ( array( 'affiliate_id', 'user_id', 'display_name', 'status', 'rate_type', 'registered_at', 'provider_modified_at', 'coupon_id', 'normalized_code', 'discount_type', 'date_expires', 'linked_affiliate_id', 'referral_id', 'referred_at', 'payout_id', 'referral_ids', 'payout_method' ) as $field ) { snapshot_assert( false !== strpos( $source, "'{$field}'" ), 'Required snapshot field mapping is missing: ' . $field ); }
snapshot_assert( false !== strpos( $source, "'items'" ) && false !== strpos( $source, "'next_cursor'" ), 'Snapshot envelope data must contain items and next_cursor.' );
echo "Connector M3 snapshot harness passed.\n";
