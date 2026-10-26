<?php
/**
 * M-P5 one-shot audit for 8 actual synthetic Woo Classic/Store API orders.
 * Strict IDs and original synthetic email. No gateway, no real customers.
 * Cancels only verified local synthetic COD orders to avoid fulfillment.
 */
if(getenv('AICC_MARKETING_P5_CANCEL_SYNTHETIC')!=='1'
    ||!defined('DB_NAME')||DB_NAME!=='aicc_commerce_p5_20261023'
    ||home_url()!=='http://127.0.0.1:18973'
    ||wp_get_environment_type()!=='local'
    ||!defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
    ||!defined('DISABLE_WP_CRON')||DISABLE_WP_CRON!==true
    ||get_woocommerce_currency()!=='USD'
    ||get_option('woocommerce_custom_orders_table_enabled')!=='yes')
    throw new RuntimeException('P5_ORDER_AUDIT_ISOLATION_DENIED');
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
$cases=[
    73=>['store','fixed',1200,1464,19764],
    74=>['store','percent',1850,1412,19062],
    75=>['store','gift',0,1560,21060],
    76=>['store','tier',3700,1264,17064],
    77=>['classic','fixed',1200,1464,19764],
    78=>['classic','percent',1850,1412,19062],
    79=>['classic','gift',0,1560,21060],
    80=>['classic','tier',3700,1264,17064],
];
$checked=0;$assert=static function(bool $v,string $label)use(&$checked):void{
    if(!$v)throw new RuntimeException('P5_NATIVE_ORDER_AUDIT_FAIL '.$label);
    $checked++;
};
$out=[];
foreach($cases as $oid=>[$mode,$type,$minor,$tax,$total]){
    $o=wc_get_order($oid);
    $assert($o instanceof WC_Order&&$o->get_type()==='shop_order'
        &&$o->get_status()==='processing',$oid.' native order');
    $assert($o->get_billing_email()==='aicc-marketing-p5@example.invalid'
        &&$o->get_payment_method()==='cod'
        &&$o->get_transaction_id()===''&&$o->get_currency()==='USD'
        &&$o->get_total_refunded()==0,$oid.' synthetic non-gateway');
    $coupon=$type==='gift'?[]:['aicc-p2-campaign_p5_'.$type.'_01'];
    $assert($o->get_coupon_codes()===$coupon,$oid.' coupon');
    $assert((int)round($o->get_discount_total()*100)===$minor
        &&(int)round($o->get_total_tax()*100)===$tax
        &&(int)round($o->get_total()*100)===$total
        &&(int)round($o->get_shipping_total()*100)===1000,
        $oid.' total/tax/shipping');
    $sum=0;$giftCount=0;$seen=[];
    foreach($o->get_items('line_item') as $item) {
        $pid=$item->get_product_id();
        $assert(!isset($seen[$pid]),$oid.' unique SKU '.$pid);
        $seen[$pid]=true;
        $qty=$item->get_quantity();
        $subtotal=(int)round($item->get_subtotal()*100);
        $actual=(int)round($item->get_total()*100);
        $assert($qty>=1 && $subtotal>=$actual && $actual>=0,
            $oid.' nonnegative SKU '.$pid);
        if($pid===30){
            $giftCount++;
            $assert($type==='gift'&&$qty===1&&$actual===0,
                $oid.' one free SKU gift');
        }else {
            $assert(in_array($pid,[27,28,29],true),$oid.' expected regular SKU');
            $sum+=$subtotal-$actual;
        }
    }
    $assert($sum===$minor && $giftCount===($type==='gift'?1:0),
        $oid.' exact native SKU allocation');
    $out[$oid]=['mode'=>$mode,'type'=>$type,
        'discount_minor'=>$minor,'tax_minor'=>$tax,'total_minor'=>$total,
        'gift_lines'=>$giftCount,'external_payment'=>false];
}
foreach(array_keys($cases) as $oid){
    $o=wc_get_order($oid);
    $assert($o->get_status()==='processing'
        &&$o->get_transaction_id()===''
        &&$o->get_billing_email()==='aicc-marketing-p5@example.invalid',
        $oid.' cancellation fixture guard');
    $o->update_status('cancelled','M-P5 test-only synthetic COD order',false);
    $assert(wc_get_order($oid)->get_status()==='cancelled',
        $oid.' cancelled and not fulfilled');
}
echo json_encode(['result'=>'M_P5_SIX_NATIVE_ORDER_AUDIT_PASS',
    'checks'=>$checked,'orders'=>$out,'cancelled'=>array_keys($cases),
    'real_customer'=>false,'external_payment'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
