<?php
/**
 * Isolated WordPress Core-only Creator route regression.
 * Uses no users, credentials, real provider, or WWW request.
 */
if (!defined('ABSPATH') || get_option('siteurl')!=='http://creator-ci.invalid') {
    throw new RuntimeException('Core-only integration must use disposable WordPress');
}
define('BYL_CREATOR_REMOTE_API_ENABLED',true);
require getenv('GITHUB_WORKSPACE').'/inc/class-yby-connector.php';
if(class_exists('YBY_Creator_Readonly_History',false)){
    throw new RuntimeException('Core eagerly loaded disabled-by-default Creator class');
}
if(has_action('rest_api_init',['YBY_Creator_Readonly_History','register_routes'])!==false){
    throw new RuntimeException('Unexpected standalone Canary callback in Core-only fixture');
}
add_action('rest_api_init',['YBY_Connector','register_routes'],10);
$routes=rest_get_server()->get_routes();
if(!class_exists('YBY_Creator_Readonly_History',false) ||
    !isset($routes['/andy-core/v1/creator/history']) ||
    count($routes['/andy-core/v1/creator/history'])!==1){
    throw new RuntimeException('Core-only Creator REST route missing/duplicated');
}
echo 'CREATOR_CORE_ONLY_LAZY_ROUTE_WP_REST_PASS'.PHP_EOL;
