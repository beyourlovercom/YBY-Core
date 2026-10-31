<?php
/**
 * R13 P0: source-USD / Woo native display ledger contract.
 *
 * Inputs MUST originate from trusted Woo server-side product, coupon,
 * shipping-zone and tax calculation data; no client-provided USD estimates.
 * This pure validator NEVER writes an order, refunds, FX or payment.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Currency_R13_Dual_Ledger {
    private const COMPONENTS=['merchandise','discount','shipping','tax'];
    private static function minor($value): int {
        if(!is_int($value) || $value<0 || $value>1000000000)
            throw new InvalidArgumentException('R13 invalid monetary minor unit');
        return $value;
    }
    public static function reconcile(array $input): array {
        $currency=$input['currency']??null;
        $rate=$input['rate_micros']??null;
        $source=$input['rate_source']??null;
        $time=$input['rate_timestamp']??null;
        $qty=$input['unit_count']??null;
        $couponCount=$input['coupon_count']??null;
        $taxInclusive=$input['tax_inclusive']??null;
        if(!is_string($currency)
            || !in_array($currency,Andy_Commerce_Currency_OS_V1::SUPPORTED,true)
            || !is_int($rate)||$rate<100000||$rate>10000000
            || ($currency==='USD'&&$rate!==1000000)
            || !is_string($source)||!preg_match('/^[a-zA-Z0-9._:-]{3,64}$/D',$source)
            || !is_int($time)||$time<1
            || !is_int($qty)||$qty<1||$qty>100
            || !is_int($couponCount)||$couponCount<0||$couponCount>3
            || !is_bool($taxInclusive))
            throw new InvalidArgumentException('R13 missing FX source or unsupported promotion');
        $raw=$input['usd']??null;
        $local=$input['woo_local']??null;
        if(!is_array($raw)||!is_array($local))throw new InvalidArgumentException('R13 missing native components');
        $components=[];
        foreach(self::COMPONENTS as $k) {
            $usd=self::minor($raw[$k]??null);
            $actual=self::minor($local[$k]??null);
            $expected=Andy_Commerce_Currency_OS_V1::convert_usd_minor($usd,$rate);
            // Native Woo rounds per product, tax line and shipping rate.
            // Never silently normalize a larger unexplained discrepancy.
            $tolerance=$k==='merchandise'? $qty :
                ($k==='tax'? $qty+3 : ($k==='shipping'?2:($couponCount?$couponCount*2+1:0)));
            if(abs($actual-$expected)>$tolerance)
                throw new InvalidArgumentException('R13 '.$k.' FX source/actual divergence');
            $components[$k]=[
                'usd_minor'=>$usd,'native_local_minor'=>$actual,
                'theoretical_local_minor'=>$expected,
                'rounding_local_minor'=>$actual-$expected,
            ];
        }
        if($raw['discount']>$raw['merchandise'] || $local['discount']>$local['merchandise'])
            throw new InvalidArgumentException('R13 discount exceeds merchandise');
        // Woo Order subtotal and shipping are net even when catalogue prices
        // include tax; Woo always adds its tax lines to the invoice total.
        $usdTotal=$raw['merchandise']-$raw['discount']+$raw['shipping']+$raw['tax'];
        $localTotal=$local['merchandise']-$local['discount']+$local['shipping']+$local['tax'];
        if($usdTotal<0||$usdTotal>1000000000
            || $localTotal!==self::minor($input['woo_order_total_minor']??null))
            throw new InvalidArgumentException('R13 Woo order total is not authoritative');
        $theory=Andy_Commerce_Currency_OS_V1::convert_usd_minor($usdTotal,$rate);
        $delta=$localTotal-$theory;
        if(abs($delta)>$qty+4)
            throw new InvalidArgumentException('R13 unexplained aggregate FX rounding');
        return [
            'mode'=>'CURRENCY_R13_TRUSTED_SERVER_DUAL_LEDGER',
            'source_currency'=>'USD','order_currency'=>$currency,
            'usd_total_minor'=>$usdTotal,'native_woo_total_minor'=>$localTotal,
            'fx_source'=>$source,'rate_micros'=>$rate,'rate_timestamp'=>$time,
            'components'=>$components,'total_rounding_local_minor'=>$delta,
            'coupon_count'=>$couponCount,'unit_count'=>$qty,
            'tax_inclusive'=>$taxInclusive,
            'payment_executed'=>false,'provider_settlement_verified'=>false,
        ];
    }
}
