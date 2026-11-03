<?php
/** Exact WebToffee BOGO gift/cart stock fixture, ISOLATED LOCAL ONLY. */
declare(strict_types=1);
define('DISABLE_WP_CRON',true);
$_SERVER['HTTP_HOST']='127.0.0.1:14141';
$_SERVER['REQUEST_METHOD']='GET';
require 'D:/ai/r10b-vendor-uat/disposable/www/wp-load.php';
if(!file_exists(ABSPATH.'.andy-r10b-isolated-local')||get_option('siteurl')!=='http://127.0.0.1:14141'
 ||WC_VERSION!=='11.2.1'||!class_exists('Wbte_Smart_Coupon_Bogo_Public',false))exit(3);
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
if(!WC()->session||!WC()->cart)wc_load_cart();
$cart=WC()->cart;if(!$cart->is_empty()||$cart->get_applied_coupons())exit(3);
$ids=[];$notices=wc_get_notices();$checks=[];
$check=function($key,$ok,$detail='')use(&$checks){
 $checks[$key]=(bool)$ok;
 echo 'H3D2_'.strtoupper($key).'='.($ok?'PASS':'FAIL').' '.$detail."\n";
};
try {
 $buy=new WC_Product_Simple();$buy->set_name('h3d2-isolated-buy-'.getmypid());
 $buy->set_status('publish');$buy->set_catalog_visibility('hidden');
 $buy->set_regular_price('60');$buy->set_manage_stock(true);$buy->set_stock_quantity(20);
 $buy_id=$buy->save();$ids[]=$buy_id;
 $gift=new WC_Product_Simple();$gift->set_name('h3d2-isolated-gift-'.getmypid());
 $gift->set_status('publish');$gift->set_catalog_visibility('hidden');
 $gift->set_regular_price('15');$gift->set_manage_stock(true);$gift->set_stock_quantity(2);
 $gift_id=$gift->save();$ids[]=$gift_id;
 $coupon=new WC_Coupon();$coupon->set_code('h3d2localbogo'.getmypid());
 $coupon->set_discount_type('wbte_sc_bogo');$coupon->set_amount('0');
 $cid=$coupon->save();$ids[]=$cid;
 $meta=[
  'wbte_sc_bogo_type'=>'wbte_sc_bogo_bxgx',
  'wbte_sc_bogo_triggers_when'=>'wbte_sc_bogo_triggers_qty',
  '_wbte_sc_bogo_min_qty'=>2,
  'wbte_sc_bogo_product_ids'=>(string)$buy_id,
  'wbte_sc_bogo_customer_gets'=>'specific_product',
  'wbte_sc_bogo_free_product_ids'=>(string)$gift_id,
  'wbte_sc_bogo_gets_product_condition'=>'all',
  'wbte_sc_bogo_customer_gets_qty'=>1,
  'wbte_sc_bogo_customer_gets_with'=>'wbte_sc_bogo_customer_gets_with_discount',
  'wbte_sc_bogo_customer_gets_discount_type'=>'wbte_sc_bogo_customer_gets_free',
  'wbte_sc_bogo_apply_offer'=>'wbte_sc_bogo_apply_repeatedly'
 ];
 $meta['wbte_sc_bogo_created_on_sc_bogo']='yes';
 foreach($meta as $key=>$value)update_post_meta($cid,$key,$value);
 $added=$cart->add_to_cart($buy_id,2);
 $applied=$cart->apply_coupon($coupon->get_code());
 $cart->calculate_totals();
 $gift_qty=static function()use($cart,$gift_id):int{
  $n=0;foreach($cart->get_cart() as $row)
   if((int)$row['product_id']===$gift_id && isset($row['wbte_sc_free_gift_coupon']))$n+=(int)$row['quantity'];
  return $n;
 };
 $q1=$gift_qty();
 $check('real_bogo_applied',$added&&$applied,'coupon='.($applied?'yes':'no'). ' notices='.json_encode(wc_get_notices()));
 $check('gift_quantity_one',$q1===1,'gift_qty='.$q1);
 $cart->calculate_totals();$q2=$gift_qty();
 $check('recalc_idempotent',$q2===$q1,'gift_qty='.$q2);
 $cart->add_to_cart($buy_id,4);$cart->calculate_totals();
 $q3=$gift_qty();
 $check('gift_stock_cap',$q3<=2,'gift_qty='.$q3.' available_stock=2');
 $check('gift_stock_unchanged',wc_get_product($gift_id)->get_stock_quantity()===2);
 wc_clear_notices();
 $checkout_allowed=$cart->check_cart_items();
 $errors=wc_get_notices('error');
 $check('checkout_rejects_overstock',$checkout_allowed===false && count($errors)>0,
  'allowed='.($checkout_allowed?'yes':'no').' error_count='.count($errors));
 echo 'H3D2_CART_ROWS='.json_encode(array_map(static fn($row)=>[
  'product'=>$row['product_id'],'qty'=>$row['quantity'],
  'gift'=>isset($row['wbte_sc_free_gift_coupon'])],array_values($cart->get_cart())))."\n";
} catch(Throwable $e){echo 'H3D2_EXCEPTION='.get_class($e).' '.$e->getMessage()."\n";}
finally {
 $cart->empty_cart();wc_set_notices($notices);
 foreach(array_reverse($ids) as $id)if(get_post($id))wp_delete_post($id,true);
}
echo 'H3D2_ASSERTIONS='.count(array_filter($checks)).'/'.count($checks)."\n";
exit(count($checks)===6&&count(array_filter($checks))===6?0:1);
