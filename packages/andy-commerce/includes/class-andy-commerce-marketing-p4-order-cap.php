<?php
/**
 * M-P4: guarded Marketing quota -> durable native Woo order coordinator.
 *
 * Explicit API, no automatically registered hooks/migration/cron. Only the
 * disposable local Woo sandbox may reserve, bind, confirm or release.
 * Integrator #213 must own eventual Classic/Store API lifecycle hook wiring.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/class-andy-commerce-marketing-p1-sandbox-contract.php';
require_once __DIR__.'/class-andy-commerce-marketing-p3-sku-woo.php';
require_once __DIR__.'/class-andy-commerce-m15-cap-ledger.php';
require_once __DIR__.'/class-andy-commerce-m16-order-cap-reconcile.php';

final class Andy_Commerce_Marketing_P4_Order_Cap {
    private const ENABLE='andy_commerce_marketing_p4_order_cap_enabled';
    private const QUOTA='andy_commerce_marketing_p4_caps';
    private const PREFIX='aicc-p2-';

    private static function context(): array {
        if(!Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated()
            || !function_exists('get_option')
            || get_option(self::ENABLE,'no')!=='yes'
            || get_option('aicc_m14_sandbox_active','no')!=='no') {
            throw new RuntimeException('M_P4_DISPOSABLE_OFF');
        }
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        if(!$draft || !in_array($draft['type'],['spend_save','bogo','bundles','tiered'],true))
            throw new RuntimeException('M_P4_UNAPPROVED_OR_GIFT');
        $caps=get_option(self::QUOTA,null);
        $row=is_array($caps)?($caps[$draft['id']]??null):null;
        if(!is_array($row)
            || ($row['revision']??null)!==$draft['revision']
            || !is_int($row['max_uses']??null)
            || $row['max_uses']<1||$row['max_uses']>1000000)
            throw new RuntimeException('M_P4_STALE_OR_MISSING_CAP');
        // Identity is immutable for each campaign revision, independent of a
        // mutable human-readable label; at most 51 ASCII chars in M15 SQL.
        $key='p4_'.substr(hash('sha256',$draft['id'].'/'.$draft['revision']),0,48);
        return [$draft,$key,$row['max_uses']];
    }

    public static function reserve(
        Andy_Commerce_M15_Cap_Ledger $ledger,string $token,WC_Cart $cart
    ): array {
        [$draft,$key,$limit]=self::context();
        if(!preg_match('/^[A-Za-z0-9_-]{8,64}$/D',$token))
            throw new InvalidArgumentException('M_P4_INVALID_TOKEN');
        $plan=Andy_Commerce_Marketing_P3_SKU_Woo::plan();
        if(!$plan||!Andy_Commerce_Marketing_P3_SKU_Woo::active()
            || ($plan['campaign']??null)!==$draft['id']
            || ($plan['revision']??null)!==$draft['revision'])
            throw new RuntimeException('M_P4_MISSING_VERIFIED_SKU_PLAN');
        $coupons=array_values((array)$cart->get_applied_coupons());
        if($coupons!==[self::PREFIX.$draft['id']]
            || (int)round((float)$cart->get_discount_total()*100)!==$plan['discount_minor'])
            throw new RuntimeException('M_P4_CART_MISMATCH');
        // Check quantity and SKU cardinality without mutating WC_Cart. The
        // M15 snapshot was created after Woo's authoritative clean totals.
        $cartSku=[];
        foreach($cart->get_cart() as $item) {
            if(!is_array($item)||!is_int($item['product_id']??null)
                ||!is_int($item['quantity']??null)
                ||isset($cartSku[$item['product_id']]))
                throw new RuntimeException('M_P4_UNVERIFIED_CART');
            $cartSku[$item['product_id']]=$item['quantity'];
        }
        foreach($plan['lines'] as $pid=>$line) {
            if(($cartSku[$pid]??null)!==($line['quantity']??null))
                throw new RuntimeException('M_P4_QUANTITY_CHANGED');
        }
        $result=$ledger->reserve($key,$token,$limit);
        return $result+['campaign_id'=>$key,'source_id'=>$draft['id'],
            'source_revision'=>$draft['revision'],'token'=>$token,
            'max_uses'=>$limit,'order_id'=>null];
    }

    /**
     * Bind a previously created (saved) native shop_order to the held claim.
     * No payment changes: only isolated Woo audit metadata is written.
     * The order must contain the actual owned native coupon in its snapshot.
     */
    public static function bind_saved_order(array $claim,int $orderId): array {
        [$draft,$key]=self::context();
        if(($claim['campaign_id']??null)!==$key
            ||($claim['source_id']??null)!==$draft['id']
            ||($claim['source_revision']??null)!==$draft['revision']
            ||($claim['status']??null)!=='reserved'
            ||!is_string($claim['token']??null)
            ||$orderId<1 || !function_exists('wc_get_order'))
            throw new RuntimeException('M_P4_CLAIM_NOT_BINDABLE');
        $order=wc_get_order($orderId);
        if(!$order instanceof WC_Order || $order->get_type()!=='shop_order'
            || $order->get_id()!==$orderId || $order->get_currency()!=='USD'
            || !in_array($order->get_status(),['pending','on-hold','processing'],true)
            || !in_array(self::PREFIX.$draft['id'],$order->get_coupon_codes(),true)
            || $order->get_meta('_aicc_m16_test_claim',true)!==''
            || $order->get_transaction_id()!=='')
            throw new RuntimeException('M_P4_UNVERIFIED_SAVED_WOO_ORDER');
        // Audit each order line against the exact previously proven M15
        // native cents plan. No hidden generic fixed-cart spreading here.
        $plan=Andy_Commerce_Marketing_P3_SKU_Woo::plan();
        if(!is_array($plan)||($plan['campaign']??null)!==$draft['id'])
            throw new RuntimeException('M_P4_MISSING_ORDER_SKU_PLAN');
        $lines=(array)($plan['lines']??[]);
        $seen=[];$total=0;$audit=[];
        foreach($order->get_items('line_item') as $item) {
            if(!($item instanceof WC_Order_Item_Product)) 
                throw new RuntimeException('M_P4_UNVERIFIED_ORDER_ITEM');
            $pid=$item->get_product_id();
            $qty=$item->get_quantity();
            if(!is_int($pid)||$pid<1||isset($seen[$pid])
                ||!is_int($qty)||$qty<1) 
                throw new RuntimeException('M_P4_ORDER_SKU_DUPLICATED');
            $seen[$pid]=true;
            $allocation=$lines[$pid]??null;
            if($allocation!==null && $qty!==$allocation['quantity'])
                throw new RuntimeException('M_P4_ORDER_QTY_MISMATCH');
            $expected=$allocation['amount_minor']??0;
            $native=(int)round(((float)$item->get_subtotal()
                -(float)$item->get_total())*100);
            if($native!==$expected || $expected<0)
                throw new RuntimeException('M_P4_ORDER_SKU_DISCOUNT_MISMATCH');
            $unitVector=$allocation['unit_discounts_usd_minor']??array_fill(0,$qty,0);
            if(!is_array($unitVector)||count($unitVector)!==$qty
                ||array_sum($unitVector)!==$expected)
                throw new RuntimeException('M_P4_ORDER_UNIT_VECTOR_MISMATCH');
            $audit[]=['item'=>$item,'minor'=>$expected,'units'=>$unitVector];
            $total+=$expected;
        }
        if($total!==$plan['discount_minor']
            ||$total!==(int)round((float)$order->get_discount_total()*100))
            throw new RuntimeException('M_P4_ORDER_TOTAL_DISCOUNT_MISMATCH');
        // Prevalidate before touching any order item. Unit vectors make a
        // later partial Woo refund auditable; no refund is executed here.
        foreach($audit as $row) {
            $row['item']->update_meta_data('_aicc_marketing_p4_discount_minor',$row['minor']);
            $row['item']->update_meta_data('_aicc_marketing_p4_unit_minor',$row['units']);
            $row['item']->save();
        }
        $order->update_meta_data('_aicc_marketing_p4_discount_minor',$total);
        $order->update_meta_data('_aicc_m16_test_campaign',$key);
        $order->update_meta_data('_aicc_m16_test_claim',$claim['token']);
        $order->update_meta_data('_aicc_marketing_p4_source',$draft['id']);
        $order->update_meta_data('_aicc_marketing_p4_revision',$draft['revision']);
        $order->save();
        return ['order_id'=>$orderId,'saved_claim'=>true];
    }

    /**
     * M16 has exact persisted Woo order/token/campaign validation,
     * idempotent commit and crash-gap recovery built in.
     */
    public static function confirm(
        Andy_Commerce_M15_Cap_Ledger $ledger,array $claim,int $orderId
    ): array {
        [$draft,$key]=self::context();
        if(($claim['campaign_id']??null)!==$key
            ||($claim['source_id']??null)!==$draft['id']
            ||($claim['source_revision']??null)!==$draft['revision']
            ||!is_string($claim['token']??null)) {
            throw new RuntimeException('M_P4_STALE_CLAIM');
        }
        $order=wc_get_order($orderId);
        if(!$order instanceof WC_Order
            || $order->get_meta('_aicc_marketing_p4_source',true)!==$draft['id']
            || (int)$order->get_meta('_aicc_marketing_p4_revision',true)!==$draft['revision']
            || !in_array(self::PREFIX.$draft['id'],$order->get_coupon_codes(),true))
            throw new RuntimeException('M_P4_SAVED_ORDER_SOURCE_MISMATCH');
        $sum=0;$seen=[];
        foreach($order->get_items('line_item') as $item) {
            if(!($item instanceof WC_Order_Item_Product)
                ||isset($seen[$item->get_product_id()]))
                throw new RuntimeException('M_P4_ORDER_AUDIT_UNVERIFIED');
            $seen[$item->get_product_id()]=true;
            $minor=$item->get_meta('_aicc_marketing_p4_discount_minor',true);
            $units=$item->get_meta('_aicc_marketing_p4_unit_minor',true);
            if(!is_int($minor)||$minor<0||!is_array($units)
                ||count($units)!==$item->get_quantity()
                ||array_sum($units)!==$minor
                ||$minor!==(int)round(((float)$item->get_subtotal()
                    -(float)$item->get_total())*100))
                throw new RuntimeException('M_P4_ORDER_ALLOCATION_TAMPER');
            $sum+=$minor;
        }
        if($sum!==(int)$order->get_meta('_aicc_marketing_p4_discount_minor',true)
            ||$sum!==(int)round((float)$order->get_discount_total()*100))
            throw new RuntimeException('M_P4_ORDER_COUPON_TAMPER');
        return Andy_Commerce_M16_Order_Cap_Reconcile::confirm(
            $ledger,$key,$claim['token'],$orderId
        );
    }

    /**
     * Explicit abort only. Caller has verified an order was NEVER persisted.
     * Any native Woo order with the token blocks release; never restore a
     * completed/cancelled/refunded order's used quota automatically.
     */
    public static function abort_before_order(
        Andy_Commerce_M15_Cap_Ledger $ledger,array $claim
    ): array {
        [$draft,$key]=self::context();
        if(($claim['campaign_id']??null)!==$key || ($claim['status']??null)!=='reserved'
            ||!is_string($claim['token']??null)||!function_exists('wc_get_orders'))
            throw new RuntimeException('M_P4_ABORT_NOT_AUTHORIZED');
        $related=wc_get_orders([
            'limit'=>1,'return'=>'ids',
            'meta_query'=>[
                ['key'=>'_aicc_m16_test_claim','value'=>$claim['token'],'compare'=>'='],
            ],
        ]);
        if(!is_array($related)||$related)
            throw new RuntimeException('M_P4_ORDER_EXISTS_OR_UNVERIFIED');
        return $ledger->release($key,$claim['token']);
    }

    /** This is an audit classification only, not a quota or refund mutator. */
    public static function classify_order_state(int $orderId): array {
        if(!Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated())
            throw new RuntimeException('M_P4_NONLOCAL_STATUS');
        $order=wc_get_order($orderId);
        if(!$order instanceof WC_Order || $order->get_type()!=='shop_order'
            || $order->get_meta('_aicc_marketing_p4_source',true)==='')
            throw new RuntimeException('M_P4_UNOWNED_ORDER');
        $status=$order->get_status();
        return ['order_id'=>$orderId,'status'=>$status,
            'quota_restored'=>false,'external_settlement_verified'=>false,
            'mode'=>'AUDIT_ONLY_NO_REFUND_CAP_REUSE'];
    }
}
