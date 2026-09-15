<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
$GLOBALS['ux_options'] = array();
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_-]/i', '', (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function wp_unslash( $v ) { return $v; }
function current_user_can( $cap ) { return 'andy_core_settings_manage' === $cap; }
function check_admin_referer( $action, $field ) { return true; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['ux_options'] ) ? $GLOBALS['ux_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['ux_options'][ $key ] = $value; return true; }
function add_query_arg( $args, $url ) {
	$separator = false === strpos( $url, '?' ) ? '?' : '&';
	return $url . $separator . http_build_query( $args );
}
require_once dirname( __DIR__ ) . '/admin/class-yby-email-template-admin.php';
class UX_Email_Template_Admin extends YBY_Email_Template_Admin {
	public function save_note_for_test( $templates ) { return $this->handle_registry_note_action( $templates ); }
}
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$admin = new UX_Email_Template_Admin();
$base = 'https://example.test/wp-admin/admin.php?page=yby-core-popups&tab=email_templates';$woo = array( 'provider' => 'woocommerce', 'template_key' => 'woocommerce:new_order', 'editor_url' => 'https://example.test/wp-admin/post.php?post=99&action=edit', 'settings_url' => 'https://example.test/wp-admin/admin.php?page=wc-settings' );
$wp = array( 'provider' => 'wordpress', 'template_key' => 'wordpress:new_user' );
$andy = array( 'provider' => 'andy_core', 'template_key' => 'andy_core:inquiry_internal_notification' );
$assert( $woo['editor_url'] === $admin->template_editor_url( $woo, $base ), 'Woo template must open current editor' );
$wp_url = $admin->template_editor_url( $wp, $base );
$andy_url = $admin->template_editor_url( $andy, $base );
$assert( false !== strpos( $wp_url, 'email_section=wordpress' ) && false !== strpos( $wp_url, 'template_key=wordpress%3Anew_user' ), 'WordPress editor deep-link missing' );
$assert( false !== strpos( $andy_url, 'email_section=andy-core' ) && false !== strpos( $andy_url, 'template_key=andy_core%3Ainquiry_internal_notification' ), 'Andy Core editor deep-link missing' );
$templates = array( 'wordpress:new_user' => $wp );
$_POST = array( 'registry_template_key' => 'wordpress:new_user', 'registry_note' => '新用户注册后发送账号信息' );
$result = $admin->save_note_for_test( $templates );
$notes = $admin->owner_notes();
$assert( 'success' === $result['type'] && '新用户注册后发送账号信息' === $notes['wordpress:new_user'], 'Owner note save failed' );
$_POST['registry_note'] = '';
$admin->save_note_for_test( $templates );
$assert( ! isset( $admin->owner_notes()['wordpress:new_user'] ), 'Empty note must clear Owner metadata' );$view = file_get_contents( dirname( __DIR__ ) . '/admin/views/email-template-settings.php' );
$runtime = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-email-template-runtime.php' );
$assert( false !== strpos( $view, "esc_html_e( '备注'" ), 'Registry note column missing' );
$assert( false !== strpos( $view, 'yby-email-os__template-link' ), 'Clickable template-name UI missing' );
$assert( false !== strpos( $view, 'yby_email_registry_note_action' ), 'Owner note save control missing' );
$assert( false !== strpos( $view, 'template_editor_url( $template, $base_url )' ), 'Overview editor routing missing' );
$assert( false === strpos( $runtime, 'OWNER_NOTES_OPTION' ) && false === strpos( $runtime, 'owner_notes' ), 'Owner notes must never enter Native Runtime' );
echo "PASS email-os-registry-ux-harness\n";
