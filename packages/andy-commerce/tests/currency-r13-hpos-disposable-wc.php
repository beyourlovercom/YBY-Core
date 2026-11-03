<?php
/**
 * R13 HPOS persistence smoke. Real Woo order/refund tables, disposable MySQL.
 * Invoked in separate CLI process for each authoritative datastore setting.
 * No gateway, emails or external HTTP; no Dev/WWW writes.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME!=='commerce_p5_isolated'
 || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL)
 throw new RuntimeException('HPOS UAT only in throwaway Woo');
$hpos=class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')
    && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
$expected=defined('AICC_CURRENCY_R13_HPOS_EXPECT') ? AICC_CURRENCY_R13_HPOS_EXPECT : 'off';
if($hpos!==($expected==='on'))
 throw new RuntimeException('R13 HPOS backend not active as requested; expected '.$expected);
$product=new WC_Product_Simple();
$product->set_status('publish');$product->set_name('R13 HPOS synthetic product');
$product->set_price('25.00');$product->set_regular_price('25.00');
$product->set_manage_stock(false);$product->set_virtual(true);
$id=$product->save();
$order=wc_create_order(['created_via'=>'aicc-r13-hpos-disposable']);
if(is_wp_error($order)||!($order instanceof WC_Order))throw new RuntimeException('R13 Woo order creation failed');
$order->set_currency('CAD');
$order->add_product(wc_get_product($id),1);
$order->calculate_totals();
$fx=['currency'=>'CAD','usd_source_minor'=>2500,'fx_rate_micros'=>1350000,
 'mode'=>'CI_HPOS_LOCKED','original'=>true];
$order->update_meta_data('_aicc_currency_r13_hpos_fx',$fx);
$orderId=$order->save();
$read=wc_get_order($orderId);
if(!($read instanceof WC_Order) || $read->get_currency()!=='CAD'
 || $read->get_meta('_aicc_currency_r13_hpos_fx',true)!==$fx
 || abs((float)$read->get_total()-25)>0.001)
 throw new RuntimeException('R13 Woo authoritative order/FX metadata mismatch');
$refund=wc_create_refund([
 'order_id'=>$orderId,'amount'=>5.00,'reason'=>'R13 disposable HPOS offline',
 'refund_payment'=>false,'restock_items'=>false
]);
if(is_wp_error($refund)||!($refund instanceof WC_Order_Refund))
 throw new RuntimeException('R13 native refund failed');
$evidence=['fx_rate_micros'=>1350000,'usd_reversed_minor'=>370,
 'local_refund_minor'=>500,'provider_refund'=>false];
$refund->update_meta_data('_aicc_currency_r13_hpos_refund',$evidence);
$refundId=$refund->save();
$again=wc_get_order($orderId);
$reread=wc_get_order($refundId);
if(!($reread instanceof WC_Order_Refund)
 || $reread->get_meta('_aicc_currency_r13_hpos_refund',true)!==$evidence
 || $again->get_meta('_aicc_currency_r13_hpos_fx',true)!==$fx
 || abs((float)$again->get_total_refunded()-5)>0.001)
 throw new RuntimeException('R13 Woo HPOS refund/FX metadata missing or changed');
echo 'R13_WOO_HPOS_'.strtoupper($expected).'_NATIVE_ORDER_REFUND_FX_PERSISTENCE_PASS'.PHP_EOL;
