<?php
/**
 * M-P4 gift quota: count an owned, single-unit, zero-priced native Woo gift.
 * No payment, inventory or refund mutation. All writes flow via M15/M16.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/class-andy-commerce-marketing-p4-order-cap.php';

final class Andy_Commerce_Marketing_P4_Gift_Cap {
    private const QUOTA='andy_commerce_marketing_p4_caps';
    private const MARK='andy_commerce_p2_sandbox_gift';
    private const PROOF='_aicc_marketing_p4_gift_proof';

    private static function authority(): array {
        if (!Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated()
            || get_option('andy_commerce_marketing_p4_order_cap_enabled','no')!=='yes'
            || get_option('aicc_m14_sandbox_active','no')!=='no')
            throw new RuntimeException('M_P4_GIFT_RUNTIME_OFF');
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        if (!is_array($draft)||($draft['type']??null)!=='free_gift'
            || !is_int($draft['rule']['product_id']??null)
            || $draft['rule']['product_id']<1
            || !is_int($draft['rule']['threshold_minor']??null)
            || $draft['rule']['threshold_minor']<1)
            throw new RuntimeException('M_P4_NOT_VERIFIED_GIFT_DRAFT');
        $caps=get_option(self::QUOTA,[]);
        $cap=is_array($caps)?($caps[$draft['id']]??null):null;
        if (!is_array($cap)||($cap['revision']??null)!==$draft['revision']
            || !is_int($cap['max_uses']??null)
            || $cap['max_uses']<1||$cap['max_uses']>1000000)
            throw new RuntimeException('M_P4_GIFT_MISSING_OR_STALE_CAP');
        return [$draft,
            'p4_'.substr(hash('sha256',$draft['id'].'/'.$draft['revision']),0,48),
            $cap['max_uses'],
            hash('sha256',json_encode($draft,JSON_THROW_ON_ERROR))];
    }

    public static function reserve(Andy_Commerce_M15_Cap_Ledger $ledger,
        string $token, WC_Cart $cart): array {
        [$draft,$key,$limit,$fingerprint]=self::authority();
        if (!preg_match('/^[A-Za-z0-9_-]{8,64}$/D',$token)
            || $cart->get_applied_coupons()!==[])
            throw new RuntimeException('M_P4_GIFT_COUPON_OR_TOKEN_INVALID');
        $giftId=$draft['rule']['product_id'];$matched=0;$subtotal=0;
        foreach($cart->get_cart() as $line) {
            if(!is_array($line)||!is_int($line['product_id']??null)
                ||!is_int($line['quantity']??null)||$line['quantity']<1
                ||!($line['data']??null) instanceof WC_Product)
                throw new RuntimeException('M_P4_GIFT_CART_UNVERIFIED');
            if($line['product_id']===$giftId) {
                if($line['quantity']!==1
                    ||!is_string($line[self::MARK]??null)
                    ||!hash_equals($fingerprint,$line[self::MARK])
                    ||(float)$line['data']->get_price()!==0.0)
                    throw new RuntimeException('M_P4_GIFT_PROVENANCE_INVALID');
                ++$matched;
            } else {
                if(!empty($line[self::MARK]))
                    throw new RuntimeException('M_P4_GIFT_MARKER_FORGED');
                $subtotal+=(int)round((float)$line['data']->get_price()*100)*$line['quantity'];
            }
        }
        if($matched!==1||$subtotal<$draft['rule']['threshold_minor']
            ||count($cart->get_cart())>20)
            throw new RuntimeException('M_P4_GIFT_QUALIFICATION_MISSING');
        $claim=$ledger->reserve($key,$token,$limit);
        return $claim+['campaign_id'=>$key,'source_id'=>$draft['id'],
            'source_revision'=>$draft['revision'],'gift_id'=>$giftId,
            'fingerprint'=>$fingerprint,'token'=>$token,'max_uses'=>$limit];
    }

    /** Persist the draft/version/proof on the Woo order before its first save. */
    public static function stamp(WC_Order $order,array $claim): void {
        [$draft,$key,, $fingerprint]=self::authority();
        if(($claim['campaign_id']??null)!==$key
            ||($claim['source_id']??null)!==$draft['id']
            ||($claim['fingerprint']??null)!==$fingerprint
            ||($claim['status']??null)!=='reserved')
            throw new RuntimeException('M_P4_GIFT_STAMP_UNOWNED');
        $order->update_meta_data(self::PROOF,$fingerprint);
    }

    private static function checked_order(array $claim,int $orderId): WC_Order {
        [$draft,$key,, $fingerprint]=self::authority();
        if(($claim['campaign_id']??null)!==$key
            ||($claim['source_id']??null)!==$draft['id']
            ||($claim['source_revision']??null)!==$draft['revision']
            ||($claim['gift_id']??null)!==$draft['rule']['product_id']
            ||($claim['fingerprint']??null)!==$fingerprint||$orderId<1)
            throw new RuntimeException('M_P4_GIFT_STALE_CLAIM');
        $order=wc_get_order($orderId);
        if(!$order instanceof WC_Order||$order->get_type()!=='shop_order'
            ||$order->get_id()!==$orderId||$order->get_currency()!=='USD'
            ||!in_array($order->get_status(),['pending','on-hold','processing'],true)
            ||$order->get_payment_method()!=='cod'
            ||$order->get_transaction_id()!==''
            ||$order->get_meta(self::PROOF,true)!==$fingerprint
            ||$order->get_coupon_codes()!==[])
            throw new RuntimeException('M_P4_GIFT_ORDER_NOT_VERIFIED');
        $giftCount=0;$subtotal=0;
        foreach($order->get_items('line_item') as $item) {
            if(!($item instanceof WC_Order_Item_Product)
                ||$item->get_quantity()<1)
                throw new RuntimeException('M_P4_GIFT_ORDER_LINE_INVALID');
            if($item->get_product_id()===$draft['rule']['product_id']) {
                if($item->get_quantity()!==1
                    ||(float)$item->get_total()!==0.0
                    ||(float)$item->get_subtotal()!==0.0)
                    throw new RuntimeException('M_P4_GIFT_NOT_FREE');
                ++$giftCount;
            } else {
                if((float)$item->get_total() < 0
                    ||abs((float)$item->get_subtotal()-(float)$item->get_total())>0.001)
                    throw new RuntimeException('M_P4_GIFT_UNEXPECTED_LINE_DISCOUNT');
                $subtotal+=(int)round((float)$item->get_total()*100);
            }
        }
        if($giftCount!==1||$subtotal<$draft['rule']['threshold_minor']
            ||(float)$order->get_discount_total()!==0.0)
            throw new RuntimeException('M_P4_GIFT_ORDER_QUALIFICATION_FAILED');
        return $order;
    }

    public static function bind_saved_order(array $claim,int $orderId): array {
        $order=self::checked_order($claim,$orderId);
        if($order->get_meta('_aicc_m16_test_claim',true)!=='')
            throw new RuntimeException('M_P4_GIFT_ALREADY_BOUND');
        $order->update_meta_data('_aicc_m16_test_campaign',$claim['campaign_id']);
        $order->update_meta_data('_aicc_m16_test_claim',$claim['token']);
        $order->update_meta_data('_aicc_marketing_p4_source',$claim['source_id']);
        $order->update_meta_data('_aicc_marketing_p4_revision',$claim['source_revision']);
        $order->save();
        return ['order_id'=>$orderId,'saved_claim'=>true];
    }

    public static function confirm(Andy_Commerce_M15_Cap_Ledger $ledger,
        array $claim,int $orderId): array {
        $order=self::checked_order($claim,$orderId);
        if($order->get_meta('_aicc_m16_test_claim',true)!==$claim['token']
            ||$order->get_meta('_aicc_m16_test_campaign',true)!==$claim['campaign_id'])
            throw new RuntimeException('M_P4_GIFT_CLAIM_NOT_BOUND');
        return Andy_Commerce_M16_Order_Cap_Reconcile::confirm(
            $ledger,$claim['campaign_id'],$claim['token'],$orderId);
    }

    public static function abort_before_order(
        Andy_Commerce_M15_Cap_Ledger $ledger,array $claim): array {
        [, $key]=self::authority();
        if(($claim['campaign_id']??null)!==$key
            ||($claim['status']??null)!=='reserved')
            throw new RuntimeException('M_P4_GIFT_UNOWNED_ABORT');
        $existing=wc_get_orders(['limit'=>1,'return'=>'ids',
            'meta_query'=>[['key'=>'_aicc_m16_test_claim',
                'value'=>$claim['token'],'compare'=>'=']]]);
        if(!is_array($existing)||$existing)
            throw new RuntimeException('M_P4_GIFT_ORDER_EXISTS');
        return $ledger->release($key,$claim['token']);
    }
}
