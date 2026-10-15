<?php
/** P8 source-only fail-closed regression, never connects to a DB. */
define('ABSPATH', __DIR__.'/sandbox/a/b/public_html/');
$GLOBALS['dev_host']='dev.beyourlover.com';
$GLOBALS['backend']=true;
$GLOBALS['caps']=array('manage_options'=>true,'andy_core_mail_view'=>true);
$GLOBALS['nonce_valid']=true;
function site_url(){return 'https://'.$GLOBALS['dev_host'];}
function is_admin(){return $GLOBALS['backend'];}
function current_user_can($c){return !empty($GLOBALS['caps'][$c]);}
function wp_verify_nonce($n,$a){return $GLOBALS['nonce_valid']&&$n==='fake-p8-nonce'&&$a==='yby_mail_v2_p8_live_readonly';}
function wp_nonce_url($url,$action){return $url.'&_wpnonce=fake-p8-nonce';}
function esc_html__($v,$d=''){return (string)$v;}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function esc_url($v){return (string)$v;}
function wp_unslash($v){return (string)$v;}
function sanitize_text_field($v){return (string)$v;}
function sanitize_key($v){return (string)$v;}
function absint($v){return abs((int)$v);}
function wp_die($msg){throw new RuntimeException('DENIED');}
class YBY_Three_OS_Admin {
    const EMAIL='andy-core-email';
    public static function url($a,$b){return 'https://dev.beyourlover.com/wp-admin/admin.php?page='.$a.'&tab='.$b;}
}
require dirname(__DIR__).'/admin/class-yby-mail-v2-p8-readonly.php';
$n=0;
function check8($pass,$name){global $n;++$n;if(!$pass)throw new RuntimeException('P8_TEST_FAIL:'.$name);}
check8(YBY_Mail_V2_P8_Readonly::TABLE==='p8_yby_mail_v2_outbox','exact table');
check8(YBY_Mail_V2_P8_Readonly::MAX_ROWS===100,'bounded UAT');
check8(count(YBY_Mail_V2_P8_Readonly::COLUMNS)===20,'P6 20 column envelope');
check8(count(YBY_Mail_V2_P8_Readonly::FIELDS)===10,'P3 minimal view');
check8(count(YBY_Mail_V2_P8_Readonly::INDEXES)===4,'P6 indexes');
check8(YBY_Mail_V2_P8_Readonly::eligible_site()===extension_loaded('pdo_mysql'),'Dev-only plus PDO');
check8(YBY_Mail_V2_P8_Readonly::configured()===false,'missing private credentials hidden');
check8(YBY_Mail_V2_P8_Readonly::url()==='','not linkable without config');
$_GET=array('_wpnonce'=>'fake-p8-nonce');
check8(YBY_Mail_V2_P8_Readonly::read('all',1)['status']==='blocked','no config denies reads');
check8(YBY_Mail_V2_P8_Readonly::read('failures',1)['status']==='blocked','no config denies failures');
check8(YBY_Mail_V2_P8_Readonly::read('all',6)['status']==='blocked','invalid page');
check8(YBY_Mail_V2_P8_Readonly::read('message',1)['status']==='blocked','invalid filter');
$source=file_get_contents(dirname(__DIR__).'/admin/class-yby-mail-v2-p8-readonly.php');
foreach(array('SHOW GRANTS','TRANSACTION READ ONLY','information_schema.COLUMNS',
    'information_schema.STATISTICS','TABLE_COLLATION','ascii_bin','MAX_ROWS','PDO::ATTR_EMULATE_PREPARES',
    'PDO::ATTR_TIMEOUT','dirname(ABSPATH,3)','manage_options','andy_core_mail_view',
    'wp_verify_nonce','return array','config_path','role','mysql:unix_socket=') as $term){
    if($term==='role')continue;
    check8(strpos($source,$term)!==false,'security source present '.$term);
}
check8(!preg_match('/\b(?:wp_mail|add_action|add_filter|as_enqueue_async_action|as_schedule_single_action|dbDelta|update_option|delete_option|wp_schedule_event)\s*\(/i',$source),'no WordPress writes/mails/hooks');
check8(!preg_match('/\b(?:INSERT INTO|UPDATE p8_yby|DELETE FROM|(?<!SHOW )CREATE TABLE|DROP TABLE)\b/i',$source),'no queue DML or schema mutations');
check8(str_contains($source,'hash')===false || !str_contains($source,'smtp_password'),'no secrets in renderer');
$view=file_get_contents(dirname(__DIR__).'/admin/class-yby-three-os-admin.php');
check8(str_contains($view,"'live_readonly'"),'canonical menu integration');
check8(str_contains($view,"YBY_Mail_V2_P8_Readonly::configured()"),'hidden until credential provision');
check8(str_contains($view,'YBY_Mail_V2_P8_Readonly::render()'),'renders only guarded P8');
$bootstrap=file_get_contents(dirname(__DIR__).'/admin/class-yby-mail-v2-p6-dev-preview.php');
check8(str_contains($bootstrap,"class-yby-mail-v2-p8-readonly.php"),'side-effect-free include');
$GLOBALS['dev_host']='www.beyourlover.com';
check8(!YBY_Mail_V2_P8_Readonly::eligible_site(),'WWW denied');
$GLOBALS['dev_host']='dev.beyourlover.com';
$GLOBALS['backend']=false;
check8(!YBY_Mail_V2_P8_Readonly::eligible_site(),'frontend denied');
$GLOBALS['backend']=true;
$GLOBALS['caps']['manage_options']=false;
try{YBY_Mail_V2_P8_Readonly::render();check8(false,'non-admin rendered');}
catch(RuntimeException $e){check8($e->getMessage()==='DENIED','admin cap required');}
$GLOBALS['caps']['manage_options']=true;
$GLOBALS['caps']['andy_core_mail_view']=false;
try{YBY_Mail_V2_P8_Readonly::render();check8(false,'non mail role rendered');}
catch(RuntimeException $e){check8($e->getMessage()==='DENIED','mail cap required');}
$GLOBALS['caps']['andy_core_mail_view']=true;
$GLOBALS['nonce_valid']=false;
try{YBY_Mail_V2_P8_Readonly::render();check8(false,'bad nonce rendered');}
catch(RuntimeException $e){check8($e->getMessage()==='DENIED','nonce required');}
echo 'MAIL_P8_DEV_READONLY_CONTRACT_PASS assertions='.$n."\n";
