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

/* P6F two distinct taxable products + same tax class + coupon allocation. */
$second=new WC_Product_Simple();
$second->set_name('P6F second USD taxable SKU');$second->set_status('publish');
$second->set_virtual(false);$second->set_manage_stock(false);
$second->set_tax_status('taxable');$second->set_regular_price('20.00');$second->set_price('20.00');
$secondId=$second->save();
// A new shopper/order must not inherit the previous Store API draft order.
WC()->session->set('store_api_draft_order',0);
WC()->session->set('order_awaiting_payment',0);
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
if($res2->get_status()!==200){
 echo 'P6F_DIAG_CART='.wp_json_encode([
    'currency'=>get_woocommerce_currency(),'total'=>WC()->cart->get_total('edit'),
    'shipping'=>WC()->cart->get_shipping_total(),'tax'=>WC()->cart->get_total_tax(),
    'discount'=>WC()->cart->get_discount_total(),'count'=>count(WC()->cart->get_cart()),
    'coupons'=>WC()->cart->get_applied_coupons(),
 ]).PHP_EOL;
 $latest=wc_get_orders(['limit'=>1,'orderby'=>'ID','order'=>'DESC','return'=>'objects']);
 foreach($latest as $diagOrder){
    echo 'P6F_DIAG_ORDER='.wp_json_encode([
        'id'=>$diagOrder->get_id(),'currency'=>$diagOrder->get_currency(),
        'total'=>$diagOrder->get_total(),'discount'=>$diagOrder->get_discount_total(),
        'shipping'=>$diagOrder->get_shipping_total(),'tax'=>$diagOrder->get_total_tax(),
        'fx'=>is_array($diagOrder->get_meta('_aicc_currency_runtime_fx_v1',true)),
        'items'=>array_map(static function($i){return ['id'=>$i->get_product_id(),'qty'=>$i->get_quantity(),
             'subtotal'=>$i->get_subtotal(),'total'=>$i->get_total()];},array_values($diagOrder->get_items('line_item'))),
    ]).PHP_EOL;
 }
}
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
