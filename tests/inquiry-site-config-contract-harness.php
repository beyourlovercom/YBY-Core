<?php
/**
 * Generic, site-configured inquiry preset contract.
 *
 * Run: php tests/inquiry-site-config-contract-harness.php
 * All leads are synthetic and never reach a WordPress REST endpoint.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_textarea_field( $value ) { return trim( (string) $value ); }
function get_option( $key, $default = null ) {
    return 'yby_inquiry_presets' === $key ? array() : $default;
}
function apply_filters( $tag, $presets ) {
    if ( 'yby_inquiry_presets' !== $tag ) { return $presets; }
    // This belongs in the website theme/site plugin, NOT Andy Core defaults.
    $presets['demo_email_required'] = array(
        'id' => 'demo_email_required', 'label' => 'Site-specific B2B Quote',
        'enabled' => true, 'version' => '1.0',
        'fields' => array( 'name', 'company', 'email', 'whatsapp', 'country', 'product_interest', 'quantity', 'message' ),
        'mobile_fields' => array( 'name', 'email', 'whatsapp', 'country', 'product_interest', 'quantity', 'message' ),
        'validation' => array( 'contact_requirement' => 'email' ),
        'source_component' => 'inquiry_modal',
        'page_profiles' => array( 'demo_business_page' ),
    );
    $presets['demo_whatsapp_required'] = array(
        'id' => 'demo_whatsapp_required', 'label' => 'WhatsApp First',
        'enabled' => true, 'version' => '1.0',
        'fields' => array( 'name', 'email', 'whatsapp' ),
        'mobile_fields' => array( 'name', 'email', 'whatsapp' ),
        'validation' => array( 'contact_requirement' => 'whatsapp' ),
    );
    $presets['demo_contact_optional'] = array(
        'id' => 'demo_contact_optional', 'label' => 'Contact Optional',
        'enabled' => true, 'version' => '1.0',
        'fields' => array( 'name', 'email', 'whatsapp' ),
        'mobile_fields' => array( 'name', 'email', 'whatsapp' ),
        'validation' => array( 'contact_requirement' => 'none' ),
    );
    return $presets;
}
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_url( $value ) { return (string) $value; }
function __( $value, $domain = '' ) { return $value; }
function esc_attr__( $value, $domain = '' ) { return $value; }
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

$fields  = new YBY_Inquiry_Field_Manager();
$manager = new YBY_Inquiry_Preset_Manager( $fields );
$defaults = $manager->defaults();
must( ! isset( $defaults['irrigation_us_wholesale'] ), 'shared Core has no US-specific preset' );
must( ! isset( $defaults['demo_email_required'] ), 'site-owned preset not baked into shared defaults' );
$site = $manager->get_preset( 'demo_email_required' );
must( is_array( $site ), 'site-provided preset resolves through public filter' );
must( 'email' === $site['validation']['contact_requirement'], 'Email requirement remains server-trusted' );
must( $manager->is_preset_allowed_for_profile( 'demo_email_required', 'demo_business_page' ), 'site profile is allowed' );
must( ! $manager->is_preset_allowed_for_profile( 'demo_email_required', 'other_site' ), 'unrelated profile denied' );
must( in_array( 'email', $site['mobile_fields'], true ), 'mobile has distinct Email field' );
must( ! in_array( 'contact', $site['mobile_fields'], true ), 'no combined contact field bypass' );
must( 'email_or_whatsapp' === $manager->get_preset( 'page_owned_inquiry' )['validation']['contact_requirement'], 'legacy global preset unchanged' );

$renderer = new YBY_Inquiry_Renderer();
$desktop = array_map( array( $fields, 'get_field' ), $site['fields'] );
$mobile = array_map( array( $fields, 'get_field' ), $site['mobile_fields'] );
$markup = $renderer->render_modal( $site, $desktop, array(
    'id' => 'demo-site-email-modal',
    'title' => 'Request a Quote',
    'layout' => 'split_visual',
    'class' => array( 'demo-brand-quote' ),
), $mobile );
must( false !== strpos( $markup, 'data-yby-contact-requirement="email"' ), 'frontend mode reflects trusted preset' );
must( false !== strpos( $markup, 'demo-brand-quote' ), 'website-specific class stays per-site' );
must( 2 === preg_match_all( '/<input[^>]*data-yby-field-id="email"[^>]*required\b/', $markup ), 'Email required on desktop and mobile' );
must( 2 === preg_match_all( '/<input[^>]*data-yby-field-id="email"[^>]*aria-required="true"/', $markup ), 'accessibility required on both layouts' );

$rest = new YBY_Lead_REST_Controller();
$validate = new ReflectionMethod( $rest, 'validate_lead' );
$base = array(
    'name' => 'Synthetic B2B buyer', 'source_preset' => 'demo_email_required',
    'page_profile' => 'demo_business_page', 'email' => '',
    'whatsapp' => '+12025550123',
);
$errors = $validate->invoke( $rest, $base );
must( isset( $errors['email'] ), 'email-required site rejects WhatsApp-only' );
$good = $base;
$good['email'] = 'synthetic@example.test';
$errors = $validate->invoke( $rest, $good );
must( ! isset( $errors['email'] ), 'valid Email passes site rule' );

$legacy = $base;
$legacy['source_preset'] = 'page_owned_inquiry';
must( ! isset( $validate->invoke( $rest, $legacy )['email'] ), 'legacy accepts WhatsApp-only' );
$whatsapp = $base;
$whatsapp['source_preset'] = 'demo_whatsapp_required';
$whatsapp['whatsapp'] = '';
must( isset( $validate->invoke( $rest, $whatsapp )['whatsapp'] ), 'WhatsApp-required mode enforced' );
$whatsapp['whatsapp'] = '+12025550123';
must( ! isset( $validate->invoke( $rest, $whatsapp )['whatsapp'] ), 'valid WhatsApp passes' );
$optional = $base;
$optional['source_preset'] = 'demo_contact_optional';
$optional['whatsapp'] = '';
must( ! isset( $validate->invoke( $rest, $optional )['email'] ), 'none mode permits no contact' );
$optional['email'] = 'invalid-address';
must( isset( $validate->invoke( $rest, $optional )['email'] ), 'supplied malformed Email rejected in none mode' );

echo "PASS: site-configured inquiry modes / required markup / profile isolation / legacy compatibility\n";
