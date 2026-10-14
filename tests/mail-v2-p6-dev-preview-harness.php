<?php
/** P6 Dev-only preview code contract; no WordPress load, mail or DB access. */
define( 'ABSPATH', __DIR__ );
define( 'YBY_CORE_VERSION', 'test' );
$GLOBALS['dev_host'] = 'dev.beyourlover.com';
$GLOBALS['admin_on'] = true;
$GLOBALS['caps'] = array('andy_core_mail_view'=>true,'manage_options'=>true);
$GLOBALS['nonce_ok'] = true;
function site_url() { return 'https://' . $GLOBALS['dev_host']; }
function is_admin() { return $GLOBALS['admin_on']; }
function current_user_can($cap) { return !empty($GLOBALS['caps'][$cap]); }
function sanitize_text_field($v) { return (string)$v; }
function wp_unslash($v) { return $v; }
function wp_verify_nonce($n,$action) { return $GLOBALS['nonce_ok'] && $n === 'fake-secure-token' && $action === 'yby_mail_v2_p6_dev_preview'; }
function wp_nonce_url($url,$action) { return $url . '&_wpnonce=fake-secure-token'; }
function esc_html($v) { return htmlspecialchars((string)$v, ENT_QUOTES,'UTF-8'); }
function esc_html__($v,$domain='') { return esc_html($v); }
function esc_url($v) { return esc_html($v); }
function wp_die($message) { throw new RuntimeException('DENIED'); }
class YBY_Three_OS_Admin {
 const EMAIL='andy-core-email';const MAIL_VIEW='andy_core_mail_view';
 public static function url($p,$tab) { return 'https://dev.beyourlover.com/wp-admin/admin.php?page='.$p.'&tab='.$tab; }
}
require dirname(__DIR__).'/admin/class-yby-mail-v2-p6-dev-preview.php';
$n=0;function checkp6($ok,$msg){global $n;++$n;if(!$ok)throw new RuntimeException('P6_DEV_PREVIEW_FAIL:'.$msg);}
checkp6(YBY_Mail_V2_P6_Dev_Preview::eligible_site() === extension_loaded('sqlite3'),'only supported Dev host');
checkp6(str_contains(YBY_Mail_V2_P6_Dev_Preview::url('preview_queue'),'_wpnonce='),'queue nonce URL');
checkp6(str_contains(YBY_Mail_V2_P6_Dev_Preview::url('preview_failures'),'_wpnonce='),'failures nonce URL');
checkp6(YBY_Mail_V2_P6_Dev_Preview::url('send')==='','send URL disallowed');
$source=file_get_contents(dirname(__DIR__).'/admin/class-yby-mail-v2-p6-dev-preview.php');
checkp6(str_contains($source,"SQLITE3_OPEN_READONLY"),'DB filesystem opened read only');
checkp6(str_contains($source,"'manage_options'")&&str_contains($source,"'andy_core_mail_view'"),'double privilege gate');
checkp6(str_contains($source,"wp_verify_nonce"),'nonce required');
checkp6(str_contains($source,"'dev.beyourlover.com'"),'hard site lock');
checkp6(str_contains($source,'MAX_FIXTURES = 100'),'100 synthetic max');
checkp6(str_contains($source,'realpath(')&&str_contains($source,'is_link('),'private file symlink guard');
checkp6(str_contains($source,'PRAGMA table_info(outbox)')&&str_contains($source,'PRAGMA index_list(outbox)'),'schema+unique index verification');
checkp6(str_contains($source,'DEV 隔离测试数据'),'always label synthetic');
checkp6(!preg_match('/\b(?:wp_mail|add_action|add_filter|as_enqueue_async_action|dbDelta|update_option|delete_option|mysqli_query|wp_schedule_event)\s*\(/i',$source),'no write/runtime hooks');
$menu=file_get_contents(dirname(__DIR__).'/admin/class-yby-three-os-admin.php');
checkp6(str_contains($menu,"'preview_queue'")&&str_contains($menu,"'preview_failures'"),'both tabs integrated');
checkp6(str_contains($menu,'YBY_Mail_V2_P6_Dev_Preview::render'),'controller uses protected preview');
$core=file_get_contents(dirname(__DIR__).'/inc/class-yby-core.php');
checkp6(str_contains($core,'admin/class-yby-mail-v2-p6-dev-preview.php'),'backend file loaded');
$GLOBALS['dev_host']='www.beyourlover.com';
checkp6(!YBY_Mail_V2_P6_Dev_Preview::eligible_site(),'WWW hard denied');
$GLOBALS['dev_host']='dev.beyourlover.com';
$GLOBALS['admin_on']=false;
checkp6(!YBY_Mail_V2_P6_Dev_Preview::eligible_site(),'frontend hard denied');
$GLOBALS['admin_on']=true;
$_GET=array('_wpnonce'=>'fake-secure-token');
$GLOBALS['caps']['manage_options']=false;
try{YBY_Mail_V2_P6_Dev_Preview::render('preview_queue');checkp6(false,'privileged view denied');}
catch(RuntimeException $e){checkp6($e->getMessage()==='DENIED','nonadmin denied');}
$GLOBALS['caps']['manage_options']=true;
$GLOBALS['nonce_ok']=false;
try{YBY_Mail_V2_P6_Dev_Preview::render('preview_failures');checkp6(false,'bad nonce denied');}
catch(RuntimeException $e){checkp6($e->getMessage()==='DENIED','nonce fail closed');}
echo 'MAIL_P6_DEV_PREVIEW_PASS assertions='.$n."\n";
