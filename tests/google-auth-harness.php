<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
define( 'YBY_CORE_PLUGIN_URL', 'https://example.test/wp-content/plugins/yby-core/' );
define( 'YBY_CORE_VERSION', '1.5.0-dev' );
define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_test' );

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
	protected $headers;
	protected $json_params;

	public function __construct( $params = array(), $headers = array(), $json_params = null ) {
		$this->params      = $params;
		$this->headers     = array_change_key_case( $headers, CASE_LOWER );
		$this->json_params = 3 <= func_num_args() ? $json_params : $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}

	public function get_header( $key ) {
		return $this->headers[ strtolower( (string) $key ) ] ?? '';
	}

	public function get_json_params() {
		return $this->json_params;
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
$GLOBALS['ga_styles']        = array();
$GLOBALS['ga_inline_scripts'] = array();
$GLOBALS['ga_dequeued_scripts'] = array();
$GLOBALS['ga_users']         = array();
$GLOBALS['ga_user_meta']     = array();
$GLOBALS['ga_password_seed'] = 0;
$GLOBALS['ga_current_user']  = 0;
$GLOBALS['ga_auth_cookies']  = array();
$GLOBALS['ga_cookie_validation'] = array();
$GLOBALS['ga_cookie_validation_calls'] = array();
$GLOBALS['ga_last_logged_in_cookie'] = '';
$GLOBALS['ga_actions']       = array();
$GLOBALS['ga_registered_actions'] = array();
$GLOBALS['ga_ssl']           = true;
$GLOBALS['ga_logged_in']     = false;
$GLOBALS['ga_is_admin']      = false;
$GLOBALS['ga_is_singular']   = true;
$GLOBALS['ga_queried_id']    = 1;
$GLOBALS['ga_post_content']  = '';
$GLOBALS['ga_post_meta']     = array();
$GLOBALS['ga_nocache_calls'] = 0;
$GLOBALS['ga_add_meta_fail'] = false;
$GLOBALS['ga_is_feed']       = false;
$GLOBALS['ga_is_embed']      = false;
$GLOBALS['ga_is_preview']    = false;
$GLOBALS['ga_doing_ajax']    = false;
$GLOBALS['ga_doing_cron']    = false;
$GLOBALS['ga_json_request']  = false;
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

function get_user_meta( $user_id, $key, $single = false ) {
	unset( $single );
	return $GLOBALS['ga_user_meta'][ $user_id ][ $key ] ?? '';
}

function add_user_meta( $user_id, $key, $value, $unique = false ) {
	if ( $GLOBALS['ga_add_meta_fail'] || ( $unique && isset( $GLOBALS['ga_user_meta'][ $user_id ][ $key ] ) ) ) {
		return false;
	}

	$GLOBALS['ga_user_meta'][ $user_id ][ $key ] = $value;
	return true;
}

function delete_user_meta( $user_id, $key, $value = '' ) {
	if ( isset( $GLOBALS['ga_user_meta'][ $user_id ][ $key ] ) && ( '' === $value || $GLOBALS['ga_user_meta'][ $user_id ][ $key ] === $value ) ) {
		unset( $GLOBALS['ga_user_meta'][ $user_id ][ $key ] );
		return true;
	}

	return false;
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
	$GLOBALS['ga_logged_in']    = 0 < (int) $user_id;
	return get_user_by( 'id', $user_id );
}

function wp_set_auth_cookie( $user_id, $remember = false, $secure = '' ) {
	$GLOBALS['ga_auth_cookies'][] = compact( 'user_id', 'remember', 'secure' );
	$cookie = 'wordpress-cookie-for-user-' . (int) $user_id;
	$GLOBALS['ga_cookie_validation'][ $cookie ] = array(
		'user_id'    => (int) $user_id,
		'expires_at' => time() + 3600,
		'signature'  => true,
		'site'       => 'example.test',
	);
	$GLOBALS['ga_last_logged_in_cookie'] = $cookie;
}

function wp_validate_auth_cookie( $cookie = '', $scheme = '' ) {
	$GLOBALS['ga_cookie_validation_calls'][] = compact( 'cookie', 'scheme' );
	$record = $GLOBALS['ga_cookie_validation'][ $cookie ] ?? null;

	if (
		'logged_in' !== $scheme ||
		! is_array( $record ) ||
		empty( $record['signature'] ) ||
		'example.test' !== $record['site'] ||
		(int) $record['expires_at'] < time()
	) {
		return false;
	}

	return (int) $record['user_id'];
}

function do_action( $hook ) {
	$GLOBALS['ga_actions'][] = array( 'hook' => $hook, 'args' => array_slice( func_get_args(), 1 ) );
}

function add_action( $hook, $callback, $priority = 10 ) {
	$GLOBALS['ga_registered_actions'][ $hook ][] = compact( 'callback', 'priority' );
}

function shortcode_atts( $defaults, $attributes ) {
	return array_merge( $defaults, is_array( $attributes ) ? $attributes : array() );
}

function shortcode_parse_atts( $text ) {
	$attributes = array();

	if ( preg_match( '/\bprovider\s*=\s*(?:"([^"]+)"|\'([^\']+)\'|([^\s]+))/i', (string) $text, $match ) ) {
		$attributes['provider'] = $match[1] ?: ( $match[2] ?: $match[3] );
	}

	return $attributes;
}

function is_user_logged_in() {
	return $GLOBALS['ga_logged_in'];
}

function is_admin() {
	return $GLOBALS['ga_is_admin'];
}

function is_singular() {
	return $GLOBALS['ga_is_singular'];
}

function is_feed() {
	return $GLOBALS['ga_is_feed'];
}

function is_embed() {
	return $GLOBALS['ga_is_embed'];
}

function is_preview() {
	return $GLOBALS['ga_is_preview'];
}

function wp_doing_ajax() {
	return $GLOBALS['ga_doing_ajax'];
}

function wp_doing_cron() {
	return $GLOBALS['ga_doing_cron'];
}

function wp_is_json_request() {
	return $GLOBALS['ga_json_request'];
}

function get_queried_object_id() {
	return $GLOBALS['ga_queried_id'];
}

function get_post_field( $field, $post_id ) {
	return 'post_content' === $field && (int) $post_id === (int) $GLOBALS['ga_queried_id'] ? $GLOBALS['ga_post_content'] : '';
}

function get_post_meta( $post_id, $key ) {
	return $GLOBALS['ga_post_meta'][ (int) $post_id ][ $key ] ?? '';
}

function nocache_headers() {
	$GLOBALS['ga_nocache_calls']++;
}

function wp_enqueue_script( $handle, $src, $dependencies, $version, $in_footer ) {
	$GLOBALS['ga_scripts'][ $handle ] = compact( 'src', 'dependencies', 'version', 'in_footer' );
}

function wp_enqueue_style( $handle, $src, $dependencies, $version ) {
	$GLOBALS['ga_styles'][ $handle ] = compact( 'src', 'dependencies', 'version' );
}

function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	$GLOBALS['ga_inline_scripts'][ $handle ][] = compact( 'data', 'position' );
	return true;
}

function wp_dequeue_script( $handle ) {
	$GLOBALS['ga_dequeued_scripts'][] = $handle;
	unset( $GLOBALS['ga_scripts'][ $handle ] );
}

function wp_json_encode( $value ) {
	return json_encode( $value );
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
require_once dirname( __DIR__ ) . '/inc/class-yby-google-one-tap-controller.php';
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
	$GLOBALS['ga_styles']        = array();
	$GLOBALS['ga_inline_scripts'] = array();
	$GLOBALS['ga_dequeued_scripts'] = array();
	$GLOBALS['ga_users']         = array();
	$GLOBALS['ga_user_meta']     = array();
	$GLOBALS['ga_password_seed'] = 0;
	$GLOBALS['ga_current_user']  = 0;
	$GLOBALS['ga_auth_cookies']  = array();
	$GLOBALS['ga_cookie_validation'] = array();
	$GLOBALS['ga_cookie_validation_calls'] = array();
	$GLOBALS['ga_last_logged_in_cookie'] = '';
	$GLOBALS['ga_actions']       = array();
	$GLOBALS['ga_registered_actions'] = array();
	$GLOBALS['ga_ssl']           = true;
	$GLOBALS['ga_logged_in']     = false;
	$GLOBALS['ga_is_admin']      = false;
	$GLOBALS['ga_is_singular']   = true;
	$GLOBALS['ga_queried_id']    = 1;
	$GLOBALS['ga_post_content']  = '';
	$GLOBALS['ga_post_meta']     = array();
	$GLOBALS['ga_nocache_calls'] = 0;
	$GLOBALS['ga_add_meta_fail'] = false;
	$GLOBALS['ga_is_feed']       = false;
	$GLOBALS['ga_is_embed']      = false;
	$GLOBALS['ga_is_preview']    = false;
	$GLOBALS['ga_doing_ajax']    = false;
	$GLOBALS['ga_doing_cron']    = false;
	$GLOBALS['ga_json_request']  = false;
	$_COOKIE                     = array();
	$_GET                        = array();
	$_REQUEST                    = array();
	$_SERVER['REMOTE_ADDR']      = '192.0.2.10';
	$GLOBALS['pagenow']          = 'index.php';
	YBY_Social_Login_Shortcodes::reset_request_state();
	ga_set_settings();
}

function ga_set_settings( $overrides = array(), $general = array() ) {
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
	$GLOBALS['ga_options'][ YBY_Social_Login::option_key() ] = array_merge(
		array(
			'add_to_login_page' => false,
			'google'            => $settings,
		),
		$general
	);
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

function ga_onetap_headers( $overrides = array() ) {
	return array_merge(
		array(
			'origin'                           => 'https://example.test',
			'content-type'                     => 'application/json',
			'x-andy-core-one-tap'              => '1',
			'x-andy-core-one-tap-session'      => 'deterministic-session-12345',
		),
		$overrides
	);
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

$tests['unrelated_page_remains_cacheable'] = static function () {
	ga_reset();
	$shortcode = new YBY_Social_Login_Shortcodes();
	$GLOBALS['ga_post_content'] = '<p>Unrelated Bottle page.</p>';
	$shortcode->maybe_disable_page_cache();
	ga_assert( 0 === $GLOBALS['ga_nocache_calls'], 'Unrelated pages must remain cacheable.' );
	ga_assert( empty( $GLOBALS['ga_actions'] ), 'Unrelated pages must not signal LiteSpeed no-cache.' );
};

$tests['shortcode_visibility_and_script_scope'] = static function () {
	ga_reset();
	$shortcode = new YBY_Social_Login_Shortcodes();
	$shortcode->register();
	ga_assert( isset( $GLOBALS['ga_registered_actions']['template_redirect'][0] ), 'Social Login must register an early cache-control hook.' );
	ga_assert( 0 === $GLOBALS['ga_registered_actions']['template_redirect'][0]['priority'], 'Cache-control hook must run at the earliest template redirect priority.' );
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
	ga_assert( false !== strpos( $first, 'data-auto_prompt="false"' ), 'Google One Tap must be disabled.' );
	ga_assert( false !== strpos( $first, 'data-auto_select="false"' ), 'Automatic Google login must be disabled.' );
	ga_assert( false !== strpos( $first, 'data-button_auto_select="false"' ), 'FedCM button auto-select must be disabled.' );
	ga_assert( false !== strpos( $first, 'data-ux_mode="redirect"' ), 'GIS redirect mode must remain configured.' );
	ga_assert( false !== strpos( $first, 'data-width="280"' ), 'Google button width must remain fixed at 280.' );
	ga_assert( false !== strpos( $first, 'data-locale="en"' ), 'Google button locale must remain English.' );
	ga_assert( 1 === $GLOBALS['ga_nocache_calls'], 'Active Google shortcode must mark the request non-cacheable once.' );
	ga_assert( 1 === count( array_filter( $GLOBALS['ga_actions'], static function ( $action ) { return 'litespeed_control_set_nocache' === $action['hook']; } ) ), 'Active Google shortcode must signal LiteSpeed no-cache once.' );
	ga_assert( false === strpos( $first, 'client_secret' ), 'Shortcode must not expose a Client Secret.' );

	$GLOBALS['ga_logged_in'] = true;
	ga_assert( '' === $shortcode->render( array( 'provider' => 'google' ) ), 'Logged-in users must not receive another Google button.' );
};

$tests['stored_shortcode_cache_and_nonce_isolation'] = static function () {
	ga_reset();
	$shortcode = new YBY_Social_Login_Shortcodes();
	$GLOBALS['ga_post_content'] = '[yby_social_login provider="google"]';
	$shortcode->maybe_disable_page_cache();
	ga_assert( 1 === $GLOBALS['ga_nocache_calls'], 'Stored Google shortcode must disable page caching before render.' );

	YBY_Social_Login_Shortcodes::reset_request_state();
	$GLOBALS['ga_nocache_calls'] = 0;
	$GLOBALS['ga_actions']       = array();
	$GLOBALS['ga_post_content']  = '';
	$GLOBALS['ga_post_meta'][1]['_bricks_page_content_2'] = array(
		array( 'settings' => array( 'text' => '[yby_social_login provider="google"]' ) ),
	);
	$shortcode->maybe_disable_page_cache();
	ga_assert( 1 === $GLOBALS['ga_nocache_calls'], 'Bricks Google shortcode must disable page caching before render.' );

	YBY_Social_Login_Shortcodes::reset_request_state();
	$first = $shortcode->render( array( 'provider' => 'google' ) );
	preg_match( '/data-nonce="([^"]+)"/', $first, $first_match );
	$first_nonce = $first_match[1] ?? '';
	ga_assert( '' !== $first_nonce && YBY_Social_Login::is_login_nonce_valid( $first_nonce ), 'First request must issue a valid nonce.' );
	YBY_Social_Login::consume_login_nonce( $first_nonce );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $first_nonce ), 'Consumed nonce must not remain reusable.' );

	YBY_Social_Login_Shortcodes::reset_request_state();
	$second = $shortcode->render( array( 'provider' => 'google' ) );
	preg_match( '/data-nonce="([^"]+)"/', $second, $second_match );
	$second_nonce = $second_match[1] ?? '';
	ga_assert( '' !== $second_nonce && $first_nonce !== $second_nonce, 'Every fresh request must issue a fresh nonce.' );
	ga_assert( YBY_Social_Login::is_login_nonce_valid( $second_nonce ), 'Fresh markup must not reuse the consumed cached nonce.' );
};

$tests['safe_allowlisted_error_messages'] = static function () {
	$messages = array(
		'google_not_configured'            => 'Google sign-in is not configured.',
		'google_login_disabled'            => 'Google sign-in is currently unavailable.',
		'invalid_request'                  => 'The Google login response was incomplete.',
		'invalid_csrf'                     => 'The login security check failed.',
		'invalid_nonce'                    => 'The login session expired.',
		'invalid_google_token'             => 'Google identity verification failed.',
		'email_required'                   => 'Google did not provide an email address',
		'email_verification_required'      => 'Google could not confirm this account email.',
		'existing_account_requires_login' => 'An account already exists with this email, but Google sign-in is not linked to it.',
		'identity_conflict'                => 'This Google account could not be matched safely.',
		'role_not_allowed'                 => 'Google login is not available for this account.',
		'registration_failed'              => 'The account could not be created.',
		'login_failed'                     => 'The account was verified, but sign-in could not be completed.',
		'verification_unavailable'         => 'Google verification is temporarily unavailable.',
	);

	foreach ( $messages as $code => $expected ) {
		ga_reset();
		$_GET['yby_social_login'] = $code;
		$output = ( new YBY_Social_Login_Shortcodes() )->render( array( 'provider' => 'google' ) );
		preg_match( '/<p class="yby-social-login__message[^"]*">([^<]+)<\/p>/', $output, $message_match );
		$message = $message_match[1] ?? '';
		ga_assert( false !== strpos( $message, $expected ), 'Allowlisted error must render a useful message: ' . $code );
		ga_assert( false !== strpos( $output, 'data-auto_prompt="false"' ), 'Error redirects must not automatically reopen the Google prompt.' );
		foreach ( array( 'person@gmail.com', 'google-subject-123', 'test-client.apps.googleusercontent.com', 'C:\\server\\path' ) as $sensitive ) {
			ga_assert( false === strpos( $message, $sensitive ), 'Public error message must remain PII-safe: ' . $code );
		}
	}
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

$tests['safe_existing_account_association'] = static function () {
	ga_reset();
	$service = new YBY_Google_Auth_Service();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'existing-subscriber', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array(
		'first_name'   => 'Original',
		'last_name'    => 'Subscriber',
		'display_name' => 'Original Subscriber',
	);
	$profile_before = array(
		'login' => $GLOBALS['ga_users'][1]->user_login,
		'email' => $GLOBALS['ga_users'][1]->user_email,
		'roles' => $GLOBALS['ga_users'][1]->roles,
		'meta'  => $GLOBALS['ga_user_meta'][1],
	);
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims(), $settings );

	ga_assert( $result instanceof WP_User && 1 === $result->ID, 'Enabled Gmail association must use the existing Subscriber.' );
	ga_assert( 1 === count( $GLOBALS['ga_users'] ), 'Association must not create a duplicate WordPress user.' );
	ga_assert( 'google-subject-123' === $GLOBALS['ga_user_meta'][1]['_yby_google_sub'], 'Association must write the verified Google sub.' );
	ga_assert( 'google' === $GLOBALS['ga_user_meta'][1]['_yby_social_provider'], 'Association must record the Google provider.' );
	ga_assert( isset( $GLOBALS['ga_user_meta'][1]['_yby_social_registered_at'], $GLOBALS['ga_user_meta'][1]['_yby_social_last_login_at'] ), 'Association timestamps must be stored.' );
	ga_assert( 'https://lh3.googleusercontent.com/avatar' === $GLOBALS['ga_user_meta'][1]['_yby_social_avatar_url'], 'Valid provider avatar must be stored.' );
	ga_assert( $profile_before['login'] === $GLOBALS['ga_users'][1]->user_login && $profile_before['email'] === $GLOBALS['ga_users'][1]->user_email && $profile_before['roles'] === $GLOBALS['ga_users'][1]->roles, 'Association must preserve login, email, and roles.' );
	foreach ( $profile_before['meta'] as $key => $value ) {
		ga_assert( $value === $GLOBALS['ga_user_meta'][1][ $key ], 'Association must preserve existing profile metadata: ' . $key );
	}
	ga_assert( 1 === count( $GLOBALS['ga_auth_cookies'] ), 'Association must establish one normal WordPress session.' );

	$result = $service->authenticate( ga_claims( array( 'email' => 'changed@example.invalid' ) ), $settings );
	ga_assert( $result instanceof WP_User && 1 === $result->ID && 1 === count( $GLOBALS['ga_users'] ), 'Returning login must resolve by Google sub rather than email.' );

	ga_reset();
	$GLOBALS['ga_roles']['customer'] = array();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'existing-customer', 'person@example.org', array( 'customer' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims( array( 'email' => 'person@example.org', 'hd' => 'example.org' ) ), $settings );
	ga_assert( $result instanceof WP_User && 1 === $result->ID, 'Verified Workspace association must use the existing Customer.' );
};

$tests['association_role_and_identity_protection'] = static function () {
	$service = new YBY_Google_Auth_Service();

	foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager', 'social_manager', 'custom_role' ) as $role ) {
		ga_reset();
		$GLOBALS['ga_roles'][ $role ] = array();
		$GLOBALS['ga_users'][1] = new WP_User( 1, 'blocked-user', 'person@gmail.com', array( $role ) );
		$GLOBALS['ga_user_meta'][1] = array();
		$settings = YBY_Social_Login::get_google_options();
		$settings['auto_link_existing_accounts'] = true;
		$result = $service->authenticate( ga_claims(), $settings );
		ga_assert( 'role_not_allowed' === ga_error_code( $result ), 'Privileged or custom role must not be associated: ' . $role );
		ga_assert( empty( $GLOBALS['ga_user_meta'][1] ) && empty( $GLOBALS['ga_auth_cookies'] ), 'Blocked role must receive no mapping or session: ' . $role );
	}

	ga_reset();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'mixed-user', 'person@gmail.com', array( 'subscriber', 'editor' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( 'role_not_allowed' === ga_error_code( $result ), 'A mixed safe and privileged role set must be rejected.' );

	ga_reset();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'disabled-subscriber', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$settings['disabled_roles'] = array( 'subscriber' );
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( 'role_not_allowed' === ga_error_code( $result ), 'Explicitly disabled Subscriber must be rejected.' );

	ga_reset();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'different-sub', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array( '_yby_google_sub' => 'different-google-sub' );
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( 'identity_conflict' === ga_error_code( $result ), 'An existing different Google sub must be rejected.' );
	ga_assert( 'different-google-sub' === $GLOBALS['ga_user_meta'][1]['_yby_google_sub'], 'Existing Google identity must never be replaced.' );

	ga_reset();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'target-user', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_users'][2] = new WP_User( 2, 'mapped-user', 'mapped@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$GLOBALS['ga_user_meta'][2] = array( '_yby_google_sub' => 'google-subject-123' );
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( $result instanceof WP_User && 2 === $result->ID, 'A pre-existing sub mapping must remain authoritative over email.' );
	ga_assert( empty( $GLOBALS['ga_user_meta'][1] ), 'Sub mapped elsewhere must not be added to the email-matched user.' );

	ga_reset();
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'atomic-failure', 'person@gmail.com', array( 'subscriber' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$GLOBALS['ga_add_meta_fail'] = true;
	$settings = YBY_Social_Login::get_google_options();
	$settings['auto_link_existing_accounts'] = true;
	$result = $service->authenticate( ga_claims(), $settings );
	ga_assert( 'identity_conflict' === ga_error_code( $result ), 'Failed unique mapping write must return identity_conflict.' );
	ga_assert( empty( $GLOBALS['ga_user_meta'][1] ), 'Failed unique mapping write must leave no partial identity.' );
};

$tests['wordpress_login_page_integration'] = static function () {
	ga_reset();
	$shortcode = new YBY_Social_Login_Shortcodes();
	$GLOBALS['pagenow'] = 'wp-login.php';
	ga_assert( false === $shortcode->login_page_is_eligible(), 'WordPress login integration must default disabled.' );
	$shortcode->enqueue_login_page_assets();
	ga_assert( empty( $GLOBALS['ga_styles'] ), 'Disabled integration must not load login-page CSS.' );

	ga_set_settings( array(), array( 'add_to_login_page' => true ) );
	ga_assert( true === $shortcode->login_page_is_eligible(), 'Enabled integration must allow the default login screen.' );
	$shortcode->enqueue_login_page_assets();
	ga_assert( 1 === count( $GLOBALS['ga_styles'] ) && isset( $GLOBALS['ga_styles']['yby-social-login-login-page'] ), 'Enabled integration must load exactly one scoped login-page stylesheet.' );
	ob_start();
	$shortcode->render_login_page();
	$output = ob_get_clean();
	ga_assert( 1 === substr_count( $output, 'id="g_id_onload"' ), 'WordPress login page must render one GIS configuration.' );
	ga_assert( 1 === substr_count( $output, 'class="g_id_signin"' ), 'WordPress login page must render one official Google button.' );
	ga_assert( false !== strpos( $output, 'data-width="280"' ), 'WordPress login button must retain the fixed 280px width.' );
	ga_assert( false !== strpos( $output, '>Or<' ), 'WordPress login integration must render the approved separator.' );
	ga_assert( 1 === count( $GLOBALS['ga_scripts'] ), 'WordPress login page must enqueue GIS exactly once.' );

	foreach ( array( 'lostpassword', 'register', 'rp', 'resetpass', 'logout' ) as $action ) {
		ga_reset();
		ga_set_settings( array(), array( 'add_to_login_page' => true ) );
		$GLOBALS['pagenow'] = 'wp-login.php';
		$_REQUEST['action'] = $action;
		ga_assert( false === $shortcode->login_page_is_eligible(), 'Google button must not render for login action: ' . $action );
	}

	ga_reset();
	ga_set_settings( array(), array( 'add_to_login_page' => true ) );
	$GLOBALS['pagenow'] = 'wp-login.php';
	$_REQUEST['interim-login'] = '1';
	ga_assert( false === $shortcode->login_page_is_eligible(), 'Interim login must not render Social Login.' );

	ga_reset();
	ga_set_settings( array( 'enabled' => false ), array( 'add_to_login_page' => true ) );
	$GLOBALS['pagenow'] = 'wp-login.php';
	ga_assert( false === $shortcode->login_page_is_eligible(), 'Disabled Google provider must suppress login-page output.' );

	ga_reset();
	ga_set_settings( array( 'client_id' => '' ), array( 'add_to_login_page' => true ) );
	$GLOBALS['pagenow'] = 'wp-login.php';
	ga_assert( false === $shortcode->login_page_is_eligible(), 'Missing Client ID must suppress login-page output.' );
};

$tests['wordpress_login_blocked_error_ux'] = static function () {
	foreach (
		array(
			'existing_account_requires_login' => 'An account already exists with this email, but Google sign-in is not linked to it. Please use the existing WordPress login.',
			'role_not_allowed'                 => 'Google login is not available for this account. Please use the existing WordPress login.',
		) as $code => $message
	) {
		ga_reset();
		ga_set_settings( array(), array( 'add_to_login_page' => true ) );
		$GLOBALS['pagenow'] = 'wp-login.php';
		$_GET['yby_social_login'] = $code;
		ob_start();
		( new YBY_Social_Login_Shortcodes() )->render_login_page();
		$output = ob_get_clean();
		ga_assert( false !== strpos( $output, $message ), 'Blocked login result must show the approved safe message.' );
		ga_assert( false === strpos( $output, 'g_id_signin' ) && false === strpos( $output, 'g_id_onload' ), 'Blocked login result must hide the repeated Google button.' );
		ga_assert( empty( $GLOBALS['ga_scripts'] ), 'Blocked login result must not enqueue GIS.' );
	}

	$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-social-login-shortcodes.php' );
	ga_assert( false === strpos( $source, 'login_form_top' ) && false === strpos( $source, 'login_form_middle' ), 'Integration must not replace standard WordPress username/password fields.' );
	$login_css = file_get_contents( dirname( __DIR__ ) . '/public/css/yby-social-login-login-page.css' );
	ga_assert( false !== strpos( $login_css, '.yby-social-login-login-page' ) && false !== strpos( $login_css, 'width: 280px' ) && false !== strpos( $login_css, 'min-height: 44px' ), 'Login-page CSS must remain scoped with stable button dimensions.' );
};

$tests['one_tap_display_scope_and_bootstrap'] = static function () {
	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$GLOBALS['pagenow'] = 'index.php';
	$controller = new YBY_Google_One_Tap_Controller();
	ga_assert( true === $controller->is_eligible_public_request(), 'Eligible logged-out HTTPS frontend page must allow One Tap.' );
	$controller->enqueue_runtime();
	ga_assert( isset( $GLOBALS['ga_scripts']['yby-google-one-tap'] ), 'Eligible page must enqueue the local One Tap runtime once.' );
	ga_assert( 1 === count( $GLOBALS['ga_scripts'] ), 'One Tap bootstrap must not enqueue GIS before the challenge succeeds.' );
	ga_assert( false !== strpos( $GLOBALS['ga_scripts']['yby-google-one-tap']['src'], 'public/assets/js/yby-google-one-tap.js' ), 'One Tap must use the scoped local runtime.' );
	$inline = $GLOBALS['ga_inline_scripts']['yby-google-one-tap'][0]['data'] ?? '';
	$inline = str_replace( '\/', '/', $inline );
	ga_assert( false === strpos( $inline, 'nonce' ), 'Cacheable page configuration must not contain a one-time nonce.' );
	ga_assert(
		false !== strpos( $inline, '/auth/google/onetap/challenge' ) &&
		false !== strpos( $inline, '/auth/google/onetap/session' ) &&
		false !== strpos( $inline, '/auth/google/onetap' ),
		'One Tap endpoints must be configured.'
	);
	ga_assert( 0 === $GLOBALS['ga_nocache_calls'], 'One Tap must not disable ordinary public page caching.' );

	$cases = array(
		'disabled'     => static function () { ga_set_settings( array( 'one_tap_enabled' => false ) ); },
		'logged_in'    => static function () { $GLOBALS['ga_logged_in'] = true; },
		'wp_login'     => static function () { $GLOBALS['pagenow'] = 'wp-login.php'; },
		'wp_admin'     => static function () { $GLOBALS['ga_is_admin'] = true; },
		'json_rest'    => static function () { $GLOBALS['ga_json_request'] = true; },
		'ajax'         => static function () { $GLOBALS['ga_doing_ajax'] = true; },
		'cron'         => static function () { $GLOBALS['ga_doing_cron'] = true; },
		'feed'         => static function () { $GLOBALS['ga_is_feed'] = true; },
		'embed'        => static function () { $GLOBALS['ga_is_embed'] = true; },
		'preview'      => static function () { $GLOBALS['ga_is_preview'] = true; },
		'insecure'     => static function () { $GLOBALS['ga_ssl'] = false; },
		'logout_cookie'=> static function () { $_COOKIE[ YBY_Google_One_Tap_Controller::LOGOUT_COOKIE ] = '1'; },
		'logout_query' => static function () { $_GET['loggedout'] = 'true'; },
		'blocked_result' => static function () { $_GET['yby_social_login'] = 'role_not_allowed'; },
		'shortcode'    => static function () { $GLOBALS['ga_post_content'] = '[yby_social_login provider="google"]'; },
	);

	foreach ( $cases as $name => $configure ) {
		ga_reset();
		ga_set_settings( array( 'one_tap_enabled' => true ) );
		$GLOBALS['pagenow'] = 'index.php';
		$configure();
		ga_assert( false === $controller->is_eligible_public_request(), 'One Tap scope exclusion failed: ' . $name );
		$controller->enqueue_runtime();
		ga_assert( empty( $GLOBALS['ga_scripts']['yby-google-one-tap'] ), 'Excluded request must not enqueue One Tap: ' . $name );
	}

	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$GLOBALS['pagenow'] = 'index.php';
	$controller->enqueue_runtime();
	ga_assert( isset( $GLOBALS['ga_scripts']['yby-google-one-tap'] ), 'One Tap must enqueue before late shortcode rendering in the compatibility test.' );
	( new YBY_Social_Login_Shortcodes() )->render( array( 'provider' => 'google' ) );
	ga_assert( ! isset( $GLOBALS['ga_scripts']['yby-google-one-tap'] ) && in_array( 'yby-google-one-tap', $GLOBALS['ga_dequeued_scripts'], true ), 'Active shortcode rendering must remove the One Tap runtime.' );
};

$tests['one_tap_challenge_transport_and_replay'] = static function () {
	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$controller = new YBY_Google_One_Tap_Controller();
	$controller->register_routes();
	ga_assert( isset( $GLOBALS['ga_routes']['yby/v1/auth/google/onetap/challenge'], $GLOBALS['ga_routes']['yby/v1/auth/google/onetap'], $GLOBALS['ga_routes']['yby/v1/auth/google/onetap/session'] ), 'All three One Tap POST routes must be registered.' );
	ga_assert(
		'POST' === $GLOBALS['ga_routes']['yby/v1/auth/google/onetap/challenge']['methods'] &&
		'POST' === $GLOBALS['ga_routes']['yby/v1/auth/google/onetap']['methods'] &&
		'POST' === $GLOBALS['ga_routes']['yby/v1/auth/google/onetap/session']['methods'],
		'One Tap routes must be POST-only.'
	);

	$request = new WP_REST_Request( array(), ga_onetap_headers(), array() );
	$first   = $controller->issue_challenge( $request );
	$second  = $controller->issue_challenge( $request );
	ga_assert( 200 === $first->status && true === $first->data['success'], 'Valid challenge request must succeed with JSON.' );
	ga_assert( isset( $first->data['nonce'] ) && 2 === count( $first->data ), 'Challenge response must contain only success and nonce.' );
	ga_assert( $first->data['nonce'] !== $second->data['nonce'], 'Every challenge must be fresh.' );
	ga_assert( YBY_Social_Login::is_login_nonce_valid( $first->data['nonce'] ), 'Issued challenge must be valid before use.' );
	$nonce_key = 'yby_google_nonce_' . hash_hmac( 'sha256', $first->data['nonce'], wp_salt( 'nonce' ) );
	ga_assert( YBY_Social_Login::LOGIN_NONCE_TTL === $GLOBALS['ga_transients'][ $nonce_key ]['expiration'], 'Challenge must expire after no more than ten minutes.' );
	ga_assert( false !== strpos( $first->headers['Cache-Control'], 'no-store' ), 'Challenge response must be no-store.' );

	YBY_Social_Login::consume_login_nonce( $first->data['nonce'] );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $first->data['nonce'] ), 'Consumed challenge must not be reusable.' );
	delete_transient( 'yby_google_nonce_' . hash_hmac( 'sha256', $second->data['nonce'], wp_salt( 'nonce' ) ) );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $second->data['nonce'] ), 'Expired challenge must be rejected.' );

	$foreign = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers( array( 'origin' => 'https://evil.test' ) ), array() ) );
	ga_assert( 'invalid_origin' === $foreign->data['code'], 'Foreign Origin must be rejected.' );
	$wrong_type = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers( array( 'content-type' => 'text/plain' ) ), array() ) );
	ga_assert( 'invalid_content_type' === $wrong_type->data['code'], 'Non-JSON challenge must be rejected.' );
	$missing_header = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers( array( 'x-andy-core-one-tap' => '' ) ), array() ) );
	ga_assert( 'invalid_request' === $missing_header->data['code'], 'Missing project header must be rejected.' );
	$malformed_json = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers(), null ) );
	ga_assert( 'invalid_request' === $malformed_json->data['code'], 'Malformed JSON must be rejected.' );
	$extra_field = $controller->issue_challenge( new WP_REST_Request( array( 'extra' => '1' ), ga_onetap_headers(), array( 'extra' => '1' ) ) );
	ga_assert( 'invalid_request' === $extra_field->data['code'], 'Challenge must reject unrelated JSON fields.' );
};

$tests['one_tap_async_authentication'] = static function () use ( $private_key, $public_key, $clock ) {
	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true, 'auto_link_existing_accounts' => true ) );
	$verifier = new YBY_Google_Token_Verifier(
		static function () use ( $public_key ) {
			return array( 'certificates' => array( 'test-key' => $public_key ), 'max_age' => 600 );
		},
		$clock
	);
	$controller = new YBY_Google_One_Tap_Controller( $verifier, new YBY_Google_Auth_Service() );
	$challenge_request = new WP_REST_Request( array(), ga_onetap_headers(), array() );
	$challenge_response = $controller->issue_challenge( $challenge_request );
	$challenge = $challenge_response->data['nonce'];
	$token = ga_token( $private_key, ga_claims( array( 'nonce' => $challenge ) ) );
	$auth_request = new WP_REST_Request(
		array( 'credential' => $token, 'challenge' => $challenge ),
		ga_onetap_headers(),
		array( 'credential' => $token, 'challenge' => $challenge )
	);
	$response = $controller->authenticate( $auth_request );
	ga_assert( 200 === $response->status && array( 'success' => true, 'code' => 'success' ) === $response->data, 'One Tap success must return the minimal JSON response.' );
	ga_assert( ! isset( $response->headers['Location'] ), 'One Tap success must not return a redirect.' );
	ga_assert( 1 === count( $GLOBALS['ga_auth_cookies'] ) && 1 === $GLOBALS['ga_current_user'], 'One Tap must establish the normal WordPress authentication cookie.' );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $challenge ), 'Successful One Tap challenge must be consumed.' );
	foreach ( array( 'person@gmail.com', 'google-subject-123', $token, 'subscriber', '_yby_google_sub' ) as $private_value ) {
		ga_assert( false === strpos( json_encode( $response->data ), $private_value ), 'One Tap JSON must not expose identity data.' );
	}

	$GLOBALS['ga_logged_in'] = false;
	$replay = $controller->authenticate( $auth_request );
	ga_assert( 'invalid_nonce' === $replay->data['code'], 'One Tap challenge replay must be rejected.' );

	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$controller = new YBY_Google_One_Tap_Controller( $verifier, new YBY_Google_Auth_Service() );
	$challenge = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers(), array() ) )->data['nonce'];
	$invalid = $controller->authenticate(
		new WP_REST_Request(
			array( 'credential' => '', 'challenge' => $challenge ),
			ga_onetap_headers(),
			array( 'credential' => '', 'challenge' => $challenge )
		)
	);
	ga_assert( 'invalid_google_token' === $invalid->data['code'], 'Invalid One Tap credential must be rejected.' );
	ga_assert( ! YBY_Social_Login::is_login_nonce_valid( $challenge ), 'Failed credential attempt must still consume its challenge.' );

	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$controller = new YBY_Google_One_Tap_Controller( $verifier, new YBY_Google_Auth_Service() );
	$challenge = YBY_Social_Login::issue_login_nonce();
	$GLOBALS['ga_logged_in'] = true;
	$already_authenticated = $controller->authenticate(
		new WP_REST_Request(
			array( 'credential' => 'unused', 'challenge' => $challenge ),
			ga_onetap_headers(),
			array( 'credential' => 'unused', 'challenge' => $challenge )
		)
	);
	ga_assert( 'already_authenticated' === $already_authenticated->data['code'], 'Already authenticated sessions must not switch identity through One Tap.' );
	ga_assert( YBY_Social_Login::is_login_nonce_valid( $challenge ), 'Logged-in rejection must not consume an unused challenge.' );

	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true, 'auto_link_existing_accounts' => true ) );
	$GLOBALS['ga_users'][1] = new WP_User( 1, 'administrator', 'admin@gmail.com', array( 'administrator' ) );
	$GLOBALS['ga_user_meta'][1] = array();
	$controller = new YBY_Google_One_Tap_Controller( $verifier, new YBY_Google_Auth_Service() );
	$challenge = $controller->issue_challenge( new WP_REST_Request( array(), ga_onetap_headers(), array() ) )->data['nonce'];
	$admin_token = ga_token( $private_key, ga_claims( array( 'nonce' => $challenge, 'email' => 'admin@gmail.com' ) ) );
	$blocked = $controller->authenticate(
		new WP_REST_Request(
			array( 'credential' => $admin_token, 'challenge' => $challenge ),
			ga_onetap_headers(),
			array( 'credential' => $admin_token, 'challenge' => $challenge )
		)
	);
	ga_assert( 'role_not_allowed' === $blocked->data['code'], 'Administrator must remain blocked in One Tap.' );
	ga_assert( empty( $GLOBALS['ga_user_meta'][1] ) && empty( $GLOBALS['ga_auth_cookies'] ), 'Blocked Administrator must receive no mapping or session.' );
};

$tests['one_tap_session_confirmation'] = static function () use ( $private_key, $public_key, $clock ) {
	ga_reset();
	ga_set_settings( array( 'one_tap_enabled' => true ) );
	$verifier = new YBY_Google_Token_Verifier(
		static function () use ( $public_key ) {
			return array( 'certificates' => array( 'test-key' => $public_key ), 'max_age' => 600 );
		},
		$clock
	);
	$controller = new YBY_Google_One_Tap_Controller( $verifier, new YBY_Google_Auth_Service() );
	$challenge_request = new WP_REST_Request( array(), ga_onetap_headers(), array() );
	$challenge = $controller->issue_challenge( $challenge_request )->data['nonce'];
	$token = ga_token( $private_key, ga_claims( array( 'nonce' => $challenge ) ) );
	$authentication = $controller->authenticate(
		new WP_REST_Request(
			array( 'credential' => $token, 'challenge' => $challenge ),
			ga_onetap_headers(),
			array( 'credential' => $token, 'challenge' => $challenge )
		)
	);

	ga_assert( true === $authentication->data['success'] && 1 === count( $GLOBALS['ga_auth_cookies'] ), 'Authentication success must set the WordPress auth cookie.' );
	ga_assert( '' !== $GLOBALS['ga_last_logged_in_cookie'], 'The simulated browser must receive a logged-in cookie.' );

	$_COOKIE[ LOGGED_IN_COOKIE ] = $GLOBALS['ga_last_logged_in_cookie'];
	$GLOBALS['ga_logged_in']     = false;
	$GLOBALS['ga_current_user']  = 0;
	$transients_before           = $GLOBALS['ga_transients'];
	$password_seed_before        = $GLOBALS['ga_password_seed'];
	$session_request             = new WP_REST_Request( array(), ga_onetap_headers(), array() );
	$confirmed                   = $controller->confirm_session( $session_request );

	ga_assert( 200 === $confirmed->status && array( 'success' => true, 'authenticated' => true ) === $confirmed->data, 'A valid logged-in cookie must pass dedicated session confirmation.' );
	ga_assert( false !== strpos( $confirmed->headers['Cache-Control'], 'no-store' ), 'Session confirmation must be no-store.' );
	ga_assert( $transients_before === $GLOBALS['ga_transients'], 'Session confirmation must not issue a challenge or consume challenge rate limits.' );
	ga_assert( $password_seed_before === $GLOBALS['ga_password_seed'], 'Session confirmation must not generate a new login nonce.' );
	ga_assert( 0 === $GLOBALS['ga_current_user'] && false === $GLOBALS['ga_logged_in'], 'Session confirmation must not mutate the REST current-user state.' );
	ga_assert( 1 === count( $GLOBALS['ga_cookie_validation_calls'] ) && 'logged_in' === $GLOBALS['ga_cookie_validation_calls'][0]['scheme'], 'Session confirmation must use WordPress logged-in cookie validation exactly once.' );
	foreach ( array( 'user_id', 'email', 'username', 'role', 'sub', 'cookie', 'token', 'claims' ) as $blocked_key ) {
		ga_assert( ! array_key_exists( $blocked_key, $confirmed->data ), 'Session response must not expose: ' . $blocked_key );
	}

	$anonymous_challenge = $controller->issue_challenge( $challenge_request );
	ga_assert( true === $anonymous_challenge->data['success'] && isset( $anonymous_challenge->data['nonce'] ), 'Regression fixture must reproduce the old REST current-user false negative at the challenge endpoint.' );

	$failure = array(
		'success'       => false,
		'authenticated' => false,
		'code'          => 'login_failed',
		'message'       => 'The account was verified, but sign-in could not be completed.',
	);
	$invalid_cases = array(
		'missing' => null,
		'expired' => array(
			'cookie' => 'expired-cookie',
			'record' => array( 'user_id' => 1, 'expires_at' => time() - 1, 'signature' => true, 'site' => 'example.test' ),
		),
		'malformed' => array(
			'cookie' => 'not-a-wordpress-cookie',
			'record' => null,
		),
		'invalid_signature' => array(
			'cookie' => 'invalid-signature-cookie',
			'record' => array( 'user_id' => 1, 'expires_at' => time() + 3600, 'signature' => false, 'site' => 'example.test' ),
		),
		'other_site' => array(
			'cookie' => 'other-site-cookie',
			'record' => array( 'user_id' => 1, 'expires_at' => time() + 3600, 'signature' => true, 'site' => 'other.test' ),
		),
	);

	foreach ( $invalid_cases as $name => $fixture ) {
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );

		if ( is_array( $fixture ) ) {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $fixture['cookie'];

			if ( is_array( $fixture['record'] ) ) {
				$GLOBALS['ga_cookie_validation'][ $fixture['cookie'] ] = $fixture['record'];
			}
		}

		$transients_before    = $GLOBALS['ga_transients'];
		$password_seed_before = $GLOBALS['ga_password_seed'];
		$result               = $controller->confirm_session( $session_request );
		ga_assert( 401 === $result->status && $failure === $result->data, 'Invalid cookie must fail safely: ' . $name );
		ga_assert( false !== strpos( $result->headers['Cache-Control'], 'no-store' ), 'Invalid cookie response must be no-store: ' . $name );
		ga_assert( $transients_before === $GLOBALS['ga_transients'] && $password_seed_before === $GLOBALS['ga_password_seed'], 'Invalid confirmation must not issue a nonce or consume rate quota: ' . $name );
	}
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
	$core       = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
	$controller = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-auth-rest-controller.php' );
	$verifier   = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-token-verifier.php' );
	$service    = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-auth-service.php' );
	$css        = file_get_contents( dirname( __DIR__ ) . '/public/assets/css/yby-core-public.css' );
	$all        = $controller . $verifier . $service;

	ga_assert( false === strpos( $all, 'YBY_DATABASE_VERSION' ), 'Google authentication must not modify the database version.' );
	ga_assert( false === stripos( $all, 'dbDelta' ), 'Google authentication must not run database migrations.' );
	ga_assert( false === stripos( $all, 'CREATE TABLE' ), 'Google authentication must not create a database table.' );
	ga_assert( false === stripos( $all, 'client_secret' ), 'No Client Secret may exist.' );
	ga_assert( false === stripos( $all, 'tokeninfo' ), 'Production verification must not depend on tokeninfo.' );
	ga_assert( false !== strpos( $core, 'YBY_Lead_REST_Controller' ) && false !== strpos( $core, 'YBY_Inquiry_Shortcodes' ), 'Lead and Inquiry registration must remain intact.' );
	ga_assert( false !== strpos( $css, '.yby-social-login--google' ) && false !== strpos( $css, 'width: 280px' ) && false !== strpos( $css, 'min-height: 44px' ), 'Google login wrapper dimensions must remain stable.' );
};

$results = array();
foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
