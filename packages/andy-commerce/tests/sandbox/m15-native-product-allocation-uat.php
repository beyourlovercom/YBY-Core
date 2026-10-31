<?php
if(getenv('AICC_M15_LOCAL_ORDER_WRITE')!=='1'
  ||home_url()!=='http://127.0.0.1:18973'
  ||DB_NAME!=='aicc_commerce_p5_20261023'
  ||wp_get_environment_type()!=='local'
  ||get_woocommerce_currency()!=='USD'
  ||get_option('woocommerce_custom_orders_table_enabled')!=='yes'
  ||get_option('aicc_m14_sandbox_active')!=='no'
  ||!defined('WP_HTTP_BLOCK_EXTERNAL')||!WP_HTTP_BLOCK_EXTERNAL)
 throw new RuntimeException('M15_LOCAL_WOO_WRITE_DENIED');
require_once getenv('AICC_M15_ALLOC_SOURCE');
require_once WP_PLUGIN_DIR.'/andy-commerce/includes/class-andy-commerce-m12-woo-six-shadow.php';
$cases=[
 'bogo'=>['bogo',['buy_product_id'=>27,'buy_qty'=>2,'get_product_id'=>28,'get_qty'=>1,'percent_bp'=>5000]],
 'bundle'=>['bundles',['product_ids'=>[27,29],'discount_minor'=>2300]],
];
$output=[];
foreach($cases as $case=>[$type,$rule]){
 WC()->customer->set_shipping_country('US');WC()->customer->set_billing_country('US');
 WC()->customer->set_shipping_state('CA');WC()->customer->set_billing_state('CA');
 WC()->customer->set_shipping_postcode('90001');WC()->session->set('chosen_shipping_methods',[]);
 $cart=WC()->cart;$cart->empty_cart();$cart->add_to_cart(27,2);$cart->add_to_cart(28,1);$cart->add_to_cart(29,1);
 $cart->calculate_shipping();$rates=WC()->shipping()->get_packages()[0]['rates']??[];
 if(count($rates)!==1)throw new RuntimeException('M15_BAD_SHIPPING');
 WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);$cart->calculate_totals();
 $flags=[];foreach($cart->get_cart() as $k=>$l)
   $flags[$k]=['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false];
 $draft=['id'=>$case,'type'=>$type,'priority'=>20,'rule'=>$rule,'lifecycle'=>'draft',
   'currency'=>'USD','countries'=>['US'],'active'=>false,'status'=>'draft','policy'=>[
   'start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'all',
   'product_ids'=>[],'stack_policy'=>'best_single']];
 $m12=Andy_Commerce_M12_Woo_Six_Shadow::preview($cart,$flags,9900,[$draft],'2026-11-15');
 $m11=Andy_Commerce_M11_WC_Cart_Reader::read($cart,$flags,9900);
 $expected=$m12['simulation']['projected_discount_usd_minor'];
 $plan=Andy_Commerce_M15_Discount_Allocation::plan($type,$rule,$m11['catalog_lines'],$expected);
 $amounts=[];
 foreach($plan['sku_lines'] as $l)$amounts[$l['product_id']]=$l['discount_usd_minor'];
 $code='aicc-m15-local-'.$case;
 $virtual=static function($prev,$incoming)use($code,$amounts){
   if(!is_string($incoming)||$incoming!==$code)return $prev;
   return ['discount_type'=>'fixed_product','amount'=>0.01,
     'product_ids'=>array_keys($amounts),'individual_use'=>true,
     'description'=>'Local M15 native Woo product-level proof only'];
 };
 $filter=static function($original,$discountable,$line,$single,$coupon)use($code,$amounts){
  if(!$coupon instanceof WC_Coupon||$coupon->get_code()!==$code ||!is_array($line))return $original;
  $id=(int)($line['product_id']??0);
  if(!array_key_exists($id,$amounts))return $original;
  return $amounts[$id]/100;
 };
 add_filter('woocommerce_get_shop_coupon_data',$virtual,99,2);
 add_filter('woocommerce_coupon_get_discount_amount',$filter,99,5);
 try{
   if(!$cart->apply_coupon($code))throw new RuntimeException('M15_NATIVE_COUPON_NOT_APPLIED_'.$case);
   $cart->calculate_totals();
   $cartSavings=(int)round((float)$cart->get_discount_total()*100);
   if($cartSavings!==$expected)throw new RuntimeException('M15_NATIVE_COUPON_CENTS_MISMATCH: '.$case.' '.$cartSavings);
   foreach($cart->get_cart() as $line){
      $id=(int)$line['product_id'];
      $actual=(int)round(((float)$line['line_subtotal']-(float)$line['line_total'])*100);
      if($actual!==($amounts[$id]??0))
         throw new RuntimeException('M15_NATIVE_SKU_WRONG: '.$case.' id '.$id.' actual '.$actual);
   }
   $oid=WC()->checkout()->create_order([
     'billing_first_name'=>'AICC','billing_last_name'=>'M15 SKU Sandbox',
     'billing_email'=>'m15-only@example.invalid','billing_country'=>'US',
     'billing_state'=>'CA','billing_postcode'=>'90001','billing_city'=>'Test',
     'billing_address_1'=>'1 Local Test','shipping_first_name'=>'AICC',
     'shipping_last_name'=>'M15','shipping_country'=>'US','shipping_state'=>'CA',
     'shipping_postcode'=>'90001','shipping_city'=>'Test',
     'shipping_address_1'=>'1 Local Test','payment_method'=>'cod']);
   if(is_wp_error($oid))throw new RuntimeException($oid->get_error_message());
   $order=wc_get_order($oid);
   if(!$order||$order->get_payment_method()!=='cod'||$order->get_transaction_id()!=='')
     throw new RuntimeException('M15_PAYMENT_BOUNDARY_FAILED');
   $orderAlloc=[];
   foreach($order->get_items() as $li){
     $p=$li->get_product_id();
     $deduct=(int)round(((float)$li->get_subtotal()-(float)$li->get_total())*100);
     $orderAlloc[$p]=$deduct;
     if($deduct!==($amounts[$p]??0))
       throw new RuntimeException('M15_NATIVE_ORDER_SKU_RECONCILE_FAIL');
   }
   $output[$case]=[
    'order_id'=>$oid,'plan_by_product'=>$amounts,'native_order_by_product'=>$orderAlloc,
    'native_coupon_discount_minor'=>$cartSavings,
    'native_order_total_minor'=>(int)round((float)$order->get_total()*100),
    'native_tax_minor'=>(int)round((float)$order->get_total_tax()*100),
    'external_payment'=>false,
   ];
 }finally{
   remove_filter('woocommerce_get_shop_coupon_data',$virtual,99);
   remove_filter('woocommerce_coupon_get_discount_amount',$filter,99);
 }
}
echo json_encode(['mode'=>'LOCAL_WOO_M15_REAL_SKU_COUPON_HPOS_PASS','cases'=>$output],JSON_THROW_ON_ERROR),PHP_EOL;
