<?php
/** Currency source contract; runtime Core #201 combination has its own gate. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
$GLOBALS['woo']=true;
$GLOBALS['dev']=true;
function current_user_can($cap){return $cap==='manage_woocommerce' && $GLOBALS['woo'];}
function admin_url($path=''){return 'https://dev.beyourlover.com/wp-admin/'.$path;}
function sanitize_key($text){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$text));}
function wp_unslash($value){return $value;}
final class YBY_Module_Registry {public static function is_enabled($x){return false;}}
final class Andy_Commerce_Currency_V2_Dev {public static function access(){return $GLOBALS['dev'] && $GLOBALS['woo'];}}
require dirname(__DIR__).'/admin/class-yby-andy-os-navigation.php';
$count=0;
$ok=function($v,$name)use(&$count){if(!$v)throw new RuntimeException('Currency navigation failed: '.$name);$count++;};
// Until Core registry #201 is composed, the old navigation file retains ONLY native items.
$items=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$ok(array_column($items,'title')===['总览','订单导出','结账运费','促销','设置'],'Core native L3 does not hardcode Currency');
require dirname(__DIR__).'/packages/andy-commerce/admin/class-andy-commerce-admin.php';
$declared=Andy_Commerce_Admin::register_l3_items([], 'andy-commerce');
$ok(count($declared)===1 && $declared[0]['title']==='多币种','Commerce declares Currency itself');
$ok(($declared[0]['after']??'')==='settings','Currency anchored after Settings');
$ok(($declared[0]['page']??'')==='andy-commerce' && ($declared[0]['tab']??'')==='currency-os','canonical controller route declared');
$ok(call_user_func($declared[0]['route_check'],'currency-os','andy-commerce')===true,'controller checks real route');
$GLOBALS['dev']=false;
$ok(Andy_Commerce_Admin::register_l3_items([], 'andy-commerce')===[],'no declaration when not Dev');
$GLOBALS['dev']=true;$GLOBALS['woo']=false;
$ok(Andy_Commerce_Admin::register_l3_items([], 'andy-commerce')===[],'Commerce declaration role gated');
$ok(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===[],'native Core capability check');
$GLOBALS['woo']=true;
$admin=file_get_contents(dirname(__DIR__).'/packages/andy-commerce/admin/views/currency-os-v2-admin.php');
$controller=file_get_contents(dirname(__DIR__).'/packages/andy-commerce/admin/class-andy-commerce-admin.php');
$ledger=file_get_contents(dirname(__DIR__).'/packages/andy-commerce/admin/views/currency-os-v1-dev-lab.php');
$ok(str_contains($admin,"'ledger'=>'USD 营销核算','fx'=>'汇率核算'"),'sixth tab ordered after five settings tabs');
$ok(str_contains($admin,'currency_os_v2_embedded=true'),'reuse existing FX without new engine');
$ok(str_contains($admin,'if($section!==\'fx\')'),'no nested HTML forms in FX view');
$ok(str_contains($admin,'<h2>汇率核算</h2>'),'visible FX heading');
$ok(str_contains($ledger,'name="section" value="fx"'),'FX GET form preserves sixth tab');
$ok(str_contains($ledger,'byl-currency-os-demo')&&str_contains($ledger,'byl-currency-fx-mode'),'existing validated currency/rate selectors retained');
$ok(str_contains($controller,"$"."currency_os_legacy_ledger"),'old ledger deeplinks mapped to unified screen');
$ok(!str_contains($admin,'href="<?php echo esc_url(add_query_arg([\'currency_screen\'=>\'ledger\']'),'standalone header link replaced by tab');
echo "PASS Commerce-owned Currency L3 contract + sixth FX tab ({$count} assertions)\n";
