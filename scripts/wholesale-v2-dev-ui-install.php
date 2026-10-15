<?php
/** Fail-closed Dev-only Installer: patch only two Andy Core UI files, add service and view. */
if (!defined('ABSPATH') || !defined('WP_CLI')) { exit(1); }
if (wp_get_environment_type()!=='development' ||
    strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST))!=='dev.ybyirrigation.com') {
    WP_CLI::error('Wholesale V2 installer refused outside isolated Dev');
}
$plugin=WP_PLUGIN_DIR.'/yby-core';
$private=__DIR__;
$inputs=[
 'admin/class-yby-project-studio.php'=>$private.'/v2-controller-candidate.php',
 'admin/views/project-list.php'=>$private.'/v2-list-candidate.php',
 'admin/views/wholesale-country-clone-v2.php'=>$private.'/v2-view.php',
 'inc/class-yby-wholesale-country-clone-v2.php'=>$private.'/v2-service.php',
];
foreach($inputs as $rel=>$src){ if (!is_file($src)) { WP_CLI::error('Missing staged candidate: '.$rel); } }
$existing=file_get_contents($plugin.'/admin/class-yby-project-studio.php');
$list=file_get_contents($plugin.'/admin/views/project-list.php');
$candidate=file_get_contents($inputs['admin/class-yby-project-studio.php']);
$list_candidate=file_get_contents($inputs['admin/views/project-list.php']);
if (strpos($existing,'render_wholesale_country_v2')!==false ||
    strpos($list,'Wholesale Country Clone V2')!==false ||
    is_file($plugin.'/inc/class-yby-wholesale-country-clone-v2.php')) {
    WP_CLI::error('V2 already partially installed: refusing to overwrite');
}
$route_needle="\t\tif ( 'runtime' === \$view && \$post_id ) {";
$method_needle="\t/**\n\t * Render Project List.";
$route_start="\t\tif ( 'wholesale' === \$view ) {";
$method_start="\t/**\n\t * Dev-only B2B Wholesale country-clone UI.";
if(substr_count($candidate,$route_start)!==1 || substr_count($candidate,$method_start)!==1 ||
   substr_count($existing,$route_needle)!==1 || substr_count($existing,$method_needle)!==1) {
    WP_CLI::error('Controller markers changed. Refuse unsafe patch.');
}
$route=substr($candidate,strpos($candidate,$route_start),strpos($candidate,$route_needle)-strpos($candidate,$route_start));
$method=substr($candidate,strpos($candidate,$method_start),strpos($candidate,$method_needle)-strpos($candidate,$method_start));
$patched=str_replace($route_needle,$route.$route_needle,$existing);
$patched=str_replace($method_needle,$method.$method_needle,$patched);
$original_line='\t<a class="page-title-action"';
preg_match('/^\s*<a class="page-title-action"[^\n]+Wholesale Country Clone V2[^\n]*$/m',$list_candidate,$extra);
preg_match('/^\s*<a class="page-title-action"[^\n]+Add Project[^\n]*$/m',$list,$anchor);
if(count($extra)!==1 || count($anchor)!==1) { WP_CLI::error('Project-list injection markers missing.'); }
$patched_list=str_replace($anchor[0],$anchor[0]."\n".$extra[0],$list);
$timestamp='v2-backup-'.gmdate('YmdHis');
$backup=$private.'/'.$timestamp;
if(!mkdir($backup,0700) && !is_dir($backup)) { WP_CLI::error('Cannot create private backup'); }
$files=[
 'admin/class-yby-project-studio.php'=>$patched,
 'admin/views/project-list.php'=>$patched_list,
 'admin/views/wholesale-country-clone-v2.php'=>file_get_contents($inputs['admin/views/wholesale-country-clone-v2.php']),
 'inc/class-yby-wholesale-country-clone-v2.php'=>file_get_contents($inputs['inc/class-yby-wholesale-country-clone-v2.php']),
];
$changed=[];
try {
  foreach($files as $rel=>$bytes){
    $dest=$plugin.'/'.$rel;
    if (is_file($dest)) {
       $save=$backup.'/'.str_replace('/','__',$rel);
       if (!copy($dest,$save)) { throw new Exception('Backup failed: '.$rel); }
    }
    if(file_put_contents($dest,$bytes,LOCK_EX)===false) { throw new Exception('Write failed: '.$rel); }
    $changed[]=$rel;
  }
} catch(Throwable $e) {
  foreach(array_reverse($changed) as $rel) {
    $old=$backup.'/'.str_replace('/','__',$rel);
    if(is_file($old)) { copy($old,$plugin.'/'.$rel); }
    else { unlink($plugin.'/'.$rel); }
  }
  WP_CLI::error('ROLLED_BACK '.$e->getMessage());
}
WP_CLI::line(wp_json_encode([
 'status'=>'DEV_ONLY_INSTALLED','backup'=>$backup,
 'files'=>$changed,'sha256'=>array_map('hash_file',[],[])
]));
