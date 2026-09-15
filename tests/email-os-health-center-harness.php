<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'WPMS_PLUGIN_VER', '4.5.0' );
$GLOBALS['p6_options'] = array();
$GLOBALS['p6_actions'] = array();
$GLOBALS['p6_mail_calls'] = array();

function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_email( $v ) { return filter_var( trim( (string) $v ), FILTER_SANITIZE_EMAIL ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); }
function is_email( $v ) { return false !== filter_var( (string) $v, FILTER_VALIDATE_EMAIL ); }
function absint( $v ) { return abs( (int) $v ); }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . ltrim( $p, '/' ); }
function current_time( $type ) { return '2030-01-02 03:04:05'; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['p6_options'] ) ? $GLOBALS['p6_options'][ $k ] : $d; }
function update_option( $k, $v, $autoload = null ) { $GLOBALS['p6_options'][ $k ] = $v; return true; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
class WP_Error { public function get_error_message() { return 'transport failure'; } }
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) { $GLOBALS['p6_actions'][ $hook ][] = $callback; }
function remove_action( $hook, $callback, $priority = 10 ) { $GLOBALS['p6_actions'][ $hook ] = array(); }
function wp_mail( $to, $subject, $message, $headers = array() ) {
	$GLOBALS['p6_mail_calls'][] = compact( 'to', 'subject', 'message', 'headers' );
	return true;
}

class P6_Fake_Mailer { public function is_mailer_complete() { return true; } }
class P6_Fake_Connection {
	public function get_title() { return 'Other SMTP'; }
	public function get_mailer_slug() { return 'smtp'; }
	public function get_mailer() { return new P6_Fake_Mailer(); }
}
class P6_Fake_Manager { public function get_mail_connection() { return new P6_Fake_Connection(); } }
class P6_Fake_Core { public function get_connections_manager() { return new P6_Fake_Manager(); } }
function wp_mail_smtp() { return new P6_Fake_Core(); }

class YBY_Email_Template_Store {
	public static function is_native_provider( $provider ) { return in_array( $provider, array( 'wordpress', 'andy_core' ), true ); }
}
class YBY_Email_Design_Settings { public static function get() { return array(); } }
class P6_Fake_Store {
	public $published = true;
	public function get_published_snapshot( $identity ) {
		if ( ! $this->published || ! YBY_Email_Template_Store::is_native_provider( $identity['provider'] ?? '' ) ) { return array(); }
		return array( 'payload' => array( 'subject' => 'Hello {user_login}' ), 'version_number' => 2, 'published_at' => '2030-01-01 00:00:00' );
	}
}
class P6_Fake_Renderer {
	public function render( $payload, $context, $design ) {
		return array( 'valid' => true, 'subject' => 'Hello alex', 'html' => '<html><body>hello</body></html>', 'diagnostics' => array() );
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-email-health-center.php';
$health = new YBY_Email_Health_Center();
$store = new P6_Fake_Store();
$renderer = new P6_Fake_Renderer();
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$transport = $health->transport_status();
$assert( 'WP Mail SMTP v4.5.0' === $transport['plugin'], 'WP Mail SMTP detection failed' );
$assert( 'smtp' === $transport['provider_slug'] && 'Other SMTP' === $transport['provider'], 'mailer metadata failed' );
$assert( true === $transport['configured'], 'configured health signal failed' );

$native = array(
	'wordpress:new_user' => array( 'template_key' => 'wordpress:new_user', 'provider' => 'wordpress', 'label' => 'New User' ),
	'woocommerce:new_order' => array( 'template_key' => 'woocommerce:new_order', 'provider' => 'woocommerce', 'label' => 'New Order' ),
);
$native_health = $health->native_health( $native, $store );
$assert( 1 === $native_health['total'] && 1 === $native_health['published'], 'Woo must be excluded from Native health' );

$identity = array(
	'template_key' => 'wordpress:new_user',
	'provider' => 'wordpress',
	'label' => 'New User',
	'sample_context' => array( 'user_login' => 'alex' ),
);
$result = $health->send_test( $identity, 'owner@example.test', $store, $renderer );
$assert( ! empty( $result['success'] ) && 1 === count( $GLOBALS['p6_mail_calls'] ), 'explicit Native test send failed' );
$assert( 0 === strpos( $GLOBALS['p6_mail_calls'][0]['subject'], '[Email OS Test] ' ), 'test subject prefix missing' );
$assert( in_array( 'Content-Type: text/html; charset=UTF-8', $GLOBALS['p6_mail_calls'][0]['headers'], true ), 'HTML header missing' );
$assert( false === strpos( serialize( $GLOBALS['p6_options'] ), 'owner@example.test' ), 'recipient must not be persisted' );

$before = count( $GLOBALS['p6_mail_calls'] );
$bad = $health->send_test( $identity, 'not-an-email', $store, $renderer );
$assert( empty( $bad['success'] ) && 'invalid_recipient' === $bad['error_code'] && $before === count( $GLOBALS['p6_mail_calls'] ), 'invalid recipient must fail before send' );

$woo = array( 'template_key' => 'woocommerce:new_order', 'provider' => 'woocommerce', 'label' => 'New Order' );
$blocked = $health->send_test( $woo, 'owner@example.test', $store, $renderer );
$assert( empty( $blocked['success'] ) && 'native_template_required' === $blocked['error_code'] && $before === count( $GLOBALS['p6_mail_calls'] ), 'Woo test must stay outside Email OS Native test send' );

$store->published = false;
$missing = $health->send_test( $identity, 'owner@example.test', $store, $renderer );
$assert( empty( $missing['success'] ) && 'published_snapshot_required' === $missing['error_code'] && $before === count( $GLOBALS['p6_mail_calls'] ), 'unpublished Native template must not send' );

$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-email-health-center.php' );
$assert( false === strpos( $source, 'WPMS_SMTP_PASS' ) && false === strpos( $source, 'WPMS_SENDGRID_API_KEY' ) && false === strpos( $source, 'CLIENT_SECRET' ), 'P6 must not read transport secrets' );
$assert( false === strpos( $source, 'woocommerce_email_' ), 'P6 must not take over Woo runtime' );
echo "PASS email-os-health-center-harness\n";
