<?php
/** P6-F disposable-only trusted Woo USD ledger for mixed taxable lines and coupons.
 * Native shopper totals always win. No payment/FX fetch operations are performed.
 * Unsupported calculations fail before Store API invokes the Stripe gateway.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_R13_Native_Tax_Shipping {
    public static function boot(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        add_filter('rest_request_before_callbacks',[__CLASS__,'reject_unsupported_shipping_before_checkout'],5,3);
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'snapshot'],80,1);
        add_action('woocommerce_store_api_checkout_order_processed',[__CLASS__,'require_snapshot'],95,1);
    }
    /** Woo wraps hook exceptions in HTTP 500. Preflight unsupported configurations. */
    public static function reject_unsupported_shipping_before_checkout($response,$handler,$request) {
        if ($response !== null || !($request instanceof WP_REST_Request)
            || $request->get_method() !== 'POST'
            || $request->get_route() !== '/wc/store/v1/checkout'
            || !Andy_Commerce_Currency_Runtime_C1::allowed()
            || !wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false)))
            return $response;
        return new WP_Error('aicc_currency_unreconciled',
           'Tax-inclusive shipping is not supported for this currency checkout.',
           ['status'=>422]);
    }
    private static function minor($v): ?int {
        // Woo native tax-inclusive discounts can have 8 decimal places before
        // its 2-decimal order currency reconciliation. Round HALF UP once.
        if (!is_scalar($v) || !preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,8})?$/D',(string)$v))return null;
        [$w,$f]=array_pad(explode('.',(string)$v,2),2,'');
        $fraction=str_pad($f,3,'0');
        return (int)$w*100+(int)substr($fraction,0,2)+((int)$fraction[2]>=5?1:0);
    }
    private static function fx_reverse(int $native,int $rate): int {
        if($native<0||$rate<100000||$rate>10000000)throw new InvalidArgumentException('Bad FX inverse');
        return intdiv($native*1000000+intdiv($rate,2),$rate);
    }
    private static function tax(int $minor,array $rates,bool $incl=false,bool $ship=false): int {
        if(!$rates||$minor<=0)return 0;
        $amount=number_format($minor/100,2,'.','');
        $r=$ship ? WC_Tax::calc_shipping_tax($amount,$rates)
                 : WC_Tax::calc_tax($amount,$rates,$incl);
        return (int)round(wc_round_tax_total(array_sum($r))*100);
    }
    /** The invoice is always net merchandise + net shipping + TAX, including tax-in-price mode. */
    public static function require_snapshot($order): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()||!($order instanceof WC_Order))return;
        if($order->get_currency()==='USD')return;
        $fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
        if(wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false))
           ||!is_array($fx)||($fx['display_currency']??null)!==$order->get_currency()
           ||!is_int($fx['usd_subtotal_minor']??null)
           ||!is_int($fx['native_woo_display_minor']??null)
           ||$fx['native_woo_display_minor']!==self::minor($order->get_total())
           ||($fx['checkout_capture_pending_save']??null)!==true) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
              'aicc_currency_unreconciled',
              'This currency checkout cannot be verified against the original USD ledger.',422);
        }
    }
    private static function allocation(array $lines,int $usdDiscount,int $nativeDiscount): array {
        $result=[];$residual=[];$given=0;
        if($usdDiscount<0||$nativeDiscount<0||($usdDiscount>0&&$nativeDiscount===0))
            throw new InvalidArgumentException('Invalid coupon calculation');
        foreach($lines as $id=>$line) {
            $numer=$nativeDiscount? $usdDiscount*$line['native_discount_minor']:0;
            $result[$id]=$nativeDiscount?intdiv($numer,$nativeDiscount):0;
            $residual[$id]=$nativeDiscount?$numer%$nativeDiscount:0;
            $given+=$result[$id];
        }
        arsort($residual,SORT_NUMERIC);
        foreach(array_keys($residual) as $id) {
            if($given===$usdDiscount)break;
            $result[$id]++;$given++;
        }
        if($given!==$usdDiscount)throw new InvalidArgumentException('Rounding allocation overflow');
        return $result;
    }
    public static function snapshot($order): void {
        if(!Andy_Commerce_Currency_Runtime_C1::allowed()
            ||!($order instanceof WC_Order)||!WC()->cart||!WC()->customer
            ||is_array($order->get_meta('_aicc_currency_runtime_fx_v1',true))
            ||wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false)))
            return;
        $cart=WC()->cart;$currency=$order->get_currency();
        $items=$cart->get_cart();$coupons=$cart->get_applied_coupons();
        $count=count($coupons);
        if(!in_array($currency,['EUR','GBP','AUD','CAD'],true)
            ||$currency!==get_woocommerce_currency()
            ||count($items)<1||count($items)>5
            ||count($order->get_items('line_item'))!==count($items)
            ||$count>3||count($order->get_coupon_codes())!==$count
            ||count($order->get_items('shipping'))!==1
            ||count($cart->get_shipping_packages())!==1)return;
        $inclusive=get_option('woocommerce_prices_include_tax')==='yes';
        $couponRules=[];
        foreach($coupons as $c) {
            $coupon=new WC_Coupon($c);
            $kind=$coupon->get_discount_type('edit');
            if(!$coupon->get_id()||!in_array($kind,['fixed_cart','fixed_product','percent'],true))return;
            $v=self::minor($coupon->get_amount('edit'));
            if($v===null)return;
            $couponRules[]=['code'=>$c,'type'=>$kind,'raw_amount'=>$v];
        }
        try {$quote=Andy_Commerce_Currency_Runtime_C1::quote($currency);}
        catch(Throwable $e){return;}
        $rate=$quote['rate_micros'];$lines=[];$merchandise=0;$units=0;
        foreach($items as $entry) {
            $product=$entry['data']??null;$qty=$entry['quantity']??null;
            if(!($product instanceof WC_Product_Simple)||$product->is_virtual()
               ||!is_int($qty)||$qty<1||$qty>20)return;
            $id=(int)$product->get_id();$rawUnit=self::minor($product->get_price('edit'));
            if($rawUnit===null||$rawUnit<1||isset($lines[$id]))return;
            $rawGross=$rawUnit*$qty;
            $taxable=$product->is_taxable();
            $rates=$taxable?WC_Tax::get_rates($product->get_tax_class(),WC()->customer):[];
            if($taxable && !$rates)return; // unknown rate table does not imply a zero rate
            $sourceBefore=$rawGross-($inclusive?self::tax($rawGross,$rates,true):0);
            $lines[$id]=['product_id'=>$id,'qty'=>$qty,'raw_price_usd_minor'=>$rawGross,
                'source_net_before_discount_minor'=>$sourceBefore,
                'source_tax_class'=>$product->get_tax_class(),
                'taxable'=>$taxable,'rates'=>$rates];
            $merchandise+=$sourceBefore;$units+=$qty;
        }
        if($units>25)return;
        $nativeDiscount=0;$nativeSubTotal=0;$orderLines=[];
        foreach($order->get_items('line_item') as $entry) {
            $id=(int)$entry->get_product_id();
            if(!isset($lines[$id])||isset($orderLines[$id])||
               (int)$entry->get_quantity()!==$lines[$id]['qty'])return;
            $sub=self::minor($entry->get_subtotal());$net=self::minor($entry->get_total());
            $subTax=self::minor($entry->get_subtotal_tax());$netTax=self::minor($entry->get_total_tax());
            if($sub===null||$net===null||$subTax===null||$netTax===null||$net>$sub||$netTax>$subTax)return;
            $lines[$id]['native_subtotal_minor']=$sub;
            $lines[$id]['native_discount_minor']=$sub-$net;
            $lines[$id]['native_gross_discount_minor']=$sub+$subTax-$net-$netTax;
            $nativeDiscount+=$sub-$net;$nativeSubTotal+=$sub;
            $orderLines[$id]=true;
        }
        if(count($orderLines)!==count($lines)
           ||$nativeDiscount!==self::minor($order->get_discount_total())
           ||$nativeSubTotal!==self::minor($order->get_subtotal()))return;
        // Native Woo coupon evidence is authoritative, not a client-provided USD discount.
        $sourceDiscount=self::fx_reverse($nativeDiscount,$rate);
        if(!$inclusive && $count===1 && $couponRules[0]['type']==='fixed_cart') {
            $nominal=$couponRules[0]['raw_amount'];
            if($nominal<=$merchandise
               && abs(Andy_Commerce_Currency_OS_V1::convert_usd_minor($nominal,$rate)-$nativeDiscount)<=$units+2)
                $sourceDiscount=$nominal;
        }
        if($sourceDiscount>$merchandise)return;
        try {$allocated=self::allocation($lines,$sourceDiscount,$nativeDiscount);}
        catch(Throwable $e){return;}
        $sourceTax=0;
        foreach($lines as $id=>$line) {
            if($allocated[$id]>$line['source_net_before_discount_minor'])return;
            if(!$line['taxable']) {
                $lines[$id]['source_tax_after_discount_minor']=0;
                continue;
            }
            $netAfter=$line['source_net_before_discount_minor']-$allocated[$id];
            if($inclusive) {
                // Native Woo itself determined the gross discount per SKU;
                // reconstruct the source gross discount using the frozen quote.
                $grossDiscount=self::fx_reverse($line['native_gross_discount_minor'],$rate);
                $grossAfter=$line['raw_price_usd_minor']-$grossDiscount;
                $tax=max(0,$grossAfter-$netAfter);
                if($grossAfter<0||$tax>$grossAfter)return;
            } else $tax=self::tax($netAfter,$line['rates']);
            $lines[$id]['source_tax_after_discount_minor']=$tax;
            $sourceTax+=$tax;
        }
        $shipItem=reset($order->get_items('shipping'));
        $rateId=$shipItem->get_method_id().':'.$shipItem->get_instance_id();
        if($shipItem->get_method_id()!=='flat_rate'||$shipItem->get_instance_id()<1)return;
        $method=new WC_Shipping_Flat_Rate($shipItem->get_instance_id());
        if($method->enabled!=='yes')return;
        $original=Andy_Commerce_Currency_Runtime_C4::source_shipping_quote($rateId);
        $shippingProvenance='TRUSTED_NATIVE_WOO_QUOTE';
        if(is_array($original)
           &&($original['currency']??null)===$currency
           &&($original['rate_micros']??null)===$rate
           &&($original['snapshot_hash']??null)===$quote['snapshot_hash']
           &&($original['provider']??null)==='WOO_NATIVE_FLAT_RATE'
           &&is_int($original['source_usd_minor']??null)) {
            $sourceShipping=$original['source_usd_minor'];
        } else {
            // Backwards-compatible fallback for static plain cost; never for dynamic formula.
            $raw=(string)$method->get_option('cost','');
            if(!preg_match('/^(0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',$raw))return;
            $sourceShipping=self::minor($raw);
            $shippingProvenance='WOO_STATIC_FLAT_RATE_CONFIG';
        }
        if(!is_int($sourceShipping)||$sourceShipping<0)return;
        $shipTaxable=$method->get_option('tax_status')==='taxable';
        $shipRates=$shipTaxable?WC_Tax::get_shipping_tax_rates(null,WC()->customer):[];
        if($shipTaxable&&!$shipRates)return;
        $sourceTax+=self::tax($sourceShipping,$shipRates,false,true);
        $actual=[
            'merchandise'=>self::minor($order->get_subtotal()),
            'discount'=>self::minor($order->get_discount_total()),
            'shipping'=>self::minor($order->get_shipping_total()),
            'tax'=>self::minor($order->get_total_tax()),
        ];
        $total=self::minor($order->get_total());
        if(in_array(null,$actual,true)||$total===null)return;
        if(abs($actual['shipping']-
                Andy_Commerce_Currency_OS_V1::convert_usd_minor($sourceShipping,$rate))>2)
            return;
        try {
            $ledger=Andy_Commerce_Currency_R13_Dual_Ledger::reconcile([
                'currency'=>$currency,'rate_micros'=>$rate,
                'rate_source'=>$quote['rate_source'],
                'rate_timestamp'=>$quote['retrieved_at_utc'],
                'unit_count'=>$units,'coupon_count'=>$count,'tax_inclusive'=>$inclusive,
                'usd'=>[
                    'merchandise'=>$merchandise,'discount'=>$sourceDiscount,
                    'shipping'=>$sourceShipping,'tax'=>$sourceTax,
                ],
                'woo_local'=>$actual,'woo_order_total_minor'=>$total,
            ]);
        } catch(Throwable $e){return;}
        $sourceLines=[];
        foreach($lines as $id=>$line) {
            unset($line['rates']);
            $line['source_net_discount_minor']=$allocated[$id];
            $sourceLines[$id]=$line;
        }
        $snapshot=[
            'display_currency'=>$currency,
            'usd_subtotal_minor'=>$ledger['usd_total_minor'],
            'native_woo_display_minor'=>$total,
            'rounding_adjustment_display_minor'=>$ledger['total_rounding_local_minor'],
            'checkout_capture_pending_save'=>true,
            'fx_snapshot'=>[
                'source'=>$quote['rate_source'],'rate_micros'=>$rate,
                'timestamp'=>$quote['retrieved_at_utc'],'quote_date'=>$quote['quote_date'],
                'snapshot_hash'=>$quote['snapshot_hash'],'is_settlement_rate'=>false,
            ],
            'r13_dual_ledger'=>$ledger,
            'verified_source_lines'=>$sourceLines,
            'source_coupon_allocation_minor'=>$allocated,
            'coupon_rules_verified'=>$couponRules,
            'shipping_quote_provenance'=>$shippingProvenance,
            'is_product_price_tax_inclusive'=>$inclusive,
        ];
        $order->update_meta_data('_aicc_currency_runtime_fx_v1',$snapshot);
        $order->save();
    }
}
