<?php
/**
 * R13 P6-D: strictly disposable Woo Checkout Store API *native* tax/shipping
 * original USD ledger. Only supports one taxable simple physical product,
 * one fixed-cart USD coupon and one static taxable flat-rate method.
 * Other scenarios fail CLOSED without false accounting metadata.
 * No payment provider, product data, tax configuration or order total mutation.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_R13_Native_Tax_Shipping {
    public static function boot(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'snapshot'],80,1);
    }
    private static function minor($v): ?int {
        if(!is_scalar($v) || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',(string)$v)) return null;
        [$w,$f]=array_pad(explode('.',(string)$v,2),2,'');
        return (int)$w*100+(int)str_pad($f,2,'0');
    }
    private static function tax_minor(string $amount,array $rates,bool $shipping=false): int {
        $tax=$shipping?WC_Tax::calc_shipping_tax($amount,$rates):WC_Tax::calc_tax($amount,$rates,false);
        return (int)round(wc_round_tax_total(array_sum($tax))*100);
    }
    public static function snapshot($order): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()
            || !($order instanceof WC_Order) || !WC()->cart || !WC()->customer
            || get_option('woocommerce_prices_include_tax')!=='no'
            || wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false))
            || is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true))) return;
        $cart=WC()->cart;
        $code=$order->get_currency();
        if(!in_array($code,['EUR','GBP','AUD','CAD'],true)
           || $code!==get_woocommerce_currency()
           || count($cart->get_cart())!==1
           || count($cart->get_applied_coupons())!==1
           || count($order->get_items('line_item'))!==1
           || count($order->get_items('shipping'))!==1
           || count($order->get_coupon_codes())!==1
           || count($cart->get_shipping_packages())!==1
           || abs((float)$order->get_shipping_total())<0.00001
           || abs((float)$order->get_total_tax())<0.00001) return;
        $line=reset($cart->get_cart());
        $product=$line['data']??null;
        if (!($product instanceof WC_Product_Simple)
            || $product->is_virtual() || !$product->is_taxable()
            || ($line['quantity']??null)!==1) return;
        $unit=self::minor($product->get_price('edit'));
        $couponCode=$cart->get_applied_coupons()[0];
        if (strtolower($couponCode)!==strtolower($order->get_coupon_codes()[0]))return;
        $coupon=new WC_Coupon($couponCode);
        if($coupon->get_discount_type('edit')!=='fixed_cart'
           || !$coupon->get_individual_use('edit'))return;
        $discount=self::minor($coupon->get_amount('edit'));
        $shipItem=reset($order->get_items('shipping'));
        if($shipItem->get_method_id()!=='flat_rate'||$shipItem->get_instance_id()<1)return;
        $shipping=new WC_Shipping_Flat_Rate($shipItem->get_instance_id());
        $shipRaw=(string)$shipping->get_option('cost','');
        if(!($shipping->enabled==='yes')
            || $shipping->get_option('tax_status')!=='taxable'
            || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',$shipRaw))return;
        $ship=self::minor($shipRaw);
        if($unit===null||$discount===null||$ship===null||$discount>$unit)return;
        $rate=Andy_Commerce_Currency_Runtime_C1::TEST_RATES[$code];
        // All source amounts are read from Woo product, coupon and shipping
        // instance server data; never from browser-supplied "USD" fields.
        $taxRates=WC_Tax::get_rates($product->get_tax_class(),WC()->customer);
        $shippingRates=WC_Tax::get_shipping_tax_rates(null,WC()->customer);
        if(!$taxRates || !$shippingRates)return;
        $usdTax=self::tax_minor(number_format(($unit-$discount)/100,2,'.',''),$taxRates)
             +self::tax_minor(number_format($ship/100,2,'.',''),$shippingRates,true);
        $native=[
            'merchandise'=>self::minor($order->get_subtotal()),
            'discount'=>self::minor($order->get_discount_total()),
            'shipping'=>self::minor($order->get_shipping_total()),
            'tax'=>self::minor($order->get_total_tax()),
        ];
        $total=self::minor($order->get_total());
        if(in_array(null,$native,true)||$total===null)return;
        try {
            $ledger=Andy_Commerce_Currency_R13_Dual_Ledger::reconcile([
                'currency'=>$code,'rate_micros'=>$rate,
                'rate_source'=>'CI_SYNTHETIC_LOCKED',
                'rate_timestamp'=>1793000000,'unit_count'=>1,
                'coupon_count'=>1,'tax_inclusive'=>false,
                'usd'=>[
                    'merchandise'=>$unit,'discount'=>$discount,
                    'shipping'=>$ship,'tax'=>$usdTax,
                ],
                'woo_local'=>$native,'woo_order_total_minor'=>$total,
            ]);
        } catch(Throwable $e) { return; }
        $sourceUsd=$ledger['usd_total_minor'];
        $snapshot=[
            'display_currency'=>$code,
            'usd_subtotal_minor'=>$sourceUsd,
            'native_woo_display_minor'=>$total,
            'rounding_adjustment_display_minor'=>$ledger['total_rounding_local_minor'],
            'checkout_capture_pending_save'=>true,
            'fx_snapshot'=>[
                'source'=>'CI_SYNTHETIC_LOCKED','rate_micros'=>$rate,
                'timestamp'=>1793000000,
            ],
            'r13_dual_ledger'=>$ledger,
        ];
        // Deliberately preserves the prepayment, immutable order-time evidence.
        // Existing metadata must NEVER be overwritten by future retries or webhooks.
        $order->update_meta_data('_aicc_currency_runtime_fx_v1',$snapshot);
        $order->save();
    }
}
