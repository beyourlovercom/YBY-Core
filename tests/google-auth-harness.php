<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

class WP_Error {
	protected $code;

	public function __construct( $code ) {
		$this->code = $code;
	}

	public function get_error_code() {
		return $this->code;
	}
}

class WP_User {
	public $ID;
	public $user_login;
	public $user_email;
	public $roles;

	public function __construct( $id, $login, $email, $roles ) {
		$this->ID         = $id;
		$this->user_login = $login;
		$this->user_email = $email;
		$this->roles      = $roles;
	}
}

class WP_REST_Server {
	const CREATABLE = 'POST';
}

class WP_REST_Request {
	protected $params;

	public function __construct( $params = array() ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

class WP_REST_Response {
	public $data;
	public $status;
	public $headers;

	public function __construct( $data = null, $status = 200, $headers = array() ) {
		$this->data    = $data;
		$this->status  = $status;
		$this->headers = $headers;
	}
}

$GLOBALS['ga_options']       = array();
$GLOBALS['ga_transients']    = array();
$GLOBALS['ga_routes']        = array();
$GLOBALS['ga_shortcodes']    = array();
$GLOBALS['ga_scripts']       = array();
$GLOBALS['ga_users']         = array();
$GLOBALS['ga_user_meta']     = array();
$GLOBALS['ga_password_seed'] = 0;
$GLOBALS['ga_current_user']  = 0;
$GLOBALS['ga_auth_cookies']  = array();
$GLOBALS['ga_actions']       = array();
$GLOBALS['ga_ssl']           = true;
$GLOBALS['ga_logged_in']     = false;
$GLOBALS['ga_roles']         = array(
	'administrator' => array(),
	'editor'        => array(),
	'author'        => array(),
	'contributor'   => array(),
	'subscriber'    => array(),
);

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['ga_options'] ) ? $GLOBALS['ga_options'][ $key ] : $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function wp_parse_url( $value ) {
	return parse_url( (string) $value );
}

function home_url( $path = '/' ) {
	return 'https://example.test' . ( '/' === substr( (string) $path, 0, 1 ) ? $path : '/' . $path );
}

function rest_url( $path = '' ) {
	return 'https://example.test/wp-json/' . ltrim( $path, '/' );
}

function esc_url_raw( $value, $protocols = null ) {
	$value = filter_var( (string) $value, FILTER_SANITIZE_URL );
	$parts = parse_url( $value );

	if ( is_array( $protocols ) && isset( $parts['scheme'] ) && ! in_array( strtolower( $parts['scheme'] ), $protocols, true ) ) {
		return '';
	}

	return $value;
}

function wp_roles() {
	return (object) array( 'roles' => $GLOBALS['ga_roles'] );
}

function wp_generate_password( $length = 12 ) {
	$GLOBALS['ga_password_seed']++;
	$value = 'DeterministicPassword' . $GLOBALS['ga_password_seed'] . 'abcdefghijklmnopqrstuvwxyz';

	return substr( $value, 0, $length );
}

function wp_salt() {
	return 'deterministic-test-salt';
}

function set_transient( $key, $value, $expiration ) {
	$GLOBALS['ga_transients'][ $key ] = array( 'value' => $value, 'expiration' => $expiration );
	return true;
}

function get_transient( $key ) {
	return isset( $GLOBALS['ga_transients'][ $key ] ) ? $GLOBALS['ga_transients'][ $key ]['value'] : false;
}

function delete_transient( $key ) {
	unset( $GLOBALS['ga_transients'][ $key ] );
	return true;
}

function is_email( $email ) {
	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function register_rest_route( $namespace, $route, $args ) {
	$GLOBALS['ga_routes'][ $namespace . $route ] = $args;
	return true;
}

function __return_true() {
	return true;
}

function is_ssl() {
	return $GLOBALS['ga_ssl'];
}

function wp_unslash( $value ) {
	return $value;
}

function wp_validate_redirect( $location, $fallback = '' ) {
	$location_parts = parse_url( $location );
	$home_parts     = parse_url( home_url( '/' ) );

	return is_array( $location_parts ) && is_array( $home_parts ) && strtolower( $location_parts['host'] ?? '' ) === strtolower( $home_parts['host'] ?? '' ) ? $location : $fallback;
}

function add_query_arg( $key, $value, $url ) {
	$separator = false === strpos( $url, '?' ) ? '?' : '&';
	return $url . $separator . rawurlencode( $key ) . '=' . rawurlencode( $value );
}

function get_users( $args ) {
	$matches = array();

	foreach ( $GLOBALS['ga_users'] as $user ) {
		$meta = $GLOBALS['ga_user_meta'][ $user->ID ][ $args['meta_key'] ] ?? null;
		if ( $meta === $args['meta_value'] ) {
			$matches[] = $user;
		}
	}

	return array_slice( $matches, 0, (int) ( $args['number'] ?? 2 ) );
}

function get_user_by( $field, $value ) {
	foreach ( $GLOBALS['ga_users'] as $user ) {
		if ( ( 'email' === $field && $user->user_email === $value ) || ( 'id' === $field && $user->ID === (int) $value ) ) {
			return $user;
		}
	}

	return false;
}

function wp_insert_user( $data ) {
	if ( username_exists( $data['user_login'] ) || get_user_by( 'email', $data['user_email'] ) ) {
		return new WP_Error( 'existing_user' );
	}

	$id = count( $GLOBALS['ga_users'] ) + 1;
	$GLOBALS['ga_users'][ $id ] = new WP_User( $id, $data['user_login'], $data['user_email'], array( $data['role'] ) );
	$GLOBALS['ga_user_meta'][ $id ] = array(
		'first_name' => $data['first_name'],
		'last_name'  => $data['last_name'],
	);

	return $id;
}

function update_user_meta( $user_id, $key, $value ) {
	$GLOBALS['ga_user_meta'][ $user_id ][ $key ] = $value;
	return true;
}

function username_exists( $username ) {
	foreach ( $GLOBALS['ga_users'] as $user ) {
		if ( $user->user_login === $username ) {
			return $user->ID;
		}
	}

	return false;
}

function sanitize_user( $username ) {
	return preg_replace( '/[^a-z0-9_\-]/i', '', (string) $username );
}

function wp_set_current_user( $user_id ) {
	$GLOBALS['ga_current_user'] = $user_id;
	return get_user_by( 'id', $user_id );
}

function wp_set_auth_cookie( $user_id, $remember = false, $secure = '' ) {
	$GLOBALS['ga_auth_cookies'][] = compact( 'user_id', 'remember', 'secure' );
}

function do_action( $hook ) {
	$GLOBALS['ga_actions'][] = array( 'hook' => $hook, 'args' => array_slice( func_get_args(), 1 ) );
}

function shortcode_atts( $defaults, $attributes ) {
	return array_merge( $defaults, is_array( $attributes ) ? $attributes : array() );
}

function is_user_logged_in() {
	return $GLOBALS['ga_logged_in'];
}

function wp_enqueue_script( $handle, $src, $dependencies, $version, $in_footer ) {
	$GLOBALS['ga_scripts'][ $handle ] = compact( 'src', 'dependencies', 'version', 'in_footer' );
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['ga_shortcodes'][ $tag ] = $callback;
}

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $value ) {
	return esc_attr( $value );
}

function esc_html__( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

require_once dirname( __DIR__ ) . '/inc/class-yby-social-login.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-google-token-verifier.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-google-auth-service.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-google-auth-rest-controller.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-social-login-shortcodes.php';

function ga_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function ga_reset() {
	$GLOBALS['ga_options']       = array();
	$GLOBALS['ga_transients']    = array();
	$GLOBALS['ga_routes']        = array();
	$GLOBALS['ga_shortcodes']    = array();
	$GLOBALS['ga_scripts']       = array();
	$GLOBALS['ga_users']         = array();
	$GLOBALS['ga_user_meta']     = array();
	$GLOBALS['ga_password_seed'] = 0;
	$GLOBALS['ga_current_user']  = 0;
	$GLOBALS['ga_auth_cookies']  = array();
	$GLOBALS['ga_actions']       = array();
	$GLOBALS['ga_ssl']           = true;
	$GLOBALS['ga_logged_in']     = false;
	$_COOKIE                     = array();
	$_GET                        = array();
	YBY_Social_Login_Shortcodes::reset_request_state();
	ga_set_settings();
}

function ga_set_settings( $overrides = array() ) {
	$settings = array_merge(
		YBY_Social_Login::google_defaults(),
		array(
			'enabled'          => true,
			'client_id'        => 'test-client.apps.googleusercontent.com',
			'redirect_url'     => '/account/',
			'disabled_roles'   => array( 'administrator', 'editor' ),
			'username_prefix'  => 'google_',
			'fallback_prefix'  => 'user_',
			'default_role'     => 'subscriber',
		),
		$overrides
	);
	$GLOBALS['ga_options'][ YBY_Social_Login::option_key() ] = array( 'google' => $settings );
}

function ga_base64url( $value ) {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}

function ga_token( $private_key, $claims, $header = array(), $signing_key = null ) {
	$header  = array_merge( array( 'alg' => 'RS256', 'kid' => 'test-key' ), $header );
	$encoded = ga_base64url( json_encode( $header ) ) . '.' . ga_base64url( json_encode( $claims ) );
	openssl_sign( $encoded, $signature, $signing_key ?: $private_key, OPENSSL_ALGO_SHA256 );

	return $encoded . '.' . ga_base64url( $signature );
}

function ga_claims( $overrides = array() ) {
	return array_merge(
		array(
			'aud'            => 'test-client.apps.googleusercontent.com',
			'iss'            => 'https://accounts.google.com',
			'exp'            => 1700003600,
			'iat'            => 1700000000,
			'nonce'          => 'valid-nonce',
			'sub'            => 'google-subject-123',
			'email'          => 'person@gmail.com',
			'email_verified' => true,
			'name'           => 'Test Person',
			'given_name'     => 'Test',
			'family_name'    => 'Person',
			'picture'        => 'https://lh3.googleusercontent.com/avatar',
		),
		$overrides
	);
}

function ga_error_code( $value ) {
	return is_wp_error( $value ) ? $value->get_error_code() : '';
}

$key = openssl_pkey_new(
	array(
		'private_key_bits' => 2048,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	)
);
ga_assert( false !== $key, 'Test RSA key generation failed.' );
openssl_pkey_export( $key, $private_key );
$details    = openssl_pkey_get_details( $key );
$public_key = $details['key'];
$retrievals = 0;
$retriever  = static function () use ( &$retrievals, $public_key ) {
	$retrievals++;
	return array(
		'certificates' => array( 'test-key' => $public_key ),
		'max_age'      => 1800,
	);
};
$clock      = static function () {
	return 1700000100;
};

$tests = array();

$tests['shortcode_visibility_and_script_scope'] = static function () {
	ga_reset();
	$shortcode = new YBY_Social_Login_Shortcodes();
	ga_set_settings( array( 'enabled' => false ) );
	ga_assert( '' === $shortcode->render( array( 'provider' => 'google' ) ), 'Disabled Google must render nothing.' );
	ga_assert( empty( $GLOBALS['ga_scripts'] ), 'Disabled Google must not enqueue GIS.' );

	ga_set_settings( array( 'client_id' => '', 'enabled' => true ) );
	ga_assert( '' === $shortcode->render( array( 'provider' => 'google' ) ), 'Missing Client ID must render nothing.' );
	ga_assert( '' === $shortcode->render( array( 'provider' => 'facebook' ) ), 'Unknown provider must render nothing.' );

	ga_set_settings();
	$first  = $shortcode->render( array( 'provider' => 'google' ) );
	$second = $shortcode->render( array( 'provider' => 'google' ) );
	ga_assert( isset( $GLOBALS['ga_scripts']['yby-google-identity-services'] ), 'Rendered shortcode must enqueue GIS.' );
	ga_assert( 1 === count( $GLOBALS['ga_scripts'] ), 'GIS script must be enqueued exactly once.' );
	ga_assert( 'https://accounts.google.com/gsi/client' === $GLOBALS['ga_scripts']['yby-google-identity-services']['src'], 'GIS URL is invalid.' );
	ga_assert( 1 === substr_count( $first . $second, 'id="g_id_onload"' ), 'Page configuration must use the required unique GIS ID.' );
	ga_assert( 0 === substr_count( $first . $second, 'id="yby-google-identity-config"' ), 'Legacy project-specific configuration ID must not render.' );
	ga_assert( 2 === substr_count( $first . $second, 'class="g_id_signin"' ), 'Each shortcode must render one button.' );
	ga_assert( false !== strpos( $first, 'data-login_uri="https://example.test/wp-json/yby/v1/auth/google"' ), 'Google login URI must remain on the YBY REST endpoint.' );
	ga_assert( false !== strpos( $first, 'data-auto_select="false"' ) && false !== strpos( $first, 'data-ux_mode="redirect"' ), 'GIS redirect safety configuration is incomplete.' );
	ga_assert( false === strpos( $first, 'client_secret' ), 'Shortcode must not expose a Client Secret.' );

	$GLOBALS['ga_logged_in'] = true;
	ga_assert( '' === $shortcode->render( array( 'provider' => 'google' ) ), 'Logged-in users must not receive another Google button.' );
};

$tests['route_is_post_only'] = static function () {
	ga_reset();
	$controller = new YBY_Google_Auth_REST_Controller();
	$controller->register_routes();
	$route = $GLOBALS['ga_routes']['yby/v1/auth/google'];
	ga_assert( 'POST' === $route['methods'], 'Google route must be POST only.' );
	ga_assert( true === call_user_func( $route['permission_callback'] ), 'Google route must allow anonymous requests.' );
};

$tests['request_and_csrf_rejection'] = static function () {
	ga_reset();
	$controller = new YBY_Google_Auth_REST_Controller();
	$response   = $controller->authenticate( new WP_REST_Request() );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_request' ), 'Missing credential must be rejected.' );
	$response = $controller->authenticate( new WP_REST_Request( array( 'credential' => str_repeat( 'a', YBY_Google_Token_Verifier::MAX_TOKEN_LENGTH + 1 ) ) ) );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_request' ), 'Oversized credential must be rejected.' );
	$response = $controller->authenticate( new WP_REST_Request( array( 'credential' => 'a.b.c', 'g_csrf_token' => 'body' ) ) );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_csrf' ), 'Missing CSRF cookie must be rejected.' );
	$_COOKIE['g_csrf_token'] = 'cookie';
	$response = $controller->authenticate( new WP_REST_Request( array( 'credential' => 'a.b.c', 'g_csrf_token' => 'body' ) ) );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_csrf' ), 'CSRF mismatch must be rejected.' );

	$GLOBALS['ga_ssl']       = false;
	$_COOKIE['g_csrf_token'] = 'matching';
	$response = $controller->authenticate( new WP_REST_Request( array( 'credential' => 'a.b.c', 'g_csrf_token' => 'matching' ) ) );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_request' ), 'Non-local HTTP authentication must be rejected.' );
};

$tests['jwt_structure_algorithm_and_kid'] = static function () use ( $private_key, $retriever, $clock ) {
	ga_reset();
	$verifier = new YBY_Google_Token_Verifier( $retriever, $clock );
	$valid_nonce = static function () {
		return true;
	};
	ga_assert( 'invalid_google_token' === ga_error_code( $verifier->verify( 'bad-token', 'test-client.apps.googleusercontent.com', $valid_nonce ) ), 'Malformed JWT must be rejected.' );
	ga_assert( 'invalid_google_token' === ga_error_code( $verifier->verify( ga_token( $private_key, ga_claims(), array( 'alg' => 'HS256' ) ), 'test-client.apps.googleusercontent.com', $valid_nonce ) ), 'Non-RS256 JWT must be rejected.' );
	ga_assert( 'invalid_google_token' === ga_error_code( $verifier->verify( ga_token( $private_key, ga_claims(), array( 'kid' => '' ) ), 'test-client.apps.googleusercontent.com', $valid_nonce ) ), 'Missing kid must be rejected.' );
};

$tests['certificate_refresh_and_signature'] = static function () use ( $private_key, $public_key, $clock ) {
	ga_reset();
	$GLOBALS['ga_transients'][ YBY_Google_Token_Verifier::CERTIFICATE_CACHE_KEY ] = array(
		'value'      => array( 'stale-key' => $public_key ),
		'expiration' => 100,
	);
	$count     = 0;
	$retriever = static function () use ( &$count, $public_key ) {
		$count++;
		return array( 'certificates' => array( 'test-key' => $public_key ), 'max_age' => 600 );
	};
	$verifier = new YBY_Google_Token_Verifier( $retriever, $clock );
	$result   = $verifier->verify( ga_token( $private_key, ga_claims() ), 'test-client.apps.googleusercontent.com', static function () { return true; } );
	ga_assert( ! is_wp_error( $result ) && 1 === $count, 'Unknown kid must refresh certificates exactly once.' );
	ga_assert( 600 === $GLOBALS['ga_transients'][ YBY_Google_Token_Verifier::CERTIFICATE_CACHE_KEY ]['expiration'], 'Certificate max-age must govern cache lifetime.' );

	$other_key = openssl_pkey_new( array( 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA ) );
	$invalid   = $verifier->verify( ga_token( $private_key, ga_claims(), array(), $other_key ), 'test-client.apps.googleusercontent.com', static function () { return true; } );
	ga_assert( 'invalid_google_token' === ga_error_code( $invalid ), 'Invalid signature must be rejected.' );

	ga_reset();
	$malformed = new YBY_Google_Token_Verifier(
		static function () {
			return array( 'certificates' => array( 'bad-key' => 'not-a-pem' ), 'max_age' => 600 );
		},
		$clock
	);
	$result = $malformed->verify( ga_token( $private_key, ga_claims(), array( 'kid' => 'bad-key' ) ), 'test-client.apps.googleusercontent.com', static function () { return true; } );
	ga_assert( 'verification_unavailable' === ga_error_code( $result ), 'Malformed certificate response must fail closed.' );
	ga_assert( false === get_transient( YBY_Google_Token_Verifier::CERTIFICATE_CACHE_KEY ), 'Malformed certificate response must not be cached.' );
};

$tests['claim_validation'] = static function () use ( $private_key, $retriever, $clock ) {
	$cases = array(
		'wrong_aud'        => array( array( 'aud' => 'other.apps.googleusercontent.com' ), 'invalid_google_token' ),
		'wrong_iss'        => array( array( 'iss' => 'https://evil.test' ), 'invalid_google_token' ),
		'expired'          => array( array( 'exp' => 1700000000 ), 'invalid_google_token' ),
		'future_iat'       => array( array( 'iat' => 1700001000 ), 'invalid_google_token' ),
		'missing_sub'      => array( array( 'sub' => '' ), 'invalid_google_token' ),
		'missing_email'    => array( array( 'email' => '' ), 'email_required' ),
		'unverified_email' => array( array( 'email_verified' => false ), 'email_verification_required' ),
		'missing_nonce'    => array( array( 'nonce' => null ), 'invalid_nonce' ),
	);

	foreach ( $cases as $case ) {
		ga_reset();
		$verifier = new YBY_Google_Token_Verifier( $retriever, $clock );
		$result   = $verifier->verify( ga_token( $private_key, ga_claims( $case[0] ) ), 'test-client.apps.googleusercontent.com', static function () { return true; } );
		ga_assert( $case[1] === ga_error_code( $result ), 'Claim rejection failed: ' . $case[1] );
	}

	ga_reset();
	$verifier = new YBY_Google_Token_Verifier( $retriever, $clock );
	$result   = $verifier->verify( ga_token( $private_key, ga_claims() ), 'test-client.apps.googleusercontent.com', static function () { return false; } );
	ga_assert( 'invalid_nonce' === ga_error_code( $result ), 'Wrong nonce must be rejected.' );
};

$tests['registration_authority_and_role'] = static function () {
	ga_reset();
	$service = new YBY_Google_Auth_Service();
	$user    = $service->authenticate( ga_claims(), YBY_Social_Login::get_google_options() );
	ga_assert( $user instanceof WP_User && 1 === count( $GLOBALS['ga_users'] ), 'Gmail registration must succeed once.' );
	ga_assert( 'google-subject-123' === $GLOBALS['ga_user_meta'][ $user->ID ]['_yby_google_sub'], 'New user must receive one Google sub mapping.' );
	ga_assert( 'google' === $GLOBALS['ga_user_meta'][ $user->ID ]['_yby_social_provider'], 'Provider mapping is invalid.' );
	ga_assert( false === strpos( $user->user_login, 'person@gmail.com' ) && false === strpos( $user->user_login, 'google-subject-123' ), 'Username must not expose the email or Google sub.' );
	ga_assert( 1 === count( $GLOBALS['ga_auth_cookies'] ) && true === $GLOBALS['ga_auth_cookies'][0]['remember'], 'WordPress login cookie must be established.' );

	ga_reset();
	$user = $service->authenticate( ga_claims( array( 'email' => 'person@example.org', 'hd' => 'example.org' ) ), YBY_Social_Login::get_google_options() );
	ga_assert( $user instanceof WP_User, 'Workspace hd registration must succeed.' );

	ga_reset();
	$result = $service->authenticate( ga_claims( array( 'email' => 'person@example.org' ) ), YBY_Social_Login::get_google_options() );
	ga_assert( 'email_verification_required' === ga_error_code( $result ) && empty( $GLOBALS['ga_users'] ), 'Third-party email without hd must be rejected.' );

	ga_reset();
	$settings                 = YBY_Social_Login::get_google_options();
	$settings['default_role'] = 'administrator';
	$user = $service->authenticate( ga_claims(), $settings );
	ga_assert( $user instanceof WP_User && array( 'subscriber' ) === $user->roles, 'Privileged registration role must fall back to subscriber.' );

	ga_reset();
	$settings                   = YBY_Social_Login::get_google_options();
	$settings['disabled_roles'] = array( 'subscriber' );
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( 'role_not_allowed' === ga_error_code( $result ) && empty( $GLOBALS['ga_users'] ), 'Disabled default registration role must be rejected.' );
};

$tests['returning_conflict_and_existing_email'] = static function () {
	ga_reset();
	$service = new YBY_Google_Auth_Service();
	$user    = $service->authenticate( ga_claims(), YBY_Social_Login::get_google_options() );
	$user2   = $service->authenticate( ga_claims(), YBY_Social_Login::get_google_options() );
	ga_assert( $user2 instanceof WP_User && $user->ID === $user2->ID && 1 === count( $GLOBALS['ga_users'] ), 'Returning sub must log into the mapped user without duplication.' );

	$GLOBALS['ga_users'][2]     = new WP_User( 2, 'duplicate', 'duplicate@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][2] = array( '_yby_google_sub' => 'google-subject-123' );
	$result = $service->authenticate( ga_claims(), YBY_Social_Login::get_google_options() );
	ga_assert( 'identity_conflict' === ga_error_code( $result ), 'Duplicate sub mappings must be rejected.' );

	ga_reset();
	$GLOBALS['ga_users'][1]     = new WP_User( 1, 'existing', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$result = $service->authenticate( ga_claims(), YBY_Social_Login::get_google_options() );
	ga_assert( 'existing_account_requires_login' === ga_error_code( $result ), 'Existing email must not be linked automatically.' );
	ga_assert( empty( $GLOBALS['ga_user_meta'][1]['_yby_google_sub'] ), 'Existing account must remain unmodified.' );

	ga_reset();
	$GLOBALS['ga_users'][1]     = new WP_User( 1, 'adminmapped', 'admin@gmail.com', array( 'administrator' ) );
	$GLOBALS['ga_user_meta'][1] = array( '_yby_google_sub' => 'google-subject-123' );
	$result = $service->authenticate( ga_claims( array( 'email' => 'admin@gmail.com' ) ), YBY_Social_Login::get_google_options() );
	ga_assert( 'role_not_allowed' === ga_error_code( $result ), 'Disabled returning-user role must be rejected.' );
};

$tests['controller_nonce_redirect_and_reuse'] = static function () use ( $private_key, $public_key, $clock ) {
	ga_reset();
	$nonce = YBY_Social_Login::issue_login_nonce();
	$token = ga_token( $private_key, ga_claims( array( 'nonce' => $nonce ) ) );
	$_COOKIE['g_csrf_token'] = 'matching-csrf';
	$verifier   = new YBY_Google_Token_Verifier(
		static function () use ( $public_key ) {
			return array( 'certificates' => array( 'test-key' => $public_key ), 'max_age' => 600 );
		},
		$clock
	);
	$controller = new YBY_Google_Auth_REST_Controller( $verifier, new YBY_Google_Auth_Service() );
	$request    = new WP_REST_Request(
		array(
			'credential'   => $token,
			'g_csrf_token' => 'matching-csrf',
			'redirect_url' => 'https://evil.test/',
			'role'         => 'administrator',
			'client_id'    => 'attacker.apps.googleusercontent.com',
		)
	);
	$response   = $controller->authenticate( $request );
	ga_assert( 302 === $response->status && 'https://example.test/account/?yby_social_login=success' === $response->headers['Location'], 'Successful redirect must use configured same-site URL.' );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $nonce ), 'Successful login nonce must be consumed.' );
	ga_assert( array( 'subscriber' ) === $GLOBALS['ga_users'][1]->roles, 'Request role must not influence registration.' );

	$response = $controller->authenticate( $request );
	ga_assert( false !== strpos( $response->headers['Location'], 'invalid_nonce' ), 'Consumed nonce must not be reusable.' );
	ga_assert( 1 === count( $GLOBALS['ga_users'] ), 'Nonce replay must not create another user.' );

	$location = $response->headers['Location'];
	foreach ( array( 'person@gmail.com', 'google-subject-123', $token, 'test-client.apps.googleusercontent.com' ) as $secret ) {
		ga_assert( false === strpos( $location, $secret ), 'Redirect must not expose credential or PII.' );
	}
};

$tests['schema_and_regression_boundary'] = static function () {
	$main       = file_get_contents( dirname( __DIR__ ) . '/yby-core.php' );
	$core       = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
	$controller = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-auth-rest-controller.php' );
	$verifier   = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-token-verifier.php' );
	$service    = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-auth-service.php' );
	$all        = $controller . $verifier . $service;

	ga_assert( false !== strpos( $main, "define( 'YBY_DATABASE_VERSION', '1.1.0' );" ), 'Database version must remain 1.1.0.' );
	ga_assert( false === stripos( $all, 'CREATE TABLE' ), 'Google authentication must not create a database table.' );
	ga_assert( false === stripos( $all, 'client_secret' ), 'No Client Secret may exist.' );
	ga_assert( false === stripos( $all, 'tokeninfo' ), 'Production verification must not depend on tokeninfo.' );
	ga_assert( false !== strpos( $core, 'YBY_Lead_REST_Controller' ) && false !== strpos( $core, 'YBY_Inquiry_Shortcodes' ), 'Lead and Inquiry registration must remain intact.' );
};

$results = array();
foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
