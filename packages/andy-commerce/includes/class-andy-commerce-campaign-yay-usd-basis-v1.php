<?php
/**
 * Read-only USD promotion eligibility under YayCurrency (Dev proof adapter).
 *
 * Does not hook Woo checkout, payment, refunds, or change a cart/order.
 * A display-currency conversion is NEVER an entitlement decision.
 *
 * V1 deliberately supports a provable subset: no coupon, or exactly one
 * unrestricted native fixed_cart coupon. Percent/fixed_product/stacked/
 * external discounts remain fail-closed until their native allocations are
 * independently verified and given a separate adapter contract.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Campaign_Yay_USD_Basis_V1 {
    private const CURRENCIES = ['USD','EUR','GBP','AUD','CAD'];
    private const GIFT_THRESHOLD_MINOR = 19900;

    private static function fail(string $reason): array {
        return ['ok'=>false,'reason'=>$reason,'basis_minor'=>null,'currency'=>null];
    }

    private static function minor($value): ?int {
        if (!is_scalar($value)) return null;
        $s=(string)$value;
        if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,8})?$/D',$s)) return null;
        [$w,$f]=array_pad(explode('.',$s,2),2,'');
        $f=str_pad($f,3,'0');
        return (int)$w*100+(int)substr($f,0,2)+((int)$f[2]>=5?1:0);
    }

    /** This is a UI decision only after source USD evidence has been verified. */
    public static function gift_slots_from_usd_minor(int $sourceNet): int {
        if($sourceNet<0 || $sourceNet>100000000) throw new InvalidArgumentException('invalid_usd_basis');
        return $sourceNet===0 ? 0 : ($sourceNet>=self::GIFT_THRESHOLD_MINOR ? 2 : 1);
    }

    private static function coupon_source(string $code): ?int {
        if(!class_exists('WC_Coupon')) return null;
        try {
            $c=new WC_Coupon($code);
            if(!$c->get_id()
              || $c->get_discount_type('edit')!=='fixed_cart'
              || $c->get_product_ids('edit')
              || $c->get_excluded_product_ids('edit')
              || $c->get_product_categories('edit')
              || $c->get_excluded_product_categories('edit')
              || $c->get_exclude_sale_items('edit')
              || null!==$c->get_limit_usage_to_x_items('edit')) return null;
            return self::minor($c->get_amount('edit'));
        } catch (Throwable $e) { return null; }
    }

    /**
     * Only an official YayCurrency price converter may validate a native
     * Woo line's shopper amount. Never derive USD basis from an inverse FX
     * conversion of cart display amounts, which already include rounding.
     */
    private static function local_unit(int $usdMinor,string $currency): ?int {
        if ($currency==='USD') return $usdMinor;
        if (!function_exists('has_filter') || has_filter('yay_currency_convert_price')===false
            || !function_exists('apply_filters')) return null;
        try {
            $price=number_format($usdMinor/100,2,'.','');
            $converted=apply_filters('yay_currency_convert_price',$price);
            return self::minor($converted);
        } catch (Throwable $e) {return null;}
    }

    public static function quote($cart): array {
        if(!class_exists('WC_Cart') || !($cart instanceof WC_Cart)
            || !function_exists('get_option')
            || get_option('woocommerce_currency','')!=='USD'
            || !function_exists('get_woocommerce_currency')
            || !function_exists('wc_get_price_decimals')
            || wc_get_price_decimals()!==2
            || get_option('woocommerce_prices_include_tax','no')!=='no')
            return self::fail('woo_base_or_tax_not_supported');
        $currency=get_woocommerce_currency();
        if(!in_array($currency,self::CURRENCIES,true))
            return self::fail('shopper_currency_not_supported');
        // The old price converter must be OFF even if its old session cookie exists.
        if(class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
            && Andy_Commerce_Currency_Runtime_Dev_V1::allowed())
            return self::fail('dual_currency_engine');
        if(!function_exists('is_plugin_active') && defined('ABSPATH')){
            $path=ABSPATH.'wp-admin/includes/plugin.php';
            if(is_file($path)) require_once $path;
        }
        $yay=false;
        if(function_exists('is_plugin_active')) {
            foreach(['yaycurrency/yaycurrency.php','yaycurrency-pro/yaycurrency.php',
                     'yaycurrency-premium/yaycurrency.php','yaycurrency-pro/yaycurrency-pro.php'] as $slug){
                if(is_plugin_active($slug)){$yay=true;break;}
            }
        }
        if(!$yay)return self::fail('yaycurrency_not_active');

        $coupons=$cart->get_applied_coupons();
        if(!is_array($coupons)||count($coupons)>1)
            return self::fail('coupon_stack_not_verified');
        $couponUsd=null;
        if(count($coupons)===1){
            $couponUsd=self::coupon_source((string)$coupons[0]);
            if($couponUsd===null) return self::fail('coupon_type_or_restrictions_not_verified');
        }

        $basis=0;
        $localMerchBefore=0;
        $localMerchAfter=0;
        $lines=0;
        $units=0;
        foreach($cart->get_cart() as $line){
            if(!is_array($line))return self::fail('line_missing');
            $gift=($line['_byl_bf2026_gift']??null)===true;
            $upgrade=($line['_byl_bf2026_upgrade']??null)===true;
            $campaignLine=array_key_exists('andy_campaign_pack_id',$line)
                || array_key_exists('_byl_bf2026_gift',$line)
                || array_key_exists('_byl_bf2026_upgrade',$line);
            if($campaignLine) {
                if(($line['andy_campaign_pack_id']??null)!=='byl-black-friday-2026'
                   || (!$gift && !$upgrade)
                   || ($gift && $upgrade))
                    return self::fail('unknown_promotion_line');
                if($couponUsd!==null &&
                    self::minor($line['line_subtotal']??null)!==self::minor($line['line_total']??null))
                    return self::fail('coupon_applied_to_gift_or_upgrade');
                continue;
            }
            $p=$line['data']??null;
            $q=$line['quantity']??null;
            if(!($p instanceof WC_Product)||!is_int($q)||$q<1||$q>100)
                return self::fail('product_or_qty_unverified');
            $usdUnit=self::minor($p->get_price('edit'));
            $localUnit=$usdUnit!==null?self::local_unit($usdUnit,$currency):null;
            $localBefore=self::minor($line['line_subtotal']??null);
            $localAfter=self::minor($line['line_total']??null);
            if($usdUnit===null||$localUnit===null||$localBefore===null
               ||$localAfter===null||$localAfter>$localBefore)
                return self::fail('native_price_unverified');
            // Exact Woo item rounding is part of the supported adapter contract.
            // Fixed per-currency prices or extra price filters require separate proof.
            if($localBefore!==$localUnit*$q)
                return self::fail('native_yay_price_mismatch');
            $basis+=$usdUnit*$q;
            $localMerchBefore+=$localBefore;
            $localMerchAfter+=$localAfter;
            $units+=$q;
            if(++$lines>200||$basis>100000000||$units>500)
                return self::fail('cart_limits');
        }
        if($lines<1)return self::fail('empty_eligible_merchandise');

        if($couponUsd!==null) {
            $sourceDiscount=min($basis,$couponUsd);
            $expectedLocal=self::local_unit($sourceDiscount,$currency);
            if($expectedLocal===null) return self::fail('coupon_conversion_unverified');
            $actualLocal=$localMerchBefore-$localMerchAfter;
            if(!method_exists($cart,'get_coupon_discount_amount'))
                return self::fail('coupon_api_missing');
            $wooCoupon=self::minor($cart->get_coupon_discount_amount((string)$coupons[0],true));
            if($wooCoupon===null||$wooCoupon!==$actualLocal
               ||abs($actualLocal-min($localMerchBefore,$expectedLocal))>$units)
                return self::fail('coupon_allocation_unverified');
            $basis-=$sourceDiscount;
        } elseif($localMerchBefore!==$localMerchAfter) {
            return self::fail('unattributed_native_discount');
        }

        return [
            'ok'=>true, 'reason'=>'verified_native_woo_usd_source',
            'basis_minor'=>$basis,'currency'=>$currency,
            'gift_slots'=>self::gift_slots_from_usd_minor($basis),
            'coupon_count'=>count($coupons),'merchandise_lines'=>$lines,
            'usd_source'=>'wc_product_edit_price_and_verified_native_coupon',
            'display_currency_never_grants_entitlement'=>true,
            'payment_or_cart_changed'=>false,
        ];
    }
}
