<?php
/**
 * R10-H3-C request path detector. READ-ONLY, never grants manual provenance.
 * A route/form shape does not prove customer intent or Store API authentication.
 */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
final class Andy_Commerce_R10H3C_Manual_Request_Shadow {
    private static function code($value):?string {
        if(!is_string($value)||strlen($value)>160)return null;
        $value=strtolower(trim($value));
        return preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D',$value)?$value:null;
    }
    private static function result(string $status,?string $channel=null):array {
        return ['status'=>$status,'channel'=>$channel,
            'manual_origin_verified'=>false,
            'replacement_authorized'=>false,
            'writes_performed'=>0];
    }
    /** Classic WC_Form_Handler::update_cart_action: POST shape is NOT auth. */
    public static function classic($method,$post):array {
        if($method!=='POST'||!is_array($post)
            ||!isset($post['apply_coupon'],$post['coupon_code'])
            ||isset($post['remove_coupon'])
            ||!is_string($post['apply_coupon'])
            ||!self::code($post['coupon_code']))
            return self::result('not_proven');
        // Native WC 11.2.1 coupon apply branch does not verify a cart nonce.
        // A future request-scoped interceptor must independently prove
        // authorization, verified cart session, and the exact server callsite.
        return self::result('candidate_requires_trusted_server_interceptor','classic');
    }
    /** Woo Store API cart/apply-coupon route shape is NOT auth alone. */
    public static function store_api($request):array {
        if(!is_object($request)||!($request instanceof WP_REST_Request)
            ||$request->get_method()!=='POST'
            ||$request->get_route()!=='/wc/store/v1/cart/apply-coupon'
            ||!self::code($request->get_param('code')))
            return self::result('not_proven');
        // This route has permission_callback '__return_true' in WC11.2.1;
        // API nonce/Cart-Token validation is handled elsewhere in Store API.
        // A direct route-looking request is NOT a trusted server-issued proof.
        return self::result('candidate_requires_store_api_auth_and_interceptor','store_api');
    }
}
