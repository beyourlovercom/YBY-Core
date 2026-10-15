<?php
/**
 * P9 actual two-PHP-process CAS claim race on independent synthetic MySQL.
 * Fork children exec a *fresh PHP process*; no inherited PDO descriptors.
 * Only an opaque synthetic row moves queued->leased. No sending/worker hooks.
 */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'||
   !function_exists('pcntl_fork')||!function_exists('pcntl_exec')){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php';
$root=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$creds=$root.'/artifacts/p9-private-creds.php';
if(!is_file($creds)||is_link($creds)||(fileperms($creds)&0077)!==0){exit(82);}
$c=require $creds;
if($c['database']!=='p9_mail_synthetic_uat'||
   $c['socket']!==$root.'/mysql.sock'){exit(83);}
$dir=$root.'/artifacts/concurrency-p9-v1';
$id=hash('sha256','p9-lab-opaque-1');
$child=($argv[1]??'')==='--claim-child';
if($child){
    $slot=$argv[2]??'';
    if(!in_array($slot,array('1','2'),true)||!is_dir($dir)||!is_file($dir.'/go')&&
       !is_file($dir.'/ready-1')){exit(84);}
    $ready=$dir.'/ready-'.$slot;
    if(file_exists($ready)||file_exists($dir.'/result-'.$slot)){exit(85);}
    file_put_contents($ready,'ready',LOCK_EX);
    $go=false;
    for($i=0;$i<80;$i++){if(is_file($dir.'/go')){$go=true;break;}usleep(50000);}
    if(!$go){exit(86);}
    $pdo=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii',
        $c['writer_user'],$c['writer_password'],
        array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
    $writer=YBY_Mail_V2_P9_Lab_Writer::open($pdo);
    if(!$writer){exit(87);}
    $response=$writer->claim($id,0,1000,1200,hash('sha256','p9-race-token-'.$slot));
    if(!in_array($response['status'],array('claimed','not_claimed'),true)){exit(88);}
    file_put_contents($dir.'/result-'.$slot,$response['status'],LOCK_EX);
    exit(0);
}
if(file_exists($dir)){throw new RuntimeException('P9_RACE_ALREADY_RAN');}
umask(0077);
if(!mkdir($dir,0700)){throw new RuntimeException('P9_RACE_CREATE_DIR');}
$children=array();
for($slot=1;$slot<=2;$slot++){
    $pid=pcntl_fork();
    if($pid===-1)throw new RuntimeException('P9_FORK_FAILED');
    if($pid===0){
        pcntl_exec(PHP_BINARY,array(__FILE__,'--claim-child',(string)$slot));
        exit(89);
    }
    $children[]=$pid;
}
$both=false;
for($i=0;$i<80;$i++){
    if(is_file($dir.'/ready-1')&&is_file($dir.'/ready-2')){$both=true;break;}
    usleep(50000);
}
if(!$both)throw new RuntimeException('P9_RACE_CHILD_NOT_READY');
file_put_contents($dir.'/go','start',LOCK_EX);
foreach($children as $pid){
    pcntl_waitpid($pid,$status);
    if(!pcntl_wifexited($status)||pcntl_wexitstatus($status)!==0){
        throw new RuntimeException('P9_RACE_CHILD_CRASH');
    }
}
$results=array();
foreach(array(1,2) as $slot){
    $path=$dir.'/result-'.$slot;
    if(!is_file($path))throw new RuntimeException('P9_RACE_RESULT_MISSING');
    $results[]=file_get_contents($path);
}
sort($results);
if($results!==array('claimed','not_claimed'))throw new RuntimeException('P9_MULTIPLE_WINNERS_OR_NO_CLAIM');
$reader=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii',
    $c['reader_user'],$c['reader_password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$stmt=$reader->prepare('SELECT state,state_version FROM p9_yby_mail_v2_outbox WHERE opaque_id=?');
$stmt->execute(array($id));
$row=$stmt->fetch(PDO::FETCH_ASSOC);
if($row['state']!=='leased'||(int)$row['state_version']!==1){
    throw new RuntimeException('P9_RACE_DATABASE_VERSION_MISMATCH');
}
if((int)$reader->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn()!==100){
    throw new RuntimeException('P9_RACE_DATASET_MUTATED');
}
echo "MAIL_P9_REAL_MYSQL_TWO_PROCESS_RACE_PASS winners=1 losers=1 rows=100\n";
echo "MAIL_P9_NO_INHERITED_PDO_SOCKETS_NO_SMTP_NO_WP\n";
