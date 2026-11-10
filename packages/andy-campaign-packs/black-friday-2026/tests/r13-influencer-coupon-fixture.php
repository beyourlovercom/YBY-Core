<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['influencer_coupon_ids']=[55];
$GLOBALS['active_sale_ids']=[9096,9118];
function apply_filters($hook,$value){
 if($hook==='byl_bf2026_verified_influencer_coupon_ids')
  return $GLOBALS['influencer_coupon_ids'];
 if($hook==='byl_bf2026_active_sale_product_ids')
  return $GLOBALS['active_sale_ids'];
 return $value;
}
class Coupon {public function __construct(private int $id){}public function get_id(){return $this->id;}}
class Product {
 public function __construct(private int $id,private int $parent=0){}
 public function get_id(){return $this->id;}
 public function get_parent_id(){return $this->parent;}
}
require __DIR__.'/../includes/class-byl-bf2026-r13-cart-writer.php';
$n=0;
function ok($x,$label){global $n;if(!$x)throw new RuntimeException('FAILED_'.$label);++$n;}
$affiliate=new Coupon(55);$regularCoupon=new Coupon(77);
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(9096),$affiliate,[])===false,'exact BF sku excluded');
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(999),$affiliate,[])===true,'ordinary item eligible');
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(9096),$regularCoupon,[])===true,'nonaffiliate coupon unaffected');
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(false,new Product(999),$affiliate,[])===false,'other coupon blocks preserved');
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(9999,9118),$affiliate,[])===false,'variation parent BF excluded');
$items=[
 (object)['object'=>['product_id'=>9096,'variation_id'=>0]],
 (object)['object'=>['product_id'=>999,'variation_id'=>0]],
 (object)['object'=>['product_id'=>999,'variation_id'=>0,
                    'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true]],
 (object)['object'=>['product_id'=>9118,'variation_id'=>42]],
];
$filtered=BYL_BF2026_R13_Cart_Writer::coupon_items($items,$affiliate,null);
ok(count($filtered)===1,'fixed cart coupon only ordinary item eligible');
ok($filtered[0]===$items[1],'ordinary item identity maintained');
ok(count(BYL_BF2026_R13_Cart_Writer::coupon_items($items,$regularCoupon,null))===4,'other coupon full list retained');
$GLOBALS['influencer_coupon_ids']=[];
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(9096),$affiliate,[])===true,'unknown affiliate must not be guessed');
ok(count(BYL_BF2026_R13_Cart_Writer::coupon_items($items,$affiliate,null))===4,'unknown coupon no filtering');
$GLOBALS['influencer_coupon_ids']=[55];
$GLOBALS['active_sale_ids']=[1234];
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(9096),$affiliate,[])===true,'manifest overrides old BF list');
ok(BYL_BF2026_R13_Cart_Writer::coupon_line(true,new Product(1234),$affiliate,[])===false,'new approved BF item excluded');
echo 'BF2026_R13_AFFILIATE_COUPON_FIXTURE_PASS='.$n.PHP_EOL;
