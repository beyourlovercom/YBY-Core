<?php
/**
 * One-shot, site-locked R2 UAT setup. Requires:
 *  - the canonical 2.3.3 Andy Core activated with all modules initially OFF;
 *  - the R2 MU-plugin preview guard already installed;
 *  - a compressed pre-R2 database backup stored outside public_html.
 *
 * Creates only one password-protected, noindex preview page and one pilot
 * Popup Field Studio V2 preset. Original WPForms page/entries are unchanged.
 * This is NOT a production rollout or reusable automatic migrator.
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'YBY_Popup_Field_Studio' ) ) {
	WP_CLI::error( 'Andy Core not loaded or WordPress unavailable.' );
}
$host = 'moccasin-badger-830954.hostingersite.com';
if ( $host !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
	WP_CLI::error( 'Wrong site; refusing all writes.' );
}
$guard = WP_CONTENT_DIR . '/mu-plugins/andy-core-r2-moccasin-guard.php';
if ( ! is_file( $guard ) ) { WP_CLI::error( 'R2 submission veto missing.' ); }
if ( YBY_Module_Registry::enabled_modules() !== array() ) {
	WP_CLI::error( 'R2 module baseline is not empty; refusing changes.' );
}
$key = YBY_Popup_Field_Studio::OPTION;
if ( get_option( $key, '__ANDY_R2_ABSENT__' ) !== '__ANDY_R2_ABSENT__' ) {
	WP_CLI::error( 'Existing Field Studio config: no overwrite allowed.' );
}
$slug = 'andy-core-r2-moccasin-429-20261112';
if ( get_page_by_path( $slug, OBJECT, 'page' ) ) { WP_CLI::error( 'Preview page exists.' ); }

$fields = array(
	'site_wpf429_f3' => array( 'id' => 'site_wpf429_f3', 'type' => 'text', 'label' => 'Subject', 'placeholder' => '', 'options' => array() ),
	'site_wpf429_f2' => array( 'id' => 'site_wpf429_f2', 'type' => 'textarea', 'label' => 'Comment or Message', 'placeholder' => '', 'options' => array() ),
);
$configs = array();
foreach ( array(
	'name' => array( 'label' => 'First & Last Name', 'required' => true ),
	'email' => array( 'label' => 'Email Address', 'required' => true ),
	'site_wpf429_f3' => array( 'label' => 'Subject', 'required' => false ),
	'site_wpf429_f2' => array( 'label' => 'Comment or Message', 'required' => false ),
) as $id => $field ) {
	$mode = array( 'enabled' => true, 'required' => $field['required'], 'label' => $field['label'], 'placeholder' => '' );
	$configs[ $id ] = array( 'desktop' => $mode, 'mobile' => $mode );
}
$preset = array(
	'id' => 'site_wpforms_429', 'label' => 'R2 Contact Form 429',
	'enabled' => true, 'layout' => 'classic',
	'title' => 'Get in Touch', 'subtitle' => 'Andy Core R2 visual preview — submissions are disabled.',
	'image' => '', 'page_profiles' => array(), 'configs' => $configs,
);
$state = YBY_Popup_Field_Studio::sanitize( array(
	'version' => 2,
	'fields' => $fields,
	'presets' => array( 'site_wpforms_429' => $preset ),
) );
if ( count( $state['fields'] ) !== 2
	|| empty( $state['presets']['site_wpforms_429'] )
	|| empty( $state['presets']['site_wpforms_429']['enabled'] ) ) {
	WP_CLI::error( 'Studio sanitization contract failed.' );
}
if ( ! update_option( $key, $state, false ) ) {
	WP_CLI::error( 'Could not save R2 site config.' );
}

$pw = wp_generate_password( 16, false, false );
$page_content = '<p><strong>R2 visual test only.</strong> WPForms Contact page remains unchanged. '
	. 'This Andy Core preview does not send inquiries.</p>'
	. '<p><button type="button" data-yby-modal-open="andy-r2-moccasin-429">Open Andy Core Inquiry Popup</button></p>'
	. '[yby_inquiry_modal preset="site_wpforms_429" id="andy-r2-moccasin-429" title="Get in Touch" subtitle="Visual UAT — submissions disabled"]';
$page = wp_insert_post( array(
	'post_title' => 'Andy Core R2 Popup Preview — No Submissions',
	'post_name' => $slug, 'post_type' => 'page', 'post_status' => 'publish',
	'post_password' => $pw, 'post_content' => $page_content,
	'comment_status' => 'closed', 'ping_status' => 'closed',
), true );
if ( is_wp_error( $page ) || ! $page ) {
	delete_option( $key );
	WP_CLI::error( 'Page creation failed; candidate config rolled back.' );
}
update_post_meta( (int) $page, '_andy_r2_preview_only', '1' );
YBY_Module_Registry::save( array( 'inquiry_os' ) );

WP_CLI::log( 'R2_SITE=' . $host );
WP_CLI::log( 'R2_PREVIEW_ID=' . (int) $page );
WP_CLI::log( 'R2_PREVIEW_URL=' . get_permalink( $page ) );
WP_CLI::log( 'R2_PAGE_PASSWORD=' . $pw );
WP_CLI::success( 'R2_VISUAL_ONLY: source WPForms unchanged, all Andy Core lead submissions vetoed.' );
