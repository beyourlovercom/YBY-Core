<?php
/**
 * M-P2 Woo promotion adapter, built from proven isolated M14 behavior.
 *
 * Ships only as source in Marketing PR #228. #213 alone owns bootstrap wiring,
 * release/ZIP and eventual Dev activation. Off by default, exact disposable
 * local Woo sandbox gate, native USD ledger, one M7 revision-bound campaign.
 *
 * Never connects payment, refund, order, shipping policy, ERP or email hooks.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

require_once __DIR__.'/class-andy-commerce-marketing-p1-sandbox-contract.php';
require_once __DIR__.'/class-andy-commerce-m12-woo-six-shadow.php';

final class Andy_Commerce_Marketing_P2_Woo_Adapter {
    private const CLASSIFICATIONS = 'andy_commerce_marketing_p2_line_flags';
    private const SESSION_DISCOUNT = 'andy_commerce_p2_discount_minor';
    private const GIFT_MARKER = 'andy_commerce_p2_sandbox_gift';
    private const COUPON_PREFIX = 'aicc-p2-';
    private static bool $busy=false;

    public static function boot(): void {
        if (!class_exists('WooCommerce')
            || !Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated()
            || !function_exists('get_option')
            || get_option('aicc_m14_sandbox_active','no')!=='no'
            || Andy_Commerce_Marketing_P1_Sandbox_Contract::current()===null) return;

        add_filter('woocommerce_get_shop_coupon_data',[self::class,'coupon_data'],10,3);
        add_filter('woocommerce_apply_with_individual_use_coupon',[self::class,'prefer_manual_coupon'],10,4);
        add_action('woocommerce_applied_coupon',[self::class,'manual_coupon_added'],5,1);
        add_action('woocommerce_before_calculate_totals',[self::class,'gift_zero_price'],99,1);
        add_action('woocommerce_after_calculate_totals',[self::class,'recalculate'],99,1);
    }

    private static function campaign(): ?array {
        return Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
    }

    private static function code(array $draft): string {
        return self::COUPON_PREFIX.$draft['id'];
    }

    private static function session() {
        return function_exists('WC') && WC() ? WC()->session : null;
    }

    private static function tracked(string $code): bool {
        return strncmp($code,self::COUPON_PREFIX,strlen(self::COUPON_PREFIX))===0;
    }

    public static function coupon_data($data,$code,$coupon) {
        $draft=self::campaign(); $session=self::session();
        if(!$draft || !$session || !is_string($code) || $code!==self::code($draft))return $data;
        $discount=$session->get(self::SESSION_DISCOUNT);
        if(!is_int($discount)||$discount<1||$discount>1000000)return $data;
        return [
            'discount_type'=>'fixed_cart',
            'amount'=>number_format($discount/100,2,'.',''),
            'individual_use'=>true,'usage_limit'=>0,
            'description'=>'AICC isolated marketing sandbox proof',
        ];
    }

    public static function prefer_manual_coupon($allowed,$incoming,$existing,$applied) {
        $draft=self::campaign();
        if(!$draft||!($incoming instanceof WC_Coupon)||!($existing instanceof WC_Coupon))
            return $allowed;
        return $existing->get_code()===self::code($draft)
            && $incoming->get_code()!==self::code($draft) ? true : $allowed;
    }

    public static function manual_coupon_added($incoming): void {
        $draft=self::campaign();
        $session=self::session();
        if(!$draft || !$session || !is_string($incoming)
            || $incoming===self::code($draft) || !WC()->cart) return;
        self::$busy=true;
        try {
            if(WC()->cart->has_discount(self::code($draft))) {
                WC()->cart->remove_coupon(self::code($draft));
            }
            $session->set(self::SESSION_DISCOUNT,null);
            self::remove_gifts(WC()->cart);
        } finally { self::$busy=false; }
    }

    public static function gift_zero_price($cart): void {
        $draft=self::campaign();
        if(!$draft || ($draft['type']??null)!=='free_gift'
            || !($cart instanceof WC_Cart))return;
        $giftId=$draft['rule']['product_id']??null;
        if(!is_int($giftId)||$giftId<1)return;
        foreach($cart->get_cart() as $item) {
            if(!empty($item[self::GIFT_MARKER])
                && (int)($item['product_id']??0)===$giftId
                && isset($item['data']) && ($item['data'] instanceof WC_Product)) {
                $item['data']->set_price('0');
            }
        }
    }

    private static function remove_gifts(WC_Cart $cart): void {
        $keys=[];
        foreach($cart->get_cart() as $key=>$item) {
            if(!empty($item[self::GIFT_MARKER]))$keys[]=$key;
        }
        if(!$keys)return;
        $prior=self::$busy;
        self::$busy=true;
        try {
            foreach($keys as $key)$cart->remove_cart_item($key);
            $cart->calculate_totals();
        } finally { self::$busy=$prior; }
    }

    private static function clean_automatic(WC_Cart $cart): bool {
        $own=[];
        foreach((array)$cart->get_applied_coupons() as $code) {
            if(is_string($code)&&self::tracked($code))$own[]=$code;
        }
        if(!$own)return false;
        $session=self::session();
        $prior=self::$busy;
        self::$busy=true;
        try {
            foreach($own as $code)$cart->remove_coupon($code);
            if($session)$session->set(self::SESSION_DISCOUNT,null);
            $cart->calculate_totals(); // recover clean native Woo authoritative total
        } finally { self::$busy=$prior; }
        return true;
    }

    private static function explicit_flags(WC_Cart $cart,array $draft): ?array {
        $map=get_option(self::CLASSIFICATIONS,null);
        if(!is_array($map)||!$map||count($map)>100)return null;
        $flags=[];
        foreach($cart->get_cart() as $key=>$item) {
            if(!is_array($item)||!is_int($item['product_id']??null)) return null;
            $pid=(string)$item['product_id'];
            if(!empty($item[self::GIFT_MARKER])
                && ($draft['type']??null)==='free_gift'
                && (int)$item['product_id']===($draft['rule']['product_id']??null)) {
                $flags[$key]=['eligible_for_promotion'=>false,
                    'special_shipping'=>false,'free_gift'=>true];
                continue;
            }
            $policy=$map[$pid]??null;
            if(!is_array($policy))return null;
            foreach(['eligible_for_promotion','special_shipping','free_gift'] as $field){
                if(!is_bool($policy[$field]??null))return null;
            }
            // A preexisting customer gift/flag must not become eligible.
            if($policy['free_gift']&&$policy['eligible_for_promotion'])return null;
            $flags[$key]=$policy;
        }
        return $flags;
    }

    public static function recalculate($cart): void {
        if(self::$busy || !($cart instanceof WC_Cart))return;
        $session=self::session();
        if(!$session)return;
        $draft=self::campaign();
        // Revision change, approval revocation or missing sandbox flag:
        // remove any stale tracked promotion rather than keep stale discounts.
        if(!$draft) {
            self::clean_automatic($cart);
            self::remove_gifts($cart);
            return;
        }
        $codes=array_values((array)$cart->get_applied_coupons());
        $manual=array_filter($codes,static fn($code):bool =>
            !is_string($code)||!self::tracked($code));
        if($manual || count($codes)>1) {
            self::clean_automatic($cart);
            self::remove_gifts($cart);
            return; // Native user coupon always wins, never double-stack.
        }

        // Remove only our coupon then recompute; otherwise repeated totals
        // calls or SKU/quantity changes would compound stale discounts.
        self::clean_automatic($cart);
        $flags=self::explicit_flags($cart,$draft);
        if($flags===null || !$flags) {self::remove_gifts($cart);return;}
        try {
            $result=Andy_Commerce_M12_Woo_Six_Shadow::preview(
                $cart,$flags,4900,[$draft],gmdate('Y-m-d')
            );
            if(($result['runtime_enabled']??null)!==false
                || ($result['native_woo_mutations']??null)!==0) return;
            $sim=$result['simulation']??[];
            $entry=$sim['evaluations'][0]??[];
            if(($entry['eligible']??false)!==true){
                self::remove_gifts($cart);
                return;
            }
            self::$busy=true;
            try {
                if($draft['type']==='free_gift') {
                    $giftId=$draft['rule']['product_id']??null;
                    if(!is_int($giftId)||$giftId<1
                        || (int)($sim['qualified_gifts'][0]['gift_product_id']??0)!==$giftId) {
                        self::remove_gifts($cart);return;
                    }
                    $product=wc_get_product($giftId);
                    if(!$product || !$product->managing_stock() || !$product->is_in_stock()
                        || (int)$product->get_stock_quantity()<1) {
                        self::remove_gifts($cart);return;
                    }
                    foreach($cart->get_cart() as $line){
                        if((int)($line['product_id']??0)===$giftId) return;
                    }
                    if($cart->add_to_cart($giftId,1,0,[],[self::GIFT_MARKER=>true])) {
                        $cart->calculate_totals();
                    }
                    return;
                }
                self::remove_gifts($cart);
                $discount=$sim['projected_discount_usd_minor']??null;
                if(!is_int($discount)||$discount<1||$discount>1000000)return;
                $session->set(self::SESSION_DISCOUNT,$discount);
                if($cart->apply_coupon(self::code($draft)))$cart->calculate_totals();
            } finally {self::$busy=false;}
        } catch(Throwable $e) {
            self::clean_automatic($cart);
            self::remove_gifts($cart);
            error_log('AICC_MARKETING_P2_SANDBOX_REJECT '.get_class($e));
        }
    }
}
