<?php
/**
 * R11-R13: Disposable WordPress 7/Woo 11.2, no real external gateways.
 * Verifies Classic, Blocks pre-payment event, immutable FX and partial→
 * cumulative→full refund ledger in a throwaway native Woo database.
 * The Blocks event proof is NOT the HTTP Store API checkout POST/payment.
 */
if (!defined('ABSPATH') || DB_NAME!=='commerce_p5_isolated'
    || get_option('siteurl')!=='http://commerce-p5.invalid'
    || !defined('AICC_CURRENCY_RUNTIME_SANDBOX')
    || AICC_CURRENCY_RUNTIME_SANDBOX!==true
    || !Andy_Commerce_Currency_Runtime_C1::allowed())
    throw new RuntimeException('R11-R13 disposable sandbox only');
$assert=static function($pass,$what){if(!$pass)throw new RuntimeException('R11-R13: '.$what);};
if(function_exists('wc_load_cart'))wc_load_cart();
$assert(WC()->session!==null&&WC()->cart instanceof WC_Cart,'Woo native session and cart');
$assert(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'lock CAD synthetic rate');
$p=new WC_Product_Simple();$p->set_name('R11-R13 isolated synthetic USD');
$p->set_status('publish');$p->set_virtual(true);
$p->set_regular_price('12.50');$p->set_price('12.50');$id=$p->save();
$cp=new WC_Coupon();$cp->set_code('aicc-r11-synthetic-five');
$cp->set_discount_type('fixed_cart');$cp->set_amount('5.00');$cp->save();
WC()->cart->empty_cart();
$assert((bool)WC()->cart->add_to_cart($id,2),'add product');
$assert((bool)WC()->cart->apply_coupon('aicc-r11-synthetic-five'),'fixed USD coupon');
WC()->cart->calculate_totals();
$assert(abs((float)WC()->cart->get_total('edit')-27.01)<0.001,'CAD native Woo total');
$assert((float)wc_get_product($id)->get_price('edit')===12.50,'persisted USD product is immutable');
// R11 Classic event occurs before Woo persists the order.
$classicalId=WC()->checkout()->create_order([
    'payment_method'=>'cod','billing_first_name'=>'Sandbox',
    'billing_last_name'=>'Classic','billing_email'=>'sandbox@example.invalid',
    'billing_country'=>'US'
]);
if(is_wp_error($classicalId))throw new RuntimeException('R11 checkout failed: '.$classicalId->get_error_message());
$classical=wc_get_order($classicalId);
$saved=$classical->get_meta('_aicc_currency_runtime_fx_v1',true);
$assert(is_array($saved)&&$saved['display_currency']==='CAD'
    && $saved['usd_subtotal_minor']===2000
    && $saved['native_woo_display_minor']===2701
    && $saved['rounding_adjustment_display_minor']===1,
    'Classic immutable FX snapshot survives native Woo order persistence');
$assert($saved['fx_snapshot']['source']==='CI_SYNTHETIC_LOCKED'
    && $saved['fx_snapshot']['rate_micros']===1350000
    && $classical->get_currency()==='CAD','original FX and CAD order currency locked');
$assert(abs((float)$classical->get_total()-27.01)<0.001,'native order/cart total parity');
echo 'R11_CLASSIC_NATIVE_WOO_ORDER_IMMUTABLE_FX_PASS'.PHP_EOL;
// Store API Blocks fires woocommerce_store_api_checkout_order_processed BEFORE
// gateway charge; prove the hook writes metadata on saved WC_Order.
// This is event-level compatibility, not a provider Checkout Blocks POST.
$blocks=wc_create_order(['status'=>'pending']);
$blocks->set_total('27.01');
do_action('woocommerce_store_api_checkout_order_processed',$blocks);
$blocks=wc_get_order($blocks->get_id());
$blockFx=$blocks->get_meta('_aicc_currency_runtime_fx_v1',true);
$assert(is_array($blockFx)&&$blockFx['usd_subtotal_minor']===2000
    && $blockFx['display_currency']==='CAD'
    && $blocks->get_currency()==='CAD','Blocks prepayment action saves ledger');
$assert($blockFx['fx_snapshot']===$saved['fx_snapshot'],'Classic and Blocks fixed FX evidence equal');
do_action('woocommerce_store_api_checkout_order_processed',$blocks);
$assert($blocks->get_meta('_aicc_currency_runtime_fx_v1',true)===$blockFx,
    'Blocks event idempotent; no FX snapshot rewrite');
echo 'R11_BLOCKS_NATIVE_PREPAYMENT_HOOK_IMMUTABLE_FX_PASS'.PHP_EOL;
// Native WP Store API checkout GET: read-only preview, no order/payment.
$checkoutRequest=new WP_REST_Request('GET','/wc/store/v1/checkout');
$checkoutRequest->set_header('Nonce',wp_create_nonce('wc_store_api'));
$checkoutGet=rest_do_request($checkoutRequest);
$checkoutDiagnostics=json_decode(wp_json_encode($checkoutGet->get_data()),true);
echo 'R11_STORE_API_GET_DIAGNOSTIC status='.$checkoutGet->get_status()
    .' code='.sanitize_key((string)($checkoutDiagnostics['code']??'none')).PHP_EOL;
$assert($checkoutGet instanceof WP_REST_Response
    && $checkoutGet->get_status()===200,'Store API GET checkout native nonpaying preview');
$get=$checkoutDiagnostics;
$assert(is_array($get),'native Checkout Blocks GET response');
echo 'R11_NATIVE_STORE_API_CHECKOUT_GET_NONPAYING_PASS'.PHP_EOL;
// Reject pretending mixed tax or shipping has already been reconciled to USD.
// Product and coupon USD core remain known; tax/shipping need separate rules.
$taxGuard=wc_create_order(['status'=>'pending']);
WC()->cart->set_total_tax(1.00);
Andy_Commerce_Currency_Runtime_C6::checkout_snapshot($taxGuard,[]);
$assert(!is_array($taxGuard->get_meta('_aicc_currency_runtime_fx_v1',true)),
    'unsupported nonzero tax fails closed');
WC()->cart->set_total_tax(0);
$shippingGuard=wc_create_order(['status'=>'pending']);
WC()->cart->set_shipping_total(10.00);
Andy_Commerce_Currency_Runtime_C6::checkout_snapshot($shippingGuard,[]);
$assert(!is_array($shippingGuard->get_meta('_aicc_currency_runtime_fx_v1',true)),
    'unsupported nonzero shipping fails closed');
WC()->cart->set_shipping_total(0);
echo 'R11_MIXED_TAX_SHIPPING_SNAPSHOT_FAIL_CLOSED_PASS'.PHP_EOL;
// R12: cumulative partial refunds must reverse exact original USD, avoiding
// per-refund rounding residue. Never call refund_payment gateways.
$refunds=[5.00,5.00,17.01];
$baseReversed=0;
$displayReversed=0;
foreach($refunds as $idx=>$amount){
    $refund=wc_create_refund([
        'amount'=>$amount,'reason'=>'Sandbox isolated FX refund '.$idx,
        'order_id'=>$classicalId,'refund_payment'=>false,'restock_items'=>false
    ]);
    if(is_wp_error($refund))throw new RuntimeException('R12 native nonpaying refund: '.$refund->get_error_message());
    $evidence=Andy_Commerce_Currency_Runtime_C6::refund_evidence($refund,$classical);
    $baseReversed+=$evidence['reversed_usd_minor'];
    $displayReversed+=$evidence['refund_display_minor'];
    $assert($evidence['cumulative_display_minor']===$displayReversed
        && $evidence['cumulative_reversed_usd_minor']===$baseReversed,
        'cumulative USD/display ledger exact after refund '.$idx);
    $assert($evidence['original_rate_micros']===1350000
        && $evidence['no_gateway_refund']===true,'immutable locked FX / no gateway');
}
$assert($displayReversed===2701 && $baseReversed===2000,
    'partial+partial+remaining full refund exactly reverses both ledger totals');
$assert(abs((float)$classical->get_total_refunded()-27.01)<0.001,
    'native Woo recorded full cumulative refunds');
echo 'R12_THREE_NATIVE_WOO_NON_GATEWAY_REFUNDS_EXACT_FX_PASS'.PHP_EOL;
// R13: paid gateway is never invoked; no change to Marketing drafts or WWW.
// Unsafe refund exceeding the original display ledger must throw.
$manualRefund=new WC_Order_Refund();
$manualRefund->set_parent_id($classicalId);
$manualRefund->set_amount('0.01');
$denied=false;
try{Andy_Commerce_Currency_Runtime_C6::refund_evidence($manualRefund,$classical);}
catch(InvalidArgumentException $e){$denied=true;}
$assert($denied,'over-original cumulative refund rejected');
$assert(get_option('andy_commerce_m7_inactive_drafts_v1',[])===[],'Marketing OFF');
echo 'R13_REFUND_OVERFLOW_DENIED_AND_MARKETING_OFF_PASS'.PHP_EOL;
