<?php
/**
 * Google authentication REST endpoint.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Google Identity Services redirect POSTs.
 */
class YBY_Google_Auth_REST_Controller {

	/**
	 * Allowlisted public result codes.
	 *
	 * @var array<int, string>
	 */
	protected static $result_codes = array(
		'google_not_configured',
		'google_login_disabled',
		'invalid_request',
		'invalid_csrf',
		'invalid_nonce',
		'invalid_google_token',
		'email_required',
		'email_verification_required',
		'existing_account_requires_login',
		'identity_conflict',
		'role_not_allowed',
		'registration_failed',
		'login_failed',
		'verification_unavailable',
	);

	/**
	 * Token verifier.
	 *
	 * @var YBY_Google_Token_Verifier
	 */
	protected $verifier;

	/**
	 * User authentication service.
	 *
	 * @var YBY_Google_Auth_Service
	 */
	protected $auth_service;

	/**
	 * Constructor.
	 *
	 * @param YBY_Google_Token_Verifier|null $verifier Token verifier.
	 * @param YBY_Google_Auth_Service|null    $auth_service Authentication service.
	 */
	public function __construct( $verifier = null, $auth_service = null ) {
		$this->verifier     = $verifier instanceof YBY_Google_Token_Verifier ? $verifier : new YBY_Google_Token_Verifier();
		$this->auth_service = $auth_service instanceof YBY_Google_Auth_Service ? $auth_service : new YBY_Google_Auth_Service();
	}

	/**
	 * Register the anonymous POST route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'yby/v1',
			'/auth/google',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'authenticate' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle one Google authentication response.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function authenticate( \WP_REST_Request $request ) {
		$settings = YBY_Social_Login::get_google_options();

		if ( '' === $settings['client_id'] ) {
			return $this->error_redirect( 'google_not_configured', $settings );
		}

		if ( empty( $settings['enabled'] ) ) {
			return $this->error_redirect( 'google_login_disabled', $settings );
		}

		if ( ! $this->is_secure_request() ) {
			return $this->error_redirect( 'invalid_request', $settings );
		}

		$credential = $request->get_param( 'credential' );
		$csrf_body  = $request->get_param( 'g_csrf_token' );
		$csrf_cookie = isset( $_COOKIE['g_csrf_token'] ) ? wp_unslash( $_COOKIE['g_csrf_token'] ) : '';

		if ( ! is_string( $credential ) || '' === $credential || strlen( $credential ) > YBY_Google_Token_Verifier::MAX_TOKEN_LENGTH ) {
			return $this->error_redirect( 'invalid_request', $settings );
		}

		if (
			! is_string( $csrf_body ) ||
			! is_string( $csrf_cookie ) ||
			'' === $csrf_body ||
			'' === $csrf_cookie ||
			strlen( $csrf_body ) > 512 ||
			strlen( $csrf_cookie ) > 512 ||
			! hash_equals( $csrf_cookie, $csrf_body )
		) {
			return $this->error_redirect( 'invalid_csrf', $settings );
		}

		$claims = $this->verifier->verify(
			$credential,
			$settings['client_id'],
			array( 'YBY_Social_Login', 'is_login_nonce_valid' )
		);

		if ( is_wp_error( $claims ) ) {
			return $this->error_redirect( $claims->get_error_code(), $settings );
		}

		YBY_Social_Login::consume_login_nonce( (string) $claims['nonce'] );
		$user = $this->auth_service->authenticate( $claims, $settings );

		if ( is_wp_error( $user ) ) {
			return $this->error_redirect( $user->get_error_code(), $settings );
		}

		return $this->redirect_response( 'success', $settings );
	}

	/**
	 * Confirm HTTPS, allowing only explicit localhost development.
	 *
	 * @return bool
	 */
	protected function is_secure_request() {
		if ( is_ssl() ) {
			return true;
		}

		$home = wp_parse_url( home_url( '/' ) );
		$host = is_array( $home ) ? strtolower( (string) ( $home['host'] ?? '' ) ) : '';

		return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
	}

	/**
	 * Build a safe error redirect response.
	 *
	 * @param string               $code Proposed error code.
	 * @param array<string, mixed> $settings Google settings.
	 * @return \WP_REST_Response
	 */
	protected function error_redirect( $code, $settings ) {
		$code = in_array( $code, self::$result_codes, true ) ? $code : 'invalid_google_token';

		return $this->redirect_response( $code, $settings );
	}

	/**
	 * Build an internal 302 response.
	 *
	 * @param string               $result Allowlisted result.
	 * @param array<string, mixed> $settings Google settings.
	 * @return \WP_REST_Response
	 */
	protected function redirect_response( $result, $settings ) {
		$configured = isset( $settings['redirect_url'] ) ? YBY_Social_Login::sanitize_internal_redirect( $settings['redirect_url'] ) : '';
		$target     = '' !== $configured ? $configured : home_url( '/' );

		if ( '/' === substr( $target, 0, 1 ) ) {
			$target = home_url( $target );
		}

		$target = wp_validate_redirect( $target, home_url( '/' ) );
		$target = add_query_arg( 'yby_social_login', $result, $target );

		return new \WP_REST_Response(
			null,
			302,
			array( 'Location' => $target )
		);
	}
}
