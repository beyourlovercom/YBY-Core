<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['o']=[];
$GLOBALS['logged']=false;
$GLOBALS['cap']=false;
$GLOBALS['user_id']=0;
$GLOBALS['is_admin']=false;
$GLOBALS['host_master']='no';
$GLOBALS['legacy']=false;
$GLOBALS['remove_returns']=true;
$GLOBALS['removed_count']=0;
$GLOBALS['boot_count']=0;
$GLOBALS['no_cache_count']=0;
$GLOBALS['ready']=true;
function get_option($name,$fallback=null){return $GLOBALS['o'][$name]??$fallback;}
function is_user_logged_in(){return $GLOBALS['logged'];}
function current_user_can($cap){return $cap==='manage_options' && $GLOBALS['cap'];}
function get_current_user_id(){return $GLOBALS['user_id'];}
function is_admin(){return $GLOBALS['is_admin'];}
function remove_action($action,$callback,$priority){
 if($action==='woocommerce_cart_loaded_from_session' && $priority===95){
  ++$GLOBALS['removed_count'];return $GLOBALS['remove_returns'];
 }
 return false;
}
function nocache_headers(){++$GLOBALS['no_cache_count'];}
class WooCommerce{}
class BYL_BF2026_Gifts {
 public const ID='byl-black-friday-2026';
 public static function ready(){return $GLOBALS['ready'];}
 public static function boot(){++$GLOBALS['boot_count'];}
}
class Andy_Campaign_Lite {
 public const MASTER='andy_campaign_lite_master';
 private static $inst=null;
 public static function boot(){return self::$inst??=new self();}
 public function decisions(){return [BYL_BF2026_Gifts::ID=>'master_off'];}
 public static function legacy_runtime_requested(){return $GLOBALS['legacy'];}
 public function revoke_stale_lines($cart){}
}
require __DIR__.'/../includes/class-byl-bf2026-r14-canary.php';
$n=0;
function ck($v,$desc){global $n;if(!$v)throw new RuntimeException('FAIL_'.$desc);++$n;}
$klass='BYL_BF2026_R14_Canary';
$token=str_repeat('a1',24);
$GLOBALS['o']['byl_bf2026_canary_v1']=[
 'state'=>'active',
 'user_id'=>42,
 'expires_at'=>time()+1000,
 'token_sha256'=>hash('sha256',$token)
];
$_COOKIE['byl_bf26_canary']=$token;
ck(!$klass::granted(),'anonymous_denied');
$GLOBALS['logged']=true;
ck(!$klass::granted(),'authenticated_no_cap_denied');
$GLOBALS['cap']=true;
$GLOBALS['user_id']=43;
ck(!$klass::granted(),'other_admin_denied');
$GLOBALS['user_id']=42;
ck($klass::granted(),'correct_admin_cookie_allowed');
$_COOKIE['byl_bf26_canary']=str_repeat('be',24);
ck(!$klass::granted(),'wrong_cookie_denied');
$_COOKIE['byl_bf26_canary']=$token;
$GLOBALS['o']['byl_bf2026_canary_v1']['expires_at']=time()-1;
ck(!$klass::granted(),'expired_denied');
$GLOBALS['o']['byl_bf2026_canary_v1']['expires_at']=time()+1000;
$GLOBALS['o']['byl_bf2026_canary_v1']['state']='off';
ck(!$klass::granted(),'operator_stopped_denied');
$GLOBALS['o']['byl_bf2026_canary_v1']['state']='active';
unset($_COOKIE['byl_bf26_canary']);
ck(!$klass::granted(),'no_browser_cookie_denied');
$_COOKIE['byl_bf26_canary']=$token;
ck($klass::runtime_allowed(),'canary_only_allowed');
$GLOBALS['is_admin']=true;
$klass::bootstrap();
ck($GLOBALS['removed_count']===0&&$GLOBALS['boot_count']===0,'never_boot_admin_screen');
$GLOBALS['is_admin']=false;
$GLOBALS['host_master']='yes';
$GLOBALS['o']['andy_campaign_lite_master']='yes';
$klass::bootstrap();
ck($GLOBALS['removed_count']===0,'master_on_blocks_canary_boot');
$GLOBALS['o']['andy_campaign_lite_master']='no';
$GLOBALS['legacy']=true;
$klass::bootstrap();
ck($GLOBALS['removed_count']===0,'legacy_conflict_blocks_canary_boot');
$GLOBALS['legacy']=false;
$GLOBALS['ready']=false;
$klass::bootstrap();
ck($GLOBALS['removed_count']===0,'gift_not_ready_blocks_boot');
$GLOBALS['ready']=true;
$GLOBALS['remove_returns']=false;
$klass::bootstrap();
ck($GLOBALS['removed_count']===1&&$GLOBALS['boot_count']===0,'no_bypass_without_revoke_detach');
$GLOBALS['remove_returns']=true;
$klass::bootstrap();
ck($GLOBALS['removed_count']===2&&$GLOBALS['boot_count']===1,'one_authorized_boot');
ck($GLOBALS['no_cache_count']===1,'canary_no_cache');
$klass::bootstrap();
ck($GLOBALS['removed_count']===2&&$GLOBALS['boot_count']===1,'no_duplicate_hooks');
$GLOBALS['logged']=false;
ck(!$klass::runtime_allowed(),'guest_still_OFF_after_canary_boot');
ck(!$klass::global_release_allowed(),'full_launch_constant_default_OFF');
echo 'BF2026_R14_ADMIN_COOKIE_CANARY_FIXTURE_PASS='.$n.PHP_EOL;
