<?php
/** Real disposable Woo test; executed by CI only after the other Woo tests. */
if ( ! defined('ABSPATH') || ! class_exists('WooCommerce') ) { exit(1); }
$assert = static function($ok,$message) {
    if (!$ok) throw new RuntimeException('OBSERVATION_DISPOSABLE_FAIL: '.$message);
};
$mode = get_option('andy_commerce_runtime_mode_v1', null);
$assert(in_array($mode,['observe','active'],true),'test must declare mode');
$assert(class_exists('Andy_Commerce_Observation_Mode'),'observer guard loaded');
$assert(is_plugin_active('andy-commerce/andy-commerce.php'),'plugin still active');
$assert(is_plugin_active('woocommerce/woocommerce.php'),'Woo still active');
$assert(get_option('woocommerce_currency')==='USD','Woo base USD unchanged');
if ($mode==='observe') {
    $assert(!class_exists('Andy_Commerce',false),'commerce runtime included in observe mode');
    $assert(!class_exists('Andy_Commerce_Shipping_Promotion_Runtime',false),'shipping class loaded');
    $assert(!class_exists('YBY_Woo_Order_Export_Module',false),'order export loaded');
    global $wp_filter;
    foreach ((array)$wp_filter as $hook=>$handlers) {
        if (!is_object($handlers)) continue;
        foreach ((array)($handlers->callbacks??[]) as $entries) {
            foreach ((array)$entries as $row) {
                $fn=$row['function']??null;
                $name='';
                if (is_array($fn)) $name=is_object($fn[0]??null)?get_class($fn[0]):(string)($fn[0]??'');
                elseif (is_string($fn)) $name=$fn;
                $assert(!str_starts_with($name,'Andy_Commerce_') && !str_starts_with($name,'YBY_Woo_Order_Export_'),
                    'Commerce callback registered on '.$hook.': '.$name);
            }
        }
    }
    echo "OBSERVATION_REAL_WOO_NO_COMMERCE_HOOKS_PASS\n";
} else {
    $assert(class_exists('Andy_Commerce',false),'active runtime did not load');
    $assert(class_exists('Andy_Commerce_Shipping_Promotion_Runtime',false),'active source shipping class absent');
    echo "OBSERVATION_REAL_WOO_ACTIVE_RESTORED_PASS\n";
}
