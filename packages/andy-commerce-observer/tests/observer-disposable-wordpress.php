<?php
/** Real disposable WP/Woo smoke, executed via wp eval-file after activation. */
if (!defined('ABSPATH')) {exit(1);}
$assert = static function ($condition, $message) {
 if (!$condition) {throw new RuntimeException('FAIL_OBSERVER_DISPOSABLE: '.$message);}
};
$assert(class_exists('WooCommerce'), 'WooCommerce loaded');
$assert(defined('YBY_CORE_VERSION'), 'Andy Core loaded');
$assert(class_exists('Andy_Commerce_Read_Only_Observer'), 'Observer loaded');
$assert(function_exists('is_plugin_active') && is_plugin_active('andy-commerce-observer/andy-commerce-observer.php'), 'observer active');
$assert(!class_exists('Andy_Commerce'), 'full Commerce must not be loaded');
$assert(get_option('woocommerce_currency') === 'USD', 'base USD unchanged');
$assert(null === get_option('andy_commerce_shipping_promotion_settings_v1', null), 'no Commerce shipping settings written');
$assert(null === get_option('andy_commerce_m7_inactive_drafts_v1', null), 'no marketing settings written');
$assert(null === get_option('byl_shipping_promotion_settings', null), 'no legacy shipping settings written');
$forbidden = array(
 'woocommerce_shipping_chosen_method',
 'woocommerce_shipping_free_shipping_is_available',
 'woocommerce_get_shop_coupon_data',
 'woocommerce_coupon_get_individual_use',
 'woocommerce_apply_with_individual_use_coupon',
 'woocommerce_cart_loaded_from_session',
 'woocommerce_before_calculate_totals',
 'woocommerce_cart_calculate_fees',
 'woocommerce_checkout_create_order',
 'woocommerce_payment_complete',
 'woocommerce_new_order',
);
global $wp_filter;
foreach ($forbidden as $name) {
 if (!isset($wp_filter[$name]) || !is_object($wp_filter[$name])) {continue;}
 foreach ((array)($wp_filter[$name]->callbacks ?? array()) as $entries) {
  foreach ((array)$entries as $item) {
   $call=$item['function'] ?? null;
   $observer = is_array($call) && isset($call[0]) && ($call[0] === 'Andy_Commerce_Read_Only_Observer' || (is_object($call[0]) && $call[0] instanceof Andy_Commerce_Read_Only_Observer));
   $assert(!$observer, 'observer forbidden callback: '.$name);
  }
 }
}
$assert(!has_action('init', array('Andy_Commerce_Read_Only_Observer','register_admin_page')), 'no public init listener');
echo "PASS observer-disposable-wordpress WP=".get_bloginfo('version')." Woo=".WC_VERSION." Core=".YBY_CORE_VERSION." PHP=".PHP_VERSION." observer_active=yes forbidden_business_hooks=0\n";
