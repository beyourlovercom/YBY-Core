<?php
/**
 * C-R4..R7 scope-limited native Woo runtime integration, no external payment.
 * Must run ONLY in one-use GHA WordPress/MySQL with sandbox flag.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'commerce_p5_isolated'
    || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !defined('AICC_CURRENCY_RUNTIME_SANDBOX') || AICC_CURRENCY_RUNTIME_SANDBOX !== true
    || !Andy_Commerce_Currency_Runtime_C1::allowed()) {
    throw new RuntimeException('Currency end-to-end sandbox guard not satisfied');
}
$verify=static function($ok,$label){ if(!$ok) throw new RuntimeException('Currency runtime: '.$label); };
if (function_exists('wc_load_cart')) wc_load_cart();
$verify(WC()->cart instanceof WC_Cart && WC()->session!==null,'Woo cart and session');
$verify(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'session select CAD');
$verify(get_woocommerce_currency()==='CAD','Woo currency CAD');
$verify(!Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('JPY'),'unsupported currency must fail closed');
$verify(get_woocommerce_currency()==='CAD','invalid request cannot mutate session');
// C-R5: actual registered WordPress REST handler and nonce authorization.
$bad=new WP_REST_Request('POST','/aicc-currency-test/v1/select');
$bad->set_param('currency','EUR');
$badResp=rest_do_request($bad);
$verify($badResp instanceof WP_REST_Response && $badResp->get_status()>=400,'unauthenticated currency request denied');
$good=new WP_REST_Request('POST','/aicc-currency-test/v1/select');
$good->set_header('X-AICC-Nonce',wp_create_nonce('aicc_currency_test_selection'));
$good->set_param('currency','EUR');
$goodResp=rest_do_request($good);
$verify($goodResp instanceof WP_REST_Response && $goodResp->get_status()===200,'nonce-verified currency session change');
$verify(get_woocommerce_currency()==='EUR','Woo session switched to EUR');
$verify(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'switch back to CAD');
echo 'C_R5_NATIVE_WOO_SESSION_REST_NONCE_PASS'.PHP_EOL;
// C-R4 native Woo product + coupon tax and flat-rate price.
$p=new WC_Product_Simple();
$p->set_name('C-R4 fixed coupon CAD virtual test');
$p->set_status('publish');$p->set_virtual(true);
$p->set_regular_price('12.50');$p->set_price('12.50');
$id=$p->save();
$cp=new WC_Coupon();
$cp->set_code('aicc-currency-fixed-five');
$cp->set_discount_type('fixed_cart');
$cp->set_amount('5.00');
$cp->save();
$verify(abs((float)$cp->get_amount('edit')-5.0)<0.001,'raw USD coupon unchanged');
$verify(abs((float)$cp->get_amount()-6.75)<0.001,'fixed USD coupon CAD conversion');
$rate=new WC_Shipping_Rate('flat_rate:aicc','synthetic fixed ship','10.00',[],'flat_rate');
$filtered=apply_filters('woocommerce_package_rates',['flat_rate:aicc'=>$rate],[]);
$verify(abs((float)$filtered['flat_rate:aicc']->get_cost()-13.50)<0.001,'Woo native flat shipping CAD');
$filtered=apply_filters('woocommerce_package_rates',$filtered,[]);
$verify(abs((float)$filtered['flat_rate:aicc']->get_cost()-13.50)<0.001,'no double-converted shipping');
// Woo native tax calculator, 5% taxable (separate from the no-tax checkout gate).
$taxes=WC_Tax::calc_tax('27.01',[1=>['rate'=>'5.0000','label'=>'Synthetic 5','compound'=>'no','shipping'=>'yes']],false);
$verify(abs((float)wc_round_tax_total(array_sum($taxes))-1.35)<0.001,'native Woo 5% tax on CAD net');
WC()->cart->empty_cart();
$verify((bool)WC()->cart->add_to_cart($id,2),'Woo cart product add');
$verify(WC()->cart->apply_coupon('aicc-currency-fixed-five'),'native coupon apply');
WC()->cart->calculate_totals();
$verify(abs((float)WC()->cart->get_subtotal()-33.76)<0.001,'native CAD pre-discount merchandise subtotal');
$verify(abs((float)WC()->cart->get_discount_total()-6.75)<0.001,'native fixed coupon CAD amount');
$verify(abs((float)WC()->cart->get_total('edit')-27.01)<0.001,'native coupon cart payable CAD');
$verify(abs((float)WC()->cart->get_shipping_total())<0.001 && abs((float)WC()->cart->get_total_tax())<0.001,'no ambiguous tax/shipping in checkout');
$verify(abs((float)wc_get_product($id)->get_price('edit')-12.50)<0.001,'USD Woo product persisted unchanged');
$verify(abs((float)(new WC_Coupon('aicc-currency-fixed-five'))->get_amount('edit')-5.0)<0.001,'USD coupon persisted unchanged');
echo 'C_R4_NATIVE_WOO_COUPON_SHIPPING_TAX_PASS CAD33.76-6.75=27.01 ship13.50 tax1.35'.PHP_EOL;
// C-R5 Store API cart must agree with Woo's native cart (when supported).
$api=rest_do_request(new WP_REST_Request('GET','/wc/store/v1/cart'));
$verify($api instanceof WP_REST_Response && $api->get_status()===200,'Store API native cart route');
$data=json_decode(wp_json_encode($api->get_data()),true);
$verify(is_array($data),'Store API cart payload');
$totals=$data['totals']??[];
$verify(($totals['currency_code']??null)==='CAD','Store API cart CAD currency');
$verify((string)($totals['total_price']??'')==='2701','Store API cart CAD checkout total');
echo 'C_R5_STORE_API_CART_CURRENCY_TOTAL_PASS CAD27.01'.PHP_EOL;
// C-R6 real native WC_Checkout::create_order, COD-only/no external gateway.
$checkout=WC()->checkout();
$orderId=$checkout->create_order([
    'payment_method'=>'cod',
    'billing_first_name'=>'Test','billing_last_name'=>'Only',
    'billing_email'=>'no-contact@example.invalid','billing_country'=>'US'
]);
if (is_wp_error($orderId)) throw new RuntimeException('Woo checkout order create failed: '.$orderId->get_error_message());
$order=wc_get_order($orderId);
$verify($order instanceof WC_Order,'Woo native checkout saved order');
$verify($order->get_currency()==='CAD','native checkout CAD currency');
$verify(abs((float)$order->get_total()-27.01)<0.001,'order total equals native Woo cart');
$fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
$verify(is_array($fx),'FX snapshot saved on native checkout');
$verify(($fx['base_currency']??'')==='USD' && ($fx['display_currency']??'')==='CAD','native checkout source/display currency');
$verify(($fx['usd_subtotal_minor']??null)===2000,'USD raw merchandise less USD coupon');
$verify(($fx['native_woo_display_minor']??null)===2701,'stored Woo amount CAD cents');
$verify(($fx['rounding_adjustment_display_minor']??null)===1,'CAD cent rounding explicitly stored');
echo 'C_R6_WOO_NATIVE_CHECKOUT_FX_SNAPSHOT_PASS order='.$orderId.' currency=CAD usd_minor=2000 cad_minor=2701'.PHP_EOL;
// C-R7 no gateway refund; do not pretend Stripe/wallet money was returned.
$refund=wc_create_refund([
    'amount'=>5.00, 'reason'=>'One-use isolated local refund',
    'order_id'=>$orderId,'refund_payment'=>false,'restock_items'=>false
]);
if (is_wp_error($refund)) throw new RuntimeException('Non-gateway refund error: '.$refund->get_error_message());
$evidence=Andy_Commerce_Currency_Runtime_C6::refund_evidence($refund,$order);
$verify(($evidence['refund_display_minor']??null)===500,'refund native CAD minor');
$verify(($evidence['reversed_usd_minor']??null)===370,'refund USD reverse original rate');
$verify(($evidence['no_gateway_refund']??false)===true,'no gateway refund enforced');
$verify(($order->get_total_refunded())>=5.0,'Woo refund linked to order');
$verify(get_option('andy_commerce_m7_inactive_drafts_v1',[])===[],'Marketing stays OFF');
echo 'C_R7_NATIVE_WOO_INTERNAL_REFUND_FX_PASS cad_minor=500 usd_reverse_minor=370'.PHP_EOL;
echo 'C_R7_NO_STRIPE_NO_EMAIL_NO_ERP_NO_PRODUCTION_WRITE=PASS'.PHP_EOL;
