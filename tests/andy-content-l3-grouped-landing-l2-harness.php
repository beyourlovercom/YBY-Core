<?php
/** Nested PR: grouped navigation labels and native Landing Pages L2; no WP I/O. */
$root = dirname(__DIR__);
$nav = file_get_contents($root.'/admin/class-yby-andy-os-navigation.php');
$shell = file_get_contents($root.'/admin/class-yby-andy-admin-shell-v22.php');
$css = file_get_contents($root.'/assets/css/andy-core-admin-shell-v22.css');
$landing = file_get_contents($root.'/inc/class-yby-landing-page-cpt.php');
$assert = static function($v,$label) { if (!$v) {fwrite(STDERR,"FAIL ".$label."\n");exit(1);} };
$assert(strpos($landing,"'show_in_menu' => 'yby-os'") !== false,'native registered CPT under Andy Core');
$assert(strpos($nav, '$items[\'edit.php?post_type=landing_page\'] = array( \'Landing Pages\', \'edit_pages\', \'native\' )') !== false,'native L2 preserved');
$assert(strpos($nav,"return 'edit.php?post_type=landing_page';") !== false,'native L2 highlight');
foreach(array('Andy Content','Andy Business','Landing Pages') as $category) {
  $assert(strpos($nav,"'".$category."'") !== false,'group '.$category);
}
foreach(array('edit.php?post_type=landing_page','post-new.php?post_type=landing_page') as $path) {
  $assert(strpos($nav,$path)!==false,'original CPT route '.$path);
}
$assert(strpos($shell,'andy-core-shell-v22__group')!==false,'server-side heading renderer');
$assert(strpos($css,'.andy-core-shell-v22__group')!==false,'scoped responsive group CSS');
$assert(strpos($nav,'if ( ! current_user_can( $cap ) ) { return; }')!==false,'capability filter');
$assert(strpos($nav,'if ( $module && ! YBY_Module_Registry::is_enabled( $module ) ) { return; }')!==false,'module filter');
echo "PASS andy-content-l3-grouped-landing-native-l2-harness\n";
