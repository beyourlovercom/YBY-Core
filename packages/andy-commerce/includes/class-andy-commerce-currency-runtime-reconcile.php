<?php
/**
 * C-R3: auditable USD-to-display-currency rounding reconciliation, pure data.
 *
 * The Woo unit price is rounded before quantity multiplication. For example,
 * USD 12.50 * 2 at CAD rate 1.35 => CAD 16.88 * 2 = 33.76, whereas converting
 * the extended USD 25.00 subtotal yields CAD 33.75. The one-cent adjustment
 * must be explicit, not silently applied to USD prices or hidden in FX.
 *
 * This is NOT a gateway settlement or historical order recording service.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Currency_Runtime_Reconcile {
    public static function cart_preview(array $input): array {
        foreach (['base_usd_minor','native_cart_local_minor','rate_micros','cart_line_count','timestamp'] as $key) {
            if (!is_int($input[$key] ?? null)) {
                throw new InvalidArgumentException('Missing integer currency snapshot field: '.$key);
            }
        }
        $base=$input['base_usd_minor'];
        $native=$input['native_cart_local_minor'];
        $lines=$input['cart_line_count'];
        $code=$input['currency']??null;
        $source=$input['source']??null;
        if (!is_string($code) || !in_array($code, Andy_Commerce_Currency_OS_V1::SUPPORTED,true)
            || !is_string($source) || !preg_match('/^[A-Za-z0-9._:-]{3,64}$/D',$source)
            || $input['timestamp']<=0 || $base<0 || $native<0
            || $base>Andy_Commerce_Currency_OS_V1::MAX_USD_MINOR
            || $native>Andy_Commerce_Currency_OS_V1::MAX_USD_MINOR
            || $lines<1 || $lines>100
            || ($code==='USD' && $input['rate_micros']!==1000000)) {
            throw new InvalidArgumentException('Invalid currency cart reconciliation snapshot');
        }
        $theory=Andy_Commerce_Currency_OS_V1::convert_usd_minor($base,$input['rate_micros']);
        $rounding=$native-$theory;
        // A larger mismatch cannot be explained by ordinary per-line rounding.
        if (abs($rounding)>$lines) {
            throw new InvalidArgumentException('Excessive Woo rounding delta; fail closed');
        }
        return [
            'mode'=>'CURRENCY_RUNTIME_SANDBOX_RECONCILE',
            'base_currency'=>'USD',
            'display_currency'=>$code,
            'usd_subtotal_minor'=>$base,
            'theoretical_display_minor'=>$theory,
            'native_woo_display_minor'=>$native,
            'rounding_adjustment_display_minor'=>$rounding,
            'fx_snapshot'=>[
                'rate_micros'=>$input['rate_micros'],
                'source'=>$source,
                'timestamp'=>$input['timestamp'],
            ],
            'payment_executed'=>false,
            'order_saved'=>false,
        ];
    }
}
