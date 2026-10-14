<?php
/**
 * B1 isolated contract harness. Does not bootstrap WordPress or real stores.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
if ( ! defined( 'YBY_CORE_PLUGIN_URL' ) ) { define( 'YBY_CORE_PLUGIN_URL', 'https://test.invalid/wp-content/plugins/andy-core/' ); }
if ( ! defined( 'YBY_CORE_VERSION' ) ) { define( 'YBY_CORE_VERSION', '1.9.0' ); }

$GLOBALS['business_test_options'] = array();
$GLOBALS['business_test_hooks'] = array();
$GLOBALS['business_test_menus'] = array();
$GLOBALS['business_test_submenus'] = array();
$GLOBALS['business_test_styles'] = array();
$GLOBALS['business_test_home'] = 'https://unrelated.example/';
$GLOBALS['business_test_brand'] = 'yby';
$GLOBALS['business_test_access'] = false;
$GLOBALS['business_test_lead_access'] = false;
$GLOBALS['business_test_writes'] = 0;

function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $v ) ); }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['business_test_options'] ) ? $GLOBALS['business_test_options'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { ++$GLOBALS['business_test_writes']; $GLOBALS['business_test_options'][ $k ] = $v; return true; }
function home_url( $p = '' ) { return rtrim( $GLOBALS['business_test_home'], '/' ) . '/' . ltrim( $p, '/' ); }
function wp_parse_url( $s, $component = -1 ) { return parse_url( $s, $component ); }
function current_user_can( $c ) { return 'andy_core_settings_manage' === $c && $GLOBALS['business_test_access']; }
function add_action( $k, $cb, $priority = 10, $args = 1 ) { $GLOBALS['business_test_hooks'][ $k ][] = $cb; }
function add_menu_page( $title, $menu, $cap, $slug, $callback, $icon = '', $position = null ) { $GLOBALS['business_test_menus'][] = compact( 'title', 'menu', 'cap', 'slug', 'callback' ); }
function add_submenu_page( $parent, $title, $menu, $cap, $slug, $callback ) { $GLOBALS['business_test_submenus'][] = compact( 'parent', 'title', 'menu', 'cap', 'slug', 'callback' ); }
function wp_enqueue_style( $handle, $src, $deps = array(), $version = '' ) { $GLOBALS['business_test_styles'][] = $handle; }
function admin_url( $p = '' ) { return 'https://test.invalid/wp-admin/' . $p; }
function __ ( $v, $domain = '' ) { return $v; }
function esc_html__( $v, $domain = '' ) { return $v; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function wp_unslash( $v ) { return stripslashes( $v ); }
function wp_die( $message, $title = '', $args = array() ) { throw new RuntimeException( 'WP_DENIED_' . $args['response'] ); }
class YBY_Site_Profile { public static function get_brand_key() { return $GLOBALS['business_test_brand']; } }
class YBY_Security { public static function can_view_leads() { return $GLOBALS['business_test_lead_access']; } }

$root = dirname( __DIR__ );
require_once $root . '/inc/class-yby-module-registry.php';
require_once $root . '/inc/class-yby-module-runtime.php';
require_once $root . '/inc/class-yby-business-os-module.php';
require_once $root . '/admin/class-yby-content-admin.php';
require_once $root . '/admin/class-yby-business-os-admin.php';
$assert = static function ( $test, $message ) { if ( ! $test ) { fwrite( STDERR, "FAIL {$message}\n" ); exit( 1 ); } };
$assert( YBY_Business_OS_Module::register_module(), 'register once' );
$assert( ! YBY_Business_OS_Module::register_module(), 'duplicate registration forbidden' );
$meta = YBY_Module_Registry::module( 'business_os' );
$assert( false === $meta['default_enabled'], 'must be default OFF' );
$assert( 'ready' === $meta['status'], 'registry ready' );
$assert( ! YBY_Module_Registry::is_enabled( 'business_os' ), 'off on unrelated site' );
$runtime = new YBY_Module_Runtime();
$runtime->boot_registered_extensions();
$assert( empty( $GLOBALS['business_test_hooks'] ), 'disabled module must not hook' );
$assert( 0 === $GLOBALS['business_test_writes'], 'registration must not persist state' );

$GLOBALS['business_test_options'][ YBY_Module_Registry::OPTION_KEY ] = array( 'business_os' );
$runtime->boot_registered_extensions();
$assert( empty( $GLOBALS['business_test_hooks'] ), 'unrelated site must not hook even when option enabled' );
$GLOBALS['business_test_home'] = 'https://dev.beyourlover.com/';
$assert( YBY_Module_Registry::is_enabled( 'business_os' ), 'BYL known host becomes available' );
$runtime->boot_registered_extensions();
$assert( 1 === count( $GLOBALS['business_test_hooks']['admin_menu'] ?? array() ), 'enabled module hooks once' );
$runtime->boot_registered_extensions();
$assert( 1 === count( $GLOBALS['business_test_hooks']['admin_menu'] ), 'idempotent boot' );
$assert( 1 === count( $GLOBALS['business_test_hooks']['admin_enqueue_scripts'] ?? array() ), 'admin-only style hook' );
$GLOBALS['business_test_access'] = false;
YBY_Business_OS_Admin::register_admin_menu();
$assert( empty( $GLOBALS['business_test_menus'] ), 'non-admin cannot see menu' );
try { YBY_Business_OS_Admin::render_page(); $assert( false, 'direct URL must reject' ); } catch ( RuntimeException $e ) { $assert( 'WP_DENIED_403' === $e->getMessage(), '403 denied' ); }

$GLOBALS['business_test_access'] = true;
YBY_Business_OS_Admin::register_admin_menu();

$assert( 0 === count( $GLOBALS['business_test_menus'] ), 'no duplicate Business top-level' );
$visible = array_values( array_filter( $GLOBALS['business_test_submenus'], static function ( $item ) { return 'yby-content' === $item['parent']; } ) );
$legacy = array_values( array_filter( $GLOBALS['business_test_submenus'], static function ( $item ) { return null === $item['parent']; } ) );
$assert( 4 === count( $visible ), 'four Business screens under Andy Content' );
$assert( 4 === count( $legacy ), 'four invisible legacy links remain gated' );
$assert( 'andy-business' === $visible[0]['slug'], 'stable root direct link' );
$assert( 'Business 招商页面' === $visible[0]['menu'], 'Chinese-first admin text' );
$assert( 'yby-content' === $meta['admin_parent'], 'canonical Andy Content parent' );
$assert( ! isset( YBY_Business_OS_Admin::pages()['andy-business-partners'] ), 'no duplicate CRM menu' );

$assert( count( YBY_Business_OS_Admin::programs() ) === 6, 'six programs' );
$_GET['page'] = 'andy-business-programs';
$assert( YBY_Business_OS_Admin::is_business_screen( '' ), 'program screen asset gate' );
ob_start();
YBY_Business_OS_Admin::render_page();
$html = ob_get_clean();
$assert( str_contains( $html, 'Dropshipping' ) && str_contains( $html, 'Wholesale' ), 'programs render' );
$assert( str_contains( $html, '批发采购' ) && str_contains( $html, '一件代发' ), 'Chinese-first program labels render' );
$assert( ! str_contains( strtolower( $html ), 'svakom' ), 'BYL-only public branding' );
$assert( ! str_contains( $html, 'Example Retailer' ), 'no fabricated data' );
$assert( 0 === $GLOBALS['business_test_writes'], 'read-only render' );
YBY_Business_OS_Admin::enqueue_assets( '' );
$assert( array( 'andy-business-os-admin' ) === $GLOBALS['business_test_styles'], 'only scoped style registered' );
$_GET['page'] = 'andy-business';
ob_start();
YBY_Business_OS_Admin::render_page();
$front_html = ob_get_clean();
$assert( str_contains( $front_html, '/business/' ), 'Business frontend link preserved' );
$assert( str_contains( $front_html, '模拟模式' ), 'Dev mock form disclosed' );
$_GET['page'] = 'andy-business-settings';
ob_start();
YBY_Business_OS_Admin::render_page();
$status_html = ob_get_clean();
$assert( str_contains( $status_html, '尚未连接' ), 'no false ERP connection' );
$_GET['page'] = 'andy-business-partners';
$assert( YBY_Business_OS_Admin::is_business_screen( '' ), 'hidden legacy route styled' );
ob_start();
YBY_Business_OS_Admin::render_page();
$legacy_html = ob_get_clean();
$assert( str_contains( $legacy_html, '原 Business 后台入口已调整' ), 'legacy URL safely explains relocation' );
$assert( 0 === $GLOBALS['business_test_writes'], 'all new routes read-only' );
$_GET['page'] = 'andy-business-unrelated';
$assert( ! YBY_Business_OS_Admin::is_business_screen( '' ), 'unrelated screen gate' );
$GLOBALS['business_test_home'] = 'https://dev.beyourlover.com.attacker.invalid/';
$assert( ! YBY_Module_Registry::is_enabled( 'business_os' ), 'lookalike host blocked' );
$assert( ! YBY_Business_OS_Admin::can_access(), 'site gate closes direct URL' );

$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$assert( str_contains( $core, "class-yby-business-os-module.php" ), 'Core requires Business module' );
$assert( str_contains( $core, "class-yby-business-os-admin.php" ), 'Core requires Business admin' );
$assert( str_contains( $core, "YBY_Business_OS_Module', 'register_module'" ), 'Core discovery hook' );
echo "PASS business-os-b1-shell-harness\n";
