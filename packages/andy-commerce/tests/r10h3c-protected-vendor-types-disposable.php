<?php
/** R10-H3-C exact locally installed Woo/vendor protection smoke. */
if (!is_file(ABSPATH.'.andy-r10b-isolated-local')
 || get_option('siteurl')!=='http://127.0.0.1:14141'
 || !defined('WC_VERSION')||WC_VERSION!=='11.2.1') exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-reward-protection-shadow.php';
if(!function_exists('get_plugins'))require_once ABSPATH.'wp-admin/includes/plugin.php';
$wanted=[
 'wt-smart-coupon-pro/wt-smart-coupon-pro.php'=>'3.2.0',
 'woocommerce-smart-coupons/woocommerce-smart-coupons.php'=>'9.51.0',
 'yaycurrency-pro/yay-currency.php'=>'3.3.5',
];
$plugins=get_plugins();$exact=true;
foreach($wanted as $slug=>$version){
 $active=is_plugin_active($slug);
 echo 'H3C_NATIVE_STACK_'.$slug.'_VERSION='.($plugins[$slug]['Version']??'MISSING')." ACTIVE=".($active?'yes':'no')."\n";
 if(!$active||($plugins[$slug]['Version']??null)!==$version)$exact=false;
}
if(!$exact)exit(3);
$cases=[
 ['ordinary','fixed_cart','10',false,'ordinary'],
 ['storeapps_credit','smart_coupon','10',false,'protected'],
 ['webtoffee_credit','store_credit','10',false,'protected'],
 ['webtoffee_bogo','wt_sc_bogo','10',false,'protected'],
 ['webtoffee_legacy_bogo','wbte_sc_bogo','10',false,'protected'],
 ['free_shipping','fixed_cart','0',true,'protected'],
 ['zero_discount','percent','0',false,'protected'],
];
$passes=0;foreach($cases as [$name,$type,$amount,$free,$status]){
 try {
  $coupon=new WC_Coupon();$coupon->set_code('h3c_'.str_replace('_','',$name));
  $coupon->set_discount_type($type);$coupon->set_amount($amount);$coupon->set_free_shipping($free);
  $out=Andy_Commerce_R10H3C_Reward_Protection_Shadow::classify($coupon,true);
  $ok=$out['status']===$status && $out['writes_performed']===0;
 }catch(Throwable $e){$ok=false;}
 echo 'H3C_NATIVE_PROTECTED_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)exit(3);$passes++;
}
echo "R10H3C_NATIVE_EXACT_VENDOR_COUPON_TYPES=".$passes."/".count($cases)." PASS; NO_ORDERS_NO_CART_WRITES\n";
