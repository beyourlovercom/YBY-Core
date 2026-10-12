<?php
/**
 * Offline test of the Marketing / Promotions read-only screen.
 * No WordPress bootstrap, DB, Woo cart, orders, payments or outbound calls.
 */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
if ( ! defined( 'ANDY_COMMERCE_PLUGIN_DIR' ) ) { define( 'ANDY_COMMERCE_PLUGIN_DIR', dirname( __DIR__ ) . '/' ); }
$GLOBALS['is_authorized'] = true;

function current_user_can( $capability ) { return 'manage_woocommerce' === $capability && $GLOBALS['is_authorized']; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ); }
function wp_unslash( $value ) { return stripslashes( (string) $value ); }
function wp_die( $message ) { throw new RuntimeException( (string) $message ); }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $pairs, $base ) { return $base . '?' . http_build_query( $pairs ); }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html_e( $value, $domain = '' ) { echo htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html__( $value, $domain = '' ) { return $value; }
function __( $value, $domain = '' ) { return $value; }
function add_action() { throw new RuntimeException( 'unexpected runtime registration' ); }
function add_filter() { throw new RuntimeException( 'unexpected filter registration' ); }
function update_option() { throw new RuntimeException( 'unexpected option write' ); }
function wp_insert_post() { throw new RuntimeException( 'unexpected WP content write' ); }

require ANDY_COMMERCE_PLUGIN_DIR . 'admin/class-andy-commerce-admin.php';
$assert = static function ( bool $condition, string $message ): void {
    if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};
$_GET['tab'] = 'marketing-promotions';
ob_start();
( new Andy_Commerce_Admin() )->render_page();
$view = (string) ob_get_clean();
$assert( str_contains( $view, 'Marketing / Promotions' ), 'marketing tab missing' );
foreach ( array( 'BOGO', 'Bundles', 'Spend &amp; Save', 'Tiered Discounts', 'Free Gift', 'Free Shipping Progress' ) as $type ) {
    $assert( str_contains( $view, $type ), "campaign type missing: {$type}" );
}
$assert( substr_count( $view, '<strong>OFF</strong>' ) === 6, 'every category must be off' );
$assert( str_contains( $view, 'Design preview only' ), 'screen must disclose draft-only design' );
$assert( str_contains( $view, 'post_discount_merchandise_subtotal_excluding_tax_and_shipping' ), 'owner money basis absent' );
$assert( str_contains( $view, 'not yet applied to existing BYL49' ), 'legacy policy change incorrectly implied' );
$assert( ! preg_match( '/<form\b|<input\b|<button\b|<script\b|submit_button|admin-post\.php/i', $view ), 'no active forms or publish controls allowed' );
foreach ( array( 'update_option(', 'woocommerce_before_calculate_totals', 'woocommerce_cart_calculate_fees', 'woocommerce_apply_coupon' ) as $needle ) {
    $sources = file_get_contents( ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-promotions.php' );
    $assert( ! str_contains( $sources, $needle ), 'view may not execute promotions or settings: ' . $needle );
}
$GLOBALS['is_authorized'] = false;
$blocked = false;
try { ( new Andy_Commerce_Admin() )->render_page(); }
catch ( RuntimeException $e ) { $blocked = str_contains( $e->getMessage(), 'permission' ); }
$assert( $blocked, 'non-woocommerce managers must be blocked' );
echo "PASS Andy Commerce Marketing V1 read-only admin, permission, all promotions OFF and no write controls\n";
