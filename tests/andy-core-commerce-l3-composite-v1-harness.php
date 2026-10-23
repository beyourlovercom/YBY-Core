<?php
/**
 * Composite source UAT: Core registry + Currency controller self-registration.
 * Simulates WP admin hooks, not a live Dev deployment.
 */
if (!defined('ABSPATH')) define('ABSPATH',__DIR__);
$GLOBALS['composite_caps']=array('manage_woocommerce');
$GLOBALS['composite_dev']=true;
$GLOBALS['composite_hook']=true;
$GLOBALS['composite_filters']=array();
$GLOBALS['composite_test_route']=false;
function is_admin(){return true;}
function current_user_can($cap){return in_array($cap,$GLOBALS['composite_caps'],true);}
function did_action($hook){return $hook==='admin_menu'?1:0;}
function get_plugin_page_hookname($page,$parent=''){
    return $parent?$parent.'_page_'.$page:'toplevel_page_'.$page;
}
function has_action($hook){return $GLOBALS['composite_hook']&&$hook==='toplevel_page_andy-commerce'?10:false;}
function admin_url($path=''){return 'https://dev.beyourlover.com/wp-admin/'.$path;}
function wp_strip_all_tags($s){return strip_tags($s);}
function __($s,$d=''){return $s;}
function add_menu_page(...$args){return 'toplevel_page_andy-commerce';}
function add_submenu_page(...$args){return 'andy-commerce_page_andy-commerce';}
function add_filter($name,$callback,$priority=10,$accepted=1){
    $GLOBALS['composite_filters'][$name][]=$callback;
}
function apply_filters($hook,$items,$page){
    foreach($GLOBALS['composite_filters'][$hook]??array() as $fn){
        $items=call_user_func($fn,$items,$page);
    }
    return $items;
}
final class YBY_Module_Registry { public static function is_enabled($m){return false;} }
final class Andy_Commerce_Currency_V2_Dev {
    public static function access(){
        return $GLOBALS['composite_dev'] && current_user_can('manage_woocommerce');
    }
}
require_once dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
require_once dirname(__DIR__).'/packages/andy-commerce/admin/class-andy-commerce-admin.php';
$check=static function($ok,$why){if(!$ok){fwrite(STDERR,"FAIL COMPOSITE L3: $why\n");exit(1);}};
(new Andy_Commerce_Admin())->add_admin_menu();
$items=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$check(array_column($items,'title')===array('总览','订单导出','结账运费','促销','设置','多币种'),
    'six items from two independently owned plugins, correct order');
$check(str_ends_with($items[5]['url'],'page=andy-commerce&tab=currency-os'),'legitimate route');
$check(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===$items,'idempotent');
$GLOBALS['composite_dev']=false;
$check(count(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'))===5,'inactive Currency hidden');
$GLOBALS['composite_dev']=true;
$GLOBALS['composite_caps']=array('andy_core_newsletter_view','read');
$check(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===array(),
    'newsletter-only role never sees Woo core or extension');
$GLOBALS['composite_caps']=array('manage_woocommerce');
$GLOBALS['composite_hook']=false;
$check(count(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'))===5,
    'missing original WP page callback cannot have extension');
$GLOBALS['composite_hook']=true;
// Commerce adds a second synthetic registered route; Core file is unchanged.
$GLOBALS['composite_test_route']=true;
add_filter('andy_core_l3_external_items',static function($items,$page){
    if ($page!=='andy-commerce'||!$GLOBALS['composite_test_route']) return $items;
    $items[]=array(
        'id'=>'andy-commerce.test-route','parent'=>'andy-commerce',
        'page'=>'andy-commerce','title'=>'新测试菜单','tab'=>'test-route',
        'capability'=>'manage_woocommerce','after'=>'settings',
        'route_check'=>static function($tab,$page){
            return $tab==='test-route'&&$page==='andy-commerce'&&$GLOBALS['composite_test_route'];
        }
    );
    return $items;
},20,2);
$more=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$check(array_column($more,'title')===array('总览','订单导出','结账运费','促销','设置','多币种','新测试菜单'),
    'Commerce-only new declaration, no Core source modification');
$GLOBALS['composite_test_route']=false;
$check(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===$items,
    'Commerce-only test route removal has immediate effect');
unset($GLOBALS['composite_filters']['andy_core_l3_external_items']);
$check(count(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'))===5,
    'deactivated consumer filter leaves original Core navigation');
echo "PASS Core + Commerce L3 composite synthetic integration\n";
