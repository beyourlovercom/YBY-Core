<?php
/**
 * M16 local-only usage cap -> persisted Woo HPOS order -> commit / abort release.
 * Not a runtime hook; never place in production installable plugin ZIP.
 */
if (getenv('AICC_ALLOW_M16_LOCAL_WOO_WRITES')!=='1'
 ||home_url()!=='http://127.0.0.1:18973'
 ||DB_NAME!=='aicc_commerce_p5_20261023'
 ||wp_get_environment_type()!=='local'
 ||get_option('woocommerce_custom_orders_table_enabled')!=='yes'
 ||get_option('aicc_m14_sandbox_active')!=='no'
 ||get_woocommerce_currency()!=='USD'
 ||!defined('WP_HTTP_BLOCK_EXTERNAL')||!WP_HTTP_BLOCK_EXTERNAL
 ||!defined('DISABLE_WP_CRON')||!DISABLE_WP_CRON)
  throw new RuntimeException('M16_DENIED_OUTSIDE_DISPOSABLE_HPOS_SANDBOX');
require_once getenv('AICC_M16_LEDGER_SOURCE');
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$campaign='m16_20261025_checkout';
$original=$ledger->counters($campaign);
if($original['used']!==0||$original['held']!==0)
  throw new RuntimeException('M16_ONE_SHOT_FIXTURE_ALREADY_EXISTS');
$cap=2;
$create=function(string $token)use($ledger,$campaign,$cap):array {
 $r=$ledger->reserve($campaign,$token,$cap);
 if(!$r['accepted'])throw new RuntimeException('M16_NO_ELIGIBLE_CAPACITY');
 try {
   WC()->customer->set_shipping_country('US');
   WC()->customer->set_billing_country('US');
   WC()->customer->set_shipping_state('CA');
   WC()->customer->set_billing_state('CA');
   WC()->customer->set_shipping_postcode('90001');
   WC()->session->set('chosen_shipping_methods',[]);
   $cart=WC()->cart;$cart->empty_cart();$cart->add_to_cart(27,2);
   $cart->calculate_shipping();
   $rates=WC()->shipping()->get_packages()[0]['rates']??[];
   if(count($rates)!==1)throw new RuntimeException('M16_WOO_SHIPPING_RATE_MISSING');
   WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
   $cart->calculate_totals();
   $oid=WC()->checkout()->create_order([
    'billing_first_name'=>'M16','billing_last_name'=>'Local Cap Only',
    'billing_email'=>'aicc-m16-test@example.invalid',
    'billing_country'=>'US','billing_state'=>'CA','billing_postcode'=>'90001',
    'billing_city'=>'Test','billing_address_1'=>'1 Sandbox Street',
    'shipping_first_name'=>'M16','shipping_last_name'=>'Local',
    'shipping_country'=>'US','shipping_state'=>'CA','shipping_postcode'=>'90001',
    'shipping_city'=>'Test','shipping_address_1'=>'1 Sandbox Street',
    'payment_method'=>'cod',
   ]);
   if(is_wp_error($oid))throw new RuntimeException($oid->get_error_message());
   $o=wc_get_order($oid);
   if(!$o||$o->get_id()<1||$o->get_currency()!=='USD'
     ||$o->get_payment_method()!=='cod'||$o->get_transaction_id()!=='')
     throw new RuntimeException('M16_WOO_ORDER_NOT_DURABLE');
   $o->update_meta_data('_aicc_m16_test_campaign',$campaign);
   $o->update_meta_data('_aicc_m16_test_claim',$token);
   $o->save();
   // Bound to a verified durable Woo HPOS order ID, never just preview/cart.
   $commit=$ledger->commit($campaign,$token,$o->get_id());
   if($commit['status']!=='committed')throw new RuntimeException('M16_COMMIT_FAILED');
   return ['order_id'=>$o->get_id(),'total'=>$o->get_total(),'claim'=>$token,'committed'=>true];
 } catch(Throwable $e) {
   // A failure after an order is saved requires explicit recovery; do NOT
   // blindly release a claim if an order might have been persisted.
   throw $e;
 }
};
$first=$create('m16_claim_local_first_20261025');
$after1=$ledger->counters($campaign);
if($after1!==['max'=>2,'used'=>1,'held'=>0])throw new RuntimeException('M16_FIRST_COMMIT_COUNTERS');
$abortToken='m16_claim_aborted_20261025';
$held=$ledger->reserve($campaign,$abortToken,$cap);
if(!$held['accepted'])throw new RuntimeException('M16_ABORT_RESERVE_FAILED');
// Simulate failure BEFORE any order creation: release the reservation.
$released=$ledger->release($campaign,$abortToken);
if($released['status']!=='released')throw new RuntimeException('M16_ABORT_RELEASE_FAILED');
$afterAbort=$ledger->counters($campaign);
if($afterAbort!==['max'=>2,'used'=>1,'held'=>0])throw new RuntimeException('M16_ABORT_COUNTERS_LEAK');
$second=$create('m16_claim_local_second_20261025');
$after2=$ledger->counters($campaign);
if($after2!==['max'=>2,'used'=>2,'held'=>0])throw new RuntimeException('M16_SECOND_COMMIT_COUNTERS');
$blocked=$ledger->reserve($campaign,'m16_claim_local_third_20261025',$cap);
if($blocked['accepted']||$blocked['status']!=='exhausted')
 throw new RuntimeException('M16_CAP_EXCEEDED_BY_NEW_ORDER');
foreach([$first,$second] as $item) {
 $o=wc_get_order($item['order_id']);
 if(!$o||$o->get_meta('_aicc_m16_test_campaign')!==$campaign
   ||$o->get_meta('_aicc_m16_test_claim')!==$item['claim'])
   throw new RuntimeException('M16_ORDER_CLAIM_AUDIT_META_MISSING');
}
echo json_encode([
 'mode'=>'M16_LOCAL_WOO_ORDER_ID_CAP_BOUND_PROOF',
 'orders'=>[$first,$second],'final_cap'=>$after2,'aborted_before_order_released'=>true,
 'third_checkout_prevented'=>true,'wp_environment'=>'local','external_payment'=>false,
],JSON_THROW_ON_ERROR),PHP_EOL;
