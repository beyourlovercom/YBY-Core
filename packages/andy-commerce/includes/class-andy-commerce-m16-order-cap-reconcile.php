<?php
/**
 * M16 trusted durable Woo order claim reconciliation.
 * Pure explicit API: no hooks, cron, auto-migrations, new orders or payment.
 */
declare(strict_types=1);
if(!defined('ABSPATH')){exit;}
require_once __DIR__.'/class-andy-commerce-m15-cap-ledger.php';

final class Andy_Commerce_M16_Order_Cap_Reconcile {
    public static function confirm(
        Andy_Commerce_M15_Cap_Ledger $ledger,
        string $campaign,
        string $token,
        int $wooOrderId
    ): array {
        if($wooOrderId<1||!function_exists('wc_get_order')){
            throw new InvalidArgumentException('Woo order authority unavailable');
        }
        $order=wc_get_order($wooOrderId);
        if(!$order instanceof WC_Order || $order->get_type()!=='shop_order' || $order->get_id()!==$wooOrderId
          ||$order->get_currency()!=='USD'
          ||!in_array($order->get_status(),['pending','on-hold','processing','completed'],true)){
            throw new RuntimeException('M16 order not eligible for cap commitment');
        }
        // The claim must be bound to the *persisted* Woo order before committing.
        // Never trust cart, raw POST/session or an unaudited order ID.
        if($order->get_meta('_aicc_m16_test_campaign',true)!==$campaign
          ||$order->get_meta('_aicc_m16_test_claim',true)!==$token){
            throw new RuntimeException('M16 persisted order claim proof mismatch');
        }
        $result=$ledger->commit($campaign,$token,$wooOrderId);
        if($result['status']!=='committed')throw new RuntimeException('M16 durable claim not committed');
        return [
            'order_id'=>$wooOrderId,'campaign_id'=>$campaign,
            'committed'=>true,'idempotent'=>$result['idempotent'],
            'payment_settlement_verified'=>false, // This API does not inspect gateway funds.
        ];
    }
}
