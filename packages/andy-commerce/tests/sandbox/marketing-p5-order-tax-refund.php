<?php
/**
 * M-P5 after real localhost Classic/Store API checkout:
 * compare order-level & SKU-level tax/discount, create one offline partial
 * native refund (reward SKU only), and test cancellation status boundaries.
 *
 * Explicit one-shot WP-CLI only. Never run on a nonlocal or paid order.
 * Must be opted in using AICC_MARKETING_P5_OFFLINE_REFUND=1 env var.
 */
if(getenv('AICC_MARKETING_P5_OFFLINE_REFUND')!=='1'
    ||!defined('DB_NAME')||DB_NAME!=='aicc_commerce_p5_20261023'
    ||home_url()!=='http://127.0.0.1:18973'
    ||wp_get_environment_type()!=='local'
    ||!defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
    ||!defined('DISABLE_WP_CRON')||DISABLE_WP_CRON!==true
    ||get_woocommerce_currency()!=='USD'
    ||get_option('woocommerce_custom_orders_table_enabled')!=='yes'
    ||!class_exists('WooCommerce'))throw new RuntimeException('M_P5_REFUND_NOT_LOCAL');
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
$testCases=[68=>['store','bogo',2250,1380,18630],
    69=>['store','bundle',2300,1376,18576],
    70=>['classic','bogo',2250,1380,18630],
    71=>['classic','bundle',2300,1376,18576]];
$expected=[
 'bogo'=>[27=>[8000,8000,640],28=>[4500,2250,180],29=>[6000,6000,480]],
 'bundle'=>[27=>[8000,6686,535],28=>[4500,4500,360],29=>[6000,5014,401]],
];
$checks=0;$ok=static function(bool $valid,string $label)use(&$checks):void{
    if(!$valid)throw new RuntimeException('M_P5_NATIVE_ORDER_FAIL '.$label);
    $checks++;
};
$out=[];
foreach($testCases as $id=>$case){
    [$mode,$campaign,$discount,$tax,$payable]=$case;
    $order=wc_get_order($id);
    $ok($order instanceof WC_Order && $order->get_type()==='shop_order',
        $id.' real Woo order');
    $ok($order->get_currency()==='USD'
        &&$order->get_payment_method()==='cod'
        &&$order->get_transaction_id()===''
        &&$order->get_billing_email()==='aicc-marketing-p5@example.invalid',
        $id.' synthetic COD no external settlement');
    $ok($order->get_coupon_codes()===['aicc-p2-campaign_p5_'.$campaign.'_01'],
        $id.' native owned coupon');
    $ok((int)round($order->get_discount_total()*100)===$discount,
        $id.' exact discount');
    $ok((int)round($order->get_total_tax()*100)===$tax
        &&(int)round($order->get_shipping_total()*100)===1000
        &&(int)round($order->get_total()*100)===$payable,
        $id.' tax/shipping/payable');
    $seen=[];$sum=0;
    foreach($order->get_items('line_item') as $line) {
        $pid=$line->get_product_id();
        $ok(!isset($seen[$pid])&&isset($expected[$campaign][$pid]),
            $id.' expected item '.$pid);
        $seen[$pid]=true;
        [$sub,$net,$lineTax]=$expected[$campaign][$pid];
        $ok((int)round($line->get_subtotal()*100)===$sub
            &&(int)round($line->get_total()*100)===$net
            &&(int)round($line->get_total_tax()*100)===$lineTax,
            $id.' SKU '.$pid.' exact money/tax');
        $sum+=$sub-$net;
    }
    $ok(count($seen)===3&&$sum===$discount,
        $id.' exact line discount allocation');
    $out[$id]=['path'=>$mode,'campaign'=>$campaign,'discount_minor'=>$discount,
        'tax_minor'=>$tax,'total_minor'=>$payable,
        'paid_by_gateway'=>false];
}
// Native partial REFUND on BOGO reward SKU. Never asks Woo gateway for money.
// Refund $5.00 discounted item net + $0.40 proportional tax, quantity unchanged.
$order=wc_get_order(68);
$ok((int)round($order->get_total_refunded()*100)===0,
    'refund fixture untouched');
$reward=null;
foreach($order->get_items('line_item') as $item)
    if($item->get_product_id()===28)$reward=$item;
$ok($reward instanceof WC_Order_Item_Product
    &&abs($reward->get_total()-22.50)<0.001
    &&abs($reward->get_total_tax()-1.80)<0.001,
    'reward SKU native discounted taxable amount');
$taxes=array_keys($reward->get_taxes()['total']);
$ok(count($taxes)===1 && (int)$taxes[0]>0,
    'one grounded native tax rate');
$refund=wc_create_refund([
    'amount'=>5.40,'reason'=>'AICC M-P5 synthetic offline partial SKU refund',
    'order_id'=>68,'refund_payment'=>false,'restock_items'=>false,
    'line_items'=>[$reward->get_id()=>[
        'qty'=>0,'refund_total'=>5.00,
        'refund_tax'=>[$taxes[0]=>0.40],
    ]],
]);
if(is_wp_error($refund))throw new RuntimeException('NATIVE_OFFLINE_REFUND '. $refund->get_error_message());
$ok($refund instanceof WC_Order_Refund
    &&(int)round($refund->get_amount()*100)===540,
    'Woo native HPOS refund amount 540 minor');
$refunded=wc_get_order(68);
$ok((int)round($refunded->get_total_refunded()*100)===540,
    'partial refund parent exact');
$ok((int)round(($refunded->get_total()-$refunded->get_total_refunded())*100)===18090,
    'remaining Woo order money exact');
$refundItems=array_values($refund->get_items('line_item'));
$ok(count($refundItems)===1
    &&(int)round($refundItems[0]->get_total()*100)===-500
    &&(int)round($refundItems[0]->get_total_tax()*100)===-40,
    'refund allocated only on discounted SKU net plus proportional tax');
// COD processing does not prove Stripe or any external fund settlement.
$ok($refunded->get_transaction_id()===''&&$refunded->get_payment_method()==='cod',
    'zero gateway refund or settlement');
// Order statuses are native Woo; Marketing usage cap must never silently
// release a consumed M15 quota on cancellation or partial refund.
foreach([68,69,70,71] as $id){
    $o=wc_get_order($id);
    $ok($o->get_status()==='processing', $id.' original native COD processing');
    $o->update_status('cancelled','M-P5 local offline cancellation; no customer payment',false);
    $ok(wc_get_order($id)->get_status()==='cancelled',
        $id.' local cancellation persisted');
}
$ok((int)round(wc_get_order(68)->get_total_refunded()*100)===540,
    'refund record survives cancellation');
echo json_encode(['result'=>'M_P5_NATIVE_CHECKOUT_TAX_REFUND_PASS',
    'checks'=>$checks,'orders'=>$out,'refund_id'=>$refund->get_id(),
    'refund_minor'=>540,'refunded_sku_id'=>28,
    'remaining_minor'=>18090,'external_payment'=>false,
    'm_p4_auto_checkout_hooks_tested'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
