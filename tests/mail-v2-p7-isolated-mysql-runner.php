<?php
/**
 * P7 explicitly authorized ISOLATED MySQL 8.4 runner. NOT a generic migration!
 * Requires its own --skip-networking Unix-socket server with a datadir under
 * ~/.byl-dev-canary/mail-v2-p7-mysql-20261014/.
 * CLI only. No WordPress bootstrap, SMTP, emails or Action Scheduler.
 * Creates only a NEW synthetic DB on that independently verified instance.
 */
if ( PHP_SAPI !== 'cli' ) { exit(72); }
define('ABSPATH', __DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
$checks=0;
function p7ok($ok,$message) {
    global $checks;
    ++$checks;
    if (!$ok) { throw new RuntimeException('P7_MYSQL_FAIL:'.$message); }
}
$home=getenv('HOME');
$base=$home.'/.byl-dev-canary/mail-v2-p7-mysql-20261014';
$socket=$base.'/mysql.sock';
if (getenv('MAIL_P7_ISOLATED_TEST')!=='EXPLICIT_SYNTHETIC_ONLY' ||
    !is_string($home) || !is_dir($base.'/data') ||
    realpath($base.'/data')!==$base.'/data' ||
    !file_exists($socket) || is_link($socket) ||
    realpath($socket)!==$socket ||
    !extension_loaded('pdo_mysql') ||
    !function_exists('pcntl_fork')) {
    throw new RuntimeException('P7_ISOLATION_PREFLIGHT_DENIED');
}
function p7db($socket,$db='') {
    return new PDO('mysql:unix_socket='.$socket.';'.($db?'dbname='.$db.';':'').'charset=ascii',
        'root','',array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES=>false));
}
// Execute the competitor in a fresh PHP image, without inherited PDO sockets.
if (($argv[1] ?? '') === '--cas-child') {
    $child_id=$argv[2] ?? '';
    if (!is_string($child_id) || !preg_match('/\\A[a-f0-9]{64}\\z/D',$child_id) ||
        count($argv)!==3) { exit(89); }
    $child_marker=$base.'/artifacts/race-result.txt';
    try {
        $child=p7db($socket,'p7_mail_synthetic_uat');
        $child->exec('SET innodb_lock_wait_timeout=4');
        $stmt=$child->prepare("UPDATE p7_yby_mail_v2_outbox SET state='leased',state_version=2 WHERE opaque_id=? AND state='queued' AND state_version=0");
        $stmt->execute(array($child_id));
        file_put_contents($child_marker,(string)$stmt->rowCount(),LOCK_EX);
        exit(0);
    } catch (Throwable $e) {
        file_put_contents($child_marker,'ERROR',LOCK_EX);
        exit(90);
    }
}
$root=p7db($socket);
$meta=$root->query('SELECT @@datadir AS d,@@skip_networking AS n,@@version AS v')->fetch(PDO::FETCH_ASSOC);
p7ok(1 === (int)$meta['n'],'network must be off');
p7ok(strpos(realpath(rtrim($meta['d'],'/')),$base.'/data')===0,'isolated mysql data root');
p7ok(strpos($meta['v'],'8.4.')===0,'mysql8.4 not mixed MariaDB');
$dbname='p7_mail_synthetic_uat';
$table='p7_yby_mail_v2_outbox';
$exists=(int)$root->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$dbname."'")->fetchColumn();
p7ok($exists===0,'never reuse existing test database');
$ddl=YBY_Mail_V2_P6_Outbox_Plan::ddl_proposal('p7_');
p7ok($ddl['status']==='review_only'&&$ddl['table']===$table,'P6 exact schema DDL');
$root->exec('CREATE DATABASE '.$dbname.' DEFAULT CHARACTER SET ascii COLLATE ascii_bin');
$root->exec('USE '.$dbname);
$root->exec($ddl['sql']);
$cols=$root->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$table."' ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_COLUMN);
p7ok($cols===array_keys(YBY_Mail_V2_P6_Outbox_Plan::columns()),'20 metadata columns exact order');
$indexes=$root->query("SELECT INDEX_NAME,COLUMN_NAME,NON_UNIQUE,SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$table."' ORDER BY INDEX_NAME,SEQ_IN_INDEX")->fetchAll(PDO::FETCH_ASSOC);
$found=array();
foreach($indexes as $i){$found[$i['INDEX_NAME']]['cols'][]=$i['COLUMN_NAME'];$found[$i['INDEX_NAME']]['nonunique']=(int)$i['NON_UNIQUE'];}
foreach($ddl['indexes'] as $name=>$keys){
    p7ok(isset($found[$name])&&$found[$name]['cols']===$keys,'index '.$name);
}
p7ok($found['uq_idempotency_key']['nonunique']===0,'unique index enforcement');
p7ok(count($found)===count($ddl['indexes']),'no unauthorized indexes');
$fields=array_keys(YBY_Mail_V2_P6_Outbox_Plan::columns());
$insert='INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
$stmt=$root->prepare($insert);
$states=array('queued','deferred','uncertain','permanent_failed','accepted_by_provider');
for($i=0;$i<100;++$i){
    $channel=$i%25===0?'critical_system':($i%5===0?'account':'campaign');
    $state=$states[$i%5];
    $priority=$channel==='critical_system'?0:($channel==='account'?1:2);
    $codes=array('queued'=>'none','deferred'=>'preacceptance_reject','uncertain'=>'acceptance_unknown',
        'permanent_failed'=>'permanent_reject','accepted_by_provider'=>'provider_accepted');
    $v=array(
        hash('sha256','p7-mysql-opaque-'.$i),hash('sha256','p7-mysql-idem-'.$i),
        hash('sha256','p7-mysql-event-'.$i),null,$channel,
        $channel==='campaign'?'newsletter_bulk':'system_tx','synthetic_only',1,
        $channel==='campaign'?hash('sha256','p7-mysql-consent-'.$i):null,
        $priority,$state,0,$state==='queued'?0:1,3,100+$i,
        null,null,$codes[$state],'2026-10-14 00:00:00.000000','2026-10-14 00:00:00.000000',
    );
    p7ok(count($v)===count($fields),'row shape '.$i);
    $stmt->execute($v);
}
$n=(int)$root->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
p7ok($n===100,'exact 100 synthetic MySQL rows');
$failures=(int)$root->query("SELECT COUNT(*) FROM ".$table." WHERE state IN ('deferred','permanent_failed','uncertain')")->fetchColumn();
p7ok($failures===60,'60 synthetic failures');
$dup=$root->query('SELECT idempotency_key FROM '.$table.' LIMIT 1')->fetchColumn();
try{
    $stmt->execute(array(hash('sha256','p7-duplicate-opaque'),$dup,
        hash('sha256','p7-duplicate-event'),null,'campaign','newsletter_bulk',
        'synthetic_only',1,null,2,'queued',0,0,3,200,null,null,'none',
        '2026-10-14 00:00:00.000000','2026-10-14 00:00:00.000000'));
    throw new RuntimeException('P7_UNIQUE_KEY_NOT_ENFORCED');
}catch(PDOException $e){p7ok($e->getCode()==='23000','duplicate key SQLSTATE 23000');}
$rowid=$root->query("SELECT opaque_id FROM ".$table." WHERE state='queued' LIMIT 1")->fetchColumn();
$root->beginTransaction();
$a=$root->prepare("UPDATE ".$table." SET state='leased',state_version=state_version+1 WHERE opaque_id=? AND state='queued' AND state_version=0");
$a->execute(array($rowid));p7ok($a->rowCount()===1,'CAS first claim');
$a->execute(array($rowid));p7ok($a->rowCount()===0,'CAS second claim denied');
$root->rollBack();
p7ok((int)$root->query("SELECT state_version FROM ".$table." WHERE opaque_id='".$rowid."'")->fetchColumn()===0,'CAS transaction reverted');
// True two-connection InnoDB row-lock isolation: SKIP LOCKED ignores locked task.
$other=p7db($socket,$dbname);
$root->beginTransaction();
$lock=$root->prepare('SELECT opaque_id FROM '.$table.' WHERE opaque_id=? FOR UPDATE');
$lock->execute(array($rowid));p7ok($lock->fetchColumn()===$rowid,'primary transaction row locked');
$other->beginTransaction();
$q=$other->prepare('SELECT opaque_id FROM '.$table.' WHERE opaque_id=? FOR UPDATE SKIP LOCKED');
$q->execute(array($rowid));
p7ok($q->fetchColumn()===false,'SKIP LOCKED cannot double claim');
$other->rollBack();
$root->rollBack();
// Two genuinely competing processes, one winner and one stale CAS loser.
$root->beginTransaction();
$upd=$root->prepare("UPDATE ".$table." SET state='leased',state_version=1 WHERE opaque_id=? AND state='queued' AND state_version=0");
$upd->execute(array($rowid));p7ok($upd->rowCount()===1,'winner locks row');
$marker=$base.'/artifacts/race-result.txt';
p7ok(!file_exists($marker),'no stale race marker');
$pid=pcntl_fork();
if($pid===-1){throw new RuntimeException('FORK_UNAVAILABLE');}
if($pid===0){
    // exec discards all inherited parent PDO connections, avoiding mysqlnd QUIT
    // sent from destructors in a forked child against the parent's sockets.
    pcntl_exec(PHP_BINARY,array(__FILE__,'--cas-child',$rowid));
    file_put_contents($marker,'EXEC_FAILED',LOCK_EX);
    exit(91);
}
usleep(250000);
$root->commit();
pcntl_waitpid($pid,$status);
p7ok(pcntl_wifexited($status)&&pcntl_wexitstatus($status)===0,'child completed');
p7ok(file_get_contents($marker)==='0','concurrent stale CAS claim rejected');
p7ok((int)$root->query("SELECT state_version FROM ".$table." WHERE opaque_id='".$rowid."'")->fetchColumn()===1,'exactly one durable claim');
$root->exec("UPDATE ".$table." SET state='queued',state_version=0 WHERE opaque_id='".$rowid."' AND state_version=1");
p7ok((int)$root->query("SELECT COUNT(*) FROM ".$table)->fetchColumn()===100,'100 remain after race');
$root=null;
// Read-only dedicated connection still cannot write (engine enforces it).
$reader=p7db($socket,$dbname);
$reader->exec('SET SESSION TRANSACTION READ ONLY');
$reader->beginTransaction();
p7ok((int)$reader->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()===100,'read-only selects fixture');
try{
    $reader->exec("UPDATE ".$table." SET state='cancelled' LIMIT 1");
    throw new RuntimeException('MYSQL_RO_WRITE_NOT_BLOCKED');
}catch(PDOException $e){p7ok(true,'read-only transaction denies UPDATE');}
$reader->rollBack();
echo "MAIL_P7_ISOLATED_MYSQL_PASS assertions=".$checks." rows=100 failures=60 unique=100 concurrent_CAS=1 skip_locked=PASS network=OFF\n";
echo "P7_MYSQL_ENGINE=8.4 DATA_ROOT=PRIVATE NO_WP_DB_Writes NO_MAIL NO_WORKER\n";
