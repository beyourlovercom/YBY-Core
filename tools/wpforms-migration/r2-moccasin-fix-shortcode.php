<?php
/**
 * R2 pilot-only shortcode compatibility workaround.
 * Existing Andy Core v2.3.3 modal shortcode may resolve zero custom Studio
 * fields when desktop_fields is omitted (string passed to array-only resolver).
 * Keep the fix in the test page, not in the shared production plugin.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( 'moccasin-badger-830954.hostingersite.com' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
	WP_CLI::error( 'Wrong R2 site.' );
}
$post = get_post( 555 );
if ( ! $post || 'page' !== $post->post_type
	|| 'andy-core-r2-moccasin-429-20261112' !== $post->post_name
	|| '' === $post->post_password ) {
	WP_CLI::error( 'Password-protected pilot preview is missing.' );
}
$original = '[yby_inquiry_modal preset="site_wpforms_429" id="andy-r2-moccasin-429" title="Get in Touch" subtitle="Visual UAT — submissions disabled"]';
$replacement = '[yby_inquiry_modal preset="site_wpforms_429" id="andy-r2-moccasin-429" title="Get in Touch" subtitle="Visual UAT — submissions disabled" desktop_fields="name,email,site_wpf429_f3,site_wpf429_f2" mobile_fields="name,email,site_wpf429_f3,site_wpf429_f2"]';
if ( 1 !== substr_count( $post->post_content, $original ) ) {
	WP_CLI::error( 'Source shortcode does not match exactly; no write.' );
}
$preview = do_shortcode( $replacement );
if ( '' === $preview || false === strpos( $preview, 'yby_inquiry_modal:' ) ) {
	WP_CLI::error( 'Replacement shortcode still fails; no write.' );
}
$changed = wp_update_post( array(
	'ID' => (int) $post->ID,
	'post_content' => str_replace( $original, $replacement, $post->post_content ),
), true );
if ( is_wp_error( $changed ) || ! $changed ) {
	WP_CLI::error( 'Preview-only post update failed.' );
}
WP_CLI::success( 'R2_SHORTCODE_EXPLICIT_FIELDS_APPLIED; preview only, form submission veto intact.' );
