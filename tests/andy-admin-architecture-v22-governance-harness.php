<?php
/** Canonical Andy-owned WordPress admin L1/L2/L3 architecture CI gate. */
$root=dirname(__DIR__);
$assert=static function($ok,$label){if(!$ok){fwrite(STDERR,"FAIL V22 GOVERNANCE: $label\n");exit(1);}};
$data=json_decode(file_get_contents($root.'/docs/andy-os/admin-navigation-v22-manifest.json'),true);
$assert(is_array($data) && ($data['schema_version']??null)===1,'manifest valid');
$assert(($data['root']['slug']??'')==='yby-os' && ($data['root']['label']??'')==='Andy Core','single original Andy Core WP root');
$assert(($data['l3']['rendering']??'')==='server-side-PHP-shell' && !empty($data['l3']['no_dom_mover']),'server-render and no DOM mover');
$categories=$data['categories']??array();
$slugs=array_column($categories,'slug');
$expected=array('andy-core-leads','andy-core-newsletter','andy-core-email','andy-core-content','andy-core-marketing','andy-core-commerce','andy-core-system');
$assert($slugs===$expected,'seven owner-approved native L2 modules and order');
$assert(count(array_unique($slugs))===count($slugs),'L2 unique');
$capabilities=array('andy_core_leads_view','andy_core_newsletter_view','andy_core_mail_view','andy_core_settings_manage','manage_woocommerce');
foreach($categories as $group) {
	$assert(in_array($group['capability']??'', $capabilities,true),'module capability allowlisted '.$group['slug']);
	$assert(in_array($group['owner']??'',array('andy-core','andy-commerce'),true),'known plugin owner');
	$assert(str_starts_with($group['landing']??'','admin.php?page='),'real WordPress admin default');
}
$shell=file_get_contents($root.'/admin/class-yby-andy-admin-shell-v22.php');
$core=file_get_contents($root.'/inc/class-yby-core.php');
$assert(strpos($shell, 'call_user_func( $body )')!==false,'original controller executed by PHP');
$assert(strpos($shell,'suppress_legacy_tabs')!==false,'old tab suppression is conditional on shell');
$assert(strpos($core,"'enqueue_tertiary'")===false,'old JS DOM moving cannot be enqueued');
$assert(strpos($core,"'enqueue_styles'")!==false,'new server shell CSS wired');
$assert(strpos($core,'class-yby-three-os-admin.php')!==false,'Dev Three OS bootstrap preserved');
$assert(strpos($core,'class-yby-mail-v2-p6-dev-preview.php')!==false,'Dev mail preview preserved');
$js=file_get_contents($root.'/assets/js/andy-core-native-l3-v2.js');
$assert(strpos($js,'view.appendChild(wrap)')!==false,'old DOM mover fixture remains intentionally unreferenced');
$source=file_get_contents($root.'/admin/class-yby-three-os-admin.php');
$assert(strpos($source,'suppress_legacy_tabs( $page )')!==false,'Three OS old nav suppressed only inside PHP shell');
foreach(array('render_newsletter','render_email') as $name){$assert(strpos($source,'function '.$name.'()')!==false && strpos($source,'function '.$name.'_original()')!==false,'original Three OS method preserved: '.$name);}
$inquiry=file_get_contents($root.'/admin/class-yby-inquiry-admin.php');
$assert(strpos($inquiry,'render_legacy_page()')!==false && strpos($inquiry,'function render_detail')!==false,'Inquiry lead/detail remain intact');
$commerce=file_get_contents($root.'/packages/andy-commerce/admin/class-andy-commerce-admin.php');
$assert(strpos($commerce,"class_exists( 'YBY_Andy_Admin_Shell_V22' )")!==false,'Commerce stays independent when Core is disabled');
$assert(strpos($commerce,'render_page_original()')!==false,'Commerce original handler preserved');
$business=file_get_contents($root.'/admin/class-yby-business-os-admin.php');
$assert(strpos($business,'render_page_original()')!==false,'Business original content preserved');
$system=file_get_contents($root.'/admin/class-yby-admin.php');
$assert(strpos($system,'render_settings_page_original()')!==false,'System original settings/POST handler preserved');
foreach(array('overview.php','order-export.php','checkout-shipping.php','marketing-promotions.php') as $name){
	$html=file_get_contents($root.'/packages/andy-commerce/admin/views/'.$name);
	$assert(strpos($html,"suppress_legacy_tabs( Andy_Commerce_Admin::PAGE_SLUG )")!==false,'Commerce view server-side single-nav '.$name);
}
foreach(array('settings-page.php','modules-page.php','addons-page.php','article-toc-settings.php','system-status.php','connector-page.php') as $name){
	$html=file_get_contents($root.'/admin/views/'.$name);
	$assert(strpos($html,"suppress_legacy_tabs( 'yby-core' )")!==false,'System view server-side single-nav '.$name);
}
$css=file_get_contents($root.'/assets/css/andy-core-admin-shell-v22.css');
$assert(strpos($css,'max-width: 782px')!==false,'M390 mobile CSS layout exists');
// M4: Brand and Social Login retain the original handlers and both POST/nonce forms.
$brand=file_get_contents($root.'/inc/class-yby-brand-os.php');
$social=file_get_contents($root.'/admin/class-yby-social-login-admin.php');
foreach(array('Brand'=>$brand,'Social Login'=>$social) as $label=>$code){
    $assert(strpos($code,"YBY_Andy_Admin_Shell_V22::render(")!==false,'M4 original controller opts into server shell '.$label);
    $assert(strpos($code,'_original()')!==false,'M4 original handler preserved '.$label);
    $assert(strpos($code,'check_admin_referer(')!==false,'M4 original nonce preserved '.$label);
}
foreach(array('brand-settings.php'=>'YBY_Brand_OS','social-login-page.php'=>'YBY_Social_Login_Admin') as $view=>$owner){
    $markup=file_get_contents($root.'/admin/views/'.$view);
    $assert(strpos($markup,"suppress_legacy_tabs( ".$owner."::page_slug() )")!==false,'M4 heading condition tied to original page '.$view);
    $assert(strpos($markup,'<form')!==false,'M4 original form preserved '.$view);
}
// M5: exercise actual navigation registry under mutually exclusive role capabilities.
// Simulate only access control and module switches, never WordPress or production data.
if(!defined('ABSPATH')) define('ABSPATH',__DIR__);
$GLOBALS['v22_role_caps']=array();
$GLOBALS['v22_modules']=array();
function current_user_can($cap){return in_array($cap,$GLOBALS['v22_role_caps'],true);}
function admin_url($path=''){return 'https://example.invalid/wp-admin/'.$path;}
function sanitize_key($v){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v));}
function wp_unslash($v){return stripslashes((string)$v);}
class YBY_Module_Registry {
    public static function is_enabled($module){return !empty($GLOBALS['v22_modules'][$module]);}
}
class Andy_Commerce_Admin {}
require $root.'/admin/class-yby-andy-os-navigation.php';
$role_check=static function($caps,$modules,$page,$expected_tabs) use($assert){
    $GLOBALS['v22_role_caps']=$caps;
    $GLOBALS['v22_modules']=array_fill_keys($modules,true);
    $items=YBY_Andy_OS_Navigation::tertiary_items($page);
    $titles=array_column($items,'title');
    $assert($titles===$expected_tabs,'M5 role/route L3 scope: '.$page.' ('.implode(',',$caps).')');
};
$role_check(array('andy_core_leads_view'),array('inquiry_os'),'andy-core-leads',array('询盘列表','询盘统计'));
$role_check(array('andy_core_newsletter_view'),array('newsletter_os'),'andy-core-newsletter',array('订阅者列表','双重确认与退订'));
$role_check(array('andy_core_mail_view'),array('email_os'),'andy-core-email',array('邮件总览','发送通道','系统健康'));
$role_check(array('manage_woocommerce'),array(),'andy-commerce',array('Commerce 总览','Order Export','Checkout / Shipping','Marketing / Promotions','Commerce 设置'));
$role_check(array(),array('inquiry_os','newsletter_os','email_os'),'andy-core-leads',array());
$role_check(array('andy_core_leads_view'),array(),'andy-core-leads',array());
$role_check(array('manage_options','andy_core_settings_manage'),array('social_login'),'yby-brand-os',array('常规设置','模块管理','Addons','系统状态','插件更新','Brand','Social Login'));
$GLOBALS['v22_role_caps']=array('edit_pages');
$GLOBALS['v22_modules']=array('landing_pages'=>true);
$groups=YBY_Andy_OS_Navigation::sections();
$visible=array_filter($groups,static function($group){return current_user_can($group[1]);});
$assert(array_keys($visible)===array('andy-core-content'),'M5 content-only visible L2 excludes Commerce, Inquiry and System');
$assert(YBY_Andy_OS_Navigation::destination('andy-core-content')==='edit.php?post_type=landing_page','M5 native editor not replaced');
$GLOBALS['v22_role_caps']=array('manage_woocommerce');
$GLOBALS['v22_modules']=array();
$assert(array_keys(YBY_Andy_OS_Navigation::sections())===array('andy-core-marketing','andy-core-commerce'),'M5 Woo manager only owns Commerce+Marketing L2');
$_GET=array('page'=>'andy-commerce','tab'=>'marketing-promotions');
$assert(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-marketing','M5 old Commerce deep link keeps Marketing L2 selected');
$_GET=array('page'=>'yby-brand-os');
$assert(YBY_Andy_OS_Navigation::active_second_level()==='andy-core-system','M5 Brand deep link keeps System L2 selected');
echo "PASS andy-admin-architecture-v22-governance-harness\n";
