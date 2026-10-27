<?php
/** Standalone Andy Core marketing routing/legacy Landing Pages ownership test. */
define('ABSPATH','/');
class WooCommerce {}
class Andy_Commerce_Admin {}
class YBY_Module_Registry { public static function is_enabled($name) { return true; } }
function current_user_can($cap) { return in_array($cap,array('manage_options','manage_woocommerce','edit_pages','edit_posts',
    'andy_core_settings_manage','andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view','manage_categories'),true); }
function admin_url($path='') { return 'https://dev.example.test/wp-admin/'.$path; }
function sanitize_key($value) { return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$value)); }
function wp_unslash($value) { return $value; }
function test_true($condition,$msg) { if(!$condition)throw new RuntimeException($msg); }
require_once dirname(__DIR__) . '/includes/class-andy-campaign-lite.php';
require_once dirname(__DIR__,3) . '/admin/class-yby-andy-os-navigation.php';

$groups=YBY_Andy_OS_Navigation::sections();
test_true(isset($groups['andy-core-marketing']),'marketing L2 provided by Core');
test_true(isset($groups['andy-core-commerce']),'Commerce L2 preserved');
test_true(YBY_Andy_OS_Navigation::destination('andy-core-marketing')==='admin.php?page=andy-campaign&tab=campaigns',
    'marketing routes to Campaign when present');
$tabs=YBY_Andy_OS_Navigation::tertiary_items('andy-campaign');
test_true(array_column($tabs,'title')===array('活动列表','执行记录','系统设置'),'Core owns Campaign L3');
$content=YBY_Andy_OS_Navigation::tertiary_items('andy-business');
$urls=array_column($content,'url');
test_true(in_array(admin_url('edit.php?post_type=landing_page'),$urls,true),'native landing page list unchanged');
test_true(in_array(admin_url('post-new.php?post_type=landing_page'),$urls,true),'native create landing page unchanged');
$_GET=array('page'=>'andy-campaign','tab'=>'campaigns');
test_true(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing','Campaign highlights Marketing');
$_GET=array('post_type'=>'landing_page');
test_true(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-content','landing CPT stays Content');
echo "ANDY_CAMPAIGN_CORE_NAV_PASS=8\n";
