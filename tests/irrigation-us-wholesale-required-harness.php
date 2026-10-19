<?php
/**
 * Standalone US wholesale inquiry email-required contract regression.
 *
 * Run: php tests/irrigation-us-wholesale-required-harness.php
 * This harness never submits a real lead.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( (string) $value ); }
function get_option( $key, $default = null ) {
    return 'yby_inquiry_presets' === $key ? array() : $default;
}
function apply_filters( $tag, $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_url( $value ) { return (string) $value; }
function __( $value, $domain = '' ) { return $value; }
function is_email( $value ) { return (bool) filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function must( $check, $message ) {
    if ( ! $check ) { fwrite( STDERR, "FAIL: " . $message . "\n" ); exit( 1 ); }
}
$root = dirname( __DIR__ );
require_once $root . '/inc/class-yby-inquiry-field-manager.php';
require_once $root . '/inc/class-yby-inquiry-preset-manager.php';
require_once $root . '/inc/class-yby-inquiry-lead-mapper.php';
require_once $root . '/inc/class-yby-inquiry-renderer.php';
require_once $root . '/inc/class-yby-lead-rest-controller.php';

$fields = new YBY_Inquiry_Field_Manager();
$presets = new YBY_Inquiry_Preset_Manager( $fields );
$preset = $presets->get_preset( 'irrigation_us_wholesale' );
must( is_array( $preset ), 'US preset backfills existing stored presets' );
must( 'email' === $preset['validation']['contact_requirement'], 'US contact rule is email-only' );
must( in_array( 'email', $preset['mobile_fields'], true ), 'mobile view has a real email field' );
must( ! in_array( 'contact', $preset['mobile_fields'], true ), 'mobile does not substitute email/whatsapp contact' );
must( $presets->is_preset_allowed_for_profile( 'irrigation_us_wholesale', 'irrigation_wholesale' ), 'wholesale profile allowed' );
must( ! $presets->is_preset_allowed_for_profile( 'irrigation_us_wholesale', 'bottle_oem' ), 'unrelated profile rejected' );
must( 'email_or_whatsapp' === $presets->get_preset( 'page_owned_inquiry' )['validation']['contact_requirement'], 'other presets unchanged' );

$renderer = new YBY_Inquiry_Renderer();
$desktop = array_map( array( $fields, 'get_field' ), $preset['fields'] );
$mobile  = array_map( array( $fields, 'get_field' ), $preset['mobile_fields'] );
$html = $renderer->render_modal( $preset, $desktop, array(
    'id' => 'us-wholesale-email-test',
    'title' => 'Request a Wholesale Quote',
    'layout' => 'split_visual',
    'class' => array( 'us-wholesale-modal-v2' ),
), $mobile );
must( false !== strpos( $html, 'data-yby-contact-requirement="email"' ), 'client-side email validation enabled' );
must( false !== strpos( $html, 'us-wholesale-modal-v2' ), 'isolated CSS hook enabled' );
must( 2 === preg_match_all( '/<input[^>]*data-yby-field-id="email"[^>]*required\b/', $html ), 'email required on desktop and mobile' );
must( 2 === preg_match_all( '/<input[^>]*data-yby-field-id="email"[^>]*aria-required="true"/', $html ), 'email accessibility requirement on both layouts' );

$controller = new YBY_Lead_REST_Controller();
$method = new ReflectionMethod( $controller, 'validate_lead' );
$base = array(
    'name' => 'Synthetic test buyer',
    'source_preset' => 'irrigation_us_wholesale',
    'page_profile' => 'irrigation_wholesale',
    'email' => '',
    'whatsapp' => '+12025550123',
);
$errors = $method->invoke( $controller, $base );
must( isset( $errors['email'] ), 'REST rejects WhatsApp-only for email-required preset' );
$valid = $base;
$valid['email'] = 'synthetic@example.test';
$errors = $method->invoke( $controller, $valid );
must( ! isset( $errors['email'] ), 'REST accepts valid email' );
$legacy = $base;
$legacy['source_preset'] = 'page_owned_inquiry';
$errors = $method->invoke( $controller, $legacy );
must( ! isset( $errors['email'] ), 'legacy page-owned still accepts WhatsApp-only' );

echo "PASS: US wholesale email-required contract (preset, desktop/mobile, REST and legacy)\n";
