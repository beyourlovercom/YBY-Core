<?php
/**
 * P6-D/F disposable-only native Woo tax/shipping USD ledger, up to three
 * distinct simple taxable SKUs sharing one tax class and one fixed-cart coupon.
 * Reject unsupported checkout before Stripe; never infer USD from client values.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_R13_Native_Tax_Shipping {
    public static function boot(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'snapshot'],80,1);
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'require_snapshot'],95,1);
    }
    private static function minor($v): ?int {
        if (!is_scalar($v) || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',(string)$v)) return null;
        [$w,$f]=array_pad(explode('.',(string)$v,2),2,'');
        return (int)$w*100+(int)str_pad($f,2,'0');
    }
    private static function tax_minor(int $sourceMinor,array $rates,bool $shipping=false): int {
        $amount=number_format($sourceMinor/100,2,'.','');
        $taxes=$shipping ? WC_Tax::calc_shipping_tax($amount,$rates)
                         : WC_Tax::calc_tax($amount,$rates,false);
        return (int)round(wc_round_tax_total(array_sum($taxes))*100);
    }
    /** Prevent TEST-only foreign-currency charging without a verifiable USD source. */
    public static function require_snapshot($order): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() || !($order instanceof WC_Order)) return;
        if ($order->get_currency()==='USD') return;
        $fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
        if (!is_array($fx)
            || ($fx['display_currency']??null)!==$order->get_currency()
            || !is_int($fx['usd_subtotal_minor']??null)
            || !is_int($fx['native_woo_display_minor']??null)
            || ($fx['native_woo_display_minor']??null)!==self::minor($order->get_total())
            || ($fx['checkout_capture_pending_save']??null)!==true) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                'aicc_currency_unreconciled',
                'This currency checkout has an unsupported tax, shipping, or discount configuration.',
                422
            );
        }
    }
    /** Source-USD allocation follows Woo's REAL native per-line coupon deduction. */
    private static function allocate_discount(array $lines,int $discount,int $nativeDiscount): array {
        if ($discount<0 || $nativeDiscount<1) throw new InvalidArgumentException('Invalid source discount');
        $alloc=[];$residues=[];$assigned=0;
        foreach ($lines as $id=>$line) {
            $numerator=$discount*$line['native_discount_minor'];
            $base=intdiv($numerator,$nativeDiscount);
            $alloc[$id]=$base;$assigned+=$base;
            $residues[$id]=$numerator%$nativeDiscount;
        }
        arsort($residues,SORT_NUMERIC);
        foreach (array_keys($residues) as $id) {
            if ($assigned===$discount) break;
            $alloc[$id]++;$assigned++;
        }
        if ($assigned!==$discount) throw new InvalidArgumentException('Coupon source allocation mismatch');
        return $alloc;
    }
    public static function snapshot($order): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()
            || !($order instanceof WC_Order) || !WC()->cart || !WC()->customer
            || get_option('woocommerce_prices_include_tax')!=='no'
            || wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false))
            || is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true))) return;
        $cart=WC()->cart;
        $currency=$order->get_currency();
        $items=$cart->get_cart();
        if (!in_array($currency,['EUR','GBP','AUD','CAD'],true)
            || $currency!==get_woocommerce_currency()
            || count($items)<1 || count($items)>3
            || count($order->get_items('line_item'))!==count($items)
            || count($cart->get_applied_coupons())!==1
            || count($order->get_coupon_codes())!==1
            || count($order->get_items('shipping'))!==1
            || count($cart->get_shipping_packages())!==1
            || (float)$order->get_shipping_total()<=0
            || (float)$order->get_total_tax()<=0) return;
        $cartCoupon=$cart->get_applied_coupons()[0];
        if (strcasecmp($cartCoupon,$order->get_coupon_codes()[0])!==0) return;
        $coupon=new WC_Coupon($cartCoupon);
        if ($coupon->get_discount_type('edit')!=='fixed_cart'
            || !$coupon->get_individual_use('edit')) return;
        $discount=self::minor($coupon->get_amount('edit'));
        if ($discount===null) return;
        $rawLines=[];$merchandise=0;$totalQty=0;$class=null;
        foreach ($items as $entry) {
            $p=$entry['data']??null;$qty=$entry['quantity']??null;
            if (!($p instanceof WC_Product_Simple)
                || $p->is_virtual() || !$p->is_taxable()
                || !is_int($qty) || $qty<1 || $qty>10
                || ($class!==null && $class!==$p->get_tax_class()))return;
            $id=(int)$p->get_id();
            $unit=self::minor($p->get_price('edit'));
            if ($unit===null||$unit<1||isset($rawLines[$id]))return;
            $class=$p->get_tax_class();
            $subtotal=$unit*$qty;
            $rawLines[$id]=['product_id'=>$id,'qty'=>$qty,'usd_minor'=>$subtotal];
            $merchandise+=$subtotal;$totalQty+=$qty;
        }
        if ($totalQty>12 || $discount>$merchandise)return;
        $orderLines=[];$nativeLineDiscountTotal=0;
        foreach ($order->get_items('line_item') as $item) {
            $id=(int)$item->get_product_id();
            if (!isset($rawLines[$id])||isset($orderLines[$id])
                || (int)$item->get_quantity()!==$rawLines[$id]['qty'])return;
            $sub=self::minor($item->get_subtotal());
            $net=self::minor($item->get_total());
            if ($sub===null||$net===null||$net>$sub)return;
            $rawLines[$id]['native_discount_minor']=$sub-$net;
            $nativeLineDiscountTotal+=$sub-$net;
            $orderLines[$id]=true;
        }
        if (count($orderLines)!==count($rawLines) || $nativeLineDiscountTotal<1
            || $nativeLineDiscountTotal!==self::minor($order->get_discount_total()))return;
        $shipItem=reset($order->get_items('shipping'));
        if ($shipItem->get_method_id()!=='flat_rate' || $shipItem->get_instance_id()<1)return;
        $method=new WC_Shipping_Flat_Rate($shipItem->get_instance_id());
        $rawShipping=(string)$method->get_option('cost','');
        if ($method->enabled!=='yes'||$method->get_option('tax_status')!=='taxable'
            || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',$rawShipping))return;
        $ship=self::minor($rawShipping);
        $taxRates=WC_Tax::get_rates($class,WC()->customer);
        $shipTaxRates=WC_Tax::get_shipping_tax_rates(null,WC()->customer);
        if ($ship===null||!$taxRates||!$shipTaxRates)return;
        $allocation=self::allocate_discount($rawLines,$discount,$nativeLineDiscountTotal);
        $usdTax=0;
        foreach ($rawLines as $id=>$line) {
            $usdTax+=self::tax_minor($line['usd_minor']-$allocation[$id],$taxRates);
        }
        $usdTax+=self::tax_minor($ship,$shipTaxRates,true);
        $actual=[
            'merchandise'=>self::minor($order->get_subtotal()),
            'discount'=>self::minor($order->get_discount_total()),
            'shipping'=>self::minor($order->get_shipping_total()),
            'tax'=>self::minor($order->get_total_tax()),
        ];
        $total=self::minor($order->get_total());
        if (in_array(null,$actual,true)||$total===null)return;
        $rate=Andy_Commerce_Currency_Runtime_C1::TEST_RATES[$currency];
        try {
            $ledger=Andy_Commerce_Currency_R13_Dual_Ledger::reconcile([
                'currency'=>$currency,'rate_micros'=>$rate,
                'rate_source'=>'CI_SYNTHETIC_LOCKED',
                'rate_timestamp'=>1793000000,
                'unit_count'=>$totalQty,'coupon_count'=>1,'tax_inclusive'=>false,
                'usd'=>['merchandise'=>$merchandise,'discount'=>$discount,'shipping'=>$ship,'tax'=>$usdTax],
                'woo_local'=>$actual,'woo_order_total_minor'=>$total,
            ]);
        } catch (Throwable $e) { return; }
        $snapshot=[
            'display_currency'=>$currency,'usd_subtotal_minor'=>$ledger['usd_total_minor'],
            'native_woo_display_minor'=>$total,
            'rounding_adjustment_display_minor'=>$ledger['total_rounding_local_minor'],
            'checkout_capture_pending_save'=>true,
            'fx_snapshot'=>['source'=>'CI_SYNTHETIC_LOCKED','rate_micros'=>$rate,'timestamp'=>1793000000],
            'r13_dual_ledger'=>$ledger,
            'verified_source_lines'=>$rawLines,
            'source_coupon_allocation_minor'=>$allocation,
        ];
        $order->update_meta_data('_aicc_currency_runtime_fx_v1',$snapshot);
        $order->save();
    }
}
