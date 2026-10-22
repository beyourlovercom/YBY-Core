<?php
/** Currency OS Dev C1: shortcode output, five-tab contract, no Woo writes. */
declare(strict_types=1);
define('ABSPATH','/unit/');
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['admin']=true;
function home_url($x='/'){return 'https://'.$GLOBALS['host'].$x;}
function current_user_can($permission){return $permission==='manage_woocommerce'&&$GLOBALS['admin'];}
function is_admin(){return !empty($GLOBALS['wp_admin_context']);}
function get_option($name,$default=[]){return $default;}
function add_shortcode($name,$fn){$GLOBALS['shortcodes'][$name]=$fn;}
function add_action($name,$fn,$priority=10){$GLOBALS['hooks'][$name]=$fn;}
function shortcode_atts($default,$args,$tag){return array_merge($default,$args);}
function wp_unique_id(){static $n=0;return ++$n;}
function wp_json_encode($v){return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
function esc_attr($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function selected($one,$two,$echo=true){return $one===$two?' selected="selected"':'';}
function sanitize_key($v){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v));}
function wp_unslash($v){return $v;}
function do_shortcode($text){return call_user_func($GLOBALS['shortcodes']['andy_currency_switcher'],[]);}
require_once dirname(__DIR__).'/includes/class-andy-commerce-currency-v2-dev.php';
$n=0;
function ok($value,$label){global $n;$n++;if(!$value)throw new RuntimeException('Currency V2 failed: '.$label);}
Andy_Commerce_Currency_V2_Dev::boot();
ok(isset($GLOBALS['shortcodes']['andy_currency_switcher']),'registered shortcode');
ok(isset($GLOBALS['hooks']['wp_footer']),'frontend test hook');
ok(Andy_Commerce_Currency_V2_Dev::access(),'authorized Dev admin');
$basic=Andy_Commerce_Currency_V2_Dev::shortcode([]);
foreach(['USD','EUR','GBP','AUD','CAD'] as $c)ok(str_contains($basic,$c),'currency '.$c);
ok(str_contains($basic,'Sample USD'),'explains sample only');
ok(str_contains($basic,'No Woo prices'),'explicit no live changes');
$compact=Andy_Commerce_Currency_V2_Dev::shortcode(['style'=>'compact']);
ok(str_contains($compact,'ac-compact'),'compact shortcode');
ok(str_contains($compact,'select'),'has selector');
$GLOBALS['wp_admin_context']=true;
$adminPreview=Andy_Commerce_Currency_V2_Dev::shortcode(['style'=>'compact']);
ok(str_contains($adminPreview,'货币切换器 · Dev 预览'),'Chinese backend shortcode label');
ok(str_contains($adminPreview,'仅展示效果'),'Chinese backend safety notice');
$GLOBALS['wp_admin_context']=false;
$GLOBALS['admin']=false;
ok(Andy_Commerce_Currency_V2_Dev::shortcode([])==='','anonymous denied');
$GLOBALS['admin']=true;$GLOBALS['host']='www.beyourlover.com';
ok(Andy_Commerce_Currency_V2_Dev::shortcode([])==='','WWW fail closed');
$GLOBALS['host']='dev.beyourlover.com';
$view=file_get_contents(dirname(__DIR__).'/admin/views/currency-os-v2-admin.php');
foreach(['币种管理','结账设置','展示设置','高级设置','USD 营销核算'] as $tab)
    ok(str_contains($view,$tab),'tab '.$tab);
ok(str_contains($view,'andy_currency_v2_preview_save'),'Dev nonce');
ok(str_contains($view,'保存 Dev 预览设置'),'localized save action');
ok(str_contains($view,'已保存'),'localized save feedback');
ok(str_contains($view,'查看汇率核算 V1'),'localized ledger navigation');
ok(str_contains($view,'固定汇率（草稿）') && str_contains($view,'自动更新'),'localized FX modes');
ok(str_contains($view,'默认国家或地区'),'localized country labels');
ok(str_contains($view,'显示货币符号') && str_contains($view,'显示国旗'),'localized switcher controls');
ok(str_contains($view,'[andy_currency_switcher style="compact"'),'shortcode displayed in admin');
$php=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-currency-v2-dev.php');
ok(str_contains($php,'check_admin_referer'),'nonce checked server-side');
ok(str_contains($php,'update_option(self::OPTION'),'isolated options storage');
ok(str_contains($php, 'if($section===\'manage\')'), 'tab-isolated saving');
ok(!str_contains($php,'woocommerce_cart_calculate_fees'),'no Woo campaign writes');
ok(!str_contains($php,'woocommerce_before_calculate_totals'),'no Woo product writes');
ok(!str_contains($php,'setcookie('),'no shopper currency cookie');
echo "PASS Currency V2 Dev-only Admin & Shortcode: {$n} assertions\n";
