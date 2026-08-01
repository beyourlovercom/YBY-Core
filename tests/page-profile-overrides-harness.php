<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['profile_meta'] = array();
$GLOBALS['profile_options'] = array(
	'yby_core_options' => array(
		'thank_you_url' => '/global-thank-you/',
		'return_page_url' => '/global-return/',
	),
);

function absint( $value ) {
	return abs( (int) $value );
}

function get_post_meta( $post_id, $key, $single = false ) {
	unset( $single );
	return $GLOBALS['profile_meta'][ $post_id ][ $key ] ?? '';
}

function get_field( $key, $post_id ) {
	return $GLOBALS['profile_meta'][ $post_id ][ $key ] ?? '';
}

function get_option( $key, $default = false ) {
	return $GLOBALS['profile_options'][ $key ] ?? $default;
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function wp_parse_url( $value ) {
	return parse_url( (string) $value );
}

function sanitize_email( $value ) {
	return filter_var( trim( (string) $value ), FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function home_url( $path = '/' ) {
	return 'https://profile.example.test/' . ltrim( (string) $path, '/' );
}

class YBY_Helpers {
	public static function option_key() {
		return 'yby_core_options';
	}

	public static function lead_recipient_option_key() {
		return 'yby_lead_recipient_email';
	}

	public static function lead_notification_primary_recipient_option_key() {
		return 'yby_lead_notification_primary_recipient_email';
	}

	public static function lead_notification_cc_recipient_option_key() {
		return 'yby_lead_notification_cc_recipient_emails';
	}

	public static function lead_notification_bcc_recipient_option_key() {
		return 'yby_lead_notification_bcc_recipient_emails';
	}

	public static function lead_notification_reply_to_policy_option_key() {
		return 'yby_lead_notification_reply_to_policy';
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-config.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-page-profile.php';

function profile_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$GLOBALS['profile_meta'][11] = array(
	'yby_thank_you_url' => ' /lp/thank-you-glass-bottle-oem/ ',
	'yby_catalog_url'   => 'https://ybybottle.com/catalog.pdf',
);

$profile = YBY_Page_Profile::get_raw_profile_by_post_id( 11 );
profile_assert( '/lp/thank-you-glass-bottle-oem/' === $profile['thankYouUrl'], 'Explicit page Thank You URL must be sanitized and preserved.' );
profile_assert( 'https://ybybottle.com/catalog.pdf' === $profile['catalogUrl'], 'Explicit page Catalog URL must be sanitized and preserved.' );

$invalid_urls = array(
	'../unsafe',
	'http://../unsafe',
	'https://../unsafe',
	'https:///missing-host',
	'//evil.example.test/path',
	'javascript:alert(1)',
	'data:text/html,test',
	'http://',
	'https://',
	'https://user:password@example.test/path',
	"https://example.test/control-\x01",
);

foreach ( $invalid_urls as $index => $url ) {
	$post_id = 20 + $index;
	$GLOBALS['profile_meta'][ $post_id ] = array(
		'yby_thank_you_url' => $url,
		'yby_catalog_url'   => $url,
	);
	$invalid_profile = YBY_Page_Profile::get_raw_profile_by_post_id( $post_id );
	profile_assert( '' === $invalid_profile['thankYouUrl'], 'Invalid page Thank You URL must be omitted: ' . json_encode( $url ) );
	profile_assert( '' === $invalid_profile['catalogUrl'], 'Invalid page Catalog URL must be omitted: ' . json_encode( $url ) );
}

echo "page-profile-overrides:PASS\n";
