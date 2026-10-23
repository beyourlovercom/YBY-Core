<?php
declare(strict_types=1);
define('ABSPATH','/test/');
require __DIR__.'/../includes/class-andy-commerce-m10-policy-fields.php';
$n=0;
$ok=function($b,$s)use(&$n){if(!$b)throw new RuntimeException($s);$n++;};
$a=Andy_Commerce_M10_Policy_Fields::normalize(['start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'selected','product_ids'=>'101,202','stack_policy'=>'coupon_exclusive']);
$ok($a['product_ids']===[101,202],'IDs saved');
$ok($a['evaluation']==='dev_shadow_only','shadow only');
foreach([['start_date'=>'2026-02-30'],['start_date'=>'2026-12-01','end_date'=>'2026-11-01'],['product_scope'=>'selected'],['product_scope'=>'all','product_ids'=>'123'],['product_scope'=>'selected','product_ids'=>'123,123'],['stack_policy'=>'allow_all'],['product_scope'=>'selected','product_ids'=>'0']] as $case){
try{Andy_Commerce_M10_Policy_Fields::normalize($case);throw new RuntimeException('accepted invalid '.json_encode($case));}
catch(InvalidArgumentException $e){$n++;}
}
echo "PASS POLICY_FIELDS=$n\n";
