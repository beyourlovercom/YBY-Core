<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_test_options']=array();
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function absint($v){return abs((int)$v);}
function get_option($k,$d=false){return array_key_exists($k,$GLOBALS['yby_test_options'])?$GLOBALS['yby_test_options'][$k]:$d;}
function add_option($k,$v,$d='',$a=null){if(array_key_exists($k,$GLOBALS['yby_test_options']))return false;$GLOBALS['yby_test_options'][$k]=$v;return true;}
function update_option($k,$v,$a=null){$GLOBALS['yby_test_options'][$k]=$v;return true;}
function wp_parse_args($a,$d=array()){return array_merge($d,is_array($a)?$a:array());}
function admin_url($p=''){return 'https://example.com/wp-admin/'.ltrim($p,'/');}
function add_query_arg($a,$u){return $u.'?'.http_build_query($a);}
function apply_filters($t,$v){return $v;}
class YBY_Helpers { public static function admin_page_slug(){return 'yby-core';} }

$repo_root = dirname( __DIR__, 3 );
require_once $repo_root . '/inc/class-yby-module-registry.php';
require_once $repo_root . '/inc/class-yby-module-settings-store.php';
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-export-presets.php';
require_once dirname(__DIR__) . '/includes/class-yby-woo-order-export-module.php';

$assert=function($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$assert(YBY_Woo_Order_Export_Module::register_module(),'module register');
$m=YBY_Module_Registry::module('woo_order_export');
$assert(false===$m['default_enabled'],'default off');
$assert('manage_woocommerce'===$m['capability'],'capability');
$assert('yby_woo_order_export_settings_v1'===$m['storage']['option_key'],'settings key');
$assert(!YBY_Module_Registry::is_available('woo_order_export'),'Woo absent -> unavailable');
define('WC_VERSION','10.0.0');
$assert(YBY_Module_Registry::is_available('woo_order_export'),'WC_VERSION -> available');
$d=YBY_Woo_Order_Export_Module::get_settings();
$assert($d['default_preset']==='default' && $d['batch_size']===200 && $d['bom']===true && $d['audit_enabled']===true,'settings defaults');
$c=YBY_Woo_Order_Export_Module::sanitize_settings(array('default_preset'=>'evil','batch_size'=>9999,'bom'=>0,'audit_enabled'=>0));
$assert($c['default_preset']==='default' && $c['batch_size']===500 && $c['bom']===false && $c['audit_enabled']===false,'settings sanitize');
$p=YBY_Woo_Order_Export_Presets::presets();
$assert(array_keys($p)===array('default','full','byl_processing_orders','byl_full_order_report'),'exact audited preset set');
$assert($p['byl_processing_orders']['label']==='BYL Processing Orders'&&$p['byl_processing_orders']['row_mode']==='order_row','processing BYL preset');
$assert($p['byl_full_order_report']['label']==='BYL Full Report'&&$p['byl_full_order_report']['row_mode']==='order_row','full BYL preset');
$assert($p['default']['row_mode']==='line_item'&&$p['full']['row_mode']==='line_item','generic line item rows');
$assert(!isset($p['byl_logistics'],$p['byl_accounting'],$p['byl_customer'],$p['byl_attribution']),'must not invent BYL presets');

$core=file_get_contents($repo_root . '/inc/class-yby-core.php');
$commerce=file_get_contents(dirname(__DIR__) . '/andy-commerce.php');
$assert(false===strpos($core,'class-yby-woo-order-export-module.php'),'Woo export implementation must not live in Core');
$assert(false===strpos($core,"'YBY_Woo_Order_Export_Module', 'register_module'"),'Core must not register Commerce module');
$assert(false!==strpos($commerce,'class-yby-woo-order-export-module.php'),'Commerce package must own export module');
$assert(false!==strpos($commerce,"andy_core_register_modules"),'Commerce must register through Core module seam');
echo "PASS v190-commerce-module-harness\n";
