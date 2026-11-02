<?php
/** R10-H3-C real Woo (owned localhost) snapshot/notice audit. */
if(!is_file(ABSPATH.'.andy-r10b-isolated-local')
 ||get_option('siteurl')!=='http://127.0.0.1:14141'
 ||!defined('WC_VERSION')||WC_VERSION!=='11.2.1')exit(3);
require_once dirname(__DIR__).'/includes/class-andy-commerce-r10h3c-full-rollback-shadow.php';
$cart=WC()->cart;$session=WC()->session;
if(!is_object($cart)||!is_object($session)
 ||count($cart->get_cart())!==0
 ||count($cart->get_applied_coupons())!==0)exit(3);
$gate='Andy_Commerce_R10H3C_Full_Rollback_Shadow';
$noticeBefore=wc_get_notices();$passed=0;
$assert=function($ok,$name)use(&$passed){
 echo 'H3C_NATIVE_ROLLBACK_'.strtoupper($name).'='.($ok?'PASS':'FAIL')."\n";
 if(!$ok)throw new RuntimeException('H3C_NATIVE_ROLLBACK '.$name);
 $passed++;
};
try {
 $before=$gate::capture($cart,$session);
 $assert(is_array($before),'snapshot_with_real_Woo_totals');
 $assert($gate::exactly_restored($before,$gate::capture($cart,$session)),'same_unchanged');
 wc_add_notice('H3C isolated synthetic message','error');
 $afterNotice=$gate::capture($cart,$session);
 $assert(!$gate::exactly_restored($before,$afterNotice),'notice_mutation_detected');
 wc_set_notices($noticeBefore);
 $assert($gate::exactly_restored($before,$gate::capture($cart,$session)),'notice_restoration_detected');
 echo 'H3C_NATIVE_WC_FULL_SNAPSHOT='.$passed.'/4 PASS; NO_COUPON_APPLY_NO_ORDERS_NO_PAYMENT'."\n";
}finally {
 wc_set_notices($noticeBefore);
}
