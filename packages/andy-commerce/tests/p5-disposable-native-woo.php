<?php
/** P5-C disposable Woo order arithmetic; ONLY on ephemeral CI WordPress. */
if (!defined('ABSPATH') || !function_exists('wc_create_order')) { throw new RuntimeException('Woo unavailable'); }
if (get_option('siteurl') !== 'http://commerce-p5.invalid') { throw new RuntimeException('Not isolated CI site'); }
if (!defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL) { throw new RuntimeException('External HTTP not blocked'); }
$p=new WC_Product_Simple();
$p->set_name('P5 synthetic item');
$p->set_status('publish');
$p->set_regular_price('50.00');
$p->set_price('50.00');
$p->set_manage_stock(false);
$id=$p->save();
$order=wc_create_order(['created_via'=>'aicc-p5-disposable']);
$order->set_currency('USD');
$item_id=$order->add_product(wc_get_product($id),2);
if (!$item_id) { throw new RuntimeException('Synthetic order product insert failed'); }
$order->calculate_totals();
if (abs((float)$order->get_total()-100.0)>0.001) {
  throw new RuntimeException('Native Woo USD order mismatch: '.$order->get_total());
}
$shipping=new WC_Order_Item_Shipping();
$shipping->set_method_id('flat_rate');
$shipping->set_method_title('Disposable fixed shipping');
$shipping->set_total('5.00');
$order->add_item($shipping);
$order->calculate_totals();
if (abs((float)$order->get_total()-105.0)>0.001) {
  throw new RuntimeException('Native Woo shipping arithmetic mismatch: '.$order->get_total());
}
$refund=wc_create_refund(['amount'=>5.0,'reason'=>'Disposable internal bookkeeping only','order_id'=>$order->get_id(),'refund_payment'=>false,'restock_items'=>false]);
if (is_wp_error($refund)) { throw new RuntimeException('Native no-gateway refund failed: '.$refund->get_error_message()); }
if (abs((float)$order->get_total_refunded()-5.0)>0.001) {
  $order=wc_get_order($order->get_id());
  if (abs((float)$order->get_total_refunded()-5.0)>0.001) throw new RuntimeException('Native refund ledger mismatch');
}
echo 'P5_NATIVE_WOO_SYNTHETIC_ORDER_PASS total=105.00 currency=USD'.PHP_EOL;
echo 'P5_NATIVE_NON_GATEWAY_REFUND_PASS amount=5.00'.PHP_EOL;
echo 'P5_NO_PAYMENT_GATEWAY=TRUE; DISPOSABLE_ONLY=TRUE'.PHP_EOL;
