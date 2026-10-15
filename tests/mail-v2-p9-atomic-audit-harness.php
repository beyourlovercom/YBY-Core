<?php
/**
 * P9-5 V2 audit atomicity proposal regression using disposable SQLite memory.
 * No current P9 MySQL schema is changed and no WordPress or SMTP is booted.
 * This tests transaction semantics, NOT a real MySQL audit deployment.
 */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-audit-contract.php';
$n=0;
function a9($ok,$label) {
    global $n; ++$n;
    if(!$ok)throw new RuntimeException('P9_AUDIT_V2_FAIL:'.$label);
}
$id=hash('sha256','p9-synthetic-atomic-ledger');
$genesis=str_repeat('0',64);
function event9($id,$v,$code,$from,$to,$diag,$prev) {
    return YBY_Mail_V2_P9_Audit_Contract::proposed_event(array(
        'synthetic_only'=>true,'opaque_id'=>$id,'state_version'=>$v,
        'event_code'=>$code,'from_state'=>$from,'to_state'=>$to,
        'diagnostic_code'=>$diag,'prev_event_hash'=>$prev));
}
$enqueue=event9($id,0,'enqueued','none','queued','none',$genesis);
a9($enqueue['status']==='review_only','synthetic-genesis');
a9($enqueue['can_write']===false && $enqueue['tamper_proof']===false,
    'never-authorizes-schema-or-writes');
a9(YBY_Mail_V2_P9_Audit_Contract::can_install_or_activate()===false,
    'hard-default-off');
a9(event9($id,1,'claimed','queued','sending','none',
    $enqueue['event_hash'])['status']==='blocked','reject-transition');
a9(event9('address@example.com',1,'claimed','queued','leased','none',
    $enqueue['event_hash'])['status']==='blocked','reject-pii');
a9(event9($id,1,'claimed','queued','leased','acceptance_unknown',
    $enqueue['event_hash'])['status']==='blocked','reject-false-proof');
$extra=array('synthetic_only'=>true,'opaque_id'=>$id,'state_version'=>1,
    'event_code'=>'claimed','from_state'=>'queued','to_state'=>'leased',
    'diagnostic_code'=>'none','prev_event_hash'=>$enqueue['event_hash'],
    'recipient'=>'private@example.com');
a9(YBY_Mail_V2_P9_Audit_Contract::proposed_event($extra)['status']==='blocked',
    'reject-extra-keys');
$pdo=new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE outbox(opaque_id TEXT PRIMARY KEY,state TEXT NOT NULL,
    state_version INTEGER NOT NULL)');
$pdo->exec('CREATE TABLE audit(opaque_id TEXT NOT NULL,
    state_version INTEGER NOT NULL,from_state TEXT NOT NULL,to_state TEXT NOT NULL,
    event_code TEXT NOT NULL,prev_event_hash TEXT NOT NULL,event_hash TEXT NOT NULL,
    PRIMARY KEY(opaque_id,state_version))');
$q=$pdo->prepare('INSERT INTO outbox VALUES(?,?,?)');$q->execute(array($id,'queued',0));
$q=$pdo->prepare('INSERT INTO audit VALUES(?,?,?,?,?,?,?)');
$q->execute(array($id,0,'none','queued','enqueued',$genesis,$enqueue['event_hash']));
function snapshot9($pdo,$id) {
    $q=$pdo->prepare('SELECT state,state_version FROM outbox WHERE opaque_id=?');
    $q->execute(array($id));$s=$q->fetch(PDO::FETCH_ASSOC);
    $q=$pdo->prepare('SELECT state_version,event_code,event_hash FROM audit WHERE opaque_id=? ORDER BY state_version');
    $q->execute(array($id));$history=$q->fetchAll(PDO::FETCH_ASSOC);
    return array('state'=>$s['state'],'version'=>(int)$s['state_version'],'history'=>$history);
}
/** In-memory disposable demonstration: paired CAS UPDATE+audit INSERT in same transaction. */
function commit_pair9($pdo,$id,$v,$from,$to,$event,$crash='') {
    $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('UPDATE outbox SET state=?,state_version=state_version+1
            WHERE opaque_id=? AND state_version=? AND state=?');
        $s->execute(array($to,$id,$v,$from));
        if($s->rowCount()!==1)throw new RuntimeException('STALE_CAS');
        if($crash==='after_cas')throw new RuntimeException('CRASH_AFTER_CAS');
        $i=$pdo->prepare('INSERT INTO audit VALUES(?,?,?,?,?,?,?)');
        $i->execute(array($id,$v+1,$from,$to,$event['event_code'],
            $event['prev_event_hash'],$event['event_hash']));
        if($crash==='before_commit')throw new RuntimeException('CRASH_BEFORE_COMMIT');
        $pdo->commit();
        return 'committed';
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        return $e->getMessage();
    }
}
$claimed=event9($id,1,'claimed','queued','leased','none',$enqueue['event_hash']);
a9($claimed['status']==='review_only','claim-manifest');
a9(commit_pair9($pdo,$id,0,'queued','leased',$claimed,'after_cas')===
    'CRASH_AFTER_CAS','inject-crash-after-cas');
$s=snapshot9($pdo,$id);
a9($s['state']==='queued' && $s['version']===0 && count($s['history'])===1,
    'rollback-before-event');
a9(commit_pair9($pdo,$id,0,'queued','leased',$claimed,'before_commit')===
    'CRASH_BEFORE_COMMIT','inject-crash-before-commit');
$s=snapshot9($pdo,$id);
a9($s['state']==='queued' && count($s['history'])===1,
    'rollback-both-changes');
a9(commit_pair9($pdo,$id,0,'queued','leased',$claimed)==='committed',
    'paired-commit');
$s=snapshot9($pdo,$id);
a9($s['state']==='leased' && $s['version']===1 &&
    count($s['history'])===2,'audit-version-matches-outbox');
a9(commit_pair9($pdo,$id,0,'queued','leased',$claimed)==='STALE_CAS',
    'stale-cas-and-duplicate-denied');
$unknown=event9($id,2,'uncertain_hold','leased','uncertain',
    'acceptance_unknown',$claimed['event_hash']);
a9($unknown['status']==='review_only','unknown-hold-proposal');
// Simulate corrupt/colliding future audit key: transaction MUST roll back Outbox.
$i=$pdo->prepare('INSERT INTO audit VALUES(?,?,?,?,?,?,?)');
$i->execute(array($id,2,'leased','uncertain','uncertain_hold',
    $claimed['event_hash'],$unknown['event_hash']));
$result=commit_pair9($pdo,$id,1,'leased','uncertain',$unknown);
a9($result!=='committed','audit-collision-denies-transition');
$s=snapshot9($pdo,$id);
a9($s['state']==='leased' && $s['version']===1,
    'audit-insert-error-rolls-back-cas');
$pdo->prepare('DELETE FROM audit WHERE opaque_id=? AND state_version=2')
    ->execute(array($id)); // disposable fault injection cleanup ONLY.
a9(commit_pair9($pdo,$id,1,'leased','uncertain',$unknown)==='committed',
    'persist-unknown-hold-and-event');
$s=snapshot9($pdo,$id);
a9($s['state']==='uncertain' && $s['version']===2 &&
    count($s['history'])===3,'unknown-audited-once');
a9($s['history'][2]['event_hash']===$unknown['event_hash'],
    'read-after-ambiguous-commit-by-version');
a9(commit_pair9($pdo,$id,1,'leased','uncertain',$unknown)==='STALE_CAS',
    'cannot-replay-ambiguous-commit');
a9(event9($id,3,'claimed','uncertain','leased','none',
    $unknown['event_hash'])['status']==='blocked',
    'uncertain-no-reclaim-event');
$src=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-audit-contract.php');
a9(!preg_match('/\\b(?:wp_mail|add_action|dbDelta|as_schedule_single_action)\\s*\\(/i',
    $src),'no-runtime-hooks');
echo 'MAIL_P9_AUDIT_V2_SQLITE_ATOMICITY_SIMULATION_PASS assertions='.$n.
    ' synthetic_only=1 real_mysql_audit=0 worker=off mail=0'.PHP_EOL;
