<?php
/**
 * P6-E: pure read-only TEST provider balance-transaction audit.
 * Three independent ledgers: merchant USD source, Woo shopper currency,
 * Stripe TEST balance currency. Never treats balance as a verified payout.
 * No hooks, provider API actions, Woo updates or live settlement claims.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_R13_Settlement_Audit {
    private static function minor($v): int {
        if(!is_int($v) || $v<0 || $v>1000000000)throw new InvalidArgumentException('Nonnegative minor units required');
        return $v;
    }
    private static function validate_balance(array $b,bool $refund): array {
        $currency=$b['currency']??null;
        $amount=$b['amount']??null;
        $fee=$b['fee']??null;
        $net=$b['net']??null;
        $rate=$b['exchange_rate']??null;
        if(!is_string($currency)||!preg_match('/^[a-z]{3}$/D',$currency)
            || !is_int($amount)||!is_int($fee)||$fee<0
            || !is_int($net)||$net!==$amount-$fee
            || ($refund ? $amount>=0 : $amount<=0)
            || (!is_float($rate)&&!is_int($rate))
            || !is_finite((float)$rate)||$rate<=0
            || !in_array(($b['status']??null),['available','pending'],true)) {
            throw new InvalidArgumentException('Unverified TEST balance transaction');
        }
        return ['currency'=>strtoupper($currency),'gross_minor'=>$amount,
            'fee_minor'=>$fee,'net_minor'=>$net,'fx_rate'=>(float)$rate,
            'status'=>$b['status'],'payout_verified'=>false];
    }
    public static function reconcile(array $order,array $charge,array $chargeBalance,array $refund,array $refundBalance): array {
        $id=$order['id']??null;
        $cur=$order['currency']??null;
        if(!is_int($id)||$id<=0||!is_string($cur)
           || !in_array($cur,['USD','EUR','GBP','AUD','CAD'],true)
           || !is_int($order['source_usd_minor']??null)
           || self::minor($order['source_usd_minor'])<=0
           || !is_string($order['locked_fx_source']??null)
           || !is_int($order['locked_fx_rate_micros']??null)
           || $order['locked_fx_rate_micros']<100000
           || $order['locked_fx_rate_micros']>10000000
           || ($charge['livemode']??null)!==false
           || ($charge['status']??null)!=='succeeded'
           || ($charge['currency']??null)!==strtolower($cur)
           || ($charge['metadata']['order_id']??null)!==(string)$id
           || !preg_match('/^ch_[A-Za-z0-9]+$/D',(string)($charge['id']??''))
           || !preg_match('/^pi_[A-Za-z0-9]+$/D',(string)($charge['payment_intent']??''))) {
           throw new InvalidArgumentException('TEST provider identity/currency/source not verified');
        }
        $charged=self::minor($charge['amount']??null);
        if($charged!==self::minor($order['woo_total_minor']??null))
            throw new InvalidArgumentException('Paid Woo amount differs from TEST charge');
        $refunded=self::minor($refund['amount']??null);
        if($refunded<1||$refunded>$charged
           || ($refund['status']??null)!=='succeeded'
           || ($refund['currency']??null)!==strtolower($cur)
           || ($refund['payment_intent']??null)!==$charge['payment_intent']
           || ($refund['charge']??null)!==$charge['id']
           || !preg_match('/^re_[A-Za-z0-9]+$/D',(string)($refund['id']??''))) {
           throw new InvalidArgumentException('TEST provider refund does not match charge');
        }
        if(($charge['amount_refunded']??null)!==$refunded)
            throw new InvalidArgumentException('Provider refund sum does not match charge');
        $captured=self::validate_balance($chargeBalance,false);
        $reversed=self::validate_balance($refundBalance,true);
        if($captured['currency']!==$reversed['currency'])
            throw new InvalidArgumentException('Provider balance currencies mismatch');
        return [
            'verification_scope'=>'STRIPE_TEST_BALANCE_READONLY',
            'order_id'=>$id,
            'source_usd_minor'=>$order['source_usd_minor'],
            'source_fx_reference'=>$order['locked_fx_source'],
            'source_locked_rate_micros'=>$order['locked_fx_rate_micros'],
            'shopper_currency'=>$cur,'shopper_charge_minor'=>$charged,
            'shopper_refund_minor'=>$refunded,
            'provider_balance_currency'=>$captured['currency'],
            'charge_balance'=>$captured,'refund_balance'=>$reversed,
            'provider_net_after_refund_minor'=>$captured['net_minor']+$reversed['net_minor'],
            'source_fx_not_settlement_fx'=>true,
            'payout_verified'=>false,
        ];
    }
}
