<?php
/** M10 P2 full six-template synthetic matrix; no WordPress write API. */
declare(strict_types=1);
define('ABSPATH','/tmp/m10-six-tests/');
require_once dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m10-six-type-simulator.php';
$n=0;
$ok=static function($yes,string $why)use(&$n):void{
 if(!$yes)throw new RuntimeException('M10 six-type FAIL: '.$why);
 $n++;
};
$items=[
 ['product_id'=>101,'quantity'=>2,'discounted_line_minor'=>8000,'catalog_present'=>true],
 ['product_id'=>202,'quantity'=>1,'discounted_line_minor'=>4500,'catalog_present'=>true],
 ['product_id'=>303,'quantity'=>1,'discounted_line_minor'=>6000,'catalog_present'=>true],
];
$s=['source'=>'DEV_SYNTHETIC','trusted'=>true,'date_utc'=>'2026-11-15',
    'country'=>'US','currency'=>'USD','fx_rate_micros'=>1000000,
    'manual_coupon'=>null,'items'=>$items];
$policy=['start_date'=>'2026-11-01','end_date'=>'2026-11-30',
    'product_scope'=>'all','product_ids'=>[],'stack_policy'=>'best_single'];
$mk=static function(string $id,string $type,array $rule,int $priority=20)use($policy):array{
 return ['id'=>$id,'type'=>$type,'rule'=>$rule,'priority'=>$priority,
    'active'=>false,'status'=>'draft','lifecycle'=>'draft',
    'currency'=>'USD','countries'=>['US'],'policy'=>$policy];
};
$rules=[
 $mk('fixed','spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]),
 $mk('percent','spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]),
 $mk('gift','free_gift',['threshold_minor'=>9900,'product_id'=>404]),
 $mk('bogo','bogo',['buy_product_id'=>101,'buy_qty'=>2,
     'get_product_id'=>202,'get_qty'=>1,'percent_bp'=>5000]),
 $mk('bundle','bundles',['product_ids'=>[101,303],'discount_minor'=>2300]),
 $mk('tier','tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],
     ['minimum_qty'=>4,'percent_bp'=>2000]]]),
];
$expected=['fixed'=>1200,'percent'=>1850,'gift'=>0,'bogo'=>2250,'bundle'=>2300,'tier'=>3700];
foreach($rules as $d){
 $r=Andy_Commerce_M10_Six_Type_Simulator::preview($s,[$d]);
 $ok($r['projected_discount_usd_minor']===$expected[$d['id']],$d['id'].' computes USD minor');
 $ok($r['actual_discount_applied']===false&&$r['actual_gift_awarded']===false
     &&$r['shipping_changed']===false&&$r['runtime_enabled']===false,
     $d['id'].' never changes Woo runtime');
 $ok($d['id']!=='percent'||($r['evaluations'][0]['variant']??null)==='percent',
     'percentage draft flavor from rule, not ID');
 $ok(count($r['evaluations'])===1&&$r['evaluations'][0]['eligible']===true,
     $d['id'].' qualifying diagnostic');
}
$r=Andy_Commerce_M10_Six_Type_Simulator::preview($s,$rules);
$ok($r['selected_discount_id']==='tier'&&$r['projected_discount_usd_minor']===3700,
    'best discount wins before priority');
$ok(count($r['qualified_gifts'])===1
    &&$r['qualified_gifts'][0]['stock_verified']===false
    &&$r['qualified_gifts'][0]['awarded']===false,'gift qualification only');
$reject=static function(array $ss,array $d,string $reason)use($ok):void{
 $r=Andy_Commerce_M10_Six_Type_Simulator::preview($ss,[$d]);
 $ok(($r['evaluations'][0]['reason']??$r['reason'])===$reason,'reject '.$reason);
};
$dated=$rules[0];
$ss=$s;$ss['date_utc']='2026-10-31';$reject($ss,$dated,'not_started');
$ss=$s;$ss['date_utc']='2026-12-01';$reject($ss,$dated,'expired');
$ss=$s;$ss['date_utc']='2026-02-30';$reject($ss,$dated,'invalid_snapshot');
$ss=$s;$ss['country']='GB';$reject($ss,$dated,'country_excluded');
$ss=$s;$ss['currency']='GBP';$ss['fx_rate_micros']=746500;
$r=Andy_Commerce_M10_Six_Type_Simulator::preview($ss,[$dated]);
$ok($r['qualified_after_campaign_local_minor']===
    Andy_Commerce_Currency_OS_V1::convert_usd_minor(17300,746500),
    'GBP conversion after USD selection');
foreach(['EUR'=>890000,'AUD'=>1421700,'CAD'=>1404200] as $c=>$fx){
 $ss=$s;$ss['currency']=$c;$ss['fx_rate_micros']=$fx;
 $r=Andy_Commerce_M10_Six_Type_Simulator::preview($ss,[$dated]);
 $ok($r['projected_discount_usd_minor']===1200,'USD basis for '.$c);
 $ok($r['qualified_after_campaign_local_minor']===
    Andy_Commerce_Currency_OS_V1::convert_usd_minor(17300,$fx),
    'post-USD conversion '.$c);
}
$only=$dated;$only['policy']['product_scope']='selected';$only['policy']['product_ids']=[101];
$r=Andy_Commerce_M10_Six_Type_Simulator::preview($s,[$only]);
$ok($r['evaluations'][0]['qualified_basis_usd_minor']===8000,
    'selected SKU discounts restricted basis, not whole cart');
$only['policy']['product_ids']=[999];$reject($s,$only,'product_excluded');
$only=$dated;$only['policy']['stack_policy']='coupon_exclusive';
$ss=$s;$ss['manual_coupon']='EXISTING';$reject($ss,$only,'coupon_conflict');
$ss=$s;$ss['manual_coupon']='EXISTING';
$r=Andy_Commerce_M10_Six_Type_Simulator::preview($ss,[$dated]);
$ok($r['projected_discount_usd_minor']===1200,
    'best_single may preview with existing coupon, but does not apply');
foreach(['paused','ended','archived'] as $life){
 $d=$dated;$d['lifecycle']=$life;$reject($s,$d,'lifecycle_not_eligible');
}
foreach(['review','scheduled'] as $life){
 $d=$dated;$d['lifecycle']=$life;
 $ok(Andy_Commerce_M10_Six_Type_Simulator::preview($s,[$d])['projected_discount_usd_minor']===1200,
     $life.' remains virtual simulation');
}
$d=$dated;$d['active']=true;$reject($s,$d,'not_inactive_draft');
$d=$dated;$d['rule']['discount_minor']=-1;$reject($s,$d,'invalid_rule');
$d=$rules[3];$d['rule']['get_product_id']=101;$reject($s,$d,'invalid_rule');
$d=$rules[4];$d['rule']['product_ids']=[101,999];$reject($s,$d,'rule_not_qualified');
$d=$rules[3];$d['rule']['buy_qty']=3;$reject($s,$d,'rule_not_qualified');
$d=$rules[5];$d['rule']['tiers'][1]['minimum_qty']=2;$reject($s,$d,'invalid_rule');
$d=$rules[2];$d['rule']['threshold_minor']=999999;$reject($s,$d,'below_threshold');
$ss=$s;$ss['items'][0]['catalog_present']=false;$reject($ss,$dated,'unverified_cart_line');
$ss=$s;$ss['items'][0]['quantity']=0;$reject($ss,$dated,'unverified_cart_line');
$ss=$s;$ss['items'][1]['product_id']=101;$reject($ss,$dated,'unverified_cart_line');
$ss=$s;$ss['items'][1]['discounted_line_minor']=-100;$reject($ss,$dated,'unverified_cart_line');
$ss=$s;$ss['trusted']=false;$reject($ss,$dated,'invalid_snapshot');
$ss=$s;$ss['fx_rate_micros']=0;$reject($ss,$dated,'invalid_snapshot');
$ss=$s;$ss['manual_coupon']=str_repeat('x',51);$reject($ss,$dated,'invalid_snapshot');
$d=$dated;$d['priority']=1001;$reject($s,$d,'invalid_draft');
echo "M10_SIX_TYPE_MATRIX_PASS=$n; ALL_REWARDS_OFF; USD_FIRST=YES; WOO_WRITES=0\n";
