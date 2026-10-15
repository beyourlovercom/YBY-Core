<?php
/** P9 real isolated MySQL lease-expiry HOLD proof. No SMTP/Worker jobs. */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-lab-writer.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$file=$base.'/artifacts/p9-private-creds.php';
if(!is_file($file)||is_link($file)||(fileperms($file)&0077)!==0){exit(82);}
$c=require $file;
if($c['database']!=='p9_mail_synthetic_uat'||$c['socket']!==$base.'/mysql.sock'){exit(83);}
$dsn='mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii';
$opt=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false);
$read=new PDO($dsn,$c['reader_user'],$c['reader_password'],$opt);
$write=new PDO($dsn,$c['writer_user'],$c['writer_password'],$opt);
$writer=YBY_Mail_V2_P9_Lab_Writer::open($write);
if(!$writer){throw new RuntimeException('P9_RECOVERY_WRITER_GATE');}
$id=hash('sha256','p9-lab-opaque-1');
$stmt=$read->prepare('SELECT state,state_version,lease_token_hash,lease_expires_at FROM p9_yby_mail_v2_outbox WHERE opaque_id=?');
$stmt->execute(array($id));$before=$stmt->fetch(PDO::FETCH_ASSOC);
if($before['state']!=='leased'||(int)$before['state_version']!==1 ||
   (int)$before['lease_expires_at']!==1200 || !is_string($before['lease_token_hash']) ||
   !preg_match('/\A[a-f0-9]{64}\z/D',$before['lease_token_hash'])){
    throw new RuntimeException('P9_RECOVERY_PRESTATE_MISMATCH');
}
$bad=$writer->quarantine_expired($id,1,hash('sha256','wrong-token'),1200);
if($bad['status']!=='not_quarantined')throw new RuntimeException('P9_WRONG_LEASE_TOKEN_ACCEPTED');
$early=$writer->quarantine_expired($id,1,$before['lease_token_hash'],1199);
if($early['status']!=='not_quarantined')throw new RuntimeException('P9_PREMATURE_EXPIRY_ACCEPTED');
$ok=$writer->quarantine_expired($id,1,$before['lease_token_hash'],1200);
if($ok['status']!=='quarantined'||$ok['retry_authorized']!==false||
   $ok['mail_sent']!==false){throw new RuntimeException('P9_QUARANTINE_FAILED');}
$replay=$writer->quarantine_expired($id,1,$before['lease_token_hash'],1200);
if($replay['status']!=='not_quarantined')throw new RuntimeException('P9_QUARANTINE_REPLAY_ACCEPTED');
if($writer->claim($id,2,1200,1250,hash('sha256','never-retry'))['status']!=='not_claimed'){
    throw new RuntimeException('P9_UNCERTAIN_REQUEUED');
}
$afterQuery=$read->prepare('SELECT state,state_version,diagnostic_code,lease_token_hash,lease_expires_at FROM p9_yby_mail_v2_outbox WHERE opaque_id=?');
$afterQuery->execute(array($id));$after=$afterQuery->fetch(PDO::FETCH_ASSOC);
if($after['state']!=='uncertain'||(int)$after['state_version']!==2||
   $after['diagnostic_code']!=='acceptance_unknown'||
   $after['lease_token_hash']!==null||$after['lease_expires_at']!==null){
    throw new RuntimeException('P9_UNCERTAIN_HOLD_STATE_INVALID');
}
if((int)$read->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn()!==100){
    throw new RuntimeException('P9_RECOVERY_CHANGED_ROW_COUNT');
}
echo "MAIL_P9_MYSQL_EXPIRED_LEASE_HOLD_PASS state=uncertain retry=blocked rows=100\n";
echo "MAIL_P9_WRONG_TOKEN_EARLY_REPLAY_DENIED_PASS\n";
echo "MAIL_P9_NO_REAL_SMTP_NO_WORKER_NO_CUSTOMER_DATA\n";
