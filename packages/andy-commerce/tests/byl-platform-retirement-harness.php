<?php
/** Executable regression: BYL Platform retired, Andy Commerce owns the policy. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('ANDY_COMMERCE_PLUGIN_DIR', dirname(__DIR__) . '/');
$GLOBALS['options'] = array();
$GLOBALS['writes'] = array();
$GLOBALS['host'] = 'dev.beyourlover.com';
$GLOBALS['endpoint_data'] = null;
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload = false) {
    $GLOBALS['options'][$key] = $value;
    $GLOBALS['writes'][] = $key;
    return true;
}
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function home_url($path = '/') { return 'https://' . $GLOBALS['host'] . $path; }
function apply_filters($hook, $value) { return $value; }
function sanitize_text_field($value) { return trim((string) $value); }
function absint($value) { return abs((int) $value); }
function wc_format_coupon_code($value) { return strtolower((string) $value); }
function woocommerce_store_api_register_endpoint_data($data) { $GLOBALS['endpoint_data'] = $data; }
class WC_Shipping_Zones {
    public static function get_zones() { return array(); }
    public static function get_zone($id) { return new class { public function get_shipping_methods() { return array(); } }; }
}
class_alias(stdClass::class, 'Automattic\\WooCommerce\\StoreApi\\Schemas\\V1\\CartSchema');
require ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-site-preset.php';
require ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-shipping-promotion-policy.php';
require ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-shipping-promotion-runtime.php';
require ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-v3-migration-runner.php';

$assert = static function($ok, $label) {
    if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); }
};
$key = Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME;
$legacy = array(
    'schema_version' => 1,
    'mode' => 'require_code',
    'free_shipping_code' => 'BYL49',
    'global_threshold' => 49.0,
    'eligibility_basis' => 'pre_discount_merchandise_subtotal',
    'zone_overrides' => array('0' => array('mode' => 'global', 'threshold' => 49.0)),
);
$GLOBALS['options']['byl_shipping_promotion_settings'] = $legacy;
$assert(Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active(), 'legacy policy read-through activates');
$assert(Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings()['free_shipping_code'] === 'byl49', 'legacy coupon normalized');
$assert(Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold(0) === 49.0, 'legacy threshold');
$assert($GLOBALS['writes'] === array() && !isset($GLOBALS['options'][$key]), 'read-through performs no database writes');
Andy_Commerce_Shipping_Promotion_Runtime::register_store_api_endpoint_data();
$assert(($GLOBALS['endpoint_data']['namespace'] ?? '') === 'byl-shipping-promotion', 'BYL theme-compatible namespace');
$assert(is_callable($GLOBALS['endpoint_data']['data_callback']), 'Store API callback stays callable');

$GLOBALS['options'][$key] = array('schema_version' => 9);
$assert(!Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active(), 'invalid Commerce option must fail closed');
$assert(Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings() === null, 'no legacy fallback on invalid new option');
unset($GLOBALS['options'][$key]);
$assert(Andy_Commerce_Shipping_Promotion_Policy::maybe_migrate_legacy_settings(), 'explicit legacy config migration works');
$assert($GLOBALS['options'][$key]['mode'] === 'require_code', 'mode kept');
$assert(!Andy_Commerce_Shipping_Promotion_Policy::maybe_migrate_legacy_settings(), 'migration idempotent');

$GLOBALS['host'] = 'generic-example.test';
unset($GLOBALS['options'][$key]);
$assert(!Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active(), 'BYL legacy option cannot leak to generic site');
Andy_Commerce_Shipping_Promotion_Runtime::register_store_api_endpoint_data();
$assert(($GLOBALS['endpoint_data']['namespace'] ?? '') === 'andy-commerce-shipping-promotion', 'generic namespace unchanged');

$GLOBALS['options']['byl_v3_applied_migrations'] = array('V3_DB_001' => array('applied_at' => '2026-09-15T01:50:32+00:00'));
$assert(array_key_exists('V3_DB_001', Andy_Commerce_V3_Migration_Runner::discover()), 'historical baseline migration present');
$assert(Andy_Commerce_V3_Migration_Runner::pending() === array(), 'existing BYL migration ledger recognized without rewrites');
echo "PASS byl-platform-retirement-harness\n";
