<?php
if(home_url()!=='http://127.0.0.1:18973'||DB_NAME!=='aicc_commerce_p5_20261023'||wp_get_environment_type()!=='local'||!defined('WP_HTTP_BLOCK_EXTERNAL')||!WP_HTTP_BLOCK_EXTERNAL||!defined('DISABLE_WP_CRON')||!DISABLE_WP_CRON)throw new RuntimeException('P5_GIFT_NOT_DISPOSABLE');
if(!defined('AICC_MARKETING_RUNTIME_SANDBOX'))define('AICC_MARKETING_RUNTIME_SANDBOX',true);
$dir=dirname(__DIR__,2).'/includes/';
require_once $dir.'class-andy-commerce-marketing-p5-checkout-hooks.php';
$id='aicc_gift_p5_'.gmdate('YmdHis');
$draft=['id'=>$id,'revision'=>1,'name'=>'UAT P5 gift','type'=>'free_gift','rule'=>['threshold_minor'=>9900,'product_id'=>88],'policy'=>['start_date'=>'','end_date'=>'','product_scope'=>'all','product_ids'=>[],'stack_policy'=>'best_single'],'countries'=>['US'],'priority'=>10,'currency'=>'USD','lifecycle'=>'scheduled','status'=>'draft','active'=>false];
add_filter('pre_option_andy_commerce_m7_inactive_drafts_v1',fn()=>[$id=>$draft],999);
$approved=Andy_Commerce_Marketing_P1_Sandbox_Contract::prepare($draft);
foreach([
  'andy_commerce_marketing_p1_approved_snapshot'=>$approved,
  'andy_commerce_marketing_p1_sandbox_enabled'=>'yes',
  'andy_commerce_marketing_p4_order_cap_enabled'=>'yes',
  'andy_commerce_marketing_p4_caps'=>[$id=>['revision'=>1,'max_uses'=>1]],
  'aicc_m14_sandbox_active'=>'no'
] as $k=>$v) add_filter('pre_option_'.$k,fn()=>$v,999);
$canonical=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
if(!$canonical)throw new RuntimeException('GIFT_NO_APPROVAL');
$mark=hash('sha256',json_encode($canonical,JSON_THROW_ON_ERROR));
if(!WC()->cart)wc_load_cart();
WC()->customer->set_shipping_country('US');
WC()->customer->set_billing_country('US');
WC()->customer->set_shipping_state('CA');
WC()->customer->set_billing_state('CA');
WC()->customer->set_shipping_postcode('90001');
$cart=WC()->cart;$cart->empty_cart();
$cart->add_to_cart(85,3);
$giftKey=$cart->add_to_cart(88,1,0,[],['andy_commerce_p2_sandbox_gift'=>$mark]);
if(!$giftKey)throw new RuntimeException('GIFT_NOT_ADDED');
$cart->get_cart()[$giftKey]['data']->set_price('0');
$cart->calculate_shipping();$cart->calculate_totals();
Andy_Commerce_Marketing_P5_Checkout_Hooks::boot();
$orderId=WC()->checkout()->create_order([
'billing_first_name'=>'Gift','billing_last_name'=>'UAT',
'billing_email'=>'aicc-gift-p5@example.invalid',
'billing_country'=>'US','billing_state'=>'CA','billing_postcode'=>'90001',
'billing_city'=>'Local','billing_address_1'=>'1 Test Street',
'shipping_first_name'=>'Gift','shipping_last_name'=>'UAT',
'shipping_country'=>'US','shipping_state'=>'CA','shipping_postcode'=>'90001',
'shipping_city'=>'Local','shipping_address_1'=>'1 Test Street',
'payment_method'=>'cod'
]);
if(is_wp_error($orderId))throw new RuntimeException($orderId->get_error_message());
$o=wc_get_order($orderId);
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$key='p4_'.substr(hash('sha256',$id.'/1'),0,48);
$c=$ledger->counters($key);
if(!$o||$o->get_meta('_aicc_m16_test_claim',true)===''
   ||$o->get_meta('_aicc_marketing_p4_gift_proof',true)!==$mark
   ||$c!==['max'=>1,'used'=>1,'held'=>0])throw new RuntimeException('GIFT_P5_DURABLE_QUOTA_MISMATCH '.json_encode($c));
$o->update_status('cancelled','Local disposable gift test; no funds',false);
$after=$ledger->counters($key);
if($after!==$c)throw new RuntimeException('GIFT_QUOTA_REUSED_AFTER_CANCEL');
$cart->empty_cart();
echo 'MARKETING_P5_GIFT_REAL_CLASSIC_HOOK_PASS order_id='.$orderId.' cap='.json_encode($after).' payment=COD_NO_FUNDS'.PHP_EOL;
