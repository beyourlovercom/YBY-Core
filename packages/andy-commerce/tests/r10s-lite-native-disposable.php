<?php
/** Real licensed Woo+vendors. Owned localhost only; no order or payment. */
declare(strict_types=1);
define('DISABLE_WP_CRON',true);
define('ANDY_COMMERCE_R10C_ISOLATED_UAT',true);
// Process-only alternate MU path: never touch the sandbox's prior R10C MU.
$wp_root=getenv('R10S_DISPOSABLE_WP_ROOT');
if(!is_string($wp_root)||!is_file(rtrim($wp_root,'/\\\\').'/wp-load.php')
 ||!is_file(rtrim($wp_root,'/\\\\').'/.andy-r10b-isolated-local'))exit(3);
$empty_mu=sys_get_temp_dir().'/r10s-mu-'.getmypid();
if(!mkdir($empty_mu,0700,true))exit(3);
define('WPMU_PLUGIN_DIR',$empty_mu);
$_SERVER['HTTP_HOST']='127.0.0.1:14141';
$_SERVER['REQUEST_METHOD']='GET';
require rtrim($wp_root,'/\\\\').'/wp-load.php';
if(!is_file(ABSPATH.'.andy-r10b-isolated-local') ||
 get_option('siteurl')!=='http://127.0.0.1:14141'||WC_VERSION!=='11.2.1')exit(3);
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
$mode=$argv[1]??'';
if(!in_array($mode,['two_manual','auto_then_manual','storeapps_auto','protected','free_shipping','api_ticket','ajax','invalid_nonce','invalid_nonce_auto','dual_claim','individual_use','legacy_only','module_off'],true))exit(3);
if(!WC()->session||!WC()->cart)wc_load_cart();
$cart=WC()->cart;$session=WC()->session;
if(!$cart->is_empty()||$cart->get_applied_coupons())exit(3);
add_filter('pre_option_andy_commerce_r10c_coupon_enabled_v1',
  static fn()=> $mode==='module_off'?'no':'yes');
add_filter('pre_option_andy_commerce_r10s_light_priority_enabled_v1',
  static fn()=> $mode==='legacy_only'?'no':'yes');
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10c-transaction-compatibility.php';
Andy_Commerce_R10C_Transaction_Compatibility::boot();
$hook_off=has_filter('woocommerce_coupon_is_valid',
 ['Andy_Commerce_R10S_Light_Coupon','allow']);
echo 'R10S_MODE='.$mode.' RUNTIME_HOOK='.($hook_off===false?'OFF':'ON')."\n";
$ids=[];$before_notices=wc_get_notices();$passed=false;
try {
 $product=new WC_Product_Simple();$product->set_name('r10slocal'.getmypid());
 $product->set_status('publish');$product->set_catalog_visibility('hidden');
 $product->set_virtual(true);$product->set_regular_price('100');
 $pid=$product->save();$ids[]=$pid;
 foreach(['auto10'=>10,'manual5'=>5,'manual7'=>7] as $name=>$amount){
  $coupon=new WC_Coupon();$coupon->set_code('r10s'.$name.getmypid());
  $coupon->set_discount_type('fixed_cart');$coupon->set_amount((string)$amount);
  $id=$coupon->save();$ids[]=$id;$coupons[$name]=[$coupon->get_code(),$id];
 }
 $cart->add_to_cart($pid,1);
 $manual=function(string $name)use($coupons,$cart,$mode){
  $code=$coupons[$name][0];
  if($mode==='ajax'){
   $_SERVER['REQUEST_METHOD']='POST';
   $_POST=['security'=>wp_create_nonce('apply-coupon'),'coupon_code'=>$code];
   Andy_Commerce_R10S_Light_Coupon::ajax_capture();
  }elseif($mode==='api_ticket'){
   $request=new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon');
   $request->set_param('code',$code);
   $request->set_header('Nonce',wp_create_nonce('wc_store_api'));
   Andy_Commerce_R10S_Light_Coupon::api_capture(null,null,$request);
  }else{
   $_SERVER['REQUEST_METHOD']='POST';
   $_POST=['apply_coupon'=>'Apply coupon','coupon_code'=>$code,
     'woocommerce-cart-nonce'=>$mode==='invalid_nonce'?'invalid':wp_create_nonce('woocommerce-cart')];
   if(class_exists('Andy_Commerce_R10S_Light_Coupon'))
    Andy_Commerce_R10S_Light_Coupon::classic_capture();
  }
  $result=$cart->apply_coupon($code);
  $_POST=[];$_SERVER['REQUEST_METHOD']='GET';
  return $result;
 };
 if(in_array($mode,['two_manual','api_ticket','ajax','invalid_nonce','legacy_only'],true)){
   $first=$manual('manual5');$second=$manual('manual7');
   $codes=$cart->get_applied_coupons();
   $passed=$first===true&&$second===false&&count($codes)===1
      &&in_array($coupons['manual5'][0],$codes,true);
   if($mode==='invalid_nonce')$passed=!$first&&!$second&&count($codes)===0;
   if($mode==='legacy_only')$passed=$passed&&$hook_off===false;
   echo 'R10S_FIRST='.($first?'YES':'NO').' SECOND='.($second?'YES':'NO').
     ' CODES='.json_encode($codes)."\n";
 }elseif(in_array($mode,['auto_then_manual','storeapps_auto','invalid_nonce_auto','dual_claim'],true)){
   $auto=$coupons['auto10'][0];$aid=$coupons['auto10'][1];
   if($mode==='storeapps_auto'){
    $session->set('wc_sc_auto_applied_coupons',[$auto]);
    $vendor=WC_SC_Auto_Apply_Coupon::get_instance()->is_coupon_applied_by_auto_apply($auto);
   }else{
    update_post_meta($aid,'_wt_make_auto_coupon','1');
    $session->set('auto_applied_coupons',[$auto]);
    if($mode==='dual_claim')$session->set('wc_sc_auto_applied_coupons',[$auto]);
    $auto_coupon=new WC_Coupon($aid);
    $vendor=is_callable(['Wt_Smart_Coupon_Auto_Coupon_Common','is_auto_coupon'])
      &&Wt_Smart_Coupon_Auto_Coupon_Common::is_auto_coupon($auto_coupon);
   }
   if(in_array($mode,['invalid_nonce_auto','dual_claim'],true)){
  add_filter('woocommerce_coupon_is_valid',function($v,$c)use($cart){
   echo 'R10S_TRACE_BEFORE_CODE='.$c->get_code().' CART='.json_encode($cart->get_applied_coupons()).' VALID='.($v?'YES':'NO')."\n";
   return $v;
  },99999,2);
  add_filter('woocommerce_coupon_is_valid',function($v,$c)use($cart){
   echo 'R10S_TRACE_AFTER_CODE='.$c->get_code().' CART='.json_encode($cart->get_applied_coupons()).' VALID='.($v?'YES':'NO')."\n";
   return $v;
  },100001,2);
 }
 $was=$mode==='storeapps_auto'?$cart->apply_coupon($auto):null;
 if($mode!=='storeapps_auto'){
  Wt_Smart_Coupon_Auto_Coupon_Public::get_instance()->auto_apply_coupons();
  $was=$cart->has_discount($auto);
 }
 $cart_after_auto=$cart->get_applied_coupons();
 echo 'R10S_CART_AFTER_AUTO='.json_encode($cart_after_auto)."\n";
 if($mode==='dual_claim')$session->set('wc_sc_auto_applied_coupons',[$auto]);
   if($mode==='invalid_nonce_auto'){
     $_SERVER['REQUEST_METHOD']='POST';
     $_POST=['apply_coupon'=>'Apply coupon','coupon_code'=>$coupons['manual5'][0],
       'woocommerce-cart-nonce'=>'invalid'];
     Andy_Commerce_R10S_Light_Coupon::classic_capture();
     $incoming=$cart->apply_coupon($coupons['manual5'][0]);
     $_POST=[];$_SERVER['REQUEST_METHOD']='GET';
   }else $incoming=$manual('manual5');
   if($mode==='auto_then_manual'){
      $cart->calculate_totals();
      Wt_Smart_Coupon_Auto_Coupon_Public::get_instance()->auto_apply_coupons();
      $cart->calculate_totals();
   }
   $codes=$cart->get_applied_coupons();
   if(in_array($mode,['invalid_nonce_auto','dual_claim'],true)){
     // Third-party source can remove its auto coupon before validity filter.
     // The required narrow contract is: no untrusted manual code is added.
     $passed=$was&&in_array($auto,$cart_after_auto,true)
        &&!$incoming&&!in_array($coupons['manual5'][0],$codes,true);
   }else{
     $passed=$vendor&&$was&&in_array($auto,$cart_after_auto,true)
        &&$incoming&&count($codes)===1
       &&in_array($coupons['manual5'][0],$codes,true);
   }
   echo 'R10S_WT_AUTO_VERIFIED='.($vendor?'YES':'NO').
      ' AUTO_APPLIED='.($was?'YES':'NO').
      ' MANUAL_APPLIED='.($incoming?'YES':'NO').
      ' CODES='.json_encode($codes)."\n";
 }elseif($mode==='protected'){
   $credit=new WC_Coupon();$credit->set_code('r10scredit'.getmypid());
   $credit->set_discount_type('store_credit');$credit->set_amount('30');
   $creditid=$credit->save();$ids[]=$creditid;
   $a=$cart->apply_coupon($credit->get_code());
   $m=$manual('manual5');$codes=$cart->get_applied_coupons();
   $passed=$a&&$m&&in_array($credit->get_code(),$codes,true)
       &&in_array($coupons['manual5'][0],$codes,true);
   echo 'R10S_CREDIT='.($a?'YES':'NO').' MANUAL='.($m?'YES':'NO').
     ' CODES='.json_encode($codes)."\n";
 }elseif($mode==='individual_use'){
   $credit=new WC_Coupon();$credit->set_code('r10scredit'.getmypid());
   $credit->set_discount_type('store_credit');$credit->set_amount('30');
   $creditid=$credit->save();$ids[]=$creditid;
   $manual_coupon=new WC_Coupon($coupons['manual5'][1]);
   $manual_coupon->set_individual_use(true);$manual_coupon->save();
   $a=$cart->apply_coupon($credit->get_code());
   $m=$manual('manual5');$codes=$cart->get_applied_coupons();
   $passed=$a&&!$m&&count($codes)===1&&in_array($credit->get_code(),$codes,true);
   echo 'R10S_INDIVIDUAL_USE_CREDIT_PROTECTED='.($passed?'YES':'NO')."\\n";
 }elseif($mode==='free_shipping'){
   $ship=new WC_Coupon();$ship->set_code('r10sship'.getmypid());
   $ship->set_discount_type('fixed_cart');$ship->set_amount('0');
   $ship->set_free_shipping(true);$shipid=$ship->save();$ids[]=$shipid;
   $a=$cart->apply_coupon($ship->get_code());
   $m=$manual('manual5');$codes=$cart->get_applied_coupons();
   $passed=$a&&$m&&in_array($ship->get_code(),$codes,true)
     &&in_array($coupons['manual5'][0],$codes,true);
   echo 'R10S_SHIPPING='.($a?'YES':'NO').' MANUAL='.($m?'YES':'NO').
     ' CODES='.json_encode($codes)."\\n";
 }else{
   $a=$manual('manual5');$b=$manual('manual7');
   $passed=$hook_off===false&&$a&&$b&&count($cart->get_applied_coupons())===2;
   echo 'R10S_MODULE_OFF_NATIVE_TWO='.($a&&$b?'YES':'NO').
      ' CODES='.json_encode($cart->get_applied_coupons())."\n";
 }
} catch(Throwable $e) {
 echo 'R10S_EXCEPTION='.get_class($e).' '.$e->getMessage()."\n";
}finally{
 $cart->empty_cart();$session->set('auto_applied_coupons',[]);
 $session->set('andy_commerce_r10s_manual_ordinary','');
 $session->set('wc_sc_auto_applied_coupons',[]);
 wc_set_notices($before_notices);
 foreach(array_reverse($ids) as $id)if(get_post($id))wp_delete_post($id,true);
 @rmdir($empty_mu);
}
echo 'R10S_NATIVE_'.$mode.'='.($passed?'PASS':'FAIL')."\n";
exit($passed?0:1);
