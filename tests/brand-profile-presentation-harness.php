<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['yby_option_store']  = array();
$GLOBALS['yby_filter_values'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['yby_option_store'] ) ? $GLOBALS['yby_option_store'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['yby_option_store'][ $key ] = $value;

	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t]+/', ' ', $value );
	$value = preg_replace( '/\s+/', ' ', $value );

	return trim( (string) $value );
}

function sanitize_email( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/[\r\n]/', $value ) ) {
		return '';
	}

	return filter_var( $value, FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function home_url( $path = '/' ) {
	return 'https://example.test/' . ltrim( (string) $path, '/' );
}

function apply_filters( $hook, $value ) {
	if ( isset( $GLOBALS['yby_filter_values'][ $hook ] ) && is_callable( $GLOBALS['yby_filter_values'][ $hook ] ) ) {
		return call_user_func( $GLOBALS['yby_filter_values'][ $hook ], $value );
	}

	return $value;
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
require_once dirname( __DIR__ ) . '/inc/class-yby-site-profile.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-brand-profile.php';

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function harness_reset_state( $options = array(), $extra = array() ) {
	$GLOBALS['yby_option_store'] = array(
		'admin_email' => 'admin@example.test',
		YBY_Helpers::option_key() => is_array( $options ) ? $options : array(),
		YBY_Helpers::lead_notification_primary_recipient_option_key() => '',
		YBY_Helpers::lead_notification_cc_recipient_option_key() => '',
		YBY_Helpers::lead_notification_bcc_recipient_option_key() => '',
	);
	$GLOBALS['yby_filter_values'] = array();

	foreach ( $extra as $key => $value ) {
		$GLOBALS['yby_option_store'][ $key ] = $value;
	}
}

$tests = array();

$tests['neutral_profile'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'                     => '!!!',
			'site_brand_name'                    => '',
			'case_id_brand_code'                 => 'too-long-code!!!',
			'website_url'                        => '/relative',
			'brand_primary_color'                => 'rgb(1,2,3)',
			'lead_notification_subject_template' => '',
			'inquiry_email_title'                => '',
		)
	);

	$site_profile  = YBY_Site_Profile::get_profile();
	$brand_profile = YBY_Brand_Profile::get_profile();

	harness_assert( 'yby_core' === $site_profile['site_brand_key'], 'Neutral site brand key must fall back to yby_core.' );
	harness_assert( 'YBY' === $site_profile['site_brand_name'], 'Neutral site brand name must fall back to YBY.' );
	harness_assert( 'CORE' === $site_profile['case_id_brand_code'], 'Neutral case ID code must fall back to CORE.' );
	harness_assert( 'https://example.test/' === $site_profile['website_url'], 'Neutral website must use home_url fallback.' );
	harness_assert( 'YBY' === YBY_Brand_Profile::get_brand_name(), 'Neutral brand name must not contain site-specific overrides.' );
	harness_assert( '[New Inquiry] {country} | {product_interest} | {case_id}' === YBY_Brand_Profile::get_subject_template(), 'Neutral subject template must be shared default.' );
	harness_assert( 'Website Inquiry' === YBY_Brand_Profile::get_inquiry_email_title(), 'Neutral inquiry title must be Website Inquiry.' );
	harness_assert( '#1F2937' === $brand_profile['brand_primary_color'], 'Neutral invalid colors must fall back safely.' );
	harness_assert( 'admin@example.test' === YBY_Config::get_lead_notification_primary_recipient_email(), 'Primary recipient must fall back to admin_email.' );
	harness_assert( '' === YBY_Config::get_lead_notification_cc_recipient_emails(), 'Neutral CC default must be empty.' );
	harness_assert( '' === YBY_Config::get_lead_notification_bcc_recipient_emails(), 'Neutral BCC default must be empty.' );
	harness_assert( false === str_contains( implode( ' ', $brand_profile ), 'Irrigation' ), 'Neutral profile must not contain irrigation identity.' );
};

$tests['irrigation_presentation'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'           => 'yby_irrigation',
			'site_brand_name'          => 'YBY Irrigation',
			'case_id_brand_code'       => 'IRR',
			'website_url'              => 'https://ybyirrigation.com/',
			'brand_primary_color'      => '#00754A',
			'brand_primary_text_color' => '#FFFFFF',
			'brand_secondary_color'    => '#17211B',
			'brand_surface_color'      => '#F4F6F4',
			'brand_text_color'         => '#17211B',
			'brand_muted_text_color'   => '#37433C',
			'brand_border_color'       => '#DDE5DF',
			'email_logo_url'           => 'https://cdn.example.test/irrigation-logo.png',
			'thank_you_url'            => '/lp/thank-you-irrigation-solution/',
			'return_page_url'          => '/lp/irrigation-solution/',
			'catalog_url'              => 'https://ybyirrigation.com/catalog.pdf',
			'default_country'          => 'Tanzania',
			'default_product_interest' => 'irrigation system solution',
		)
	);

	$brand_profile = YBY_Brand_Profile::get_profile();

	harness_assert( 'IRR' === YBY_Site_Profile::get_case_id_code(), 'Irrigation site profile must preserve IRR code.' );
	harness_assert( '#00754A' === $brand_profile['brand_primary_color'], 'Irrigation configured primary color must resolve.' );
	harness_assert( 'https://ybyirrigation.com/' === YBY_Brand_Profile::get_website_url(), 'Irrigation website must resolve from trusted identity.' );
	harness_assert( '/lp/thank-you-irrigation-solution/' === YBY_Brand_Profile::get_thank_you_url(), 'Irrigation thank-you URL must remain readable.' );
	harness_assert( 'Tanzania' === YBY_Brand_Profile::get_default_country(), 'Irrigation default country must remain readable when stored.' );
};

$tests['bottle_presentation'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'           => 'yby_bottle',
			'site_brand_name'          => 'BC Glass Bottles',
			'case_id_brand_code'       => 'BCB',
			'website_url'              => 'https://bottle.example.test/',
			'brand_primary_color'      => '#8B5E3C',
			'brand_secondary_color'    => '#1F2937',
			'catalog_url'              => 'https://bottle.example.test/catalog.pdf',
			'thank_you_url'            => '/thank-you-bottle/',
			'return_page_url'          => '/bottle-products/',
			'youtube_video_id'         => 'Bottle123',
			'default_product_interest' => 'glass bottle wholesale',
		)
	);

	$brand_profile = YBY_Brand_Profile::get_profile();

	harness_assert( 'BCB' === YBY_Site_Profile::get_case_id_code(), 'Bottle site profile must preserve configured case ID code.' );
	harness_assert( false === str_contains( YBY_Brand_Profile::get_brand_name(), 'Irrigation' ), 'Bottle profile must not leak irrigation identity.' );
	harness_assert( '#8B5E3C' === $brand_profile['brand_primary_color'], 'Bottle primary color must resolve.' );
	harness_assert( '/bottle-products/' === YBY_Brand_Profile::get_return_page_url(), 'Bottle return page must resolve.' );
	harness_assert( 'Bottle123' === YBY_Brand_Profile::get_youtube_video_id(), 'Bottle YouTube ID must resolve.' );
};

$tests['invalid_color_filter_locking'] = static function () {
	harness_reset_state(
		array(
			'site_brand_key'     => 'trusted-brand',
			'site_brand_name'    => 'Trusted Brand',
			'case_id_brand_code' => 'BOT',
			'website_url'        => 'https://trusted.example.test/',
		)
	);

	$GLOBALS['yby_filter_values']['yby_brand_profile'] = static function ( $profile ) {
		$profile['site_brand_key']         = 'spoofed';
		$profile['site_brand_name']        = 'Spoofed Name';
		$profile['case_id_brand_code']     = 'BAD';
		$profile['website_url']            = 'https://evil.example.test/';
		$profile['brand_primary_color']    = 'rgba(1,2,3,0.4)';
		$profile['brand_secondary_color']  = '<style>bad</style>';
		$profile['email_company_website']  = 'javascript:alert(1)';
		$profile['thank_you_url']          = 'javascript:alert(1)';

		return $profile;
	};

	$brand_profile = YBY_Brand_Profile::get_profile();

	harness_assert( 'trusted-brand' === $brand_profile['site_brand_key'], 'Brand filters must not override trusted brand key.' );
	harness_assert( 'Trusted Brand' === $brand_profile['site_brand_name'], 'Brand filters must not override trusted brand name.' );
	harness_assert( 'BOT' === $brand_profile['case_id_brand_code'], 'Brand filters must not override trusted case code.' );
	harness_assert( 'https://trusted.example.test/' === $brand_profile['website_url'], 'Brand filters must not override trusted website URL.' );
	harness_assert( '#1F2937' === $brand_profile['brand_primary_color'], 'Invalid filter color must fall back to neutral primary color.' );
	harness_assert( '#374151' === $brand_profile['brand_secondary_color'], 'Invalid filter color must fall back to neutral secondary color.' );
	harness_assert( '' === $brand_profile['email_company_website'], 'Invalid absolute URL override must be rejected.' );
	harness_assert( '/' === $brand_profile['thank_you_url'], 'Invalid thank-you URL must fall back safely.' );
};

$tests['legacy_override_and_recipients'] = static function () {
	harness_reset_state(
		array(
			'site_brand_name'       => 'YBY',
			'website_url'           => 'https://canonical.example.test/',
			'email_company_name'    => 'Legacy Override Name',
			'email_company_website' => 'https://legacy.example.test/',
		),
		array(
			YBY_Helpers::lead_notification_primary_recipient_option_key() => 'owner@example.test',
			YBY_Helpers::lead_notification_cc_recipient_option_key()      => 'bad, second@example.test, second@example.test',
			YBY_Helpers::lead_notification_bcc_recipient_option_key()     => 'bcc@example.test invalid',
		)
	);

	harness_assert( 'Legacy Override Name' === YBY_Brand_Profile::get_brand_name(), 'Legacy company name override must remain readable.' );
	harness_assert( 'https://legacy.example.test/' === YBY_Brand_Profile::get_website_url(), 'Legacy company website override must remain readable.' );
	harness_assert( 'owner@example.test' === YBY_Config::get_lead_notification_primary_recipient_email(), 'Valid stored primary recipient must win.' );
	harness_assert( 'second@example.test' === YBY_Config::get_lead_notification_cc_recipient_emails(), 'Invalid CC addresses must be excluded.' );
	harness_assert( 'bcc@example.test' === YBY_Config::get_lead_notification_bcc_recipient_emails(), 'Invalid BCC addresses must be excluded.' );
	};

$results = array();

foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
