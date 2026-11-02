<?php
/** R10-H3-C fail-closed full rollback snapshot simulator (no WP writes). */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
class H3CCart {
 public $coupons=['auto10','freecode'];
 public $totals=['subtotal'=>100.0,'discount_total'=>10.0,'discount_tax'=>1.0,
   'shipping_total'=>0.0,'shipping_tax'=>0.0,'total_tax'=>9.0,'total'=>90.0];
 function get_applied_coupons(){return $this->coupons;}
 function get_totals(){return $this->totals;}
}
class H3CSession {
 public $data=['auto_applied_coupons'=>['auto10'],
 'wc_sc_auto_applied_coupons'=>[], 'wc_sc_auto_apply_coupons'=>[],
 'chosen_shipping_methods'=>['free_shipping:1']];
 function get($k,$default=null){return $this->data[$k]??$default;}
}
$GLOBALS['h3c_notices']=['success'=>[['notice'=>'Coupon applied','data'=>[]]]];
function wc_get_notices(){return $GLOBALS['h3c_notices'];}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-full-rollback-shadow.php';
$cart=new H3CCart();$session=new H3CSession();
$c='Andy_Commerce_R10H3C_Full_Rollback_Shadow';
$before=$c::capture($cart,$session);
if(!is_array($before))exit(3);
$tests=[];
$test=function($name,$status)use(&$tests){$tests[$name]=(bool)$status;};
$test('exact_unchanged',$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->coupons=['freecode','auto10'];
$test('coupon_reordered_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->coupons=['auto10','freecode'];
$cart->totals['discount_total']=15.0;
$test('discount_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->totals['discount_total']=10.0;
$cart->totals['total_tax']=8.0;
$test('tax_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->totals['total_tax']=9.0;
$cart->totals['shipping_total']=20.0;
$test('shipping_charge_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->totals['shipping_total']=0.0;
$GLOBALS['h3c_notices']['error']=[['notice'=>'Invalid coupon','data'=>[]]];
$test('error_notice_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
unset($GLOBALS['h3c_notices']['error']);
$session->data['auto_applied_coupons']=[];
$test('provider_history_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$session->data['auto_applied_coupons']=['auto10'];
$session->data['chosen_shipping_methods']=['flat_rate:2'];
$test('shipping_selection_changed_rejected',!$c::exactly_restored($before,$c::capture($cart,$session)));
$session->data['chosen_shipping_methods']=['free_shipping:1'];
$test('exact_recovery_passes',$c::exactly_restored($before,$c::capture($cart,$session)));
$cart->totals=['total'=>new stdClass()];
$test('unsafe_object_totals_denied',$c::capture($cart,$session)===null);
$cart->totals=['total'=>90.0];
$GLOBALS['h3c_notices']['success']=[['notice'=>new stdClass()]];
$test('unsafe_notice_denied',$c::capture($cart,$session)===null);
$total=count($tests);$fail=0;
foreach($tests as $name=>$ok){echo 'R10H3C_ROLLBACK_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";if(!$ok)$fail++;}
echo 'R10H3C_FULL_ROLLBACK_INTEGRITY='.($total-$fail).'/'.$total."\n";
exit($fail?1:0);
