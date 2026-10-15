<?php
/** P7 MySQL-origin snapshot Dev-only integration contract (offline/no writes). */
define('ABSPATH', __DIR__);
$GLOBALS['host']='dev.beyourlover.com';
$GLOBALS['admin_on']=true;
$GLOBALS['grants']=array('manage_options'=>true,'andy_core_mail_view'=>true);
$GLOBALS['nonce_good']=true;
function site_url(){return 'https://'.$GLOBALS['host'];}
function is_admin(){return $GLOBALS['admin_on'];}
function current_user_can($v){return !empty($GLOBALS['grants'][$v]);}
function wp_verify_nonce($v,$action){return $GLOBALS['nonce_good']&&$action==='yby_mail_v2_p6_dev_preview'&&$v==='synthetic_nonce';}
function wp_nonce_url($v,$action){return $v.'&_wpnonce=synthetic_nonce';}
function wp_die($text){throw new RuntimeException('DENIED');}
function esc_html__($v,$domain=''){return $v;}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function esc_url($v){return esc_html($v);}
function wp_unslash($v){return $v;}
function sanitize_text_field($v){return (string)$v;}
function sanitize_key($v){return (string)$v;}
class YBY_Three_OS_Admin {
 const EMAIL='andy-core-email';
 public static function url($p,$tab){return 'https://dev.beyourlover.com/wp-admin/admin.php?page='.$p.'&tab='.$tab;}
}
require dirname(__DIR__).'/admin/class-yby-mail-v2-p6-dev-preview.php';
$n=0;function okp7($x,$why){global $n;++$n;if(!$x)throw new RuntimeException('P7_DEV_FAIL:'.$why);}
okp7(YBY_Mail_V2_P6_Dev_Preview::eligible_site()===extension_loaded('sqlite3'),'Dev-only');
okp7(str_contains(YBY_Mail_V2_P6_Dev_Preview::url('mysql_snapshot'),'mysql_snapshot'),'explicit snapshot URL');
okp7(str_contains(YBY_Mail_V2_P6_Dev_Preview::url('mysql_snapshot'),'_wpnonce='),'nonce-bearing URL');
okp7(YBY_Mail_V2_P6_Dev_Preview::url('retry')==='','no retry URL');
okp7(YBY_Mail_V2_P6_Dev_Preview::MYSQL_SNAPSHOT_SHA256==='aa10d6ee5a12ecfdeb6ac580f464a6fe75acea1e89ce217464ba7ed7f15e04c4','source pinned SHA');
$source=file_get_contents(dirname(__DIR__).'/admin/class-yby-mail-v2-p6-dev-preview.php');
okp7(str_contains($source,'P7 MySQL 8.4 隔离验证快照'),'source origin transparent');
okp7(str_contains($source,'非实时、非正式 Outbox'),'never call snapshot real queue');
okp7(str_contains($source,'hash_equals')&&str_contains($source,"hash_file( 'sha256'"),'whole-file integrity');
okp7(str_contains($source,'is_link')&&str_contains($source,'realpath'),'symlink deny');
okp7(str_contains($source,'snapshot_not_live')&&str_contains($source,'mysql_network_disabled'),'snapshot provenance checks');
okp7(str_contains($source,'count( $data['),'structural validation');
okp7(!preg_match('/\b(?:wp_mail|add_action|add_filter|as_enqueue_async_action|as_schedule_single_action|dbDelta|update_option|delete_option|mysqli_query|wp_schedule_event)\s*\(/i',$source),'no email, no DB mutations');
$menu=file_get_contents(dirname(__DIR__).'/admin/class-yby-three-os-admin.php');
okp7(substr_count($menu,"$"."tabs['mysql_snapshot']")===2,'only Dev conditional menu registrations');
okp7(str_contains($menu,'YBY_Mail_V2_P6_Dev_Preview::render'),'P6 controller handles P7');
$GLOBALS['host']='www.beyourlover.com';
okp7(!YBY_Mail_V2_P6_Dev_Preview::eligible_site(),'WWW excluded');
$GLOBALS['host']='dev.beyourlover.com';$_GET=array('_wpnonce'=>'synthetic_nonce');
$GLOBALS['grants']['manage_options']=false;
try{YBY_Mail_V2_P6_Dev_Preview::render('mysql_snapshot');okp7(false,'non-admin allowed');}
catch(RuntimeException $e){okp7($e->getMessage()==='DENIED','admin gate');}
$GLOBALS['grants']['manage_options']=true;$GLOBALS['nonce_good']=false;
try{YBY_Mail_V2_P6_Dev_Preview::render('mysql_snapshot');okp7(false,'invalid nonce allowed');}
catch(RuntimeException $e){okp7($e->getMessage()==='DENIED','nonce gate');}
echo 'MAIL_P7_DEV_SNAPSHOT_PASS assertions='.$n."\n";
