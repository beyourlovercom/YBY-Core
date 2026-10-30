<?php
/** Admin-side rendering smoke with stubbed WordPress; no DB/network. */
define('ABSPATH', '/fake/');
$GLOBALS['observer_admin'] = true;
$GLOBALS['observer_actions'] = array();
$GLOBALS['observer_reads'] = array();
$GLOBALS['wp_filter'] = array('woocommerce_shipping_chosen_method' => (object) array('callbacks' => array(20 => array('a'=>array(), 'b'=>array()))));
function is_admin() {return $GLOBALS['observer_admin'];}
function add_action($hook, $callback, $priority = 10) {$GLOBALS['observer_actions'][] = $hook;}
function current_user_can($cap) {return $cap === 'manage_options';}
function add_management_page($a,$b,$c,$d,$e) {$GLOBALS['observer_page'] = $d;}
function get_plugins() {return array(
 'woocommerce/woocommerce.php' => array('Name'=>'WooCommerce', 'Version'=>'11.2.1'),
 'yaycurrency-pro/yay-currency.php' => array('Name'=>'YayCurrency Pro', 'Version'=>'3.3.5'),
 'other/other.php' => array('Name'=>'Unrelated (should be ignored)', 'Version'=>'1'),
 'evil/evil.php' => array('Name'=>'Andy Commerce <script>oops()</script>', 'Version'=>'1.2'),
 );}
function is_plugin_active($slug) {return strpos($slug,'woocommerce') !== false;}
function get_bloginfo($k) {return '7.0';}
function wp_get_environment_type() {return 'development';}
class Theme {function get($k) {return $k==='Name' ? 'Shoptimizer' : '2.9.9';}}
function wp_get_theme() {return new Theme();}
function get_option($key,$default=null) {$GLOBALS['observer_reads'][]=$key; return $key==='woocommerce_currency' ? 'USD' : $default;}
function esc_html($s) {return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');}
function esc_html__($s,$domain) {return $s;}
function wp_die($s) {throw new Exception($s);}
define('YBY_CORE_VERSION','2.3.2');
define('WC_VERSION','11.2.1');
require __DIR__ . '/../andy-commerce-observer.php';
Andy_Commerce_Read_Only_Observer::register_admin_page();
ob_start(); Andy_Commerce_Read_Only_Observer::render_admin_page(); $html=ob_get_clean();
$assert=static function($ok,$msg) {if(!$ok) {fwrite(STDERR,'FAIL '.$msg."\n"); exit(1);}};
$assert($GLOBALS['observer_actions']===array('admin_menu'),'only admin_menu');
$assert($GLOBALS['observer_page']==='andy-commerce-read-only-observer','correct tools menu');
$assert(strpos($html,'WooCommerce')!==false && strpos($html,'YayCurrency Pro')!==false,'inventory output');
$assert(strpos($html,'Unrelated (should be ignored)')===false,'inventory scoped');
$assert(strpos($html,'&lt;script&gt;')!==false && strpos($html,'<script>oops()')===false,'XSS escaped');
$assert(strpos($html,'woocommerce_shipping_chosen_method')!==false,'hook counts shown');
$assert(strpos($html,'read-only')!==false || strpos($html,'Read-only')!==false,'observer label');
$assert(strpos($html,'<form')===false,'no writable forms');
$assert(in_array('byl_shipping_promotion_settings',$GLOBALS['observer_reads'],true),'shipping read');
echo "PASS observer-admin-smoke privilege/menu/inventory/XSS/hook-counts/no-form\n";
