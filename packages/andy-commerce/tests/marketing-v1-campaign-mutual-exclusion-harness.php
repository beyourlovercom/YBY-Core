<?php
/**
 * Read-only contract: Commerce Marketing V1 Dev execution yields to Campaign master.
 * No DB, checkout, schema writes, Woo order, HTTP or real merchant credentials.
 * php packages/andy-commerce/tests/marketing-v1-campaign-mutual-exclusion-harness.php
 */
declare(strict_types=1);
define( 'ABSPATH', '/' );
define( 'DB_NAME', 'aicc_commerce_p5_20261023' );
define( 'AICC_MARKETING_RUNTIME_SANDBOX', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'DISABLE_WP_CRON', true );
$GLOBALS['ac_test_options'] = array(
    'andy_commerce_marketing_p1_sandbox_enabled' => 'yes',
    'andy_commerce_marketing_p4_order_cap_enabled' => 'yes',
);
function get_option( $id, $default = false ) {
    return $GLOBALS['ac_test_options'][ $id ] ?? $default;
}
function home_url( $path = '' ) { return 'http://127.0.0.1:18973' . $path; }
function wp_get_environment_type() { return 'local'; }
function get_woocommerce_currency() { return 'USD'; }
function wc_get_price_decimals() { return 2; }
function assert_guard( $yes, $message ) {
    if ( ! $yes ) { throw new RuntimeException( 'DUAL_ENGINE_FAIL ' . $message ); }
}
require dirname(__DIR__) . '/includes/class-andy-commerce-marketing-p1-sandbox-contract.php';
require dirname(__DIR__) . '/includes/class-andy-commerce-marketing-p5-checkout-hooks.php';

assert_guard( Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated(), 'isolated fixture eligibility' );
assert_guard( Andy_Commerce_Marketing_P1_Sandbox_Contract::legacy_runtime_allowed(), 'normal legacy when Campaign OFF' );
$method = new ReflectionMethod( Andy_Commerce_Marketing_P5_Checkout_Hooks::class, 'enabled' );
assert_guard( $method->invoke( null ) === true, 'legacy COD quota gate available with Campaign OFF' );

$GLOBALS['ac_test_options']['andy_campaign_lite_master'] = 'yes';
assert_guard( ! Andy_Commerce_Marketing_P1_Sandbox_Contract::legacy_runtime_allowed(), 'Campaign master vetoes Commerce V1' );
assert_guard( Andy_Commerce_Marketing_P1_Sandbox_Contract::current() === null, 'legacy V1 campaign snapshot blocked' );
assert_guard( $method->invoke( null ) === false, 'legacy checkout reservation blocked' );

unset( $GLOBALS['ac_test_options']['andy_campaign_lite_master'] );
assert_guard( Andy_Commerce_Marketing_P1_Sandbox_Contract::legacy_runtime_allowed(), 'legacy can be tested only after Campaign OFF' );
echo "COMMERCE_MARKETING_V1_CAMPAIGN_EXCLUSION_PASS=6\n";
