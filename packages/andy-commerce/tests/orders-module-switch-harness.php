<?php
/** Real Andy Core registry gate harness for the Commerce Orders UI two switches. */
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ );
if ( ! defined( 'ANDY_COMMERCE_PLUGIN_DIR' ) ) define( 'ANDY_COMMERCE_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
if ( ! defined( 'ANDY_COMMERCE_PLUGIN_URL' ) ) define( 'ANDY_COMMERCE_PLUGIN_URL', 'https://example.test/wp-content/plugins/andy-commerce/' );
if ( ! defined( 'ANDY_COMMERCE_VERSION' ) ) define( 'ANDY_COMMERCE_VERSION', '1.0.0' );
$GLOBALS['opts'] = array();
$GLOBALS['hooks'] = array();
$GLOBALS['caps'] = array( 'manage_woocommerce' => true, 'manage_options' => true, 'edit_shop_orders' => true );
$GLOBALS['screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function get_option( $name, $default = false ) { return array_key_exists( $name, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $name ] : $default; }
function update_option( $name, $value, $autoload = null ) { $GLOBALS['opts'][ $name ] = $value; return true; }
function admin_url( $s = '' ) { return 'https://example.test/wp-admin/' . $s; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function is_admin() { return true; }
function current_user_can( $cap ) { return ! empty( $GLOBALS['caps'][ $cap ] ); }
function get_current_screen() { return $GLOBALS['screen']; }
function absint( $v ) { return abs( (int) $v ); }
function wp_unslash( $v ) { return $v; }
function add_action( $name, $fn, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][ $name ] = $fn; }
function add_filter( $name, $fn, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][ $name ] = $fn; }
class WooCommerce {}
require_once dirname( __DIR__, 3 ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__, 3 ) . '/inc/class-yby-module-runtime.php';
require_once dirname( __DIR__ ) . '/includes/class-andy-commerce-orders-features.php';
$a = static function ( $valid, $message ) { if ( ! $valid ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } };
Andy_Commerce_Orders_Features::register();
$l = Andy_Commerce_Orders_Features::LIST_MODULE;
$d = Andy_Commerce_Orders_Features::DETAIL_MODULE;
$a( (bool) YBY_Module_Registry::module( $l ), 'list registered with Core' );
$a( (bool) YBY_Module_Registry::module( $d ), 'detail registered with Core' );
$a( ! YBY_Module_Registry::is_enabled( $l ) && ! YBY_Module_Registry::is_enabled( $d ), 'default OFF on new sites' );
$a( ! YBY_Module_Registry::module( $l )['default_enabled'] && ! YBY_Module_Registry::module( $d )['default_enabled'], 'contract OFF' );
$a( Andy_Commerce_Orders_Features::statuses() === array( $l => false, $d => false ), 'same registry statuses' );
$a( Andy_Commerce_Orders_Features::can_manage(), 'admin allowed' );
$GLOBALS['caps']['manage_options'] = false;
$a( ! Andy_Commerce_Orders_Features::can_manage(), 'admin privilege gate' );
$GLOBALS['caps']['manage_options'] = true;
YBY_Module_Registry::save( array( 'email_os', $l ) );
$a( YBY_Module_Registry::is_enabled( $l ) && ! YBY_Module_Registry::is_enabled( $d ), 'independent toggles' );
$a( in_array( 'email_os', YBY_Module_Registry::enabled_modules(), true ), 'preserves unrelated Core feature' );
$GLOBALS['hooks'] = array();
$runtime = new YBY_Module_Runtime();
$runtime->boot_registered_extensions();
$a( isset( $GLOBALS['hooks']['screen_settings'] ), 'list enabled installs native layout settings' );
$a( isset( $GLOBALS['hooks']['woocommerce_admin_order_buyer_name'] ), 'list enabled installs SKU buyer display hook' );
$a( ! isset( $GLOBALS['hooks']['gettext'] ), 'detail OFF excludes title filter' );
YBY_Module_Registry::save( array( 'email_os', $d ) );
$GLOBALS['hooks'] = array();
$runtime2 = new YBY_Module_Runtime();
$runtime2->boot_registered_extensions();
$a( ! isset( $GLOBALS['hooks']['screen_settings'] ), 'list OFF stops list enhancements' );
$a( ! isset( $GLOBALS['hooks']['woocommerce_admin_order_buyer_name'] ), 'list OFF removes SKU display hook' );
$a( isset( $GLOBALS['hooks']['gettext'] ), 'detail ON enables native translated heading' );
$a( YBY_Module_Registry::module( $d )['settings']['page'] === 'andy-commerce', 'Commerce settings route' );
$bootstrap = file_get_contents( dirname( __DIR__ ) . '/andy-commerce.php' );
$a( str_contains( $bootstrap, "Andy_Commerce_Orders_Features', 'register" ), 'feature registered at core window' );
$a( str_contains( $bootstrap, 'andy_commerce_orders_modules_save' ), 'admin save wired' );
$commerce = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce.php' );
$a( ! str_contains( $commerce, 'Andy_Commerce_Orders_UI::boot();' ), 'no unconditional Orders UI hooks' );
$a( ! str_contains( $commerce, 'Andy_Commerce_Order_Labels::boot();' ), 'no unconditional labels' );
echo "PASS Andy Commerce Core-synced Orders module switches (OFF / ON / independent / no unwanted hooks)\n";
