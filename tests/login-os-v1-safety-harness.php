<?php
/** Standalone tests for Login OS safe settings, URL aliases and fail-open behavior. */
define( 'ABSPATH', '/mock-wp/' );
$GLOBALS['opts'] = array();
$GLOBALS['pages'] = array();
function get_option( $key, $fallback = false ) { return $GLOBALS['opts'][ $key ] ?? $fallback; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['opts'][ $key ] = $value; return true; }
function get_site_option( $key, $fallback = false ) { return $fallback; }
function is_multisite() { return false; }
function sanitize_hex_color( $s ) { return preg_match( '/^#[0-9a-f]{6}$/i', $s ) ? strtolower( $s ) : null; }
function esc_url_raw( $s, $allowed = array( 'http', 'https' ) ) { return preg_match( '~^https?://[a-z0-9.-]+/~i', $s ) ? $s : ''; }
function home_url( $path = '' ) { return 'https://dev.example.com' . $path; }
function wp_parse_url( $s, $part = -1 ) { return parse_url( $s, $part ); }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function get_page_by_path( $slug ) { return $GLOBALS['pages'][ $slug ] ?? null; }
function __( $text, $domain = '' ) { return $text; }
class WP_Error { public $code; function __construct( $code, $message ) { $this->code = $code; } }
require dirname( __DIR__ ) . '/inc/class-yby-login-os.php';
function check( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } }

$default = YBY_Login_OS::get_options();
check( $default['enabled'] === false, 'default styles OFF' );
check( $default['url_enabled'] === false, 'default URL OFF' );
check( $default['hide_legacy'] === false, 'default hide OFF on existing installations' );
check( ! YBY_Login_OS::valid_slug( 'wp-admin' ), 'reserved slug rejected' );
check( ! YBY_Login_OS::valid_slug( '../hack' ), 'path traversal rejected' );
check( ! YBY_Login_OS::valid_slug( array( 'unexpected' ) ), 'non-scalar slug rejected without PHP notice' );
check( ! YBY_Login_OS::valid_slug( 'x' ), 'short slug rejected' );
check( YBY_Login_OS::valid_slug( 'owner-login-007' ), 'valid slug accepted' );
check( YBY_Login_OS::blocked_legacy_route( '/wp-login.php', false ), 'legacy wp-login concealed' );
check( YBY_Login_OS::blocked_legacy_route( '/wp-admin', false ), 'admin without slash concealed' );
check( YBY_Login_OS::blocked_legacy_route( '/wp-admin/', false ), 'admin with slash concealed' );
check( YBY_Login_OS::blocked_legacy_route( '/wp-admin/index.php', false ), 'unauthenticated admin index concealed' );
check( YBY_Login_OS::blocked_legacy_route( '/admin', false ), 'admin alias concealed' );
check( YBY_Login_OS::blocked_legacy_route( '/admin/', false ), 'admin alias slash concealed' );
check( ! YBY_Login_OS::blocked_legacy_route( '/wp-admin/', true ), 'logged-in dashboard allowed' );
check( ! YBY_Login_OS::blocked_legacy_route( '/admin/', true ), 'logged-in admin alias allowed' );
check( ! YBY_Login_OS::blocked_legacy_route( '/wp-admin/admin-ajax.php', false ), 'public ajax preserved' );
check( ! YBY_Login_OS::blocked_legacy_route( '/wp-admin/admin-post.php', false ), 'admin-post preserved' );
check( ! YBY_Login_OS::blocked_legacy_route( '/wp-admin/load-styles.php', false ), 'admin style asset preserved' );
check( ! YBY_Login_OS::blocked_legacy_route( '/my-account/', false ), 'WooCommerce login preserved' );
check( ! YBY_Login_OS::blocked_legacy_route( '/checkout/', false ), 'checkout preserved' );
check( ! YBY_Login_OS::blocked_legacy_route( '/secure-login/', false ), 'custom login not blocked' );
check( ! YBY_Login_OS::slug_available( 'my-account' ), 'commerce slug protected' );
$GLOBALS['pages']['real-page'] = (object) array( 'ID' => 9 );
check( ! YBY_Login_OS::slug_available( 'real-page' ), 'existing page protected' );

$result = YBY_Login_OS::save_section( 'appearance', array(
	'enabled' => '1', 'accent_color' => '#9B3749',
	'background_color' => '#123abc', 'logo_url' => 'javascript:alert(1)',
) );
check( is_array( $result ) && $result['enabled'], 'style opt-in saved' );
check( '' === $result['logo_url'], 'unsafe logo rejected' );
check( '#9b3749' === $result['accent_color'], 'color normalized' );
check( ! $result['url_enabled'], 'style save cannot activate URL' );

check( YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'slug'=>'wp-json' ) ) instanceof WP_Error, 'invalid URL rejected' );
check( YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'slug'=>'real-page' ) ) instanceof WP_Error, 'page collision rejected' );
check( YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'slug'=>array('unsafe') ) ) instanceof WP_Error, 'bad input type rejected' );
$GLOBALS['opts']['active_plugins'] = array( 'wps-hide-login/wps-hide-login.php' );
check( YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'slug'=>'secure-login' ) ) instanceof WP_Error, 'conflict blocked' );
check( ! YBY_Login_OS::alias_active(), 'conflict remains fail-open' );
$GLOBALS['opts']['active_plugins'] = array();

$result = YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'slug'=>'owner-gate-007' ) );
check( is_array( $result ) && $result['url_enabled'], 'alias opt-in saved' );
check( YBY_Login_OS::alias_active(), 'safe alias enabled' );
check( ! YBY_Login_OS::stealth_active(), 'old installs stay unhidden on upgrade' );
$result = YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'1', 'hide_legacy'=>'1', 'slug'=>'owner-gate-007' ) );
check( is_array( $result ) && YBY_Login_OS::stealth_active(), 'explicit hide enabled' );
check( YBY_Login_OS::alias_url() === 'https://dev.example.com/owner-gate-007/', 'alias URL grounded at home' );
$url = YBY_Login_OS::filter_site_url( 'https://dev.example.com/wp-login.php?action=logout', 'wp-login.php?action=logout', 'login', 1 );
check( $url === 'https://dev.example.com/owner-gate-007/?action=logout', 'login URL query kept' );
$checkout = YBY_Login_OS::filter_site_url( 'https://dev.example.com/checkout/', 'checkout/', 'https', 1 );
check( $checkout === 'https://dev.example.com/checkout/', 'checkout untouched' );
$other = YBY_Login_OS::filter_site_url( 'https://dev.example.com/wp-login.php', 'wp-login.php', 'https', 1 );
check( $other === 'https://dev.example.com/wp-login.php', 'emergency fallback untouched' );
check( YBY_Login_OS::filter_login_url( 'https://dev.example.com/wp-login.php?redirect_to=%2Fwp-admin%2F', '', false ) ===
	'https://dev.example.com/owner-gate-007/?redirect_to=%2Fwp-admin%2F', 'login redirect retained' );
check( YBY_Login_OS::filter_network_site_url(
	'https://dev.example.com/wp-login.php?action=rp&key=sample', 'wp-login.php?action=rp&key=sample', 'login'
) === 'https://dev.example.com/owner-gate-007/?action=rp&key=sample', 'password reset email URL rewritten' );

$result = YBY_Login_OS::save_section( 'url', array( 'url_enabled'=>'0', 'slug'=>'owner-gate-007' ) );
check( is_array( $result ) && ! YBY_Login_OS::alias_active(), 'alias disabled safely' );
check( ! YBY_Login_OS::stealth_active(), 'hide disabled whenever alias disabled' );
check( YBY_Login_OS::filter_login_url( 'https://dev.example.com/wp-login.php', '', false ) ===
	'https://dev.example.com/wp-login.php', 'default login restored' );
// Menu contract: existing Social Login is preserved, independent backend login
// appears immediately beneath it in Andy Core's actual system L3 rail.
class YBY_Module_Registry {
	public static function is_enabled( $id ) {
		return in_array( $id, array( 'social_login', 'login_os' ), true );
	}
}
function current_user_can( $cap ) { return true; }
function admin_url( $path ) { return 'https://dev.example.com/wp-admin/' . $path; }
require dirname( __DIR__ ) . '/admin/class-yby-andy-os-navigation.php';
$nav = YBY_Andy_OS_Navigation::tertiary_items( 'yby-login-os' );
$titles = array_column( $nav, 'title' );
$social_index = array_search( '社交登录', $titles, true );
$backend_index = array_search( '后台登录', $titles, true );
check( $social_index !== false && $backend_index === $social_index + 1, 'backend login L3 immediately under Social Login' );
check( $nav[ $backend_index ]['url'] === 'https://dev.example.com/wp-admin/admin.php?page=yby-login-os', 'independent backend URL' );
check( in_array( '社交登录', array_column( YBY_Andy_OS_Navigation::tertiary_items( 'yby-social-login' ), 'title' ), true ), 'existing Social Login retained' );
echo "Login OS V1 isolated safety smoke: PASS\n";
