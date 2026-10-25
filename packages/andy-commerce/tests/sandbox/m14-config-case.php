<?php
if(home_url()!=='http://127.0.0.1:18973' || DB_NAME!=='aicc_commerce_p5_20261023')
throw new RuntimeException('NOT_LOCAL');
$case=getenv('AICC_M14_CASE');
$ids=[101=>27,202=>28,303=>29,'gift'=>30];
$rules=[
'fixed'=>['spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]],
'percent'=>['spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]],
'gift'=>['free_gift',['threshold_minor'=>9900,'product_id'=>30]],
'bogo'=>['bogo',['buy_product_id'=>27,'get_product_id'=>28,'buy_qty'=>2,'get_qty'=>1,'percent_bp'=>5000]],
'bundle'=>['bundles',['product_ids'=>[27,29],'discount_minor'=>2300]],
'tier'=>['tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>4,'percent_bp'=>2000]]]]
];
if(!isset($rules[$case]))throw new RuntimeException('UNKNOWN_CASE');
$p=['start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'all','product_ids'=>[],'stack_policy'=>'best_single'];
$draft=['id'=>$case,'type'=>$rules[$case][0],'priority'=>20,'rule'=>$rules[$case][1],
  'lifecycle'=>'draft','currency'=>'USD','countries'=>['US'],'active'=>false,'status'=>'draft','policy'=>$p];
$map=[];
foreach([27,28,29] as $id)$map[(string)$id]=['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false];
$map['30']=['eligible_for_promotion'=>false,'special_shipping'=>false,'free_gift'=>true];
update_option('aicc_m14_sandbox_case',['draft'=>$draft,'map'=>$map,'date_utc'=>'2026-11-15'],false);
update_option('aicc_m14_sandbox_active','yes',false);
echo "M14_ACTIVE_LOCAL_CASE=$case\n";
