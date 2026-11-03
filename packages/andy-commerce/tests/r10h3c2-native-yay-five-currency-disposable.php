<?php
/** H3-C2 actual installed Yay+Woo isolated currency/coupon calculation probe. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local') ||get_option('siteurl')!=='http://127.0.0.1:14141' ||!defined('WC_VERSION')||WC_VERSION!=='11.2.1')exit(3);
$id=(int)getenv('H3C2_CURRENCY_ID');
$codes=[10=>'USD',20=>'EUR',21=>'GBP',22=>'AUD',23=>'CAD'];
if(!isset($codes[$id]))exit(3);
$_COOKIE['yay_currency_widget']=(string)$id;
$currencyClass='Yay_Currency\\Helpers\\YayCurrencyHelper';
if(!class_exists($currencyClass))throw new RuntimeException('YAY_CLASS_MISSING');
$meta=$currencyClass::get_currency_by_ID($id);
if(!is_array($meta) || ($meta['currency']??null)!==$codes[$id])throw new RuntimeException('YAY_CONFIG_MISSING_'.$id);
$rate=(float)($meta['rate']??0);
if($rate<=0 || (int)($meta['status']??0)!==1)throw new RuntimeException('YAY_RATE_INACTIVE');
$det=$currencyClass::detect_current_currency();
echo 'H3C2_EXPECTED_CURRENCY='.$codes[$id].' ID='.$id.' FIXTURE_RATE='.$rate."\n";
echo 'H3C2_DETECTED_CURRENCY='.($det['currency']??'MISSING')."\n";
echo 'H3C2_WC_DISPLAY_CURRENCY='.get_woocommerce_currency()."\n";
$cart=WC()->cart;$session=WC()->session;
if(!$cart || !$session||count($cart->get_cart())||count($cart->get_applied_coupons()))exit(3);
$ids=[];$tag='h3c2'.$id.getmypid();$oldNotices=wc_get_notices();
try {
 $p=new WC_Product_Simple();$p->set_name($tag);$p->set_status('publish');$p->set_catalog_visibility('hidden');$p->set_regular_price('100');$pid=$p->save();$ids[]=$pid;
 $coupon=new WC_Coupon();$coupon->set_code($tag.'minus');$coupon->set_discount_type('fixed_cart');$coupon->set_amount('10');$cid=$coupon->save();$ids[]=$cid;
 $cart->add_to_cart($pid);
 if(!$cart->apply_coupon($coupon->get_code()))throw new RuntimeException('NATIVE_COUPON_FAILED');
 $cart->calculate_totals();
 $totals=$cart->get_totals();
 echo 'H3C2_PRODUCT_EFFECTIVE_PRICE='.$cart->get_cart_contents_total()."\n";
 echo 'H3C2_DISCOUNT_TOTAL='.($totals['discount_total']??'NONE')."\n";
 echo 'H3C2_CART_TOTAL='.($totals['total']??'NONE')."\n";
 echo 'H3C2_CURRENCY_METADATA_RATE='.($det['rate']??'MISSING')."\n";
 echo 'H3C2_WPCLI_BASE_PRICE_COUPON_VALID='.(is_numeric($totals['discount_total']??null)?'YES':'NO')."\n";
}finally{
 $cart->empty_cart();
 foreach($ids as $postid)if($postid>0)wp_delete_post($postid,true);
 wc_set_notices($oldNotices);
 unset($_COOKIE['yay_currency_widget']);
}
