<?php
/**
 * P9-6 real 102-row synthetic MySQL 8.4 read-only bridge integration proof.
 * No WordPress boot, no REST, no SQL mutation, SMTP or worker. Dedicated P9 lab.
 */
if (PHP_SAPI!=='cli'||
    getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-integration-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-grant-preflight.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$secrets=$base.'/artifacts/p9-private-creds.php';
if(realpath($base)!==$base||is_link($secrets)||!is_file($secrets)||
   (fileperms($secrets)&0077)!==0){exit(82);}
$c=require $secrets;
if(!is_array($c)||$c['database']!=='p9_mail_synthetic_uat'||
   $c['socket']!==$base.'/mysql.sock' ||
   $c['reader_user']!=='p9_reader' ||!file_exists($c['socket'])||
   is_link($c['socket'])){exit(83);}
$heads=array('p9'=>getenv('MAIL_P9_EXPECTED_P9_HEAD'),
    'p8'=>getenv('MAIL_P9_EXPECTED_P8_HEAD'),
    'v22'=>getenv('MAIL_P9_EXPECTED_V22_HEAD'));
foreach($heads as $sha){
    if(!is_string($sha)||!preg_match('/\\A[a-f0-9]{40}\\z/D',$sha)){
        throw new RuntimeException('P9_HEAD_NOT_INDEPENDENTLY_VERIFIED');
    }
}
$pdo=new PDO('mysql:unix_socket='.$c['socket'].
    ';dbname=p9_mail_synthetic_uat;charset=ascii',
    $c['reader_user'],$c['reader_password'],array(
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3));
if($pdo->query('SELECT DATABASE()')->fetchColumn()!=='p9_mail_synthetic_uat'||
   $pdo->query('SELECT CURRENT_USER()')->fetchColumn()!=='p9_reader@localhost'||
   (int)$pdo->query('SELECT @@skip_networking')->fetchColumn()!==1){
    throw new RuntimeException('P9_READONLY_IDENTITY_OR_NETWORK_DRIFT');
}
$grants=$pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
$exact=array('GRANT USAGE ON *.* TO `p9_reader`@`localhost`',
    'GRANT SELECT ON `p9_mail_synthetic_uat`.* TO `p9_reader`@`localhost`');
sort($grants);sort($exact);
if($grants!==$exact){throw new RuntimeException('P9_READONLY_GRANT_DRIFT');}
$rows=$pdo->query('SELECT state,diagnostic_code,COUNT(*) AS n FROM '.
    'p9_yby_mail_v2_outbox GROUP BY state,diagnostic_code');
$states=array_fill_keys(YBY_Mail_V2_P9_Integration_Plan::STATES,0);
$physical=0;$exhausted=0;$uncertain=0;
while($r=$rows->fetch(PDO::FETCH_ASSOC)){
    $state=$r['state'];$diag=$r['diagnostic_code'];$count=(int)$r['n'];
    if(!array_key_exists($state,$states)||$count<1||
       !is_string($diag)||!in_array($diag,array(
            'none','acceptance_unknown','preacceptance_reject','retry_exhausted'
        ),true))throw new RuntimeException('P9_UNEXPECTED_STATE_OR_DIAGNOSTIC');
    if($state==='deferred' && $diag==='retry_exhausted'){
        $exhausted+=$count;
    }elseif($state==='uncertain'&&$diag==='acceptance_unknown'){
        $uncertain+=$count;
    }elseif($diag!=='none'&& !($state==='deferred'&&
        $diag==='preacceptance_reject')){
        throw new RuntimeException('P9_UNEXPECTED_STATE_DIAGNOSTIC_PAIR');
    }
    $states[$state]+=$count;$physical+=$count;
}
if($physical!==102||$states['queued']!==98||
   $states['leased']!==1||$states['uncertain']!==2||
   $states['deferred']!==1||$uncertain!==2||$exhausted!==1){
    throw new RuntimeException('P9_102_READONLY_SNAPSHOT_DRIFT');
}
$states['retry_exhausted']=$exhausted; // subset, not additional rows.
$health=array('status'=>'synthetic_snapshot','total'=>$physical,
    'states'=>$states,'contains_pii'=>false,
    'event_log_verified'=>false,'mail_sent'=>false);
$gate=array('synthetic_only'=>true,'owner_deploy_approved'=>false,
    'smtp_enabled'=>false,'worker_enabled'=>false);
$r=YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$gate);
if($r['status']!=='source_only_plan'||$r['total']!==102||
   $r['queued']!==98||$r['uncertain_hold']!==2||
   $r['retry_exhausted_hold']!==1||$r['can_deploy']!==false||
   $r['can_write']!==false||$r['can_send']!==false||
   $r['event_history_available']!==false||$r['contains_pii']!==false||
   $r['ui_owner']!=='v22_plus_p8_existing_email_tabs'){
   throw new RuntimeException('P9_BRIDGE_CONTRACT_FAILED');
}
echo 'MAIL_P9_102_REAL_MYSQL_READONLY_BRIDGE_PASS rows=102'.
     ' queued=98 leased=1 uncertain=2 exhausted=1'.
     ' user=p9_reader mysql_network=off dev=off SMTP=off worker=off'.PHP_EOL;
