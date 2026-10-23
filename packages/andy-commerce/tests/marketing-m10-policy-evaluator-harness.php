<?php
declare(strict_types=1);
define('ABSPATH','/test/');
require __DIR__.'/../includes/class-andy-commerce-m10-policy-fields.php';
require __DIR__.'/../includes/class-andy-commerce-m10-policy-evaluator.php';
$n=0;
$ok=static function(bool $b,string $why)use(&$n){if(!$b)throw new RuntimeException($why);$n++;};
$d=['active'=>false,'status'=>'draft','policy'=>[
 'start_date'=>'2026-11-01','end_date'=>'2026-11-30',
 'product_scope'=>'selected','product_ids'=>[101,202],'stack_policy'=>'coupon_exclusive'
]];
$s=['date_utc'=>'2026-11-15','product_ids'=>[101,999],'manual_coupon'=>null];
$ok(Andy_Commerce_M10_Policy_Evaluator::check($d,$s)['eligible'],'eligible baseline');
foreach([
 [['date_utc'=>'2026-10-31'],'not_started'],
 [['date_utc'=>'2026-12-01'],'expired'],
 [['date_utc'=>'invalid'],'unverified_date'],
 [['product_ids'=>[]],'unverified_products'],
 [['product_ids'=>[999]],'product_excluded'],
 [['product_ids'=>['101']],'unverified_products'],
 [['manual_coupon'=>'SAVE10'],'coupon_conflict'],
] as [$mut,$reason]){
  $out=Andy_Commerce_M10_Policy_Evaluator::check($d,array_merge($s,$mut));
  $ok(!$out['eligible']&&$out['reason']===$reason,$reason);
}
$bad=$d;$bad['active']=true;
$ok(Andy_Commerce_M10_Policy_Evaluator::check($bad,$s)['reason']==='not_inactive_draft','active blocked');
$bad=$d;$bad['policy']['stack_policy']='stack_anything';
$ok(Andy_Commerce_M10_Policy_Evaluator::check($bad,$s)['reason']==='invalid_policy','unknown policy blocked');
$old=['active'=>false,'status'=>'draft'];
$ok(Andy_Commerce_M10_Policy_Evaluator::check($old,['date_utc'=>'2026-11-15','product_ids'=>[]])['eligible'],'legacy with confirmed date allowed');
$ok(Andy_Commerce_M10_Policy_Evaluator::check($old,['product_ids'=>[]])['reason']==='unverified_date','no trusted date fail closed');
echo "PASS POLICY_EVALUATOR=$n; SHADOW_ONLY\n";
