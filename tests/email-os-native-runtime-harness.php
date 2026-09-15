<?php

define( 'ABSPATH', __DIR__ . '/' );
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_email( $v ) { return filter_var( (string) $v, FILTER_SANITIZE_EMAIL ); }
function esc_url_raw( $v ) { return filter_var( (string) $v, FILTER_SANITIZE_URL ); }
function wp_login_url() { return 'https://example.test/wp-login.php'; }
function network_site_url( $path = '', $scheme = null ) { return 'https://example.test/' . ltrim( $path, '/' ); }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function is_multisite() { return false; }
function get_option( $key ) { return 'Example Site'; }
function wp_specialchars_decode( $v, $flags = ENT_QUOTES ) { return html_entity_decode( (string) $v, $flags ); }

class YBY_Email_Template_Store {
	public $snapshots = array();
	public static function is_native_provider( $provider ) { return in_array( $provider, array( 'wordpress', 'andy_core' ), true ); }
	public function get_published_snapshot( $identity ) { return $this->snapshots[ $identity['template_key'] ] ?? array(); }
}
class YBY_Email_Design_Settings { public static function get() { return array(); } }
class P5B_Registry {
	public function discover() {
		return array(
			'wordpress:new_user' => array( 'template_key' => 'wordpress:new_user', 'provider' => 'wordpress' ),
			'wordpress:reset_password' => array( 'template_key' => 'wordpress:reset_password', 'provider' => 'wordpress' ),
			'andy_core:inquiry_internal_notification' => array( 'template_key' => 'andy_core:inquiry_internal_notification', 'provider' => 'andy_core' ),
			'woocommerce:new_order' => array( 'template_key' => 'woocommerce:new_order', 'provider' => 'woocommerce' ),
		);
	}
}
class P5B_Renderer {
	public $contexts = array();
	public function render( $payload, $context, $design ) {
		$this->contexts[] = $context;
		return array(
			'valid' => empty( $payload['invalid'] ),
			'diagnostics' => empty( $payload['invalid'] ) ? array() : array( 'invalid' ),
			'subject' => (string) ( $payload['subject'] ?? 'Subject' ),
			'html' => '<strong>' . (string) ( $payload['body'] ?? 'HTML' ) . '</strong>',
			'text' => (string) ( $payload['body'] ?? 'TEXT' ),
		);
	}
}
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-runtime.php';
$store = new YBY_Email_Template_Store();
$renderer = new P5B_Renderer();
$runtime = new YBY_Email_Template_Runtime( new P5B_Registry(), $store, $renderer );
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$base_email = array( 'to' => 'alex@example.test', 'subject' => '[%s] Login Details', 'message' => "Set password:\nhttps://example.test/wp-login.php?key=abc&action=rp", 'headers' => 'X-Test: 1' );
$user = (object) array( 'user_login' => 'alex', 'user_email' => 'alex@example.test' );
$assert( $base_email === $runtime->filter_new_user_notification_email( $base_email, $user, 'Example Site' ), 'Unpublished new-user template must fall back unchanged' );

$store->snapshots['wordpress:new_user'] = array( 'payload' => array( 'subject' => 'Welcome 100%', 'body' => 'NEWUSER' ), 'version_number' => 3, 'content_hash' => str_repeat( 'a', 64 ) );
$new_user = $runtime->filter_new_user_notification_email( $base_email, $user, 'Example Site' );
$assert( 'Welcome 100%%' === $new_user['subject'], 'New-user subject must survive core sprintf safely' );
$assert( false !== strpos( $new_user['message'], 'NEWUSER' ), 'Published new-user HTML not applied' );
$assert( in_array( 'X-Test: 1', $new_user['headers'], true ) && in_array( 'Content-Type: text/html; charset=UTF-8', $new_user['headers'], true ), 'New-user HTML headers not preserved/appended' );
$ctx = $renderer->contexts[ count( $renderer->contexts ) - 1 ];
$assert( 'alex' === $ctx['user_login'] && false !== strpos( $ctx['set_password_url'], 'key=abc' ), 'New-user runtime context is incomplete' );

$store->snapshots['wordpress:reset_password'] = array( 'payload' => array( 'subject' => 'Reset now', 'body' => 'RESET' ), 'version_number' => 2, 'content_hash' => str_repeat( 'b', 64 ) );
$reset = $runtime->filter_reset_password_notification_email( array( 'to' => 'alex@example.test', 'subject' => 'old', 'message' => 'old', 'headers' => '' ), 'reset-key', 'alex', $user );
$assert( 'Reset now' === $reset['subject'] && false !== strpos( $reset['message'], 'RESET' ), 'Published reset-password template not applied' );
$ctx = $renderer->contexts[ count( $renderer->contexts ) - 1 ];
$assert( false !== strpos( $ctx['reset_url'], 'key=reset-key' ) && 'alex' === $ctx['user_login'], 'Reset runtime context is incomplete' );

$store->snapshots['andy_core:inquiry_internal_notification'] = array( 'payload' => array( 'subject' => 'Inquiry runtime', 'body' => 'INQUIRY' ), 'version_number' => 5, 'content_hash' => str_repeat( 'c', 64 ) );
$inquiry = $runtime->render_inquiry_notification( array( 'case_id' => 'YBY-001', 'name' => 'Buyer', 'company' => 'ACME', 'email' => 'buyer@example.test', 'product_interest' => 'Kit', 'quantity' => '50' ) );
$assert( ! empty( $inquiry['active'] ) && 5 === (int) $inquiry['version_number'], 'Published inquiry runtime snapshot not activated' );
$ctx = $renderer->contexts[ count( $renderer->contexts ) - 1 ];
$assert( 'YBY-001' === $ctx['inquiry']['case_id'] && false !== strpos( $ctx['admin_inquiry_url'], 'andy-core-leads' ), 'Inquiry runtime context is incomplete' );

$woo = $runtime->render_published( 'woocommerce:new_order', array() );
$assert( empty( $woo['active'] ) && 'unsupported_template' === $woo['reason'], 'Woo runtime must remain excluded' );

echo "PASS email-os-native-runtime-harness\n";
