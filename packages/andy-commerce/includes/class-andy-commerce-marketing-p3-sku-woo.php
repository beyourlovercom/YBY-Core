<?php
/**
 * M-P3: Native Woo SKU attribution using the existing M15 cents allocator.
 * Sandbox-only, opt-in, OFF if this file is merely loaded. Not a replacement
 * for M10/M12 rule evaluation and never mutates shipping or Currency OS.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/class-andy-commerce-marketing-p1-sandbox-contract.php';
require_once __DIR__.'/class-andy-commerce-m11-wc-cart-reader.php';
require_once __DIR__.'/class-andy-commerce-m15-discount-allocation.php';

final class Andy_Commerce_Marketing_P3_SKU_Woo {
    private const SWITCH_OPTION='andy_commerce_marketing_p3_sku_enabled';
    private const SESSION_PLAN='andy_commerce_p3_sku_plan';
    private const COUPON_PREFIX='aicc-p2-';
    private static bool $hooks=false;

    public static function active(): bool {
        return Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated()
            && function_exists('get_option')
            && get_option(self::SWITCH_OPTION,'no')==='yes'
            && Andy_Commerce_Marketing_P1_Sandbox_Contract::current()!==null;
    }

    public static function boot(): void {
        if(self::$hooks || !class_exists('WooCommerce')
            || !Andy_Commerce_Marketing_P1_Sandbox_Contract::disposable_origin()) return;
        self::$hooks=true;
        add_filter('woocommerce_get_shop_coupon_data',[self::class,'coupon_data'],20,3);
        add_filter('woocommerce_coupon_get_discount_amount',[self::class,'line_amount'],90,5);
    }

    private static function session() {
        return function_exists('WC') && WC() ? WC()->session : null;
    }

    public static function clear(): void {
        $session=self::session();
        if($session)$session->set(self::SESSION_PLAN,null);
    }

    /**
     * Must be invoked only with CLEAN native Woo totals before P2 applies a
     * virtual coupon. All SKU business flags are caller-verified via M11.
     * Only fixed/percentage spend-save, tier, BOGO and Bundle use this path.
     */
    public static function prepare(WC_Cart $cart,array $flags,array $draft,int $minor): bool {
        self::clear();
        if(!self::active() || $minor<1 || $minor>1000000
            || !in_array($draft['type']??null,['spend_save','bogo','bundles','tiered'],true)
            || (($approved=Andy_Commerce_Marketing_P1_Sandbox_Contract::current())===null)
            || $approved!==$draft) return false;
        try {
            $read=Andy_Commerce_M11_WC_Cart_Reader::read($cart,$flags,4900);
            if(($read['applied_native_coupon_codes']??[])!==[])return false;
            $eligible=array_values(array_filter($read['catalog_lines'],
                static fn(array $line):bool=>$line['eligible_for_promotion']===true));
            $plan=Andy_Commerce_M15_Discount_Allocation::plan(
                $draft['type'],$draft['rule'],$eligible,$minor);
            if($plan['allocated_discount_usd_minor']!==$minor
                || $plan['checkout_mutations']!==0 || !$plan['sku_lines'])return false;
            $items=[];
            $sum=0;
            foreach($plan['sku_lines'] as $line) {
                $id=$line['product_id'];
                if(!is_int($id)||$id<1||isset($items[$id]))return false;
                $qty=$line['quantity'];
                $cents=$line['discount_usd_minor'];
                if(!is_int($qty)||$qty<1||!is_int($cents)||$cents<0
                    ||array_sum($line['unit_discounts_usd_minor'])!==$cents) return false;
                $items[$id]=['amount_minor'=>$cents,'quantity'=>$qty,
                    'unit_discounts_usd_minor'=>$line['unit_discounts_usd_minor']];
                $sum+=$cents;
            }
            if($sum!==$minor || !$items)return false;
            $fingerprint=hash('sha256',json_encode($draft,JSON_THROW_ON_ERROR));
            $session=self::session();
            if(!$session)return false;
            $session->set(self::SESSION_PLAN,[
                'campaign'=>$draft['id'],'revision'=>$draft['revision'],
                'fingerprint'=>$fingerprint,'discount_minor'=>$minor,
                'lines'=>$items,
            ]);
            return true;
        } catch(Throwable $e){
            self::clear();
            return false;
        }
    }

    public static function plan(): ?array {
        if(!self::active())return null;
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        $session=self::session();
        $plan=$session ? $session->get(self::SESSION_PLAN) : null;
        if(!is_array($plan)||!is_array($plan['lines']??null)||!$plan['lines']
            ||!is_int($plan['discount_minor']??null)||$plan['discount_minor']<1
            ||($plan['campaign']??null)!==$draft['id']
            ||($plan['revision']??null)!==$draft['revision'])return null;
        try{
            if(!hash_equals(hash('sha256',json_encode($draft,JSON_THROW_ON_ERROR)),
                (string)($plan['fingerprint']??'')))return null;
        }catch(Throwable $e){return null;}
        return $plan;
    }

    /** Woo reads this after P2's virtual coupon data, before validation. */
    public static function coupon_data($data,$code,$coupon) {
        if(!is_string($code)||strncmp($code,self::COUPON_PREFIX,strlen(self::COUPON_PREFIX))!==0)
            return $data;
        $plan=self::plan();
        if(!$plan || !is_array($data)
            || $code!==self::COUPON_PREFIX.$plan['campaign'])return $data;
        $ids=array_map('intval',array_keys($plan['lines']));
        return array_merge($data,[
            'discount_type'=>'fixed_product','amount'=>'0.01',
            'product_ids'=>$ids,
            'individual_use'=>true,
        ]);
    }

    /**
     * Woo 11.2.1 invokes this after deriving one cart line's coupon amount.
     * The proven M15 allocation is per WHOLE line (not per unit). Unknown
     * lines are zero rather than receiving an unrelated proportional discount.
     */
    public static function line_amount($amount,$discountable,$line,$single,$coupon) {
        if(!($coupon instanceof WC_Coupon))return $amount;
        $code=$coupon->get_code();
        if(!is_string($code)||strncmp($code,self::COUPON_PREFIX,strlen(self::COUPON_PREFIX))!==0)
            return $amount;
        $plan=self::plan();
        if(!$plan || $code!==self::COUPON_PREFIX.$plan['campaign'])return $amount;
        if($single!==false || !is_array($line) || !is_int($line['product_id']??null)
            || !is_int($line['quantity']??null)) return 0.0;
        $attribution=$plan['lines'][$line['product_id']]??null;
        if(!$attribution || $attribution['quantity']!==$line['quantity'])return 0.0;
        $minor=$attribution['amount_minor'];
        if(!is_int($minor)||$minor<0 || !is_numeric($discountable)
            || $minor/100 >(float)$discountable+0.00001)return 0.0;
        return $minor/100;
    }
}
