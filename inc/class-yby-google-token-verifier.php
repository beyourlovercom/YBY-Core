<?php
/**
 * Local Google ID Token verification.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies Google ID Tokens with cached Google PEM certificates.
 */
class YBY_Google_Token_Verifier {

	/**
	 * Google PEM certificate endpoint.
	 */
	const CERTIFICATE_URL = 'https://www.googleapis.com/oauth2/v1/certs';

	/**
	 * Maximum accepted encoded token length.
	 */
	const MAX_TOKEN_LENGTH = 16384;

	/**
	 * Certificate cache key.
	 */
	const CERTIFICATE_CACHE_KEY = 'yby_google_pem_certificates';

	/**
	 * Optional certificate retriever used by deterministic tests.
	 *
	 * @var callable|null
	 */
	protected $certificate_retriever;

	/**
	 * Optional clock used by deterministic tests.
	 *
	 * @var callable|null
	 */
	protected $clock;

	/**
	 * Constructor.
	 *
	 * @param callable|null $certificate_retriever Certificate retriever.
	 * @param callable|null $clock Current-time provider.
	 */
	public function __construct( $certificate_retriever = null, $clock = null ) {
		$this->certificate_retriever = is_callable( $certificate_retriever ) ? $certificate_retriever : null;
		$this->clock                 = is_callable( $clock ) ? $clock : null;
	}

	/**
	 * Verify an encoded Google ID Token.
	 *
	 * @param string   $token Token from Google Identity Services.
	 * @param string   $client_id Configured OAuth client ID.
	 * @param callable $nonce_validator Server-side nonce validator.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function verify( $token, $client_id, $nonce_validator ) {
		if ( ! function_exists( 'openssl_verify' ) || ! is_callable( $nonce_validator ) ) {
			return new \WP_Error( 'verification_unavailable' );
		}

		if ( ! is_string( $token ) || strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$segments = explode( '.', $token );

		if ( 3 !== count( $segments ) ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$header_json  = $this->decode_base64url( $segments[0] );
		$payload_json = $this->decode_base64url( $segments[1] );
		$signature    = $this->decode_base64url( $segments[2] );

		if ( false === $header_json || false === $payload_json || false === $signature ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$header = json_decode( $header_json, true );
		$claims = json_decode( $payload_json, true );

		if ( ! is_array( $header ) || ! is_array( $claims ) || 'RS256' !== ( $header['alg'] ?? '' ) ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$kid = isset( $header['kid'] ) && is_string( $header['kid'] ) ? $header['kid'] : '';

		if ( '' === $kid || strlen( $kid ) > 255 ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$certificate = $this->get_certificate( $kid );

		if ( is_wp_error( $certificate ) ) {
			return $certificate;
		}

		$verified = openssl_verify(
			$segments[0] . '.' . $segments[1],
			$signature,
			$certificate,
			OPENSSL_ALGO_SHA256
		);

		if ( 1 !== $verified ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		$claim_error = $this->validate_claims( $claims, $client_id, $nonce_validator );

		return is_wp_error( $claim_error ) ? $claim_error : $claims;
	}

	/**
	 * Validate signed Google claims.
	 *
	 * @param array<string, mixed> $claims Signed claims.
	 * @param string               $client_id Configured client ID.
	 * @param callable             $nonce_validator Nonce validator.
	 * @return true|\WP_Error
	 */
	protected function validate_claims( $claims, $client_id, $nonce_validator ) {
		$now   = $this->now();
		$aud   = $claims['aud'] ?? null;
		$iss   = $claims['iss'] ?? null;
		$exp   = $claims['exp'] ?? null;
		$iat   = $claims['iat'] ?? null;
		$nonce = $claims['nonce'] ?? null;
		$sub   = $claims['sub'] ?? null;
		$email = $claims['email'] ?? null;

		if ( ! is_string( $aud ) || ! hash_equals( $client_id, $aud ) ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		if ( ! in_array( $iss, array( 'accounts.google.com', 'https://accounts.google.com' ), true ) ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		if ( ! is_numeric( $exp ) || (int) $exp <= $now || ! is_numeric( $iat ) || (int) $iat <= 0 || (int) $iat > $now + 300 || (int) $iat > (int) $exp ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		if ( ! is_string( $nonce ) || ! call_user_func( $nonce_validator, $nonce ) ) {
			return new \WP_Error( 'invalid_nonce' );
		}

		if ( ! is_string( $sub ) || '' === $sub || strlen( $sub ) > 255 ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		if ( ! is_string( $email ) || '' === $email || ! is_email( $email ) ) {
			return new \WP_Error( 'email_required' );
		}

		if ( true !== ( $claims['email_verified'] ?? false ) ) {
			return new \WP_Error( 'email_verification_required' );
		}

		return true;
	}

	/**
	 * Resolve a certificate, refreshing once when the key ID is unknown.
	 *
	 * @param string $kid Token key ID.
	 * @return string|\WP_Error
	 */
	protected function get_certificate( $kid ) {
		$certificates = $this->get_certificates( false );

		if ( is_wp_error( $certificates ) ) {
			return $certificates;
		}

		if ( ! isset( $certificates[ $kid ] ) ) {
			$certificates = $this->get_certificates( true );
		}

		if ( is_wp_error( $certificates ) ) {
			return $certificates;
		}

		if ( ! isset( $certificates[ $kid ] ) ) {
			return new \WP_Error( 'invalid_google_token' );
		}

		return $certificates[ $kid ];
	}

	/**
	 * Return cached or freshly retrieved certificates.
	 *
	 * @param bool $force_refresh Whether to bypass the cache.
	 * @return array<string, string>|\WP_Error
	 */
	protected function get_certificates( $force_refresh ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CERTIFICATE_CACHE_KEY );

			if ( is_array( $cached ) && $this->certificates_are_valid( $cached ) ) {
				return $cached;
			}

			if ( false !== $cached ) {
				delete_transient( self::CERTIFICATE_CACHE_KEY );
			}
		}

		$result = $this->retrieve_certificates();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$certificates = isset( $result['certificates'] ) && is_array( $result['certificates'] ) ? $result['certificates'] : array();
		$max_age      = isset( $result['max_age'] ) ? (int) $result['max_age'] : 0;

		if ( ! $this->certificates_are_valid( $certificates ) ) {
			return new \WP_Error( 'verification_unavailable' );
		}

		$max_age = max( 60, min( 86400, $max_age ) );
		set_transient( self::CERTIFICATE_CACHE_KEY, $certificates, $max_age );

		return $certificates;
	}

	/**
	 * Retrieve Google PEM certificates.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	protected function retrieve_certificates() {
		if ( $this->certificate_retriever ) {
			return call_user_func( $this->certificate_retriever );
		}

		$response = wp_remote_get(
			self::CERTIFICATE_URL,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'verification_unavailable' );
		}

		$certificates = json_decode( wp_remote_retrieve_body( $response ), true );
		$cache_header = (string) wp_remote_retrieve_header( $response, 'cache-control' );
		$max_age      = 300;

		if ( preg_match( '/(?:^|,)\s*max-age=(\d+)/i', $cache_header, $matches ) ) {
			$max_age = (int) $matches[1];
		}

		return array(
			'certificates' => $certificates,
			'max_age'      => $max_age,
		);
	}

	/**
	 * Check that every certificate is a usable PEM public key.
	 *
	 * @param mixed $certificates Candidate certificate map.
	 * @return bool
	 */
	protected function certificates_are_valid( $certificates ) {
		if ( ! is_array( $certificates ) || empty( $certificates ) ) {
			return false;
		}

		foreach ( $certificates as $kid => $certificate ) {
			if ( ! is_string( $kid ) || '' === $kid || ! is_string( $certificate ) || false === openssl_pkey_get_public( $certificate ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Strictly decode one unpadded JWT base64url segment.
	 *
	 * @param string $segment Encoded segment.
	 * @return string|false
	 */
	protected function decode_base64url( $segment ) {
		if ( ! is_string( $segment ) || '' === $segment || 1 !== preg_match( '/^[A-Za-z0-9_-]+$/', $segment ) ) {
			return false;
		}

		$padding = strlen( $segment ) % 4;

		if ( 1 === $padding ) {
			return false;
		}

		if ( $padding ) {
			$segment .= str_repeat( '=', 4 - $padding );
		}

		return base64_decode( strtr( $segment, '-_', '+/' ), true );
	}

	/**
	 * Return current Unix time.
	 *
	 * @return int
	 */
	protected function now() {
		return $this->clock ? (int) call_user_func( $this->clock ) : time();
	}
}
