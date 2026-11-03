<?php
/** H3-B StoreApps pure runtime-filter source check, no WP/site writes. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
class WC_Coupon {
 private string $code;private string $type;private string $amount;private bool $free;
 function __construct(string $code,string $type='fixed_cart',string $amount='10',bool $free=false){
  $this->code=$code;$this->type=$type;$this->amount=$amount;$this->free=$free;}
 function get_code(){return $this->code;}
 function get_discount_type(){return $this->type;}
 function get_amount(){return $this->amount;}
 function get_free_shipping(){return $this->free;}
}
class H3BStoreAppsCart {
 public array $codes=['manual15'];
 function has_discount(string $code):bool{return in_array($code,$this->codes,true);}
}
$GLOBALS['cart']=new H3BStoreAppsCart();
function WC(){return (object)['cart'=>$GLOBALS['cart']];}
class WC_SC_Auto_Apply_Coupon {}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3b-storeapps-reapply-shadow.php';
$f='Andy_Commerce_R10H3B_StoreApps_Reapply_Shadow';
$make=static fn($coupon)=>['coupon_obj'=>$coupon,'cart_obj'=>WC()->cart,'cart_total'=>100];
$auto=new WC_Coupon('auto10');
$other=new WC_Coupon('auto20');
$free=new WC_Coupon('auto10','fixed_cart','0',true);
$credit=new WC_Coupon('auto10','store_credit','10');
$gift=new WC_Coupon('auto10','gift','10');
$zero=new WC_Coupon('auto10','fixed_cart','0');
$unknown=new WC_Coupon('auto10','unknown','10');
$manual=new WC_Coupon('manual15','fixed_cart','15');
$tests=[
 'verified_auto_rejected'=>$f::filter(true,$make($auto),'manual15','auto10',true)===false,
 'vendor_denial_preserved'=>$f::filter(false,$make($auto),'manual15','auto10',true)===false,
 'untrusted_no_filter'=>$f::filter(true,$make($auto),'manual15','auto10',false)===true,
 'other_auto_untouched'=>$f::filter(true,$make($other),'manual15','auto10',true)===true,
 'free_shipping_untouched'=>$f::filter(true,$make($free),'manual15','auto10',true)===true,
 'store_credit_untouched'=>$f::filter(true,$make($credit),'manual15','auto10',true)===true,
 'gift_untouched'=>$f::filter(true,$make($gift),'manual15','auto10',true)===true,
 'zero_untouched'=>$f::filter(true,$make($zero),'manual15','auto10',true)===true,
 'unknown_untouched'=>$f::filter(true,$make($unknown),'manual15','auto10',true)===true,
 'manual_coupon_not_auto'=>$f::filter(true,$make($manual),'manual15','auto10',true)===true,
 'null_vendor_context_denied_to_change'=>$f::filter(true,null,'manual15','auto10',true)===true,
 'context_without_coupon_denied_to_change'=>$f::filter(true,['cart_total'=>100],'manual15','auto10',true)===true,
];
$GLOBALS['cart']->codes=[];
$tests['manual_missing_do_not_suppress']=$f::filter(true,$make($auto),'manual15','auto10',true)===true;
$total=count($tests);$fail=0;
foreach($tests as $name=>$pass){echo 'R10H3B_SA_'.strtoupper($name).'='.($pass?'PASS':'FAIL')."\n";if(!$pass)$fail++;}
echo 'R10H3B_STOREAPPS_SOURCE_SHADOW='.($total-$fail).'/'.$total."\n";
exit($fail?1:0);
