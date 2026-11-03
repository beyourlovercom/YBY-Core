<?php
/**
 * R10-H3-C1 native request-bound manual intent, SOURCE-ONLY, never booted.
 *
 * Calls must originate from a trusted SERVER interceptor at the matched
 * Classic/Store API handler, after Woo has loaded the correct cart/session.
 * This cannot be called by a browser. No globals/WordPress options are set.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_R10H3C1_Manual_Ticket_Shadow {
    private static array $tickets=[];
    private static function result(string $status,?string $ticket=null):array {
        return ['status'=>$status,'ticket'=>$ticket,'verified'=>$status==='issued',
            'writes_performed'=>0];
    }
    private static function code($v):?string {
        if(!is_string($v)||strlen($v)>160)return null;
        $c=strtolower(trim($v));
        return preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D',$c)?$c:null;
    }
    private static function session_id($cart,$session):?string {
        if(!is_object($cart)||!is_object($session)||!function_exists('WC')
            ||!is_object(WC()) ||WC()->cart!==$cart ||WC()->session!==$session
            ||!is_callable([$cart,'get_applied_coupons'])
            ||!is_callable([$session,'get_customer_id']))return null;
        $id=$session->get_customer_id();
        if(!is_string($id)&&!is_int($id))return null;
        $id=(string)$id;
        return $id!=='' && strlen($id)<=192?$id:null;
    }
    private static function cart_hash($cart):?string {
        $rows=$cart->get_applied_coupons();
        if(!is_array($rows)||count($rows)>50)return null;
        foreach($rows as $code)if(self::code($code)===null)return null;
        return hash('sha256',json_encode(array_values($rows),JSON_UNESCAPED_SLASHES));
    }
    private static function issue(string $channel,string $coupon,$cart,$session):array {
        $sid=self::session_id($cart,$session);
        $hash=$sid===null?null:self::cart_hash($cart);
        if($sid===null||$hash===null||count(self::$tickets)>=16)return self::result('cart_unverified');
        try{$key=bin2hex(random_bytes(16));}catch(\Throwable $e){return self::result('entropy_failure');}
        self::$tickets[$key]=[$channel,$coupon,$sid,$hash];
        return self::result('issued',$key);
    }
    /** Classic requires an independently validated nonce, unlike WC's form branch. */
    public static function from_classic($method,$post,$cart,$session):array {
        if($method!=='POST'||!is_array($post)||empty($post['apply_coupon'])
            ||!is_string($post['apply_coupon'])||isset($post['remove_coupon'])
            ||!is_string($post['woocommerce-cart-nonce']??null)
            ||!function_exists('wp_verify_nonce'))return self::result('unverified_classic');
        $code=self::code($post['coupon_code']??null);
        if($code===null||wp_verify_nonce($post['woocommerce-cart-nonce'],'woocommerce-cart')===false)
            return self::result('unverified_classic');
        return self::issue('classic',$code,$cart,$session);
    }
    /** Match exact current Woo Store API nonce or customer-bound signed Cart-Token. */
    public static function from_store_api($request,$cart,$session):array {
        if(!is_object($request)||!($request instanceof \WP_REST_Request)
            ||$request->get_method()!=='POST'
            ||$request->get_route()!=='/wc/store/v1/cart/apply-coupon')return self::result('unverified_store_route');
        $code=self::code($request->get_param('code'));
        $sid=self::session_id($cart,$session);
        if($code===null||$sid===null)return self::result('unverified_store_session');
        $nonce=$request->get_header('Nonce');
        $verified=false;
        if(is_string($nonce)&&$nonce!==''&&function_exists('wp_verify_nonce')) {
            $verified=wp_verify_nonce($nonce,'wc_store_api')!==false;
        }
        if(!$verified) {
            $token=$request->get_header('Cart-Token');
            $class='Automattic\\WooCommerce\\StoreApi\\Utilities\\CartTokenUtils';
            if(is_string($token)&&$token!==''&&class_exists($class)
                &&method_exists($class,'get_cart_token_payload')
                &&method_exists($class,'get_request_cart_token')
                &&hash_equals($token,$class::get_request_cart_token())) {
                $payload=$class::get_cart_token_payload($token);
                $verified=isset($payload['user_id'],$payload['iss'])
                    &&(string)$payload['user_id']===$sid
                    &&$payload['iss']==='store-api';
            }
        }
        if(!$verified)return self::result('unverified_store_auth');
        return self::issue('store_api',$code,$cart,$session);
    }
    /** Consume exactly once; same request, cart identity, code and snapshot. */
    public static function consume($ticket,$code,$cart,$session):bool {
        if(!is_string($ticket)||!isset(self::$tickets[$ticket]))return false;
        $expected=self::$tickets[$ticket];unset(self::$tickets[$ticket]);
        $normalized=self::code($code);
        $sid=self::session_id($cart,$session);
        $hash=$sid===null?null:self::cart_hash($cart);
        return $normalized!==null&&$sid!==null&&$hash!==null
            &&hash_equals($expected[1],$normalized)
            &&hash_equals($expected[2],$sid)
            &&hash_equals($expected[3],$hash);
    }
}
