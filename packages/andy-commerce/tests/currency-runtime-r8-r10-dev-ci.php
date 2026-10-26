<?php
/**
 * R8-R10 Dev runtime gated integration test. Runs only on disposable CI site,
 * temporarily simulating Dev host through filter; never sends real mail/payment.
 */
if(!defined('ABSPATH') || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('DB_NAME') || DB_NAME!=='commerce_p5_isolated'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL)
 throw new RuntimeException('R8-R10 ephemeral CI guard');
$ok=static function($condition,$label){ if(!$condition)throw new RuntimeException('R8-R10: '.$label); };
add_filter('home_url',static function($url){return str_replace('commerce-p5.invalid','dev.beyourlover.com',$url);},999,1);
$rt='Andy_Commerce_Currency_Runtime_Dev_V1';
$ok($rt::site(),'simulated Dev and USD Woo baseline');
update_option($rt::OPTION,['enabled'=>true,'allowed'=>$rt::CODES],false);
$ok(!$rt::allowed(),'fail closed without rates');
$old=[
 'base'=>'USD','rate_source'=>Andy_Commerce_Currency_Reference_FX_V1::SOURCE,
 'retrieved_at_utc'=>time()-72*3600-2,
 'rate_micros'=>['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000]
];
update_option($rt::FX_OPTION,$old,false);
$ok(!$rt::allowed(),'stale rates must fail closed');
$old['retrieved_at_utc']=time();
update_option($rt::FX_OPTION,$old,false);
$ok($rt::allowed(),'validated fresh provider snapshot');
$rt::boot();
if(function_exists('wc_load_cart')) wc_load_cart();
$ok(WC()->cart instanceof WC_Cart && WC()->session!==null,'cart and shopper session ready');
$ok(get_woocommerce_currency()==='USD','default currency USD');
$ok(str_contains($rt::shortcode(),'<form')&&str_contains($rt::shortcode(),'name="ac_currency_nonce"') && str_contains($rt::shortcode(),'name="ac_currency_code"'),'shopper Footer renders POST form + nonce');
WC()->session->set('ac_currency_runtime_dev_selected','EUR');
$ok(get_woocommerce_currency()==='EUR','session changes Woo currency');
$ok($rt::gateways(['cod'=>new stdClass()])===[],'forex gateway blocked');
$p=new WC_Product_Simple();
$p->set_status('publish');$p->set_name('R8-R10 USD base');
$p->set_regular_price('20.00');$p->set_sale_price('12.50');$p->set_price('12.50');
$id=$p->save();$product=wc_get_product($id);
$ok(abs((float)$product->get_price()-11.25)<0.001,'EUR price converted');
$ok(abs((float)$product->get_regular_price()-18.00)<0.001,'EUR regular price');
$ok(abs((float)$product->get_price('edit')-12.50)<0.001,'source USD immutable');
WC()->cart->empty_cart();WC()->cart->add_to_cart($id,2);WC()->cart->calculate_totals();
$ok(abs((float)WC()->cart->get_total('edit')-22.50)<0.001,'EUR native Woo cart');
$rest=rest_do_request(new WP_REST_Request('GET','/wc/store/v1/products/'.$id));
$ok($rest instanceof WP_REST_Response && $rest->get_status()===200,'real Store API route');
$pr=json_decode(wp_json_encode($rest->get_data()),true);
$ok(($pr['prices']['currency_code']??'')==='EUR'&&(string)($pr['prices']['price']??'')==='1125','Store API EUR price');
$cr=new WC_Coupon();$cr->set_code('aicc-r8-r10-test');$cr->set_discount_type('fixed_cart');$cr->set_amount('5.00');$cr->save();
$ok(abs((float)(new WC_Coupon('aicc-r8-r10-test'))->get_amount()-4.50)<0.001,'EUR coupon FX');
$r=new WC_Shipping_Rate('flat_rate:aicc','Synthetic shipping','10.00',[],'flat_rate');
$filtered=apply_filters('woocommerce_package_rates',['flat_rate:aicc'=>$r],[]);
$ok(abs((float)$filtered['flat_rate:aicc']->get_cost()-9.0)<0.001,'flat rate converted once');
$other=new WC_Shipping_Rate('local_pickup:aicc','Pickup','10.00',[],'local_pickup');
$filterOther=apply_filters('woocommerce_package_rates',['local_pickup:aicc'=>$other],[]);
$ok(abs((float)$filterOther['local_pickup:aicc']->get_cost()-10.0)<0.001,'unsupported shipping method untouched');
$rateTax=WC_Tax::calc_tax('9.00',[1=>['rate'=>'5.0000','label'=>'Synthetic 5','compound'=>'no','shipping'=>'yes']],false);
$ok(abs((float)wc_round_tax_total(array_sum($rateTax))-0.45)<0.001,'native Woo tax calculator');
$ok(!str_contains($rt::shortcode(),'Dev preview'),'shopper form is functional not C3 DOM overlay');
echo 'R8_FX_RATE_FRESHNESS_DEFAULT_OFF_AND_CONFLICT_GATE_PASS'.PHP_EOL;
$source=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-currency-runtime-dev-v1.php');
$ok(str_contains($source,'set_customer_session_cookie(true)'),'guest session cookie must be explicitly persisted');
echo 'R9_REAL_DEV_FOOTER_SESSION_WOO_PRODUCT_STORE_API_PASS'.PHP_EOL;
echo 'R10_EUR_COUPON_FLAT_SHIPPING_NATIVE_TAX_CALCULATOR_PASS'.PHP_EOL;
update_option($rt::OPTION,['enabled'=>false,'allowed'=>$rt::CODES],false);
$ok(!$rt::allowed(),'instant Dev runtime disable');
$ok($rt::currency('USD')==='USD','disabled must fall back USD');
echo 'R10_RUNTIME_OFF_RECOVERY_PASS'.PHP_EOL;
