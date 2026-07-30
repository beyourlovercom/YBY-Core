<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

$GLOBALS['yby_social_options']  = array();
$GLOBALS['yby_social_autoload'] = array();
$GLOBALS['yby_social_roles']    = array(
	'administrator' => array( 'name' => 'Administrator' ),
	'editor'        => array( 'name' => 'Editor' ),
	'author'        => array( 'name' => 'Author' ),
	'contributor'   => array( 'name' => 'Contributor' ),
	'subscriber'    => array( 'name' => 'Subscriber' ),
);
$GLOBALS['yby_can_manage']      = true;
$GLOBALS['yby_submenus']        = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['yby_social_options'] ) ? $GLOBALS['yby_social_options'][ $key ] : $default;
}

function add_option( $key, $value, $deprecated = '', $autoload = true ) {
	unset( $deprecated );
	$GLOBALS['yby_social_options'][ $key ]  = $value;
	$GLOBALS['yby_social_autoload'][ $key ] = $autoload;

	return true;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['yby_social_options'][ $key ] = $value;

	if ( null !== $autoload ) {
		$GLOBALS['yby_social_autoload'][ $key ] = $autoload;
	}

	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function sanitize_text_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t]+/', ' ', $value );

	return trim( (string) $value );
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

function esc_url_raw( $value ) {
	return trim( (string) $value );
}

function wp_roles() {
	return (object) array( 'roles' => $GLOBALS['yby_social_roles'] );
}

function current_user_can( $capability ) {
	return 'manage_options' === $capability && $GLOBALS['yby_can_manage'];
}

function wp_die( $message ) {
	throw new RuntimeException( (string) $message );
}

function add_submenu_page( $parent, $page_title, $menu_title, $capability, $slug, $callback ) {
	$GLOBALS['yby_submenus'][] = compact( 'parent', 'page_title', 'menu_title', 'capability', 'slug', 'callback' );

	return 'yby-os_page_' . $slug;
}

function esc_html__( $value ) {
	return (string) $value;
}

function __( $value ) {
	return (string) $value;
}

class YBY_Security {
	public function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}
}

class YBY_Project_Studio {
	public static function menu_slug() {
		return 'yby-os';
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-social-login.php';
require_once dirname( __DIR__ ) . '/admin/class-yby-social-login-admin.php';

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function harness_reset() {
	$GLOBALS['yby_social_options']  = array();
	$GLOBALS['yby_social_autoload'] = array();
	$GLOBALS['yby_social_roles']    = array(
		'administrator' => array( 'name' => 'Administrator' ),
		'editor'        => array( 'name' => 'Editor' ),
		'author'        => array( 'name' => 'Author' ),
		'contributor'   => array( 'name' => 'Contributor' ),
		'subscriber'    => array( 'name' => 'Subscriber' ),
	);
	$GLOBALS['yby_can_manage'] = true;
	$GLOBALS['yby_submenus']   = array();
}

$tests = array();

$tests['defaults'] = static function () {
	harness_reset();
	$options = YBY_Social_Login::defaults();
	$google = YBY_Social_Login::google_defaults();

	harness_assert( false === $options['add_to_login_page'], 'WordPress login-page integration must default to disabled.' );
	harness_assert( false === $google['enabled'], 'Google must default to disabled.' );
	harness_assert( false === $google['auto_link_existing_accounts'], 'Existing-account association must default to disabled.' );
	harness_assert( '' === $google['client_id'], 'Client ID must default to empty.' );
	harness_assert( true === $google['select_account'], 'Account selection must default to true.' );
	harness_assert( 'google_' === $google['username_prefix'], 'Username prefix default is invalid.' );
	harness_assert( 'user_' === $google['fallback_prefix'], 'Fallback prefix default is invalid.' );
	harness_assert( 'subscriber' === $google['default_role'], 'Default role must be subscriber.' );
};

$tests['role_security'] = static function () {
	harness_reset();
	foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager', 'social_manager', 'custom_role' ) as $role ) {
		$google = YBY_Social_Login::sanitize_google( array( 'default_role' => $role ) );
		harness_assert( 'subscriber' === $google['default_role'], 'Privileged or arbitrary roles must be rejected.' );
	}

	$customer = YBY_Social_Login::sanitize_google( array( 'default_role' => 'customer' ) );
	harness_assert( 'subscriber' === $customer['default_role'], 'Customer must be rejected when not registered.' );

	$GLOBALS['yby_social_roles']['customer'] = array( 'name' => 'Customer' );
	$customer = YBY_Social_Login::sanitize_google( array( 'default_role' => 'customer' ) );
	harness_assert( 'customer' === $customer['default_role'], 'Registered customer role must be allowed.' );
};

$tests['redirect_security'] = static function () {
	harness_reset();
	harness_assert( '' === YBY_Social_Login::sanitize_internal_redirect( 'https://evil.test/account' ), 'External redirect must be rejected.' );
	harness_assert( '' === YBY_Social_Login::sanitize_internal_redirect( '//evil.test/account' ), 'Protocol-relative redirect must be rejected.' );
	harness_assert( '/account/?source=google#done' === YBY_Social_Login::sanitize_internal_redirect( '/account/?source=google#done' ), 'Relative redirect must be accepted.' );
	harness_assert( 'https://example.test/account/' === YBY_Social_Login::sanitize_internal_redirect( 'https://example.test/account/' ), 'Same-origin redirect must be accepted.' );
};

$tests['field_validation'] = static function () {
	harness_reset();
	$invalid = YBY_Social_Login::sanitize_google(
		array(
			'client_id'          => '<b>bad.apps.googleusercontent.com</b>',
			'profile_image_size' => 'gigantic',
			'username_prefix'    => '---',
			'fallback_prefix'    => '',
		)
	);
	harness_assert( '' === $invalid['client_id'], 'HTML client ID must be rejected.' );
	harness_assert( false === YBY_Social_Login::sanitize_google( array( 'enabled' => true ) )['enabled'], 'Google cannot remain enabled without a valid client ID.' );
	harness_assert( 'default' === $invalid['profile_image_size'], 'Invalid image size must use default.' );
	harness_assert( 'google_' === $invalid['username_prefix'], 'Invalid username prefix must use fallback.' );
	harness_assert( 'user_' === $invalid['fallback_prefix'], 'Empty fallback prefix must use fallback.' );

	$valid = YBY_Social_Login::sanitize_google( array( 'client_id' => '123-example.apps.googleusercontent.com' ) );
	harness_assert( '123-example.apps.googleusercontent.com' === $valid['client_id'], 'Valid Google client ID must be accepted.' );
	harness_assert( '' === YBY_Social_Login::sanitize_google( array( 'client_id' => 'example.test' ) )['client_id'], 'Invalid client ID must be rejected.' );
	harness_assert( false === YBY_Social_Login::sanitize( array( 'add_to_login_page' => array( '1' ) ) )['add_to_login_page'], 'Malformed general checkbox values must sanitize to false.' );
	harness_assert( false === YBY_Social_Login::sanitize_google( array( 'auto_link_existing_accounts' => array( '1' ) ) )['auto_link_existing_accounts'], 'Malformed association checkbox values must sanitize to false.' );
};

$tests['disabled_roles'] = static function () {
	harness_reset();
	$google = YBY_Social_Login::sanitize_google(
		array(
			'disabled_roles' => array( 'administrator', 'subscriber', 'unknown', 'editor', 'editor' ),
		)
	);
	harness_assert( array( 'administrator', 'subscriber', 'editor' ) === $google['disabled_roles'], 'Disabled roles must be unique and registered.' );
};

$tests['array_shape_security'] = static function () {
	harness_reset();
	$google = YBY_Social_Login::sanitize_google(
		array(
			'enabled'            => array( '1' ),
			'client_id'          => array( 'bad' ),
			'select_account'     => array( '1' ),
			'username_prefix'    => array( 'bad' ),
			'fallback_prefix'    => array( 'bad' ),
			'profile_image_size' => array( 'large' ),
			'default_role'       => array( 'administrator' ),
			'disabled_roles'     => array( array( 'administrator' ), 'editor' ),
			'redirect_url'       => array( 'https://evil.test/' ),
		)
	);

	harness_assert( false === $google['enabled'], 'Array checkbox value must not enable Google.' );
	harness_assert( '' === $google['client_id'], 'Array client ID must be rejected.' );
	harness_assert( false === $google['select_account'], 'Array checkbox value must be rejected.' );
	harness_assert( 'google_' === $google['username_prefix'], 'Array username prefix must use the fallback.' );
	harness_assert( 'user_' === $google['fallback_prefix'], 'Array fallback prefix must use the fallback.' );
	harness_assert( 'default' === $google['profile_image_size'], 'Array image size must use the default.' );
	harness_assert( 'subscriber' === $google['default_role'], 'Array role must use subscriber.' );
	harness_assert( array( 'editor' ) === $google['disabled_roles'], 'Nested disabled roles must be filtered.' );
	harness_assert( '' === $google['redirect_url'], 'Array redirect must be rejected.' );
};

$tests['activation_merge_and_autoload'] = static function () {
	harness_reset();
	$GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ] = array(
		'add_to_login_page' => true,
		'google' => array(
			'client_id'      => 'existing.apps.googleusercontent.com',
			'enabled'        => false,
			'redirect_url'   => '/members/',
			'select_account' => false,
			'auto_link_existing_accounts' => true,
		),
	);
	$options = YBY_Social_Login::get_options();
	YBY_Social_Login::save( $options );
	$saved = $GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ]['google'];

	harness_assert( true === $GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ]['add_to_login_page'], 'Existing login-page setting must survive default merging.' );
	harness_assert( 'existing.apps.googleusercontent.com' === $saved['client_id'], 'Existing valid client ID must survive default merging.' );
	harness_assert( false === $saved['enabled'], 'Activation merging must not enable Google.' );
	harness_assert( '/members/' === $saved['redirect_url'], 'Existing valid redirect must survive merging.' );
	harness_assert( false === $saved['select_account'], 'Existing boolean settings must survive merging.' );
	harness_assert( true === $saved['auto_link_existing_accounts'], 'Existing association setting must survive merging.' );
	harness_assert( false === $GLOBALS['yby_social_autoload'][ YBY_Social_Login::option_key() ], 'Option autoload must be disabled.' );

	$options = YBY_Social_Login::get_options();
	$options['add_to_login_page'] = false;
	YBY_Social_Login::save( $options );
	$saved_after_general = $GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ];
	harness_assert( 'existing.apps.googleusercontent.com' === $saved_after_general['google']['client_id'], 'General Settings save must preserve the existing Client ID.' );
	harness_assert( '/members/' === $saved_after_general['google']['redirect_url'], 'General Settings save must preserve provider configuration.' );

	harness_reset();
	YBY_Social_Login::save( YBY_Social_Login::defaults() );
	harness_assert( false === $GLOBALS['yby_social_autoload'][ YBY_Social_Login::option_key() ], 'First option creation must disable autoload.' );
};

$tests['provider_status'] = static function () {
	harness_reset();
	harness_assert( 'Not Configured' === YBY_Social_Login::provider_status( 'google' ), 'Empty Google config status is invalid.' );
	$GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ] = array(
		'google' => array( 'client_id' => 'status.apps.googleusercontent.com', 'enabled' => false ),
	);
	harness_assert( 'Disabled' === YBY_Social_Login::provider_status( 'google' ), 'Disabled status is invalid.' );
	$GLOBALS['yby_social_options'][ YBY_Social_Login::option_key() ]['google']['enabled'] = true;
	harness_assert( 'Enabled' === YBY_Social_Login::provider_status( 'google' ), 'Enabled status is invalid.' );
};

$tests['admin_menu'] = static function () {
	harness_reset();
	( new YBY_Social_Login_Admin() )->add_admin_menu();
	harness_assert( 1 === count( $GLOBALS['yby_submenus'] ), 'Exactly one Social Login submenu must be registered.' );
	$menu = $GLOBALS['yby_submenus'][0];
	harness_assert( 'yby-os' === $menu['parent'], 'Social Login parent menu is invalid.' );
	harness_assert( 'Social Login' === $menu['page_title'] && 'Social Login' === $menu['menu_title'], 'Social Login menu labels are invalid.' );
	harness_assert( 'manage_options' === $menu['capability'], 'Social Login menu capability is invalid.' );
	harness_assert( 'yby-social-login' === $menu['slug'], 'Social Login menu slug is invalid.' );
	harness_assert( array( $menu['callback'][0], 'render_page' ) === $menu['callback'], 'Social Login menu callback is invalid.' );
};

$tests['capability_and_nonce'] = static function () {
	harness_reset();
	$GLOBALS['yby_can_manage'] = false;
	$blocked = false;

	try {
		( new YBY_Social_Login_Admin() )->render_page();
	} catch ( RuntimeException $exception ) {
		$blocked = true;
	}

	harness_assert( $blocked, 'Social Login page must require manage_options.' );
	$source = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-social-login-admin.php' );
	harness_assert( false !== strpos( $source, "check_admin_referer( 'yby_social_login_save_google'" ), 'Save action must require its nonce.' );
	harness_assert( false !== strpos( $source, "check_admin_referer( 'yby_social_login_save_general'" ), 'General Settings save must require its dedicated nonce.' );
	harness_assert( false !== strpos( $source, 'can_manage_settings()' ), 'General Settings save must remain behind manage_options.' );
	harness_assert( false !== strpos( $source, 'YBY_Social_Login::get_options()' ), 'General Settings save must merge into the complete existing configuration.' );
};

$tests['view_scope'] = static function () {
	$view = file_get_contents( dirname( __DIR__ ) . '/admin/views/social-login-page.php' );

	foreach ( array( 'Google', 'Facebook', "'X'", 'TikTok', 'Coming Soon', 'v1.6.0', 'v1.7.0', 'v1.8.0' ) as $expected ) {
		harness_assert( false !== strpos( $view, $expected ), 'Provider overview is incomplete: ' . $expected );
	}

	harness_assert( false === strpos( $view, 'client_secret' ), 'Google page must not contain a secret credential field.' );
	harness_assert( false === strpos( $view, 'name="client_secret"' ), 'Google page must not render a secret credential input.' );
	harness_assert( false !== strpos( $view, '[yby_social_login provider="google"]' ), 'Canonical Social Login shortcode must appear on the overview.' );
	harness_assert( false !== strpos( $view, 'type="button" class="button" id="yby-copy-social-login-shortcode"' ), 'Copy control must be a non-submit button.' );
	harness_assert( false !== strpos( $view, 'Configure social sign-in providers and choose where login buttons appear.' ), 'Overview description must match the approved copy.' );
	harness_assert( false !== strpos( $view, 'Automatically connect matching existing accounts' ), 'Google automatic association setting must render.' );
	harness_assert( false !== strpos( $view, 'Privileged and custom roles are never connected automatically.' ), 'Automatic association warning must render.' );
};

$tests['public_scope_registration'] = static function () {
	$core  = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
	$model = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-social-login.php' );
	$admin = file_get_contents( dirname( __DIR__ ) . '/admin/class-yby-social-login-admin.php' );
	$rest  = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-google-auth-rest-controller.php' );
	$shortcodes = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-social-login-shortcodes.php' );
	$all   = $core . $model . $admin . $rest . $shortcodes;

	harness_assert( false !== strpos( $all, "'/auth/google'" ), 'Google auth REST route must be registered.' );
	harness_assert( false !== strpos( $all, "add_shortcode( 'yby_social_login'" ), 'Social Login shortcode must be registered.' );
	harness_assert( false !== strpos( $all, 'accounts.google.com/gsi/client' ), 'Google frontend script must be available to the shortcode.' );
	harness_assert( false === strpos( $core, 'accounts.google.com/gsi/client' ), 'Google script must not be loaded globally by the Core bootstrap.' );
};

$tests['version_and_regression_boundary'] = static function () {
	$main = file_get_contents( dirname( __DIR__ ) . '/yby-core.php' );
	$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );

	harness_assert( false !== strpos( $main, "define( 'YBY_DATABASE_VERSION', '1.1.0' );" ), 'Database version must remain 1.1.0.' );
	harness_assert( false !== strpos( $core, "'/wp-json/yby/v1/" ) || false !== strpos( file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-lead-rest-controller.php' ), "'yby/v1'" ), 'Lead REST namespace must remain present.' );
	harness_assert( false !== strpos( $core, 'YBY_Inquiry_Shortcodes' ), 'Inquiry runtime must remain registered.' );
	harness_assert( false !== strpos( $core, 'YBY_Lead_REST_Controller' ), 'Lead runtime must remain registered.' );
};

$results = array();
foreach ( $tests as $name => $test ) {
	$test();
	$results[] = $name . ':PASS';
}

echo implode( PHP_EOL, $results ) . PHP_EOL;
