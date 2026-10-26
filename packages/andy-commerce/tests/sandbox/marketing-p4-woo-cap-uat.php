<?php
/**
 * M-P4 true isolated Woo/HPOS + M15 SQL reservation -> M16 durable order.
 *
 * Explicit WP-CLI-only, no payment, email, shipping, real customer or WWW.
 * Runs ONE synthetic local COD order; the test tables were provisioned by M15.
 */
if(!defined('AICC_MARKETING_RUNTIME_SANDBOX')) define('AICC_MARKETING_RUNTIME_SANDBOX',true);
if(!defined('DB_NAME')||DB_NAME!=='aicc_commerce_p5_20261023'
    ||home_url()!=='http://127.0.0.1:18973'
    ||wp_get_environment_type()!=='local'
    ||get_woocommerce_currency()!=='USD'
    ||!defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
    ||!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON!==true
    ||get_option('aicc_m14_sandbox_active','no')!=='no'
    ||get_option('woocommerce_custom_orders_table_enabled')!=='yes'
    ||!class_exists('WooCommerce') || !isset(WC()->cart,WC()->customer,WC()->session)) {
    throw new RuntimeException('MARKETING_P4_LOCAL_HP0S_ONLY');
}
require_once dirname(__DIR__,2).'/includes/class-andy-commerce-marketing-p2-woo-adapter.php';
require_once dirname(__DIR__,2).'/includes/class-andy-commerce-marketing-p4-order-cap.php';
global $wpdb;
$ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
$checks=0;
$ok=static function(bool $test,string $why)use(&$checks):void {
    if(!$test)throw new RuntimeException('MARKETING_P4_WOO_FAIL '.$why);
    $checks++;
};
$settings=[
 Andy_Commerce_Marketing_P1_Sandbox_Contract::SWITCH_OPTION=>'no',
 Andy_Commerce_Marketing_P1_Sandbox_Contract::APPROVAL_OPTION=>null,
 Andy_Commerce_M7_Draft_Studio::OPTION=>[],
 'andy_commerce_marketing_p2_line_flags'=>[
    '27'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
    '28'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
    '29'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
 ],
 'andy_commerce_marketing_p3_sku_enabled'=>'yes',
 'andy_commerce_marketing_p4_order_cap_enabled'=>'no',
 'andy_commerce_marketing_p4_caps'=>[],
];
foreach($settings as $key=>$v) {
    add_filter('pre_option_'.$key,static function($old)use(&$settings,$key){return $settings[$key];},1,1);
}
$p1=Andy_Commerce_Marketing_P1_Sandbox_Contract::class;
$p2=Andy_Commerce_Marketing_P2_Woo_Adapter::class;
$p4=Andy_Commerce_Marketing_P4_Order_Cap::class;
$p2::boot();
$cart=WC()->cart;
$customer=WC()->customer;
$customer->set_shipping_country('US');$customer->set_billing_country('US');
$customer->set_shipping_state('CA');$customer->set_billing_state('CA');
$customer->set_shipping_postcode('90001');$customer->set_billing_postcode('90001');
WC()->session->set('chosen_shipping_methods',[]);
$orderId=null;
try {
    foreach([27,28,29] as $id)$ok(wc_get_product($id) instanceof WC_Product,'catalog fixture');
    $cart->empty_cart();
    $cart->add_to_cart(27,2);$cart->add_to_cart(28,1);$cart->add_to_cart(29,1);
    $cart->calculate_shipping();
    $rates=WC()->shipping()->get_packages()[0]['rates']??[];
    $ok(count($rates)===1,'single sandbox zone');
    WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
    $uniq=substr(str_replace('-','',wp_generate_uuid4()),0,12);
    $draft=[
        'id'=>'campaign_p4_'.$uniq,'revision'=>1,'name'=>'P4 synthetic BOGO',
        'type'=>'bogo','rule'=>[
            'buy_product_id'=>27,'get_product_id'=>28,'buy_qty'=>2,
            'get_qty'=>1,'percent_bp'=>5000],
        'priority'=>10,'lifecycle'=>'scheduled',
        'active'=>false,'status'=>'draft',
        'policy'=>['start_date'=>'','end_date'=>'','product_scope'=>'all',
            'product_ids'=>[],'stack_policy'=>'best_single'],
        'currency'=>'USD','countries'=>['US'],
    ];
    $settings[Andy_Commerce_M7_Draft_Studio::OPTION]=[$draft['id']=>$draft];
    $settings[$p1::APPROVAL_OPTION]=$p1::prepare($draft);
    $settings[$p1::SWITCH_OPTION]='yes';
    $settings['andy_commerce_marketing_p4_caps']=[$draft['id']=>[
        'revision'=>1,'max_uses'=>2,
    ]];
    $cart->calculate_totals();
    $ok((int)round((float)$cart->get_discount_total()*100)===2250,'native BOGO exact discount');
    $token1='p4a_'.$uniq; $token2='p4b_'.$uniq; $token3='p4c_'.$uniq;
    try {
        $p4::reserve($ledger,$token1,$cart);
        throw new RuntimeException('M_P4_OFF_GATE_BROKEN');
    }catch(RuntimeException $e){
        $ok($e->getMessage()==='M_P4_DISPOSABLE_OFF','quota default OFF');
    }
    $settings['andy_commerce_marketing_p4_order_cap_enabled']='yes';
    $one=$p4::reserve($ledger,$token1,$cart);
    $ok(($one['accepted']??false)===true&&$one['status']==='reserved','first held');
    $again=$p4::reserve($ledger,$token1,$cart);
    $ok(($again['idempotent']??false)===true,'reserve replay idempotent');
    $two=$p4::reserve($ledger,$token2,$cart);
    $ok($two['accepted']===true,'second held');
    $full=$p4::reserve($ledger,$token3,$cart);
    $ok($full['accepted']===false&&$full['status']==='exhausted','third blocked by cap2');
    $before=$ledger->counters($one['campaign_id']);
    $ok($before['held']===2&&$before['used']===0,'held quota two');

    $orderId=WC()->checkout()->create_order([
        'billing_first_name'=>'AICC','billing_last_name'=>'Marketing P4',
        'billing_email'=>'p4-no-real-customer@example.invalid',
        'billing_country'=>'US','billing_state'=>'CA','billing_postcode'=>'90001',
        'billing_city'=>'Local', 'billing_address_1'=>'1 Isolated Test',
        'shipping_first_name'=>'AICC','shipping_last_name'=>'P4',
        'shipping_country'=>'US','shipping_state'=>'CA','shipping_postcode'=>'90001',
        'shipping_city'=>'Local','shipping_address_1'=>'1 Isolated Test',
        'payment_method'=>'cod',
    ]);
    $ok(is_int($orderId)&&$orderId>0,'durable synthetic Woo checkout order');
    $order=wc_get_order($orderId);
    $ok($order instanceof WC_Order && $order->get_currency()==='USD'
        && $order->get_transaction_id()===''
        && in_array('aicc-p2-'.$draft['id'],$order->get_coupon_codes(),true),
        'real Woo order coupon and no external money');
    $p4::bind_saved_order($one,$orderId);
    // Read back persisted per-unit SKU discount metadata, not only totals.
    $saved=wc_get_order($orderId);
    $expectedVectors=[27=>[0,0],28=>[2250],29=>[0]];
    foreach($saved->get_items('line_item') as $item){
        $pid=$item->get_product_id();
        $ok($item->get_meta('_aicc_marketing_p4_unit_minor',true)===$expectedVectors[$pid],
            'persisted SKU '.$pid.' exact unit allocation');
    }
    $wrong=$one;$wrong['token']='wrong_'.$uniq;
    try {
        $p4::confirm($ledger,$wrong,$orderId);
        throw new RuntimeException('M_P4_TOKEN_FORGERY_BROKEN');
    } catch(RuntimeException $e) {
        $ok($e->getMessage()!=='M_P4_TOKEN_FORGERY_BROKEN',
            'wrong claim token rejected');
    }
    $confirmed=$p4::confirm($ledger,$one,$orderId);
    $ok($confirmed['committed']===true && $confirmed['idempotent']===false,
        'durable order consumes one cap once');
    $replay=$p4::confirm($ledger,$one,$orderId);
    $ok($replay['idempotent']===true,'post-save replay idempotent');
    $middle=$ledger->counters($one['campaign_id']);
    $ok($middle['used']===1&&$middle['held']===1,'used1 held1');
    $released=$p4::abort_before_order($ledger,$two);
    $ok($released['status']==='released','failed pre-order reservation released');
    $after=$ledger->counters($one['campaign_id']);
    $ok($after['used']===1&&$after['held']===0,'failed claim did not double debit');
    $third=$p4::reserve($ledger,$token3,$cart);
    $ok($third['accepted']===true,'capacity freed for new order');
    $p4::abort_before_order($ledger,$third);
    try{
        $p4::abort_before_order($ledger,$one);
        throw new RuntimeException('M_P4_COMMITTED_RELEASE_BROKEN');
    }catch(RuntimeException $e){
        $ok($e->getMessage()!=='M_P4_COMMITTED_RELEASE_BROKEN',
            'committed order cannot be released');
    }
    $order->update_status('cancelled','M-P4 local-only no-money cancel classification',false);
    $audit=$p4::classify_order_state($orderId);
    $ok($audit['status']==='cancelled'&&$audit['quota_restored']===false,
        'cancelled order does not auto restore spent quota');
    $end=$ledger->counters($one['campaign_id']);
    $ok($end['used']===1&&$end['held']===0,'cancelled order cap remains consumed');
    echo json_encode(['mode'=>'M_P4_DURABLE_WOO_CAP_PASS',
        'checks'=>$checks,'order_id'=>$orderId,
        'campaign_key'=>$one['campaign_id'],'quota'=>$end,
        'external_payment'=>false,'no_real_customer'=>true,
        'approval_persisted'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
}finally{
    $settings[$p1::SWITCH_OPTION]='no';
    $settings['andy_commerce_marketing_p4_order_cap_enabled']='no';
    $cart->empty_cart();
}
