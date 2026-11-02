<?php
/** H3-C deterministic vendor reward fail-closed contract. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
class WC_Coupon {
 public $kind,$amt,$ship,$id;
 function __construct($kind,$amt='10',$ship=false,$id=0){
  $this->kind=$kind;$this->amt=$amt;$this->ship=$ship;$this->id=$id;}
 function get_discount_type(){return $this->kind;}
 function get_amount(){return $this->amt;}
 function get_free_shipping(){return $this->ship;}
 function get_id(){return $this->id;}
}
$GLOBALS['h3c_meta']=[1=>['_wt_make_auto_coupon'=>['1'],'wc_sc_auto_apply_coupon'=>['yes']],
 2=>['_wt_gc_user_wallet_coupon'=>['1']],3=>['wc_sc_free_product_ids'=>['12']],
 4=>['wbte_sc_bogo_product_ids'=>['44']],5=>['wt_marketing_promo'=>['x']]];
function get_post_meta($id){return $GLOBALS['h3c_meta'][$id]??[];}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-reward-protection-shadow.php';
$c='Andy_Commerce_R10H3C_Reward_Protection_Shadow';
$tests=[
 ['ordinary_percent',new WC_Coupon('percent','10'),true,'ordinary'],
 ['ordinary_fixed_cart',new WC_Coupon('fixed_cart','5'),true,'ordinary'],
 ['ordinary_fixed_product',new WC_Coupon('fixed_product','5'),true,'ordinary'],
 ['ordinary_with_auto_markers',new WC_Coupon('fixed_cart','5',false,1),true,'ordinary'],
 ['native_free_shipping',new WC_Coupon('fixed_cart','5',true),true,'protected'],
 ['storeapps_store_credit',new WC_Coupon('smart_coupon','10'),true,'protected'],
 ['webtoffee_store_credit',new WC_Coupon('store_credit','10'),true,'protected'],
 ['webtoffee_bogo',new WC_Coupon('wt_sc_bogo','1'),true,'protected'],
 ['webtoffee_old_bogo',new WC_Coupon('wbte_sc_bogo','1'),true,'protected'],
 ['unknown_vendor_type',new WC_Coupon('unknown','10'),true,'unknown'],
 ['zero_value',new WC_Coupon('percent','0'),true,'protected'],
 ['negative_value',new WC_Coupon('fixed_cart','-5'),true,'unknown'],
 ['invalid_amount',new WC_Coupon('fixed_cart','bad'),true,'unknown'],
 ['unverified_vendor_versions',new WC_Coupon('percent','10'),false,'unknown'],
 ['gift_wallet_meta',new WC_Coupon('fixed_cart','10',false,2),true,'protected'],
 ['storeapps_free_product_meta',new WC_Coupon('fixed_cart','10',false,3),true,'protected'],
 ['webtoffee_bogo_meta',new WC_Coupon('fixed_cart','10',false,4),true,'protected'],
 ['unknown_vendor_extension_meta',new WC_Coupon('percent','10',false,5),true,'protected'],
 ['no_coupon',null,true,'unknown'],
];
$n=0;$fail=0;foreach($tests as [$name,$coupon,$version,$expect]){
 $v=$c::classify($coupon,$version);
 $ok=$v['status']===$expect&&$v['writes_performed']===0&&
   ($v['allow_ordinary_removal']===($expect==='ordinary'));
 echo 'R10H3C_REWARD_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)$fail++;$n++;
}
echo "R10H3C_REWARD_PROTECTION=".($n-$fail)."/".$n."\n";
exit($fail?1:0);
