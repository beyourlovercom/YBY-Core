<?php
/** Site-isolation and end-to-end contract harness, no WP installation or real leads. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
$GLOBALS['yby_test_site'] = 'irrigation';
$GLOBALS['yby_test_options'] = array( 'irrigation' => array(), 'bottle' => array() );
$GLOBALS['yby_test_filters'] = array();
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $s ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
function sanitize_email( $s ) { return trim( (string) $s ); }
function esc_url_raw( $s ) { return (string) $s; }
function esc_url( $s ) { return (string) $s; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr__( $s, $d = '' ) { return $s; }
function __( $s, $d = '' ) { return $s; }
function absint( $n ) { return abs( (int) $n ); }
function is_email( $s ) { return (bool) filter_var( $s, FILTER_VALIDATE_EMAIL ); }
function wp_json_encode( $data ) { return json_encode( $data ); }
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['yby_test_filters'][ $hook ][] = $callback;
}
function apply_filters( $hook, $value ) {
	foreach ( $GLOBALS['yby_test_filters'][ $hook ] ?? array() as $callback ) {
		$value = call_user_func( $callback, $value );
	}
	return $value;
}
function get_option( $key, $default = null ) {
	return $GLOBALS['yby_test_options'][ $GLOBALS['yby_test_site'] ][ $key ] ?? $default;
}
function must( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL ' . $message . "\n" ); exit( 1 ); }
}
$base = dirname( __DIR__ );
require_once $base . '/inc/class-yby-inquiry-field-manager.php';
require_once $base . '/inc/class-yby-inquiry-preset-manager.php';
require_once $base . '/inc/class-yby-inquiry-lead-mapper.php';
require_once $base . '/inc/class-yby-inquiry-renderer.php';
require_once $base . '/inc/class-yby-popup-field-studio.php';
require_once $base . '/inc/class-yby-lead-rest-controller.php';

YBY_Popup_Field_Studio::boot();
$raw = array(
	'fields' => array(
		'site_farm_size' => array(
			'id' => 'site_farm_size', 'type' => 'select', 'label' => 'Farm Size',
			'placeholder' => 'Select farm size', 'options' => array( 'Under 1 ha', '1–5 ha', '5–20 ha' ),
		),
		'email' => array( 'id' => 'email', 'type' => 'text', 'label' => 'Malicious override' ),
		'site_unsafe' => array( 'id' => 'site_unsafe', 'type' => 'script', 'label' => 'Unsafe' ),
	),
	'presets' => array(
		'site_us_wholesale' => array(
			'id' => 'site_us_wholesale', 'label' => 'Irrigation US Wholesale', 'enabled' => true,
			'layout' => 'split_visual', 'title' => 'Request a Wholesale Quote',
			'subtitle' => 'Tell us what you need.', 'image' => 'https://example.test/irrigation.webp',
			'page_profiles' => array( 'irrigation_wholesale' ),
			'configs' => array(
				'name' => array(
					'desktop' => array( 'enabled' => true, 'required' => true, 'label' => 'Name', 'placeholder' => 'Your name' ),
					'mobile' => array( 'enabled' => true, 'required' => true, 'label' => 'Name', 'placeholder' => 'Your name' ),
				),
				'email' => array(
					'desktop' => array( 'enabled' => true, 'required' => true, 'label' => 'Email', 'placeholder' => 'Business email' ),
					'mobile' => array( 'enabled' => true, 'required' => true, 'label' => 'Email', 'placeholder' => 'Email address' ),
				),
				'site_farm_size' => array(
					'desktop' => array( 'enabled' => true, 'required' => true, 'label' => 'Farm Size', 'placeholder' => 'Select farm size' ),
					'mobile' => array( 'enabled' => false, 'required' => true, 'label' => 'Farm Size', 'placeholder' => 'Farm size' ),
				),
				'quantity' => array(
					'desktop' => array( 'enabled' => true, 'required' => false, 'label' => 'Quantity', 'placeholder' => 'Qty' ),
					'mobile' => array( 'enabled' => true, 'required' => true, 'label' => 'Quantity', 'placeholder' => 'Quantity *' ),
				),
				'message' => array(
					'desktop' => array( 'enabled' => true, 'required' => false, 'label' => 'Project Details', 'placeholder' => 'Message' ),
					'mobile' => array( 'enabled' => true, 'required' => false, 'label' => 'Message', 'placeholder' => 'Details' ),
				),
			),
		),
	),
);
$clean = YBY_Popup_Field_Studio::sanitize( $raw );
must( isset( $clean['fields']['site_farm_size'] ), 'site field whitelisted' );
must( ! isset( $clean['fields']['email'] ), 'built-in field collision rejected' );
must( ! isset( $clean['fields']['site_unsafe'] ), 'unsupported field type rejected' );
must( count( $clean['fields']['site_farm_size']['options'] ) === 3, 'select options retained' );
must( $clean['presets']['site_us_wholesale']['configs']['site_farm_size']['mobile']['required'] === false, 'disabled field cannot be required' );
$GLOBALS['yby_test_options']['irrigation'][YBY_Popup_Field_Studio::OPTION] = $clean;
$fm = new YBY_Inquiry_Field_Manager();
$pm = new YBY_Inquiry_Preset_Manager( $fm );
$site_fields = $fm->get_fields();
must( isset( $site_fields['site_farm_size'] ), 'Irrigation field registered into runtime' );
must( $site_fields['site_farm_size']['type'] === 'select', 'site field type' );
must( $site_fields['email']['type'] === 'email', 'global field definition unchanged' );
$preset = $pm->get_preset( 'site_us_wholesale' );
must( is_array( $preset ) && in_array( 'site_farm_size', $preset['fields'], true ), 'site preset registered with custom field' );
must( ! in_array( 'site_farm_size', $preset['mobile_fields'], true ), 'mobile hides Farm Size' );
must( $pm->is_preset_allowed_for_profile( 'site_us_wholesale', 'irrigation_wholesale' ), 'allowed profile' );
must( ! $pm->is_preset_allowed_for_profile( 'site_us_wholesale', 'bottle_oem' ), 'other profile denied' );
must( $pm->get_preset( 'page_owned_inquiry' ) !== null, 'legacy default untouched' );

$render = new YBY_Inquiry_Renderer();
$attrs = YBY_Popup_Field_Studio::render_attributes( 'site_us_wholesale' );
$attrs['id'] = 'test-site-inquiry';
$desktop = array_map( array( $fm, 'get_field' ), YBY_Popup_Field_Studio::desktop_fields( $preset['id'] ) );
$mobile = array_map( array( $fm, 'get_field' ), $preset['mobile_fields'] );
$html = $render->render_modal( $preset, $desktop, $attrs, $mobile );
must( false !== strpos( $html, 'yby-popup-field-studio' ), 'site Studio activates per-device contract' );
must( 2 === preg_match_all( '/<input[^>]+data-yby-field-id="email"[^>]+required\b/', $html ), 'Email required on both modes' );
must( 1 === preg_match_all( '/data-yby-field-wrapper="site_farm_size"/', $html ), 'Farm Size desktop only' );
must( false !== strpos( $html, 'Select farm size' ), 'custom field placeholder' );

$rest = new YBY_Lead_REST_Controller();
$validate = new ReflectionMethod( $rest, 'validate_lead' );
$lead = array(
	'name' => 'Synthetic buyer', 'source_preset' => 'site_us_wholesale',
	'page_profile' => 'irrigation_wholesale', 'email' => '', 'whatsapp' => '+12025550123',
	'form_device' => 'desktop', 'quantity' => '', 'project_details' => '',
	'custom_fields' => array(),
);
$errors = $validate->invoke( $rest, $lead );
must( isset( $errors['email'] ), 'desktop Email enforced by REST' );
must( isset( $errors['site_farm_size'] ), 'desktop Farm Size enforced by REST' );
$lead['email'] = 'buyer@example.test';
$lead['custom_fields'] = array( 'site_farm_size' => '1–5 ha' );
$errors = $validate->invoke( $rest, $lead );
must( ! isset( $errors['email'] ) && ! isset( $errors['site_farm_size'] ), 'valid desktop data passes' );
$lead['form_device'] = 'mobile';$lead['custom_fields'] = array();
$errors = $validate->invoke( $rest, $lead );
must( ! isset( $errors['site_farm_size'] ) && isset( $errors['quantity'] ), 'mobile required differs safely' );
$lead['quantity'] = '100';
$errors = $validate->invoke( $rest, $lead );
must( ! isset( $errors['quantity'] ), 'mobile required Quantity passes' );
$legacy = $lead;$legacy['source_preset'] = 'page_owned_inquiry';$legacy['email']='';
must( ! isset( $validate->invoke( $rest, $legacy )['email'] ), 'legacy WhatsApp-only form remains valid' );

$GLOBALS['yby_test_site'] = 'bottle';
must( ! isset( ( new YBY_Inquiry_Field_Manager() )->get_fields()['site_farm_size'] ), 'site isolation: Bottle has no Farm Size' );
must( null === ( new YBY_Inquiry_Preset_Manager() )->get_preset( 'site_us_wholesale' ), 'site isolation: Bottle has no Irrigation preset' );
must( ( new YBY_Inquiry_Preset_Manager() )->get_preset( 'page_owned_inquiry' ) !== null, 'other site original presets intact' );

echo "PASS Popup Field Studio V2: strict site isolation, field library, device matrix, REST and legacy\n";
