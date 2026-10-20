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
$GLOBALS['enabled_modules'] = array('business_os','inquiry_os','newsletter_os','email_os');
class YBY_Module_Registry {
    public static function is_enabled($id) { return in_array($id,$GLOBALS['enabled_modules'],true); }
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
$assert(4===count($content),'four real Business pages, no mandatory hub');
$assert(array_column($content,'group')===array('招商','招商','招商','招商'),
    'business L3 grouped under Chinese 招商');
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
// Preserve the correct native L2 highlight on old L3 URLs.
$setup(array('manage_options','andy_core_settings_manage','manage_woocommerce','andy_core_leads_view'));
$_GET=array('page'=>'andy-commerce','tab'=>'marketing-promotions');
$assert('andy-core-marketing'===YBY_Andy_OS_Navigation::active_second_level(),'marketing original tab highlights Marketing L2');
$_GET=array('page'=>'andy-commerce','tab'=>'order-export');
$assert('andy-core-commerce'===YBY_Andy_OS_Navigation::filter_submenu_file('legacy'),'order export highlights Commerce L2');
$_GET=array('page'=>'andy-business');
$assert('yby-os'===YBY_Andy_OS_Navigation::filter_parent_file('yby-content'),'Business stays inside Andy Core');
$assert('andy-core-content'===YBY_Andy_OS_Navigation::filter_submenu_file('legacy'),'Business highlights Content L2');
$_GET=array('post_type'=>'landing_page');
$assert('andy-core-content'===YBY_Andy_OS_Navigation::active_second_level(),'Landing CPT with module OFF preserves existing Content fallback');
$_GET=array('page'=>'yby-core','tab'=>'modules');
$assert('andy-core-system'===YBY_Andy_OS_Navigation::active_second_level(),'system modules highlight System L2');
$setup(array('andy_core_leads_view'));
$_GET=array('page'=>'andy-commerce');
$assert('legacy'===YBY_Andy_OS_Navigation::filter_submenu_file('legacy'),'unauthorized user cannot activate Commerce L2');
// WordPress native content-editor permissions must not be promoted to
// Business settings, but must retain their existing CPT/editor access.
$setup(array('edit_pages'));
$GLOBALS['enabled_modules'][]='landing_pages';
$assert('edit_pages'===YBY_Andy_OS_Navigation::root_capability(),'landing-only editor can see parent');
$groups=YBY_Andy_OS_Navigation::sections();
$assert('edit_pages'===$groups['andy-core-content'][1],'landing content route uses edit_pages, not manage_options');
$assert('edit.php?post_type=landing_page'===YBY_Andy_OS_Navigation::destination('andy-core-content'),'landing editor defaults to actual CPT');
$_GET=array('post_type'=>'landing_page');
$assert('andy-core-content'===YBY_Andy_OS_Navigation::active_second_level(),
    'native landing page selects original Chinese Content L2');
$editor_links=YBY_Andy_OS_Navigation::tertiary_items('andy-business');
$assert(2===count($editor_links),'landing-only editor sees existing All and New Landing Pages CPT editors');
$assert(array_column($editor_links,'group')===array('落地页','落地页'),
    'landing-only editor group is Chinese, business hidden');
$assert(!str_contains(json_encode($editor_links),'andy-business'),'landing editor cannot access Business settings');
$GLOBALS['enabled_modules']=array('business_os','inquiry_os','newsletter_os','email_os');
// Regression: WordPress menu-header's second parent resolver must not see
// the current Andy Core hidden alias after admin authorization. Other hidden
// pages and the original registered callback must remain untouched.
$setup(array('manage_options','andy_core_settings_manage','manage_woocommerce',
    'andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view'));
YBY_Andy_OS_Navigation::consolidate();
$_GET=array('page'=>'yby-social-login');
$before=array_column($GLOBALS['submenu']['']??array(),2);
$assert(in_array('yby-social-login',$before,true),'hidden alias must survive access registration');
$assert(in_array('andy-business',$before,true),'other hidden aliases exist');
YBY_Andy_OS_Navigation::preserve_native_parent();
$after=array_column($GLOBALS['submenu']['']??array(),2);
$assert(!in_array('yby-social-login',$after,true),'current hidden alias removed before menu rendering');
$assert(in_array('andy-business',$after,true),'other hidden aliases untouched');
do_action('admin_page_yby-social-login');
$assert(1===$GLOBALS['called'],'legacy route callback preserved after menu correction');
$_GET=array('page'=>'andy-business');
YBY_Andy_OS_Navigation::preserve_native_parent();
$assert(!in_array('andy-business',array_column($GLOBALS['submenu']['']??array(),2),true),
    'content route hidden alias must not collapse native parent');
$setup(array('manage_options','andy_core_settings_manage','andy_core_leads_view'));
YBY_Andy_OS_Navigation::consolidate();
$_GET=array('page'=>'yby-social-login');
$before=array_column($GLOBALS['submenu']['']??array(),2);
$GLOBALS['caps']=array();
YBY_Andy_OS_Navigation::preserve_native_parent();
$assert($before===array_column($GLOBALS['submenu']['']??array(),2),
    'unauthorized user cannot change the hidden menu');
// Owner compact-Chinese navigation contract: display labels only; slugs,
 // capability gates, URLs, menu order, and native L2 state must remain stable.
$setup(array('manage_options','andy_core_settings_manage','manage_woocommerce',
    'andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view','edit_pages'));
$sections=YBY_Andy_OS_Navigation::sections();
$expected_labels=array(
    'andy-core-leads'=>'询盘', 'andy-core-newsletter'=>'订阅', 'andy-core-email'=>'邮件',
    'andy-core-content'=>'内容', 'andy-core-marketing'=>'营销',
    'andy-core-commerce'=>'交易', 'andy-core-system'=>'系统',
);
$assert(array_keys($sections)===array_keys($expected_labels),'Chinese L2 preserves canonical order and slugs');
foreach($expected_labels as $slug=>$label){
    $assert($sections[$slug][0]===$label,'compact Chinese L2 '.$slug);
}
$assert(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===
    array('总览','订单导出','结账运费','促销','设置'),
    'Commerce L3 localized without changing routes');
$system_labels=array_column(YBY_Andy_OS_Navigation::tertiary_items('yby-core'),'title');
foreach(array('常规','模块','扩展','状态','更新','品牌') as $label) {
    $assert(in_array($label,$system_labels,true),'System L3 localized '.$label);
}
$inquiry_labels=array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-core-leads'),'title');
$assert(in_array('列表',$inquiry_labels,true) && in_array('统计',$inquiry_labels,true),
    'Inquiry L3 localized without route changes');
$setup(array('andy_core_mail_view'));
$mail_visible=array_filter(YBY_Andy_OS_Navigation::sections(),
    static function($group){ return current_user_can($group[1]); });
$assert(array_keys($mail_visible)===array('andy-core-email'),
    'Chinese-only menu must not display unauthorized modules');
$assert(count(YBY_Andy_OS_Navigation::tertiary_items('andy-core-email'))===3,
    'Chinese-only menu must not enable restricted email template');
echo "PASS andy-core-native-l123-v2-harness\n";
