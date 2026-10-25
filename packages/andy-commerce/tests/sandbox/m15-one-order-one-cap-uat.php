<?php
if(getenv('AICC_ALLOW_M15_LOCAL_DB_TEST')!=='1'
 ||home_url()!=='http://127.0.0.1:18973'
 ||DB_NAME!=='aicc_commerce_p5_20261023'
 ||wp_get_environment_type()!=='local')
 throw new RuntimeException('NOT_EXPLICIT_M15_LOCAL_TEST');
require_once getenv('AICC_M15_SOURCE');
$l=new Andy_Commerce_M15_Cap_Ledger($GLOBALS['wpdb']);
$c='m15_20261025_simple';$t='token_second_order_test_20261025';
$before=$l->counters($c);
if($before!==['max'=>2,'used'=>1,'held'=>0])throw new RuntimeException('M15_EXISTING_TEST_MISMATCH');
$res=$l->reserve($c,$t,2);
if(!$res['accepted'])throw new RuntimeException('M15_NO_SPACE_AFTER_RELEASE');
$blocked=false;
try{$l->commit($c,$t,9000001);}
catch(RuntimeException $e){$blocked=true;}
if(!$blocked)throw new RuntimeException('M15_DUPLICATE_ORDER_REDEMPTION_ALLOWED');
$middle=$l->counters($c);
if($middle!==['max'=>2,'used'=>1,'held'=>1])
 throw new RuntimeException('M15_SQL_ERROR_FAILED_TO_ROLLBACK_COUNTERS');
$l->release($c,$t);
$after=$l->counters($c);
if($after!==$before)throw new RuntimeException('M15_RELEASE_AFTER_FAILED_COMMIT');
echo json_encode(['status'=>'M15_DUPLICATE_WOO_ORDER_DENIED','after'=>$after,
'unique_campaign_order'=>true,'rollback_on_SQL_failure'=>true],JSON_THROW_ON_ERROR),PHP_EOL;
