<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['profile_meta'] = array();
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

function metadata_exists( $meta_type, $post_id, $key ) {
	return 'post' === $meta_type && array_key_exists( $key, $GLOBALS['profile_meta'][ $post_id ] ?? array() );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function esc_url_raw( $value ) {
	return preg_match( '#^https?://#i', (string) $value ) ? trim( (string) $value ) : '';
}

class YBY_Config {
	public static function get_default_product_interest() {
		return 'global product';
	}

	public static function get_default_country() {
		return 'global country';
	}

	public static function get_runtime_config() {
		return array(
			'thankYouUrl' => '/global-thank-you/',
			'returnPageUrl' => '/global-return/',
		);
	}

	public static function sanitize_path_value( $value ) {
		$value = '/' . ltrim( trim( (string) $value ), '/' );
		return false !== strpos( $value, '..' ) ? '' : $value;
	}
}

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
profile_assert( array() === YBY_Page_Profile::get_overrides_by_post_id( 13 ), 'Invalid override values must be omitted.' );

$profile = YBY_Page_Profile::get_profile_by_post_id( 12 );
profile_assert( '/global-thank-you/' === $profile['thankYouUrl'], 'Complete profiles must preserve global fallback behavior.' );
profile_assert( '/global-return/' === $profile['returnPageUrl'], 'Complete profiles must preserve existing return URL behavior.' );

echo "page-profile-overrides:PASS\n";
