<?php
/**
 * R2 Contact Page First pilot: create a protected INLINE form preview, not a popup.
 *
 * One-shot and site-locked. Does not modify the public Contact page or WPForms.
 * Safe only after offline site DB backup and the R2 submission blocking MU guard.
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'YBY_Popup_Field_Studio' ) ) {
	WP_CLI::error( 'Andy Core missing.' );
}
if ( 'moccasin-badger-830954.hostingersite.com' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
	WP_CLI::error( 'Wrong site.' );
}
if ( ! is_file( WP_CONTENT_DIR . '/mu-plugins/andy-core-r2-moccasin-guard.php' ) ) {
	WP_CLI::error( 'Missing server-side R2 REST submission veto.' );
}
if ( ! get_post( 213 ) || false === strpos( (string) get_post_field( 'post_content', 213 ), '[wpforms id="429"' ) ) {
	WP_CLI::error( 'Contact source changed; refusing to proceed.' );
}
$slug = 'andy-contact-first-r2-20261112';
if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
	WP_CLI::error( 'Preview exists; refuse overwrite.' );
}
$key = YBY_Popup_Field_Studio::OPTION;
$old = YBY_Popup_Field_Studio::get_state();
if ( ! isset( $old['presets']['site_wpforms_429'] ) || isset( $old['presets']['site_contact_v1'] ) ) {
	WP_CLI::error( 'Unexpected pilot Field Studio state; no writes.' );
}

$labels = array(
	'name' => array( 'Full name', true ),
	'email' => array( 'Work email', true ),
	'country' => array( 'Destination country', true ),
	'product_interest' => array( 'Product or model', false ),
	'quantity' => array( 'Estimated quantity', false ),
	'company' => array( 'Company', false ),
	'message' => array( 'Requirements', true ),
	'site_contact_consent' => array( 'I agree to be contacted about my inquiry.', true ),
);
$cfg = array();
foreach ( $labels as $id => $settings ) {
	$mode = array( 'enabled' => true, 'required' => $settings[1], 'label' => $settings[0], 'placeholder' => '' );
	$cfg[ $id ] = array( 'desktop' => $mode, 'mobile' => $mode );
}
$old['fields']['site_contact_consent'] = array(
	'id' => 'site_contact_consent', 'type' => 'checkbox',
	'label' => 'I agree to be contacted about my inquiry.',
	'placeholder' => '', 'options' => array(),
);
$old['presets']['site_contact_v1'] = array(
	'id' => 'site_contact_v1', 'label' => 'Contact Page First',
	'enabled' => true, 'layout' => 'classic',
	'title' => 'Contact Us', 'subtitle' => '',
	'image' => '', 'page_profiles' => array(), 'configs' => $cfg,
);
$clean = YBY_Popup_Field_Studio::sanitize( $old );
if ( count( $clean['presets'] ) < 2
	|| empty( $clean['presets']['site_contact_v1']['enabled'] )
	|| ! isset( $clean['presets']['site_contact_v1']['configs']['site_contact_consent'] ) ) {
	WP_CLI::error( 'Contact lite sanitizer did not preserve required fields.' );
}
$form = '[yby_inquiry preset="site_contact_v1" id="andy-contact-first-r2" '
	. 'title="Contact Us" submit_label="Send inquiry" '
	. 'desktop_fields="name,email,country,product_interest,quantity,company,message,site_contact_consent" '
	. 'mobile_fields="name,email,country,product_interest,quantity,company,message,site_contact_consent"]';
if ( ! update_option( $key, $clean, false ) ) { WP_CLI::error( 'Studio state write failed.' ); }
if ( '' === do_shortcode( $form ) ) {
	update_option( $key, $old, false );
	WP_CLI::error( 'Inline renderer emitted nothing; Studio state restored.' );
}
$password = wp_generate_password( 18, false, false );
$post = wp_insert_post( array(
	'post_title' => 'R2 Contact First — Protected Visual Preview',
	'post_type' => 'page', 'post_name' => $slug,
	'post_status' => 'publish', 'post_password' => $password,
	'post_content' => '<p>R2 visual-only preview. All submissions are disabled. The live Contact page remains WPForms.</p>' . "\n" . $form,
	'comment_status' => 'closed', 'ping_status' => 'closed',
), true );
if ( is_wp_error( $post ) || ! $post ) {
	update_option( $key, $old, false );
	WP_CLI::error( 'Page creation failed; state reverted.' );
}
update_post_meta( $post, '_andy_r2_contact_only', '1' );
WP_CLI::log( 'R2_CONTACT_PREVIEW_URL=' . get_permalink( $post ) );
WP_CLI::log( 'R2_CONTACT_PREVIEW_PASSWORD=' . $password );
WP_CLI::log( 'R2_CONTACT_PREVIEW_ID=' . (int) $post );
WP_CLI::success( 'INLINE_PREVIEW_CREATED; WPForms untouched; API submission veto remains.' );
