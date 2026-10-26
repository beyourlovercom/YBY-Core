<?php
/**
 * M-P5 readback and controlled cancellation of two native localhost HTTP COD
 * orders created through the real Store API and Classic M15/M16 cap hooks.
 * Does not release committed usage on cancellation or issue any payment refund.
 */
if(getenv('AICC_MARKETING_P5_QUOTA_ORDER_AUDIT')!=='1'
    ||!defined('DB_NAME')||DB_NAME!=='aicc_commerce_p5_20261023'
    ||home_url()!=='http://127.0.0.1:18973'
    ||wp_get_environment_type()!=='local'
    ||!defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
    ||!defined('DISABLE_WP_CRON')||DISABLE_WP_CRON!==true
    ||get_woocommerce_currency()!=='USD'
    ||get_option('woocommerce_custom_orders_table_enabled')!=='yes')
    throw new RuntimeException('M_P5_NON_LOCAL_CAP_AUDIT_DENIED');
require_once dirname(__DIR__,2).'/includes/class-andy-commerce-m15-cap-ledger.php';
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
$capKey='p4_'.substr(hash('sha256','campaign_p5_bogo_01/1'),0,48);
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$checks=0;$ok=static function(bool $b,string $why)use(&$checks):void{
    if(!$b)throw new RuntimeException('M_P5_CAP_HTTP_AUDIT_FAIL '.$why);
    $checks++;
};
$capBefore=$ledger->counters($capKey);
$ok($capBefore===['max'=>2,'used'=>2,'held'=>0],'two confirmed zero reserved');
$orderIds=[81=>'store',82=>'classic'];
$out=[];$tokens=[];
foreach($orderIds as $oid=>$path){
    $o=wc_get_order($oid);
    $ok($o instanceof WC_Order && $o->get_status()==='processing',$oid.' native HTTP checkout');
    $ok($o->get_type()==='shop_order'&&$o->get_currency()==='USD'
        &&$o->get_payment_method()==='cod'
        &&$o->get_transaction_id()===''
        &&$o->get_billing_email()==='aicc-marketing-p5@example.invalid',
        $oid.' synthetic zero gateway settlement');
    $ok($o->get_coupon_codes()===['aicc-p2-campaign_p5_bogo_01'],
        $oid.' single native automatic coupon');
    $ok((int)round($o->get_discount_total()*100)===2250
        &&(int)round($o->get_total_tax()*100)===1380
        &&(int)round($o->get_shipping_total()*100)===1000
        &&(int)round($o->get_total()*100)===18630,
        $oid.' total-tax-shipping');
    $token=$o->get_meta('_aicc_m16_test_claim',true);
    $ok($o->get_meta('_aicc_m16_test_campaign',true)===$capKey
        &&$o->get_meta('_aicc_marketing_p4_source',true)==='campaign_p5_bogo_01'
        &&$o->get_meta('_aicc_marketing_p4_revision',true)==='1'
        &&is_string($token)&&preg_match('/^p5_[a-f0-9]{32}$/D',$token)
        &&!isset($tokens[$token]),$oid.' M16 persisted unique reservation');
    $tokens[$token]=true;
    $expected=[27=>[0,0],28=>[2250],29=>[0]];
    $count=0;
    foreach($o->get_items('line_item') as $item){
        $pid=$item->get_product_id();
        $ok(isset($expected[$pid]) && $item->get_meta('_aicc_marketing_p4_unit_minor',true)===$expected[$pid],
            $oid.' exact persisted per-unit cents '.$pid);
        $count++;
    }
    $ok($count===3,$oid.' three verified SKU lines');
    $out[$oid]=['channel'=>$path,'campaign_key'=>$capKey,
        'claim'=>$token,'discount_minor'=>2250,'gateway_payment'=>false];
}
$ok(count($tokens)===2,'two different idempotency tokens');
foreach(array_keys($orderIds) as $oid){
    $o=wc_get_order($oid);
    $o->update_status('cancelled','M-P5 synthetic COD quota test: no funds moved',false);
    $ok(wc_get_order($oid)->get_status()==='cancelled',
        $oid.' cancelled and not fulfilled');
}
$capAfter=$ledger->counters($capKey);
$ok($capAfter===$capBefore && $capAfter['used']===2 && $capAfter['held']===0,
    'cancel does not silently restore committed usage');
echo json_encode(['mode'=>'M_P5_REAL_HTTP_WOO_CAP_ORDER_AUDIT_PASS',
    'checks'=>$checks,'orders'=>$out,'quota'=>$capAfter,
    'customer_payments'=>0,'used_quota_restored'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
