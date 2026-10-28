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

    /**
     * Audited *single* native fixed-cart coupon on one merchandise line.
     *
     * All figures are read from the current Woo cart and persisted Woo coupon.
     * This is intentionally NOT a general coupon calculator: any mismatch,
     * extra line, limits/restrictions or extra promotions refuses entitlement.
     */
    private static function single_fixed_cart_coupon_usd(
        $cart, string $code, array $line, int $usd_pre, int $local_pre,
        int $local_net, int $rate
    ): array {
        if (!class_exists('WC_Coupon') || !method_exists($cart,'get_coupon_discount_amount'))
            return ['ok'=>false,'reason'=>'coupon_api_missing'];
        try {
            $coupon=new WC_Coupon($code);
            if (!$coupon->get_id() || $coupon->get_discount_type('edit')!=='fixed_cart')
                return ['ok'=>false,'reason'=>'foreign_coupon_type_unverified'];
            if (!empty($coupon->get_product_ids('edit'))
                || !empty($coupon->get_excluded_product_ids('edit'))
                || !empty($coupon->get_product_categories('edit'))
                || !empty($coupon->get_excluded_product_categories('edit'))
                || $coupon->get_exclude_sale_items('edit')
                || null!==$coupon->get_limit_usage_to_x_items('edit'))
                return ['ok'=>false,'reason'=>'foreign_coupon_restrictions_unverified'];
            $usd_discount=self::minor($coupon->get_amount('edit'));
            $native_discount=self::minor($cart->get_coupon_discount_amount($code,true));
        } catch (\\Throwable $e) {
            return ['ok'=>false,'reason'=>'coupon_read_failed'];
        }
        if ($usd_discount===null || $usd_discount<1 || $native_discount===null)
            return ['ok'=>false,'reason'=>'coupon_amount_invalid'];
        // Require Woo's actual pre-discount subtotal and post-discount line.
        $native_pre=self::minor($line['line_subtotal']??null);
        if ($native_pre===null || $native_pre!==$local_pre)
            return ['ok'=>false,'reason'=>'coupon_subtotal_mismatch'];
        $local_discount=intdiv($usd_discount*$rate+500000,1000000);
        $expected_local_discount=min($local_pre,$local_discount);
        if ($native_discount!==$expected_local_discount
            || $local_net!==$local_pre-$native_discount)
            return ['ok'=>false,'reason'=>'coupon_native_allocation_mismatch'];
        return ['ok'=>true,'usd_discount_minor'=>min($usd_pre,$usd_discount)];
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
        // Only one native fixed-cart coupon and exactly one merchandise unit
        // is eligible for the strict foreign-coupon bridge. All other combinations
        // remain fail-closed to avoid guessing Woo line discount allocation.
        $foreign_coupon=$currency!=='USD' && count($coupons)>0;
        if ($foreign_coupon && count($coupons)!==1)
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
                if ($foreign_coupon &&
                    (self::minor($line['line_subtotal']??null)===null ||
                     self::minor($line['line_subtotal'])!==self::minor($line['line_total']??null)))
                    return self::fail('coupon_discounted_gift');
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
                if ($foreign_coupon) {
                    if ($qty!==1 || $items>0)
                        return self::fail('foreign_coupon_cart_shape_unverified');
                    $coupon_quote=self::single_fixed_cart_coupon_usd(
                        $cart, (string)$coupons[0], $line, $unit_usd,
                        $local_unit, $net, $rate
                    );
                    if (!$coupon_quote['ok'])return self::fail($coupon_quote['reason']);
                    $amount=$unit_usd-$coupon_quote['usd_discount_minor'];
                } else {
                    if (abs($net-$local_unit*$qty)>1)
                        return self::fail('local_price_mismatch');
                    $amount=$unit_usd*$qty;
                }
            }
            $base+=$amount;
            if ($base>100000000 || ++$items>200)
                return self::fail('cart_limits');
        }
        if ($foreign_coupon && $items!==1)
            return self::fail('foreign_coupon_cart_shape_unverified');
        return [
            'ok'=>true,'reason'=>'verified','basis_minor'=>$base,
            'currency'=>$currency,
            'provenance'=>$currency==='USD'?'woo_coupon_allocated_net':
                ($foreign_coupon?'woo_one_line_fixed_cart_source_usd_and_native_coupon_verified':
                'woo_unfiltered_sale_price_and_local_line_verified'),
            'fx_rate_micros'=>$rate,
            'fx_snapshot_hash'=>(string)($rates['snapshot_hash']??''),
            'coupon_count'=>count($coupons),
            'merchandise_lines'=>$items
        ];
    }
}
