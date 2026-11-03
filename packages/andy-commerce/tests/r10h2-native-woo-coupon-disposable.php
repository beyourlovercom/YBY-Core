<?php
/**
 * R10-H2 native Woo metadata adapter/plan smoke. Disposable DB only.
 * Uses unsaved WC_Coupon objects; never applies a coupon to any cart.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME!=='commerce_p5_isolated'
    || get_option('siteurl')!=='http://commerce-p5.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
    || !class_exists('WC_Coupon')) {
    throw new RuntimeException('R10H2_DISPOSABLE_WOO_ONLY');
}
$file=WP_PLUGIN_DIR.'/andy-commerce/includes/class-andy-commerce-r10h2-manual-priority-shadow.php';
if(!is_file($file))throw new RuntimeException('R10H2_SHADOW_SOURCE_NOT_IN_ZIP');
require_once $file;
$test=function($result,$name){if(!$result)throw new RuntimeException('R10H2_NATIVE_FAIL: '.$name);};
$build=function($code,$kind,$amount,$ship,$origin){
    $c=new WC_Coupon();
    $c->set_code($code);
    $c->set_discount_type($kind);
    $c->set_amount($amount);
    $c->set_free_shipping($ship);
    $row=[
      'code'=>$c->get_code(),
      'type'=>$c->get_discount_type(),
      'amount'=>(string)$c->get_amount(),
      'free_shipping'=>$c->get_free_shipping(),
      // These trusted labels are issued by this isolated *server* fixture.
      // Woo itself does NOT certify the coupon's auto/manual origin.
      'origin'=>$origin,
      'origin_verified'=>true,
      'origin_evidence'=>$origin==='auto'?'trusted_vendor_auto_apply':
                         'trusted_server_customer_submission',
      'vendor'=>$origin==='auto'?'woocommerce':null,
    ];
    return [$c,$row];
};
[$wcAuto,$auto]=$build('auto10','percent','10',false,'auto');
[$wcManual,$manual]=$build('manual5','fixed_cart','5',false,'manual');
[$wcFree,$free]=$build('freecode','fixed_cart','0',true,'auto');
[$wcFixed,$fixed]=$build('manual7','fixed_product','7',false,'manual');
$test($wcAuto instanceof WC_Coupon && $auto['type']==='percent','WC native percent');
$test($wcManual instanceof WC_Coupon && $manual['type']==='fixed_cart','WC native fixed_cart');
$test($wcFixed instanceof WC_Coupon && $fixed['type']==='fixed_product','WC native fixed_product');
$test($wcFree->get_free_shipping()===true,'WC native freeship');
$p=Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($manual,[$auto,$free],true);
$test($p['action']==='replace_auto','native auto to manual plan');
$test($p['remove_codes']===['auto10'],'native only normal auto removal');
$test($p['writes_performed']===0,'no cart writes from planner');
$test(Andy_Commerce_R10H2_Manual_Priority_Shadow::snapshot_matches($p['expected_snapshot'],[$auto,$free]),'native same snapshot');
$test(!Andy_Commerce_R10H2_Manual_Priority_Shadow::snapshot_matches($p['expected_snapshot'],[$free,$auto]),'native order changed rejected');
$test(Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($auto,[$manual,$free],true)['action']==='deny','native auto reapply rejected');
$test(Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($free,[$manual],true)['action']==='keep','native free shipping retained');
$test(Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($fixed,[$manual],true)['action']==='deny','native second manual rejected');
$test(Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($manual,[$auto],false)['reason']==='vendor_veto','native veto retained');
$test(!has_filter('woocommerce_coupon_is_valid',
    ['Andy_Commerce_R10H2_Manual_Priority_Shadow','plan']),'no H2 Woo filter registration');
echo "R10H2_REAL_NATIVE_WC_COUPON_METADATA=14/14 PASS; HOOKS_ADDED=0; CART_WRITES=0; ORDERS=0\n";
