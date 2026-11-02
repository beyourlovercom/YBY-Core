<?php
/** Owned disposable Woo only: exercise genuine StoreApps AUTO application. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')
||get_option('siteurl')!=='http://127.0.0.1:14141'
||!defined('WC_VERSION')||WC_VERSION!=='11.2.1')exit(3);
if(!class_exists('WC_SC_Auto_Apply_Coupon',false))exit(3);
$plugin=WC_SC_Auto_Apply_Coupon::get_instance();
if(!is_callable([$plugin,'auto_apply_coupons']))exit(3);
$cart=WC()->cart;$session=WC()->session;
if(count($cart->get_cart())||count($cart->get_applied_coupons()))exit(3);
$idList=[];$keys=['auto_applied_coupons','wc_sc_auto_applied_coupons','wc_sc_auto_apply_coupons'];
$old=[];foreach($keys as $key)$old[$key]=$session->get($key,null);
$prefix='r10h3genuine'.getmypid();
try {
 $prod=new WC_Product_Simple();$prod->set_name($prefix.'test');$prod->set_status('publish');$prod->set_regular_price('100');$prod->set_catalog_visibility('hidden');$id=$prod->save();$idList[]=$id;
 $c=new WC_Coupon();$c->set_code($prefix.'auto');$c->set_discount_type('fixed_cart');$c->set_amount('8');$c->update_meta_data('wc_sc_auto_apply_coupon','yes');$couponId=$c->save();$idList[]=$couponId;
 update_post_meta($couponId,'wc_sc_user_role_ids','');
 echo 'H3A_REQUIRED_USER_ROLE_JOIN_META='.(metadata_exists('post',$couponId,'wc_sc_user_role_ids')?'YES':'NO')."\n";
 $cart->add_to_cart($id,1);
 echo 'H3_GENUINE_AUTOTRIGGER_PRODUCT_IN_CART='.($cart->is_empty()?'NO':'YES')."\n";
 echo 'H3_GENUINE_STOREAPPS_PLUGIN_OPTION='.get_option('wc_sc_allow_auto_apply','not-set')."\n";
 try {$plugin->auto_apply_coupons();echo 'H3_GENUINE_STOREAPPS_CALL=RETURNED'."\n";}
 catch(Throwable $error){echo 'H3_GENUINE_STOREAPPS_CALL=THREW_'.get_class($error)."\n";}
 $applied=$cart->get_applied_coupons();
 $record=$session->get('wc_sc_auto_applied_coupons',[]);
 $isOnCart=$cart->has_discount($c->get_code());
 $hasAuto=in_array($c->get_code(),(array)$record,true);
 echo 'H3_GENUINE_STOREAPPS_AUTO_COUPON_APPLIED='.($isOnCart?'YES':'NO')."\n";
 echo 'H3_GENUINE_STOREAPPS_AUTO_SESSION_RECENT='.($hasAuto?'YES':'NO')."\n";
 echo 'H3_GENUINE_AUTOMATION_MATCH='.($isOnCart&&$hasAuto?'PASS':'NOT_CONFIRMED')."\n";
 if (!$isOnCart || !$hasAuto) throw new RuntimeException('R10H3A_STOREAPPS_GENUINE_AUTO_NOT_APPLIED');
}finally{
 $cart->empty_cart();
 foreach($idList as $id)if($id>0)wp_delete_post($id,true);
 foreach($keys as $key){
  if($old[$key]===null)$session->__unset($key);
  else $session->set($key,$old[$key]);
 }
}
