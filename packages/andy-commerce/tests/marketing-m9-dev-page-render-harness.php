<?php
/** M9 admin DEV-only view rendering contract with isolated fake WP API. */
declare(strict_types=1);
define('ABSPATH', '/tmp/marketing-m9-lab-render/');
$GLOBALS['testHost'] = 'dev.beyourlover.com';
$GLOBALS['testPermission'] = true;
function current_user_can($permission){return $GLOBALS['testPermission'] && $permission==='manage_woocommerce';}
function home_url($suffix=''){return 'https://'.$GLOBALS['testHost'].'/'.$suffix;}
function admin_url($suffix=''){return 'https://'.$GLOBALS['testHost'].'/wp-admin/'.$suffix;}
function add_query_arg($args,$url){return $url.'?'.http_build_query($args);}
function esc_url($url){return htmlspecialchars((string)$url,ENT_QUOTES);}
function esc_html($text){return htmlspecialchars((string)$text,ENT_QUOTES);}
function esc_attr($text){return htmlspecialchars((string)$text,ENT_QUOTES);}
function sanitize_key($key){return preg_replace('/[^a-z0-9_-]/','',strtolower($key));}
function wp_unslash($data){return $data;}
function selected($current,$value){if((string)$current===(string)$value)echo 'selected="selected"';}
function wp_nonce_field($action){echo '<input type="hidden" name="_wpnonce" data-action="'.htmlspecialchars((string)$action,ENT_QUOTES).'" value="dev-placeholder">';}
final class Andy_Commerce_M7_Draft_Studio {
    public static function dev_only():bool {return $GLOBALS['testHost']==='dev.beyourlover.com';}
    public static function rows():array {
        return [
          'id_123'=>[
             'id'=>'id_123','name'=>'Dev inactive $139 discount','currency'=>'USD',
             'active'=>false,'status'=>'draft','type'=>'spend_save','priority'=>10,
             'rule'=>['threshold_minor'=>13900,'discount_minor'=>1200]
          ],
          'id_456'=>[
             'id'=>'id_456','name'=>'EUR draft excluded','currency'=>'EUR',
             'active'=>false,'status'=>'draft','type'=>'spend_save','priority'=>10,
             'rule'=>['threshold_minor'=>13900,'discount_minor'=>800]
          ],
        ];
    }
}
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-m9-usd-rule-engine.php';
$count=0;
function m9pageCheck(bool $v,string $msg):void {
    global $count; $count++;
    if(!$v)throw new RuntimeException('FAIL: '.$msg);
}
function renderM9(array $params):string {
    $_GET=$params;
    ob_start();
    include dirname(__DIR__).'/admin/views/marketing-m9-usd-multirule-lab.php';
    return (string)ob_get_clean();
}
set_error_handler(static function(int $severity,string $message):never {
    throw new ErrorException($message,0,$severity);
});
$codes=['USD','EUR','GBP','AUD','CAD'];
foreach($codes as $code) {
    foreach(['0','1','3','4','5'] as $zone) {
        $html=renderM9(['m9_currency'=>$code,'m9_zone'=>$zone,'m9_gross'=>'180',
            'm9_discount'=>'15','m9_gift_threshold'=>'99',
            'm9_discount_threshold'=>'169','m9_reward'=>'20',
            'm9_extra_threshold'=>'129','m9_extra_reward'=>'10']);
        m9pageCheck(str_contains($html,'营销 OS · 美元多规则工作台'),
            "$code/$zone M9 WordPress admin view renders");
        m9pageCheck(str_contains($html,'$165.00') && str_contains($html,'$153.00'),
            "$code/$zone inactive USD M7 draft $12 beats built-in $10: final USD153");
        m9pageCheck(str_contains($html,'未启用 · 不发放'),
            "$code/$zone awards remain off");
        m9pageCheck(str_contains($html,'draft_id_123') && !str_contains($html,'EUR draft excluded'),
            "$code/$zone compatible inactive USD M7 draft projection");
        m9pageCheck(str_contains($html,'未修改 Woo 运费'),
            "$code/$zone native shipping fee untouched");
        m9pageCheck(str_contains($html,'name="m9_currency"')
            && str_contains($html,'name="action" value="andy_commerce_m7_save_draft"'),
            "$code/$zone simulation GET plus separately guarded M7 inactive-draft form");
        m9pageCheck(str_contains($html,'data-action="andy_commerce_m7_save_draft"'),
            "$code/$zone form reuses exact canonical M7 save nonce");
    }
}
$html=renderM9(['m9_currency'=>'USD','m9_zone'=>'3','m9_gross'=>'180','m9_discount'=>'5']);
m9pageCheck(str_contains($html,'$175.00') && str_contains($html,'$155.00'),
    'USD175 before M9 off20 means USD155 after');
m9pageCheck(str_contains($html,'未达标或待验证'),
    'Zone3 native free shipping is not unlocked after USD20 award');
$html=renderM9(['m9_currency'=>'<script>','m9_zone'=>'999',
    'm9_gross'=>['array'=>'attack'],'m9_reward'=>'0']);
m9pageCheck(str_contains($html,'营销 OS · 美元多规则工作台'),
    'Malformed GET parameters safely fallback without runtime crash');
$GLOBALS['testPermission']=false;
$html=renderM9(['m9_currency'=>'USD']);
m9pageCheck(str_contains($html,'多规则预览仅限获得授权的 Dev 管理员使用。')
    && !str_contains($html,'独立营销规则'),
    'non-admin cannot see currency/marketing preview');
$GLOBALS['testPermission']=true;
$GLOBALS['testHost']='www.beyourlover.com';
$html=renderM9(['m9_currency'=>'USD']);
m9pageCheck(str_contains($html,'多规则预览仅限获得授权的 Dev 管理员使用。')
    && !str_contains($html,'独立营销规则'),
    'WWW always refuses M9 view');
restore_error_handler();
echo "PASS Marketing M9 Dev page: {$count} render assertions (5 currencies x 5 Woo zones), no WordPress writes\n";
