<?php
if (!is_file(ABSPATH.'.andy-r10b-isolated-local')
 || get_option('siteurl')!=='http://127.0.0.1:14141'
 || !defined('WC_VERSION') || WC_VERSION!=='11.2.1') exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3-vendor-auto-provenance.php';
$s=WC()->session;
if(!is_object($s)||!is_callable([$s,'get'])||!is_callable([$s,'set']))exit(3);
$keys=['auto_applied_coupons','wc_sc_auto_applied_coupons'];
$old=[];
foreach($keys as $k)$old[$k]=$s->get($k,null);
$cart=new class{
 function get_applied_coupons(){return ['auto10','freeship'];}
};
$assert=function($name,$expected)use($cart,$s){
 $data=Andy_Commerce_R10H3_Vendor_Auto_Provenance::inspect('auto10',$cart,$s);
 echo $name.'='.$data['status'].' provider='.($data['provider']??'none')."\n";
 if ($data['status']!==$expected)throw new RuntimeException($name.' EXPECTED '.$expected);
};
try {
 $s->set('auto_applied_coupons',['auto10']);
 $s->set('wc_sc_auto_applied_coupons',[]);
 $assert('H3_REAL_WEBTOFFEE_SESSION_CONTRACT','auto_verified');
 $s->set('auto_applied_coupons',[]);
 $s->set('wc_sc_auto_applied_coupons',['auto10']);
 $assert('H3_REAL_STOREAPPS_METHOD_CONTRACT','auto_verified');
 $s->set('auto_applied_coupons',['auto10']);
 $assert('H3_REAL_DUAL_PROVIDER_CONFLICT','ambiguous_dual_vendor');
 $s->set('auto_applied_coupons',[]);
 $s->set('wc_sc_auto_applied_coupons',[]);
 $assert('H3_REAL_NO_PROVENANCE_DENIED','not_auto_proven');
 echo "R10H3_EXACT_VENDOR_RUNTIME_SESSION_CONTRACT=4/4 PASS (synthetic session entries)\n";
} finally {
 foreach($keys as $k){
  if($old[$k]===null && is_callable([$s,'__unset']))$s->__unset($k);
  else $s->set($k,$old[$k]);
 }
}
