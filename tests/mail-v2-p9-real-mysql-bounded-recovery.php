<?php
/**
 * P9-5 ONLY: existing private MySQL 8.4 synthetic lab (100-row seeded backup).
 * Durable preacceptance retry, 2 independent process CAS, exhausted HOLD,
 * expired-lease unknown HOLD. Never WP/SMTP, never init or touch P7/P8/WWW.
 * Deliberately one-shot: only when exactly the preserved 100-row baseline exists.
 */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'||
   !function_exists('pcntl_fork')||!function_exists('pcntl_exec')){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$path=$base.'/artifacts/p9-private-creds.php';
if(realpath($base)!==$base||is_link($path)||!is_file($path)||
   (fileperms($path)&0077)!==0){exit(82);}
$c=require $path;
if($c['database']!=='p9_mail_synthetic_uat'||
   $c['socket']!==$base.'/mysql.sock'||$c['writer_user']!=='p9_writer'||
   $c['reader_user']!=='p9_reader'){exit(83);}
$dsn='mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii';
$options=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>3);
$id=hash('sha256','p9-5-real-bounded-exhaustion-20261015');
$expiredId=hash('sha256','p9-5-real-expiry-unknown-20261015');
$child=($argv[1]??'')==='--race-child';
$raceDir=$base.'/artifacts/p9-5-race-proof-20261015';
if($child) {
    $slot=$argv[2]??'';
    $due=(int)($argv[3]??0);
    if(!in_array($slot,array('1','2'),true)||$due<100||
       !is_dir($raceDir)||is_file($raceDir.'/ready-'.$slot)){exit(84);}
    file_put_contents($raceDir.'/ready-'.$slot,'ready',LOCK_EX);
    $go=false;
    for($i=0;$i<100;$i++) {
        if(is_file($raceDir.'/go')){$go=true;break;}
        usleep(50000);
    }
    if(!$go)exit(85);
    // Fresh authenticated process: never inherit parent's PDO socket.
    $pdo=new PDO($dsn,$c['writer_user'],$c['writer_password'],$options);
    $w=YBY_Mail_V2_P9_Lab_Writer::open($pdo);
    if(!$w)exit(86);
    $out=$w->claim($id,2,$due,$due+200,hash('sha256','p9-5-race-token-'.$slot));
    if(!in_array($out['status'],array('claimed','not_claimed'),true))exit(87);
    file_put_contents($raceDir.'/result-'.$slot,$out['status'],LOCK_EX);
    exit(0);
}
$read=new PDO($dsn,$c['reader_user'],$c['reader_password'],$options);
$write=new PDO($dsn,$c['writer_user'],$c['writer_password'],$options);
$writer=YBY_Mail_V2_P9_Lab_Writer::open($write);
if(!$writer)throw new RuntimeException('P9_5_WRITER_GATE');
if((int)$read->query('SELECT @@skip_networking')->fetchColumn()!==1||
   (int)$read->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn()!==100||
   is_dir($raceDir)){throw new RuntimeException('P9_5_BASELINE_OR_ONE_SHOT_GUARD');}
$job=static function($id,$tag){return array('synthetic_only'=>true,'opaque_id'=>$id,
  'idempotency_key'=>hash('sha256',$tag.'-key'),
  'event_hash'=>hash('sha256',$tag.'-event'),
  'channel'=>'critical_system','sender_key'=>'system_tx','next_attempt_at'=>100);
};
if($writer->enqueue($job($id,'p9-5-bounded-real'))['status']!=='inserted'||
   $writer->enqueue($job($expiredId,'p9-5-unknown-real'))['status']!=='inserted'){
    throw new RuntimeException('P9_5_SYNTHETIC_SETUP_FAILED');
}
$lease1=hash('sha256','p9-5-first-token');
if($writer->claim($id,0,100,150,$lease1)['status']!=='claimed')throw new RuntimeException('FIRST_CLAIM');
if($writer->record_mock_nontransmission($id,1,$lease1,110,
    'unknown_delivery')['status']!=='blocked')throw new RuntimeException('UNKNOWN_NOT_HELD');
$r=$writer->record_mock_nontransmission($id,1,$lease1,110,'verified_synthetic_preacceptance_only');
if($r['status']!=='deferred'||$r['attempt']!==1)throw new RuntimeException('RETRY_1');
$due=$r['next_attempt_at'];
if($writer->claim($id,2,$due-1,$due+20,hash('sha256','early'))['status']!=='not_claimed'){
    throw new RuntimeException('COOLDOWN_BYPASSED');
}
umask(0077);
if(!mkdir($raceDir,0700))throw new RuntimeException('RACE_DIR');
$children=array();
for($s=1;$s<=2;$s++) {
    $pid=pcntl_fork();
    if($pid===-1)throw new RuntimeException('FORK');
    if($pid===0){pcntl_exec(PHP_BINARY,array(__FILE__,'--race-child',(string)$s,(string)$due));exit(90);}
    $children[$s]=$pid;
}
$ready=false;
for($i=0;$i<100;$i++){
    if(is_file($raceDir.'/ready-1')&&is_file($raceDir.'/ready-2')){$ready=true;break;}
    usleep(50000);
}
if(!$ready)throw new RuntimeException('RACE_READY_TIMEOUT');
file_put_contents($raceDir.'/go','start',LOCK_EX);
foreach($children as $pid){
    pcntl_waitpid($pid,$status);
    if(!pcntl_wifexited($status)||pcntl_wexitstatus($status)!==0){
        throw new RuntimeException('RACE_CHILD_FAILED');
    }
}
$results=array();$winner=null;
for($s=1;$s<=2;$s++){
    $state=file_get_contents($raceDir.'/result-'.$s);
    $results[]=$state;
    if($state==='claimed')$winner=$s;
}
sort($results);
if($results!==array('claimed','not_claimed'))throw new RuntimeException('RACE_DOUBLE_CLAIM');
$lease2=hash('sha256','p9-5-race-token-'.$winner);
$afterRace=$read->prepare('SELECT state,state_version,attempt,lease_token_hash FROM p9_yby_mail_v2_outbox WHERE opaque_id=?');
$afterRace->execute(array($id));$rec=$afterRace->fetch(PDO::FETCH_ASSOC);
if($rec['state']!=='leased'||(int)$rec['state_version']!==3||
   (int)$rec['attempt']!==1||$rec['lease_token_hash']!==$lease2){
    throw new RuntimeException('RACE_PERSISTENCE');
}
$r=$writer->record_mock_nontransmission($id,3,$lease2,$due+1,'verified_synthetic_preacceptance_only');
if($r['status']!=='deferred'||$r['attempt']!==2)throw new RuntimeException('RETRY_2');
$due2=$r['next_attempt_at'];
$lease3=hash('sha256','p9-5-third');
if($writer->claim($id,4,$due2,$due2+200,$lease3)['status']!=='claimed')throw new RuntimeException('THIRD_CLAIM');
$r=$writer->record_mock_nontransmission($id,5,$lease3,$due2+1,'verified_synthetic_preacceptance_only');
if($r['status']!=='retry_exhausted'||$r['attempt']!==3||
   $writer->claim($id,6,$due2+1,$due2+180,hash('sha256','illegal-reclaim'))['status']!=='not_claimed'){
    throw new RuntimeException('RETRY_EXHAUSTED_BYPASS');
}
$unk=hash('sha256','p9-5-expired');
if($writer->claim($expiredId,0,100,180,$unk)['status']!=='claimed')throw new RuntimeException('UNKNOWN_LEASE');
if($writer->quarantine_expired($expiredId,1,$unk,179)['status']!=='not_quarantined'){
    throw new RuntimeException('UNKNOWN_PREMATURE');
}
if($writer->quarantine_expired($expiredId,1,$unk,180)['status']!=='quarantined'){
    throw new RuntimeException('UNKNOWN_HOLD');
}
if($writer->claim($expiredId,2,180,230,hash('sha256','retry-unknown'))['status']!=='not_claimed'){
    throw new RuntimeException('UNKNOWN_AUTO_RETRY');
}
$health=$writer->synthetic_health();
if($health['status']!=='synthetic_snapshot'||$health['total']!==102||
   $health['states']['uncertain']!==2||
   $health['states']['retry_exhausted']!==1||
   $health['states']['leased']!==1||
   $health['states']['queued']!==98||
   $health['mail_sent']!==false||$health['event_log_verified']!==false){
    throw new RuntimeException('P9_5_FINAL_HEALTH_INVALID');
}
echo "MAIL_P9_5_REAL_MYSQL_RECOVERY_PASS rows=102 retried=2 exhausted=1 unknown_hold=2\n";
echo "MAIL_P9_5_REAL_MYSQL_TWO_PROCESS_DEFERRED_RACE_PASS winner=1 loser=1\n";
echo "MAIL_P9_5_PRIVACY_PASS mail=0 worker=off wp=off p8_unchanged=true\n";
