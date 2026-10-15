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
echo "PASS andy-admin-architecture-v22-governance-harness\n";
