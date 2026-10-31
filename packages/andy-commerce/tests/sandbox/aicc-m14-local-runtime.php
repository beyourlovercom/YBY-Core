<?php
/**
 * Isolated M14 proof, NEVER include in installable Andy Commerce.
 * Before proceeding, verifies exact localhost/DB, LOCAL environment and Woo COD.
 * Activation option is OFF absent explicit, per-scenario test fixture.
 */
if (!defined('ABSPATH')) {exit;}
final class AICC_M14_Isolated_Auto_Rule {
    private const LOCAL_URL='http://127.0.0.1:18973';
    private const LOCAL_DB='aicc_commerce_p5_20261023';
    private static bool $busy=false;
    private static function environment_is_verified(): bool {
        return function_exists('home_url')
            && home_url()===self::LOCAL_URL && defined('DB_NAME') && DB_NAME===self::LOCAL_DB
            && function_exists('wp_get_environment_type') && wp_get_environment_type()==='local'
            && defined('DISABLE_WP_CRON') && DISABLE_WP_CRON===true
            && defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL===true
            && function_exists('get_woocommerce_currency') && get_woocommerce_currency()==='USD';
    }
    public static function is_enabled():bool {
        return self::environment_is_verified() && get_option('aicc_m14_sandbox_active','no')==='yes';
    }
    private static function fixture():?array {
        if(!self::is_enabled())return null;
        $v=get_option('aicc_m14_sandbox_case');
        if(!is_array($v)||!isset($v['draft'],$v['map'],$v['date_utc'])
            ||!is_array($v['draft'])||!is_array($v['map'])||!is_string($v['date_utc']))
            return null;
        return $v;
    }
    public static function register():void {
        if(!self::environment_is_verified() || !class_exists('WooCommerce')) return;
        add_filter('woocommerce_get_shop_coupon_data',[self::class,'virtual_coupon'],10,3);
        add_filter('woocommerce_apply_with_individual_use_coupon',[self::class,'allow_manual_over_auto'],10,4);
        add_action('woocommerce_applied_coupon',[self::class,'manual_coupon_wins'],5,1);
        add_action('woocommerce_before_calculate_totals',[self::class,'zero_local_gift'],99,1);
        add_action('woocommerce_after_calculate_totals',[self::class,'apply_verified_rule'],99,1);
    }
    private static function code(array $case):string {
        return 'aicc-m14-only-'.$case['draft']['id'];
    }
    public static function virtual_coupon($couponData,$input,$coupon) {
        $case=self::fixture();
        if(!$case || !is_string($input) || $input!==self::code($case) || !WC()->session) return $couponData;
        $amount=WC()->session->get('aicc_m14_virtual_discount_minor');
        if(!is_int($amount) || $amount<1 || $amount>1000000)return $couponData;
        return ['discount_type'=>'fixed_cart','amount'=>number_format($amount/100,2,'.',''),
            'individual_use'=>true,'usage_limit'=>0,'description'=>'AICC local proof only'];
    }
    public static function allow_manual_over_auto($permitted,$incoming,$existing,$applied) {
        $case=self::fixture();
        if(!$case || !($incoming instanceof WC_Coupon) || !($existing instanceof WC_Coupon))
            return $permitted;
        // Explicit user-entered coupon takes priority over the local auto offer.
        return $existing->get_code()===self::code($case)
            && $incoming->get_code()!==self::code($case) ? true : $permitted;
    }
    public static function manual_coupon_wins($newCode):void {
        $case=self::fixture();
        if(!$case || !is_string($newCode) || $newCode===self::code($case)
           || !WC()->cart || !WC()->cart->has_discount(self::code($case)))return;
        self::$busy=true;
        try {
            WC()->cart->remove_coupon(self::code($case));
            WC()->session->set('aicc_m14_virtual_discount_minor',null);
        } finally { self::$busy=false; }
    }
    public static function zero_local_gift($cart):void {
        $case=self::fixture();
        if(!$case || !($case['draft']['type']==='free_gift'))return;
        foreach($cart->get_cart() as $item){
            if(!empty($item['aicc_m14_local_gift']) && (int)$item['product_id']===(int)$case['draft']['rule']['product_id']){
                $item['data']->set_price('0');
            }
        }
    }
    private static function revoke_local_gift($cart, array $case):void {
        if(($case['draft']['type']??'')!=='free_gift')return;
        $giftId=(int)($case['draft']['rule']['product_id']??0);
        $keys=[];
        foreach($cart->get_cart() as $key=>$item){
            if(!empty($item['aicc_m14_local_gift'])
                && (int)$item['product_id']===$giftId) $keys[]=$key;
        }
        if(!$keys)return;
        $previous=self::$busy;
        self::$busy=true;
        try {
            foreach($keys as $key)$cart->remove_cart_item($key);
            $cart->calculate_totals();
        } finally {self::$busy=$previous;}
    }
    public static function apply_verified_rule($cart):void {
        if(self::$busy)return;
        $case=self::fixture();
        if(!$case || !($cart instanceof WC_Cart) || !WC()->session)return;
        // Other native coupons have authority: no stacking. For our own virtual
        // coupon, REMOVE it, recompute clean native Woo totals, and recalculate
        // current campaign eligibility/amount. This avoids stale discounts when
        // quantity or SKU changes between Store API calls.
        $codes=array_values((array)$cart->get_applied_coupons());
        if($codes) {
            if(($case['draft']['type']??'')==='free_gift'){
                self::revoke_local_gift($cart,$case);
                return;
            }
            if(count($codes)!==1 || $codes[0]!==self::code($case))return;
            self::$busy=true;
            try {
                $cart->remove_coupon(self::code($case));
                WC()->session->set('aicc_m14_virtual_discount_minor',null);
                $cart->calculate_totals();
            } finally {
                self::$busy=false;
            }
        }
        if(!class_exists('Andy_Commerce_M12_Woo_Six_Shadow')) {
            require_once WP_PLUGIN_DIR.'/andy-commerce/includes/class-andy-commerce-m12-woo-six-shadow.php';
        }
        $flags=[];
        $map=$case['map'];
        foreach($cart->get_cart() as $key=>$item){
            $id=(int)($item['product_id']??0);
            $name=(string)$id;
            if(!isset($map[$name]) || !is_array($map[$name])){
                self::revoke_local_gift($cart,$case);
                return;
            }
            $flags[$key]=$map[$name];
        }
        if(!$flags)return;
        try{
            $r=Andy_Commerce_M12_Woo_Six_Shadow::preview(
                $cart,$flags,9900,[$case['draft']],$case['date_utc']
            );
            if($r['runtime_enabled']!==false || $r['native_woo_mutations']!==0)return;
            $s=$r['simulation'];
            if(empty($s['evaluations'][0]['eligible'])){
                self::revoke_local_gift($cart,$case);
                return;
            }
            self::$busy=true;
            if($case['draft']['type']==='free_gift'){
                $giftId=(int)($s['qualified_gifts'][0]['gift_product_id']??0);
                if($giftId!== (int)$case['draft']['rule']['product_id'])return;
                $p=wc_get_product($giftId);
                if(!$p || !$p->managing_stock() || !$p->is_in_stock() || (int)$p->get_stock_quantity()<1)return;
                foreach($cart->get_cart() as $item){if((int)$item['product_id']===$giftId)return;}
                $added=$cart->add_to_cart($giftId,1,0,[],['aicc_m14_local_gift'=>true]);
                if($added){ $cart->calculate_totals(); }
            } else {
                $discount=(int)($s['projected_discount_usd_minor']??0);
                if($discount<1 || $discount>1000000)return;
                WC()->session->set('aicc_m14_virtual_discount_minor',$discount);
                if($cart->apply_coupon(self::code($case))) $cart->calculate_totals();
            }
        } catch (Throwable $e) {
            self::revoke_local_gift($cart,$case);
            error_log('AICC_M14_REJECT '.get_class($e));
        } finally {
            self::$busy=false;
        }
    }
}
add_action('plugins_loaded',[AICC_M14_Isolated_Auto_Rule::class,'register'],120);
