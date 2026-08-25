<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function wp_parse_url( $value ) {
	return parse_url( (string) $value );
}

require_once dirname( __DIR__ ) . '/inc/class-yby-config.php';

function url_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$valid_urls = array(
	'/lp/thank-you-glass-bottle-oem/',
	'/lp/thank-you-glass-bottle-oem/?source=oem',
	'https://ybybottle.com/lp/thank-you-glass-bottle-oem/',
	'https://example.test/thank-you/',
	'http://127.0.0.1:8080/thank-you/',
	'https://example.test:65535/thank-you/',
	'https://[2001:db8::1]/thank-you/',
	'http://localhost/thank-you/',
);

foreach ( $valid_urls as $url ) {
	url_assert( $url === YBY_Config::sanitize_path_or_absolute_url_value( $url, '' ), 'Valid URL must be preserved: ' . $url );
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
	'https://example..test/path',
	'https://example.test:0/path',
	'https://example.test:65536/path',
	'',
);

foreach ( $invalid_urls as $url ) {
	url_assert( '' === YBY_Config::sanitize_path_or_absolute_url_value( $url, '' ), 'Invalid page override must be empty: ' . json_encode( $url ) );
	url_assert( '/' === YBY_Config::sanitize_path_or_absolute_url_value( $url, '/' ), 'Invalid global value must use fallback: ' . json_encode( $url ) );
}

echo "url-sanitization:PASS\n";
