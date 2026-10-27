<?php
/** P6-D one-use native Woo taxable shipping, coupon, Checkout POST; COD only. */
if (!defined('ABSPATH') || DB_NAME!=='commerce_p5_isolated'
 || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
 || !defined('AICC_CURRENCY_RUNTIME_SANDBOX') || AICC_CURRENCY_RUNTIME_SANDBOX!==true
 || !Andy_Commerce_Currency_Runtime_C1::allowed()) {
 throw new RuntimeException('P6D requires disposable WordPress CI only');
}
$check=static function($condition,$msg){if(!$condition)throw new RuntimeException('P6D '.$msg);};
if(function_exists('wc_load_cart'))wc_load_cart();
$check(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'CAD session');
update_option('woocommerce_calc_taxes','yes');
update_option('woocommerce_prices_include_tax','no');
update_option('woocommerce_shipping_tax_class','inherit');
update_option('woocommerce_default_country','US:CA');
WC_Tax::_insert_tax_rate([
 'tax_rate_country'=>'US','tax_rate_state'=>'CA','tax_rate'=>'8.0000',
 'tax_rate_name'=>'P6D_CA8','tax_rate_priority'=>1,'tax_rate_compound'=>0,
 'tax_rate_shipping'=>1,'tax_rate_order'=>1,'tax_rate_class'=>'',
]);
$zone=new WC_Shipping_Zone();
$zone->set_zone_name('P6D disposable US CA');
$zone->set_zone_order(0);
$zone->set_zone_locations([(object)['code'=>'US:CA','type'=>'state']]);
$zone->save();
$instance=$zone->add_shipping_method('flat_rate');
$check($instance>0,'shipping instance');
update_option('woocommerce_flat_rate_'.$instance.'_settings',[
 'enabled'=>'yes','title'=>'P6D taxable shipping','cost'=>'10.00',
 'tax_status'=>'taxable'],false);
$method=new WC_Shipping_Flat_Rate($instance);
$check((string)$method->get_option('cost')==='10.00','base USD shipping setting');
$product=new WC_Product_Simple();
$product->set_name('P6D original USD taxable product');$product->set_status('publish');
$product->set_virtual(false);$product->set_manage_stock(false);
$product->set_tax_status('taxable');$product->set_regular_price('50.00');
$product->set_price('50.00');$pid=$product->save();
$coupon=new WC_Coupon();$coupon->set_code('p6d-taxable-usd-five');
$coupon->set_discount_type('fixed_cart');$coupon->set_individual_use(true);
$coupon->set_amount('5.00');$coupon->save();
WC()->cart->empty_cart();
foreach(['set_billing_country','set_shipping_country'] as $method)WC()->customer->$method('US');
foreach(['set_billing_state','set_shipping_state'] as $method)WC()->customer->$method('CA');
foreach(['set_billing_postcode','set_shipping_postcode'] as $method)WC()->customer->$method('90001');
foreach(['set_billing_city','set_shipping_city'] as $method)WC()->customer->$method('Los Angeles');
WC()->customer->save();
$check((bool)WC()->cart->add_to_cart($pid,1),'cart add');
$check((bool)WC()->cart->apply_coupon($coupon->get_code()),'cart coupon');
WC()->session->set('chosen_shipping_methods',['flat_rate:'.$instance]);
WC()->cart->calculate_shipping();
WC()->cart->calculate_totals();
$check(get_woocommerce_currency()==='CAD','selected native CAD');
$check(abs((float)WC()->cart->get_shipping_total()-13.50)<0.001,'USD10->CAD13.50');
$check(abs((float)WC()->cart->get_shipping_tax()-1.08)<0.001,'shipping tax CAD1.08');
$check(abs((float)WC()->cart->get_total('edit')-80.19)<0.001,'taxable native cart CAD80.19');
$check((float)wc_get_product($pid)->get_price('edit')===50.0,'raw USD source unchanged');
echo "R13_P6D_TAXABLE_NATIVE_CART_CAD8019_PASS\n";
update_option('woocommerce_cod_settings',[
 'enabled'=>'yes','title'=>'P6D Offline COD','description'=>'No provider or funds'],false);
add_filter('woocommerce_available_payment_gateways',static function($gateways){
 $cod=new WC_Gateway_COD();$cod->enabled='yes';return ['cod'=>$cod];
},999,1);
$address=[
 'first_name'=>'Disposable','last_name'=>'Sandbox','company'=>'',
 'address_1'=>'1 Fake Lane','address_2'=>'','city'=>'Los Angeles',
 'state'=>'CA','postcode'=>'90001','country'=>'US',
 'email'=>'p6d-nobody@example.invalid','phone'=>'',
];
$req=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req->set_header('Nonce',wp_create_nonce('wc_store_api'));
$req->set_header('Content-Type','application/json');
$req->set_param('payment_method','cod');
$req->set_param('billing_address',$address);
$req->set_param('shipping_address',$address);
$req->set_param('expected_total','8019');
$resp=rest_do_request($req);
$d=json_decode(wp_json_encode($resp->get_data()),true);
$check($resp->get_status()===200,
 'Checkout POST http='.$resp->get_status().' code='.($d['code']??'none'));
$o=wc_get_order((int)($d['order_id']??0));
$check($o instanceof WC_Order,'native order exists');
$fx=$o->get_meta('_aicc_currency_runtime_fx_v1',true);
$check(is_array($fx) && isset($fx['r13_dual_ledger']),'P6D immutable FX recorded');
$ledger=$fx['r13_dual_ledger'];
$check($o->get_currency()==='CAD'&&abs((float)$o->get_total()-80.19)<0.001,
 'native order CAD80.19');
$check(($fx['usd_subtotal_minor']??null)===5940
  && ($fx['native_woo_display_minor']??null)===8019
  && ($ledger['components']['tax']['usd_minor']??null)===440
  && ($ledger['components']['shipping']['usd_minor']??null)===1000
  && ($ledger['components']['shipping']['native_local_minor']??null)===1350
  && ($ledger['components']['tax']['native_local_minor']??null)===594,
  'trusted native components');
$check(($ledger['payment_executed']??null)===false
  && ($ledger['provider_settlement_verified']??null)===false,'no provider assertion');
$locked=$fx;
do_action('woocommerce_store_api_checkout_order_processed',$o);
$check(wc_get_order($o->get_id())->get_meta('_aicc_currency_runtime_fx_v1',true)===$locked,
 'repeat checkout snapshot immutable');
echo 'R13_P6D_NATIVE_BLOCKS_POST_TAX_SHIPPING_LOCKED_DUAL_LEDGER_PASS order='.$o->get_id()."\n";

/* P6F two distinct taxable products + same tax class + coupon allocation. */
$second=new WC_Product_Simple();
$second->set_name('P6F second USD taxable SKU');$second->set_status('publish');
$second->set_virtual(false);$second->set_manage_stock(false);
$second->set_tax_status('taxable');$second->set_regular_price('20.00');$second->set_price('20.00');
$secondId=$second->save();
WC()->cart->empty_cart();
$check((bool)WC()->cart->add_to_cart($pid,1),'P6F first product');
$check((bool)WC()->cart->add_to_cart($secondId,1),'P6F second product');
$check((bool)WC()->cart->apply_coupon($coupon->get_code()),'P6F coupon');
WC()->session->set('chosen_shipping_methods',['flat_rate:'.$instance]);
WC()->cart->calculate_shipping();WC()->cart->calculate_totals();
$check(abs((float)WC()->cart->get_total('edit')-109.35)<0.001,'P6F two item native CAD109.35');
$req2=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req2->set_header('Nonce',wp_create_nonce('wc_store_api'));
$req2->set_param('payment_method','cod');
$req2->set_param('billing_address',$address);
$req2->set_param('shipping_address',$address);
$req2->set_param('expected_total','10935');
$res2=rest_do_request($req2);
$body2=(array)$res2->get_data();
$check($res2->get_status()===200,'P6F two item native checkout HTTP '.$res2->get_status().' CODE '.($body2['code']??'none'));
$order2=wc_get_order((int)($body2['order_id']??0));
$check($order2 instanceof WC_Order,'P6F second order persisted');
$fx2=$order2->get_meta('_aicc_currency_runtime_fx_v1',true);
$check(is_array($fx2)&&($fx2['usd_subtotal_minor']??null)===8100
  &&($fx2['native_woo_display_minor']??null)===10935,
  'P6F source USD81 to CAD109.35 locked');
$check(count($fx2['verified_source_lines']??[])===2
  &&array_sum($fx2['source_coupon_allocation_minor']??[])===500
  &&($fx2['source_coupon_allocation_minor'][$pid]??null)===250
  &&($fx2['source_coupon_allocation_minor'][$secondId]??null)===250
  &&($fx2['verified_source_lines'][$pid]['native_discount_minor']??null)===338
  &&($fx2['verified_source_lines'][$secondId]['native_discount_minor']??null)===337
  &&($fx2['r13_dual_ledger']['components']['tax']['usd_minor']??null)===600
  &&($fx2['r13_dual_ledger']['components']['tax']['native_local_minor']??null)===810,
  'P6F coupon allocation and native tax source');
echo "R13_P6F_TWO_SKU_COUPON_TAX_SHIPPING_NATIVE_ORDER_PASS id=".$order2->get_id()."\n";

/* Mixed taxable + exempt: must reject BEFORE any gateway call, not silently omit FX. */
$exempt=new WC_Product_Simple();
$exempt->set_name('P6F mixed exempt unsafe tax');$exempt->set_status('publish');
$exempt->set_virtual(false);$exempt->set_manage_stock(false);
$exempt->set_tax_status('none');$exempt->set_regular_price('20.00');$exempt->set_price('20.00');
$exemptId=$exempt->save();
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
