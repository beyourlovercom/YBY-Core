<?php
/** Mail OS V2 P6 source-only schema & SQL boundary regression. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php';
$n=0;
function p6ok($x,$why){global $n;++$n;if(!$x)throw new RuntimeException('MAIL_P6_FAIL:'.$why);}
$p=YBY_Mail_V2_P6_Outbox_Plan::ddl_proposal('wp_');
p6ok($p['status']==='review_only'&&!$p['can_apply']&&$p['requires_owner_schema_write_approval'],'no DDL write');
p6ok($p['table']==='wp_yby_mail_v2_outbox','isolated table');
p6ok(count($p['columns'])===20,'exact 20 columns');
p6ok(array_keys($p['columns'])===YBY_Mail_V2_Governance::schema_contract()['columns'],'P1 schema match');
p6ok(count($p['indexes'])===4,'all expected indexes');
p6ok($p['indexes']['PRIMARY']===array('opaque_id'),'PK');
p6ok($p['indexes']['uq_idempotency_key']===array('idempotency_key'),'dedupe');
p6ok($p['indexes']['idx_state_priority_due']===array('state','priority','next_attempt_at'),'priority order index');
p6ok($p['indexes']['idx_sender_state_due']===array('sender_key','state','next_attempt_at'),'sender index');
p6ok(str_starts_with($p['sql'],'CREATE TABLE'),'DDL review text');
p6ok(str_contains($p['sql'],'UNIQUE KEY')&&!str_contains($p['sql'],'IF NOT EXISTS'),'no invisible drift');
p6ok(str_contains($p['sql'],'state_version')&&str_contains($p['sql'],'lease_token_hash'),'CAS+lease');
p6ok(str_contains($p['sql'],'ENGINE=InnoDB')&&str_contains($p['sql'],'ascii_bin'),'storage backend');
foreach(array('recipient','smtp_password','subject','body','headers','smtp_response') as $s){
 p6ok(!str_contains($p['sql'],$s),'no sensitive column '.$s);
}
foreach(array('','wp-','x'.chr(96).'DROP','x y','a;DELETE','../../',"x\n") as $bad){
 p6ok(YBY_Mail_V2_P6_Outbox_Plan::table($bad)===null,'SQL identifier rejection');
}
p6ok(YBY_Mail_V2_P6_Outbox_Plan::table(str_repeat('x',33))===null,'overlong prefix');
p6ok(YBY_Mail_V2_P6_Outbox_Plan::table('abc_')==='abc_yby_mail_v2_outbox','safe prefix');
$m=array('columns'=>$p['columns'],'indexes'=>$p['indexes']);
p6ok(YBY_Mail_V2_P6_Outbox_Plan::schema_matches($m),'exact manifest');
$bad=$m;unset($bad['indexes']['uq_idempotency_key']);
p6ok(!YBY_Mail_V2_P6_Outbox_Plan::schema_matches($bad),'dedup index absent blocked');
$bad=$m;$bad['columns']['payload_ref']='TEXT NOT NULL';
p6ok(!YBY_Mail_V2_P6_Outbox_Plan::schema_matches($bad),'type drift blocked');
$bad=$m;$bad['columns']['to_address']='TEXT';
p6ok(!YBY_Mail_V2_P6_Outbox_Plan::schema_matches($bad),'PII column blocked');
$gate=array('capability_verified'=>true,'nonce_verified'=>true,'schema_verified'=>true,
 'read_only_connection_verified'=>true,'isolated_environment_verified'=>true);
$q=YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',2,20,$gate);
p6ok($q['status']==='read_only_plan'&&!$q['can_execute']&&!$q['mutations_allowed'],'SELECT only and inert');
p6ok($q['rows_sql_params']===array(20,20),'numeric pagination binds');
p6ok($q['data_status']==='not_queried','never fake data');
p6ok(count($q['columns'])===10,'ten allowlisted fields');
foreach(array('email','recipient','subject','body','payload_ref','event_hash',
 'consent_snapshot_hash','lease_token_hash','smtp_response') as $secret){
 p6ok(!in_array($secret,$q['columns'],true),'not selected '.$secret);
}
p6ok(str_contains($q['rows_sql_template'],'LIMIT %d OFFSET %d'),'prepared limit/offset');
p6ok(str_contains($q['total_sql_template'],'COUNT(*)'),'whole population total');
p6ok(str_contains($q['counts_sql_template'],'GROUP BY state'),'whole population states');
$q=YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','failures',1,50,$gate);
p6ok(str_contains($q['rows_sql_template'],"'uncertain'")&&str_contains($q['total_sql_template'],"'uncertain'"),'uncertain triage');
p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','overview',1,10,$gate)['status']==='read_only_plan','overview');
foreach(array('logs','health','transport','send','enqueue','retry') as $view){
 p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_',$view,1,20,$gate)['status']==='blocked','no fake log/write '.$view);
}
foreach(array('capability_verified','nonce_verified') as $key){
 $bad=$gate;$bad[$key]=false;
 p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',1,20,$bad)['reason']==='auth_gate','missing auth '.$key);
}
foreach(array('schema_verified','read_only_connection_verified','isolated_environment_verified') as $key){
 $bad=$gate;$bad[$key]=false;
 p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',1,20,$bad)['reason']==='source_gate','missing source '.$key);
}
p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',0,20,$gate)['reason']==='invalid_query','page 0 refused');
p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',1001,20,$gate)['reason']==='invalid_query','huge offset refused');
p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp_','queue',1,999,$gate)['reason']==='invalid_query','oversize page refused');
p6ok(YBY_Mail_V2_P6_Outbox_Plan::readonly_query('wp;','queue',1,20,$gate)['reason']==='invalid_query','unsafe table refused');
foreach(array('enqueue','delete','retry','create_table','send') as $action){
 p6ok(YBY_Mail_V2_P6_Outbox_Plan::can_write($action,array('owner_approved'=>true))===false,'write denied '.$action);
}
$source=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p6-outbox-plan.php');
p6ok(!preg_match('/\b(?:wp_mail|add_action|add_filter|dbDelta|mysqli_query|as_enqueue_async_action|as_schedule_single_action|update_option)\s*\(/i',$source),'no code execution hooks');
echo 'MAIL_P6_OUTBOX_PLAN_PASS assertions='.$n."\n";
