<?php
declare(strict_types=1);
define('ABSPATH','/m15-test/');
require_once dirname(__DIR__).'/includes/class-andy-commerce-m15-discount-allocation.php';
$count=0;
function yes(bool $ok,string $label):void {
 global $count; $count++;
 if(!$ok)throw new RuntimeException('M15_ALLOC_FAIL: '.$label);
}
function reject(callable $f,string $label):void {
 try {$f();throw new RuntimeException('M15_SHOULD_REJECT: '.$label);}
 catch(InvalidArgumentException $e){yes(true,$label);}
}
$lines=[
 ['product_id'=>101,'quantity'=>2,'discounted_line_minor'=>8000,'eligible_for_promotion'=>true],
 ['product_id'=>202,'quantity'=>1,'discounted_line_minor'=>4500,'eligible_for_promotion'=>true],
 ['product_id'=>303,'quantity'=>1,'discounted_line_minor'=>6000,'eligible_for_promotion'=>true],
];
$plan=static fn(string $type,array $r,int $c,?array $l=null)
    =>Andy_Commerce_M15_Discount_Allocation::plan($type,$r,$l ?? $lines,$c);
$b=$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1],2250);
yes($b['allocated_discount_usd_minor']===2250,'BOGO total');
yes(count($b['sku_lines'])===1,'BOGO only reward product');
yes($b['sku_lines'][0]['product_id']===202,'BOGO reward SKU not buyer');
yes($b['sku_lines'][0]['unit_discounts_usd_minor']===[2250],'BOGO unit amount');
$bundle=$plan('bundles',['product_ids'=>[101,303]],2300);
yes(count($bundle['sku_lines'])===2,'bundle exact two product lines');
yes(array_sum(array_column($bundle['sku_lines'],'discount_usd_minor'))===2300,'Bundle cents exact');
yes(!in_array(202,array_column($bundle['sku_lines'],'product_id'),true),'Bundle excludes third SKU');
yes($bundle['sku_lines'][0]['discount_usd_minor']===1314,'Bundle amount on 80 USD line');
yes($bundle['sku_lines'][1]['discount_usd_minor']===986,'Bundle amount on 60 USD line');
yes(array_sum($bundle['sku_lines'][0]['unit_discounts_usd_minor'])===1314,'Bundle per unit remainder');
foreach([['spend_save',1200],['tiered',3700]] as [$type,$amount]) {
 $r=$plan($type,[],$amount);
 yes(array_sum(array_column($r['sku_lines'],'discount_usd_minor'))===$amount,$type.' sum');
 yes($r['checkout_mutations']===0,$type.' read-only');
}
$dup=[
 ['product_id'=>101,'quantity'=>1,'discounted_line_minor'=>1,'eligible_for_promotion'=>true],
 ['product_id'=>101,'quantity'=>1,'discounted_line_minor'=>1,'eligible_for_promotion'=>true]
];
reject(fn()=>$plan('spend_save',[],1,$dup),'duplicate SKU');
reject(fn()=>$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>999,'get_qty'=>1],100),'reward missing');
reject(fn()=>$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>2],100),'insufficient reward quantity');
reject(fn()=>$plan('bundles',['product_ids'=>[101,101]],10),'duplicate bundle set');
reject(fn()=>$plan('bundles',['product_ids'=>[101,404]],10),'missing bundle member');
reject(fn()=>$plan('bundles',['product_ids'=>[101,303]],20000),'discount too big');
reject(fn()=>$plan('tiered',[],20000),'general discount bigger than cart');
$notEligible=$lines;$notEligible[1]['eligible_for_promotion']=false;
reject(fn()=>$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1],2250,$notEligible),'excluded reward line');
$rewardTwo=$lines;$rewardTwo[1]['quantity']=2;
$part=$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1],2250,$rewardTwo);
yes($part['sku_lines'][0]['unit_discounts_usd_minor']===[2250,0],'BOGO free/discounted first reward unit only');
$rewardThree=$lines;$rewardThree[1]['quantity']=3;$rewardThree[1]['discounted_line_minor']=4500;
$part=$plan('bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1],750,$rewardThree);
yes($part['sku_lines'][0]['unit_discounts_usd_minor']===[750,0,0],'BOGO multi-unit remainder');
$one=[
 ['product_id'=>11,'quantity'=>3,'discounted_line_minor'=>300,'eligible_for_promotion'=>true],
];
$split=$plan('spend_save',[],101,$one);
yes($split['sku_lines'][0]['unit_discounts_usd_minor']===[34,34,33],'Stable 1-cent refund attribution');
$two=[
 ['product_id'=>2,'quantity'=>1,'discounted_line_minor'=>1,'eligible_for_promotion'=>true],
 ['product_id'=>1,'quantity'=>1,'discounted_line_minor'=>1,'eligible_for_promotion'=>true],
];
$tie=$plan('tiered',[],1,$two);
yes($tie['sku_lines'][0]['product_id']===1&&$tie['sku_lines'][0]['discount_usd_minor']===1,'Stable SKU ID tie');
yes($tie['sku_lines'][1]['discount_usd_minor']===0,'No duplicate remainder');
echo "M15_SKU_ALLOCATION_PASS=".$count." EXACT_CENTS=YES BOGO_GET_ONLY=YES SKU_REFUND_UNITS=YES\n";
