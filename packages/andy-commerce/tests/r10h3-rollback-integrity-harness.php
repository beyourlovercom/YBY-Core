<?php
/** R10-H3 source-only rollback integrity; zero WordPress writes. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3-rollback-integrity-shadow.php';
class H3IntegrityCart {
 public array $list=['auto10','freecode'];
 function get_applied_coupons(){return $this->list;}
}
class H3IntegritySession {
 public array $values=[
 'auto_applied_coupons'=>['auto10'],
 'wc_sc_auto_applied_coupons'=>[],
 'wc_sc_auto_apply_coupons'=>[],
 'chosen_shipping_methods'=>['free_shipping:3'],
 ];
 function get($k,$default=null){return $this->values[$k]??$default;}
}
$cart=new H3IntegrityCart();
$session=new H3IntegritySession();
$original=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session);
if(!is_array($original))exit(3);
$cases=[];
$cases['same_state']=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$cart->list=['freecode','auto10'];
$cases['reordered_coupon_rejected']=!Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$cart->list=['auto10','freecode'];
$session->values['auto_applied_coupons']=[];
$cases['lost_auto_marker_rejected']=!Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$session->values['auto_applied_coupons']=['auto10'];
$session->values['wc_sc_auto_applied_coupons']=['auto10'];
$cases['storeapps_claim_changed_rejected']=!Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$session->values['wc_sc_auto_applied_coupons']=[];
$session->values['chosen_shipping_methods']=['flat_rate:2'];
$cases['chosen_shipping_changed_rejected']=!Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$session->values['chosen_shipping_methods']=['free_shipping:3'];
$cases['exact_recovery_allowed']=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback(
 $original,Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session));
$cart->list=['auto10',new stdClass()];
$cases['invalid_cart_snapshot_denied']=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session)===null;
$cart->list=['auto10','freecode'];
$session->values['auto_applied_coupons']=[new stdClass()];
$cases['invalid_vendor_snapshot_denied']=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$session)===null;
$failed=0;foreach($cases as $name=>$ok){echo 'R10H3_ROLLBACK_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";if(!$ok)$failed++;}
echo 'R10H3_ROLLBACK_INTEGRITY='. (count($cases)-$failed).'/'.count($cases)."\n";
exit($failed?1:0);
