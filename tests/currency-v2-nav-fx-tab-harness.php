<?php
/** Ensure currency-navigation integration is role-scoped and Dev-only. */
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
$items=YBY_Andy_OS_Navigation::tertiary_items('andy-commerce');
$labels=array_column($items,'title');
$ok($labels===['总览','订单导出','结账运费','促销','设置','多币种'],'one canonical L3 currency entry after settings');
$ok(str_ends_with(end($items)['url'],'page=andy-commerce&tab=currency-os'),'real currency route used');
$GLOBALS['dev']=false;
$ok(array_column(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce'),'title')===['总览','订单导出','结账运费','促销','设置'],'no dead links on non-Dev');
$GLOBALS['dev']=true;$GLOBALS['woo']=false;
$ok(YBY_Andy_OS_Navigation::tertiary_items('andy-commerce')===[],'capability check');
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
echo "PASS Currency OS native L3 + sixth FX tab ({$count} assertions)\n";
