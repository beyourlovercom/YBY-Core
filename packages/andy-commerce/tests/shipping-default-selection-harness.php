<?php
/** Shipping default selection contract, no WordPress DB or cart mutation. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$GLOBALS['filters']=array();
function add_filter($hook,$callback,$priority=10,$args=1) { $GLOBALS['filters'][$hook]=array($callback,$priority,$args); }
function add_action() {}
class Andy_Commerce_Shipping_Promotion_Policy {
  public static bool $active=true;
  public static function is_runtime_active(): bool { return self::$active; }
}
final class FakeShippingRate {
  public function __construct(private string $id, private float $cost) {}
  public function get_method_id(): string { return $this->id; }
  public function get_cost(): float { return $this->cost; }
}
require dirname(__DIR__).'/includes/class-andy-commerce-shipping-promotion-runtime.php';
$assert=static function($ok,$message){ if(!$ok){fwrite(STDERR,"FAIL ".$message.PHP_EOL);exit(1);} };
Andy_Commerce_Shipping_Promotion_Runtime::boot();
$assert(isset($GLOBALS['filters']['woocommerce_shipping_chosen_method']),'register Woo default hook');
$assert($GLOBALS['filters']['woocommerce_shipping_chosen_method'][1]===20,'default selection priority');
$rates=array('flat_rate:5'=>new FakeShippingRate('flat_rate',20),'free_shipping:7'=>new FakeShippingRate('free_shipping',0));
$choose=static fn($default,$rates,$previous='')=>Andy_Commerce_Shipping_Promotion_Runtime::prefer_available_free_shipping($default,$rates,$previous);
$assert($choose('flat_rate:5',$rates)==='free_shipping:7','eligible free must be chosen by default');
$assert($choose('flat_rate:5',$rates,'flat_rate:5')==='free_shipping:7','rates transition recomputes free default');
$assert($choose('flat_rate:5',array('flat_rate:5'=>$rates['flat_rate:5']))==='flat_rate:5','ineligible no free rate');
$assert($choose('local_pickup:2',array('local_pickup:2'=>new FakeShippingRate('local_pickup',0))+ $rates,'local_pickup:2')==='local_pickup:2','keep customer pickup choice');
$assert($choose('flat_rate:5',array('flat_rate:5'=>$rates['flat_rate:5'],'free_shipping:8'=>new FakeShippingRate('free_shipping',2)))==='flat_rate:5','non-zero free rate not preferred');
$assert($choose('flat_rate:5',array('flat_rate:5'=>new FakeShippingRate('flat_rate',0)))==='flat_rate:5','zero priced flat rate is not free shipping');
Andy_Commerce_Shipping_Promotion_Policy::$active=false;
$assert($choose('flat_rate:5',$rates)==='flat_rate:5','inactive policy leaves Woo choice unchanged');
Andy_Commerce_Shipping_Promotion_Policy::$active=true;
$assert($choose('flat_rate:5',false)==='flat_rate:5','invalid rates keep Woo choice');
$assert($choose('flat_rate:5',array('free_shipping:1'=>new stdClass()))==='flat_rate:5','unknown rate fails closed');
echo "PASS shipping-default-selection-harness (Woo default only, no cart/session writes)".PHP_EOL;
