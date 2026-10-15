<?php
/** Offline P8 credential setup, MySQL/MariaDB grant and exact schema checks. */
define('ABSPATH', __DIR__.'/p8-unmounted/wp/public_html/');
define('DB_NAME','wp_real_business_db');
$GLOBALS['host']='dev.beyourlover.com';$GLOBALS['admin_on']=true;
$GLOBALS['caps']=array('manage_options'=>true,'andy_core_mail_view'=>true);
$GLOBALS['hooked']=array();
function site_url(){return 'https://'.$GLOBALS['host'];}
function is_admin(){return $GLOBALS['admin_on'];}
function current_user_can($v){return !empty($GLOBALS['caps'][$v]);}
function add_action($n,$c){$GLOBALS['hooked'][]=$n;}
function esc_html__($v,$d=''){return $v;}
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function esc_attr($v){return esc_html($v);}
function esc_url($v){return esc_html($v);}
function admin_url($v){return 'https://dev.beyourlover.com/wp-admin/'.$v;}
function wp_nonce_field($v){echo 'nonce';}
function submit_button($v){echo '<button>'.$v.'</button>';}
function wp_die($v){throw new RuntimeException('DENIED');}
function is_ssl(){return false;}
function check_admin_referer($v){throw new RuntimeException('SHOULD_NOT_REACH');}
class YBY_Three_OS_Admin {
 const EMAIL='andy-core-email';
 public static function url($p,$t){return 'https://dev.beyourlover.com/wp-admin/admin.php?page='.$p.'&tab='.$t;}
}
require dirname(__DIR__).'/admin/class-yby-mail-v2-p8-readonly.php';
require dirname(__DIR__).'/admin/class-yby-mail-v2-p8-setup.php';
$n=0;
function ck($x,$v){global $n;++$n;if(!$x)throw new RuntimeException('P8_SETUP_FAIL:'.$v);}
ck(YBY_Mail_V2_P8_Setup::available()===extension_loaded('pdo_mysql'),'Dev admin access');
ck(in_array('admin_post_yby_mail_v2_p8_save_config',$GLOBALS['hooked'],true),'private admin POST registered');
ob_start();YBY_Mail_V2_P8_Setup::render();$html=ob_get_clean();
ck(strpos($html,'type="password"')!==false,'password hidden');
ck(strpos($html,'p8_dbname')!==false&&strpos($html,'p8_user')!==false,'db/user editable');
ck(strpos($html,'admin-post.php')!==false&&strpos($html,'nonce')!==false,'POST with nonce');
ck(strpos($html,'WordPress 业务数据库')!==false,'isolation warning');
$source=file_get_contents(dirname(__DIR__).'/admin/class-yby-mail-v2-p8-setup.php');
ck(strpos($source,"fileperms(")!==false&&strpos($source,"umask(")!==false,'0600 private secret file');
ck(strpos($source,"@fopen(")!==false&&strpos($source,"'x'")!==false,'exclusive one-time file');
ck(strpos($source,"is_ssl()")!==false&&strpos($source,"check_admin_referer")!==false,'TLS and CSRF required');
ck(!preg_match('/\b(?:wp_mail|update_option|add_option|dbDelta|as_enqueue_async_action)\s*\(/i',$source),'no sends/WP business writes');
$_SERVER['REQUEST_METHOD']='POST';
try{YBY_Mail_V2_P8_Setup::save();ck(false,'non TLS allowed');}
catch(RuntimeException $e){ck($e->getMessage()==='DENIED','non TLS blocked');}
$GLOBALS['host']='www.beyourlover.com';
ck(!YBY_Mail_V2_P8_Setup::available(),'WWW denied');
$GLOBALS['host']='dev.beyourlover.com';$GLOBALS['caps']['manage_options']=false;
ck(!YBY_Mail_V2_P8_Setup::available(),'admin required');
$GLOBALS['caps']['manage_options']=true;$GLOBALS['caps']['andy_core_mail_view']=false;
ck(!YBY_Mail_V2_P8_Setup::available(),'mail capability required');
$GLOBALS['caps']['andy_core_mail_view']=true;
class P8FakeResult {
 private $rows;
 public function __construct($r){$this->rows=$r;}
 public function fetchColumn(){return $this->rows[0][0]??null;}
 public function fetchAll($mode=0){return $this->rows;}
 public function fetch($mode=0){return $this->rows[0]??false;}
}
class P8FakeStmt {
 private $kind;private $parent;
 public function __construct($kind,$p){$this->kind=$kind;$this->parent=$p;}
 public function execute($a){}
 public function fetchColumn(){return $this->parent->table_count;}
 public function fetch($m=0){
  if($this->kind==='table')return array('ENGINE'=>'InnoDB','TABLE_COLLATION'=>'ascii_bin');
  return false;
 }
 public function fetchAll($m=0){
  if($this->kind==='cols')return $this->parent->columns;
  if($this->kind==='indexes')return $this->parent->indexes;
  return array();
 }
}
class P8FakeDB {
 public $grants=array();public $table_count=0;
 public $columns=array();public $indexes=array();public $dbname='p8_test';
 public function query($sql){
  if($sql==='SHOW GRANTS')return new P8FakeResult(array_map(static function($x){return array($x);},$this->grants));
  if($sql==='SELECT DATABASE()')return new P8FakeResult(array(array($this->dbname)));
  return new P8FakeResult(array());
 }
 public function prepare($sql){
  if(strpos($sql,'information_schema.TABLES')!==false)return new P8FakeStmt('table',$this);
  if(strpos($sql,'information_schema.COLUMNS')!==false)return new P8FakeStmt('cols',$this);
  if(strpos($sql,'information_schema.STATISTICS')!==false)return new P8FakeStmt('indexes',$this);
  return new P8FakeStmt('other',$this);
 }
}
function invokePrivate($class,$name,$args){
 $method=new ReflectionMethod($class,$name);
 $method->setAccessible(true);
 return $method->invokeArgs(null,$args);
}
$d=new P8FakeDB();
$d->grants=array("GRANT USAGE ON *.* TO 'p8user'@'localhost'",
                 "GRANT ALL PRIVILEGES ON p8_test.* TO 'p8user'@'localhost'");
ck(invokePrivate('YBY_Mail_V2_P8_Setup','inspect',array($d,'p8_test'))===true,'scoped empty new DB');
$d->table_count=1;
ck(invokePrivate('YBY_Mail_V2_P8_Setup','inspect',array($d,'p8_test'))===false,'nonempty DB denied');
$d->table_count=0;$d->grants[]="GRANT SELECT ON *.* TO 'p8user'@'localhost'";
ck(invokePrivate('YBY_Mail_V2_P8_Setup','inspect',array($d,'p8_test'))===false,'global access denied');
$d->grants=array("GRANT USAGE ON *.* TO 'p8user'@'localhost'",
                 "GRANT SELECT ON p8_test.* TO 'p8user'@'localhost'");
ck(invokePrivate('YBY_Mail_V2_P8_Readonly','safe_grants',array($d,'p8_test'))===true,'SELECT-only grant');
$d->grants[]="GRANT UPDATE ON p8_test.* TO 'p8user'@'localhost'";
ck(invokePrivate('YBY_Mail_V2_P8_Readonly','safe_grants',array($d,'p8_test'))===false,'extra UPDATE denied');
$d->columns=array();
foreach(YBY_Mail_V2_P8_Readonly::COLUMNS as $name=>$def){
 $nullable=strpos($def,' not null')===false;
 $type=substr($def,0,strpos($def,$nullable?' null':' not null'));
 if($type==='tinyint unsigned')$type='tinyint(3) unsigned';
 if($type==='int unsigned')$type='int(10) unsigned';
 if($type==='bigint unsigned')$type='bigint(20) unsigned';
 $d->columns[]=array('COLUMN_NAME'=>$name,'COLUMN_TYPE'=>$type,'IS_NULLABLE'=>$nullable?'YES':'NO');
}
$d->indexes=array();
foreach(YBY_Mail_V2_P8_Readonly::INDEXES as $name=>$cols){
 foreach($cols as $i=>$col){
  $d->indexes[]=array('INDEX_NAME'=>$name,'COLUMN_NAME'=>$col,'NON_UNIQUE'=>in_array($name,array('PRIMARY','uq_idempotency_key'),true)?0:1,'SEQ_IN_INDEX'=>$i+1);
 }
}
ck(invokePrivate('YBY_Mail_V2_P8_Readonly','verify_schema',array($d,'p8_test'))===true,'MariaDB integer widths supported');
$d->columns[0]['COLUMN_TYPE']='varchar(63)';
ck(invokePrivate('YBY_Mail_V2_P8_Readonly','verify_schema',array($d,'p8_test'))===false,'column drift denied');
echo 'MAIL_P8_SETUP_MARIADB_COMPAT_PASS assertions='.$n."\n";
