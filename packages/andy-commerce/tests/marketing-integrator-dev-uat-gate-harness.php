<?php
declare(strict_types=1);
define('ABSPATH','/tmp/marketing-gate/');
define('AICC_MARKETING_DEV_UAT',true);
$GLOBALS['dev_host']='https://www.beyourlover.com';
$GLOBALS['environment']='production';
$GLOBALS['login']=false;
$GLOBALS['can_manage']=false;
$GLOBALS['opts']=[];
function home_url($path=''){return $GLOBALS['dev_host'].$path;}
function wp_get_environment_type(){return $GLOBALS['environment'];}
function is_ssl(){return true;}
function is_user_logged_in(){return $GLOBALS['login'];}
function current_user_can($permission){return $GLOBALS['can_manage'];}
function get_woocommerce_currency(){return 'USD';}
function wc_get_price_decimals(){return 2;}
function get_option($key,$fallback=null){return $GLOBALS['opts'][$key]??$fallback;}
function is_admin(){return false;}
require_once dirname(__DIR__).'/includes/class-andy-commerce-marketing-dev-uat.php';
$p1=Andy_Commerce_Marketing_P1_Sandbox_Contract::class;
$admin=Andy_Commerce_Marketing_Dev_UAT::class;
$checks=0;
$assert=static function(bool $condition,string $label)use(&$checks){
 if(!$condition)throw new RuntimeException('MARKETING_DEV_SECURITY_FAIL '.$label);
 $checks++;
};
$assert(!$admin::site_gate() && !$p1::isolated(),'WWW host denied');
$GLOBALS['dev_host']='https://dev.beyourlover.com';
$assert(!$admin::site_gate(),'production WP_ENV denied');
$GLOBALS['environment']='staging';
$assert($admin::site_gate(),'Dev identity works only with config constant');
$assert(!$p1::dev_admin_setup(),'guest denied');
$GLOBALS['login']=true;
$assert(!$p1::dev_admin_setup(),'logged-in restricted role denied');
$GLOBALS['can_manage']=true;
$assert($p1::dev_admin_setup(),'Woo admin can prepare Dev UAT');
$assert(!$p1::approved_dev_owner(),'Dev UAT OFF by default');
$GLOBALS['opts']['andy_commerce_marketing_dev_uat_enabled']='yes';
$assert($p1::approved_dev_owner(),'explicit Dev UAT option and permission');
$assert($p1::isolated(),'Dev owner UAT accepted');
$assert($p1::disposable_origin(),'Dev operator-only hooks available for safe cleanup');
$GLOBALS['can_manage']=false;
$assert(!$p1::isolated() && !$p1::disposable_origin(),'Dev guest without privileges denied');
$GLOBALS['can_manage']=true;
$GLOBALS['dev_host']='https://www.beyourlover.com';
$assert(!$admin::site_gate() && !$p1::isolated(),'WWW cannot arm');
$GLOBALS['dev_host']='https://dev.beyourlover.com';
$GLOBALS['environment']='production';
$assert(!$p1::isolated(),'bad environment cannot arm despite host');
echo 'MARKETING_DEV_UAT_SECURITY_PASS='.$checks.'; WWW=OFF; GUEST=OFF; DEFAULT=OFF'.PHP_EOL;
