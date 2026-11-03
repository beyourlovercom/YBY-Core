<?php
/** H3-B pure WebToffee replay suppression policy, no WP boot/writes. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
define('WC_VERSION','11.2.1');
final class WC_Coupon {
 private string $code;private string $type;private string $amount;private bool $free;
 function __construct(string $code,string $type='fixed_cart',string $amount='10',bool $free=false){
 $this->code=$code;$this->type=$type;$this->amount=$amount;$this->free=$free;}
 function get_code(){return $this->code;}
 function get_discount_type(){return $this->type;}
 function get_amount(){return $this->amount;}
 function get_free_shipping(){return $this->free;}
}
final class H3B_Test_Cart {
 public array $codes=['manual15'];
 function has_discount($code):bool{return in_array($code,$this->codes,true);}
}
$GLOBALS['h3b_cart']=new H3B_Test_Cart();
function WC(){return (object)['cart'=>$GLOBALS['h3b_cart']];}
class Wt_Smart_Coupon_Auto_Coupon_Common {
 static function is_auto_coupon($coupon):bool{return str_starts_with($coupon->get_code(),'auto');}
}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3b-webtoffee-reapply-shadow.php';
$tests=[];
function chk($name,$expr){$GLOBALS['tests'][$name]=(bool)$expr;}
$a=new WC_Coupon('auto10');
$other=new WC_Coupon('auto20');
$free=new WC_Coupon('autofree','fixed_cart','0',true);
$protected=new WC_Coupon('autocredit','store_credit','10');
$gift=new WC_Coupon('autogift','gift','1');
$unknown=new WC_Coupon('autounknown','special_provider','5');
$fixed=new WC_Coupon('autofixed','fixed_product','10');
$zero=new WC_Coupon('autozero','fixed_cart','0');
$manual=new WC_Coupon('manual15');
$guard='Andy_Commerce_R10H3B_WebToffee_Reapply_Shadow';
chk('trusted_auto_is_suppressed',$guard::is_valid(true,$a,'manual15','auto10',true)===false);
chk('existing_vendor_veto_preserved',$guard::is_valid(false,$a,'manual15','auto10',true)===false);
chk('missing_origin_proof_does_not_suppress',$guard::is_valid(true,$a,'manual15','auto10',false)===true);
chk('unrelated_auto_preserved',$guard::is_valid(true,$other,'manual15','auto10',true)===true);
chk('manual_code_not_auto_preserved',$guard::is_valid(true,$manual,'manual15','manual15',true)===true);
chk('free_shipping_entitlement_preserved',$guard::is_valid(true,$free,'manual15','autofree',true)===true);
chk('store_credit_entitlement_preserved',$guard::is_valid(true,$protected,'manual15','autocredit',true)===true);
chk('gift_entitlement_preserved',$guard::is_valid(true,$gift,'manual15','autogift',true)===true);
chk('unknown_vendor_type_preserved',$guard::is_valid(true,$unknown,'manual15','autounknown',true)===true);
chk('zero_coupon_preserved',$guard::is_valid(true,$zero,'manual15','autozero',true)===true);
chk('fixed_product_auto_suppressed',$guard::is_valid(true,$fixed,'manual15','autofixed',true)===false);
$all=['auto10'=>$a,'autofree'=>$free,'autogift'=>$gift,'other'=>$other,'manual'=>$manual];
$filtered=$guard::candidates($all,'manual15','auto10',true);
chk('cached_candidates_target_only',array_keys($filtered)===['autofree','autogift','other','manual']);
chk('cached_candidates_unverified_unchanged',$guard::candidates($all,'manual15','auto10',false)===$all);
chk('uncached_false_input_unchanged',$guard::candidates(false,'manual15','auto10',true)===false);
$GLOBALS['h3b_cart']->codes=[];
chk('no_manual_coupon_no_suppression',$guard::is_valid(true,$a,'manual15','auto10',true)===true);
chk('no_manual_coupon_cached_unchanged',$guard::candidates($all,'manual15','auto10',true)===$all);
$failed=0;foreach($tests as $name=>$ok){echo 'R10H3B_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";if(!$ok)$failed++;}
echo 'R10H3B_WEBTOFFEE_SOURCE_SHADOW='.(count($tests)-$failed).'/'.count($tests)."\n";
exit($failed?1:0);
