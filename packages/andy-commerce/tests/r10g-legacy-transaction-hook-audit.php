<?php
/** R10-G read-only legacy-bootstrap risk inventory, no WordPress startup. */
declare(strict_types=1);
$root=dirname(__DIR__);
$entry=file_get_contents($root.'/andy-commerce.php');
$bootstrap=file_get_contents($root.'/includes/class-andy-commerce.php');
$shipping=file_get_contents($root.'/includes/class-andy-commerce-shipping-promotion-runtime.php');
$policy=file_get_contents($root.'/includes/class-andy-commerce-shipping-promotion-policy.php');
$p2=file_get_contents($root.'/includes/class-andy-commerce-marketing-p2-woo-adapter.php');
$p5=file_get_contents($root.'/includes/class-andy-commerce-marketing-p5-checkout-hooks.php');
$c1=file_get_contents($root.'/includes/class-andy-commerce-currency-runtime-c1.php');
$dev=file_get_contents($root.'/includes/class-andy-commerce-currency-runtime-dev-v1.php');
$r10=file_get_contents($root.'/includes/class-andy-commerce-r10c-transaction-compatibility.php');
$checks=[
 'observe_returns_before_legacy_imports'=>
    strpos($entry,"Andy_Commerce_Observation_Mode::is_observing()") <
    strpos($entry,"class-andy-commerce-shipping-promotion-runtime.php"),
 'boot_calls_shipping_outside_r10c_independent_switch'=>
    str_contains($bootstrap,'Andy_Commerce_Shipping_Promotion_Runtime::boot()') &&
    str_contains($bootstrap,'Andy_Commerce_Shipping_Promotion_Runtime::boot_default_shipping_preference()'),
 'shipping_registers_native_eligibility_filter'=>
    str_contains($shipping,"'woocommerce_shipping_free_shipping_is_available'") &&
    str_contains($shipping,'filter_free_shipping'),
 'shipping_registers_virtual_coupon_hook'=>
    str_contains($shipping,"'woocommerce_get_shop_coupon_data'") &&
    str_contains($shipping,"'woocommerce_apply_with_individual_use_coupon'"),
 'shipping_policy_may_depend_on_persisted_settings'=>
    str_contains($policy,'is_runtime_active(): bool { return self::has_persisted_settings(); }'),
 'shipping_also_registers_chosen_method_preference'=>
    str_contains($shipping,"'woocommerce_shipping_chosen_method'"),
 'marketing_p2_has_coupon_gift_cart_hooks'=>
    str_contains($p2,"'woocommerce_coupon_is_valid'") &&
    str_contains($p2,"'woocommerce_before_calculate_totals'") &&
    str_contains($bootstrap,'Andy_Commerce_Marketing_P2_Woo_Adapter::boot()'),
 'marketing_p5_has_classic_and_blocks_checkout_hooks'=>
    str_contains($p5,"'woocommerce_checkout_create_order'") &&
    str_contains($p5,"'woocommerce_store_api_checkout_update_order_from_request'") &&
    str_contains($bootstrap,'Andy_Commerce_Marketing_P5_Checkout_Hooks::boot()'),
 'currency_can_register_payment_and_price_hooks_when_armed'=>
    str_contains($c1,"'woocommerce_available_payment_gateways'") &&
    str_contains($c1,"'woocommerce_product_get_price'") &&
    str_contains($bootstrap,'Andy_Commerce_Currency_Runtime_C1::boot()'),
 'dev_currency_contains_shipping_and_coupon_filters'=>
    str_contains($dev,"'woocommerce_package_rates'") &&
    str_contains($dev,"'woocommerce_coupon_get_amount'") &&
    str_contains($bootstrap,'Andy_Commerce_Currency_Runtime_Dev_V1::boot()'),
 'r10c_requires_separate_module_flags'=>
    str_contains($r10,"self::SHIPPING_OPTION") &&
    str_contains($r10,"self::COUPON_OPTION") &&
    str_contains($r10,'ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH'),
 'r10c_import_after_early_observe_return'=>
    strpos($entry,'class-andy-commerce-r10c-transaction-compatibility.php') >
    strpos($entry,'Andy_Commerce_Observation_Mode::is_observing()')
];
$bad=[];
foreach($checks as $name=>$ok){
 echo 'R10G_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)$bad[]=$name;
}
echo 'R10G_AUDIT_CONTRACT='. (count($checks)-count($bad)).'/'.count($checks)."\n";
exit($bad?1:0);
