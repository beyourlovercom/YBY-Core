<?php
/**
 * R6 Commerce -> Campaign trusted USD merchandise basis (read-only Dev contract).
 *
 * No Woo hooks, orders, coupons, payments, writes or browser-supplied amounts.
 * This intentionally supports only a provable subset of foreign carts.
 * A non-USD coupon / pre-tax pricing ambiguity is a hard NO-GO, not an FX guess.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_Campaign_USD_Basis_V1 {
    private static function fail(string $reason): array {
        return ['ok'=>false,'reason'=>$reason,'basis_minor'=>0,'currency'=>null];
    }

    /** Strict decimal cents. Never pass floats derived from external FX into this helper. */
    private static function minor($value): ?int {
        if (!is_scalar($value)) return null;
        $s=(string)$value;
        if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',$s)) return null;
        [$whole,$dec]=array_pad(explode('.',$s,2),2,'');
        return (int)$whole*100+(int)str_pad($dec,2,'0');
    }

    /** Quoted value is meaningful only for the *current* post-calculate Woo cart. */
    public static function quote($cart): array {
        if (!class_exists('WC_Cart') || !($cart instanceof WC_Cart)
            || !class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
            || !Andy_Commerce_Currency_Runtime_Dev_V1::site()
            || !function_exists('get_woocommerce_currency')
            || !function_exists('wc_get_price_decimals')
            || wc_get_price_decimals()!==2) return self::fail('runtime_not_supported');

        $currency=get_woocommerce_currency();
        if (!in_array($currency,Andy_Commerce_Currency_Runtime_Dev_V1::CODES,true))
            return self::fail('unsupported_currency');

        $rates=Andy_Commerce_Currency_Runtime_Dev_V1::rates();
        if (!is_array($rates) || !isset($rates['rate_micros'][$currency]))
            return self::fail('fx_not_verified');
        $rate=$rates['rate_micros'][$currency];
        if (!is_int($rate) || $rate<100000 || $rate>10000000)
            return self::fail('invalid_rate');
        if (!Andy_Commerce_Currency_Runtime_Dev_V1::allowed()
            || Andy_Commerce_Currency_Runtime_Dev_V1::selected()!==$currency)
            return self::fail('foreign_runtime_not_active');

        // Store may be configured to enter prices inclusive of tax: raw product
        // edit prices then include VAT and cannot be used as net threshold.
        if ($currency!=='USD'
            && function_exists('get_option')
            && get_option('woocommerce_prices_include_tax','no')!=='no')
            return self::fail('tax_inclusive_unverified');

        $coupons=$cart->get_applied_coupons();
        if (!is_array($coupons)) return self::fail('coupon_state_invalid');
        if ($currency!=='USD' && count($coupons)>0)
            return self::fail('foreign_coupon_unverified');

        $base=0;
        $items=0;
        foreach ($cart->get_cart() as $line) {
            if (!is_array($line))return self::fail('cart_line_invalid');
            if (isset($line['andy_campaign_pack_id']) ||
                isset($line['_byl_bf2026_gift'])) {
                // Never count promotional gifts as qualifying merchandise.
                if (($line['andy_campaign_pack_id']??null)!=='byl-black-friday-2026'
                    || ($line['_byl_bf2026_gift']??null)!==true)
                    return self::fail('unknown_campaign_line');
                continue;
            }
            $qty=$line['quantity']??null;
            if (!is_int($qty) || $qty<1 || $qty>1000)
                return self::fail('quantity_invalid');
            $net=self::minor($line['line_total']??null);
            if ($net===null)return self::fail('line_total_invalid');
            if ($currency==='USD') {
                $amount=$net;
            } else {
                $product=$line['data']??null;
                if (!($product instanceof WC_Product))return self::fail('product_missing');
                // Woo 'edit' context is the unfiltered USD sale price of this
                // server-side cart object. Validate it against local line total.
                $unit_usd=self::minor($product->get_price('edit'));
                if ($unit_usd===null)return self::fail('source_usd_invalid');
                $local_unit=intdiv($unit_usd*$rate+500000,1000000);
                if (abs($net-$local_unit*$qty)>1)
                    return self::fail('local_price_mismatch');
                $amount=$unit_usd*$qty;
            }
            $base+=$amount;
            if ($base>100000000 || ++$items>200)
                return self::fail('cart_limits');
        }
        return [
            'ok'=>true,'reason'=>'verified','basis_minor'=>$base,
            'currency'=>$currency,
            'provenance'=>$currency==='USD'?'woo_coupon_allocated_net':'woo_unfiltered_sale_price_and_local_line_verified',
            'fx_rate_micros'=>$rate,
            'fx_snapshot_hash'=>(string)($rates['snapshot_hash']??''),
            'coupon_count'=>count($coupons),
            'merchandise_lines'=>$items
        ];
    }
}
