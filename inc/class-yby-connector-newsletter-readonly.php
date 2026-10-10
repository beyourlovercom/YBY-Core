<?php
/**
 * M2.8: independent, CLI-issued, short-lived Newsletter snapshot identity.
 *
 * This credential is NOT the general ERP Connector identity. It cannot
 * authenticate any other snapshot, any write route, or the legacy Elementor
 * subscriber source. Provisioning is intentionally unavailable over HTTP.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Connector_Newsletter_Readonly {
	const OPTION = 'yby_core_newsletter_readonly_options';
	const SECRET_OPTION = 'yby_core_newsletter_readonly_secret';
	const MAX_TTL = 900;
	const ROUTE = '/andy-core/v1/erp/snapshot/subscribers';
	const PATH = '/wp-json/andy-core/v1/erp/snapshot/subscribers';

	/** CLI-only, one-time issuance; returns secret to trusted CLI caller. */
	public static function provision( $connection_key, $key_id, $ttl = 600 ) {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
			! is_string( $connection_key ) || ! is_string( $key_id ) ||
			! is_int( $ttl ) || $ttl < 1 || $ttl > self::MAX_TTL ) { return false; }
		$candidate = YBY_Connector::sanitize( array(
			'enabled' => true, 'connection_key' => $connection_key, 'key_id' => $key_id,
		) );
		if ( ! YBY_Connector::has_identity( $candidate ) ||
			$candidate['connection_key'] !== $connection_key || $candidate['key_id'] !== $key_id ||
			$key_id === YBY_Connector::get_options()['key_id'] ||
			false !== get_option( self::OPTION, false ) ||
			false !== get_option( self::SECRET_OPTION, false ) ) { return false; }

		try { $secret = bin2hex( random_bytes( 32 ) ); } catch ( Exception $e ) { return false; }
		$stored = self::encrypt( $secret );
		if ( false === $stored || ! add_option( self::SECRET_OPTION, $stored, '', false ) ) { return false; }
		$candidate['expires_at'] = time() + $ttl;
		if ( ! add_option( self::OPTION, $candidate, '', false ) ) {
			delete_option( self::SECRET_OPTION );
			return false;
		}
		return $secret;
	}

	/** Explicit revocation; the expiration check also denies stale keys. */
	public static function revoke() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return false; }
		// Never report revocation success if a failed delete leaves a live key.
		if ( false !== get_option( self::OPTION, false ) && ! delete_option( self::OPTION ) ) { return false; }
		if ( false !== get_option( self::SECRET_OPTION, false ) && ! delete_option( self::SECRET_OPTION ) ) { return false; }
		return false === get_option( self::OPTION, false ) && false === get_option( self::SECRET_OPTION, false );
	}

	/**
	 * Return null only when this is NOT the scoped key ID. For a scoped ID,
	 * always return true or WP_Error; never fall through to the general key.
	 */
	public static function authenticate( $request ) {
		$options = get_option( self::OPTION, array() );
		$key_id = self::header( $request, 'X-YBY-Key-Id' );
		if ( ! is_array( $options ) || empty( $options['key_id'] ) ||
			! is_string( $options['key_id'] ) || ! hash_equals( $options['key_id'], $key_id ) ) { return null; }

		if ( ! YBY_Connector::is_https() || empty( $options['enabled'] ) ||
			! is_int( $options['expires_at'] ?? null ) ||
			$options['expires_at'] <= time() || $options['expires_at'] > time() + self::MAX_TTL ||
			! is_string( $options['connection_key'] ?? null ) ||
			! hash_equals( $options['connection_key'], self::header( $request, 'X-YBY-Connection-Key' ) ) ) {
			return self::denied( 'AUTH_INVALID', 401 );
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? $_SERVER['REQUEST_URI'] : '';
		if ( ! self::allowed( $request, $path ) ) { return self::denied( 'AUTH_FORBIDDEN', 403 ); }

		$version = self::header( $request, 'X-YBY-Signature-Version' );
		$timestamp = self::header( $request, 'X-YBY-Timestamp' );
		$nonce = self::header( $request, 'X-YBY-Nonce' );
		$signature = self::header( $request, 'X-YBY-Signature' );
		if ( 'v1' !== $version || ! ctype_digit( $timestamp ) ||
			abs( time() - (int) $timestamp ) > YBY_Connector::TIMESTAMP_TOLERANCE ||
			! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/D', $nonce ) ||
			! preg_match( '/^[a-f0-9]{64}$/D', $signature ) ) {
			return self::denied( 'AUTH_INVALID', 401 );
		}
		$secret = self::decrypt();
		if ( '' === $secret ) { return self::denied( 'AUTH_INVALID', 401 ); }
		$expected = YBY_Connector::sign(
			'GET', $path, $timestamp, $nonce, $options['connection_key'], '', '', $secret
		);
		if ( ! hash_equals( $expected, $signature ) ) { return self::denied( 'AUTH_INVALID', 401 ); }

		$prefix = hash( 'sha256', $options['key_id'] . ':' . $options['connection_key'] );
		$nonce_key = 'yby_nl_read_nonce_' . hash( 'sha256', $prefix . ':' . $nonce );
		if ( get_transient( $nonce_key ) ) { return self::denied( 'REPLAY_DETECTED', 409 ); }
		$rate_key = 'yby_nl_read_rate_' . $prefix;
		$count = (int) get_transient( $rate_key );
		if ( $count >= YBY_Connector::RATE_LIMIT_MAX_REQUESTS ) { return self::denied( 'RATE_LIMITED', 429 ); }
		$remaining = max( 1, $options['expires_at'] - time() );
		if ( ! set_transient( $nonce_key, 1, min( $remaining, YBY_Connector::NONCE_TTL ) ) ||
			! set_transient( $rate_key, $count + 1, min( $remaining, YBY_Connector::RATE_LIMIT_WINDOW ) ) ) {
			return self::denied( 'AUTH_UNAVAILABLE', 503 );
		}
		return true;
	}

	private static function allowed( $request, $path ) {
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ||
			! method_exists( $request, 'get_method' ) || ! method_exists( $request, 'get_body' ) ||
			'GET' !== $request->get_method() || self::ROUTE !== $request->get_route() ||
			'' !== (string) $request->get_body() ||
			'' !== self::header( $request, 'X-YBY-Idempotency-Key' ) ) { return false; }
		// Exactly two query arguments, in ERP gateway's signed order.
		// Deny legacy source, cursor, updated_after, duplicates and extras.
		return 1 === preg_match(
			'~^' . preg_quote( self::PATH, '~' ) .
			'\?source=andy_core_newsletter&limit=(?:[1-9]|[1-9][0-9]|100)$~D',
			$path
		);
	}

	private static function header( $request, $name ) {
		return is_object( $request ) && method_exists( $request, 'get_header' )
			? trim( (string) $request->get_header( $name ) ) : '';
	}

	private static function denied( $code, $status ) {
		return new WP_Error( $code, 'Newsletter-only Connector request denied.',
			array( 'status' => $status, 'retryable' => false ) );
	}

	private static function encryption_key() {
		$salts = array();
		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $key ) {
			if ( ! defined( $key ) || '' === constant( $key ) ) { return ''; }
			$salts[] = constant( $key );
		}
		return hash( 'sha256', implode( '|', $salts ), true );
	}

	private static function encrypt( $secret ) {
		$key = self::encryption_key();
		if ( '' === $key || ! function_exists( 'openssl_encrypt' ) ) { return false; }
		$iv = random_bytes( 16 );
		$cipher = openssl_encrypt( $secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $cipher ) { return false; }
		$iv = base64_encode( $iv );
		$cipher = base64_encode( $cipher );
		return array(
			'ciphertext' => $cipher, 'iv' => $iv,
			'mac' => hash_hmac( 'sha256', $iv . '|' . $cipher, $key ), 'version' => 1,
		);
	}

	private static function decrypt() {
		$stored = get_option( self::SECRET_OPTION, array() );
		$key = self::encryption_key();
		if ( '' === $key || ! function_exists( 'openssl_decrypt' ) ||
			! is_array( $stored ) || ! is_string( $stored['iv'] ?? null ) ||
			! is_string( $stored['ciphertext'] ?? null ) || ! is_string( $stored['mac'] ?? null ) ||
			! hash_equals( hash_hmac( 'sha256', $stored['iv'] . '|' . $stored['ciphertext'], $key ), $stored['mac'] ) ) {
			return '';
		}
		$iv = base64_decode( $stored['iv'], true );
		$cipher = base64_decode( $stored['ciphertext'], true );
		if ( false === $iv || strlen( $iv ) !== 16 || false === $cipher ) { return ''; }
		$value = openssl_decrypt( $cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		return false === $value ? '' : (string) $value;
	}
}
