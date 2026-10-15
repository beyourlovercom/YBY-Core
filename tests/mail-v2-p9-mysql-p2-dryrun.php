<?php
/**
 * P9 real isolated DB → P2/P5 *preview only*. Reader SELECT and inert
 * Action Scheduler proposals; no SQL writes, WP hooks, SMTP or real quotas.
 */
if(PHP_SAPI!=='cli'||getenv('MAIL_P9_LAB_ONLY')!=='EXPLICIT_ISOLATED_SYNTHETIC_ONLY'){exit(81);}
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p2-planner.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p2-scheduler.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p5-stress.php';
$base=getenv('HOME').'/.byl-dev-canary/mail-v2-p9-mysql-20261015';
$path=$base.'/artifacts/p9-private-creds.php';
if(!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0){exit(82);}
$c=require $path;
if($c['database']!=='p9_mail_synthetic_uat'||$c['socket']!==$base.'/mysql.sock'||
    $c['reader_user']!=='p9_reader'){exit(83);}
$db=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'].';charset=ascii',
    $c['reader_user'],$c['reader_password'],
    array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
if((int)$db->query('SELECT @@skip_networking')->fetchColumn()!==1 ||
    $db->query('SELECT DATABASE()')->fetchColumn()!==$c['database'] ||
    $db->query('SELECT CURRENT_USER()')->fetchColumn()!=='p9_reader@localhost'){
    throw new RuntimeException('P9_READER_ISOLATION_DENIED');
}
$columns='opaque_id,idempotency_key,sender_key,channel,priority,state,state_version,attempt,max_attempts,next_attempt_at';
$records=$db->query('SELECT '.$columns.' FROM p9_yby_mail_v2_outbox')->fetchAll(PDO::FETCH_ASSOC);
if(count($records)!==100)throw new RuntimeException('P9_LAB_COUNT_CHANGED');
foreach($records as &$row){
    if(array_keys($row)!==explode(',',$columns))throw new RuntimeException('P9_UNEXPECTED_METADATA_FIELDS');
    foreach(array('priority','state_version','attempt','max_attempts','next_attempt_at') as $key){
        if(!ctype_digit((string)$row[$key]))throw new RuntimeException('P9_BAD_NUMBER');
        $row[$key]=(int)$row[$key];
    }
}
unset($row);
$before=array_count_values(array_column($records,'state'));
if(($before['leased']??0)!==1 || ($before['queued']??0)!==99){
    throw new RuntimeException('P9_NOT_POST_CAS_FIXTURE');
}
$budgets=array(
 'system_tx'=>array('tokens'=>30,'max_inflight'=>30,'inflight'=>0,'cooldown_until'=>0,'source'=>'synthetic_verified'),
 'newsletter_bulk'=>array('tokens'=>20,'max_inflight'=>20,'inflight'=>0,'cooldown_until'=>0,'source'=>'synthetic_verified')
);
$cfg=array('synthetic_only'=>true,'health_ok'=>true,'max_batch'=>50,'critical_reserve'=>10);
$plan=YBY_Mail_V2_P2_Planner::plan($records,$budgets,1000,$cfg);
if($plan['status']!=='dry_run'||count($plan['claims'])!==50){
    throw new RuntimeException('P9_P2_DATABASE_PLAN_FAIL');
}
$by=array_count_values(array_column($plan['claims'],'priority'));
if(($by[0]??0)!==24||($by[1]??0)!==6||($by[2]??0)!==20){
    throw new RuntimeException('P9_CRITICAL_RESERVE_OR_SENDER_QUOTA_FAIL');
}
$actions=YBY_Mail_V2_P2_Scheduler::preview_actions($plan['claims'],
    array('synthetic_only'=>true,'as_version'=>'4.1.0',
        'db_stable'=>true,'woo_jobs_isolated'=>true));
if(count($actions)!==50)throw new RuntimeException('P9_AS_PREVIEW_FAIL');
foreach($actions as $a){
    if($a['scheduled']||$a['executed']||$a['group']!=='andy_core_mail_v2'){
        throw new RuntimeException('P9_REAL_SCHEDULER_ACTION_UNEXPECTED');
    }
}
$bad=$budgets;$bad['newsletter_bulk']['tokens']=0;
$deny=YBY_Mail_V2_P2_Planner::plan($records,$bad,1000,$cfg);
if($deny['status']!=='dry_run'||count($deny['claims'])!==30||
    count(array_filter($deny['claims'],static fn($r)=>$r['priority']===2))!==0){
    throw new RuntimeException('P9_NO_MARKETING_TOKENS_POLICY');
}
$after=(int)$db->query('SELECT COUNT(*) FROM p9_yby_mail_v2_outbox')->fetchColumn();
if($after!==100)throw new RuntimeException('P9_DRYRUN_MUTATED_STORAGE');
echo 'MAIL_P9_REAL_MYSQL_P2_DRYRUN_PASS synthetic=100 preview=50 critical=24 account=6 marketing=20'."\n";
echo 'MAIL_P9_ZERO_MARKETING_TOKENS_PASS preview=30'."\n";
echo 'MAIL_P9_AS_ACTIONS_ALL_UNSCHEDULED_PASS sender_budgets_synthetic=true'."\n";
echo 'MAIL_P9_NO_DATABASE_WRITE_NO_SMTP_NO_WORKER'."\n";
