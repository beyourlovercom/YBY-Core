<?php
/**
 * C-R6/C-R7: sandbox-only Woo native checkout FX evidence and no-gateway
 * refund snapshot. No payment provider, dispatch, shipment or real customers.
 * Invariant: only one native fixed_cart coupon; no tax/shipping checkout in
 * this experimental slice. Everything else intentionally has NO snapshot.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_Runtime_C6 {
    public static function boot(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        add_action('woocommerce_checkout_create_order',[__CLASS__,'checkout_snapshot'],60,2);
        // Store API/Checkout Blocks hook is before payment. This proof runs
        // solely on disposable Woo, NEVER on actual Dev/WWW checkout.
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'blocks_snapshot'],60,1);
        // This hook executes after Woo saved a successful gateway refund.
        // The R13 handler independently verifies Stripe TEST refund and original payment via GET.
        if (class_exists('Andy_Commerce_Currency_R13_Stripe_Refund_Ledger'))
            Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::boot();
    }
    public static function blocks_snapshot($order): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()
            || !($order instanceof WC_Order)) return;
        if (is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true)))return;
        self::checkout_snapshot($order,[]);
        if (is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true)))
            $order->save();
    }
    private static function usd_minor($value): int {
        if (!is_scalar($value) || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',(string)$value)) {
            throw new InvalidArgumentException('Unexpected USD source');
        }
        [$w,$f]=array_pad(explode('.',(string)$value,2),2,'');
        return (int)$w*100+(int)str_pad($f,2,'0');
    }
    public static function checkout_snapshot($order,$data): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() || !($order instanceof WC_Order)
            || !function_exists('WC') || !WC()->cart) return;
        $cart=WC()->cart;
        // Preserve the checkout-time rate snapshot; never rewrite it as
        // a later Store API hook or shipping/cart calculation runs.
        if (is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true)))return;
        // We must not pretend a mixed tax/shipping or unsupported promotion
        // order has a reconciled USD ledger.
        if (abs((float)$cart->get_shipping_total())>0.00001 ||
            abs((float)$cart->get_total_tax())>0.00001 ||
            count($cart->get_applied_coupons())>1) return;
        $usd=0;
        $lineCount=0;
        foreach($cart->get_cart() as $line) {
            $p=$line['data']??null;
            $qty=$line['quantity']??null;
            if (!($p instanceof WC_Product) || !is_int($qty) || $qty<1 || $qty>1000) return;
            $usd+=self::usd_minor($p->get_price('edit'))*$qty;
            $lineCount++;
        }
        if (!$lineCount) return;
        foreach($cart->get_applied_coupons() as $couponCode) {
            $coupon=new WC_Coupon($couponCode);
            if ($coupon->get_discount_type('edit')!=='fixed_cart') return;
            $usd-=min($usd,self::usd_minor($coupon->get_amount('edit')));
        }
        if ($usd<0) return;
        $currency=get_woocommerce_currency();
        $local=self::usd_minor($cart->get_total('edit'));
        try {
            $ledger=Andy_Commerce_Currency_Runtime_Reconcile::cart_preview([
                'base_usd_minor'=>$usd,'native_cart_local_minor'=>$local,
                'rate_micros'=>Andy_Commerce_Currency_Runtime_C1::TEST_RATES[$currency],
                'cart_line_count'=>$lineCount+count($cart->get_applied_coupons()),
                'currency'=>$currency,'source'=>'CI_SYNTHETIC_LOCKED',
                'timestamp'=>1793000000,
            ]);
        } catch (Throwable $e) { return; }
        $order->set_currency($currency);
        // The checkout_create_order hook runs before Woo saves the new order.
        // Mark this as a pending-to-save snapshot, not as a failed order.
        $ledger['checkout_capture_pending_save']=true;
        unset($ledger['order_saved']);
        $order->update_meta_data('_aicc_currency_runtime_fx_v1',$ledger);
    }
    public static function refund_evidence($refund,$order): array {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() ||
            !($refund instanceof WC_Order_Refund) || !($order instanceof WC_Order)
            || (int)$refund->get_parent_id() !== (int)$order->get_id()) {
            throw new InvalidArgumentException('Currency refund not in independent sandbox');
        }
        $snapshot=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
        if (!is_array($snapshot) || ($snapshot['checkout_capture_pending_save']??null)!==true
            || ($snapshot['display_currency']??null)!==$order->get_currency()) {
            throw new InvalidArgumentException('Missing verified checkout FX snapshot');
        }
        $rate=(int)($snapshot['fx_snapshot']['rate_micros']??0);
        if ($rate<100000) throw new InvalidArgumentException('Invalid original rate');
        $local=self::usd_minor(abs((float)$refund->get_amount()));
        $originalDisplay=(int)($snapshot['native_woo_display_minor']??0);
        $originalUsd=(int)($snapshot['usd_subtotal_minor']??0);
        if ($local<1 || $originalDisplay<1 || $originalUsd<0 || $local>$originalDisplay) {
            throw new InvalidArgumentException('Invalid display refund amount');
        }
        $alreadyDisplay=0;
        $alreadyUsd=0;
        foreach ($order->get_refunds() as $previous) {
            if (!($previous instanceof WC_Order_Refund)
                || (int)$previous->get_id()===(int)$refund->get_id()) continue;
            // No unverifiable legacy refund in this sandbox proof.
            $existing=$previous->get_meta('_aicc_currency_runtime_refund_v1',true);
            if (!is_array($existing)
                || ($existing['original_order_id']??null)!==(int)$order->get_id()
                || ($existing['original_rate_micros']??null)!==$rate
                || !is_int($existing['refund_display_minor']??null)
                || !is_int($existing['reversed_usd_minor']??null)) {
                throw new InvalidArgumentException('Prior refund has no verifiable FX evidence');
            }
            $alreadyDisplay+=$existing['refund_display_minor'];
            $alreadyUsd+=$existing['reversed_usd_minor'];
        }
        if ($alreadyDisplay+$local>$originalDisplay || $alreadyUsd>$originalUsd)
            throw new InvalidArgumentException('Cumulative refund exceeds captured order ledger');
        // The final refund must reverse the exact USD remainder. Intermediate
        // refunds use the original locked FX, never the current market rate.
        $usd=($alreadyDisplay+$local===$originalDisplay)
            ? ($originalUsd-$alreadyUsd)
            : intdiv(($local*1000000)+intdiv($rate,2),$rate);
        if ($usd<0 || $usd>$originalUsd-$alreadyUsd)
            throw new InvalidArgumentException('Cumulative USD refund exceeds original');
        $info=[
            'original_order_id'=>(int)$order->get_id(),'currency'=>$order->get_currency(),
            'refund_display_minor'=>$local,'reversed_usd_minor'=>$usd,
            'original_rate_micros'=>$rate,'no_gateway_refund'=>true,
            'cumulative_display_minor'=>$alreadyDisplay+$local,
            'cumulative_reversed_usd_minor'=>$alreadyUsd+$usd,
        ];
        $refund->update_meta_data('_aicc_currency_runtime_refund_v1',$info);
        $refund->save();
        return $info;
    }
}
