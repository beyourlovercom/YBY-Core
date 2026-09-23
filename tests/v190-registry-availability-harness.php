<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options'] = array();
function sanitize_key( $v ) { return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v)); }
function get_option($k,$d=false){return array_key_exists($k,$GLOBALS['yby_test_options'])?$GLOBALS['yby_test_options'][$k]:$d;}
function update_option($k,$v,$a=null){$GLOBALS['yby_test_options'][$k]=$v;return true;}
function admin_url($p=''){return 'https://example.com/wp-admin/'.ltrim($p,'/');}
function add_query_arg($a,$u){return $u.'?'.http_build_query($a);}
require_once dirname(__DIR__).'/inc/class-yby-module-registry.php';
$assert=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$GLOBALS['available']=false;
$assert(YBY_Module_Registry::register('external_test',array('name'=>'External','default_enabled'=>false,'availability'=>function(){return $GLOBALS['available'];},'availability_message'=>'Need provider')),'register');
$assert(!YBY_Module_Registry::is_available('external_test'),'unavailable gate');
YBY_Module_Registry::save(array('external_test'));
$assert(in_array('external_test',YBY_Module_Registry::enabled_modules(),true),'selection preserved while provider absent');
$assert(!YBY_Module_Registry::is_enabled('external_test'),'runtime fail closed');
$assert(!YBY_Module_Registry::dependencies_met('external_test'),'dependency gate includes availability');
$GLOBALS['available']=true;
$assert(YBY_Module_Registry::is_available('external_test'),'provider becomes available');
$assert(YBY_Module_Registry::is_enabled('external_test'),'selection becomes effective without rewriting settings');
$view=file_get_contents(dirname(__DIR__).'/admin/views/modules-page.php');
$assert(false!==strpos($view,'availability_message'),'module UI availability message');
$assert(false!==strpos($view,'disabled( $is_planned || ! $is_available )'),'unavailable toggle disabled');
echo "PASS v190-registry-availability-harness\n";
