<?php
/** One disposable Woo Store API request per PHP process, no provider/payment writes. */
if(!defined('ABSPATH')||DB_NAME!=='commerce_p5_isolated'
   ||get_option('siteurl')!=='http://commerce-p5.invalid'
   ||!defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
   ||!Andy_Commerce_Currency_Runtime_C1::allowed())
 throw new RuntimeException('P6F isolated Woo only');
$case=getenv('P6_COMPLEX_CASE');
if(!in_array($case,['mixed','coupons','inclusive','dynamic','deny_shipping_inclusive'],true))
 throw new RuntimeException('Unknown complex Woo contract');
$check=static function(bool $valid,string $name) use($case): void {
 if(!$valid)throw new RuntimeException("P6F ".$case." ".$name);
};
if(function_exists('wc_load_cart'))wc_load_cart();
$check(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'CAD selector');
$sku=(int)get_option('aicc_p6f_ci_primary_sku',0);
$instance=(int)get_option('aicc_p6f_ci_shipping_instance',0);
$check($sku>0&&$instance>0,'existing isolated sku/flat shipping fixture');
if($case==='inclusive')update_option('woocommerce_prices_include_tax','yes');
if($case==='dynamic') {
 $set=get_option('woocommerce_flat_rate_'.$instance.'_settings',[]);
 $check(is_array($set),'flat configuration');
 $set['cost']='7+3*[qty]';
 update_option('woocommerce_flat_rate_'.$instance.'_settings',$set,false);
}
foreach(['set_billing_country','set_shipping_country'] as $m)WC()->customer->$m('US');
foreach(['set_billing_state','set_shipping_state'] as $m)WC()->customer->$m('CA');
foreach(['set_billing_postcode','set_shipping_postcode'] as $m)WC()->customer->$m('90001');
foreach(['set_billing_city','set_shipping_city'] as $m)WC()->customer->$m('Los Angeles');
WC()->customer->save();
WC()->cart->empty_cart();
$check((bool)WC()->cart->add_to_cart($sku,$case==='dynamic'?2:1),'source product');
if(in_array($case,['mixed','coupons'],true)) {
 $extra=new WC_Product_Simple();$extra->set_status('publish');$extra->set_virtual(false);
 $extra->set_name('P6F CI isolated second SKU');$extra->set_manage_stock(false);
 $extra->set_regular_price('20.00');$extra->set_price('20.00');
 $extra->set_tax_status($case==='mixed'?'none':'taxable');
 $check((bool)WC()->cart->add_to_cart($extra->save(),1),'second SKU');
}
$codes=[];
if($case==='coupons') {
 foreach([['p6f-ci-fixed2','fixed_cart','2.00'],['p6f-ci-percent10','percent','10']] as $spec) {
  [$couponCode,$type,$amount]=$spec;
  $c=new WC_Coupon($couponCode);
  if(!$c->get_id()) {$c=new WC_Coupon();$c->set_code($couponCode);
   $c->set_discount_type($type);$c->set_amount($amount);
   $c->set_individual_use(false);$c->save();}
  $codes[]=$couponCode;
 }
} else $codes=['p6d-taxable-usd-five'];
foreach($codes as $c)$check((bool)WC()->cart->apply_coupon($c),'coupon='.$c);
WC()->session->set('chosen_shipping_methods',['flat_rate:'.$instance]);
WC()->cart->calculate_shipping();WC()->cart->calculate_totals();
$currency=get_woocommerce_currency();
$total=(float)WC()->cart->get_total('edit');
$check($currency==='CAD'&&$total>0,'native CAD cart');
if($case==='deny_shipping_inclusive')
 add_filter('woocommerce_shipping_prices_include_tax','__return_true',200);
add_filter('woocommerce_available_payment_gateways',static function($gateways) {
 $cod=new WC_Gateway_COD();$cod->enabled='yes';return ['cod'=>$cod];},999);
$address=['first_name'=>'AICC','last_name'=>'P6F','address_1'=>'1 Test Lane',
 'city'=>'Los Angeles','state'=>'CA','postcode'=>'90001','country'=>'US',
 'email'=>'p6f-no-contact@example.invalid','phone'=>''];
$req=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req->set_header('Nonce',wp_create_nonce('wc_store_api'));
$req->set_param('billing_address',$address);
$req->set_param('shipping_address',$address);
$req->set_param('payment_method','cod');
$req->set_param('expected_total',(string)round($total*100));
$res=rest_do_request($req);
$data=(array)$res->get_data();
if($case==='deny_shipping_inclusive') {
 echo 'P6F_NEGATIVE_DIAG_STATUS='.$res->get_status().' CODE='.($data['code']??'none')
     .' INCLUSIVE='.json_encode(apply_filters('woocommerce_shipping_prices_include_tax',false))
     .' REGISTERED='.json_encode(has_action('woocommerce_store_api_checkout_order_processed',
         ['Andy_Commerce_Currency_R13_Native_Tax_Shipping','require_snapshot'])).PHP_EOL;
 $check($res->get_status()===422
      &&($data['code']??null)==='aicc_currency_unreconciled',
      'untrusted inclusive shipping blocked before gateway');
 echo 'P6F_COMPLEX_UNSUPPORTED_SHIPPING_INCLUSIVE_422_PASS'.PHP_EOL;
 return;
}
$check($res->get_status()===200,'native checkout HTTP '.$res->get_status().' '.($data['code']??'none'));
$order=wc_get_order((int)($data['order_id']??0));
$check($order instanceof WC_Order,'saved native order');
$fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
$check(is_array($fx)&&($fx['display_currency']??null)==='CAD'
 &&($fx['native_woo_display_minor']??null)===(int)round($total*100)
 &&($fx['fx_snapshot']['source']??null)==='CI_SYNTHETIC_LOCKED','immutable source FX');
$expectedSource=['mixed'=>7960,'coupons'=>7668,'inclusive'=>5580,'dynamic'=>11664];
$check(abs($fx['usd_subtotal_minor']-$expectedSource[$case])<=2,'source USD total');
if($case==='mixed')$check(count(array_filter($fx['verified_source_lines'],static fn($l)=>!$l['taxable']))===1,'exempt source SKU');
if($case==='coupons')$check(($fx['r13_dual_ledger']['coupon_count']??null)===2,'two native coupons');
if($case==='inclusive')$check(($fx['is_product_price_tax_inclusive']??null)===true,'gross prices accepted');
if($case==='dynamic')$check(($fx['r13_dual_ledger']['components']['shipping']['usd_minor']??null)===1300
 &&($fx['shipping_quote_provenance']??null)==='TRUSTED_NATIVE_WOO_QUOTE','evaluated dynamic USD shipping');
if($case==='dynamic') {
 $settings=get_option('woocommerce_flat_rate_'.$instance.'_settings',[]);
 $settings['cost']='10.00';update_option('woocommerce_flat_rate_'.$instance.'_settings',$settings,false);
}
if($case==='inclusive')update_option('woocommerce_prices_include_tax','no');
echo 'P6F_COMPLEX_NATIVE_'.$case.'_PASS order='.$order->get_id()
 .' USD='.$fx['usd_subtotal_minor'].' CAD='.$fx['native_woo_display_minor'].PHP_EOL;
