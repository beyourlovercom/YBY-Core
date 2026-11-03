<?php
/** R10-H3 exact-vendor server-session provenance contract (no WP writes). */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
define('WC_VERSION','11.2.1');
$GLOBALS['h3_versions']=[
 'woocommerce/woocommerce.php'=>['Version'=>'11.2.1'],
 'yaycurrency-pro/yay-currency.php'=>['Version'=>'3.3.5'],
 'wt-smart-coupon-pro/wt-smart-coupon-pro.php'=>['Version'=>'3.2.0'],
 'woocommerce-smart-coupons/woocommerce-smart-coupons.php'=>['Version'=>'9.51.0'],
];
$GLOBALS['h3_inactive']=[];
function get_plugins(){return $GLOBALS['h3_versions'];}
function is_plugin_active($key){return !isset($GLOBALS['h3_inactive'][$key]);}
class H3Session {
 public array $data=[];
 function get($key,$default=[]){return $this->data[$key]??$default;}
}
class H3Cart {
 public array $applied=[];
 function get_applied_coupons(){return $this->applied;}
}
$GLOBALS['h3_session']=new H3Session();
$GLOBALS['h3_cart']=new H3Cart();
class WC_SC_Auto_Apply_Coupon {
 static $allow=true;
 static function get_instance(){return new self();}
 public function is_coupon_applied_by_auto_apply($code){
  $c=$GLOBALS['h3_session']->get('wc_sc_auto_applied_coupons',[]);
  return self::$allow && in_array(strtolower((string)$code),array_map('strtolower',(array)$c),true);
 }
}
require dirname(__DIR__).'/includes/class-andy-commerce-r10h3-vendor-auto-provenance.php';
$pass=0;$fail=0;
function verify($name,$expected,$code='auto10'){
 global $pass,$fail;
 $r=Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect($code,$GLOBALS['h3_cart'],$GLOBALS['h3_session']);
 $ok=$r['status']===$expected && $r['writes_performed']===0;
 if($ok)$pass++;else{$fail++;echo 'FAIL '.$name.' GOT='.$r['status']."\n";}
 echo 'H3_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
}
$GLOBALS['h3_cart']->applied=['AUTO10','SHIPFREE','CREDIT'];
verify('no_auto_claims','not_auto_proven');
$GLOBALS['h3_session']->data['auto_applied_coupons']=['AUTO10'];
verify('webtoffee_verified','auto_verified');
verify('webtoffee_no_cart_code','not_applied','ghost');
$GLOBALS['h3_session']->data=['wc_sc_auto_applied_coupons'=>['AUTO10']];
verify('storeapps_verified','auto_verified');
WC_SC_Auto_Apply_Coupon::$allow=false;
verify('storeapps_runtime_mismatch','unverified_vendor_runtime');
WC_SC_Auto_Apply_Coupon::$allow=true;
$GLOBALS['h3_session']->data['auto_applied_coupons']=['AUTO10'];
verify('dual_vendor_ambiguous','ambiguous_dual_vendor');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>['OTHER']];
verify('stale_other_coupon','not_auto_proven');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>['AUTO10'],'wc_sc_auto_applied_coupons'=>'malformed'];
verify('bad_storeapps_session','unverified_vendor_session');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>'malformed'];
verify('bad_webtoffee_session','unverified_vendor_session');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>[false]];
verify('nonstring_session_value','unverified_vendor_session');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>['auto10']];
verify('coupon_case_insensitive','auto_verified');
$GLOBALS['h3_cart']->applied=[];
verify('session_claim_no_cart','not_applied');
$GLOBALS['h3_cart']->applied=['auto10'];
$GLOBALS['h3_inactive']['wt-smart-coupon-pro/wt-smart-coupon-pro.php']=true;
verify('vendor_inactive_fail_closed','unverified_runtime');
unset($GLOBALS['h3_inactive']['wt-smart-coupon-pro/wt-smart-coupon-pro.php']);
$GLOBALS['h3_versions']['wt-smart-coupon-pro/wt-smart-coupon-pro.php']['Version']='3.2.1';
verify('version_drift_fail_closed','unverified_runtime');
$GLOBALS['h3_versions']['wt-smart-coupon-pro/wt-smart-coupon-pro.php']['Version']='3.2.0';
verify('exact_version_recovered','auto_verified');
verify('invalid_code','unverified_runtime','');
$GLOBALS['h3_cart']->applied=['auto10',new stdClass()];
verify('invalid_cart_snapshot','unverified_cart');
$GLOBALS['h3_cart']->applied=['auto10'];
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>array_fill(0,101,'auto10')];
verify('huge_session_list','unverified_vendor_session');
$GLOBALS['h3_session']->data=['wc_sc_auto_apply_coupon_ids'=>[1111],'auto_applied_coupons'=>[]];
verify('eligibility_list_not_origin','not_auto_proven');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>[],'wc_sc_auto_applied_coupons'=>['auto10']];
verify('storeapps_only_verified','auto_verified');
$GLOBALS['h3_cart']->applied=['auto10','AUTO10'];
verify('duplicate_cart_claim_denied','unverified_cart');
$GLOBALS['h3_cart']->applied=['auto10'];
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>['auto10','AUTO10']];
verify('duplicate_vendor_claim_denied','unverified_vendor_session');
$GLOBALS['h3_session']->data=['auto_applied_coupons'=>['auto10']];
$GLOBALS['h3_versions']['yaycurrency-pro/yay-currency.php']['Version']='3.4.0';
verify('yay_version_drift_denied','unverified_runtime');
$GLOBALS['h3_versions']['yaycurrency-pro/yay-currency.php']['Version']='3.3.5';
echo 'R10H3_PROVIDER_PROVENANCE_TOTAL='.$pass.'/'.($pass+$fail)."\n";
exit($fail?1:0);
