<?php
/** H3-B genuine WebToffee auto -> manual: INDEPENDENT OWNED DISPOSABLE SITE ONLY. */
if (!is_file(ABSPATH.'.andy-r10b-isolated-local') ||
 get_option('siteurl')!=='http://127.0.0.1:14141' ||
 !defined('WC_VERSION')||WC_VERSION!=='11.2.1' ||
 !class_exists('Wt_Smart_Coupon_Auto_Coupon_Public',false)) exit(3);
$base=dirname(__DIR__).'/includes/';
require_once $base.'class-andy-commerce-r10h2-manual-priority-shadow.php';
require_once $base.'class-andy-commerce-r10h3-rollback-integrity-shadow.php';
require_once $base.'class-andy-commerce-r10h3-vendor-auto-provenance.php';
require_once $base.'class-andy-commerce-r10h3b-webtoffee-reapply-shadow.php';
$cart=WC()->cart;$sess=WC()->session;
if(!$cart->is_empty() || count($cart->get_applied_coupons())!==0)exit(3);
$keys=['auto_applied_coupons','wc_sc_auto_applied_coupons','wc_sc_auto_apply_coupons','chosen_shipping_methods'];
$old=[];foreach($keys as $k)$old[$k]=$sess->get($k,null);
$ids=[];$pfx='h3bwt'.getmypid();$checks=0;
$assert=function($name,$ok)use(&$checks){
 echo 'H3B_WT_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)throw new RuntimeException('H3B_ASSERT '.$name);
 $checks++;
};
$newCoupon=function($suffix,$amt,$min=0,$free=false)use(&$ids,$pfx){
 $c=new WC_Coupon();$c->set_code($pfx.$suffix);$c->set_discount_type('fixed_cart');
 $c->set_amount($amt);$c->set_minimum_amount($min);$c->set_free_shipping($free);
 $id=$c->save();$ids[]=$id;
 return $c;
};
try {
 $product=new WC_Product_Simple();$product->set_name($pfx.'test');$product->set_regular_price('100');$product->set_status('publish');$product->set_catalog_visibility('hidden');$id=$product->save();$ids[]=$id;
 $auto=$newCoupon('auto','8');$auto->update_meta_data('_wt_make_auto_coupon','1');$auto->save();
 $manual=$newCoupon('manual','15');$invalid=$newCoupon('invalid','25',999);$free=$newCoupon('free','0',0,true);
 $assert('product_added',boolval($cart->add_to_cart($id)));
 Wt_Smart_Coupon_Auto_Coupon_Public::get_instance()->auto_apply_coupons();
 $assert('genuine_auto_applied',$cart->has_discount($auto->get_code()));
 $assert('genuine_origin_verified',Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($auto->get_code(),$cart,$sess)['status']==='auto_verified');
 $assert('freeship_applied',$cart->apply_coupon($free->get_code())===true);
 $before=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$sess);
 $assert('before_snapshot',is_array($before));
 $attempt=$cart->apply_coupon($invalid->get_code());
 $after=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$sess);
 $assert('invalid_manual_denied',$attempt!==true);
 $assert('failed_manual_exact_snapshot',Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback($before,$after));
 $assert('manual_valid_native',$cart->apply_coupon($manual->get_code())===true);
 $verified=Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($auto->get_code(),$cart,$sess);
 $trusted=$verified['status']==='auto_verified' && $verified['provider']==='webtoffee';
 $assert('webtoffee_origin_still_verified_before_swap',$trusted);
 // These filters are installed ONLY for this disposable test invocation.
 $scCandidates=static function($list)use($manual,$auto,$trusted){
   return Andy_Commerce_R10H3B_WebToffee_Reapply_Shadow::candidates(
    $list,$manual->get_code(),$auto->get_code(),$trusted);
 };
 $scValidity=static function($valid,$coupon)use($manual,$auto,$trusted){
   return Andy_Commerce_R10H3B_WebToffee_Reapply_Shadow::is_valid(
    $valid,$coupon,$manual->get_code(),$auto->get_code(),$trusted);
 };
 add_filter('wt_sc_auto_coupons_list',$scCandidates,900,1);
 add_filter('wt_is_valid_coupon',$scValidity,900,2);

 $assert('auto_still_there_pre_commit',$cart->has_discount($auto->get_code()));
 $assert('remove_auto_after_success',$cart->remove_coupon($auto->get_code())===true);
 // No public WebToffee per-code unset API: update only this verified
 // auto code in its WC server session; preserve all unrelated entries.
 $prior=(array)$sess->get('auto_applied_coupons',[]);
 $left=array_values(array_filter($prior,static fn($code)=>$code!==$auto->get_code()));
 $sess->set('auto_applied_coupons',$left);
 $assert('webtoffee_auto_history_cleared',!in_array($auto->get_code(),(array)$sess->get('auto_applied_coupons',[]),true));
 $cart->calculate_totals();
 $assert('free_survives',$cart->has_discount($free->get_code()));
 $assert('manual_survives',$cart->has_discount($manual->get_code()));
 $assert('no_auto_reapply_after_totals',!$cart->has_discount($auto->get_code()));
 Wt_Smart_Coupon_Auto_Coupon_Public::get_instance()->auto_apply_coupons();
 $assert('no_auto_reapply_after_vendor_trigger',!$cart->has_discount($auto->get_code()));
 $assert('auto_session_not_stale_after_swap',!in_array($auto->get_code(),(array)$sess->get('auto_applied_coupons',[]),true));
 // After customer removes manual coupon, vendor automatic promotions
 // should be eligible again (no suppression for an empty manual slot).
 $assert('remove_manual_then_auto_allowed',$cart->remove_coupon($manual->get_code())===true);
 Wt_Smart_Coupon_Auto_Coupon_Public::get_instance()->auto_apply_coupons();
 $assert('auto_returns_after_manual_removal',$cart->has_discount($auto->get_code()));
  echo "H3B_WT_TOTAL=".$checks."\n";
}finally{
 if(isset($scCandidates))remove_filter('wt_sc_auto_coupons_list',$scCandidates,900);
 if(isset($scValidity))remove_filter('wt_is_valid_coupon',$scValidity,900);
 $cart->empty_cart();
 foreach($ids as $id) if($id>0)wp_delete_post($id,true);
 foreach($old as $k=>$v){if($v===null)$sess->__unset($k);else $sess->set($k,$v);}
}
