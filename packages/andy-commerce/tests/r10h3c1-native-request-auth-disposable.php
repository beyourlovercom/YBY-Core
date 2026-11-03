<?php
/** H3-C1 real WordPress nonce + Woo session and Store signed CartToken. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')
 || get_option('siteurl')!=='http://127.0.0.1:14141'
 || !defined('WC_VERSION')||WC_VERSION!=='11.2.1')exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3c1-manual-ticket-shadow.php';
$cart=WC()->cart;$session=WC()->session;
if(!$cart || !$session ||count($cart->get_cart())!==0||count($cart->get_applied_coupons())!==0)exit(3);
$gate='Andy_Commerce_R10H3C1_Manual_Ticket_Shadow';
$claims=0;$assert=function($name,$v)use(&$claims) {
 echo 'H3C1_NATIVE_'.strtoupper($name).'='.($v?'PASS':'FAIL')."\n";
 if(!$v)throw new RuntimeException('H3C1_GATE_FAIL '.$name);$claims++;
};
$cartOriginal=$cart->get_applied_coupons();
$oldToken=$_SERVER['HTTP_CART_TOKEN']??null;
try {
 $nonce=wp_create_nonce('woocommerce-cart');
 $good=['apply_coupon'=>'Apply coupon','coupon_code'=>'MANUAL15','woocommerce-cart-nonce'=>$nonce];
 $ticket=$gate::from_classic('POST',$good,$cart,$session);
 $assert('classic_verified_nonce_issued',$ticket['status']==='issued');
 $assert('classic_code_bound',$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $assert('classic_replay_denied',!$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $assert('classic_wrong_nonce_denied',$gate::from_classic('POST',array_merge($good,['woocommerce-cart-nonce'=>'broken']),$cart,$session)['status']!=='issued');
 $assert('classic_missing_nonce_denied',$gate::from_classic('POST',array_diff_key($good,['woocommerce-cart-nonce'=>0]),$cart,$session)['status']!=='issued');
 $assert('classic_wrong_method_denied',$gate::from_classic('GET',$good,$cart,$session)['status']!=='issued');
 $ticket=$gate::from_classic('POST',$good,$cart,$session);
 $assert('classic_ticket_has_no_site_writes',$ticket['writes_performed']===0);
 $assert('classic_wrong_code_consumes_ticket',!$gate::consume($ticket['ticket'],'other',$cart,$session));
 $assert('classic_wrong_code_cannot_retry',!$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $request=new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon');$request->set_param('code','MANUAL15');
 $request->set_header('Nonce',wp_create_nonce('wc_store_api'));
 $ticket=$gate::from_store_api($request,$cart,$session);
 $assert('store_valid_woo_nonce_issued',$ticket['status']==='issued');
 $assert('store_valid_nonce_consumed',$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $request->set_header('Nonce','broken');
 $assert('store_invalid_nonce_denied',$gate::from_store_api($request,$cart,$session)['status']!=='issued');
 $id=$session->get_customer_id();
 $assert('session_id_present',is_string($id)&&strlen($id)>0);
 $class='Automattic\\WooCommerce\\StoreApi\\Utilities\\CartTokenUtils';
 $ct=$class::get_cart_token((string)$id);
 $_SERVER['HTTP_CART_TOKEN']=$ct;
 $request->set_header('Nonce','');
 $request->set_header('Cart-Token',$ct);
 $ticket=$gate::from_store_api($request,$cart,$session);
 $assert('store_valid_signed_cart_token_issued',$ticket['status']==='issued');
 $assert('store_cart_token_one_time_consumed',$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $request->set_header('Cart-Token','tampered');
 $assert('store_fake_token_denied',$gate::from_store_api($request,$cart,$session)['status']!=='issued');
 $request->set_header('Cart-Token',$ct);
 $ticket=$gate::from_store_api($request,$cart,$session);
 $cart->set_applied_coupons(['local-dummy-code']);
 $assert('changed_cart_snapshot_consumption_denied',!$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 $cart->set_applied_coupons([]);
 $assert('snapshot_after_change_cannot_replay',!$gate::consume($ticket['ticket'],'manual15',$cart,$session));
 echo 'R10H3C1_NATIVE_REQUEST_AUTH='.$claims.'/'.$claims.' PASS; NO_WOO_HOOK_NO_ORDER_PAYMENT'."\n";
}finally {
 $cart->set_applied_coupons($cartOriginal);
 if($oldToken===null)unset($_SERVER['HTTP_CART_TOKEN']);else $_SERVER['HTTP_CART_TOKEN']=$oldToken;
}
