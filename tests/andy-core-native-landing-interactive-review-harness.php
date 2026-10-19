<?php
/** No WordPress I/O: ensure native Landing Pages actual screen is not replaced. */
$root=dirname(__DIR__);
$shell=file_get_contents($root.'/admin/class-yby-andy-admin-shell-v22.php');
$core=file_get_contents($root.'/inc/class-yby-core.php');
$css=file_get_contents($root.'/assets/css/andy-core-admin-shell-v22.css');
$nav=file_get_contents($root.'/admin/class-yby-andy-os-navigation.php');
$must=static function($yes,$why){if(!$yes){fwrite(STDERR,"FAIL ".$why."\n");exit(1);}};
$must(strpos($shell,"'edit-landing_page' !== \$screen->id")!==false,'strict native CPT screen');
$must(strpos($shell,"current_user_can( 'edit_pages' )")!==false,'role guard');
$must(strpos($shell,"YBY_Module_Registry::is_enabled( 'landing_pages' )")!==false,'module guard');
$must(strpos($core,"'all_admin_notices', 'YBY_Andy_Admin_Shell_V22'")!==false,'native sibling rail hook');
$must(strpos($css,'body.edit-php.post-type-landing_page')!==false,'CSS scoped native screen');
$must(strpos($nav,"'admin.php?page=yby-docs-os'")!==false,'original Docs route');
$must(strpos($shell,"edit.php?post_type=landing_page")!==false,'original landing native link and active');
$must(strpos($shell,'window.location')===false && strpos($shell,'iframe')===false,'no iframe or fake navigation');
echo "PASS andy-core-native-landing-interactive-review-harness\n";
