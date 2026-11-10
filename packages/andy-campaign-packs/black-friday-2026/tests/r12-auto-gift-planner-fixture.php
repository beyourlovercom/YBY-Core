<?php
declare(strict_types=1);
define('ABSPATH','/');
class WC_Cart {
    public function __construct(private array $lines){}
    public function get_cart(): array {return $this->lines;}
}
class MockProduct {
    public function __construct(private int $id,private string $sku,private bool $stock=true){}
    public function get_sku(){return $this->sku;}
    public function is_type($t){return $t==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return $this->stock;}
    public function backorders_allowed(){return false;}
}
$GLOBALS['mockProducts']=[
  11=>new MockProduct(11,'FREE-11'),
  12=>new MockProduct(12,'FREE-12'),
  13=>new MockProduct(13,'FREE-13'),
  14=>new MockProduct(14,'PREMIUM-14')
];
function wc_get_product($id){return $GLOBALS['mockProducts'][$id]??false;}
function get_woocommerce_currency(){return 'USD';}
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r9c-woo-adapter.php';
require __DIR__.'/../includes/class-byl-bf2026-r12-auto-gift-planner.php';
$n=0;
function yes($value,$note){global $n;if(!$value)throw new RuntimeException('FAILED '.$note);++$n;}
$manifest=[];
foreach ([11,12,13] as $id) {
 $manifest[$id]=[
  'product_id'=>$id,'sku'=>'FREE-'.$id,'approved'=>true,
  'warehouse_verified'=>true,'warehouse_slug'=>'us-ny',
  'available_units'=>20,'upgrade_minor'=>0,'allowed_countries'=>['US','CA']
 ];
}
$manifest[14]=[
 'product_id'=>14,'sku'=>'PREMIUM-14','approved'=>true,
 'warehouse_verified'=>true,'warehouse_slug'=>'us-la',
 'available_units'=>5,'upgrade_minor'=>1000,'allowed_countries'=>['US']
];
$seed='session-seed-1234567890-foobar';
$merch=static fn(float $price)=>['product_id'=>9096,'quantity'=>1,'line_total'=>$price];
$free=static fn(int $id)=>[
 'product_id'=>$id,'quantity'=>1,'line_total'=>0,
 'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true
];
$paid=static fn()=>[
 'product_id'=>14,'quantity'=>1,'line_total'=>10,
 'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true,
 '_byl_bf2026_paid_slot'=>true
];
$plan=static fn($rows,$country='US',$m=null,$s=null)=>
 BYL_BF2026_R12_Auto_Gift_Planner::plan(new WC_Cart($rows),$country,$m??$GLOBALS['gift_manifest'],$s??$GLOBALS['seed']);
$GLOBALS['gift_manifest']=$manifest;$GLOBALS['seed']=$seed;
$x=$plan(['product'=>$merch(69)]);
yes($x['ok'] && $x['slots']===1,'one free slot below 99');
yes(count($x['add_free_ids'])===1 && $x['paid_slots']===0,'free one auto selected');
yes($x['basis_minor']===6900,'69 dollar coupon basis');
$y=$plan(['product'=>$merch(217)]);
yes($y['ok']&&$y['slots']===2&&count($y['add_free_ids'])===2,'199 two free gifts');
yes(count(array_unique($y['add_free_ids']))===2,'two distinct products');
$repeat=$plan(['product'=>$merch(217)]);
yes($repeat['add_free_ids']===$y['add_free_ids'],'stable deterministic across retries');
$first=$y['add_free_ids'][0];
$existing=$plan(['product'=>$merch(217),'g1'=>$free($first)]);
yes($existing['ok']&&$existing['keep_free_ids']==[$first]&&count($existing['add_free_ids'])===1,'existing assigned gift kept');
yes(!in_array($first,$existing['add_free_ids'],true),'no duplicated gift');
$both=$plan(['product'=>$merch(217),'g1'=>$free($y['add_free_ids'][0]),'g2'=>$free($y['add_free_ids'][1])]);
yes($both['ok']&&!$both['add_free_ids']&&!$both['remove_keys'],'full slots stable');
$premium=$plan(['product'=>$merch(217),'p1'=>$paid()]);
yes($premium['ok']&&$premium['paid_slots']===1&&count($premium['add_free_ids'])===1,'premium plus one random');
$premiumFull=$plan(['product'=>$merch(217),'p1'=>$paid(),'g1'=>$free(11)]);
yes($premiumFull['ok']&&$premiumFull['paid_slots']===1&&!$premiumFull['add_free_ids'],'one premium and one free');
$premiumSmall=$plan(['product'=>$merch(138),'p1'=>$paid()]);
yes($premiumSmall['ok']&&$premiumSmall['paid_slots']===1&&!$premiumSmall['add_free_ids'],'99 premium replaces only slot');
$lower=$plan(['product'=>$merch(69),'p1'=>$paid()]);
yes($lower['ok']&&count($lower['remove_keys'])===1&&count($lower['add_free_ids'])===1,'premium removed after subtotal below 99');
$outside=$plan(['product'=>$merch(217),'p1'=>$paid()],'CA');
yes($outside['ok']&&count($outside['remove_keys'])===1&&count($outside['add_free_ids'])===2,'non-US premium revoked, two global free');
yes($plan(['product'=>$merch(69)],'')===['ok'=>false,'reason'=>'shipping_country_required'],'unknown country fail closed');
$empty=$plan([]);
yes($empty['ok']&&$empty['slots']===0&&!$empty['add_free_ids'],'no merchandise no gifts');
$revoked=$plan(['gift'=>$free(11)]);
yes($revoked['ok']&&$revoked['slots']===0&&count($revoked['remove_keys'])===1,'gift removed when cart emptied');
$duplicate=$plan(['product'=>$merch(217),'g1'=>$free(11),'g2'=>$free(11)]);
yes($duplicate['ok']&&count($duplicate['remove_keys'])===1,'duplicate gift quarantined');
$bad=$manifest;$bad[11]['approved']=false;$bad[12]['approved']=false;$bad[13]['approved']=false;
$x=$plan(['product'=>$merch(217)],'US',$bad);
yes(!$x['ok']&&$x['reason']==='insufficient_destination_stocked_gifts','unapproved pool no-go');
$bad=$manifest;$bad[11]['sku']='WRONG';$bad[12]['available_units']=0;
$x=$plan(['product'=>$merch(217)],'US',$bad);
yes(!$x['ok'] && $x['reason']==='insufficient_destination_stocked_gifts','mismatched SKU and zero warehouse stock rejected');
unset($GLOBALS['mockProducts'][11]);
$x=$plan(['product'=>$merch(217)]);
yes($x['ok']&&count($x['add_free_ids'])===2&&!in_array(11,$x['add_free_ids'],true),'missing Woo product never added');
echo 'BF2026_R12_AUTO_GIFT_PLANNER_FIXTURE_PASS='.$n.PHP_EOL;

