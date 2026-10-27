<?php
/**
 * R11-B: actual Woo Store API POST /checkout on ONE-USE disposable WP,
 * with offline COD only, no emails, no external HTTP, no real gateway.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME!=='commerce_p5_isolated'
    || get_option('siteurl')!=='http://commerce-p5.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL
    || !defined('AICC_CURRENCY_RUNTIME_SANDBOX') || AICC_CURRENCY_RUNTIME_SANDBOX!==true
    || !Andy_Commerce_Currency_Runtime_C1::allowed())
    throw new RuntimeException('R11 Blocks POST is allowed only inside throwaway CI');
$ok=static function($yes,$msg){if(!$yes)throw new RuntimeException('R11-B POST: '.$msg);};
if(function_exists('wc_load_cart'))wc_load_cart();
$ok(WC()->cart instanceof WC_Cart && WC()->session!==null,'Woo initialized');
$ok(Andy_Commerce_Currency_Runtime_C1::select_for_sandbox('CAD'),'sandbox CAD');
$p=new WC_Product_Simple();
$p->set_name('R11-B sandbox-only Checkout Blocks product');$p->set_status('publish');
$p->set_virtual(true);$p->set_manage_stock(false);
$p->set_regular_price('12.50');$p->set_price('12.50');$id=$p->save();
$cp=new WC_Coupon();
$cp->set_code('aicc-r11-blocks-five');$cp->set_discount_type('fixed_cart');
$cp->set_amount('5.00');$cp->save();
WC()->cart->empty_cart();
$ok((bool)WC()->cart->add_to_cart($id,2),'add synthetic product');
$ok((bool)WC()->cart->apply_coupon('aicc-r11-blocks-five'),'apply fixed USD coupon');
WC()->cart->calculate_totals();
$ok(get_woocommerce_currency()==='CAD'
    && abs((float)WC()->cart->get_total('edit')-27.01)<0.001,
    'source CAD cart total is 27.01');
update_option('woocommerce_cod_settings',['enabled'=>'yes','title'=>'Offline Sandbox COD',
    'description'=>'CI only','enable_for_virtual'=>'yes'],false);
$req=new WP_REST_Request('POST','/wc/store/v1/checkout');
$req->set_header('Nonce',wp_create_nonce('wc_store_api'));
$req->set_header('Content-Type','application/json');
$req->set_param('payment_method','cod');
$req->set_param('expected_total','2701');
$req->set_param('billing_address',[
    'first_name'=>'Only','last_name'=>'Sandbox','company'=>'',
    'address_1'=>'123 Test Lane','address_2'=>'','city'=>'Fake City',
    'state'=>'CA','postcode'=>'90001','country'=>'US',
    'email'=>'no-contact@example.invalid','phone'=>'',
]);
$resp=rest_do_request($req);
$data=json_decode(wp_json_encode($resp->get_data()),true);
echo 'R11_BLOCKS_POST_DIAGNOSTIC status='.$resp->get_status()
    .' code='.sanitize_key((string)($data['code']??'none'))
    .' message='.substr(sanitize_text_field((string)($data['message']??'')),0,130).PHP_EOL;
$ok($resp instanceof WP_REST_Response && $resp->get_status()===200,
    'native Checkout Blocks real Store API POST must succeed using offline COD');
$orderId=(int)($data['order_id']??0);
$order=wc_get_order($orderId);
$ok($order instanceof WC_Order,'order returned by real Store API POST');
$fx=$order->get_meta('_aicc_currency_runtime_fx_v1',true);
$ok($order->get_currency()==='CAD'
    && abs((float)$order->get_total()-27.01)<0.001
    && is_array($fx)
    && ($fx['usd_subtotal_minor']??null)===2000
    && ($fx['native_woo_display_minor']??null)===2701
    && ($fx['fx_snapshot']['source']??null)==='CI_SYNTHETIC_LOCKED',
    'real Blocks POST persisted CAD, USD and immutable FX evidence');
$ok((float)wc_get_product($id)->get_price('edit')===12.50,
    'USD original product remains unchanged');
echo 'R11_REAL_BLOCKS_CHECKOUT_POST_OFFLINE_COD_FX_PASS id='.$orderId.PHP_EOL;
