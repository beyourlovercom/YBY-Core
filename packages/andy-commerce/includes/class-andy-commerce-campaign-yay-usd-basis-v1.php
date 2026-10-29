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
require_once __DIR__ . '/class-andy-commerce-campaign-source-usd-discounts-v1.php';

final class Andy_Commerce_Campaign_Yay_USD_Basis_V1 {
    private const CURRENCIES = ['USD','EUR','GBP','AUD','CAD'];
    private const GIFT_THRESHOLD_MINOR = 19900;

    /**
     * USD-only threshold comparator for gifts, spend-and-save, tiered offers
     * and per-zone shipping progress. DOES NOT grant a Woo shipping method.
     * Values are exact integer USD cents; display currency is irrelevant.
     */
    public static function threshold(array $quote,int $requiredUsdMinor): array {
        $net=$quote['basis_minor']??null;
        if(($quote['ok']??false)!==true || !is_int($net)
            || $net<0 || $net>100000000
            || $requiredUsdMinor<1 || $requiredUsdMinor>100000000)
            return ['ok'=>false,'reason'=>'unverified_usd_basis'];
        return [
            'ok'=>true,'currency'=>'USD',
            'threshold_minor'=>$requiredUsdMinor,
            'eligible'=>$net >= $requiredUsdMinor,
            'remaining_minor'=>max(0,$requiredUsdMinor-$net),
            'source_only'=>true,
        ];
    }

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
        // Owner policy: only licensed YayCurrency PRO may authorize currency-
        // independent campaign entitlements. The Free build is not a fallback.
        // Installation alone is NOT license verification: the Pro license and
        // five enabled checkout currencies must also pass admin UAT.
        if(!function_exists('is_plugin_active'))
            return self::fail('yaycurrency_pro_required');
        if(is_plugin_active('yaycurrency/yaycurrency.php')
           || is_plugin_active('yaycurrency/yay-currency.php'))
            return self::fail('yaycurrency_free_conflict');
        $pro=false;
        foreach([
            'yaycurrency-pro/yay-currency.php',
            'yaycurrency-pro/yaycurrency.php',
            'yaycurrency-pro/yaycurrency-pro.php',
            'yaycurrency-premium/yaycurrency.php'
        ] as $slug){
            if(is_plugin_active($slug)){$pro=true;break;}
        }
        if(!$pro)return self::fail('yaycurrency_pro_required');

        $coupons=$cart->get_applied_coupons();
        if(!is_array($coupons)||count($coupons)>1)
            return self::fail('coupon_stack_not_verified');

        $lines=0;
        $units=0;
        $sourceLines=[];
        $actualLines=[];
        foreach($cart->get_cart() as $key=>$line){
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
                if(count($coupons)>0 &&
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
            // Never trust a browser-supplied USD amount or reconstruct USD
            // by inverting a rounded storefront total.
            $sourceLines[(string)$key]=[
                'product'=>$p, 'quantity'=>$q,
                'usd_unit_minor'=>$usdUnit, 'cart_item'=>$line,
            ];
            $actualLines[(string)$key]=[
                'pre_minor'=>$localBefore,'net_minor'=>$localAfter,'quantity'=>$q,
            ];
            $units+=$q;
            if(++$lines>200||$units>500)
                return self::fail('cart_limits');
        }
        if($lines<1)return self::fail('empty_eligible_merchandise');

        $source=Andy_Commerce_Campaign_Source_USD_Discounts_V1::replay($sourceLines,$coupons);
        if(!($source['ok']??false))
            return self::fail('source_'.($source['reason']??'unverified'));
        // The current local Woo cart is the authority for amounts actually
        // owed. Compare its final line allocation to the same USD source
        // after applying Woo's native coupon replay in memory, not to an
        // independently guessed discount or reference FX snapshot.
        foreach($actualLines as $key=>$actual){
            $usdNet=$source['by_item_minor'][$key]['net_minor']??null;
            if(!is_int($usdNet))return self::fail('source_line_not_found');
            $localExpected=self::local_unit($usdNet,$currency);
            if($localExpected===null)return self::fail('source_to_local_unverified');
            // Woo rounds discounts per item/quantity, whereas currency
            // conversion rounds each display unit. Bound that known
            // per-item difference; never treat a large difference as PASS.
            $tolerance=min(10,2*$actual['quantity']+1);
            if(abs($localExpected-$actual['net_minor'])>$tolerance)
                return self::fail('source_native_local_net_mismatch');
        }
        $basis=$source['basis_minor'];
        if(!is_int($basis)||$basis<0)return self::fail('source_usd_invalid');

        return [
            'ok'=>true, 'reason'=>'verified_native_woo_usd_source',
            'basis_minor'=>$basis,'currency'=>$currency,
            'gift_slots'=>self::gift_slots_from_usd_minor($basis),
            'coupon_count'=>count($coupons),'merchandise_lines'=>$lines,
            'source_discount_minor'=>$source['discount_minor'],
            'source_subtotal_minor'=>$source['subtotal_minor'],
            'usd_source'=>'wc_product_edit_price_and_native_wc_discounts_replay',
            'display_currency_never_grants_entitlement'=>true,
            'payment_or_cart_changed'=>false,
        ];
    }
}
