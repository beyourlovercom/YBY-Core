<?php
if(home_url()!=='http://127.0.0.1:18973'
  || DB_NAME!=='aicc_commerce_p5_20261023'
  || wp_get_environment_type()!=='local'
  ||!defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
  ||!defined('DISABLE_WP_CRON')||DISABLE_WP_CRON!==true)
 throw new RuntimeException('NOT_DISPOSABLE_LOCAL');
if(!defined('AICC_MARKETING_RUNTIME_SANDBOX'))define('AICC_MARKETING_RUNTIME_SANDBOX',true);
$dir=dirname(__DIR__,2).'/includes/';
require_once $dir.'class-andy-commerce-marketing-p4-gift-cap.php';
$draft=['id'=>'aicc_local_gift_'.gmdate('YmdHis'),'revision'=>1,'name'=>'Local Gift Quota UAT',
  'type'=>'free_gift','rule'=>['threshold_minor'=>9900,'product_id'=>88],
  'policy'=>['start_date'=>'','end_date'=>'','product_scope'=>'all','product_ids'=>[],
             'stack_policy'=>'best_single'],
  'countries'=>['US'],'priority'=>10,'currency'=>'USD','lifecycle'=>'scheduled',
  'status'=>'draft','active'=>false];
add_filter('pre_option_andy_commerce_m7_inactive_drafts_v1',
  fn()=>[$draft['id']=>$draft],999);
$approved=Andy_Commerce_Marketing_P1_Sandbox_Contract::prepare($draft);
$map=['andy_commerce_marketing_p1_approved_snapshot'=>$approved,
      'andy_commerce_marketing_p1_sandbox_enabled'=>'yes',
      'andy_commerce_marketing_p4_order_cap_enabled'=>'yes',
      'andy_commerce_marketing_p4_caps'=>[$draft['id']=>['revision'=>1,'max_uses'=>2]],
      'aicc_m14_sandbox_active'=>'no'];
foreach($map as $name=>$value)add_filter('pre_option_'.$name,fn()=>$value,999);
$canonical=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
if(!$canonical||$canonical['type']!=='free_gift')throw new RuntimeException('GIFT_LOCAL_APPROVAL_FAIL');
$mark=hash('sha256',json_encode($canonical,JSON_THROW_ON_ERROR));
if(!WC()->cart)wc_load_cart();
$cart=WC()->cart;
$cart->empty_cart();
$cart->add_to_cart(85,3);
$giftKey=$cart->add_to_cart(88,1,0,[],['andy_commerce_p2_sandbox_gift'=>$mark]);
if(!$giftKey)throw new RuntimeException('GIFT_ADD_FAIL');
$cart->get_cart()[$giftKey]['data']->set_price('0');
$cart->calculate_totals();
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$key='p4_'.substr(hash('sha256',$draft['id'].'/1'),0,48);
$before=$ledger->counters($key);
if($before['used']!==0||$before['held']!==0)throw new RuntimeException('GIFT_TEST_NONEMPTY_CAP');
$assertions=0;$check=function($yes,$tag)use(&$assertions){if(!$yes)throw new RuntimeException($tag);++$assertions;};
$reject=function($fn,$tag)use($check){try{$fn();$check(false,$tag);}catch(RuntimeException $e){$check($e->getMessage()!==$tag,$tag.'_EXPECTED_DENIAL');}};
$badToken='gift_local_bad_'.gmdate('YmdHis');
$cart->get_cart()[$giftKey]['data']->set_price('10');
$reject(fn()=>Andy_Commerce_Marketing_P4_Gift_Cap::reserve($ledger,$badToken,$cart),'FREE_GIFT_NOT_FREE');
$cart->get_cart()[$giftKey]['data']->set_price('0');
$cart->set_quantity($giftKey,2,false);
$reject(fn()=>Andy_Commerce_Marketing_P4_Gift_Cap::reserve($ledger,$badToken,$cart),'FREE_GIFT_QUANTITY');
$cart->set_quantity($giftKey,1,false);
foreach([1,2] as $n){
  $token='gift_local_token_'.$n.'_'.gmdate('YmdHis');
  $claim=Andy_Commerce_Marketing_P4_Gift_Cap::reserve($ledger,$token,$cart);
  $check($claim['accepted']===true && $claim['status']==='reserved','RESERVE_'.$n);
  $order=wc_create_order(['status'=>'pending']);
  $order->set_currency('USD');$order->set_payment_method('cod');
  $order->set_billing_email('aicc-gift-uat@example.invalid');
  $order->add_product(wc_get_product(85),3);
  $order->add_product(wc_get_product(88),1);
  foreach($order->get_items('line_item') as $item){
    if($item->get_product_id()===88){$item->set_subtotal('0');$item->set_total('0');$item->save();}
  }
  $order->calculate_totals(false);
  Andy_Commerce_Marketing_P4_Gift_Cap::stamp($order,$claim);
  $order->save();
  $bound=Andy_Commerce_Marketing_P4_Gift_Cap::bind_saved_order($claim,$order->get_id());
  $commit=Andy_Commerce_Marketing_P4_Gift_Cap::confirm($ledger,$claim,$order->get_id());
  $check($bound['saved_claim'] && $commit['committed'],'COMMIT_'.$n);
  $check($ledger->counters($key)===['max'=>2,'used'=>$n,'held'=>0],'COUNTER_'.$n);
  $order->update_status('cancelled','Disposable gift-cap test; no funds',false);
  $check($ledger->counters($key)===['max'=>2,'used'=>$n,'held'=>0],'CANCEL_DOES_NOT_REUSE_'.$n);
}
$deny=Andy_Commerce_Marketing_P4_Gift_Cap::reserve($ledger,'gift_local_third_'.gmdate('YmdHis'),$cart);
$check($deny['accepted']===false && $deny['status']==='exhausted','EXHAUSTED');
$check($ledger->counters($key)===['max'=>2,'used'=>2,'held'=>0],'FINAL_CAP');
$cart->empty_cart();
echo 'MARKETING_GIFT_REAL_WOO_CAP_PASS='.$assertions.'; max=2;used=2;held=0; no_external_payment=true'.PHP_EOL;
