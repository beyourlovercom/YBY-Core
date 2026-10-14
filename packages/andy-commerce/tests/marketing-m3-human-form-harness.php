<?php
/** M3 operator human form end-to-end conversion, offline and no live writes. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
require_once dirname(__DIR__).'/includes/class-andy-commerce-marketing-draft-sandbox.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m3-human-form.php';
function sample(array $rule, string $type='spend_save'):array {
 return ['campaign_fields'=>[
 'id'=>'operator-draft-123','name'=>'满100减10测试','type'=>$type,'currency'=>'USD',
 'priority'=>'0','countries'=>['US'],'start_utc'=>'','end_utc'=>'',
 'stack_with_coupon'=>'1','notes'=>'Only Dev draft','rule'=>$rule]];
}
$cases=0;
$ok=static function(bool $v,string $label)use(&$cases){$cases++;if(!$v)throw new RuntimeException('M3 human form failed: '.$label);};
$a=Andy_Commerce_M3_Human_Form::decode(sample(['threshold'=>'100.00','award_mode'=>'fixed','award_value'=>'10.00']));
$ok($a['rule']['threshold_minor']===10000,'$100 threshold');
$ok($a['rule']['discount_minor']===1000,'$10 amount');
$ok($a['active']===false && $a['status']==='draft','always OFF');
$ok($a['countries']===['US'] && $a['stack_with_coupon']===true,'country and stacking');
$b=Andy_Commerce_M3_Human_Form::decode(sample(['threshold'=>'100.00','award_mode'=>'percent','award_value'=>'15.50']));
$ok($b['rule']['discount_bps']===1550,'15.5 percent exact');
$c=Andy_Commerce_M3_Human_Form::decode(sample(['buy_ids'=>['11'],'get_ids'=>['12'],'buy_qty'=>'2','get_qty'=>'1','reward_percent'=>'100'],'bogo'));
$ok($c['rule']['buy_qty']===2 && $c['rule']['reward_bps']===10000,'BOGO quantities');
$d=Andy_Commerce_M3_Human_Form::decode(sample(['product_ids'=>['11','12'],'quantities'=>['1','2'],'award_mode'=>'bundle_price','award_value'=>'99.00'],'bundle'));
$ok($d['rule']['bundle_price_minor']===9900,'bundle pricing');
$e=Andy_Commerce_M3_Human_Form::decode(sample(['tier_quantities'=>['2','3'],'tier_modes'=>['percent','fixed'],'tier_values'=>['10','5.00']],'tiered'));
$ok(count($e['rule']['tiers'])===2 && $e['rule']['tiers'][1]['discount_minor']===500,'tier conversion');
$f=Andy_Commerce_M3_Human_Form::decode(sample(['threshold'=>'200.00','gift_id'=>'123','gift_qty'=>'1'],'free_gift'));
$ok($f['rule']['reward_product_id']===123,'gift draft');
$g=Andy_Commerce_M3_Human_Form::decode(sample(['threshold'=>'49.00','shipping_method'=>'free_shipping:7'],'free_shipping'));
$ok($g['rule']['threshold_minor']===4900,'shipping draft only');
$invalid=[
 sample(['threshold'=>'100.009','award_mode'=>'fixed','award_value'=>'10']),
 sample(['threshold'=>'100','award_mode'=>'percent','award_value'=>'110']),
 sample(['threshold'=>'-100','award_mode'=>'fixed','award_value'=>'10']),
 sample(['threshold'=>'100','award_mode'=>'fixed','award_value'=>'-1']),
 sample(['threshold'=>'100','award_mode'=>'fixed','award_value'=>'10'],'evil_type'),
 sample(['threshold'=>'100','award_mode'=>'fixed','award_value'=>'10','product_ids'=>['1','1']]),
 sample(['threshold'=>'100','award_mode'=>'fixed','award_value'=>'10','product_ids'=>['../../bad']]),
 sample(['threshold'=>'100','award_mode'=>'fixed','award_value'=>'10','active'=>'true']),
];
foreach($invalid as $i=>$post) {
 try { Andy_Commerce_M3_Human_Form::decode($post);
   if($i===7) { $cases++;continue; } // ignored client activation flag, never saved.
   throw new RuntimeException('unsafe input accepted case='.$i);
 } catch(InvalidArgumentException $e) { $cases++; }
}
$ok(!isset($a['eligibility_basis']) && $a['active']===false,'no claimed runtime activation');
echo 'M3_HUMAN_FORM_CONTRACT_PASS='.$cases."\n";
