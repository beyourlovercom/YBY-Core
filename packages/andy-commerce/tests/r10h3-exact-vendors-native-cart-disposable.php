<?php
/** Owned disposable site ONLY. Real WC_Cart apply/remove, no checkout/orders/payment. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')
 || get_option('siteurl')!=='http://127.0.0.1:14141'
 || !defined('WC_VERSION') || WC_VERSION!=='11.2.1')exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h2-manual-priority-shadow.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3-vendor-auto-provenance.php';
if(!is_object(WC()->cart)||!is_object(WC()->session))exit(3);
$session=WC()->session;$cart=WC()->cart;
if(count($cart->get_cart())!==0 || count($cart->get_applied_coupons())!==0) {
 echo "R10H3_NOT_EMPTY_SAFETY_STOP\n";exit(3);
}
$original=[];
foreach(['auto_applied_coupons','wc_sc_auto_applied_coupons'] as $key)$original[$key]=$session->get($key,null);
$created=[];$prefix='r10h3'.getmypid();$passed=0;$total=0;
$check=function($name,$truth)use(&$passed,&$total){
 $total++;if(!$truth)throw new RuntimeException('R10H3_CART_FAIL '.$name);
 $passed++;echo 'R10H3_CART_'.strtoupper($name).'=PASS'."\n";
};
$make_coupon=function($suffix,$amount,$min='0',$free=false)use(&$created,$prefix) {
 $coupon=new WC_Coupon();
 $coupon->set_code($prefix.$suffix);
 $coupon->set_discount_type('fixed_cart');
 $coupon->set_amount($amount);
 $coupon->set_minimum_amount($min);
 $coupon->set_free_shipping($free);
 $coupon->save();
 $created[]=$coupon->get_id();
 return $coupon->get_code();
};
try {
 $product=new WC_Product_Simple();
 $product->set_name($prefix.'fixture');
 $product->set_regular_price('100');
 $product->set_status('publish');
 $product->set_catalog_visibility('hidden');
 $id=$product->save();$created[]=$id;
 $auto=$make_coupon('auto','10');
 $manual=$make_coupon('manual','15');
 $invalid=$make_coupon('invalid','20','999');
 $free=$make_coupon('free','0','0',true);
 $check('cart_item_added', (bool)$cart->add_to_cart($id,1));
 $check('native_auto_coupon_apply', $cart->apply_coupon($auto)===true);
 $check('native_free_shipping_apply', $cart->apply_coupon($free)===true);
 $session->set('wc_sc_auto_applied_coupons',[$auto]);
 $session->set('auto_applied_coupons',[]);
 $prov=Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($auto,$cart,$session);
 $check('real_storeapps_origin_reader',$prov['status']==='auto_verified' && $prov['provider']==='storeapps');
 $autoCoupon=new WC_Coupon($auto);
 $freeCoupon=new WC_Coupon($free);
 $manualCoupon=new WC_Coupon($manual);
 $asrow=static function($c,$origin,$vendor=null){
  return ['code'=>$c->get_code(),'type'=>$c->get_discount_type(),'amount'=>(string)$c->get_amount(),
   'free_shipping'=>$c->get_free_shipping(),'origin'=>$origin,'origin_verified'=>true,
   'origin_evidence'=>$origin==='auto'?'trusted_vendor_auto_apply':'trusted_server_customer_submission',
   'vendor'=>$vendor];
 };
 $autoRow=$asrow($autoCoupon,'auto','storeapps');
 $freeRow=$asrow($freeCoupon,'auto','storeapps');
 $manualRow=$asrow($manualCoupon,'manual');
 $plan=Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($manualRow,[$autoRow,$freeRow],true);
 $check('native_manual_priority_plan',$plan['action']==='replace_auto'&&$plan['remove_codes']===[$auto]);
 $check('native_remove_old_auto',$cart->remove_coupon($auto)===true);
 $check('native_apply_new_manual',$cart->apply_coupon($manual)===true);
 $check('native_free_shipping_survived',$cart->has_discount($free) && $cart->has_discount($manual) && !$cart->has_discount($auto));
 $cart->remove_coupon($manual);
 $cart->remove_coupon($free);
 $cart->apply_coupon($auto);
 $cart->apply_coupon($free);
 $saved=$cart->get_applied_coupons();
 $cart->remove_coupon($auto);
 $failed=$cart->apply_coupon($invalid);
 $check('invalid_manual_native_validation_denied',$failed!==true);
 $cart->apply_coupon($auto);
 $check('native_failed_swap_compensation',$cart->has_discount($auto) && !$cart->has_discount($invalid) && $cart->has_discount($free));
 $exact=array_values($cart->get_applied_coupons())===array_values($saved);
 echo 'R10H3_NAIVE_COMPENSATION_ORDER_NOT_EXACT='.( !$exact ? 'PASS_BLOCKER_DETECTED':'UNEXPECTED_EXACT' )."\n";
 if ($exact) throw new RuntimeException('Expected prior-order native rollback risk was not reproducible; investigate changing vendor or Woo behavior');
 echo 'R10H3_STOREAPPS_AUTO_MARKER_AFTER_RESTORE='.(in_array($auto,(array)$session->get('wc_sc_auto_applied_coupons',[]),true)?'PRESENT':'MISSING')."\n";
 $check('no_product_stock_change', $product->get_manage_stock()===false);
 echo 'R10H3_EXACT_VENDOR_NATIVE_CART='.$passed.'/'.$total.' PASS (synthetic StoreApps session marker; rollback order BLOCKED)' . "\n";
} finally {
 $cart->empty_cart();
 foreach($created as $postId)if($postId>0)wp_delete_post($postId,true);
 foreach($original as $key=>$value){
  if($value===null)$session->__unset($key);
  else $session->set($key,$value);
 }
}
