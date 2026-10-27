<?php
/** Isolated R3 Woo Classic runtime route and OFF/on rollback gate. */
if (!defined('ABSPATH')) define('ABSPATH',__DIR__);
class WooCommerce {}
$GLOBALS['opts']=array();$GLOBALS['scripts']=array();$GLOBALS['config']=array();
$GLOBALS['admin']=false;$GLOBALS['route']='global';
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function get_option($key,$d=false){return $GLOBALS['opts'][$key]??$d;}
function update_option($key,$v,$autoload=null){$GLOBALS['opts'][$key]=$v;return true;}
function is_admin(){return $GLOBALS['admin'];}
function is_feed(){return false;}
function is_embed(){return false;}
function is_checkout(){return in_array($GLOBALS['route'],array('checkout','order-pay','thankyou'),true);}
function is_cart(){return $GLOBALS['route']==='cart';}
function is_order_received_page(){return $GLOBALS['route']==='thankyou';}
function is_wc_endpoint_url($s){return $s==='order-pay'&&$GLOBALS['route']==='order-pay';}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){$GLOBALS['scripts'][]=compact('handle','src','deps','version','footer');}
function wp_localize_script($handle,$name,$data){$GLOBALS['config'][]=compact('handle','name','data');}
function admin_url($p=''){return '/wp-admin/'.$p;}
function add_query_arg($args,$u){return $u.'?'.http_build_query($args);}
function check($ok,$message){if(!$ok){fwrite(STDERR,'FAIL '.$message."\n");exit(1);}}
define('YBY_CORE_PLUGIN_URL','https://localhost/wp-content/plugins/yby-core/');
define('YBY_CORE_VERSION','2.2.12');
require dirname(__DIR__).'/inc/class-yby-module-registry.php';
require dirname(__DIR__).'/inc/class-yby-notification-ui-module.php';
check(YBY_Notification_UI_Module::register_module(),'module registry');$meta=YBY_Module_Registry::module('notification_ui');
$existing=YBY_Module_Registry::defaults();
$GLOBALS['route']='checkout';
YBY_Notification_UI_Module::enqueue_assets($meta);
check(!$GLOBALS['scripts'],'off checkout no assets');
YBY_Module_Registry::save(array_merge($existing,array('notification_ui')));
function collect($route,$admin=false){
 $GLOBALS['scripts']=array();$GLOBALS['config']=array();
 $GLOBALS['route']=$route;$GLOBALS['admin']=$admin;
 YBY_Notification_UI_Module::enqueue_assets(YBY_Module_Registry::module('notification_ui'));
 return array_map(static function($e){return $e['handle'];},$GLOBALS['scripts']);
}
check(collect('global')===array('andy-core-notification-ui'),'on global only core engine');
check(collect('checkout')===array('andy-core-notification-ui','andy-core-notification-woo-classic'),'checkout addon');
check($GLOBALS['scripts'][1]['deps']===array('andy-core-notification-ui'),'Woo depends on engine');
check(collect('cart')===array('andy-core-notification-ui','andy-core-notification-woo-classic'),'cart addon');
check(collect('order-pay')===array('andy-core-notification-ui'),'exclude order pay');
check(collect('thankyou')===array('andy-core-notification-ui'),'exclude receipt');
check(collect('checkout',true)===array(),'exclude admin');
YBY_Module_Registry::save($existing);
check(collect('checkout')===array(),'off checkout disables everything');
check(YBY_Module_Registry::enabled_modules()===$existing,'preserves existing modules');
$js=file_get_contents(dirname(__DIR__).'/public/assets/js/andy-core-notification-woo-classic.js');
check(strpos($js,'woocommerce-message')!==false,'Woo success DOM selector');
check(strpos($js,'woocommerce-error')!==false,'Woo error skip guard');
check(strpos($js,"getComputedStyle(node)")!==false,'visibility check');
check(strpos($js,'notify({ type:')!==false,'Core-only API handoff');
check(strpos($js,'restoreNative')!==false,'native fallback');
check(strpos($js,'style.setProperty')!==false,'only per-node style suppression');
check(strpos($js,'.woocommerce-message{display:none}')===false,'never hide Woo globally');
check(strpos($js,'fetch(')===false&&strpos($js,'XMLHttpRequest')===false,'no business network');
echo "PASS notification-ui-v1-r3-route-harness (OFF/ON/OFF, Classic, Order Pay excluded, no Woo business side effects)\n";
