<?php
/** Deterministic, vendor-free S1–S5 default-OFF registration contract. */
declare(strict_types=1);
define('ABSPATH',__DIR__.'/');
$GLOBALS['r10s_opt']='no';$GLOBALS['r10s_gate']=true;$GLOBALS['r10s_hooks']=[];
function get_option($name,$default=null){
 return $name==='andy_commerce_r10s_light_priority_enabled_v1'
  ?$GLOBALS['r10s_opt']:$default;
}
function add_action($name,$callback,$priority=10,$args=1){
 $GLOBALS['r10s_hooks'][]=['action',$name,$priority,$args];
}
function add_filter($name,$callback,$priority=10,$args=1){
 $GLOBALS['r10s_hooks'][]=['filter',$name,$priority,$args];
}
class Andy_Commerce_R10C_Transaction_Compatibility {
 public static function enabled($module):bool{
  return $module==='coupon'&&$GLOBALS['r10s_gate'];
 }
}
require dirname(__DIR__).'/includes/class-andy-commerce-r10s-light-coupon.php';
$tests=[];
function test($name,$pass){$GLOBALS['tests'][$name]=(bool)$pass;
 echo 'R10S_'.strtoupper($name).'='.($pass?'PASS':'FAIL')."\n";}
Andy_Commerce_R10S_Light_Coupon::register();
test('default_off_zero_hooks',count($GLOBALS['r10s_hooks'])===0);
$GLOBALS['r10s_opt']='yes';$GLOBALS['r10s_gate']=false;
Andy_Commerce_R10S_Light_Coupon::register();
test('master_gate_off_zero_hooks',count($GLOBALS['r10s_hooks'])===0);
$GLOBALS['r10s_gate']=true;
Andy_Commerce_R10S_Light_Coupon::register();
test('opt_in_registers_six',count($GLOBALS['r10s_hooks'])===6);
$names=array_column($GLOBALS['r10s_hooks'],1);
test('only_coupon_entrypoints',$names===[
 'wp_loaded','wc_ajax_apply_coupon','wp_ajax_woocommerce_apply_coupon',
 'wp_ajax_nopriv_woocommerce_apply_coupon',
 'rest_request_before_callbacks','woocommerce_applied_coupon']);
test('no_order_credit_inventory_hooks',
 count(array_filter($names,static fn($v)=>
  str_contains($v,'order')||str_contains($v,'payment')||
  str_contains($v,'stock')||str_contains($v,'refund')) )===0);
Andy_Commerce_R10S_Light_Coupon::register();
test('no_duplicate_registration',count($GLOBALS['r10s_hooks'])===6);
$passed=count(array_filter($tests));$total=count($tests);
echo 'R10S_STATIC_GATE='.$passed.'/'.$total."\n";
exit($passed===$total?0:1);
