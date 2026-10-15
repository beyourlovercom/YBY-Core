<?php
/** P9 dedicated MySQL8 synthetic writer+reader integration proof. No SMTP. */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$private=$base.'/artifacts/p9-private-creds.php';
if(realpath($base)!==$base||!is_file($private)||is_link($private)||
   (fileperms($private)&0077)!==0){throw new RuntimeException('P9_PRIVATE_LAB_GATE');}
$c=require $private;
if($c['database']!=='p9_mail_synthetic_uat'||$c['socket']!==$base.'/mysql.sock'||
   $c['writer_user']!=='p9_writer'||$c['reader_user']!=='p9_reader'){
    throw new RuntimeException('P9_CREDENTIALS_WRONG_TARGET');
}
$opts=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,
    PDO::ATTR_TIMEOUT=>3);
function p9connect($c,$id,$opts) {
    return new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii',
        $c[$id.'_user'],$c[$id.'_password'],$opts);
}
$write=p9connect($c,'writer',$opts);
$read=p9connect($c,'reader',$opts);
$w=YBY_Mail_V2_P9_Lab_Writer::open($write);
if(!$w)throw new RuntimeException('P9_WRITER_IDENTITY_OR_SCHEMA_GATE');
if(YBY_Mail_V2_P9_Lab_Writer::open($read)!==null)throw new RuntimeException('P9_READER_ALLOWED_TO_WRITE');
$count=(int)$read->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn();
if($count!==0)throw new RuntimeException('P9_NOT_INITIAL_EMPTY');
$states=array('critical_system','account','campaign','low_priority');
for($i=0;$i<100;$i++){
    $ch=$states[$i%count($states)];
    $job=array(
        'synthetic_only'=>true,
        'opaque_id'=>hash('sha256','p9-lab-opaque-'.$i),
        'idempotency_key'=>hash('sha256','p9-lab-idempotency-'.$i),
        'event_hash'=>hash('sha256','p9-lab-event-'.$i),
        'channel'=>$ch,
        'sender_key'=>YBY_Mail_V2_P4_Routing::CHANNEL_SENDER[$ch],
        'next_attempt_at'=>1000
    );
    if($w->enqueue($job)['status']!=='inserted'){
        throw new RuntimeException('P9_SYNTHETIC_INSERT_FAILED_'.$i);
    }
}
$total=(int)$read->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn();
$distinct=(int)$read->query('SELECT COUNT(DISTINCT idempotency_key) FROM p9_yby_mail_v2_outbox')->fetchColumn();
if($total!==100||$distinct!==100){throw new RuntimeException('P9_SYNTHETIC_COUNT_OR_UNIQUE_FAIL');}
$replay=array(
 'synthetic_only'=>true,'opaque_id'=>hash('sha256','p9-lab-opaque-0'),
 'idempotency_key'=>hash('sha256','p9-lab-idempotency-0'),
 'event_hash'=>hash('sha256','p9-lab-event-0'),
 'channel'=>'critical_system','sender_key'=>'system_tx','next_attempt_at'=>1000
);
if($w->enqueue($replay)['status']!=='duplicate')throw new RuntimeException('P9_IDEMPOTENCE_FAIL');
$collision=$replay;$collision['event_hash']=hash('sha256','collision-fails');
$r=$w->enqueue($collision);
if($r['status']!=='blocked'||$r['reason']!=='idempotency_collision')throw new RuntimeException('P9_ID_COLLISION_NOT_BLOCKED');
foreach(array(
 'INSERT'=>"INSERT INTO p9_yby_mail_v2_outbox SELECT * FROM p9_yby_mail_v2_outbox WHERE 0",
 'UPDATE'=>"UPDATE p9_yby_mail_v2_outbox SET state='leased' WHERE 0",
 'DELETE'=>"DELETE FROM p9_yby_mail_v2_outbox WHERE 0"
) as $name=>$sql){
    $denied=false;
    try{$read->exec($sql);}catch(PDOException $e){$denied=true;}
    if(!$denied)throw new RuntimeException('P9_READER_WRITE_PERMISSION_'.$name);
}
$now=1000;$until=1200;$lease=hash('sha256','p9-lab-first-claim');
if($w->claim($replay['opaque_id'],0,$now,$until,$lease)['status']!=='claimed'){
    throw new RuntimeException('P9_CLAIM_FAIL');
}
$w2=YBY_Mail_V2_P9_Lab_Writer::open(p9connect($c,'writer',$opts));
if(!$w2 || $w2->claim($replay['opaque_id'],0,$now,$until,$lease)['status']!=='not_claimed'){
    throw new RuntimeException('P9_SECOND_SESSION_STALE_CAS_FAIL');
}
$st=$read->prepare('SELECT state,state_version,lease_token_hash FROM p9_yby_mail_v2_outbox WHERE opaque_id=?');
$st->execute(array($replay['opaque_id']));
$row=$st->fetch(PDO::FETCH_ASSOC);
if($row['state']!=='leased'||(int)$row['state_version']!==1||
   $row['lease_token_hash']!==$lease){throw new RuntimeException('P9_LEASE_AUDIT_FAIL');}
if((int)$read->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn()!==100){
    throw new RuntimeException('P9_COUNT_AFTER_CAS_CHANGED');
}
echo "MAIL_P9_MYSQL8_ISOLATED_WRITER_PASS synthetic=100 idempotent=1 collisions_denied=1\n";
echo "MAIL_P9_SEPARATE_READER_DENIES_INSERT_UPDATE_DELETE_PASS\n";
echo "MAIL_P9_TWO_SESSIONS_CAS_PASS winner=1 stale=0\n";
echo "MAIL_P9_NO_SMTP_NO_WP_NO_P8_NO_V22_CHANGES\n";
