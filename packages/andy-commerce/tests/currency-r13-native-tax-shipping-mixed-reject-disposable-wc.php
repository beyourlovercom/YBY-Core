<?php
/** Follow-up independent WP CLI process: distinct Woo Store API route and session. */
if (!defined('ABSPATH') || DB_NAME!=='commerce_p5_isolated'
 || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
 || !Andy_Commerce_Currency_Runtime_C1::allowed()) throw new RuntimeException('P6F disposable only');
$check=static function($yes,$msg){if(!$yes)throw new RuntimeException('P6F '.$msg);};
if(function_exists('wc_load_cart'))wc_load_cart();
$check(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'currency session');
$pid=(int)get_option('aicc_p6f_ci_primary_sku',0);
$instance=(int)get_option('aicc_p6f_ci_shipping_instance',0);
$coupon=new WC_Coupon('p6d-taxable-usd-five');
$check($pid>0 && $instance>0 && $coupon->get_id()>0,'stored isolated fixture');
foreach(['set_billing_country','set_shipping_country'] as $m)WC()->customer->$m('US');
foreach(['set_billing_state','set_shipping_state'] as $m)WC()->customer->$m('CA');
foreach(['set_billing_postcode','set_shipping_postcode'] as $m)WC()->customer->$m('90001');
foreach(['set_billing_city','set_shipping_city'] as $m)WC()->customer->$m('Los Angeles');
WC()->customer->save();
$address=['first_name'=>'Disposable','last_name'=>'Sandbox','company'=>'',
 'address_1'=>'1 Fake Lane','address_2'=>'','city'=>'Los Angeles',
 'state'=>'CA','postcode'=>'90001','country'=>'US',
 'email'=>'p6d-nobody@example.invalid','phone'=>''];
add_filter('woocommerce_available_payment_gateways',static function($gateways){
 $cod=new WC_Gateway_COD();$cod->enabled='yes';return ['cod'=>$cod];
},999,1);

/* Mixed taxable + exempt: must reject BEFORE any gateway call, not silently omit FX. */
$exempt=new WC_Product_Simple();
$exempt->set_name('P6F mixed exempt unsafe tax');$exempt->set_status('publish');
$exempt->set_virtual(false);$exempt->set_manage_stock(false);
$exempt->set_tax_status('none');$exempt->set_regular_price('20.00');$exempt->set_price('20.00');
$exemptId=$exempt->save();
WC()->session->set('store_api_draft_order',0);
WC()->session->set('order_awaiting_payment',0);
WC()->cart->empty_cart();
$check((bool)WC()->cart->add_to_cart($pid,1),'P6F mixed taxable item');
$check((bool)WC()->cart->add_to_cart($exemptId,1),'P6F mixed exempt item');
$check((bool)WC()->cart->apply_coupon($coupon->get_code()),'P6F mixed coupon');
WC()->session->set('chosen_shipping_methods',['flat_rate:'.$instance]);
WC()->cart->calculate_shipping();WC()->cart->calculate_totals();
$rejectReq=new WP_REST_Request('POST','/wc/store/v1/checkout');
$rejectReq->set_header('Nonce',wp_create_nonce('wc_store_api'));
$rejectReq->set_param('payment_method','cod');
$rejectReq->set_param('billing_address',$address);
$rejectReq->set_param('shipping_address',$address);
$badResponse=rest_do_request($rejectReq);
$bad=(array)$badResponse->get_data();
$check($badResponse->get_status()===422
 && ($bad['code']??null)==='aicc_currency_unreconciled',
 'P6F mixed taxable/exempt checkout rejects before gateway');
echo "R13_P6F_MIXED_TAX_FOREIGN_CHECKOUT_DENIED_BEFORE_PAYMENT_PASS\n";
