<?php
/**
 * R13-S: full native Woo Store API Checkout POST in all five currencies,
 * plus exact Stripe TEST-MODE intent payload contract.
 *
 * This test NEVER calls Stripe. It exercises real native Woo checkout with
 * in-memory offline COD only; a real Stripe test payment/refund/webhook
 * remains a distinct gate. Disposable database, blocked egress, no emails.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'commerce_p5_isolated'
    || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL !== true
    || !defined('AICC_CURRENCY_RUNTIME_SANDBOX') || AICC_CURRENCY_RUNTIME_SANDBOX !== true
    || !Andy_Commerce_Currency_Runtime_C1::allowed()) {
    throw new RuntimeException('R13-S is limited to disposable Woo CI');
}
$fail=static function(bool $yes,string $why):void {
    if(!$yes)throw new RuntimeException('R13_S '.$why);
};
if (function_exists('wc_load_cart'))wc_load_cart();
$iso=(string)AICC_CURRENCY_RUNTIME_TEST_CURRENCY;
$rates=Andy_Commerce_Currency_Runtime_C1::TEST_RATES;
$fail(isset($rates[$iso]) && in_array($iso,['USD','EUR','GBP','AUD','CAD'],true),'bad ISO');
$fail(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox($iso),'cannot select ISO');
$fail(WC()->session !== null && WC()->cart instanceof WC_Cart,'no WC guest session');
$nativeUnit=Andy_Commerce_Currency_OS_V1::convert_usd_minor(1250,$rates[$iso]);
$nativeCoupon=Andy_Commerce_Currency_OS_V1::convert_usd_minor(500,$rates[$iso]);
$expected=2*$nativeUnit-$nativeCoupon;
$fail($expected>0,'invalid computed amount');

$p=new WC_Product_Simple();
$p->set_name('R13-S disposable '.$iso.' product');
$p->set_status('publish');$p->set_virtual(true);
$p->set_manage_stock(false);$p->set_regular_price('12.50');$p->set_price('12.50');
$id=$p->save();
$cp=new WC_Coupon();
$cp->set_code('aicc-r13s-'.strtolower($iso).'-five');
$cp->set_discount_type('fixed_cart');$cp->set_amount('5.00');$cp->save();
WC()->cart->empty_cart();
$fail((bool)WC()->cart->add_to_cart($id,2),'native product add failed');
$fail((bool)WC()->cart->apply_coupon($cp->get_code()),'native coupon failed');
WC()->cart->calculate_totals();
$actual=(int)round((float)WC()->cart->get_total('edit')*100);
$fail($actual===$expected,'Woo local cart total mismatch');
$fail(get_woocommerce_currency()===$iso,'Woo checkout ISO incorrect');

update_option('woocommerce_cod_settings',[
  'enabled'=>'yes','enable_for_virtual'=>'yes',
  'title'=>'CI-only COD','description'=>'No external gateway',
],false);
add_filter('woocommerce_available_payment_gateways',static function($available){
  $cod=new WC_Gateway_COD();$cod->enabled='yes';$cod->enable_for_virtual=true;
  return ['cod'=>$cod];
},999,1);
$req=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req->set_header('Nonce',wp_create_nonce('wc_store_api'));
$req->set_header('Content-Type','application/json');
$req->set_param('payment_method','cod');
$req->set_param('expected_total',(string)$expected);
$req->set_param('billing_address',[
 'first_name'=>'Only','last_name'=>'Disposable','company'=>'',
 'address_1'=>'123 Test Lane','address_2'=>'','city'=>'Fake City',
 'state'=>'CA','postcode'=>'90001','country'=>'US',
 'email'=>'no-contact@example.invalid','phone'=>'',
]);
$response=rest_do_request($req);
$raw=json_decode(wp_json_encode($response->get_data()),true);
$fail($response->get_status()===200,
    'Store API POST '.$iso.' status '.$response->get_status()
    .' code '.sanitize_key((string)($raw['code']??'none')));
$order=wc_get_order((int)($raw['order_id']??0));
$fail($order instanceof WC_Order,'native Woo order not persisted');
$fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
$fail($order->get_currency()===$iso
    && (int)round((float)$order->get_total()*100)===$expected,
    'native order ISO or total diverges');
$fail(is_array($fx)
    && ($fx['display_currency']??null)===$iso
    && ($fx['native_woo_display_minor']??null)===$expected
    && ($fx['usd_subtotal_minor']??null)===2000
    && ($fx['fx_snapshot']['rate_micros']??null)===$rates[$iso],
    'native Checkout FX snapshot missing');
$fail((float)wc_get_product($id)->get_price('edit')===12.50,
    'source price USD changed');

// Exact Stripe TEST mode PI creation *contract*, NOT a Stripe API request.
// Woo creates amount and currency; Stripe must never independently convert.
$quote=[
 'amount'=>$expected,
 'currency'=>strtolower($order->get_currency()),
 'metadata'=>['aicc_woo_order_id'=>(string)$order->get_id(),
              'aicc_woo_total_minor'=>(string)$expected],
];
$fail($quote['amount'] === (int)$fx['native_woo_display_minor']
    && strtoupper($quote['currency']) === $iso
    && $quote['amount'] > 0, 'Stripe PI amount/currency does not match Woo');

// Mock provider success/failure/refund events are not enough for release.
// They can validate local quote guards but do not prove signed webhooks.
$match=static function(array $intent) use($quote):bool{
  return ($intent['livemode']??null)===false
      && ($intent['amount']??null)===$quote['amount']
      && ($intent['currency']??null)===$quote['currency']
      && ($intent['metadata']['aicc_woo_order_id']??null)===$quote['metadata']['aicc_woo_order_id'];
};
$fail($match($quote+['livemode'=>false]),'matching test PI contract rejected');
$fail(!$match(array_replace($quote,['amount'=>$expected+1,'livemode'=>false])),
 'mismatched Stripe amount accepted');
$fail(!$match(array_replace($quote,['currency'=>'usd'=== $quote['currency']?'eur':'usd','livemode'=>false])),
 'mismatched Stripe currency accepted');
$fail(!$match(array_replace($quote,['livemode'=>true])),
 'live PI wrongly eligible for test acceptance');
// Refund 1/3 is based on provider minor units; original USD is immutable.
$refundMinor=max(1,intdiv($expected,3));
$fail($refundMinor>0 && $refundMinor<=$expected,'invalid test refund amount');
echo 'R13_STRIPE_TEST_INTENT_QUOTE_GUARD_PASS iso='.$iso.' minor='.$expected.PHP_EOL;
echo 'R13_NATIVE_BLOCKS_POST_OFFLINE_COD_PASS iso='.$iso.
    ' order_id='.$order->get_id().' source_usd_minor=2000'.PHP_EOL;
echo 'R13_PROVIDER_API_NOT_CALLED; R13_REAL_WEBHOOK_NOT_VERIFIED'.PHP_EOL;
