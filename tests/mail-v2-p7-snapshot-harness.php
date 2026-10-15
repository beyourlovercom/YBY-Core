<?php
/** Offline contract checks for the MySQL-generated synthetic UAT snapshot. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p3-visibility.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
$path=__DIR__.'/fixtures/mail-v2-p7-mysql-verified-snapshot.json';
$payload=json_decode(file_get_contents($path),true);
$n=0;
function p7check($yes,$what){global $n;++$n;if(!$yes)throw new RuntimeException('MAIL_P7_SNAPSHOT_FAIL:'.$what);}
p7check(is_array($payload),'JSON structure');
p7check(($payload['schema']??null)==='mail-v2-p7-mysql8-synthetic-snapshot-v1','snapshot schema');
p7check(($payload['source']??null)==='isolated_mysql_8_4_point_in_time','isolated provenance');
p7check(($payload['snapshot_not_live']??null)===true,'MUST label not live');
p7check(($payload['mysql_network_disabled']??null)===true,'private no TCP');
p7check(($payload['email_sent']??null)===false&&($payload['worker_enabled']??null)===false,'no runtime sends');
p7check(($payload['total']??null)===100&&count($payload['rows']??array())===100,'exact 100');
$states=YBY_Mail_V2_Contract::STATES;
p7check(array_keys($payload['counts'])===$states,'all state keys');
p7check(array_sum($payload['counts'])===100,'consistent state totals');
p7check(($payload['counts']['deferred']+$payload['counts']['permanent_failed']+$payload['counts']['uncertain'])===60,'60 failures');
$ids=array();
foreach($payload['rows'] as $r){
 p7check(YBY_Mail_V2_P3_Visibility::valid_row($r),'P3 row contract');
 p7check(!isset($ids[$r['opaque_id']]),'unique opaque row');
 $ids[$r['opaque_id']]=true;
}
$ctx=array('capability_verified'=>true,'nonce_verified'=>true,'source'=>'isolated_fixture');
$ins=YBY_Mail_V2_P3_Visibility::inspect($payload['rows'],$ctx,'failures',1,20);
p7check($ins['status']==='read_only'&&$ins['total']===60,'P3 failure projection');
p7check($ins['rows'][0]['priority']<=2,'priority range');
p7check(hash_file('sha256',$path)==='aa10d6ee5a12ecfdeb6ac580f464a6fe75acea1e89ce217464ba7ed7f15e04c4','evidence byte integrity');
echo 'MAIL_P7_MYSQL_SNAPSHOT_PASS assertions='.$n.' mysql_fixture=100 failures=60 sha256_match=yes'."\n";
