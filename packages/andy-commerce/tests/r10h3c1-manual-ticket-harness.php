<?php
/** Native auth code safety regression with no WordPress writes or server. */
namespace Automattic\WooCommerce\StoreApi\Utilities {
 class CartTokenUtils {
  public static function get_request_cart_token():string {return $_SERVER['HTTP_CART_TOKEN']??'';}
  public static function get_cart_token_payload(string $v):array {
   return $v==='signed-token' ? ['user_id'=>'session-1','iss'=>'store-api'] : [];
  }
 }
}
namespace {
 define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
 class WP_REST_Request {
  private $method,$route,$params=[],$headers=[];
  function __construct($m,$r){$this->method=$m;$this->route=$r;}
  function get_method(){return $this->method;}
  function get_route(){return $this->route;}
  function set_param($k,$v){$this->params[$k]=$v;}
  function get_param($k){return $this->params[$k]??null;}
  function set_header($k,$v){$this->headers[strtolower($k)]=$v;}
  function get_header($k){return $this->headers[strtolower($k)]??'';}
 }
 class C1Cart {
  public $codes=['auto10'];
  function get_applied_coupons(){return $this->codes;}
 }
 class C1Session {
  function get_customer_id(){return 'session-1';}
 }
 $GLOBALS['c1_cart']=new C1Cart();$GLOBALS['c1_sess']=new C1Session();
 function WC(){return (object)['cart'=>$GLOBALS['c1_cart'],'session'=>$GLOBALS['c1_sess']];}
 function wp_verify_nonce($nonce,$action){
  return $nonce==='nonce:'.$action ? 1 : false;
 }
 require dirname(__DIR__).'/includes/class-andy-commerce-r10h3c1-manual-ticket-shadow.php';
 $g='Andy_Commerce_R10H3C1_Manual_Ticket_Shadow';$c=$GLOBALS['c1_cart'];$s=$GLOBALS['c1_sess'];
 $form=['apply_coupon'=>'yes','coupon_code'=>'manual15','woocommerce-cart-nonce'=>'nonce:woocommerce-cart'];
 $results=[];
 $results['classic_issued']=($ticket=$g::from_classic('POST',$form,$c,$s))['status']==='issued';
 $results['classic_one_time']=$g::consume($ticket['ticket'],'manual15',$c,$s);
 $results['classic_replay_denied']=!$g::consume($ticket['ticket'],'manual15',$c,$s);
 $results['classic_invalid_nonce_denied']=$g::from_classic('POST',array_merge($form,['woocommerce-cart-nonce'=>'bad']),$c,$s)['status']!=='issued';
 $results['classic_get_denied']=$g::from_classic('GET',$form,$c,$s)['status']!=='issued';
 $results['classic_missing_nonce_denied']=$g::from_classic('POST',array_diff_key($form,['woocommerce-cart-nonce'=>0]),$c,$s)['status']!=='issued';
 $request=new WP_REST_Request('POST','/wc/store/v1/cart/apply-coupon');$request->set_param('code','manual15');$request->set_header('Nonce','nonce:wc_store_api');
 $results['store_nonce_issued']=($ticket=$g::from_store_api($request,$c,$s))['status']==='issued';
 $results['store_nonce_used']=$g::consume($ticket['ticket'],'manual15',$c,$s);
 $request->set_header('Nonce','bad');
 $results['store_bad_nonce_denied']=$g::from_store_api($request,$c,$s)['status']!=='issued';
 $request->set_header('Nonce','');$request->set_header('Cart-Token','signed-token');$_SERVER['HTTP_CART_TOKEN']='signed-token';
 $results['signed_cart_token_issued']=($ticket=$g::from_store_api($request,$c,$s))['status']==='issued';
 $results['token_bound_to_session']=$g::consume($ticket['ticket'],'manual15',$c,$s);
 $_SERVER['HTTP_CART_TOKEN']='other';
 $results['header_mismatch_denied']=$g::from_store_api($request,$c,$s)['status']!=='issued';
 $_SERVER['HTTP_CART_TOKEN']='signed-token';
 $ticket=$g::from_store_api($request,$c,$s);
 $c->codes=['auto10','another'];
 $results['cart_changed_token_denied']=!$g::consume($ticket['ticket'],'manual15',$c,$s);
 $c->codes=['auto10'];
 $results['stale_ticket_never_replays']=!$g::consume($ticket['ticket'],'manual15',$c,$s);
 $fail=0;
 foreach($results as $name=>$ok){echo 'R10H3C1_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";if(!$ok)$fail++;}
 echo 'R10H3C1_MANUAL_TICKET_SHADOW='.(count($results)-$fail).'/'.count($results)."\n";
 exit($fail?1:0);
}
