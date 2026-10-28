<?php
/**
 * A fork-safe CI probe of the exact Dev Core (V22 registry) navigation path.
 * Usage:
 *   php tests/andy-campaign-r5-live-nav-harness.php absent
 *   php tests/andy-campaign-r5-live-nav-harness.php present
 * No database, WP admin writes, or real checkout calls.
 */
define('ABSPATH','/');
$mode=$argv[1]??'';
if(!in_array($mode,array('absent','present'),true))exit(3);
class Andy_Commerce_Admin {}
if($mode==='present'){ class Andy_Campaign_Lite {} class WooCommerce {} }
class YBY_Module_Registry { public static function is_enabled($key):bool{return $key==='landing_pages';} }
class YBY_Andy_L3_Registry_V1 {
 public static function extend($entries,$page) {
  if($page==='andy-commerce') {
   $entries[]=array('url'=>'https://dev.example/wp-admin/admin.php?page=andy-commerce&tab=marketing-create','title'=>'新建活动');
   $entries[]=array('url'=>'https://dev.example/wp-admin/admin.php?page=andy-commerce&tab=currency-os','title'=>'多币种');
  }
  return $entries;
 }
}
function current_user_can($cap){return in_array($cap,array('manage_woocommerce','manage_options','edit_pages','andy_core_settings_manage'),true);}
function admin_url($path=''){return 'https://dev.example/wp-admin/'.$path;}
function sanitize_key($x){return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$x));}
function wp_unslash($x){return $x;}
function must($x,$label){if(!$x)throw new RuntimeException($label);}
require dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
$destination=YBY_Andy_OS_Navigation::destination('andy-core-marketing');
$_GET=array('page'=>'andy-commerce','tab'=>'marketing-promotions');
$commerce=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$labels=array_column($commerce,'title');
if($mode==='present'){
 must($destination==='admin.php?page=andy-campaign&tab=campaigns','Marketing L2 points to Campaign');
 $ct=YBY_Andy_OS_Navigation::tertiary_items('andy-campaign');
 must(array_column($ct,'title')===array('活动列表','执行记录','系统设置'),'new Campaign L3 available');
 must(!in_array('促销',$labels,true) && !in_array('新建活动',$labels,true),'legacy Marketing menu hidden');
 must(in_array('多币种',$labels,true),'existing Currency L3 registry retained');
 must(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-commerce','archived Commerce URL remains under Commerce');
 $_GET=array('page'=>'andy-campaign','tab'=>'campaigns');
 must(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing','Campaign highlights Marketing');
}else{
 must($destination==='admin.php?page=andy-commerce&tab=marketing-promotions','old Marketing destination restored');
 must(in_array('促销',$labels,true) && in_array('新建活动',$labels,true),'old Marketing L3 retained');
 must(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing','old Commerce archive highlights Marketing');
}
$content=YBY_Andy_OS_Navigation::tertiary_items('andy-business');
$urls=array_column($content,'url');
must(in_array(admin_url('edit.php?post_type=landing_page'),$urls,true),'Landing list unchanged');
must(in_array(admin_url('post-new.php?post_type=landing_page'),$urls,true),'Landing editor unchanged');
echo 'R5_DEV_CORE_NAV_'.$mode.'_PASS'.PHP_EOL;
