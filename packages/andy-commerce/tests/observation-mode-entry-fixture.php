<?php
/** Synthetic WordPress fixture for plugin entrypoint isolation; NO real Woo/DB. */
error_reporting(E_ALL);
$case = $argv[1] ?? '';
$host = match ($case) {
    'www-frontend', 'www-admin', 'www-admin-hook-alert', 'www-post-active-confirmed', 'www-post-active-denied', 'www-explicit-active', 'www-post-observe', 'www-local' => 'www.beyourlover.com',
    'dev-default', 'dev-explicit-observe', 'dev-nonstaging' => 'dev.beyourlover.com',
    default => 'unknown.example.test',
};
$GLOBALS['fixture_host'] = $host;
$GLOBALS['fixture_environment'] = in_array($case,['local-default','www-local'],true) ? 'local' : (in_array($case,['dev-default','dev-explicit-observe'],true) ? 'staging' : 'production');
$GLOBALS['fixture_admin'] = in_array($case, ['www-admin','www-admin-hook-alert','www-post-active-confirmed','www-post-active-denied','www-post-observe','dev-explicit-observe'], true);
$GLOBALS['fixture_mode'] = match ($case) {
    'www-explicit-active', 'www-post-observe' => 'active',
    'dev-explicit-observe' => 'observe',
    'malformed-option' => 'surprise',
    default => null,
};
$GLOBALS['fixture_hooks'] = [];
$GLOBALS['fixture_writes'] = [];
$GLOBALS['fixture_audit'] = [];
$GLOBALS['wp_filter'] = [];
$GLOBALS['fixture_menus'] = [];
define('ABSPATH', '/isolated-wordpress/');
function plugin_dir_path($file) {return dirname($file) . '/';}
function plugin_dir_url($file) {return 'https://example.invalid/plugins/andy-commerce/';}
function home_url($path='/') {return 'https://' . $GLOBALS['fixture_host'] . $path;}
function wp_get_environment_type() {return $GLOBALS['fixture_environment'];}
function is_admin() {return $GLOBALS['fixture_admin'];}
function get_option($key, $default=null) {
    if ($key==='andy_commerce_runtime_mode_v1') return $GLOBALS['fixture_mode'];
    if ($key==='andy_commerce_runtime_audit_v1') return $GLOBALS['fixture_audit'];
    if ($key==='active_plugins') return ['yaycurrency-pro/yaycurrency.php','woocommerce-smart-coupons/smart-coupons.php','wt-smart-coupons-pro/smart-coupons.php'];
    if ($key==='woocommerce_currency') return 'USD';
    return $default;
}
function add_action($hook, $cb, $priority=10) {$GLOBALS['fixture_hooks'][]=$hook;}
function current_user_can($cap) {return $cap==='manage_options';}
function add_menu_page(...$args) {$GLOBALS['fixture_menus'][]=['top',$args[3]];}
function add_submenu_page(...$args) {
    $GLOBALS['fixture_menus'][]=['sub',$args[4]];
    if (isset($GLOBALS['submenu']) && is_array($GLOBALS['submenu'])) {
        $GLOBALS['submenu'][$args[0]][] = [$args[2],$args[3],$args[4]];
    }
}
function esc_html($text) {return htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8');}
function esc_url($text) {return htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8');}
function esc_attr($text) {return htmlspecialchars((string)$text,ENT_QUOTES,'UTF-8');}
function admin_url($path='') {return 'https://example.invalid/wp-admin/'.$path;}
function wp_nonce_field($action,$name) {echo '<input name="'.esc_attr($name).'" value="synthetic-nonce">';}
function wp_unslash($value) {return $value;}
function check_admin_referer($action,$name) {if (($_POST[$name]??'')!=='synthetic-nonce') throw new RuntimeException('BAD_NONCE');}
function update_option($key,$value,$autoload=false) {
    $GLOBALS['fixture_writes'][]=[$key,$value,$autoload];
    if ($key==='andy_commerce_runtime_mode_v1') {
        $GLOBALS['fixture_mode']=$value;
        echo 'MODE_PERSISTED='.$value.PHP_EOL;
    }
    if ($key==='andy_commerce_runtime_audit_v1') {
        $GLOBALS['fixture_audit']=$value;
        echo 'AUDIT_PERSISTED='.count($value).PHP_EOL;
    }
    return true;
}
function wp_safe_redirect($url) {echo 'SAFE_REDIRECT='. $url . PHP_EOL;}
function wp_die($error,$title='', $args=[]) {throw new RuntimeException('WP_DIE:'.$error);}
function get_bloginfo($arg) {return '7.1.4';}
function get_current_user_id() {return 123;}
function fail($msg) {fwrite(STDERR,'FAIL_MODE='.$msg.PHP_EOL);exit(1);}
function check($truth,$msg) {if(!$truth)fail($msg);}
$plugin=dirname(__DIR__) . '/andy-commerce.php';
if (in_array($case,['dev-default','local-default','www-explicit-active','www-post-observe'],true)) {
    require dirname(__DIR__) . '/includes/class-andy-commerce-observation-mode.php';
    check(Andy_Commerce_Observation_Mode::is_observing()===false,'explicit-active/dev-default');
    if ($case==='www-post-observe') {
        $_POST=['mode'=>'observe','expected_mode'=>'active','andy_commerce_observation_nonce'=>'synthetic-nonce'];
        Andy_Commerce_Observation_Mode::handle_toggle();
        fail('post should redirect and exit');
    }
    check(Andy_Commerce_Observation_Mode::valid_transition('observe','active','active',''),'entry should be one-click');
    check(!Andy_Commerce_Observation_Mode::valid_transition('active','observe','observe',''),'exit requires acknowledgement');
    check(Andy_Commerce_Observation_Mode::valid_transition('active','observe','observe','ENABLE_COMMERCE_RUNTIME'),'acknowledged exit');
    check(!Andy_Commerce_Observation_Mode::valid_transition('active','active','observe','ENABLE_COMMERCE_RUNTIME'),'stale mode blocked');
    check(!Andy_Commerce_Observation_Mode::valid_transition('trash','observe','observe','ENABLE_COMMERCE_RUNTIME'),'unknown mode blocked');
    echo 'PASS_MODE '.$case.PHP_EOL;
    exit;
}
require $plugin;
check(Andy_Commerce_Observation_Mode::is_observing()===($case!=='www-post-observe'),'production defaults observe');
check(!class_exists('Andy_Commerce',false),'full-commerce-was-loaded');
check(!class_exists('YBY_Woo_Order_Export_Module',false),'order-export loaded');
check(!class_exists('Andy_Commerce_Shipping_Promotion_Runtime',false),'shipping runtime loaded');
check($GLOBALS['fixture_writes']===[],'startup wrote options');
if ($case==='www-frontend'||$case==='unknown-host'||$case==='malformed-option'||$case==='www-local'||$case==='dev-nonstaging') {
    check($GLOBALS['fixture_hooks']===[],'frontend observe hooks registered');
} else {
    check($GLOBALS['fixture_hooks']===['admin_menu','admin_menu','admin_post_andy_commerce_observation_toggle'],'only admin hooks');
}
if ($case==='www-admin') {
    Andy_Commerce_Observation_Mode::register_menu();
    check($GLOBALS['fixture_menus']===[['top','andy-commerce'],['sub','andy-commerce-observation']],'safe menu expected');
    ob_start();Andy_Commerce_Observation_Mode::render_page();$html=ob_get_clean();
    check(str_contains($html,'进入观察模式')===false,'observe should show exit button');
    check(str_contains($html,'退出观察模式'),'exit button not rendered');
    check(str_contains($html,'交易 Hook 不加载'),'safe mode text missing');
}
echo 'PASS_MODE '.$case.PHP_EOL;
