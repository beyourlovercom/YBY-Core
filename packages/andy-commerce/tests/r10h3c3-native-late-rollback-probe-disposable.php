<?php
/** H3-C3 native Woo late-error exact restore EXPERIMENT, owned disposable site only. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')||get_option('siteurl')!=='http://127.0.0.1:14141'
 ||!defined('WC_VERSION')||WC_VERSION!=='11.2.1')exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-full-rollback-shadow.php';
$cart=WC()->cart;$session=WC()->session;
if(count($cart->get_cart())||count($cart->get_applied_coupons()))exit(3);
$keys=['auto_applied_coupons','wc_sc_auto_applied_coupons','wc_sc_auto_apply_coupons','chosen_shipping_methods'];
$old=[];foreach($keys as $k)$old[$k]=$session->get($k,null);
$oldNotices=wc_get_notices();$ids=[];$prefix='r10h3c3'.getmypid();$gate='Andy_Commerce_R10H3C_Full_Rollback_Shadow';
try {
 $prod=new WC_Product_Simple();$prod->set_name($prefix.'test');$prod->set_status('publish');$prod->set_catalog_visibility('hidden');$prod->set_regular_price('100');$productId=$prod->save();$ids[]=$productId;
 $mk=function($suffix,$amount,$free=false)use(&$ids,$prefix){
  $c=new WC_Coupon();$c->set_code($prefix.$suffix);$c->set_discount_type('fixed_cart');$c->set_amount($amount);$c->set_free_shipping($free);$c->save();$ids[]=$c->get_id();return $c->get_code();
 };
 $auto=$mk('auto','8');$manual=$mk('manual','15');$free=$mk('free','0',true);
 $cart->add_to_cart($productId);$cart->apply_coupon($auto);$cart->apply_coupon($free);$cart->calculate_totals();
 $before=$gate::capture($cart,$session);
 if(!is_array($before))throw new RuntimeException('BEFORE_UNCAPTURED');
 echo 'H3C3_NATIVE_BEFORE_ORDER='.json_encode($before['coupon_order'])."\n";
 echo 'H3C3_NATIVE_BEFORE_TOTAL='.json_encode($before['native_cart_totals'])."\n";
 $cart->apply_coupon($manual);
 $cart->remove_coupon($auto);
 $cart->calculate_totals();
 $afterChange=$gate::capture($cart,$session);
 echo 'H3C3_NATIVE_MUTATION_DETECTED='.(!$gate::exactly_restored($before,$afterChange)?'PASS':'FAIL')."\n";
 // Simulated LATE failure: best-effort framework restoration, not yet a
 // certified refund/credit/gift rollback or a released transaction adapter.
 $cart->set_applied_coupons($before['coupon_order']);
 foreach($before['vendor_history_and_shipping'] as $key=>$value){
  if($value===null)$session->__unset($key);else $session->set($key,$value);
 }
 wc_set_notices($before['woocommerce_notices']);
 $cart->calculate_totals();
 $restored=$gate::capture($cart,$session);
 $same=$gate::exactly_restored($before,$restored);
 echo 'H3C3_NATIVE_EXACT_LATE_ROLLBACK='.($same?'PASS':'BLOCKED')."\n";
 foreach(['coupon_order','native_cart_totals','woocommerce_notices','vendor_history_and_shipping'] as $k)
  echo 'H3C3_NATIVE_RESTORE_'.$k.'='.($before[$k]===$restored[$k]?'PASS':'DIFF')."\n";
 echo 'H3C3_NATIVE_AFTER_ORDER='.json_encode($restored['coupon_order'])."\n";
} finally {
 $cart->empty_cart();
 foreach($ids as $id)wp_delete_post($id,true);
 foreach($old as $k=>$v){if($v===null)$session->__unset($k);else $session->set($k,$v);}
 wc_set_notices($oldNotices);
}
