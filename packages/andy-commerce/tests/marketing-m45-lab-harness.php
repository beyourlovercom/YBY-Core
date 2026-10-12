<?php
/** M4/M5 owner preview fixed-fixture and fail-closed route contract. */
declare(strict_types=1);
define('ABSPATH', '/dev/synthetic/');
define('ANDY_COMMERCE_PLUGIN_DIR', dirname(__DIR__).'/');
function current_user_can(string $cap):bool {return $cap==='manage_woocommerce';}
function wp_die(string $message, string $title='', array $params=[]):void {throw new RuntimeException($message);}
function sanitize_key(string $value):string {return preg_replace('/[^a-z0-9_-]/','',strtolower($value));}
function wp_unslash($value){return $value;}
function admin_url(string $path):string{return 'https://dev.beyourlover.com/wp-admin/'.$path;}
function add_query_arg(array $values,string $url):string{return $url.'?'.http_build_query($values);}
function esc_url(string $value):string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function esc_html(string $value):string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function esc_attr(string $value):string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
final class Andy_Commerce_M3_Dev_Bridge { public static function enabled():bool{return true;} }
final class Andy_Commerce_Shipping_Promotion_Policy {
  public const ELIGIBILITY_BASIS='pre_discount_merchandise_subtotal';
}
$tests=0;
$check=static function(bool $flag,string $name)use(&$tests):void{
  ++$tests;if(!$flag)throw new RuntimeException('M45 LAB FAIL '.$name);
};
$cases=[
  'discount'=>[6000,4500,true,false,400],
  'exact'=>[4900,4900,true,true,0],
  'gift'=>[4900,4900,false,false,5100],
  'ineligible'=>[4500,4500,false,false,400],
];
foreach ($cases as $name=>$expect) {
  $_GET=['m45_case'=>$name];
  ob_start();
  include dirname(__DIR__).'/admin/views/marketing-m45-shadow-lab.php';
  $html=ob_get_clean();
  $check((int)$shadow['legacy_merchandise_minor']===$expect[0],'legacy '.$name);
  $check((int)$shadow['proposed_merchandise_minor']===$expect[1],'new '.$name);
  $check($shadow['legacy_eligible']===$expect[2],'legacy eligibility '.$name);
  $check($shadow['proposed_eligible']===$expect[3],'new eligibility '.$name);
  $check(max(0,$shadow['threshold_minor']-$shadow['proposed_merchandise_minor'])===$expect[4],'remain '.$name);
  $check($progress['show_progress']===false
    && $progress['reason']==='legacy_policy_migration_pending','hidden '.$name);
  $check(str_contains($html,'模拟环境，所有优惠 OFF')
    && str_contains($html,'GET-only') && str_contains($html,'HIDDEN'),'safe user labeling '.$name);
  $check(!str_contains($html,'Free shipping applied')
    && !str_contains($html,'发布优惠'),'no misleading award '.$name);
}
$_GET=['m45_case'=>'../garbage'];ob_start();
include dirname(__DIR__).'/admin/views/marketing-m45-shadow-lab.php';
ob_end_clean();
$check($selected==='discount','invalid GET selection falls back');
$router=file_get_contents(dirname(__DIR__).'/admin/class-andy-commerce-admin.php');
$check(str_contains($router,"Andy_Commerce_M3_Dev_Bridge::enabled()")
    && str_contains($router,"'m45_preview'")
    && str_contains($router,'marketing-m45-shadow-lab.php'),'explicit Dev-only authenticated router');
$boot=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce.php');
$check(!str_contains($boot,'marketing-m45-shadow-lab.php'),'no runtime bootstrap');
echo "M45_LAB_HARNESS_PASS={$tests}; READ_ONLY=YES; PROMOTIONS=OFF\n";
