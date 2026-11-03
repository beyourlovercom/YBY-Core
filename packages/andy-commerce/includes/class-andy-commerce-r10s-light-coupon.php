<?php
/**
 * R10-S: narrow manual-over-auto coupon gate. No credit, BOGO, shipping,
 * inventory, order, payment or refund writes. Explicit dual opt-in only.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_R10S_Light_Coupon {
    private static ?array $intent=null;
    private static array $pending=[];
    private static bool $registered=false;
    private static ?string $untrusted_api_code=null;
    private static $deferred_api_request=null;
    private static function code($value): ?string {
        if(!is_string($value)||strlen($value)>160)return null;
        // Use WooCommerce's own canonical code, not an ASCII-only rule
        // which could reject previously valid affiliate/customer coupons.
        $c=function_exists('wc_format_coupon_code')
            ?wc_format_coupon_code($value):strtolower(trim($value));
        return is_string($c)&&$c!==''&&strlen($c)<=160
            &&!preg_match('/[\\x00-\\x1f\\x7f]/',$c) ? $c : null;
    }
    private static function context(): ?array {
        if(!function_exists('WC')||!is_object(WC())||
            !is_object(WC()->cart)||!is_object(WC()->session)||
            !is_callable([WC()->cart,'get_applied_coupons'])||
            !is_callable([WC()->session,'get_customer_id']))return null;
        $sid=WC()->session->get_customer_id();
        if(!is_int($sid)&&!is_string($sid))return null;
        $sid=(string)$sid;
        $codes=WC()->cart->get_applied_coupons();
        if($sid===''||strlen($sid)>192||!is_array($codes)||count($codes)>50)return null;
        $seen=[];
        foreach($codes as $value){
            $c=self::code($value);if($c===null||isset($seen[$c]))return null;
            $seen[$c]=true;
        }
        return [$sid,array_values($codes)];
    }
    private static function arm($code): void {
        $c=self::code($code);$ctx=self::context();
        if($c===null||$ctx===null)return;
        $auto=null;$ordinary=0;
        foreach($ctx[1] as $old){
            try{$coupon=new WC_Coupon($old);}catch(Throwable $e){return;}
            $kind=self::kind($coupon);
            if($kind==='unknown')return;
            if($kind==='ordinary'){
                $ordinary++;
                if(self::vendor_auto($old,$coupon))$auto=$old;
            }
        }
        if($ordinary>1)$auto=null;
        self::$intent=['code'=>$c,'sid'=>$ctx[0],'before'=>$ctx[1],'auto'=>$auto];
    }
    public static function classic_capture(): void {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST'
            ||!isset($_POST['coupon_code'],$_POST['apply_coupon'])
            ||!is_string($_POST['apply_coupon'])||$_POST['apply_coupon']===''
            ||isset($_POST['remove_coupon']))return;
        self::$intent=null;
        $nonce=$_POST['woocommerce-cart-nonce']??null;
        if(!is_string($nonce)||wp_verify_nonce($nonce,'woocommerce-cart')===false)return;
        self::arm(wp_unslash($_POST['coupon_code']));
    }
    /** Native Woo checkout wc-ajax and admin-ajax apply-coupon endpoints. */
    public static function ajax_capture(): void {
        self::$intent=null;
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST'
            ||!is_string($_POST['coupon_code']??null)
            ||!is_string($_POST['security']??null)
            ||wp_verify_nonce(wp_unslash($_POST['security']),'apply-coupon')===false)return;
        self::arm(wp_unslash($_POST['coupon_code']));
    }
    public static function api_capture($response,$server,$request) {
        if($response!==null||!($request instanceof WP_REST_Request)
            ||$request->get_method()!=='POST'
            ||$request->get_route()!=='/wc/store/v1/cart/apply-coupon')return $response;
        self::$intent=null;
        // In WC Store API this filter may fire BEFORE WC()->cart/session
        // exist. Hold this *server* REST object and verify after cart load.
        if(self::context()===null){
            self::$deferred_api_request=$request;
            return $response;
        }
        $authenticated=false;
        $nonce=$request->get_header('Nonce');
        if(is_string($nonce)&&$nonce!=='')
            $authenticated=wp_verify_nonce($nonce,'wc_store_api')!==false;
        if(!$authenticated){
            $token=$request->get_header('Cart-Token');
            $class='Automattic\\WooCommerce\\StoreApi\\Utilities\\CartTokenUtils';
            if(is_string($token)&&$token!==''&&class_exists($class)
                &&method_exists($class,'get_cart_token_payload')
                &&method_exists($class,'get_request_cart_token')
                &&hash_equals($token,$class::get_request_cart_token())){
                $payload=$class::get_cart_token_payload($token);
                $ctx=self::context();
                $authenticated=$ctx!==null&&isset($payload['user_id'],$payload['iss'])
                    &&(string)$payload['user_id']===$ctx[0]&&$payload['iss']==='store-api';
            }
        }
        self::$untrusted_api_code=null;
        if($authenticated)self::arm($request->get_param('code'));
        else self::$untrusted_api_code=self::code($request->get_param('code'));
        return $response;
    }
    private static function manual($code,$ctx): bool {
        $v=self::$intent;
        if($v===null||$ctx===null||self::code($code)!==$v['code']
          ||!hash_equals($ctx[0],$v['sid']))return false;
        if($ctx[1]===$v['before'])return true;
        // Vendor may remove its verified auto code BEFORE native validation.
        // Never bless drift of protected or unknown entitlements.
        return is_string($v['auto']) &&
          array_values(array_diff($v['before'],[$v['auto']]))===$ctx[1];
    }
    /** Exact, non-destructive coupon categorization. Unknown fails closed. */
    private static function kind($coupon): string {
        if(!($coupon instanceof WC_Coupon))return 'unknown';
        try {
            if($coupon->get_free_shipping())return 'protected';
            $type=$coupon->get_discount_type();
            if(in_array($type,['smart_coupon','store_credit','wt_sc_bogo','wbte_sc_bogo'],true))
                return 'protected';
            if(!in_array($type,['percent','fixed_cart','fixed_product'],true))return 'unknown';
            $amount=$coupon->get_amount();
            if(!is_numeric($amount)||!is_finite((float)$amount)||(float)$amount<0)return 'unknown';
            if((float)$amount===0.0)return 'protected';
            $id=(int)$coupon->get_id();
            if($id>0){
                $meta=get_post_meta($id);
                if(!is_array($meta))return 'unknown';
                foreach(array_keys($meta) as $key)
                    if(is_string($key)&&preg_match('/^(?:_?wt_|_?wbte_|wc_sc_|_wc_sc_)/i',$key)
                        &&!in_array($key,['wc_sc_auto_apply_coupon','_wt_make_auto_coupon',
                            '_wbte_sc_auto_coupon_priority','wc_sc_user_role_ids'],true))
                        return 'unknown';
            }
            return 'ordinary';
        }catch(Throwable $e){return 'unknown';}
    }
    private static function vendor_auto($code,$coupon): bool {
        if(self::kind($coupon)!=='ordinary'||$coupon->get_individual_use())return false;
        $session=WC()->session;
        $found=[];
        foreach(['auto_applied_coupons','wc_sc_auto_applied_coupons'] as $key){
            $claims=$session->get($key,[]);
            if(!is_array($claims)||count($claims)>100)return false;
            $hits=0;$seen=[];
            foreach($claims as $s){
                $normalized=self::code($s);
                if($normalized===null||isset($seen[$normalized]))return false;
                $seen[$normalized]=true;
                if($normalized===self::code($code))$hits++;
            }
            if($hits===1)$found[]=$key;
        }
        // Both vendors claiming the same coupon is ambiguous, not consent.
        if(count($found)!==1)return false;
        if($found[0]==='wc_sc_auto_applied_coupons'){
            if(!class_exists('WC_SC_Auto_Apply_Coupon',false))return false;
            return WC_SC_Auto_Apply_Coupon::get_instance()
                ->is_coupon_applied_by_auto_apply($code)===true;
        }
        if(!class_exists('Wt_Smart_Coupon_Auto_Coupon_Common',false))return false;
        if(!is_callable(['Wt_Smart_Coupon_Auto_Coupon_Common','is_auto_coupon']))return false;
        return Wt_Smart_Coupon_Auto_Coupon_Common::is_auto_coupon($coupon)===true;
    }
    public static function allow($valid,$coupon) {
        if(self::$deferred_api_request!==null){
            $deferred=self::$deferred_api_request;
            self::$deferred_api_request=null;
            self::api_capture(null,null,$deferred);
        }
        if($valid!==true||self::kind($coupon)!=='ordinary')return $valid;
        $ctx=self::context();
        if($ctx===null)return false;
        $code=self::code($coupon->get_code());
        if($code===null)return false;
        // Native Classic apply-coupon form has no nonce gate of its own;
        // do not grant a new coupon via this form without our verified proof.
        $already=in_array($code,$ctx[1],true);
        if(!$already && self::$untrusted_api_code===$code)return false;
        if(!$already && ($_SERVER['REQUEST_METHOD']??'')==='POST'
            &&isset($_POST['apply_coupon'],$_POST['coupon_code'])
            &&self::code(wp_unslash($_POST['coupon_code']))===$code
            &&!self::manual($code,$ctx))return false;
        // An issued ticket must not authorize unexpected cart drift.
        if(self::$intent!==null && self::$intent['code']===$code
            &&!self::manual($code,$ctx))return false;
        $ordinary=[];
        foreach($ctx[1] as $old){
            try{$native=new WC_Coupon($old);}catch(Throwable $e){return false;}
            $kind=self::kind($native);
            if($kind==='unknown')return false;
            if($kind==='ordinary')$ordinary[]=['code'=>$old,'coupon'=>$native];
        }
        if(count($ordinary)>1)return false;
        // Woo individual-use would evict existing protected entitlements.
        if($coupon->get_individual_use() && count($ctx[1])>0)return false;
        if(!$ordinary)return true;
        $prior=$ordinary[0];
        if(self::code($prior['code'])===$code)return true;
        if(!self::manual($code,$ctx)
            ||$coupon->get_individual_use()
            ||!self::vendor_auto($prior['code'],$prior['coupon']))return false;
        self::$pending[$code]=['before'=>$ctx[1],'sid'=>$ctx[0],
            'remove'=>$prior['code']];
        return true;
    }
    public static function applied($code): void {
        $c=self::code($code);$ctx=self::context();
        if($c===null||$ctx===null)return;
        if(isset(self::$pending[$c])){
            $p=self::$pending[$c];unset(self::$pending[$c]);
            $expected=array_merge($p['before'],[$code]);
            // Coupon has already passed Woo core and vendor eligibility. If
            // another hook changed the cart, remove only our NEW manual code.
            $safe=$p['sid']===$ctx[0]&&$expected===$ctx[1]
                &&is_callable([WC()->cart,'remove_coupon']);
            if(!$safe||WC()->cart->remove_coupon($p['remove'])!==true){
                WC()->cart->remove_coupon($code);
                self::$intent=null;
                return;
            }
        }
        self::$intent=null;
    }
    public static function register(): void {
        if(self::$registered||!class_exists('Andy_Commerce_R10C_Transaction_Compatibility')
            ||!Andy_Commerce_R10C_Transaction_Compatibility::enabled('coupon')
            ||get_option('andy_commerce_r10s_light_priority_enabled_v1','no')!=='yes')return;
        add_action('wp_loaded',[self::class,'classic_capture'],19,0);
        add_action('wc_ajax_apply_coupon',[self::class,'ajax_capture'],1,0);
        add_action('wp_ajax_woocommerce_apply_coupon',[self::class,'ajax_capture'],1,0);
        add_action('wp_ajax_nopriv_woocommerce_apply_coupon',[self::class,'ajax_capture'],1,0);
        add_filter('rest_request_before_callbacks',[self::class,'api_capture'],10,3);
        add_action('woocommerce_applied_coupon',[self::class,'applied'],1,1);
        self::$registered=true;
    }
}