<?php
if(getenv('AICC_M15_REAL_WOO_READ')!=='1'
  ||home_url()!=='http://127.0.0.1:18973'
  ||DB_NAME!=='aicc_commerce_p5_20261023'
  ||wp_get_environment_type()!=='local')
  throw new RuntimeException('M15_NONLOCAL_REAL_WOO_READER_DENIED');
require_once getenv('AICC_M15_ALLOC_SOURCE');
require_once WP_PLUGIN_DIR.'/andy-commerce/includes/class-andy-commerce-m12-woo-six-shadow.php';
$prods=[27,28,29];
foreach($prods as $id)if(!wc_get_product($id))throw new RuntimeException('UNEXPECTED_WOO_PRODUCT');
WC()->customer->set_shipping_country('US');
WC()->customer->set_billing_country('US');
WC()->customer->set_shipping_state('CA');
WC()->customer->set_billing_state('CA');
WC()->customer->set_shipping_postcode('90001');
WC()->session->set('chosen_shipping_methods',[]);
$cart=WC()->cart;$cart->empty_cart();$cart->add_to_cart(27,2);$cart->add_to_cart(28,1);$cart->add_to_cart(29,1);
$cart->calculate_shipping();$packages=WC()->shipping()->get_packages();
$rates=$packages[0]['rates']??[];
if(count($rates)!==1)throw new RuntimeException('SHIPPING_RATE_NOT_ONE');
WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
$cart->calculate_totals();
$flags=[];
foreach($cart->get_cart() as $key=>$item)
  $flags[$key]=['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false];
$cases=[
 'bogo'=>['bogo',['buy_product_id'=>27,'buy_qty'=>2,'get_product_id'=>28,'get_qty'=>1,'percent_bp'=>5000]],
 'bundle'=>['bundles',['product_ids'=>[27,29],'discount_minor'=>2300]],
 'tier'=>['tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>4,'percent_bp'=>2000]]]],
];
$out=[];
foreach($cases as $label=>[$type,$rule]){
 $draft=['id'=>$label,'type'=>$type,'priority'=>20,'rule'=>$rule,'lifecycle'=>'draft',
   'currency'=>'USD','countries'=>['US'],'active'=>false,'status'=>'draft','policy'=>[
   'start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'all',
   'product_ids'=>[],'stack_policy'=>'best_single']];
 $m12=Andy_Commerce_M12_Woo_Six_Shadow::preview($cart,$flags,9900,[$draft],'2026-11-15');
 $m11=Andy_Commerce_M11_WC_Cart_Reader::read($cart,$flags,9900);
 $projection=$m12['simulation']['projected_discount_usd_minor'];
 $plan=Andy_Commerce_M15_Discount_Allocation::plan($type,$rule,$m11['catalog_lines'],$projection);
 if($plan['allocated_discount_usd_minor']!==$projection||$plan['checkout_mutations']!==0)
  throw new RuntimeException('M15_WOO_SKU_ALLOCATION_NOT_BALANCED: '.$label);
 $allocatedIds=array_column($plan['sku_lines'],'product_id');
 if($label==='bogo'&&$allocatedIds!==[28])throw new RuntimeException('BOGO_WRONG_SKU');
 if($label==='bundle'&&$allocatedIds!==[27,29])throw new RuntimeException('BUNDLE_WRONG_SKUS');
 $out[$label]=[
  'native_order_payable_usd_minor'=>$m12['native_woo']['actual_payable_usd_minor'],
  'projected_discount_usd_minor'=>$projection,
  'allocated_product_ids'=>$allocatedIds,
  'product_line_minor'=>array_column($plan['sku_lines'],'discount_usd_minor'),
  'per_item_refund_discounts'=>array_column($plan['sku_lines'],'unit_discounts_usd_minor'),
  'actual_woo_order_write'=>false,
 ];
}
echo json_encode(['mode'=>'REAL_WOO_M15_SKU_READ_ONLY_PASS','cases'=>$out],JSON_THROW_ON_ERROR),PHP_EOL;
