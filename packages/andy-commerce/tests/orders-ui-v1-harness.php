<?php
/**
 * Orders UI V1 contract: HPOS / legacy, sanitized user preferences, save-on-demand.
 * Offline test; no WP DB, network, or real order writes.
 */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
define( 'ANDY_COMMERCE_PLUGIN_URL', 'https://example.test/plugins/andy-commerce/' );
define( 'ANDY_COMMERCE_VERSION', '1.0.0' );

$GLOBALS['ac_hooks'] = array();
$GLOBALS['ac_enqueue'] = array();
$GLOBALS['ac_meta'] = array();
$GLOBALS['ac_updates'] = 0;
$GLOBALS['ac_can'] = true;
$GLOBALS['ac_screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
function add_action( $hook, $callback ) { $GLOBALS['ac_hooks'][ $hook ] = $callback; }
function current_user_can( $cap ) { return $GLOBALS['ac_can']; }
function get_current_screen() { return $GLOBALS['ac_screen']; }
function get_current_user_id() { return 7; }
function get_user_meta( $user, $key, $single ) { return $GLOBALS['ac_meta'][ $key ] ?? ''; }
function update_user_meta( $user, $key, $val ) { ++$GLOBALS['ac_updates']; $GLOBALS['ac_meta'][ $key ] = $val; }
function wp_enqueue_style( $handle, $src, $deps, $ver ) { $GLOBALS['ac_enqueue']['style'] = compact( 'handle', 'src' ); }
function wp_enqueue_script( $handle, $src, $deps, $ver, $footer ) { $GLOBALS['ac_enqueue']['script'] = compact( 'handle', 'src', 'footer' ); }
function wp_localize_script( $handle, $name, $value ) { $GLOBALS['ac_enqueue']['localized'] = compact( 'handle', 'name', 'value' ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function wp_create_nonce( $value ) { return 'nonce-for-test'; }
function check_ajax_referer( $action, $field ) { $GLOBALS['ac_nonce_checked'] = $action === 'andy_commerce_orders_ui_v1' && $field === 'nonce'; }
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function wp_send_json_error( $value, $status = 200 ) { throw new RuntimeException( 'error:' . $status ); }
function wp_send_json_success( $value ) { throw new RuntimeException( 'success' ); }
require dirname( __DIR__ ) . '/includes/class-andy-commerce-orders-ui.php';
$ok = static function ( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL ); exit( 1 ); }
};

Andy_Commerce_Orders_UI::boot();
$ok( isset( $GLOBALS['ac_hooks']['admin_enqueue_scripts'], $GLOBALS['ac_hooks']['wp_ajax_andy_commerce_orders_ui_save'] ), 'hooks registered' );
$ok( Andy_Commerce_Orders_UI::on_orders_screen( (object) array( 'id' => 'woocommerce_page_wc-orders' ) ), 'HPOS supported' );
$ok( Andy_Commerce_Orders_UI::on_orders_screen( (object) array( 'id' => 'edit-shop_order' ) ), 'legacy supported' );
$ok( ! Andy_Commerce_Orders_UI::on_orders_screen( (object) array( 'id' => 'dashboard' ) ), 'no other screens' );
$ok( ! Andy_Commerce_Orders_UI::on_orders_screen( null ), 'no screen fails closed' );

$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'nonsense' ) ), 'reject bad mode' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( 'invalid' ), 'reject wrong type' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array( 'cb' ) ) ), 'checkbox not reorderable' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array( '../orders' ) ) ), 'sanitize keys' );
$ok( false === Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'cards', 'columns' => array_fill( 0, 41, 'id' ) ) ), 'bounded metadata' );
$clean = Andy_Commerce_Orders_UI::sanitize_preferences( array( 'layout' => 'compact', 'columns' => array( 'order_total', 'order_date', 'order_total' ) ) );
$ok( $clean === array( 'layout' => 'compact', 'columns' => array( 'order_total', 'order_date' ) ), 'deduplicated valid order' );

Andy_Commerce_Orders_UI::enqueue();
$ok( isset( $GLOBALS['ac_enqueue']['style'], $GLOBALS['ac_enqueue']['script'] ), 'assets on HPOS' );
$ok( $GLOBALS['ac_enqueue']['localized']['value']['preferences']['layout'] === 'standard', 'safe defaults' );
$ok( $GLOBALS['ac_updates'] === 0, 'no writes on initial page render' );
$ok( $GLOBALS['ac_enqueue']['script']['footer'], 'footer loaded JS' );

$GLOBALS['ac_enqueue'] = array();
$GLOBALS['ac_screen'] = (object) array( 'id' => 'edit-shop_order' );
Andy_Commerce_Orders_UI::enqueue();
$ok( isset( $GLOBALS['ac_enqueue']['script'] ), 'legacy screen assets' );

$GLOBALS['ac_enqueue'] = array();
$GLOBALS['ac_screen'] = (object) array( 'id' => 'post' );
Andy_Commerce_Orders_UI::enqueue();
$ok( empty( $GLOBALS['ac_enqueue'] ), 'no assets on other admin pages' );

$GLOBALS['ac_screen'] = (object) array( 'id' => 'woocommerce_page_wc-orders' );
$GLOBALS['ac_can'] = false;
Andy_Commerce_Orders_UI::enqueue();
$ok( empty( $GLOBALS['ac_enqueue'] ), 'permission gate for assets' );

$GLOBALS['ac_can'] = true;
$_POST = array( 'layout' => 'cards', 'columns' => array( 'order_number', 'andy_commerce_order_coupons', 'order_total' ) );
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'success' === $e->getMessage(), 'save success' ); }
$ok( $GLOBALS['ac_nonce_checked'], 'nonce enforced' );
$ok( $GLOBALS['ac_updates'] === 1, 'only explicit save writes one user preference' );
$ok( $GLOBALS['ac_meta'][ Andy_Commerce_Orders_UI::META_KEY ]['layout'] === 'cards', 'saved card preference' );

$_POST = array( 'layout' => 'oops' );
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'error:400' === $e->getMessage(), 'invalid preference error' ); }
$ok( $GLOBALS['ac_updates'] === 1, 'invalid request writes nothing' );

$GLOBALS['ac_can'] = false;
$_POST = array( 'layout' => 'standard' );
try { Andy_Commerce_Orders_UI::save(); } catch ( RuntimeException $e ) { $ok( 'error:403' === $e->getMessage(), 'capability enforcement' ); }
$ok( $GLOBALS['ac_updates'] === 1, 'unauthorized request writes nothing' );

$js = file_get_contents( dirname( __DIR__ ) . '/assets/js/orders-ui-v1.js' );
$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/orders-ui-v1.css' );
$ok( false !== strpos( $js, "layout = value" ), 'client-side instant switching' );
$ok( false !== strpos( $js, "saveBtn.addEventListener" ), 'explicit save only' );
$ok( false !== strpos( $css, '[data-andy-layout="cards"]' ), 'card view CSS' );
$ok( false !== strpos( $css, '[data-andy-layout="compact"]' ), 'compact view CSS' );
$ok( false === strpos( $js, 'wc_get_orders' ), 'no JS order queries' );
$toolbar_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/orders-toolbar-v1-1.js' );
$toolbar_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/orders-toolbar-v1-1.css' );
$ok( false !== strpos( $toolbar_js, '#screen-meta-links' ), 'column config in top utility row' );
$ok( false !== strpos( $toolbar_js, 'andy-commerce-orders-native-bar' ), 'Orders native operations grouped' );
$ok( false !== strpos( $toolbar_js, 'andy-commerce-orders-framework-bar' ), 'three layouts in separate framework row' );
$ok( false !== strpos( $toolbar_js, "table.closest('form')" ), 'native WooCommerce search and bulk actions remain in form' );
$ok( false !== strpos( $toolbar_js, "node.appendChild(" ) || false !== strpos( $toolbar_js, 'el.appendChild(node)' ), 'native controls are moved, not cloned' );
$ok( false !== strpos( $toolbar_js, 'more order requests' ), 'no new order fetches' );
$ok( false !== strpos( $toolbar_js, 'fetch(' ), 'layout does not fetch additional orders' );
$ok( false !== strpos( $toolbar_css, '.andy-commerce-orders-floating-panel' ), 'column settings popover' );
$bootstrap = file_get_contents( dirname( __DIR__ ) . '/includes/class-andy-commerce.php' );
$ok( false !== strpos( $bootstrap, 'Andy_Commerce_Orders_UI::boot();' ), 'Commerce-only bootstrap' );
echo 'PASS orders-ui-v1-harness (HPOS, legacy, scoped assets, preference security, no query on switch)' . PHP_EOL;
