<?php
/** Synthetic Core/Commerce M10 L2/L3 routing, permissions, missing modules. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
$GLOBALS['test_woo']=true;
$GLOBALS['test_dev']=true;
$GLOBALS['test_currency']=true;
$GLOBALS['test_callbacks']=true;
$GLOBALS['test_filters']=[];
function is_admin(){return true;}
function current_user_can($cap){return $cap==='manage_woocommerce'&&$GLOBALS['test_woo'];}
function did_action($hook){return $hook==='admin_menu'?1:0;}
function get_plugin_page_hookname($page,$parent=''){return $parent?$parent.'_page_'.$page:'toplevel_page_'.$page;}
function has_action($hook){return $GLOBALS['test_callbacks']&&$hook==='toplevel_page_andy-commerce'?10:false;}
function admin_url($path=''){return 'https://dev.beyourlover.com/wp-admin/'.$path;}
function wp_strip_all_tags($text){return strip_tags($text);}
function __($text,$domain=''){return $text;}
function add_menu_page(...$args){return 'toplevel_page_andy-commerce';}
function add_submenu_page(...$args){return 'andy-commerce_page_andy-commerce';}
function add_filter($name,$callable,$priority=10,$args=1){$GLOBALS['test_filters'][$name][]=$callable;}
function apply_filters($hook,$items,$page){foreach($GLOBALS['test_filters'][$hook]??[] as $cb)$items=$cb($items,$page);return $items;}
function sanitize_key($v){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v));}
function wp_unslash($v){return $v;}
final class YBY_Module_Registry {public static function is_enabled($mod){return false;}}
final class Andy_Commerce_M7_Draft_Studio {public static function dev_only(){return $GLOBALS['test_dev'];}}
final class Andy_Commerce_Currency_V2_Dev {public static function access(){return $GLOBALS['test_currency']&&$GLOBALS['test_dev']&&$GLOBALS['test_woo'];}}
require dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
require dirname(__DIR__).'/packages/andy-commerce/admin/class-andy-commerce-admin.php';
$n=0;
$ok=static function(bool $test,string $why)use(&$n){if(!$test)throw new RuntimeException("M10 NAV FAIL: ".$why);$n++;};
(new Andy_Commerce_Admin())->add_admin_menu();
$_GET=['page'=>'andy-commerce','tab'=>'marketing-promotions'];
$expected=['活动列表','新建活动','活动模板','规则模拟','执行记录'];
$items=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$ok(array_column($items,'title')===$expected,'five Marketing L3, no Currency/Orders');
$ok(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing','Marketing L2 active for list');
$ok(str_contains($items[0]['url'],'tab=marketing-promotions'),'Core-owned list native route');
$ok(count(array_unique(array_column($items,'url')) )===5,'all routes unique');
$ok(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===$items,'idempotent');
foreach(['marketing-create','marketing-templates','marketing-simulate','marketing-logs'] as $tab){
    $_GET['tab']=$tab;
    $ok(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing',"$tab belongs to marketing");
    $ok(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===$expected,"$tab retains five items");
    $ok(Andy_Commerce_Admin::marketing_l3_route_exists($tab,'andy-commerce'),"$tab direct route allowlisted");
}
$_GET['tab']='overview';
$trade=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$ok(array_column($trade,'title')===['总览','订单导出','结账运费','设置','多币种'],'commerce rail excludes Marketing when plugin active');
$ok(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-commerce','Trading L2');
$_GET['tab']='marketing-create';
$GLOBALS['test_dev']=false;
$ok(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===['活动列表'],'Dev-only extra Marketing L3 hidden');
$ok(!Andy_Commerce_Admin::marketing_l3_route_exists('marketing-create','andy-commerce'),'no direct edit on nonDev');
$GLOBALS['test_dev']=true;
$GLOBALS['test_woo']=false;
$ok(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===[],'nonWoo role gets no Commerce L3');
$ok(!Andy_Commerce_Admin::marketing_l3_route_exists('marketing-create','andy-commerce'),'no direct editor for nonWoo role');
$GLOBALS['test_woo']=true;$GLOBALS['test_callbacks']=false;
$ok(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===['活动列表'],
    'no WordPress controller callback hides all third-party extension links');
$GLOBALS['test_callbacks']=true;
$ok(!Andy_Commerce_Admin::marketing_l3_route_exists('marketing-unsupported','andy-commerce'),
    'unknown Marketing tab fail closed');
$ok(Andy_Commerce_Admin::register_l3_items([], 'andy-core-newsletter')===[],
    'no registration outside Commerce host');
unset($GLOBALS['test_filters']['andy_core_l3_external_items']);
$ok(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===['活动列表'],
    'disabling Commerce declarations instantly removes four menu items');
$admin=file_get_contents(dirname(__DIR__).'/packages/andy-commerce/admin/views/marketing-m10-workbench.php');
$controller=file_get_contents(dirname(__DIR__).'/packages/andy-commerce/admin/class-andy-commerce-admin.php');
$ok(str_contains($controller,'marketing-m10-workbench.php')&&
    str_contains($controller,'marketing_l3_route_exists'), 'controller owns real registered routes');
foreach(['活动列表','新建活动','活动模板','规则模拟','执行记录','wp_nonce_field',
    'andy_commerce_m7_save_draft','andy_commerce_m7_archive_draft',
    'draft_revision','marketing-v2-rule-editor.php','Dev-only'] as $label) {
    $ok(str_contains($admin,$label),'M10 real view contract: '.$label);
}
$ok(!str_contains($admin,'add_menu_page(')&&!str_contains($admin,'woocommerce_cart_calculate_fees')&&
    !str_contains($admin,'woocommerce_package_rates'),'no alternate menu or live Woo hooks');
echo "PASS CORE_COMMERCE_MARKETING_M10_L3={$n}; DESKTOP=ONLY; REAL_AWARDS=OFF\n";
