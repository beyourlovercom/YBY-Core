<?php
/**
 * R16 P0: verified WordPress logged-in cookie + independently signed
 * administrator Canary during Woo Store API REST auth demotion.
 *
 * No real WordPress database, customer cart or payment is used here.
 */
declare(strict_types=1);
define('ABSPATH','/');
define('REST_REQUEST',true);
define('LOGGED_IN_COOKIE','wordpress_logged_in_r16_fixture');
$GLOBALS['o']=[];
$GLOBALS['logged']=false;
$GLOBALS['cap']=true;
$GLOBALS['uid']=0;
$GLOBALS['master']='no';
$GLOBALS['removed']=0;
$GLOBALS['booted']=0;
$GLOBALS['cache_headers']=0;
$GLOBALS['admin_page']=false;
function get_option($name,$default=null) {return $GLOBALS['o'][$name]??$default;}
function is_user_logged_in(): bool {return $GLOBALS['logged'];}
function get_current_user_id(): int {return (int)$GLOBALS['uid'];}
function current_user_can($cap): bool {
 return $GLOBALS['cap'] && $GLOBALS['logged'] && $cap==='manage_options';
}
function wp_validate_auth_cookie($cookie,$scheme='') {
 if ($scheme!=='logged_in') {return false;}
 if ($cookie==='valid-admin-42') {return 42;}
 if ($cookie==='valid-other-admin-43') {return 43;}
 return false;
}
function user_can($user,$cap): bool {
 return $GLOBALS['cap'] && $user===42 && $cap==='manage_options';
}
function is_admin(): bool {return $GLOBALS['admin_page'];}
function remove_action($hook,$cb,$priority=10) {
 if ($hook==='woocommerce_cart_loaded_from_session' && $priority===95
     && $cb[1]==='revoke_stale_lines') {
  ++$GLOBALS['removed'];
  return true;
 }
 return false;
}
function nocache_headers() {++$GLOBALS['cache_headers'];}
class WooCommerce {}
class BYL_BF2026_Gifts {public const ID='byl-black-friday-2026';}
class BYL_BF2026_R15_Entitlements {
 public static function ready(): bool {return true;}
 public static function boot() {++$GLOBALS['booted'];}
}
class Andy_Campaign_Lite {
 public const MASTER='andy_campaign_lite_master';
 private static $host=null;
 public static function boot() {return self::$host??=new self();}
 public function decisions(): array {return [BYL_BF2026_Gifts::ID=>'master_off'];}
 public static function legacy_runtime_requested() {return false;}
 public function revoke_stale_lines($cart) {}
}
require __DIR__.'/../includes/class-byl-bf2026-r14-canary.php';
$n=0;
function verify($condition,$name): void {
 global $n;
 if (!$condition) {throw new RuntimeException('R16_CANARY_FAIL_'.$name);}
 ++$n;
}
$token=str_repeat('ab',24);
$sha=hash('sha256',$token);
$GLOBALS['o']['byl_bf2026_canary_v1']=[
 'state'=>'active','user_id'=>42,'expires_at'=>time()+1200,
 'token_sha256'=>$sha
];
$_COOKIE['byl_bf26_canary']=$token;
verify(!BYL_BF2026_R14_Canary::granted(),'no_wp_logged_in_cookie');
$_COOKIE[LOGGED_IN_COOKIE]='valid-admin-42';
verify(BYL_BF2026_R14_Canary::granted(),'rest_valid_wp_cookie_and_signed_canary');
verify(BYL_BF2026_R14_Canary::runtime_allowed(),'rest_canary_runtime_ready');
$GLOBALS['cap']=false;
verify(!BYL_BF2026_R14_Canary::granted(),'rest_no_manage_options');
$GLOBALS['cap']=true;
$_COOKIE[LOGGED_IN_COOKIE]='valid-other-admin-43';
verify(!BYL_BF2026_R14_Canary::granted(),'rest_mismatched_wp_identity');
$_COOKIE[LOGGED_IN_COOKIE]='expired-admin-cookie';
verify(!BYL_BF2026_R14_Canary::granted(),'rest_expired_login_cookie');
$_COOKIE[LOGGED_IN_COOKIE]='valid-admin-42';
$_COOKIE['byl_bf26_canary']=str_repeat('cd',24);
verify(!BYL_BF2026_R14_Canary::granted(),'rest_wrong_canary_signature');
$_COOKIE['byl_bf26_canary']=$token;
$GLOBALS['o']['byl_bf2026_canary_v1']['expires_at']=time()-1;
verify(!BYL_BF2026_R14_Canary::granted(),'rest_expired_canary');
$GLOBALS['o']['byl_bf2026_canary_v1']['expires_at']=time()+1200;
$GLOBALS['o']['byl_bf2026_canary_v1']['state']='off';
verify(!BYL_BF2026_R14_Canary::granted(),'rest_stopped_canary');
$GLOBALS['o']['byl_bf2026_canary_v1']['state']='active';
$GLOBALS['logged']=true;$GLOBALS['uid']=43;
verify(!BYL_BF2026_R14_Canary::granted(),'rest_other_logged_in_admin_never_fallback');
$GLOBALS['uid']=42;
verify(BYL_BF2026_R14_Canary::granted(),'normal_wp_admin_auth');
$GLOBALS['logged']=false;$GLOBALS['uid']=0;
verify(BYL_BF2026_R14_Canary::granted(),'rest_login_demotion_recovered');
$GLOBALS['o']['andy_campaign_lite_master']='yes';
BYL_BF2026_R14_Canary::bootstrap();
verify($GLOBALS['removed']===0 && $GLOBALS['booted']===0,
 'master_on_still_blocks_rest_canary');
$GLOBALS['o']['andy_campaign_lite_master']='no';
BYL_BF2026_R14_Canary::bootstrap();
verify($GLOBALS['removed']===1 && $GLOBALS['booted']===1,
 'rest_canary_detaches_legacy_revocation_and_boots');
BYL_BF2026_R14_Canary::bootstrap();
verify($GLOBALS['removed']===1 && $GLOBALS['booted']===1,
 'no_duplicate_hooks_after_bootstrap');
unset($_COOKIE[LOGGED_IN_COOKIE]);
verify(!BYL_BF2026_R14_Canary::runtime_allowed(),'no_cookie_guest_never_allowed');
verify(!BYL_BF2026_R14_Canary::global_release_allowed(),
 'public_global_gifts_default_off');
echo 'BF2026_R16_REST_OPERATOR_AUTH_FIXTURE_PASS='.$n.PHP_EOL;
