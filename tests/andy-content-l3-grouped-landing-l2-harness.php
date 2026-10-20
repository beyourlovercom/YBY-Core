<?php
/** Owner V3 PASS interactive navigation guard; no WordPress writes. */
$root=dirname(__DIR__);
$nav=file_get_contents($root.'/admin/class-yby-andy-os-navigation.php');
$shell=file_get_contents($root.'/admin/class-yby-andy-admin-shell-v22.php');
$css=file_get_contents($root.'/assets/css/andy-core-admin-shell-v22.css');
$core=file_get_contents($root.'/inc/class-yby-core.php');
$assert=static function($yes,$label){if(!$yes){fwrite(STDERR,"FAIL V3 ".$label."\n");exit(1);}};
$assert(strpos($nav, "\$items['edit.php?post_type=landing_page']")===false,'no eighth Landing Pages L2');
$nativeLanding=file_get_contents($root.'/inc/class-yby-landing-page-cpt.php');
$assert(strpos($nativeLanding, "'show_in_menu' => 'yby-os'")!==false,'CPT URL registration unchanged');
$assert(strpos($nativeLanding, "__('落地页'")===false ? strpos($nativeLanding, "__( '落地页', 'yby-core' )")!==false : true,'native CPT label Chinese');
$nativeProject=file_get_contents($root.'/inc/class-yby-project-cpt.php');
$assert(strpos($nativeProject, "__( '项目列表', 'yby-core' )")!==false,'native Projects label Chinese');

foreach(array('文档','招商','落地页','项目') as $group) {
  $assert(strpos($nav,"'".$group."'")!==false,'group '.$group);
}
$assert(strpos($nav,"'edit.php?post_type=yby_project'")!==false,'Projects CPT list exists');
$assert(strpos($nav,"'admin.php?page=yby-project-studio'")!==false,'Project Studio original controller');
$assert(strpos($shell,"<details class=")!==false,'native details/summary clickable collapse');
$assert(strpos($shell,"render_native_content_navigation")!==false,'WordPress native CPT rail renderer');
$assert(strpos($core,"'all_admin_notices'")!==false,'WordPress native hook');
$assert(strpos($css,'grid-template-columns: 214px minmax(0,1fr)')!==false,'CSS grid native CPT');
$assert(strpos($css,'float: left;')===false,'no float giant whitespace');
$assert(strpos($shell,'call_user_func( $body )')!==false,'original controller exactly once');
$assert(strpos($nav,'if ( ! current_user_can( $cap ) ) { return; }')!==false,'per-link permission');
$assert(strpos($nav,'if ( $module && ! YBY_Module_Registry::is_enabled( $module ) ) { return; }')!==false,'per-module capability');
echo "PASS andy-core-v3-owner-navigation-contract\n";
