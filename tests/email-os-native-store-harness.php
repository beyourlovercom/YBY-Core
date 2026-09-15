<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); }
function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); }
function wp_json_encode( $v ) { return json_encode( $v, JSON_UNESCAPED_SLASHES ); }
function absint( $v ) { return abs( (int) $v ); }
function current_time( $type ) { return '2030-01-02 03:04:05'; }

class YBY_Email_Design_Settings { public static function get() { return array(); } }
class YBY_Database {
	public static function email_templates_table_name() { return 'wp_yby_email_templates'; }
	public static function email_template_versions_table_name() { return 'wp_yby_email_template_versions'; }
}
class Native_Test_Renderer {
	public function render( $payload, $context, $design ) {
		return array( 'valid' => false === strpos( $payload['subject'] ?? '', '{bad}' ), 'diagnostics' => array() );
	}
}
class Native_Test_WPDB {
	public $insert_id = 0;
	public $templates = array();
	public $versions = array();
	private $next_template_id = 1;
	private $next_version_id = 1;
	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) ? (string) $arg : "'" . str_replace( "'", "''", (string) $arg ) . "'";
			$query = preg_replace( '/%[sd]/', $replacement, $query, 1 );
		}
		return $query;
	}
	public function get_row( $query, $format ) {
		if ( preg_match( "/template_key = '([^']+)'/", $query, $m ) ) {
			foreach ( $this->templates as $row ) { if ( $row['template_key'] === $m[1] ) { return $row; } }
		}
		if ( preg_match( '/WHERE id = (\d+)/', $query, $m ) ) {
			foreach ( $this->versions as $row ) { if ( (int) $row['id'] === (int) $m[1] ) { return $row; } }
		}
		return null;
	}
	public function get_var( $query ) {
		if ( preg_match( '/template_id = (\d+)/', $query, $m ) ) {
			$max = 0;
			foreach ( $this->versions as $row ) { if ( (int) $row['template_id'] === (int) $m[1] ) { $max = max( $max, (int) $row['version_number'] ); } }
			return $max;
		}
		return null;
	}
	public function insert( $table, $data ) {
		if ( 'wp_yby_email_templates' === $table ) {
			$data['id'] = $this->next_template_id++;
			$this->templates[] = $data; $this->insert_id = $data['id']; return 1;
		}
		$data['id'] = $this->next_version_id++;
		$this->versions[] = $data; $this->insert_id = $data['id']; return 1;
	}
	public function update( $table, $data, $where ) {
		if ( 'wp_yby_email_templates' === $table ) { $rows =& $this->templates; } else { $rows =& $this->versions; }
		foreach ( $rows as &$row ) {
			if ( (int) $row['id'] === (int) $where['id'] ) { $row = array_merge( $row, $data ); return 1; }
		}
		return 0;
	}
}
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-schema.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-store.php';
$wpdb = new Native_Test_WPDB();
$GLOBALS['wpdb'] = $wpdb;
$store = new YBY_Email_Template_Store();
$renderer = new Native_Test_Renderer();
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$woo = array( 'template_key' => 'woocommerce:new_order', 'provider' => 'woocommerce', 'label' => 'New order' );
$assert( false === $store->save_draft( $woo, array() )['success'], 'Woo provider must remain read-only' );
$identity = array(
	'template_key' => 'wordpress:new_user',
	'provider' => 'wordpress',
	'label' => 'New User / Account',
	'default_payload' => array( 'subject' => 'Hello {user_login}' ),
	'sample_context' => array( 'user_login' => 'alex' ),
);
$draft = $store->save_draft( $identity, array( 'subject' => 'Hello {user_login}' ) );
$assert( ! empty( $draft['success'] ) && 1 === count( $wpdb->templates ), 'Draft insert failed' );
$first = $store->publish( $identity, array( 'subject' => 'Hello {user_login}' ), $renderer, 7 );
$assert( ! empty( $first['success'] ) && 1 === (int) $first['version_number'], 'Publish v1 failed' );
$v1_hash = $first['content_hash_sha256'];
$state = $store->get_state( $identity );
$assert( 'published' === $state['status'] && 1 === (int) $state['published_version'], 'Published state missing' );
$draft2 = $store->save_draft( $identity, array( 'subject' => 'Updated {user_login}' ) );
$state2 = $store->get_state( $identity );
$assert( ! empty( $draft2['success'] ) && 'published' === $state2['status'] && 1 === (int) $state2['published_version'], 'Draft edit changed published pointer' );
$second = $store->publish( $identity, array( 'subject' => 'Updated {user_login}' ), $renderer, 7 );
$assert( ! empty( $second['success'] ) && 2 === (int) $second['version_number'], 'Publish v2 failed' );
$assert( 2 === count( $wpdb->versions ), 'Immutable version history was not preserved' );
$assert( $v1_hash !== $second['content_hash_sha256'], 'Content hash did not change for changed payload' );
$assert( 64 === strlen( $second['content_hash_sha256'] ), 'SHA-256 length invalid' );
echo "PASS email-os-native-store-harness\n";
