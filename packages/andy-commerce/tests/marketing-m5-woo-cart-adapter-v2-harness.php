<?php
/** M5 eligibility & shipping truth adapter v2 isolated source-only contract. */
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-progress-projection.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m5-woo-cart-adapter.php';
$N=0;
$ok=static function(bool $v,string $reason)use(&$N):void{
  $N++;if (!$v)throw new RuntimeException("M5 Woo Adapter FAIL: ".$reason);
};
$new='post_discount_merchandise_subtotal_excluding_tax_and_shipping';
$old='pre_discount_merchandise_subtotal';
$case=[
  'items'=>[
    ['key'=>'a','is_server_eligible'=>true,'prices'=>['currency_code'=>'USD','currency_minor_unit'=>2],
      'totals'=>['line_total'=>'4500']],
    ['key'=>'gift','is_server_eligible'=>false,'prices'=>['currency_code'=>'USD','currency_minor_unit'=>2],
      'totals'=>['line_total'=>'7000']],
  ],
  'shipping_rates'=>[['shipping_rates'=>[['selected'=>true,'rate_id'=>'flat_rate:7'],
    ['selected'=>false,'rate_id'=>'free_shipping:3']]]],
  'totals'=>['currency_code'=>'USD','currency_minor_unit'=>2,'total_items'=>'11500'],
];
$eligible=static fn(array $line):bool=>$line['is_server_eligible']===true;
$eval=static fn(array $cart,string $basis=$new,bool $trusted=true,?callable $predicate=$eligible):array=>
  Andy_Commerce_M5_Progress_Projection::project(
    Andy_Commerce_M5_Woo_Cart_Adapter::source($cart,$basis,4900,$trusted,$predicate)
  );
$x=$eval($case);
$ok($x['show_progress']===true && $x['qualified_minor']===4500
    && $x['remaining_minor']===400,'gift line 7000 is excluded');
$ok($x['percent']===91,'progress fraction');
$ok(!$x['claim_free_shipping'] && !$x['gift_enabled'],'no shipping award or free gift');
$x=$eval($case,$old);
$ok(!$x['show_progress'] && $x['reason']==='legacy_policy_migration_pending','legacy shipping basis hides');
$x=$eval($case,$new,false);
$ok(!$x['show_progress'] && $x['reason']==='woo_authority_unconfirmed','untrusted snapshot hides');
$x=$eval($case,$new,true,null);
$ok(!$x['show_progress'],'missing server predicate hides');
$mut=$case;$mut['items'][0]['prices']['currency_code']='EUR';
$ok(!$eval($mut)['show_progress'],'item currency mismatch hidden');
$mut=$case;$mut['items'][0]['prices']['currency_minor_unit']=3;
$ok(!$eval($mut)['show_progress'],'item currency decimals mismatch hidden');
$mut=$case;$mut['shipping_rates'][]=$mut['shipping_rates'][0];
$ok(!$eval($mut)['show_progress'],'two shipping packages hidden');
$mut=$case;$mut['shipping_rates'][0]['shipping_rates'][0]['selected']=false;
$ok(!$eval($mut)['show_progress'],'no Woo selected rate hidden');
$mut=$case;$mut['shipping_rates'][0]['shipping_rates'][1]['selected']=true;
$ok(!$eval($mut)['show_progress'],'two selected rates hidden');
$mut=$case;$mut['shipping_rates'][0]['shipping_rates'][0]['rate_id']='flat_rate:<script>';
$ok(!$eval($mut)['show_progress'],'malicious selected rate ID hidden');
$mut=$case;$mut['items'][0]['totals']['line_total']='-1';
$ok(!$eval($mut)['show_progress'],'negative line total hidden');
$mut=$case;$mut['items'][0]['totals']['line_total']='01';
$ok(!$eval($mut)['show_progress'],'noncanonical amount hidden');
$mut=$case;$mut['items'][0]['totals']['line_total']='1000000001';
$ok(!$eval($mut)['show_progress'],'overflow hidden');
$mut=$case;$mut['items'][0]['totals']['line_total']='6000';
$x=$eval($mut);$ok($x['remaining_minor']===0 && $x['percent']===100
    && !$x['claim_free_shipping'],'100 percent never claims free award');
$mut=$case;$mut['items']=[];
$ok(!$eval($mut)['show_progress'],'empty cart hidden');
$mut=$case;$mut['totals']['currency_code']='US1';
$ok(!$eval($mut)['show_progress'],'invalid ISO currency hidden');
$mut=$case;$mut['items'][0]['is_server_eligible']=false;
$ok(!$eval($mut)['show_progress'],'gift-only no qualifying line hidden');
$badPredicate=static fn(array $line):string=>'yes';
$ok(!$eval($case,$new,true,$badPredicate)['show_progress'],'invalid server predicate output hidden');
$throwing=static function(array $line):bool {throw new RuntimeException('boom');};
$ok(!$eval($case,$new,true,$throwing)['show_progress'],'predicate exception fail closed');
$mut=$case;$mut['items'][1]['totals']['line_total']='NaN';
$ok(!$eval($mut)['show_progress'],'invalid excluded gift source hidden');
$entry=file_get_contents(dirname(__DIR__).'/andy-commerce.php');
$ok(!str_contains($entry,'class-andy-commerce-m5-woo-cart-adapter.php'),'adapter not bootstrapped');
echo "M5_WOO_CART_ADAPTER_V2_PASS={$N}; GIFTS_EXCLUDED=YES; CURRENCY_CONSISTENT=YES; WRITES=0\n";
