<?php
if(getenv('AICC_ALLOW_M16_LOCAL_WOO_WRITES')!=='1'
 ||home_url()!=='http://127.0.0.1:18973'
 ||DB_NAME!=='aicc_commerce_p5_20261023'
 ||wp_get_environment_type()!=='local'
 ||get_option('woocommerce_custom_orders_table_enabled')!=='yes'
 ||get_option('aicc_m14_sandbox_active')!=='no'
 ||!defined('WP_HTTP_BLOCK_EXTERNAL')||!WP_HTTP_BLOCK_EXTERNAL)
 throw new RuntimeException('M16_RECOVERY_TEST_ONLY_LOCAL');
require_once getenv('AICC_M16_RECONCILE_SOURCE');
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$campaign='m16_20261025_recovery';
$token='m16_recovery_token_20261025';
$before=$ledger->counters($campaign);
if($before['used']||$before['held'])
 throw new RuntimeException('M16_RECOVERY_ALREADY_EXECUTED');
$r=$ledger->reserve($campaign,$token,1);
if(!$r['accepted']||$r['status']!=='reserved')throw new RuntimeException('NO_RESERVED_SLOT');
WC()->customer->set_shipping_country('US');
WC()->customer->set_billing_country('US');
WC()->customer->set_shipping_state('CA');
WC()->customer->set_billing_state('CA');
WC()->customer->set_shipping_postcode('90001');
WC()->session->set('chosen_shipping_methods',[]);
$cart=WC()->cart;$cart->empty_cart();$cart->add_to_cart(27,1);
$cart->calculate_shipping();
$rates=WC()->shipping()->get_packages()[0]['rates']??[];
WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
$cart->calculate_totals();
$id=WC()->checkout()->create_order([
'billing_first_name'=>'M16','billing_last_name'=>'Recovery Local',
'billing_email'=>'m16-recovery@example.invalid',
'billing_country'=>'US','billing_state'=>'CA','billing_postcode'=>'90001',
'billing_city'=>'Test','billing_address_1'=>'1 Local Test',
'shipping_first_name'=>'M16','shipping_last_name'=>'Recovery',
'shipping_country'=>'US','shipping_state'=>'CA','shipping_postcode'=>'90001',
'shipping_city'=>'Test','shipping_address_1'=>'1 Local Test','payment_method'=>'cod']);
if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
$o=wc_get_order($id);
if(!$o||$o->get_currency()!=='USD'||$o->get_transaction_id()!=='')
 throw new RuntimeException('REAL_WOO_ORDER_BOUNDARY');
$o->update_meta_data('_aicc_m16_test_campaign',$campaign);
$o->update_meta_data('_aicc_m16_test_claim',$token);
$o->save();
// Crash boundary: order exists, audit metadata is durable, but reservation
// has NOT been committed yet.
$held=$ledger->counters($campaign);
if($held!==['max'=>1,'used'=>0,'held'=>1])throw new RuntimeException('RECOVERY_START_BALANCE');
$negative=false;
try{
 Andy_Commerce_M16_Order_Cap_Reconcile::confirm($ledger,$campaign,'wrong_claim_20261025',$id);
}catch(RuntimeException $e){$negative=true;}
if(!$negative||$ledger->counters($campaign)!==$held)
 throw new RuntimeException('UNTRUSTED_RECOVERY_MUTATED_QUOTA');
$one=Andy_Commerce_M16_Order_Cap_Reconcile::confirm($ledger,$campaign,$token,$id);
$two=Andy_Commerce_M16_Order_Cap_Reconcile::confirm($ledger,$campaign,$token,$id);
if($one['idempotent']||!$two['idempotent']
 ||$ledger->counters($campaign)!==['max'=>1,'used'=>1,'held'=>0])
 throw new RuntimeException('RECOVERY_NOT_IDEMPOTENT');
$extra=$ledger->reserve($campaign,'m16_recovery_second_token_20261025',1);
if($extra['accepted'])throw new RuntimeException('RECOVERY_CAP_EXCEEDED');
echo json_encode(['mode'=>'M16_POST_SAVE_RECOVERY_PASS','order_id'=>$id,
 'before'=>$held,'after'=>$ledger->counters($campaign),'first_recovery'=>$one,
 'replayed'=>$two,'wrong_token_rejected'=>true,'additional_sale_rejected'=>true,
 'external_money_moved'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
