<?php
/** LOCAL SANDBOX TEST ONLY: M12 projected amount -> genuine native Woo checkout. */
if (getenv('AICC_ALLOW_SANDBOX_WOO_WRITES') !== '1'
    || home_url() !== 'http://127.0.0.1:18973'
    || DB_NAME !== 'aicc_commerce_p5_20261023'
    || wp_get_environment_type() !== 'local'
    || get_woocommerce_currency() !== 'USD'
    || !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL) {
    throw new RuntimeException('HARD_STOP_NOT_EPHEMERAL_LOCAL_SANDBOX');
}
require_once WP_PLUGIN_DIR.'/andy-commerce/includes/class-andy-commerce-m12-woo-six-shadow.php';
$gatewayKeys=array_keys(WC()->payment_gateways()->payment_gateways());
if ($gatewayKeys !== ['cod']) {
    throw new RuntimeException('GATEWAY_ISOLATION_NOT_VERIFIED: '.implode(',', $gatewayKeys));
}
$prices=[101=>40,202=>45,303=>60];
$ids=[];
foreach($prices as $code=>$price) {
  $p=new WC_Product_Simple();
  $p->set_name('AICC M13 local '.$code);$p->set_status('publish');
  $p->set_regular_price((string)$price);$p->set_price((string)$price);
  $p->set_virtual(false);$p->set_manage_stock(false);$ids[$code]=$p->save();
}
$giftProduct=new WC_Product_Simple();
$giftProduct->set_name('AICC M13 local Gift (sandbox only)');
$giftProduct->set_status('publish');$giftProduct->set_regular_price('15');
$giftProduct->set_price('15');$giftProduct->set_manage_stock(true);
$giftProduct->set_stock_quantity(5);$giftProduct->set_stock_status('instock');
$giftId=$giftProduct->save();
$policy=['start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'all','product_ids'=>[],'stack_policy'=>'best_single'];
$mk=static fn($id,$type,$rule):array=>[
'id'=>$id,'type'=>$type,'rule'=>$rule,'priority'=>20,'active'=>false,
'status'=>'draft','lifecycle'=>'draft','currency'=>'USD','countries'=>['US'],'policy'=>$policy];
$drafts=[
 $mk('fixed','spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]),
 $mk('percent','spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]),
 $mk('gift','free_gift',['threshold_minor'=>9900,'product_id'=>$giftId]),
 $mk('bogo','bogo',['buy_product_id'=>$ids[101],'buy_qty'=>2,'get_product_id'=>$ids[202],'get_qty'=>1,'percent_bp'=>5000]),
 $mk('bundle','bundles',['product_ids'=>[$ids[101],$ids[303]],'discount_minor'=>2300]),
 $mk('tier','tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>4,'percent_bp'=>2000]]]),
];
$out=['harness'=>'SANDBOX_LOCAL_ONLY_NOT_PLUGIN_RUNTIME','off_unified_promotion_engine'=>true,
 'external_gateway'=>false,'no_email'=>true,'no_real_woo_customer'=>true,'cases'=>[]];
$testKeys=[];
foreach($drafts as $draft) {
 $country='US';WC()->customer->set_shipping_country($country);WC()->customer->set_billing_country($country);
 WC()->customer->set_shipping_state('CA');WC()->customer->set_billing_state('CA');
 WC()->customer->set_shipping_postcode('90001');WC()->session->set('chosen_shipping_methods',[]);
 $cart=WC()->cart;$cart->empty_cart();
 $cart->add_to_cart($ids[101],2);$cart->add_to_cart($ids[202],1);$cart->add_to_cart($ids[303],1);
 $cart->calculate_shipping();
 $rates=WC()->shipping()->get_packages()[0]['rates']??[];
 if(count($rates)!==1)throw new RuntimeException('RATE_NOT_ISOLATED_'.$draft['id']);
 WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
 $cart->calculate_totals();
 if(count($cart->get_applied_coupons())!==0)throw new RuntimeException('COUPON_DIRTY_BEFORE_'.$draft['id']);
 $flags=[];foreach($cart->get_cart() as $key=>$line)
  $flags[$key]=['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false];
 $before=(float)$cart->get_total('edit');
 $m12=Andy_Commerce_M12_Woo_Six_Shadow::preview($cart,$flags,9900,[$draft],'2026-11-15');
 $sim=$m12['simulation'];$projection=$sim['projected_discount_usd_minor'];
 if(!$sim['evaluations'][0]['eligible'] || $sim['actual_discount_applied']!==false
    || $m12['runtime_enabled']!==false)throw new RuntimeException('M12_INVALID_OFF_SHADOW_'.$draft['id']);
 if($draft['id']==='gift'){
    $fresh=wc_get_product($giftId);
    if(!$fresh || !$fresh->managing_stock() || !$fresh->is_in_stock()
       || (int)$fresh->get_stock_quantity()<1)throw new RuntimeException('GIFT_STOCK_NOT_VERIFIED');
    if($sim['qualified_gifts'][0]['gift_product_id']!==$giftId)throw new RuntimeException('GIFT_ID_NOT_QUALIFIED');
    $giftZero = static function($liveCart)use($giftId):void {
      foreach($liveCart->get_cart() as $line) {
       if((int)$line['product_id']===$giftId) $line['data']->set_price('0');
      }
    };
    add_action('woocommerce_before_calculate_totals',$giftZero,9999);
    $cart->add_to_cart($giftId,1);
    $cart->calculate_totals();
    remove_action('woocommerce_before_calculate_totals',$giftZero,9999);
    if(abs((float)$cart->get_total('edit')-$before)>0.01)throw new RuntimeException('GIFT_TOTAL_CHANGED');
 } else {
    if($projection<=0)throw new RuntimeException('NOT_QUALIFIED_'.$draft['id']);
    $coupon=new WC_Coupon();
    $coupon->set_code('aicc-m13-local-'.$draft['id'].'-'.time());
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount(number_format($projection/100,2,'.',''));
    $coupon->set_individual_use(true);
    $couponId=$coupon->save();
    if(!$couponId)throw new RuntimeException('TEST_COUPON_SAVE_FAILED');
    if(!$cart->apply_coupon($coupon->get_code()))throw new RuntimeException('APPLY_NATIVE_COUPON_FAILED');
    $cart->calculate_totals();
    if(abs((float)$cart->get_discount_total()-($projection/100))>0.01)
     throw new RuntimeException('NATIVE_DISCOUNT_DIFFERS_'.$draft['id']);
 }
 $orderId=WC()->checkout()->create_order([
  'billing_first_name'=>'AICC','billing_last_name'=>'Local M13',
  'billing_email'=>'m13-test@example.invalid',
  'billing_country'=>'US','billing_state'=>'CA','billing_postcode'=>'90001',
  'billing_city'=>'Local','billing_address_1'=>'1 Local Sandbox Only',
  'shipping_first_name'=>'AICC','shipping_last_name'=>'Local M13',
  'shipping_country'=>'US','shipping_state'=>'CA','shipping_postcode'=>'90001',
  'shipping_city'=>'Local','shipping_address_1'=>'1 Local Sandbox Only',
  'payment_method'=>'cod',
  'customer_note'=>'Explicit local-only test proof, no real payment or marketing activation'
 ]);
 if(is_wp_error($orderId))throw new RuntimeException($orderId->get_error_message());
 $order=wc_get_order($orderId);
 $nativeActual=(float)$cart->get_total('edit');
 if(abs((float)$order->get_total()-$nativeActual)>0.01
    ||$order->get_currency()!=='USD'||$order->get_payment_method()!=='cod'
    ||$order->is_paid()||$order->get_transaction_id()!=='')
    throw new RuntimeException('REAL_NATIVE_ORDER_DIVERGED_'.$draft['id']);
 if($draft['id']==='gift'){
   $giftLines=array_filter($order->get_items(),static fn($l)=>(int)$l->get_product_id()===$giftId);
   if(count($giftLines)!==1 || abs((float)reset($giftLines)->get_total())>0.001)
    throw new RuntimeException('GIFT_NOT_ZERO_PRICE_ON_ORDER');
 }
 else if(abs((float)$order->get_discount_total()-($projection/100))>0.01)
   throw new RuntimeException('REAL_ORDER_DISCOUNT_DIVERGED_'.$draft['id']);
 $out['cases'][$draft['id']]=[
  'order_id'=>$orderId,'projected_discount_usd_minor'=>$projection,
  'native_coupon_discount_usd_minor'=>(int)round((float)$order->get_discount_total()*100),
  'native_shipping_usd_minor'=>(int)round((float)$order->get_shipping_total()*100),
  'native_tax_usd_minor'=>(int)round((float)$order->get_total_tax()*100),
  'native_order_total_usd_minor'=>(int)round((float)$order->get_total()*100),
  'gift_awarded_only_in_isolated_test'=>$draft['id']==='gift',
  'external_payment'=>false,'original_marketing_draft_is_off'=>true
 ];
}
file_put_contents(dirname(__DIR__).'/logs/p5-m13-native-six-execution.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'M13_LOCAL_SIX_ORDER_PROTOTYPE_PASS='.json_encode($out,JSON_THROW_ON_ERROR),PHP_EOL;
