<?php
/**
 * R13 TEST-only post-provider refund USD reversal from a locked Woo checkout FX snapshot.
 * This module NEVER issues a charge or refund. It only GETs Stripe refund/account proof
 * and records a derived, immutable WC_Order_Refund metadata ledger in an isolated sandbox.
 * Fail closed in Dev/WWW: no test-account constant or C1 isolation => no hook.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_Currency_R13_Stripe_Refund_Ledger {
    public const META = '_aicc_currency_runtime_refund_v1';
    public static function armed(): bool {
        return class_exists('Andy_Commerce_Currency_Runtime_C1')
            && Andy_Commerce_Currency_Runtime_C1::allowed()
            && defined('AICC_CURRENCY_REFUND_TEST_ACCOUNT')
            && is_string(AICC_CURRENCY_REFUND_TEST_ACCOUNT)
            && preg_match('/^acct_[A-Za-z0-9]+$/D', AICC_CURRENCY_REFUND_TEST_ACCOUNT) === 1;
    }
    public static function boot(): void {
        if (self::armed()) add_action('woocommerce_order_refunded', [__CLASS__, 'after_woo_refund'], 50, 2);
    }
    /** An existing refund is NEVER overwritten, even if a provider event is replayed. */
    public static function after_woo_refund($orderId, $refundId): void {
        if (!self::armed()) return;
        $result=self::reconcile((int)$orderId, (int)$refundId);
        if (is_wp_error($result) && function_exists('wc_get_logger')) {
            wc_get_logger()->warning(
                'R13 TEST-only refund ledger deferred or blocked: '.$result->get_error_code(),
                ['source'=>'aicc-r13-refund-ledger','order_id'=>(int)$orderId,'refund_id'=>(int)$refundId]
            );
        }
    }
    /** Pure, deterministic calculation; values in integer minor units. No I/O. */
    public static function plan(array $original, array $prior, array $incoming): array {
        $currency=$original['currency']??null;
        $display=$original['display_minor']??null;
        $usd=$original['usd_minor']??null;
        $rate=$original['rate_micros']??null;
        $refund=$incoming['amount_minor']??null;
        $refundCurrency=$incoming['currency']??null;
        $count=$prior['display_minor']??null;
        $used=$prior['usd_minor']??null;
        $source=$original['fx_source']??null;
        if (!is_string($currency) || !in_array($currency,['USD','EUR','GBP','AUD','CAD'],true)
            || $currency!==$refundCurrency
            || !is_int($display)||$display<1||$display>1000000000
            || !is_int($usd)||$usd<1||$usd>1000000000
            || !is_int($rate)||$rate<100000||$rate>10000000
            || ($currency==='USD'&&$rate!==1000000)
            || !is_int($refund)||$refund<1
            || !is_int($count)||$count<0||$count>$display
            || !is_int($used)||$used<0||$used>$usd
            || !is_string($source)||!preg_match('/^[A-Za-z0-9._:-]{3,64}$/D',$source)
            || $count+$refund>$display) {
            throw new InvalidArgumentException('R13 refund data invalid or over-refunded');
        }
        // Partial = round each refund using original locked rate. Final = reverse
        // exact remaining USD cents, accounting for native per-unit rounding residue.
        $reversed=($count+$refund===$display)
            ? $usd-$used : intdiv($refund*1000000+intdiv($rate,2),$rate);
        if ($reversed<0||$used+$reversed>$usd)
            throw new InvalidArgumentException('R13 refund original USD overflow');
        return [
            'currency'=>$currency,'refund_display_minor'=>$refund,
            'reversed_usd_minor'=>$reversed,
            'cumulative_display_minor'=>$count+$refund,
            'cumulative_reversed_usd_minor'=>$used+$reversed,
            'original_rate_micros'=>$rate,'original_usd_minor'=>$usd,
            'original_display_minor'=>$display,'original_fx_source'=>$source,
            'no_gateway_refund'=>false,
        ];
    }
    /** Exactly two-decimal Woo currencies only, discard fractional refund line costs. */
    private static function minor($value): ?int {
        if (!is_scalar($value) || !preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D', (string)$value)) return null;
        [$whole,$frac]=array_pad(explode('.',(string)$value,2),2,'');
        return (int)$whole*100+(int)str_pad($frac,2,'0');
    }
    private static function stripe_get(string $route, string $key) {
        $response=wp_remote_get('https://api.stripe.com/v1/'.$route,[
            'timeout'=>15,'redirection'=>0,
            'headers'=>['Authorization'=>'Basic '.base64_encode($key.':')],
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response)!==200)
            return new WP_Error('r13_provider_read_failed','TEST-only provider GET failed');
        $json=json_decode(wp_remote_retrieve_body($response),true);
        return is_array($json)?$json:new WP_Error('r13_provider_bad_json','Provider result invalid');
    }
    /** Reconcile only an already-created, already-paid and provider-verified test refund. */
    public static function reconcile(int $orderId,int $refundId) {
        if (!self::armed()) return new WP_Error('r13_not_armed','Sandbox-only refund reconciliation disabled');
        $order=wc_get_order($orderId);
        $refund=wc_get_order($refundId);
        if (!($order instanceof WC_Order) || !($refund instanceof WC_Order_Refund)
            || (int)$refund->get_parent_id()!==$orderId
            || $order->get_payment_method()!=='stripe'
            // Woo sets the parent to refunded when the final gateway refund succeeds.
            || (!$order->is_paid() && $order->get_status()!=='refunded')
            || !in_array($refund->get_currency(),['USD','EUR','GBP','AUD','CAD'],true)) {
            return new WP_Error('r13_wrong_order','Refund and original order not eligible');
        }
        $locked=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
        if (!is_array($locked)
            || ($locked['display_currency']??null)!==$order->get_currency()
            || !is_array($locked['fx_snapshot']??null)
            || ($locked['checkout_capture_pending_save']??null)!==true)
            return new WP_Error('r13_no_original_fx','Original checkout FX snapshot absent');
        $original=[
            'currency'=>$order->get_currency(),
            'display_minor'=>$locked['native_woo_display_minor']??null,
            'usd_minor'=>$locked['usd_subtotal_minor']??null,
            'rate_micros'=>$locked['fx_snapshot']['rate_micros']??null,
            'fx_source'=>$locked['fx_snapshot']['source']??null,
        ];
        $secret=get_option('woocommerce_stripe_settings',[]);
        $key=is_array($secret)?($secret['test_secret_key']??''):'';
        if (!is_array($secret) || ($secret['testmode']??null)!=='yes'
            || !is_string($key) || strpos($key,'sk_test_')!==0)
            return new WP_Error('r13_not_test_key','Cannot verify provider without TEST credentials');
        $account=self::stripe_get('account',$key);
        if (is_wp_error($account) || ($account['id']??'')!==AICC_CURRENCY_REFUND_TEST_ACCOUNT)
            return new WP_Error('r13_wrong_test_account','Connected account does not match frozen TEST target');
        $providerId=(string)$refund->get_meta('_stripe_refund_id',true);
        $piId=(string)$order->get_meta('_stripe_intent_id',true);
        $chargeId=(string)$order->get_transaction_id();
        if (!preg_match('/^re_[A-Za-z0-9]+$/D',$providerId)
            || !preg_match('/^pi_[A-Za-z0-9]+$/D',$piId)
            || !preg_match('/^ch_[A-Za-z0-9]+$/D',$chargeId))
            return new WP_Error('r13_missing_proof','Refund/PI/charge ID missing');
        $proof=self::stripe_get('refunds/'.rawurlencode($providerId),$key);
        if (is_wp_error($proof)) return $proof;
        $amount=self::minor($refund->get_amount());
        if ($amount===null || ($proof['id']??null)!==$providerId
            || ($proof['status']??null)!=='succeeded'
            || ($proof['currency']??null)!==strtolower($order->get_currency())
            || ($proof['amount']??null)!==$amount
            || ($proof['payment_intent']??null)!==$piId
            || ($proof['charge']??null)!==$chargeId)
            return new WP_Error('r13_refund_mismatch','Provider refund does not match Woo refund');
        $old=$refund->get_meta(self::META,true);
        if (is_array($old)) {
            return (($old['provider_refund_id']??'')===$providerId
                && ($old['refund_display_minor']??null)===$amount
                && ($old['original_rate_micros']??null)===$original['rate_micros'])
                ? ['result'=>'idempotent','evidence'=>$old]
                : new WP_Error('r13_existing_conflict','Stored refund evidence is immutable');
        }
        $intent=self::stripe_get('payment_intents/'.rawurlencode($piId),$key);
        if (is_wp_error($intent) || ($intent['livemode']??null)!==false
            || ($intent['status']??null)!=='succeeded'
            || ($intent['currency']??null)!==strtolower($order->get_currency())
            || ($intent['amount']??null)!==$original['display_minor']
            || ($intent['metadata']['order_id']??null)!==(string)$orderId)
            return new WP_Error('r13_pi_mismatch','Original TEST paid intent not verifiable');
        $priorDisplay=0;$priorUsd=0;
        $refunds=$order->get_refunds();
        usort($refunds,static fn($a,$b)=>$a->get_id()<=>$b->get_id());
        foreach ($refunds as $previous) {
            if ((int)$previous->get_id()===$refundId) break;
            $evidence=$previous->get_meta(self::META,true);
            if (!is_array($evidence)
                || ($evidence['original_order_id']??null)!==$orderId
                || ($evidence['currency']??null)!==$order->get_currency()
                || ($evidence['original_rate_micros']??null)!==$original['rate_micros']
                || ($evidence['no_gateway_refund']??null)!==false
                || ($evidence['provider_status']??null)!=='succeeded'
                || !is_int($evidence['refund_display_minor']??null)
                || !is_int($evidence['reversed_usd_minor']??null))
                return new WP_Error('r13_unverified_previous','Older refund has no verified USD reversal');
            $priorDisplay += $evidence['refund_display_minor'];
            $priorUsd += $evidence['reversed_usd_minor'];
            if ($priorDisplay!==($evidence['cumulative_display_minor']??null)
                || $priorUsd!==($evidence['cumulative_reversed_usd_minor']??null)
                || ($evidence['provider_refund_id']??null)===$providerId)
                return new WP_Error('r13_duplicate_or_bad_prior','Refund history untrustworthy');
        }
        try {
            $reversal=self::plan($original,
                ['display_minor'=>$priorDisplay,'usd_minor'=>$priorUsd],
                ['currency'=>$refund->get_currency(),'amount_minor'=>$amount]);
        } catch (InvalidArgumentException $e) {
            return new WP_Error('r13_reconciliation_failed','Refund exceeds original FX ledger');
        }
        $reversal += [
            'original_order_id'=>$orderId,
            'provider_status'=>'succeeded',
            'provider_refund_id'=>$providerId,
            'provider_charge_id'=>$chargeId,
            'provider_payment_intent_id'=>$piId,
            'provider_account_id'=>AICC_CURRENCY_REFUND_TEST_ACCOUNT,
            'provider_testmode'=>true,
            'verified_source'=>'STRIPE_TEST_API_READONLY',
            'locked_order_fx_hash'=>hash('sha256',wp_json_encode($locked)),
        ];
        // Only metadata is written. NEVER call wc_create_refund or provider POST.
        $refund->update_meta_data(self::META,$reversal);
        $refund->save_meta_data();
        $readback=wc_get_order($refundId)->get_meta(self::META,true);
        if (!is_array($readback) || $readback!==$reversal)
            return new WP_Error('r13_refund_readback_failed','Metadata not persisted as verified');
        return ['result'=>'created','evidence'=>$reversal];
    }
}
