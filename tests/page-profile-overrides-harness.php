<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['profile_meta'] = array();
$GLOBALS['profile_fields'] = array();
$GLOBALS['profile_options'] = array(
	'yby_core_options' => array(
		'thank_you_url' => '/global-thank-you/',
		'return_page_url' => '/global-return/',
	),
);
$GLOBALS['queried_id']   = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function get_queried_object_id() {
	return $GLOBALS['queried_id'];
}

function get_post_meta( $post_id, $key, $single = false ) {
	unset( $single );
	return $GLOBALS['profile_meta'][ $post_id ][ $key ] ?? '';
}

function get_field( $key, $post_id ) {
	return $GLOBALS['profile_fields'][ $post_id ][ $key ] ?? '';
}

function metadata_exists( $meta_type, $post_id, $key ) {
	return 'post' === $meta_type && array_key_exists( $key, $GLOBALS['profile_meta'][ $post_id ] ?? array() );
}

function get_option( $key, $default = false ) {
	return $GLOBALS['profile_options'][ $key ] ?? $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
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
	'yby_return_page_url' => '',
);
$GLOBALS['profile_fields'][11] = $GLOBALS['profile_meta'][11];

$overrides = YBY_Page_Profile::get_overrides_by_post_id( 11 );
profile_assert( array( 'thankYouUrl' => '/lp/thank-you-glass-bottle-oem/' ) === $overrides, 'Only the sanitized explicit page override may be returned.' );

$GLOBALS['queried_id'] = 11;
profile_assert( $overrides === YBY_Page_Profile::get_current_overrides(), 'Current override lookup must use the queried page.' );

profile_assert( array() === YBY_Page_Profile::get_overrides_by_post_id( 0 ), 'Missing post IDs must return no overrides.' );
profile_assert( array() === YBY_Page_Profile::get_overrides_by_post_id( 12 ), 'Missing meta must not expose global or system defaults.' );

$GLOBALS['profile_meta'][13] = array(
	'yby_thank_you_url' => '../unsafe',
	'yby_catalog_url' => 'javascript:alert(1)',
);
$GLOBALS['profile_fields'][13] = array(
	'yby_thank_you_url' => 'http://../unsafe',
	'yby_catalog_url' => 'javascript:alert(1)',
);
profile_assert( array() === YBY_Page_Profile::get_overrides_by_post_id( 13 ), 'Invalid override values must be omitted.' );

$valid_urls = array(
	'/lp/thank-you-glass-bottle-oem/',
	'/lp/thank-you-glass-bottle-oem/?source=oem',
	'https://ybybottle.com/lp/thank-you-glass-bottle-oem/',
	'https://example.test/thank-you/',
);

foreach ( $valid_urls as $index => $url ) {
	$post_id = 20 + $index;
	$GLOBALS['profile_meta'][ $post_id ] = array( 'yby_thank_you_url' => $url );
	$GLOBALS['profile_fields'][ $post_id ] = array( 'yby_thank_you_url' => $url );
	profile_assert(
		array( 'thankYouUrl' => $url ) === YBY_Page_Profile::get_overrides_by_post_id( $post_id ),
		'Valid page URL must remain an explicit override: ' . $url
	);
}

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
	'',
);

foreach ( $invalid_urls as $index => $url ) {
	$post_id = 30 + $index;
	$GLOBALS['profile_meta'][ $post_id ] = array( 'yby_thank_you_url' => $url );
	$GLOBALS['profile_fields'][ $post_id ] = array( 'yby_thank_you_url' => $url );
	profile_assert(
		array() === YBY_Page_Profile::get_overrides_by_post_id( $post_id ),
		'Invalid page URL must not become an explicit override: ' . json_encode( $url )
	);
}

$profile = YBY_Page_Profile::get_profile_by_post_id( 12 );
profile_assert( '/global-thank-you/' === $profile['thankYouUrl'], 'Complete profiles must preserve global fallback behavior.' );
profile_assert( '/global-return/' === $profile['returnPageUrl'], 'Complete profiles must preserve existing return URL behavior.' );

echo "page-profile-overrides:PASS\n";
