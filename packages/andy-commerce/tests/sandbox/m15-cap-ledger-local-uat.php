<?php
/** Explicit test-only MySQL cap acceptance, never autoload this file. */
if (getenv('AICC_ALLOW_M15_LOCAL_DB_TEST')!=='1'
 ||home_url()!=='http://127.0.0.1:18973'
 ||DB_NAME!=='aicc_commerce_p5_20261023'
 ||wp_get_environment_type()!=='local'
 ||!defined('WP_HTTP_BLOCK_EXTERNAL')||!WP_HTTP_BLOCK_EXTERNAL
 ||!defined('DISABLE_WP_CRON')||!DISABLE_WP_CRON) {
 throw new RuntimeException('M15_NONLOCAL_CAP_TEST_DENIED');
}
require_once getenv('AICC_M15_SOURCE');
$ledger=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$mode=getenv('AICC_M15_MODE') ?: 'single';
$id='m15_20261025_';
if($mode==='concurrent') {
 $id.='concurrent';
 $token='claim_'.getenv('AICC_M15_TOKEN');
 if(!preg_match('/^[A-Za-z0-9_-]{8,64}$/D',$token))
 throw new RuntimeException('M15_INVALID_WORKER_TOKEN');
 echo json_encode(['token'=>$token,'reserve'=>$ledger->reserve($id,$token,3)],JSON_THROW_ON_ERROR),PHP_EOL;
 return;
}
if($mode==='count'){
 echo json_encode(['counts'=>$ledger->counters($id.'concurrent')],JSON_THROW_ON_ERROR),PHP_EOL;
 return;
}
if($mode==='init') {
 foreach($ledger->schema_sql() as $sql){
  if($GLOBALS['wpdb']->query($sql)===false)throw new RuntimeException('ISOLATED_M15_SCHEMA_FAIL');
 }
 echo "M15_LOCAL_SCHEMA_INITIALIZED\n";return;
}
if($mode!=='single')throw new RuntimeException('M15_BAD_MODE');
$id.='simple';
$assert=static function(bool $v,string $reason):void{
 if(!$v) throw new RuntimeException('M15_ASSERT_'.$reason);
};
$a=$ledger->reserve($id,'token_a1_20261025',2);
$assert($a['accepted']&&$a['status']==='reserved','first reservation');
$a2=$ledger->reserve($id,'token_a1_20261025',2);
$assert($a2['idempotent']&&$a2['status']==='reserved','reserve idempotent');
$b=$ledger->reserve($id,'token_b2_20261025',2);
$assert($b['accepted'],'second reservation');
$c=$ledger->reserve($id,'token_c3_20261025',2);
$assert(!$c['accepted']&&$c['status']==='exhausted','cap full');
$counters=$ledger->counters($id);
$assert($counters['held']===2&&$counters['used']===0,'held balance');
$d=$ledger->commit($id,'token_a1_20261025',9000001);
$assert(!$d['idempotent']&&$d['status']==='committed','commit success');
$d2=$ledger->commit($id,'token_a1_20261025',9000001);
$assert($d2['idempotent'],'commit idempotent');
$rejected=false;
try {$ledger->commit($id,'token_a1_20261025',9000002);}
catch(RuntimeException $e){$rejected=true;}
$assert($rejected,'committed order identity cannot change');
$r=$ledger->release($id,'token_b2_20261025');
$assert($r['status']==='released','cancel releases held');
$r2=$ledger->release($id,'token_b2_20261025');
$assert($r2['idempotent'],'release idempotent');
$rejected=false;
try {$ledger->reserve($id,'token_b2_20261025',2);}
catch(RuntimeException $e){$rejected=true;}
$assert($rejected,'released token cannot be reused');
$rejected=false;
try {$ledger->reserve($id,'token_other_20261025',3);}
catch(RuntimeException $e){$rejected=true;}
$assert($rejected,'cap version immutable');
$after=$ledger->counters($id);
$assert($after['max']===2&&$after['used']===1&&$after['held']===0,'committed used persisted');
$allowed=$ledger->reserve($id,'token_d4_20261025',2);
$assert($allowed['accepted'],'one slot still available');
$ledger->release($id,'token_d4_20261025');
$after=$ledger->counters($id);
$assert($after['used']===1&&$after['held']===0,'final counters');
echo json_encode(['result'=>'M15_LOCAL_CAP_SINGLE_PASS','caps'=>$after,'exhausted_when_held'=>true,
 'idempotency'=>true,'order_id_protected'=>true,'release_after_abort'=>true,'lock'=>'per_campaign_sql_advisory',
 'external_funds'=>false],JSON_THROW_ON_ERROR),PHP_EOL;
