<?php
/** H3D1 real licensed cart probes; isolated Windows site only. */
define('DISABLE_WP_CRON', true);
$_SERVER['HTTP_HOST']='127.0.0.1:14141';
$_SERVER['REQUEST_METHOD']='GET';
require 'D:/ai/r10b-vendor-uat/disposable/www/wp-load.php';
if (!file_exists(ABSPATH.'.andy-r10b-isolated-local') || get_option('siteurl')!=='http://127.0.0.1:14141' ||
 !defined('WC_VERSION') || WC_VERSION!=='11.2.1' ||
 !class_exists('Wt_Smart_Coupon_Store_Credit_Apply', false) ||
 !class_exists('WC_SC_Coupon_Process', false)) exit(3);
add_filter('pre_wp_mail', static fn() => true, PHP_INT_MAX);
if (!WC()->session || !WC()->cart) wc_load_cart();
$cart=WC()->cart;
if(!$cart->is_empty() || $cart->get_applied_coupons()) exit(3);
$ids=[]; $notices=wc_get_notices();
try {
 $p=new WC_Product_Simple(); $p->set_name('h3d1creditprobe'.getmypid());
 $p->set_regular_price('75');$p->set_status('publish');
 $p->set_catalog_visibility('hidden'); $pid=$p->save();$ids[]=$pid;
 foreach(['webtoffee'=>'store_credit','storeapps'=>'smart_coupon'] as $vendor=>$type) {
   $cart->empty_cart();wc_clear_notices();
   $c=new WC_Coupon();$c->set_code('h3d1probe'.$vendor.getmypid());
   $c->set_discount_type($type);$c->set_amount('100');$cid=$c->save();$ids[]=$cid;
   $added=$cart->add_to_cart($pid);
   $ok=$cart->apply_coupon($c->get_code());
   $cart->calculate_totals();
   $tot=$cart->get_totals();
   echo 'PROBE='.$vendor.' TYPE='.$type.' ADD='.($added?'1':'0').
    ' APPLY='.($ok?'1':'0').' AMOUNT='.(new WC_Coupon($cid))->get_amount().
    ' DISCOUNTS='.json_encode($cart->get_coupon_discount_totals()).
    ' TOTAL='.($tot['total']??'missing').' NOTICES='.json_encode(wc_get_notices())."\n";
   $cart->empty_cart();
   wp_delete_post($cid,true);array_pop($ids);
 }
} finally {
 $cart->empty_cart();wc_set_notices($notices);
 foreach($ids as $id)wp_delete_post($id,true);
}
