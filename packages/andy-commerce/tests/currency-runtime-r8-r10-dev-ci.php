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
// R10-USD invariant: even when AUD converts USD50 into AUD75, a Woo
// native $69 minimum must NOT grant free shipping. Conversely EUR prices
// below 69 in display units must not block a USD70-eligible shipment.
$shipping=new WC_Shipping_Free_Shipping();
$shipping->settings=['requires'=>'min_amount','min_amount'=>'69','ignore_discounts'=>'no'];
$low=new WC_Product_Simple();$low->set_name('R10 USD50 below free shipping');
$low->set_status('publish');$low->set_regular_price('50.00');$low->set_price('50.00');
$lowId=$low->save();
$high=new WC_Product_Simple();$high->set_name('R10 USD70 eligible');
$high->set_status('publish');$high->set_regular_price('70.00');$high->set_price('70.00');
$highId=$high->save();
foreach(['EUR','GBP','AUD','CAD'] as $iso){
    WC()->session->set('ac_currency_runtime_dev_selected',$iso);
    WC()->cart->empty_cart();
    $ok((bool)WC()->cart->add_to_cart($lowId,1),'USD50 add to cart '.$iso);
    $ok($rt::free_shipping_usd(true,[],$shipping)===false,
        'USD50 below $69 must not be eligible '.$iso);
    WC()->cart->empty_cart();
    $ok((bool)WC()->cart->add_to_cart($highId,1),'USD70 add to cart '.$iso);
    $ok($rt::free_shipping_usd(false,[],$shipping)===true,
        'USD70 above $69 eligible despite display FX '.$iso);
    $ok((bool)WC()->cart->apply_coupon('aicc-r8-r10-test'),'USD5 coupon applied '.$iso);
    $ok($rt::free_shipping_usd(true,[],$shipping)===false,
        'USD70 - $5 coupon is below $69 '.$iso);
}
echo 'R10_USD_69_FREE_SHIPPING_FOUR_FOREIGN_CURRENCIES_PASS'.PHP_EOL;

// #233 reproduces separate-HTTP-request Store API catalog behavior.
// Simulate a signed Woo shopper cookie persisted to native WC session DB.
// A fresh product-only request must recover EUR when WC()->session begins NULL.
WC()->session->set('ac_currency_runtime_dev_selected','EUR');
$wcSession=WC()->session;
$reflect=new ReflectionObject($wcSession);
$expiration=(int)$reflect->getProperty('_session_expiration')->getValue($wcSession);
$expiring=(int)$reflect->getProperty('_session_expiring')->getValue($wcSession);
$customerId=(string)$wcSession->get_customer_id();
$ok($customerId!=='' && $expiration>time() && function_exists('wp_hash'),
    '#233 fixture requires valid Woo signed session');
$cookieName='wp_woocommerce_session_'.COOKIEHASH;
$cookieMessage=$customerId.'|'.$expiration;
// Woo 11.2.1 signs WC session cookies with hash_hmac('md5', msg, wp_hash(msg)).
$cookieValue=$customerId.'|'.$expiration.'|'.$expiring.'|'
    .hash_hmac('md5',$cookieMessage,wp_hash($cookieMessage));
$_COOKIE[$cookieName]=$cookieValue;
$wcSession->save_data();
$ok(is_array($wcSession->get_session_cookie()),'#233 native cookie signature verifies');
WC()->session=null;
$ok(WC()->session===null,'#233 simulated REST product request starts without Woo session');
$productResponse=rest_do_request(new WP_REST_Request('GET','/wc/store/v1/products/'.$id));
$ok($productResponse instanceof WP_REST_Response && $productResponse->get_status()===200,
    '#233 single product REST GET');
$productPayload=json_decode(wp_json_encode($productResponse->get_data()),true);
$ok(($productPayload['prices']['currency_code']??null)==='EUR'
  && (string)($productPayload['prices']['price']??'')==='1125',
    '#233 REST product EUR price uses restored signed cookie');
$ok(WC()->session!==null && WC()->session->get('ac_currency_runtime_dev_selected')==='EUR',
    '#233 restored Woo guest session');
WC()->session=null;
$listRequest=new WP_REST_Request('GET','/wc/store/v1/products');
$listRequest->set_param('include',[$id]);
$listResponse=rest_do_request($listRequest);
$ok($listResponse instanceof WP_REST_Response && $listResponse->get_status()===200,
    '#233 catalog list REST GET');
$catalog=json_decode(wp_json_encode($listResponse->get_data()),true);
$matching=array_values(array_filter($catalog,static fn($p)=>(int)($p['id']??0)===$id));
$ok(count($matching)===1 && ($matching[0]['prices']['currency_code']??null)==='EUR',
    '#233 REST product catalog EUR via restored signed cookie');
$ok((float)wc_get_product($id)->get_price('edit')===12.50,
    '#233 product canonical USD unchanged');
echo 'R9_ISSUE233_GUEST_PRODUCT_SINGLE_AND_LIST_RESTORE_EUR_PASS'.PHP_EOL;
// A fresh anonymous product GET without any Woo cookie must not initialize
// a session, and the catalog should remain USD.
WC()->session=null;
unset($_COOKIE[$cookieName]);
$anonymousRequest=new WP_REST_Request('GET','/wc/store/v1/products/'.$id);
$anonymousResponse=rest_do_request($anonymousRequest);
$anonymous=json_decode(wp_json_encode($anonymousResponse->get_data()),true);
$ok(WC()->session===null && ($anonymous['prices']['currency_code']??null)==='USD',
    '#233 cookie-less catalog keeps USD without creating session');
echo 'R9_ISSUE233_ANON_NO_COOKIE_USD_FALLBACK_PASS'.PHP_EOL;

update_option($rt::OPTION,['enabled'=>false,'allowed'=>$rt::CODES],false);
$ok(!$rt::allowed(),'instant Dev runtime disable');
$ok($rt::currency('USD')==='USD','disabled must fall back USD');
echo 'R10_RUNTIME_OFF_RECOVERY_PASS'.PHP_EOL;
