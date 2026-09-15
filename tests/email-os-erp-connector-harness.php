<?php
/** P8B authenticated read-only Connector snapshot contract harness. */
define( 'ABSPATH', __DIR__ );
$routes = array();
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function absint( $v ) { return abs( (int) $v ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_timezone() { return new DateTimeZone( 'UTC' ); }
function register_rest_route( $ns, $route, $args ) { global $routes; $routes[ $ns . $route ] = $args; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
class WP_Error { public $code; public $message; public $data; public function __construct( $c, $m = '', $d = array() ) { $this->code=$c; $this->message=$m; $this->data=$d; } public function get_error_code(){return $this->code;} public function get_error_message(){return $this->message;} public function get_error_data(){return $this->data;} }
class P8B_Request { private $params; public function __construct( $params=array() ){ $this->params=$params; } public function get_param( $k ){ return $this->params[$k] ?? null; } }
function p8b_assert( $ok, $msg ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$msg}\n" ); exit(1); } }
class YBY_Email_Template_Store {
	public static $snapshots = array();
	public static function is_native_provider( $p ) { return in_array( (string) $p, array( 'wordpress', 'andy_core' ), true ); }
	public function get_published_snapshot( $identity ) { return self::$snapshots[ $identity['template_key'] ] ?? array(); }
}
class YBY_Email_Template_Registry {
	public function discover() {
		return array(
			'andy_core:notice' => array( 'template_key'=>'andy_core:notice','provider'=>'andy_core','label'=>'Notice','runtime_enabled'=>true ),
			'wordpress:new_user' => array( 'template_key'=>'wordpress:new_user','provider'=>'wordpress','label'=>'New User','runtime_enabled'=>true ),
			'wordpress:draft_only' => array( 'template_key'=>'wordpress:draft_only','provider'=>'wordpress','label'=>'Draft','runtime_enabled'=>true ),
			'woocommerce:new_order' => array( 'template_key'=>'woocommerce:new_order','provider'=>'woocommerce','label'=>'Woo','runtime_enabled'=>true ),
		);
	}
}
YBY_Email_Template_Store::$snapshots = array(
	'andy_core:notice' => array( 'version_number'=>2, 'content_hash'=>str_repeat('a',64), 'published_at'=>'2026-09-15 10:00:00' ),
	'wordpress:new_user' => array( 'version_number'=>4, 'content_hash'=>str_repeat('b',64), 'published_at'=>'2026-09-15 11:00:00' ),
	'woocommerce:new_order' => array( 'version_number'=>9, 'content_hash'=>str_repeat('c',64), 'published_at'=>'2026-09-15 12:00:00' ),
);
require_once dirname( __DIR__ ) . '/inc/class-yby-email-erp-contract.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-connector.php';
YBY_Connector::register_routes();
$route_key = YBY_Connector::REST_NAMESPACE . '/snapshot/email-templates';
p8b_assert( isset( $routes[ $route_key ] ), 'Email template snapshot route must be registered.' );
p8b_assert( 'GET' === $routes[ $route_key ]['methods'], 'Email template route must be GET-only.' );

$result = YBY_Connector::snapshot( 'email-templates', new P8B_Request() );
p8b_assert( ! is_wp_error( $result ), 'Email template snapshot must resolve.' );
p8b_assert( YBY_Email_ERP_Contract::VERSION === $result['resource_contract_version'], 'Resource schema version must be exposed.' );
p8b_assert( 2 === count( $result['items'] ), 'Only valid Published Native templates may be returned.' );
p8b_assert( 'andy_core:notice' === $result['items'][0]['template_key'], 'Items must be stable-sorted by template_key.' );
p8b_assert( 'wordpress:new_user' === $result['items'][1]['template_key'], 'WordPress Published template must be returned.' );
p8b_assert( ! isset( $result['items'][0]['payload'] ) && ! isset( $result['items'][0]['subject'] ), 'Snapshot must not expose content payload.' );

$filtered = YBY_Connector::snapshot( 'email-templates', new P8B_Request( array( 'updated_after'=>'2026-09-15T10:30:00Z' ) ) );
p8b_assert( 1 === count( $filtered['items'] ) && 'wordpress:new_user' === $filtered['items'][0]['template_key'], 'updated_after must filter by Published time.' );
$limited = YBY_Connector::snapshot( 'email-templates', new P8B_Request( array( 'limit'=>'1' ) ) );
p8b_assert( 1 === count( $limited['items'] ) && ! empty( $limited['next_cursor'] ), 'Snapshot must support bounded pagination.' );

$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-connector.php' );
p8b_assert( false === strpos( $source, "'/snapshot/email-templates', array( 'methods' => 'POST'" ), 'No template POST route may exist.' );
echo "PASS email-os-erp-connector-harness\n";
