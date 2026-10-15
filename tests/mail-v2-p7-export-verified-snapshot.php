<?php
/** P7 one-way sanitized snapshot export, only from standalone socket MySQL8 lab. */
if (PHP_SAPI !== 'cli' || getenv('MAIL_P7_ISOLATED_TEST') !== 'EXPLICIT_SYNTHETIC_ONLY') { exit(76); }
$root=getenv('HOME').'/.byl-dev-canary/mail-v2-p7-mysql-20261014';
$socket=$root.'/mysql.sock';
if (!is_dir($root.'/data') || !is_file($root.'/mysql.pid') ||
    !file_exists($socket) || is_link($socket) || realpath($socket)!==$socket) { exit(77); }
$pdo=new PDO('mysql:unix_socket='.$socket.';dbname=p7_mail_synthetic_uat;charset=ascii','root','',
    array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
$meta=$pdo->query('SELECT @@skip_networking AS isolated,@@version AS version,@@datadir AS datadir')->fetch(PDO::FETCH_ASSOC);
if ((int)$meta['isolated']!==1 || strpos($meta['version'],'8.4.')!==0 ||
    strpos(realpath(rtrim($meta['datadir'],'/')),$root.'/data')!==0) { exit(78); }
$states=array('queued','leased','sending','accepted_by_provider','deferred','permanent_failed','uncertain','cancelled');
$fields=array('opaque_id','state','channel','sender_key','priority','attempt','max_attempts',
              'next_attempt_at','diagnostic_code','state_version');
$quoted=implode(',',array_map(static function($s){return chr(96).$s.chr(96);},$fields));
$rows=$pdo->query('SELECT '.$quoted.' FROM p7_yby_mail_v2_outbox ORDER BY priority,next_attempt_at,opaque_id')->fetchAll(PDO::FETCH_ASSOC);
if(count($rows)!==100){exit(79);}
$counts=array_fill_keys($states,0);
$ids=array();
foreach($rows as &$r){
    if(count($r)!==10 || array_keys($r)!==$fields || !in_array($r['state'],$states,true) ||
       !preg_match('/\A[a-f0-9]{64}\z/D',$r['opaque_id']) ||
       !preg_match('/\A[a-z][a-z0-9_]{0,47}\z/D',$r['sender_key']) ||
       isset($ids[$r['opaque_id']])) {exit(80);}
    $ids[$r['opaque_id']]=true;
    foreach(array('priority','attempt','max_attempts','next_attempt_at','state_version') as $field){
        $r[$field]=(int)$r[$field];
    }
    ++$counts[$r['state']];
}
unset($r);
if($counts['deferred']+$counts['uncertain']+$counts['permanent_failed']!==60) {exit(81);}
$payload=array(
 'schema'=>'mail-v2-p7-mysql8-synthetic-snapshot-v1',
 'source'=>'isolated_mysql_8_4_point_in_time',
 'snapshot_not_live'=>true,
 'mysql_network_disabled'=>true,
 'email_sent'=>false, 'worker_enabled'=>false,
 'total'=>count($rows), 'counts'=>$counts, 'rows'=>$rows,
);
$path=$root.'/artifacts/p7-mysql-verified-snapshot.json';
if(file_exists($path)){exit(82);}
$data=json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
if(!is_string($data)){exit(83);}
file_put_contents($path,$data."\n",LOCK_EX);
chmod($path,0600);
echo "P7_MYSQL_SNAPSHOT_EXPORT_PASS rows=100 errors=60 sha256=".hash_file('sha256',$path)."\n";
