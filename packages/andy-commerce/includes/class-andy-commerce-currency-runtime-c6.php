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
        $order->update_meta_data('_aicc_currency_runtime_fx_v1',$ledger);
    }
    public static function refund_evidence($refund,$order): array {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() ||
            !($refund instanceof WC_Order_Refund) || !($order instanceof WC_Order)
            || (int)$refund->get_parent_id() !== (int)$order->get_id()) {
            throw new InvalidArgumentException('Currency refund not in independent sandbox');
        }
        $snapshot=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
        if (!is_array($snapshot) || ($snapshot['order_saved']??null)!==false
            || ($snapshot['display_currency']??null)!==$order->get_currency()) {
            throw new InvalidArgumentException('Missing verified checkout FX snapshot');
        }
        $rate=(int)($snapshot['fx_snapshot']['rate_micros']??0);
        if ($rate<100000) throw new InvalidArgumentException('Invalid original rate');
        $local=self::usd_minor(abs((float)$refund->get_amount()));
        $usd=intdiv(($local*1000000)+intdiv($rate,2),$rate);
        if ($usd>($snapshot['usd_subtotal_minor']??0)) {
            throw new InvalidArgumentException('Refund exceeds USD base');
        }
        $info=[
            'original_order_id'=>(int)$order->get_id(),'currency'=>$order->get_currency(),
            'refund_display_minor'=>$local,'reversed_usd_minor'=>$usd,
            'original_rate_micros'=>$rate,'no_gateway_refund'=>true,
        ];
        $refund->update_meta_data('_aicc_currency_runtime_refund_v1',$info);
        $refund->save();
        return $info;
    }
}
