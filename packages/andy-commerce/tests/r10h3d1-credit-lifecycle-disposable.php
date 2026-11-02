<?php
/** H3-D1 native credit lifecycle: licensed LOCALHOST ONLY; test orders, NEVER payment. */
declare(strict_types=1);
define('DISABLE_WP_CRON',true);
$_SERVER['HTTP_HOST']='127.0.0.1:14141';
$_SERVER['REQUEST_METHOD']='POST';
$_SERVER['REMOTE_ADDR']='127.0.0.1';
require 'D:/ai/r10b-vendor-uat/disposable/www/wp-load.php';
if (!file_exists(ABSPATH.'.andy-r10b-isolated-local')
 ||get_option('siteurl')!=='http://127.0.0.1:14141'
 ||WC_VERSION!=='11.2.1'
 ||!class_exists('Wt_Smart_Coupon_Store_Credit_Apply',false)
 ||!class_exists('WC_SC_Coupon_Process',false))exit(3);
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
$vendor=$argv[1]??'';
if(!in_array($vendor,['webtoffee','storeapps'],true))exit(3);
if(!WC()->session||!WC()->cart)wc_load_cart();
$cart=WC()->cart;$session=WC()->session;
if(!$cart->is_empty()||$cart->get_applied_coupons())exit(3);
$ids=[];$order_ids=[];$before_notices=wc_get_notices();$cases=[];
$amount=static fn($id):float => (float)(new WC_Coupon($id))->get_amount();
$check=function($name,$success,$extra='')use(&$cases,$vendor){
 $cases[$name]=(bool)$success;
 echo 'H3D1_'.$vendor.'_'.strtoupper($name).'='.($success?'PASS':'FAIL').($extra!==''?' '.$extra:'')."\n";
};
try {
 $p=new WC_Product_Simple();
 $p->set_name('h3d1-local-only-'.$vendor.'-'.getmypid());
 $p->set_status('publish');$p->set_catalog_visibility('hidden');
 $p->set_virtual(true);$p->set_regular_price('75');
 $product_id=$p->save();$ids[]=$product_id;
 $selected_case=$argv[2]??'failed';
 if(!in_array($selected_case,['failed','refunded'],true))throw new RuntimeException('BAD_CASE');
 foreach([$selected_case] as $case) {
  $cart->empty_cart();wc_clear_notices();
  $coupon=new WC_Coupon();
  $coupon->set_code('h3d1'.$vendor.$case.getmypid());
  $coupon->set_discount_type($vendor==='webtoffee'?'store_credit':'smart_coupon');
  $coupon->set_amount('100');$credit_id=$coupon->save();$ids[]=$credit_id;
  $cart->add_to_cart($product_id,1);
  $applied=$cart->apply_coupon($coupon->get_code());
  $cart->calculate_totals();
  $tot=$cart->get_coupon_discount_totals();
  $discount=(float)($tot[$coupon->get_code()]??0);
  $check($case.'_valid_partial_cart', $applied && $discount>0 && $discount<100,
    'cart_discount='.$discount);
  if(!$applied || $discount<=0 || $discount>=100)continue;
  $checkout=WC()->checkout();
  $order_id=$checkout->create_order([
   'billing_first_name'=>'Disposable',
   'billing_last_name'=>'H3D1',
   'billing_email'=>'h3d1-no-mail@example.invalid',
   'billing_country'=>'US',
   'payment_method'=>'',
   'customer_note'=>'LOCAL DISPOSABLE H3D1'
  ]);
  if(is_wp_error($order_id) || !(int)$order_id) {
   $check($case.'_create_native_order',false,is_wp_error($order_id)?$order_id->get_error_message():'no order');
   continue;
  }
  $order_ids[]=(int)$order_id;
  $order=wc_get_order($order_id);
  $order->update_meta_data('_h3d1_disposable_test_owner','h3d1-'.$vendor);
  $order->save();
  $items=$order->get_items('coupon');
  $item_discount=0;
  foreach($items as $item)if($item->get_code()===$coupon->get_code())$item_discount+=(float)$item->get_discount();
  $check($case.'_native_order_coupon_item',$item_discount>0,
    'order_discount='.$item_discount.' id='.$order_id);
  $before_processing=$amount($credit_id);
  $contribution_before=$order->get_meta('smart_coupons_contribution',true);
  echo 'H3D1_'.$vendor.'_PRE_PROCESS_BALANCE='.$before_processing.' SC_CONTRIBUTION='.json_encode($contribution_before)."\n";
  $order->update_status('processing','Local disposable H3D1 credit audit',true);
  $order=wc_get_order($order_id);
  echo 'H3D1_'.$vendor.'_POST_PROCESS_CONTRIBUTION='.json_encode($order->get_meta('smart_coupons_contribution',true))."\n";
  $after_debit=$amount($credit_id);
  $used=100-$after_debit;
  $check($case.'_partial_debit',$used>0&&$used<100,
    'before=100 after='.$after_debit.' deducted='.$used);
  if($vendor==='webtoffee'){
    do_action('woocommerce_new_order',$order_id,$order);
  }else{
    do_action('woocommerce_order_status_pending_to_processing',$order_id,$order);
  }
  $repeat=$amount($credit_id);
  $check($case.'_retry_idempotent',abs($repeat-$after_debit)<0.011,
    'after_retry='.$repeat);
  $order->update_status($case,'Local disposable H3D1 '.$case.' audit',true);
  $after_recovery=$amount($credit_id);
  $check($case.'_restores_credit',abs($after_recovery-100)<0.011,
    'after_status='.$after_recovery);
  echo 'H3D1_'.$vendor.'_'.strtoupper($case).'_LIFECYCLE='.
    json_encode(['order_coupon_discount'=>$item_discount,'cart_discount'=>$discount,
      'initial'=>100,'debited'=>$after_debit,'retried'=>$repeat,'recovered'=>$after_recovery])."\n";
  $cart->empty_cart();
 }
} catch(Throwable $e){
 echo 'H3D1_EXCEPTION='.get_class($e).' '.$e->getMessage()."\n";
} finally {
 $cart->empty_cart();wc_set_notices($before_notices);
 foreach(array_reverse($order_ids) as $id){
  $o=wc_get_order($id);
  if($o && $o->get_meta('_h3d1_disposable_test_owner',true)==='h3d1-'.$vendor){
   try{$o->delete(true);echo 'H3D1_CLEAN_ORDER_'.$id.'=YES'."\n";}catch(Throwable $e){echo 'H3D1_CLEAN_ORDER_'.$id.'=FAILED'."\n";}
  }
 }
 foreach(array_reverse($ids) as $id) {
  if(get_post($id))wp_delete_post($id,true);
 }
}
echo 'H3D1_'.$vendor.'_ASSERTIONS='.count(array_filter($cases)).'/'.count($cases)."\n";
exit(count($cases)===5 && count(array_filter($cases))===5 ? 0 : 1);
