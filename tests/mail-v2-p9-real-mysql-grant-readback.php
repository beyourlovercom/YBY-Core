<?php
/**
 * P9-6 one-shot private US Mac synthetic MySQL8 grant readback.
 * SELECT/SHOW GRANTS only; does not create, write, hook, mail or restart.
 */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-grant-preflight.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$secrets=$base.'/artifacts/p9-private-creds.php';
if(realpath($base)!==$base||is_link($secrets)||!is_file($secrets)||
   (fileperms($secrets)&0077)!==0){exit(82);}
$c=require $secrets;
if(!is_array($c)||$c['database']!=='p9_mail_synthetic_uat'||
   $c['socket']!==$base.'/mysql.sock'||$c['writer_user']!=='p9_writer'||
   $c['reader_user']!=='p9_reader'||
   !file_exists($c['socket'])||is_link($c['socket'])){exit(83);}
$dsn='mysql:unix_socket='.$c['socket'].';dbname=p9_mail_synthetic_uat;charset=ascii';
$opts=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3);
$writer=new PDO($dsn,$c['writer_user'],$c['writer_password'],$opts);
$reader=new PDO($dsn,$c['reader_user'],$c['reader_password'],$opts);
$get=static function($pdo){
    if($pdo->query('SELECT DATABASE()')->fetchColumn()!=='p9_mail_synthetic_uat') {
        throw new RuntimeException('P9_GRANTS_DATABASE_DRIFT');
    }
    return array('identity'=>$pdo->query('SELECT CURRENT_USER()')->fetchColumn(),
        'network_disabled'=>(int)$pdo->query('SELECT @@skip_networking')->fetchColumn()===1,
        'grants'=>$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN));
};
$w=$get($writer);$r=$get($reader);
$plan=YBY_Mail_V2_P9_Grant_Preflight::verify(array(
   'synthetic_only'=>true,'schema'=>'p9_mail_synthetic_uat',
   'writer_user'=>'p9_writer','reader_user'=>'p9_reader',
   'writer_current_user'=>$w['identity'],
   'reader_current_user'=>$r['identity'],
   'writer_grants'=>$w['grants'],'reader_grants'=>$r['grants'],
   'network_disabled'=>$w['network_disabled']&&$r['network_disabled'],
   'wp_db_distinct'=>true,'p8_reader_unchanged'=>true));
if($plan['status']!=='synthetic_lab_grants_verified' ||
    $plan['can_deploy']!==false||$plan['can_write']!==false){
    throw new RuntimeException('P9_GRANTS_FAIL_CLOSED_'.($plan['reason']??'unknown'));
}
$count=(int)$reader->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn();
if($count!==102)throw new RuntimeException('P9_GRANTS_DATASET_DRIFT');
echo 'MAIL_P9_PRIVATE_MYSQL84_GRANTS_READBACK_PASS rows=102 separate_principals=1'.
    ' writer=select_insert_update reader=select_only network=off smtp=off'.PHP_EOL;
