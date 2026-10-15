<?php
/** P9 read-only point-in-time backup of the isolated 100 synthetic tasks. */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
$root=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$path=$root.'/artifacts/p9-private-creds.php';
if(realpath($root)!==$root||!is_file($path)||is_link($path)||
   (fileperms($path)&0077)!==0){exit(82);}
$c=require $path;
if($c['database']!=='p9_mail_synthetic_uat'||$c['socket']!==$root.'/mysql.sock'||
   $c['reader_user']!=='p9_reader'){exit(83);}
$db=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii',
    $c['reader_user'],$c['reader_password'],
    array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
if($db->query('SELECT DATABASE()')->fetchColumn()!==$c['database'] ||
   (int)$db->query('SELECT @@skip_networking')->fetchColumn()!==1 ||
   $db->query('SELECT CURRENT_USER()')->fetchColumn()!=='p9_reader@localhost'){
    throw new RuntimeException('P9_PRIVATE_READER_GATE');
}
$db->exec('SET SESSION TRANSACTION READ ONLY');
$db->beginTransaction();
$ddl=$db->query('SHOW CREATE TABLE p9_yby_mail_v2_outbox')->fetch(PDO::FETCH_NUM);
$rows=$db->query('SELECT * FROM p9_yby_mail_v2_outbox ORDER BY opaque_id')->fetchAll(PDO::FETCH_ASSOC);
$db->rollBack();
if(!is_array($ddl)||$ddl[0]!=='p9_yby_mail_v2_outbox'||
   strpos($ddl[1],'ENGINE=InnoDB')===false||count($rows)!==100){throw new RuntimeException('P9_BACKUP_TABLE_DRIFT');}
$ids=array();$statuses=array();
foreach($rows as $r){
    if(count($r)!==20||!preg_match('/\A[a-f0-9]{64}\z/D',$r['opaque_id'])||
       !preg_match('/\A[a-f0-9]{64}\z/D',$r['idempotency_key'])||
       isset($ids[$r['idempotency_key']])||
       $r['template_key']!=='synthetic_only'||$r['payload_ref']!==null){
        throw new RuntimeException('P9_BACKUP_UNTRUSTED_RECORD');
    }
    $ids[$r['idempotency_key']]=true;
    $statuses[$r['state']]=($statuses[$r['state']]??0)+1;
}
if(($statuses['queued']??0)!==98||($statuses['leased']??0)!==1||
   ($statuses['uncertain']??0)!==1){throw new RuntimeException('P9_SNAPSHOT_STATE_DRIFT');}
$out=$root.'/artifacts/p9-synthetic-outbox-100.json';
if(file_exists($out)){throw new RuntimeException('P9_BACKUP_ALREADY_EXISTS');}
$payload=array(
 'schema'=>'p9_mysql8_synthetic_only_v1',
 'source'=>'isolated_mysql8_socket_no_network',
 'real_mail_sent'=>false,'worker_enabled'=>false,
 'total'=>100,'statuses'=>$statuses,'create_table'=>$ddl[1],'rows'=>$rows
);
$raw=json_encode($payload,JSON_UNESCAPED_SLASHES);
if(!is_string($raw)){throw new RuntimeException('P9_SNAPSHOT_JSON_ENCODE');}
$old=umask(0077);
try{
    $h=fopen($out,'x');if(!$h)throw new RuntimeException('P9_BACKUP_CREATE_DENIED');
    $bytes=fwrite($h,$raw);fflush($h);fclose($h);
    if($bytes!==strlen($raw)||!chmod($out,0600)){@unlink($out);throw new RuntimeException('P9_BACKUP_INCOMPLETE');}
}finally{umask($old);}
$check=json_decode(file_get_contents($out),true);
if(!is_array($check)||$check['total']!==100||
   $check['statuses']!==$statuses||count($check['rows'])!==100||
   (fileperms($out)&0077)!==0){throw new RuntimeException('P9_BACKUP_READBACK_FAIL');}
echo 'MAIL_P9_SYNTHETIC_MYSQL_BACKUP_PASS rows=100 queued=98 leased=1 uncertain=1'."\n";
echo 'MAIL_P9_PRIVATE_BACKUP_SHA256='.hash_file('sha256',$out)."\n";
echo "MAIL_P9_BACKUP_0600_READBACK_PASS_NO_SECRETS_NO_CUSTOMERS\n";
