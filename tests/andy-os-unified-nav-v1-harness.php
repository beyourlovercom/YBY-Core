<?php
/** Isolated V1 navigation contract. No WordPress bootstrap, network or writes. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['aus_caps'] = array();
$GLOBALS['menu'] = array();
$GLOBALS['submenu'] = array();
$GLOBALS['aus_hooks'] = array();
$GLOBALS['aus_removed'] = array();
$GLOBALS['aus_legacy_count'] = 0;
function current_user_can( $cap ) { return in_array( $cap, $GLOBALS['aus_caps'], true ); }
function is_admin() { return true; }
function __($v,$d='') { return $v; }
function esc_html__($v,$d='') { return $v; }
function esc_html($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function esc_url($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function wp_strip_all_tags($v) { return strip_tags($v); }
function admin_url($v='') { return 'https://dev.example.invalid/wp-admin/'.$v; }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v)); }
function wp_unslash($v) { return stripslashes($v); }
function wp_die($v,$title='',$args=array()) { throw new RuntimeException('DENIED_'.($args['response']??403)); }
function get_plugin_page_hookname($slug,$parent='') {
    // WP keeps previously registered top-level slugs on their original hooks.
    if (in_array($slug,array('yby-content','andy-commerce'),true)) return 'toplevel_page_'.$slug;
    return $parent !== '' ? $parent.'_page_'.$slug : 'toplevel_page_'.$slug;
}
function has_action($hook) { return isset($GLOBALS['aus_hooks'][$hook]); }
function do_action($hook) { if(isset($GLOBALS['aus_hooks'][$hook]))call_user_func($GLOBALS['aus_hooks'][$hook]); }
function add_action($hook,$callback) { $GLOBALS['aus_hooks'][$hook]=$callback; }
function add_submenu_page($parent,$title,$menu,$cap,$slug,$callback) {
    if (!current_user_can($cap)) return false;
    $GLOBALS['submenu'][$parent][] = array($menu,$cap,$slug);
    $hook=get_plugin_page_hookname($slug,(string)$parent);
    if (is_callable($callback)) $GLOBALS['aus_hooks'][$hook]=$callback;
    return $hook;
}
function remove_menu_page($slug) {
    foreach($GLOBALS['menu'] as $i=>$item)if(($item[2]??'')===$slug) {
        $GLOBALS['aus_removed'][]=$slug;
        unset($GLOBALS['menu'][$i]);
        return $item;
    }
    return false;
}
class YBY_Module_Registry {
    public static function is_enabled($id){return in_array($id,array('business_os','inquiry_os'),true);}
}
class Andy_Commerce_Admin {}
require_once dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
$assert=static function($cond,$name){if(!$cond){fwrite(STDERR,"FAIL ".$name."\n");exit(1);}};
$install=static function($role)use($assert){
    $GLOBALS['aus_caps']=$role;
    $GLOBALS['menu']=array(
        array('Andy Core','manage_options','yby-os'),
        array('Andy Content','andy_core_settings_manage','yby-content'),
        array('Andy Commerce','manage_woocommerce','andy-commerce')
    );
    $GLOBALS['submenu']=array(
        'yby-content'=>array(
            array('Overview','andy_core_settings_manage','yby-content'),
            array('Business 招商页面','andy_core_settings_manage','andy-business'),
            array('合作项目','andy_core_settings_manage','andy-business-programs'),
        )
    );
    $GLOBALS['aus_hooks']=array(
        'toplevel_page_yby-content'=>function(){++$GLOBALS['aus_legacy_count'];},
        'toplevel_page_andy-commerce'=>function(){++$GLOBALS['aus_legacy_count'];},
        'yby-content_page_andy-business'=>function(){++$GLOBALS['aus_legacy_count'];},
        'yby-content_page_andy-business-programs'=>function(){++$GLOBALS['aus_legacy_count'];},
    );
    $GLOBALS['aus_removed']=array();
    $GLOBALS['aus_legacy_count']=0;
};
$install(array('manage_options','andy_core_settings_manage','manage_woocommerce','andy_core_leads_view'));
$assert('manage_options'===YBY_Andy_OS_Navigation::root_capability(),'admin root authority');
YBY_Andy_OS_Navigation::consolidate();
$assert(in_array('yby-content',$GLOBALS['aus_removed'],true),'content top-level removed');
$assert(in_array('andy-commerce',$GLOBALS['aus_removed'],true),'commerce top-level removed');
$assert(count($GLOBALS['menu'])===1,'only one top-level');
$slugs=array_column($GLOBALS['submenu']['yby-os'],2);
foreach(array('andy-os-overview','andy-os-content','andy-os-inquiry','andy-os-marketing','andy-os-commerce','andy-os-settings','yby-content','andy-business','andy-business-programs','andy-commerce') as $slug) {
    $assert(in_array($slug,$slugs,true),'preserved or grouped: '.$slug);
}
do_action('toplevel_page_andy-commerce'); // WP keeps original top-level hook identity
do_action('yby-os_page_andy-business');
$assert(2===$GLOBALS['aus_legacy_count'],'old page callbacks are delegated');
$_GET['page']='andy-os-content';
ob_start();YBY_Andy_OS_Navigation::render_section();$html=ob_get_clean();
$assert(str_contains($html,'Business 招商页面'),'Business card renders');
$assert(str_contains($html,'andy-business'),'Business deep link stable');
$_GET['page']='andy-os-commerce';
ob_start();YBY_Andy_OS_Navigation::render_section();$html=ob_get_clean();
$assert(str_contains($html,'andy-commerce'),'Commerce deep link stable');
$install(array('manage_woocommerce'));
$assert('manage_woocommerce'===YBY_Andy_OS_Navigation::root_capability(),'commerce-only root capability');
YBY_Andy_OS_Navigation::consolidate();
$assert(!in_array('andy-business',array_column($GLOBALS['submenu']['yby-os'],2),true),'no content-only route exposed to commerce');
$assert(in_array('andy-commerce',$GLOBALS['aus_removed'],true),'commerce manager has unified Commerce');
$_GET['page']='andy-os-settings';
try{YBY_Andy_OS_Navigation::render_section();$assert(false,'commerce cannot access settings');}
catch(RuntimeException $e){$assert('DENIED_403'===$e->getMessage(),'system settings denied');}
$install(array('andy_core_settings_manage'));
$assert('andy_core_settings_manage'===YBY_Andy_OS_Navigation::root_capability(),'content root capability');
YBY_Andy_OS_Navigation::consolidate();
$assert(in_array('yby-content',$GLOBALS['aus_removed'],true),'content manager unified');
$assert(!in_array('andy-commerce',array_column($GLOBALS['submenu']['yby-os'],2),true),'commerce route not exposed to content-only role');
$install(array('andy_core_leads_view'));
$assert('andy_core_leads_view'===YBY_Andy_OS_Navigation::root_capability(),'inquiry-only root capability');
$_GET['page']='andy-os-inquiry';
ob_start();YBY_Andy_OS_Navigation::render_section();$html=ob_get_clean();
$assert(str_contains($html,'Inquiry OS'),'inquiry-only group works');
$install(array());
$assert('manage_options'===YBY_Andy_OS_Navigation::root_capability(),'fail closed for unrelated user');
$assert(count(YBY_Andy_OS_Navigation::sections())===6,'six canonical sections');
echo "PASS andy-os-unified-nav-v1-harness\n";
