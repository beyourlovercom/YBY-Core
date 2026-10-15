<?php
/** Dev read-only Project Studio UI smoke, no HTTP or forms submitted. */
if(!defined('ABSPATH') || !defined('WP_CLI'))exit(1);
if(wp_get_environment_type()!=='development' ||
   strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST))!=='dev.ybyirrigation.com') {
   WP_CLI::error('Only isolated Dev');
}
if(!defined('YBY_WHOLESALE_CLONE_V2_DEV_ENABLED') ||
   YBY_WHOLESALE_CLONE_V2_DEV_ENABLED!==true)WP_CLI::error('Dev flag missing');
if(!class_exists('YBY_Project_Studio') ||
   !method_exists('YBY_Project_Studio','render_wholesale_country_v2'))WP_CLI::error('UI not registered');
if(!function_exists('submit_button'))require_once ABSPATH.'wp-admin/includes/template.php';
$_SERVER['REQUEST_METHOD']='GET';
$_REQUEST['country']='DE';
$viewer=new YBY_Project_Studio('yby-core','1.8.1');
$method=new ReflectionMethod($viewer,'render_wholesale_country_v2');
$method->setAccessible(true);
ob_start();
$method->invoke($viewer);
$html=ob_get_clean();
$items=[
 'render_bytes'=>strlen($html),
 'has_wholesale_heading'=>strpos($html,'Wholesale Country Clone V2')!==false,
 'has_germany_country'=>strpos($html,'Germany')!==false,
 'has_draft_action'=>strpos($html,'yby_wholesale_create')!==false,
 'has_preview_links'=>strpos($html,'Preview')!==false,
 'source_215_unchanged'=>get_post_status(215)==='publish',
 'germany_819_still_draft'=>get_post_status(819)==='draft',
];
if(in_array(false,$items,true))WP_CLI::error(wp_json_encode($items));
WP_CLI::line(wp_json_encode(['status'=>'DEV_STUDIO_GET_PASS','checks'=>$items]));
