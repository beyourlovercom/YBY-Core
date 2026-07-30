<?php
/**
 * Google One Tap challenge, authentication, and public runtime.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides a cache-safe asynchronous Google One Tap flow.
 */
class YBY_Google_One_Tap_Controller {

	/**
	 * Required project-specific request header.
	 */
	const REQUEST_HEADER = 'X-Andy-Core-One-Tap';

	/**
	 * Required anonymous browser-session header.
	 */
	const SESSION_HEADER = 'X-Andy-Core-One-Tap-Session';

	/**
	 * Login suppression cookie used immediately after WordPress logout.
	 */
	const LOGOUT_COOKIE = 'yby_one_tap_suppress';

	/**
	 * Rate window in seconds.
	 */
	const RATE_WINDOW = 60;

	/**
	 * Token verifier.
	 *
	 * @var YBY_Google_Token_Verifier
	 */
	protected $verifier;

	/**
	 * Authentication service.
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
	 * Register separate JSON endpoints for One Tap.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'yby/v1',
			'/auth/google/onetap/challenge',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'issue_challenge' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'yby/v1',
			'/auth/google/onetap',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'authenticate' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'yby/v1',
			'/auth/google/onetap/session',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'confirm_session' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Enqueue only a cache-safe bootstrap on eligible frontend pages.
	 *
	 * @return void
	 */
	public function enqueue_runtime() {
		if ( ! $this->is_eligible_public_request() ) {
			return;
		}

		$settings = YBY_Social_Login::get_google_options();
		$handle   = 'yby-google-one-tap';
		$path     = 'public/assets/js/yby-google-one-tap.js';
		$file     = YBY_CORE_PLUGIN_DIR . $path;

		wp_enqueue_script(
			$handle,
			YBY_CORE_PLUGIN_URL . $path,
			array(),
			is_file( $file ) ? (string) filemtime( $file ) : YBY_CORE_VERSION,
			true
		);

		wp_add_inline_script(
			$handle,
			'window.YBYGoogleOneTapConfig = ' . wp_json_encode(
				array(
					'clientId'          => $settings['client_id'],
					'challengeEndpoint' => rest_url( 'yby/v1/auth/google/onetap/challenge' ),
					'authEndpoint'      => rest_url( 'yby/v1/auth/google/onetap' ),
					'sessionEndpoint'   => rest_url( 'yby/v1/auth/google/onetap/session' ),
					'requestHeader'     => self::REQUEST_HEADER,
					'sessionHeader'     => self::SESSION_HEADER,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Determine whether One Tap may bootstrap on this request.
	 *
	 * @return bool
	 */
	public function is_eligible_public_request() {
		$settings = YBY_Social_Login::get_google_options();
		$result   = isset( $_GET['yby_social_login'] ) ? sanitize_key( wp_unslash( $_GET['yby_social_login'] ) ) : '';

		if (
			empty( $settings['one_tap_enabled'] ) ||
			empty( $settings['enabled'] ) ||
			! YBY_Social_Login::is_valid_client_id( $settings['client_id'] ) ||
			is_user_logged_in() ||
			! is_ssl() ||
			is_admin() ||
			( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ||
			( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ||
			( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ||
			is_feed() ||
			( function_exists( 'is_embed' ) && is_embed() ) ||
			is_preview() ||
			! empty( $_COOKIE[ self::LOGOUT_COOKIE ] ) ||
			! empty( $_GET['loggedout'] ) ||
			'' !== $result
		) {
			return false;
		}

		return ! $this->current_page_has_google_shortcode();
	}

	/**
	 * Issue one opaque, hashed-at-rest challenge.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function issue_challenge( \WP_REST_Request $request ) {
		$error = $this->validate_json_request( $request, true );

		if ( $error ) {
			return $error;
		}

		if ( is_user_logged_in() ) {
			return $this->error_response( 'already_authenticated', 409 );
		}

		$settings = YBY_Social_Login::get_google_options();

		if ( empty( $settings['one_tap_enabled'] ) || empty( $settings['enabled'] ) || ! YBY_Social_Login::is_valid_client_id( $settings['client_id'] ) ) {
			return $this->error_response( 'google_login_disabled', 403 );
		}

		$session = $this->session_id( $request );

		if ( '' === $session || ! $this->consume_rate_limit( $session ) ) {
			return $this->error_response( 'rate_limited', 429 );
		}

		$nonce = YBY_Social_Login::issue_login_nonce();

		if ( '' === $nonce ) {
			return $this->error_response( 'login_failed', 500 );
		}

		return $this->response(
			array(
				'success' => true,
				'nonce'   => $nonce,
			),
			200
		);
	}

	/**
	 * Verify and authenticate one asynchronous One Tap credential.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function authenticate( \WP_REST_Request $request ) {
		$error = $this->validate_json_request( $request, false );

		if ( $error ) {
			return $error;
		}

		if ( is_user_logged_in() ) {
			return $this->error_response( 'already_authenticated', 409 );
		}

		$settings = YBY_Social_Login::get_google_options();

		if ( empty( $settings['one_tap_enabled'] ) || empty( $settings['enabled'] ) || ! YBY_Social_Login::is_valid_client_id( $settings['client_id'] ) ) {
			return $this->error_response( 'google_login_disabled', 403 );
		}

		$params     = $request->get_json_params();
		$credential = $params['credential'] ?? null;
		$challenge  = $params['challenge'] ?? null;

		if (
			! is_string( $challenge ) ||
			'' === $challenge ||
			strlen( $challenge ) > 128 ||
			! YBY_Social_Login::is_login_nonce_valid( $challenge )
		) {
			return $this->error_response( 'invalid_nonce', 400 );
		}

		YBY_Social_Login::consume_login_nonce( $challenge );

		if ( ! is_string( $credential ) || '' === $credential || strlen( $credential ) > YBY_Google_Token_Verifier::MAX_TOKEN_LENGTH ) {
			return $this->error_response( 'invalid_google_token', 400 );
		}

		$claims = $this->verifier->verify(
			$credential,
			$settings['client_id'],
			static function ( $nonce ) use ( $challenge ) {
				return is_string( $nonce ) && hash_equals( $challenge, $nonce );
			}
		);

		if ( is_wp_error( $claims ) ) {
			return $this->error_response( $claims->get_error_code(), 401 );
		}

		$user = $this->auth_service->authenticate( $claims, $settings );

		if ( is_wp_error( $user ) ) {
			return $this->error_response( $user->get_error_code(), 403 );
		}

		return $this->response(
			array(
				'success' => true,
				'code'    => 'success',
			),
			200
		);
	}

	/**
	 * Confirm the logged-in cookie returned by the browser after authentication.
	 *
	 * REST cookie authentication requires a REST nonce and therefore cannot provide
	 * the current-user state for this anonymous, same-origin confirmation request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function confirm_session( \WP_REST_Request $request ) {
		$error = $this->validate_json_request( $request, true );

		if ( $error ) {
			return $error;
		}

		$cookie = defined( 'LOGGED_IN_COOKIE' ) && isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) && is_string( $_COOKIE[ LOGGED_IN_COOKIE ] )
			? $_COOKIE[ LOGGED_IN_COOKIE ]
			: '';
		$user_id = '' !== $cookie ? wp_validate_auth_cookie( $cookie, 'logged_in' ) : false;

		if ( ! is_int( $user_id ) || $user_id < 1 ) {
			return $this->session_response( false, 401 );
		}

		return $this->session_response( true, 200 );
	}

	/**
	 * Suppress One Tap briefly after an explicit WordPress logout.
	 *
	 * @return void
	 */
	public function suppress_after_logout() {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::LOGOUT_COOKIE,
			'1',
			array(
				'expires'  => time() + 300,
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				'secure'   => true,
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Validate transport, origin, headers, and exact JSON shape.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @param bool             $challenge_request Whether an empty JSON object is required.
	 * @return \WP_REST_Response|null
	 */
	protected function validate_json_request( $request, $challenge_request ) {
		if ( ! is_ssl() ) {
			return $this->error_response( 'invalid_request', 400 );
		}

		if ( ! $this->has_same_origin( $request ) ) {
			return $this->error_response( 'invalid_origin', 403 );
		}

		$content_type = strtolower( trim( (string) $request->get_header( 'content-type' ) ) );

		if ( 1 !== preg_match( '/^application\/json(?:\s*;|$)/', $content_type ) ) {
			return $this->error_response( 'invalid_content_type', 415 );
		}

		if ( '1' !== (string) $request->get_header( self::REQUEST_HEADER ) || '' === $this->session_id( $request ) ) {
			return $this->error_response( 'invalid_request', 400 );
		}

		$params = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return $this->error_response( 'invalid_request', 400 );
		}

		$keys = array_keys( $params );
		sort( $keys );
		$expected = $challenge_request ? array() : array( 'challenge', 'credential' );

		if ( $keys !== $expected ) {
			return $this->error_response( 'invalid_request', 400 );
		}

		return null;
	}

	/**
	 * Confirm the browser Origin exactly matches this site.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return bool
	 */
	protected function has_same_origin( $request ) {
		$origin = trim( (string) $request->get_header( 'origin' ) );
		$home   = wp_parse_url( home_url( '/' ) );

		if ( '' === $origin || ! is_array( $home ) || empty( $home['scheme'] ) || empty( $home['host'] ) ) {
			return false;
		}

		$scheme   = strtolower( $home['scheme'] );
		$expected = $scheme . '://' . strtolower( $home['host'] );

		if (
			isset( $home['port'] ) &&
			! ( 'https' === $scheme && 443 === (int) $home['port'] ) &&
			! ( 'http' === $scheme && 80 === (int) $home['port'] )
		) {
			$expected .= ':' . (int) $home['port'];
		}

		return hash_equals( $expected, rtrim( strtolower( $origin ), '/' ) );
	}

	/**
	 * Resolve a validated anonymous session identifier.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return string
	 */
	protected function session_id( $request ) {
		$session = trim( (string) $request->get_header( self::SESSION_HEADER ) );

		return 1 === preg_match( '/^[A-Za-z0-9_-]{16,128}$/', $session ) ? $session : '';
	}

	/**
	 * Apply independent hashed browser-session and IP limits.
	 *
	 * @param string $session Anonymous session identifier.
	 * @return bool
	 */
	protected function consume_rate_limit( $session ) {
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$ip       = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
		$material = array(
			'session' => $session,
			'ip'      => $ip,
		);
		$limits   = array(
			'session' => 10,
			'ip'      => 30,
		);

		foreach ( $material as $type => $value ) {
			$key   = 'yby_onetap_rate_' . $type . '_' . hash_hmac( 'sha256', $value, wp_salt( 'nonce' ) );
			$count = (int) get_transient( $key );

			if ( $count >= $limits[ $type ] ) {
				return false;
			}

			set_transient( $key, $count + 1, self::RATE_WINDOW );
		}

		return true;
	}

	/**
	 * Detect a Google Social Login shortcode in current content or Bricks data.
	 *
	 * @return bool
	 */
	protected function current_page_has_google_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}

		$post_id = (int) get_queried_object_id();

		if ( $post_id < 1 ) {
			return false;
		}

		return $this->contains_google_shortcode( get_post_field( 'post_content', $post_id ) ) ||
			$this->contains_google_shortcode( get_post_meta( $post_id, '_bricks_page_content_2', true ) );
	}

	/**
	 * Recursively inspect content for the canonical Google shortcode.
	 *
	 * @param mixed $value Content value.
	 * @return bool
	 */
	protected function contains_google_shortcode( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( $this->contains_google_shortcode( $item ) ) {
					return true;
				}
			}

			return false;
		}

		if ( ! is_string( $value ) || false === stripos( $value, '[yby_social_login' ) ) {
			return false;
		}

		if ( ! preg_match_all( '/\[yby_social_login\b([^\]]*)\]/i', $value, $matches ) ) {
			return false;
		}

		foreach ( $matches[1] as $attributes ) {
			$parsed = shortcode_parse_atts( $attributes );

			if ( is_array( $parsed ) && 'google' === sanitize_key( $parsed['provider'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build an allowlisted PII-safe error response.
	 *
	 * @param string $code Proposed error code.
	 * @param int    $status HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function error_response( $code, $status ) {
		$messages = array(
			'already_authenticated'           => 'You are already signed in.',
			'google_login_disabled'           => 'Google sign-in is currently unavailable.',
			'invalid_request'                 => 'The Google login request was invalid.',
			'invalid_origin'                  => 'The Google login request origin was invalid.',
			'invalid_content_type'            => 'The Google login request format was invalid.',
			'rate_limited'                    => 'Too many login attempts. Please try again later.',
			'invalid_nonce'                   => 'The login session expired. Refresh the page and try again.',
			'invalid_google_token'            => 'Google identity verification failed.',
			'email_required'                  => 'Google did not provide an email address for this account.',
			'email_verification_required'     => 'Google could not confirm this account email.',
			'existing_account_requires_login' => 'An account already exists with this email, but Google sign-in is not linked to it. Please use the existing WordPress login.',
			'identity_conflict'               => 'This Google account could not be matched safely. Please contact site support.',
			'role_not_allowed'                => 'Google login is not available for this account. Please use the existing WordPress login.',
			'registration_failed'             => 'The account could not be created.',
			'login_failed'                    => 'The account was verified, but sign-in could not be completed.',
			'verification_unavailable'        => 'Google verification is temporarily unavailable. Please try again later.',
		);
		$code = isset( $messages[ $code ] ) ? $code : 'invalid_google_token';

		return $this->response(
			array(
				'success' => false,
				'code'    => $code,
				'message' => $messages[ $code ],
			),
			$status
		);
	}

	/**
	 * Build a PII-free session confirmation response.
	 *
	 * @param bool $authenticated Whether WordPress validated the logged-in cookie.
	 * @param int  $status HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function session_response( $authenticated, $status ) {
		if ( $authenticated ) {
			return $this->response(
				array(
					'success'       => true,
					'authenticated' => true,
				),
				$status
			);
		}

		return $this->response(
			array(
				'success'       => false,
				'authenticated' => false,
				'code'          => 'login_failed',
				'message'       => 'The account was verified, but sign-in could not be completed.',
			),
			$status
		);
	}

	/**
	 * Return no-store JSON data without identity fields.
	 *
	 * @param array<string, mixed> $data Response data.
	 * @param int                  $status HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function response( $data, $status ) {
		return new \WP_REST_Response(
			$data,
			$status,
			array(
				'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
				'Pragma'        => 'no-cache',
			)
		);
	}
}
