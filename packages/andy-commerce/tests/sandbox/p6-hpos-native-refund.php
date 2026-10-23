<?php
if(getenv('AICC_ALLOW_SANDBOX_WOO_WRITES')!=='1'
 || home_url()!=='http://127.0.0.1:18973'
 || DB_NAME!=='aicc_commerce_p5_20261023'
 || wp_get_environment_type()!=='local'
 || get_option('woocommerce_custom_orders_table_enabled')!=='yes') {
 throw new RuntimeException('P6_NOT_ISOLATED_HPOS_SANDBOX');
}
$id=41;$o=wc_get_order($id);
if(!$o||$o->get_currency()!=='USD'||$o->get_payment_method()!=='cod'
 || $o->get_transaction_id()!==''||$o->is_paid()
 ||abs((float)$o->get_total()-170.64)>.005
 ||abs((float)$o->get_total_refunded())>.001)throw new RuntimeException('P6_FIXTURE_NOT_SAFE');
$r=wc_create_refund([
  'amount'=>5,'reason'=>'AICC HPOS sandbox only local refund (no gateway)',
  'order_id'=>$id,'refund_payment'=>false,'restock_items'=>false]);
if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
$o=wc_get_order($id);
if(!($r instanceof WC_Order_Refund)
 ||abs((float)$o->get_total_refunded()-5)>.005
 ||abs((float)$o->get_total()-170.64)>.005)throw new RuntimeException('P6_HPOS_REFUND_MISMATCH');
echo json_encode(['mode'=>'LOCAL_HPOS_OFFLINE_NO_FUNDS_MOVED','order_id'=>$id,
'refund_id'=>$r->get_id(),'refund_amount'=>5,'total_refunded'=>$o->get_total_refunded(),
'order_total'=>$o->get_total(),'balance_unrefunded'=>165.64,
'gateway'=>'cod','gateway_transaction_id'=>$o->get_transaction_id()],JSON_THROW_ON_ERROR),PHP_EOL;
