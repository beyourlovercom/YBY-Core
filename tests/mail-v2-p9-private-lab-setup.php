<?php
/** P9 CLI-only setup of a brand-new isolated synthetic MySQL8 laboratory. */
if(PHP_SAPI!=='cli' || getenv('MAIL_P9_LAB_SETUP')!=='EXPLICIT_NEW_PRIVATE_P9_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$socket=$base.'/mysql.sock';
if(realpath($base)!==$base || is_link($base) || !is_dir($base.'/data/mysql') ||
    !file_exists($socket) || is_link($socket) ||
    !extension_loaded('pdo_mysql') ||
    file_exists($base.'/artifacts/p9-private-creds.php')) {exit(82);}
$root=new PDO('mysql:unix_socket='.$socket.';charset=ascii','root','',
    array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3));
$meta=$root->query('SELECT @@skip_networking AS isolated,@@version AS version,@@datadir AS datadir')->fetch(PDO::FETCH_ASSOC);
if((int)$meta['isolated']!==1||strpos($meta['version'],'8.4.')!==0 ||
    realpath(rtrim($meta['datadir'],'/'))!==$base.'/data' ||
    $root->query('SELECT CURRENT_USER()')->fetchColumn()!=='root@localhost'){
    throw new RuntimeException('P9_SEPARATE_MYSQL_SOCKET_GATE');
}
$db='p9_mail_synthetic_uat';
$c=$root->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
$c->execute(array($db));
if((int)$c->fetchColumn()!==0)throw new RuntimeException('P9_DATABASE_COLLISION');
$ddl=YBY_Mail_V2_P6_Outbox_Plan::ddl_proposal('p9_');
if($ddl['status']!=='review_only' || $ddl['table']!=='p9_yby_mail_v2_outbox' ||
   count($ddl['columns'])!==20 || count($ddl['indexes'])!==4){
    throw new RuntimeException('P9_P6_DDL_MANIFEST_MISMATCH');
}
$root->exec('CREATE DATABASE p9_mail_synthetic_uat CHARACTER SET ascii COLLATE ascii_bin');
$root->exec('USE p9_mail_synthetic_uat');
$root->exec($ddl['sql']);
$writer=bin2hex(random_bytes(32));
$reader=bin2hex(random_bytes(32));
foreach(array('p9_writer'=>$writer,'p9_reader'=>$reader) as $name=>$password){
    // Both identifiers are fixed by the source; random hex is safe in SQL literal.
    $root->exec("CREATE USER '".$name."'@'localhost' IDENTIFIED BY '".$password."'");
}
$root->exec('GRANT SELECT, INSERT, UPDATE ON p9_mail_synthetic_uat.* TO '.
    "'p9_writer'@'localhost'");
$root->exec('GRANT SELECT ON p9_mail_synthetic_uat.* TO '.
    "'p9_reader'@'localhost'");
$records=array(
    'database'=>$db,'socket'=>$socket,
    'writer_user'=>'p9_writer','writer_password'=>$writer,
    'reader_user'=>'p9_reader','reader_password'=>$reader
);
$path=$base.'/artifacts/p9-private-creds.php';
$old=umask(0077);
try{
    $fp=fopen($path,'x');
    if(!$fp)throw new RuntimeException('P9_CREDENTIAL_FILE_COLLISION');
    $body="<?php\nreturn ".var_export($records,true).";\n";
    $length=fwrite($fp,$body);
    fflush($fp);fclose($fp);
    if($length!==strlen($body)||!chmod($path,0600)){
        @unlink($path);throw new RuntimeException('P9_CREDENTIAL_FILE_INCOMPLETE');
    }
} finally {umask($old);}
foreach(array('p9_writer','p9_reader') as $identity){
    $p=new PDO('mysql:unix_socket='.$socket.';dbname='.$db.';charset=ascii',
        $identity,$records[$identity==='p9_writer'?'writer_password':'reader_password'],
        array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
    if($p->query('SELECT DATABASE()')->fetchColumn()!==$db){
        throw new RuntimeException('P9_ROLE_CONNECTION_FAIL');
    }
}
echo "P9_SEPARATE_MYSQL84_DB_PASS\n";
echo "P9_DISTINCT_WRITER_AND_READER_ROLES_PASS\n";
echo "P9_P6_20COL_4INDEX_SCHEMA_PASS\n";
echo "P9_PRIVATE_CREDENTIALS_0600_NO_SECRET_OUTPUT_PASS\n";
echo "P9_NO_SMTP_NO_WP_NO_P8_CHANGES\n";
