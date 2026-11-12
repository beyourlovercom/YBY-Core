<?php
/**
 * Read-only R2 field-validation smoke. No leads are saved and no mail is sent.
 * Invoke through WordPress CLI on the specifically approved Hostinger pilot.
 */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'YBY_Popup_Field_Studio' ) ) {
	WP_CLI::error( 'Missing Andy Core runtime.' );
}
if ( 'moccasin-badger-830954.hostingersite.com' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
	WP_CLI::error( 'Wrong pilot site.' );
}

$tests = array(
	'desktop_empty' => array( 'device' => 'desktop', 'name' => '', 'email' => '', 'want' => array( 'name', 'email' ) ),
	'mobile_empty' => array( 'device' => 'mobile', 'name' => '', 'email' => '', 'want' => array( 'name', 'email' ) ),
	'desktop_missing_email' => array( 'device' => 'desktop', 'name' => 'R2 Example', 'email' => '', 'want' => array( 'email' ) ),
	'mobile_invalid_email' => array( 'device' => 'mobile', 'name' => 'R2 Example', 'email' => 'not-email', 'want' => array( 'email' ) ),
	'desktop_valid_minimum' => array( 'device' => 'desktop', 'name' => 'R2 Example', 'email' => 'r2-test@example.invalid', 'want' => array() ),
	'mobile_valid_minimum' => array( 'device' => 'mobile', 'name' => 'R2 Example', 'email' => 'r2-test@example.invalid', 'want' => array() ),
);
foreach ( $tests as $label => $test ) {
	$errors = YBY_Popup_Field_Studio::required_errors( array(
		'source_preset' => 'site_wpforms_429',
		'form_device' => $test['device'],
		'name' => $test['name'],
		'email' => $test['email'],
		'custom_fields' => array( 'site_wpf429_f3' => '', 'site_wpf429_f2' => '' ),
	) );
	$keys = array_keys( $errors );
	sort( $keys );
	$want = $test['want'];
	sort( $want );
	if ( $keys !== $want ) {
		WP_CLI::error( 'R2_REQUIRED_VALIDATION_FAILED:' . $label . ':' . implode( ',', $keys ) );
	}
	WP_CLI::log( 'PASS:' . $label . ' (' . count( $keys ) . ' expected errors)' );
}
WP_CLI::success( 'R2_VALIDATION_6_OF_6_PASS; zero submissions / zero emails.' );
