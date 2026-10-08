<?php
/** Legacy BYL Platform coexistence: default choice hook only, no double shipping policy. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
define('ANDY_COMMERCE_PLUGIN_DIR', dirname(__DIR__).'/');
$GLOBALS['filters']=array();
$GLOBALS['actions']=array();
function add_filter($hook,$callback,$priority=10,$accepted=1) {$GLOBALS['filters'][$hook][] = array($callback,$priority,$accepted);}
function add_action($hook,$callback,$priority=10,$accepted=1) {$GLOBALS['actions'][$hook][] = array($callback,$priority,$accepted);}
function is_admin() {return false;}
final class BYL_Shipping_Promotion_Runtime {}
final class Andy_Commerce_Shipping_Promotion_Policy {
 public static int $booted=0;
 public static function boot(): void {self::$booted++;}
 public static function is_runtime_active(): bool {return true;}
}
require dirname(__DIR__).'/includes/class-andy-commerce-shipping-promotion-runtime.php';
require dirname(__DIR__).'/includes/class-andy-commerce.php';
(new Andy_Commerce())->run();
$assert=static function($condition,$name){ if(!$condition){fwrite(STDERR,'FAIL '.$name.PHP_EOL);exit(1);} };
$assert(Andy_Commerce_Shipping_Promotion_Policy::$booted===1,'Commerce settings boot once');
$chosen=$GLOBALS['filters']['woocommerce_shipping_chosen_method'] ?? array();
$assert(count($chosen)===1,'chosen method filter once');
$assert($chosen[0][1]===20 && $chosen[0][2]===3,'chosen filter signature');
$shippingHooks=array('woocommerce_shipping_free_shipping_is_available','woocommerce_get_shop_coupon_data','woocommerce_coupon_get_individual_use','woocommerce_apply_with_individual_use_coupon');
foreach($shippingHooks as $hook){$assert(!isset($GLOBALS['filters'][$hook]),'must not double-register '.$hook);}
$assert(!isset($GLOBALS['actions']['woocommerce_cart_loaded_from_session']),'must not normalize legacy coupons twice');
$assert(!isset($GLOBALS['actions']['woocommerce_blocks_loaded']),'must not double-register Store API');
final class Rate {
 public function __construct(private string $id, private float $cost){}
 public function get_method_id(){return $this->id;}
 public function get_cost(){return $this->cost;}
}
$rates=array('flat_rate:3'=>new Rate('flat_rate',20), 'free_shipping:4'=>new Rate('free_shipping',0));
$callback=$chosen[0][0];
$assert(call_user_func($callback,'flat_rate:3',$rates,false)==='free_shipping:4','legacy coexistence default free');
echo "PASS shipping-legacy-coexistence-harness".PHP_EOL;
