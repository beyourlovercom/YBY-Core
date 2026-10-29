<?php
/**
 * Currency-independent source USD discount replay using native WC_Discounts.
 *
 * Pure in-memory/read-only: no Woo session, orders, product prices, coupons or
 * checkout hooks are mutated. Only called on a fully calculated WC cart.
 * Amounts and eligibility are based on the original Woo USD item prices.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

if (class_exists('WC_Coupon') && !class_exists('BYL_Campaign_Source_USD_Coupon_V1')) {
    /** Bypass the storefront currency amount filter for this isolated replay. */
    class BYL_Campaign_Source_USD_Coupon_V1 extends WC_Coupon {
        public function get_amount($context = 'view') {
            return parent::get_amount('edit');
        }
        public function get_discount_type($context = 'view') {
            return parent::get_discount_type('edit');
        }
    }
}

final class Andy_Commerce_Campaign_Source_USD_Discounts_V1 {
    private static function fail(string $reason): array {
        return ['ok'=>false,'reason'=>$reason,'basis_minor'=>null,'by_item_minor'=>[]];
    }

    private static function minor($value): ?int {
        if (!is_scalar($value)) return null;
        $str=(string)$value;
        if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/D',$str)) return null;
        [$whole,$cents]=array_pad(explode('.',$str,2),2,'');
        return (int)$whole*100+(int)str_pad($cents,2,'0');
    }

    /**
     * @param array $sourceLines keyed by the real Woo cart line key, containing
     *   ['product' => WC_Product, 'quantity' => int, 'usd_unit_minor' => int,
     *    'cart_item' => original Woo cart array].
     * @param string[] $couponCodes already applied by native Woo to this cart.
     */
    public static function replay(array $sourceLines,array $couponCodes): array {
        if (!class_exists('WC_Discounts') || !class_exists('WC_Coupon')
            || !class_exists('BYL_Campaign_Source_USD_Coupon_V1')
            || !function_exists('wc_add_number_precision'))
            return self::fail('native_woo_discounts_missing');
        if(count($sourceLines)<1 || count($sourceLines)>200 || count($couponCodes)>1)
            return self::fail('cart_or_coupon_limits');

        $items=[];$subtotal=0;
        foreach($sourceLines as $key=>$row){
            if(!is_array($row) || !($row['product']??null) instanceof WC_Product
                || !is_array($row['cart_item']??null)
                || !is_int($row['quantity']??null) || $row['quantity']<1 || $row['quantity']>100
                || !is_int($row['usd_unit_minor']??null) || $row['usd_unit_minor']<0)
                return self::fail('invalid_source_line');
            $lineMinor=$row['usd_unit_minor']*$row['quantity'];
            $subtotal+=$lineMinor;
            if($subtotal>100000000)return self::fail('usd_limit');
            $items[(string)$key]=(object)[
                'key'=>(string)$key,
                'object'=>$row['cart_item'],
                'product'=>$row['product'],
                'quantity'=>$row['quantity'],
                'price'=>$lineMinor,
            ];
        }

        $engine=new WC_Discounts();
        $engine->set_items($items);
        $sourceCoupons=[];
        foreach($couponCodes as $code){
            if(!is_string($code) || $code==='' || strlen($code)>200)
                return self::fail('invalid_coupon_code');
            try{
                $coupon=new BYL_Campaign_Source_USD_Coupon_V1($code);
                $type=$coupon->get_discount_type('edit');
                if(!$coupon->get_id() || !in_array($type,['fixed_cart','fixed_product','percent'],true))
                    return self::fail('coupon_type_not_supported');
                $amount=(string)$coupon->get_amount('edit');
                if($type==='percent'){
                    if(!is_numeric($amount) || (float)$amount<=0 || (float)$amount>100)
                        return self::fail('coupon_percent_invalid');
                } elseif(self::minor($amount)===null)
                    return self::fail('coupon_usd_amount_invalid');

                // Woo has already accepted the native coupon in the actual cart.
                // Re-check monetary threshold in ORIGINAL USD to prevent a
                // displayed-local amount from becoming a source-USD threshold.
                $minRaw=$coupon->get_minimum_amount('edit');
                $maxRaw=$coupon->get_maximum_amount('edit');
                // Woo stores an unset coupon spend limit as an empty string.
                $minimum=($minRaw===''||$minRaw===null)?0:self::minor($minRaw);
                $maximum=($maxRaw===''||$maxRaw===null)?0:self::minor($maxRaw);
                if($minimum===null || $maximum===null) return self::fail('coupon_spend_limit_invalid');
                if($subtotal<$minimum || ($maximum>0 && $subtotal>$maximum))
                    return self::fail('coupon_usd_spend_limit_not_met');
                if($coupon->get_email_restrictions('edit') || $coupon->get_limit_usage_to_x_items('edit')!==null)
                    return self::fail('coupon_restriction_not_verified');

                // Do not mutate the Woo cart; all discount records exist only
                // in this independent in-memory WC_Discounts instance.
                $ok=$engine->apply_coupon($coupon,false);
                if($ok!==true)return self::fail('native_coupon_replay_failed');
                $sourceCoupons[]=$code;
            } catch(Throwable $e){
                return self::fail('native_coupon_replay_exception');
            }
        }
        $amounts=$engine->get_discounts_by_item(true);
        $out=[];$totalDiscount=0;
        foreach($items as $key=>$row){
            $discount=$amounts[$key]??0;
            if(!is_numeric($discount) || $discount<0 || $discount>$row->price)
                return self::fail('source_discount_invalid');
            $minor=(int)round((float)$discount);
            $out[$key]=[
                'pre_minor'=>(int)round((float)$row->price),
                'discount_minor'=>$minor,
                'net_minor'=>(int)round((float)$row->price)-$minor,
            ];
            $totalDiscount+=$minor;
        }
        if($totalDiscount>$subtotal) return self::fail('source_overdiscount');
        return [
            'ok'=>true,'reason'=>'native_wc_discount_replay',
            'basis_minor'=>$subtotal-$totalDiscount,'subtotal_minor'=>$subtotal,
            'discount_minor'=>$totalDiscount,'by_item_minor'=>$out,
            'coupon_types'=>count($sourceCoupons),
        ];
    }
}
