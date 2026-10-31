<?php
/** R10-C fail-closed bootstrap tests without WordPress or customer data. */
declare(strict_types=1);
define('ABSPATH', __DIR__.'/not-a-real-wordpress-site/');
define('WC_VERSION','11.2.1');
class WC_Coupon {}
class Andy_Commerce_Observation_Mode {
    public static bool $observing=true;
    public static function is_observing(): bool { return self::$observing; }
}
$GLOBALS['r10c_options'] = array(
    'siteurl'=>'https://www.beyourlover.com',
    'woocommerce_currency'=>'USD',
    'andy_commerce_r10c_shipping_enabled_v1'=>'yes',
    'andy_commerce_r10c_coupon_enabled_v1'=>'yes',
);
$GLOBALS['r10c_filters']=array();
function get_option($name,$default=null){return $GLOBALS['r10c_options'][$name] ?? $default;}
function WC(){return (object)array('cart'=>null);}
function add_filter($hook,$callback,$priority=10,$accepted=1){
    $GLOBALS['r10c_filters'][]=$hook;
}
function wc_get_price_decimals(){return 2;}
function wp_get_environment_type(){return 'production';}
require __DIR__.'/../includes/class-andy-commerce-r10c-transaction-compatibility.php';
$checks=array();
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('shipping');
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('coupon');
Andy_Commerce_R10C_Transaction_Compatibility::boot();
$checks[] = count($GLOBALS['r10c_filters'])===0;
Andy_Commerce_Observation_Mode::$observing=false; // test context only, not WP option write.
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('shipping');
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('coupon');
$GLOBALS['r10c_options']['siteurl']='https://dev.beyourlover.com';
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('shipping');
$GLOBALS['r10c_options']['siteurl']='http://127.0.0.1:14141';
define('ANDY_COMMERCE_R10C_ISOLATED_UAT',true);
$checks[] = !Andy_Commerce_R10C_Transaction_Compatibility::enabled('shipping'); // missing marker.
$checks[] = Andy_Commerce_R10C_Transaction_Compatibility::tiny_native_threshold_epsilon(false,array(),null)===false;
$checks[] = Andy_Commerce_R10C_Transaction_Compatibility::single_ordinary_discount(false,null)===false;
$checks[] = count($GLOBALS['r10c_filters'])===0;
foreach($checks as $i=>$pass) {
    if (!$pass) {fwrite(STDERR,"FAIL_R10C_GATE_CASE_".($i+1)."\n");exit(1);}
}
echo 'R10C_FAIL_CLOSED_GATES_'.count($checks).'/'.count($checks).' PASS'."\n";
