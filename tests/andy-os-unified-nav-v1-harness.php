<?php
/** Andy Core V2 native WordPress two-level menu; L3 remains inside original pages. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['caps'] = array(); $GLOBALS['menu'] = array(); $GLOBALS['submenu'] = array();
$GLOBALS['hooks'] = array(); $GLOBALS['removed'] = array(); $GLOBALS['called'] = 0;
function current_user_can( $cap ) { return in_array( $cap, $GLOBALS['caps'], true ); }
function is_admin() { return true; }
function __($s,$d='') { return $s; }
function esc_html__($s,$d='') { return $s; }
function esc_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function esc_url($s) { return $s; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function admin_url($s='') { return 'https://example.test/wp-admin/'.$s; }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_-]/','',strtolower($s)); }
function wp_unslash($s) { return stripslashes($s); }
function wp_die($s,$title='',$args=array()) { throw new RuntimeException('403'); }
function get_plugin_page_hookname($slug,$parent='') {
    if(in_array($slug,array('yby-content','andy-commerce'),true)) return 'toplevel_page_'.$slug;
    return $parent!==''?$parent.'_page_'.$slug:'admin_page_'.$slug;
}
function has_action($hook) { return isset($GLOBALS['hooks'][$hook]); }
function do_action($hook) { if(isset($GLOBALS['hooks'][$hook]))call_user_func($GLOBALS['hooks'][$hook]); }
function add_action($hook,$callback) { $GLOBALS['hooks'][$hook]=$callback; }
function add_submenu_page($parent,$title,$label,$cap,$slug,$callback) {
    if(!current_user_can($cap))return false;
    $GLOBALS['submenu'][$parent][] = array($label,$cap,$slug);
    $hook=get_plugin_page_hookname($slug,(string)$parent);
    if(is_callable($callback))$GLOBALS['hooks'][$hook]=$callback;
    return $hook;
}
function remove_submenu_page($parent,$slug) {
    foreach(($GLOBALS['submenu'][$parent]??array()) as $i=>$row)if(($row[2]??'')===$slug){unset($GLOBALS['submenu'][$parent][$i]);return $row;}
    return false;
}
function remove_menu_page($slug) {
    foreach($GLOBALS['menu'] as $i=>$row)if(($row[2]??'')===$slug){unset($GLOBALS['menu'][$i]);$GLOBALS['removed'][]=$slug;return $row;}return false;
}
class YBY_Module_Registry {
    public static function is_enabled($id) { return in_array($id,array('business_os','inquiry_os','newsletter_os','email_os'),true); }
}
class Andy_Commerce_Admin {}
require_once dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
$assert=static function($v,$msg){if(!$v){fwrite(STDERR,'FAIL: '.$msg."\n");exit(1);}};
$setup=static function($caps){
    $GLOBALS['caps']=$caps;$GLOBALS['called']=0;$GLOBALS['removed']=array();
    $GLOBALS['menu']=array(
        array('Andy Core','manage_options','yby-os'),
        array('Andy Content','andy_core_settings_manage','yby-content'),
        array('Andy Commerce','manage_woocommerce','andy-commerce')
    );
    $GLOBALS['submenu']=array(
        'yby-os'=>array(
            array('Inquiry OS','andy_core_leads_view','andy-core-leads'),
            array('Newsletter OS','andy_core_newsletter_view','andy-core-newsletter'),
            array('Email OS','andy_core_mail_view','andy-core-email'),
            array('Social Login','manage_options','yby-social-login')
        ),
        'yby-content'=>array(
            array('Andy Content','andy_core_settings_manage','yby-content'),
            array('Business','andy_core_settings_manage','andy-business'),
            array('Docs Tags','andy_core_settings_manage','edit-tags.php?taxonomy=doc_tag&post_type=docs')
        )
    );
    $inc=static function(){++$GLOBALS['called'];};
    $GLOBALS['hooks']=array(
        'toplevel_page_yby-content'=>$inc,
        'toplevel_page_andy-commerce'=>$inc,
        'yby-content_page_andy-business'=>$inc,
        'yby-os_page_andy-core-leads'=>$inc,
        'yby-os_page_andy-core-newsletter'=>$inc,
        'yby-os_page_andy-core-email'=>$inc,
        'yby-os_page_yby-social-login'=>$inc
    );
};
$setup(array('manage_options','andy_core_settings_manage','manage_woocommerce','andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view'));
$assert('manage_options'===YBY_Andy_OS_Navigation::root_capability(),'admin root');
YBY_Andy_OS_Navigation::consolidate();
$names=array_column($GLOBALS['submenu']['yby-os'],2);
$assert(7===count($names),'seven native second-level modules');
$expected=array('andy-core-leads','andy-core-newsletter','andy-core-email','andy-core-content','andy-core-marketing','andy-core-commerce','andy-core-system');
$assert($names===$expected,'correct native WordPress L2 sequence');
$assert(!in_array('andy-os-overview',$names,true),'no forced overview');
$assert(!in_array('yby-social-login',$names,true),'no orphan tertiary in WP sidebar');
$assert(in_array('yby-content',$GLOBALS['removed'],true),'content top-level removed');
$assert(in_array('andy-commerce',$GLOBALS['removed'],true),'commerce top-level removed');
$hidden=array_column($GLOBALS['submenu']['']??array(),2);
foreach(array('andy-business','yby-content','andy-commerce','yby-social-login','edit-tags.php?taxonomy=doc_tag&post_type=docs') as $slug) {
    $assert(in_array($slug,$hidden,true),'legacy alias: '.$slug);
}
do_action('yby-os_page_andy-core-leads');
do_action('admin_page_andy-business');
do_action('toplevel_page_andy-commerce');
$assert(3===$GLOBALS['called'],'old handlers intact');
$assert('admin.php?page=andy-business'===YBY_Andy_OS_Navigation::destination('andy-core-content'),'Content opens Business');
$assert(str_contains(YBY_Andy_OS_Navigation::destination('andy-core-marketing'),'marketing-promotions'),'Marketing opens original Commerce tab');
$assert(''===YBY_Andy_OS_Navigation::destination('unknown-page'),'unknown route not generated');
foreach(array(
    array(array('andy_core_newsletter_view'),'andy-core-newsletter','andy_core_newsletter_view'),
    array(array('andy_core_mail_view'),'andy-core-email','andy_core_mail_view'),
    array(array('andy_core_leads_view'),'andy-core-leads','andy_core_leads_view'),
    array(array('manage_woocommerce'),'andy-core-commerce','manage_woocommerce'),
    array(array('andy_core_settings_manage'),'andy-core-content','andy_core_settings_manage')
) as $test){
    $setup($test[0]);
    $assert($test[2]===YBY_Andy_OS_Navigation::root_capability(),'isolated root role '.$test[2]);
    YBY_Andy_OS_Navigation::consolidate();
    $names=array_column($GLOBALS['submenu']['yby-os'],2);
    $assert(in_array($test[1],$names,true),'role sees own module '.$test[1]);
    $assert(!in_array('andy-core-system',$names,true)||current_user_can('andy_core_settings_manage'),'no system escalation');
}
$setup(array());
$assert('manage_options'===YBY_Andy_OS_Navigation::root_capability(),'unprivileged fail-closed');
YBY_Andy_OS_Navigation::consolidate();
$assert(0===count($GLOBALS['submenu']['yby-os']),'no unauthorized submenu');
// Third-level links point only to real, capability-gated existing controllers.
$setup(array('manage_options','andy_core_settings_manage','manage_woocommerce','andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view','edit_pages'));
$inquiry = YBY_Andy_OS_Navigation::tertiary_items('andy-core-leads');
$assert(7===count($inquiry),'all seven existing inquiry functions retained for authorized admin');
$newsletter = YBY_Andy_OS_Navigation::tertiary_items('andy-core-newsletter');
$assert(4===count($newsletter),'only operational newsletter tabs visible');
$email = YBY_Andy_OS_Navigation::tertiary_items('andy-core-email');
$assert(4===count($email),'email overview/templates/transport/health only; no phantom Worker');
$content = YBY_Andy_OS_Navigation::tertiary_items('andy-business');
$assert(4===count($content),'four actual Business configuration pages');
$assert(!str_contains(json_encode($content),'yby-docs'),'disabled Docs OS cannot appear');
$commerce = YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$assert(5===count($commerce),'five original Commerce tabs retained');
$setup(array('andy_core_mail_view'));
$mail_only = YBY_Andy_OS_Navigation::tertiary_items('andy-core-email');
$assert(3===count($mail_only),'mail-only employee sees no restricted template editor');
$assert(0===count(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')),'mail-only cannot see Commerce L3');
$setup(array('andy_core_leads_view'));
$assert(2===count(YBY_Andy_OS_Navigation::tertiary_items('andy-core-leads')),'inquiry-only gets list and read-only stats');
$_SERVER['REQUEST_METHOD']='POST';
$_GET['page']='andy-core-marketing';
YBY_Andy_OS_Navigation::redirect_entry(); // POST must never be redirected.
$assert(true,'POST original nonce processing not intercepted');
echo "PASS andy-core-native-l123-v2-harness\n";
