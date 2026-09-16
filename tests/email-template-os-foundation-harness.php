<?php
/**
 * Email Template OS P3 foundation contract harness.
 */

define( 'ABSPATH', __DIR__ . '/' );

if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $v ) { return trim( wp_strip_all_tags( (string) $v ) ); } }
if ( ! function_exists( 'sanitize_hex_color' ) ) { function sanitize_hex_color( $v ) { return preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $v ) ? (string) $v : null; } }
if ( ! function_exists( 'absint' ) ) { function absint( $v ) { return abs( (int) $v ); } }
if ( ! function_exists( 'esc_url_raw' ) ) { function esc_url_raw( $v ) { return filter_var( (string) $v, FILTER_VALIDATE_URL ) ? (string) $v : ''; } }
if ( ! function_exists( 'esc_url' ) ) { function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'wp_strip_all_tags' ) ) { function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); } }
if ( ! function_exists( 'wp_parse_args' ) ) { function wp_parse_args( $a, $d = array() ) { return array_merge( $d, is_array( $a ) ? $a : array() ); } }
if ( ! function_exists( 'get_option' ) ) { function get_option( $key, $default = false ) { return $default; } }

require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-schema.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-email-design-settings.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-renderer.php';

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$assert( '1.5.0' === YBY_Email_Template_Schema::DATABASE_VERSION, 'schema target must be 1.5.0' );
$assert( array( 'draft', 'published', 'disabled' ) === YBY_Email_Template_Schema::statuses(), 'publish states changed' );
$assert( '#9B3749' === YBY_Email_Template_Schema::design_defaults()['primary_color'], 'owner-approved primary color changed' );
$assert( 600 === YBY_Email_Template_Schema::design_defaults()['content_width'], 'canonical email width changed' );

$registry = new YBY_Email_Template_Registry();
$items = $registry->discover();
$assert( isset( $items['wordpress:new_user'], $items['wordpress:reset_password'] ), 'WordPress V1 adapters missing' );
$assert( isset( $items['andy_core:inquiry_internal_notification'] ), 'Andy Core inquiry template missing' );
$assert( 'customer' === $items['wordpress:new_user']['audience'], 'WordPress new-user audience changed' );

$design = YBY_Email_Design_Settings::sanitize( array( 'primary_color' => '#123456', 'content_width' => 9999, 'card_radius' => -4 ) );
$assert( '#123456' === $design['primary_color'], 'valid primary color rejected' );
$assert( 720 === $design['content_width'], 'content width upper bound failed' );
$assert( 4 === $design['card_radius'], 'radius normalization failed' );

$renderer = new YBY_Email_Template_Renderer();
$payload = array(
	'subject' => 'Order {order_number}',
	'preheader' => 'Update for {customer_name}',
	'heading' => 'Thank you, {customer_name}',
	'intro_copy' => 'We are preparing order {order_number}.',
	'dynamic_sections' => array(
		array( 'label' => 'Order', 'value' => '{order_number}' ),
	),
	'primary_cta' => array( 'label' => 'View order', 'url' => '{order_url}' ),
);
$context = array( 'order_number' => '1001', 'customer_name' => 'Alex', 'order_url' => 'https://example.test/orders/1001' );
$rendered = $renderer->render( $payload, $context, YBY_Email_Template_Schema::design_defaults() );
$assert( true === $rendered['valid'], 'known-variable render must be valid' );
$assert( 'Order 1001' === $rendered['subject'], 'subject variable rendering failed' );
$assert( false !== strpos( $rendered['html'], 'Thank you, Alex' ), 'HTML renderer lost heading context' );
$assert( false !== strpos( $rendered['text'], 'View order: https://example.test/orders/1001' ), 'plain-text CTA missing' );

$invalid = $renderer->render( array( 'heading' => 'Hi {unknown_token}' ), array(), YBY_Email_Template_Schema::design_defaults() );
$assert( false === $invalid['valid'], 'unknown variable must invalidate render' );
$assert( in_array( 'unknown_variable:unknown_token', $invalid['diagnostics'], true ), 'unknown-variable diagnostic missing' );
$assert( false === strpos( $invalid['html'], '{unknown_token}' ), 'raw unknown token leaked into HTML' );

$architecture = file_get_contents( dirname( __DIR__ ) . '/docs/v1.5.8/ANDY-CORE-EMAIL-TEMPLATE-OS-V1-ARCHITECTURE.md' );
$assert( false !== strpos( $architecture, 'woocommerce-email-template-customizer' ) || false !== strpos( $architecture, 'Legacy Customizer' ), 'legacy coexist contract missing' );
$assert( false !== strpos( $architecture, 'andy-core/v1/erp' ), 'ERP read-only connector contract missing' );
$assert( false !== strpos( $architecture, 'P4 is a governance bridge, not a runtime canary.' ), 'Woo governance bridge pivot contract missing' );
$assert( false !== strpos( $architecture, 'MUST NOT register `woocommerce_email_*` runtime replacement hooks' ), 'Woo runtime takeover prohibition missing' );
$assert( false !== strpos( $architecture, 'WooCommerce runtime/editor ownership remains with WooCommerce / Mailonix / active provider' ), 'Woo editor ownership contract missing' );
$assert( false === strpos( $architecture, 'P4: Woo canary adapters / UAT' ), 'stale Woo canary roadmap remains' );

$bootstrap = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$assert( false !== strpos( $bootstrap, "class-yby-email-template-registry.php" ), 'registry bootstrap wiring missing' );
$assert( false !== strpos( $bootstrap, "class-yby-email-template-renderer.php" ), 'renderer bootstrap wiring missing' );
$assert( false !== strpos( $bootstrap, "admin/class-yby-email-template-admin.php" ), 'Email Template admin bootstrap wiring missing' );
$assert( false === strpos( $bootstrap, 'woocommerce_email_' ), 'Woo runtime override must remain absent under Email OS V1.1 bridge architecture' );
$assert( false !== strpos( $bootstrap, 'wp_new_user_notification_email' ) && false !== strpos( $bootstrap, 'retrieve_password_notification_email' ), 'WordPress native runtime adapter hooks missing' );
$database = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-database.php' );
$assert( false !== strpos( $database, 'yby_email_templates' ) && false !== strpos( $database, 'yby_email_template_versions' ), 'Email Template OS DB ownership missing' );
$admin = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-admin.php' );
$popup_admin = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-popup-admin.php' );
$popup_view = file_get_contents( dirname( __DIR__ ) . '/admin/views/popup-settings.php' );
$settings_view = file_get_contents( dirname( __DIR__ ) . '/admin/views/settings-page.php' );
$assert( false !== strpos( $popup_admin, "'email_templates'" ) && false !== strpos( $popup_view, "'email_templates' => '邮件模板'" ), 'Email OS Email Template route missing' );
$assert( false !== strpos( $popup_view, '<h1>Email OS</h1>' ) && false === strpos( $popup_view, '统一管理网站 Email 弹窗、短代码和后续营销能力。' ), 'Email OS compact header contract failed' );
$assert( false === strpos( $settings_view, "'email-templates' => '邮件模板'" ), 'Email Template must not remain a Settings navigation tab' );
$assert( false !== strpos( $admin, "'email-templates' => 'email_templates'" ), 'Legacy Settings Email Template redirect missing' );

echo "PASS email-template-os-foundation-harness\n";
