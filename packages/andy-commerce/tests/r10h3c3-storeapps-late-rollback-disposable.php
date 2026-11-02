<?php
/** H3-B genuine StoreApps auto -> manual: INDEPENDENT OWNED DISPOSABLE SITE ONLY. */
if (!is_file(ABSPATH.'.andy-r10b-isolated-local') ||
 get_option('siteurl')!=='http://127.0.0.1:14141' ||
 !defined('WC_VERSION')||WC_VERSION!=='11.2.1' ||
 !class_exists('WC_SC_Auto_Apply_Coupon',false)) exit(3);
$base=dirname(__DIR__).'/includes/';
require_once $base.'class-andy-commerce-r10h2-manual-priority-shadow.php';
require_once $base.'class-andy-commerce-r10h3-rollback-integrity-shadow.php';
require_once $base.'class-andy-commerce-r10h3-vendor-auto-provenance.php';
require_once $base.'class-andy-commerce-r10h3c-full-rollback-shadow.php';
require_once $base.'class-andy-commerce-r10h3b-storeapps-reapply-shadow.php';
$cart=WC()->cart;$sess=WC()->session;
if(!$cart->is_empty() || count($cart->get_applied_coupons())!==0)exit(3);
$keys=['auto_applied_coupons','wc_sc_auto_applied_coupons','wc_sc_auto_apply_coupons','chosen_shipping_methods'];
$old=[];foreach($keys as $k)$old[$k]=$sess->get($k,null);
$ids=[];$pfx='h3bsa'.getmypid();$checks=0;
$assert=function($name,$ok)use(&$checks){
 echo 'H3B_SA_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
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
 $auto=$newCoupon('auto','8');$auto->update_meta_data('wc_sc_auto_apply_coupon','yes');$auto->save();
 update_post_meta($auto->get_id(),'wc_sc_user_role_ids','');
 $manual=$newCoupon('manual','15');$invalid=$newCoupon('invalid','25',999);$free=$newCoupon('free','0',0,true);
 $assert('product_added',boolval($cart->add_to_cart($id)));
 WC_SC_Auto_Apply_Coupon::get_instance()->auto_apply_coupons();
 $assert('genuine_auto_applied',$cart->has_discount($auto->get_code()));
 $assert('genuine_origin_verified',Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($auto->get_code(),$cart,$sess)['status']==='auto_verified');
 $assert('freeship_applied',$cart->apply_coupon($free->get_code())===true);
 $before=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$sess);
 $assert('before_snapshot',is_array($before));
 $beforeFull=Andy_Commerce_R10H3C_Full_Rollback_Shadow::capture($cart,$sess);
 $assert('full_before_snapshot',is_array($beforeFull));
 $attempt=$cart->apply_coupon($invalid->get_code());
 $after=Andy_Commerce_R10H3_Rollback_Integrity_Shadow::capture($cart,$sess);
 $assert('invalid_manual_denied',$attempt!==true);
 $assert('failed_manual_exact_snapshot',Andy_Commerce_R10H3_Rollback_Integrity_Shadow::exact_rollback($before,$after));
 $assert('manual_valid_native',$cart->apply_coupon($manual->get_code())===true);
 $source=Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($auto->get_code(),$cart,$sess);
 $trusted=$source['status']==='auto_verified' && $source['provider']==='storeapps';
 $assert('storeapps_origin_before_swap',$trusted);
 $saFilter=static function($allow,$args)use($manual,$auto,$trusted){
   return Andy_Commerce_R10H3B_StoreApps_Reapply_Shadow::filter(
    $allow,$args,$manual->get_code(),$auto->get_code(),$trusted);
 };
 add_filter('wc_sc_is_auto_apply',$saFilter,900,2);

 $assert('auto_still_there_pre_commit',$cart->has_discount($auto->get_code()));
 $assert('remove_auto_after_success',$cart->remove_coupon($auto->get_code())===true);
 // Vendor-native API removes the exact StoreApps history entry only.
 $sa=WC_SC_Auto_Apply_Coupon::get_instance();
 $assert('storeapps_targeted_session_method',is_callable([$sa,'unset_auto_applied_coupon']));
 $sa->unset_auto_applied_coupon($auto->get_code());
 $assert('storeapps_auto_history_cleared',!in_array($auto->get_code(),(array)$sess->get('wc_sc_auto_applied_coupons',[]),true));
 $cart->calculate_totals();
 $assert('free_survives',$cart->has_discount($free->get_code()));
 $assert('manual_survives',$cart->has_discount($manual->get_code()));
  $assert('no_auto_reapply_after_totals',!$cart->has_discount($auto->get_code()));
 WC_SC_Auto_Apply_Coupon::get_instance()->auto_apply_coupons();
  $assert('no_auto_reapply_after_vendor_trigger',!$cart->has_discount($auto->get_code()));
 $assert('auto_session_not_stale_after_swap',!in_array($auto->get_code(),(array)$sess->get('wc_sc_auto_applied_coupons',[]),true));

 // Simulate a late post-apply failure, then test exact private vendor restoration.
 // No checkout/payment, no order, and no source adapter registration.
 $changedFull=Andy_Commerce_R10H3C_Full_Rollback_Shadow::capture($cart,$sess);
 $assert('full_change_detected',!Andy_Commerce_R10H3C_Full_Rollback_Shadow::exactly_restored($beforeFull,$changedFull));
 $cart->set_applied_coupons($beforeFull['coupon_order']);
 foreach($beforeFull['vendor_history_and_shipping'] as $key=>$value) {
   if($value===null)$sess->__unset($key);else $sess->set($key,$value);
 }
 wc_set_notices($beforeFull['woocommerce_notices']);
 $cart->calculate_totals();
 $restored=Andy_Commerce_R10H3C_Full_Rollback_Shadow::capture($cart,$sess);
 foreach(['coupon_order','native_cart_totals','woocommerce_notices','vendor_history_and_shipping'] as $field)
   echo 'H3C3_PROVIDER_RESTORE_'.strtoupper($field).'='.($beforeFull[$field]===$restored[$field]?'PASS':'DIFF')."\n";
 $assert('native_late_exact_rollback',Andy_Commerce_R10H3C_Full_Rollback_Shadow::exactly_restored($beforeFull,$restored));

 echo "H3C3_SA_TOTAL=".$checks."\n";
}finally{
 if(isset($saFilter))remove_filter('wc_sc_is_auto_apply',$saFilter,900);
 $cart->empty_cart();
 foreach($ids as $id) if($id>0)wp_delete_post($id,true);
 foreach($old as $k=>$v){if($v===null)$sess->__unset($k);else $sess->set($k,$v);}
}
