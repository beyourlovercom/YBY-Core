<?php
/** P9 synthetic-only transport outcome and lease recovery; zero I/O. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-governance.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p2-planner.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
$n=0;
function ok9($yes,$why){global $n;++$n;if(!$yes)throw new RuntimeException('MAIL_P9_MOCK_RECOVERY_FAIL:'.$why);}
$catalog=array(
 'system_tx'=>array('transport'=>'existing_wp_mail','verified'=>true,'source'=>'synthetic_owner_review'),
 'newsletter_bulk'=>array('transport'=>'tencent_exmail_scoped','verified'=>true,'source'=>'synthetic_owner_review')
);
$policy=array('synthetic_only'=>true,'owner_identity_reviewed'=>true);
foreach(YBY_Mail_V2_P4_Routing::CHANNEL_SENDER as $channel=>$sender){
 $r=YBY_Mail_V2_P4_Routing::route($channel,$sender,$catalog,$policy);
 ok9($r['status']==='dry_run' && $r['execute']===false,'mock-only routing '.$channel);
 ok9($r['sender_key']===$sender && $r['fallback']===false,'sender scoped no fallback '.$channel);
}
$disabled=$policy;$disabled['synthetic_only']=false;
ok9(YBY_Mail_V2_P4_Routing::route('campaign','newsletter_bulk',$catalog,$disabled)['status']==='blocked','no live catalog route');
ok9(YBY_Mail_V2_P4_Routing::route('campaign','system_tx',$catalog,$policy)['status']==='blocked','no campaign on critical sender');
$unverified=$catalog;$unverified['newsletter_bulk']['verified']=false;
ok9(YBY_Mail_V2_P4_Routing::route('campaign','newsletter_bulk',$unverified,$policy)['status']==='blocked','no unverified identity');
foreach(array(
 'provider_ack'=>array('to'=>'accepted_by_provider','known'=>true),
 'definite_preacceptance_failure'=>array('to'=>'deferred','known'=>true),
 'permanent_failure'=>array('to'=>'permanent_failed','known'=>true),
 'acceptance_unknown'=>array('to'=>'uncertain','known'=>false)
) as $signal=>$expected){
 $e=array('synthetic_only'=>true,'signal_verified'=>true,'acceptance_known'=>$expected['known']);
 $r=YBY_Mail_V2_P4_Routing::classify($signal,$e);
 ok9($r['status']==='dry_run' && $r['to']===$expected['to'],'typed outcome '.$signal);
 ok9(!$r['execute'] && !$r['retry_authorized'] && !$r['delivery_proven'],'no automatic send/retry/delivery '.$signal);
 $invalid=$e;$invalid['acceptance_known']=!$expected['known'];
 ok9(YBY_Mail_V2_P4_Routing::classify($signal,$invalid)['status']==='blocked','contradictory evidence denied '.$signal);
}
ok9(YBY_Mail_V2_P2_Planner::recover_expired('sending',100,100,false)==='uncertain','expired sending HOLD uncertain');
ok9(YBY_Mail_V2_P2_Planner::recover_expired('sending',100,100,true)==='uncertain','cannot assert never-sent after sending');
ok9(YBY_Mail_V2_P2_Planner::recover_expired('leased',100,110,false)==='uncertain','leased without transmission proof HOLD');
ok9(YBY_Mail_V2_P2_Planner::recover_expired('leased',100,110,true)==='deferred','lease safe only with proof');
ok9(YBY_Mail_V2_P2_Planner::recover_expired('leased',100,99,true)===null,'not yet expired no recovery');
ok9(YBY_Mail_V2_P2_Planner::recover_expired('accepted_by_provider',100,110,true)===null,'never requeue accepted');
ok9(YBY_Mail_V2_Governance::OUTBOUND_ENABLED===false && YBY_Mail_V2_Governance::QUEUE_ENABLED===false,'P1 runtime gates still off');
ok9(!function_exists('wp_mail') && !function_exists('as_enqueue_async_action'),'no mail/ActionScheduler functions loaded');
echo 'MAIL_P9_MOCK_TRANSPORT_AND_RECOVERY_PASS assertions='.$n.' real_mail=0 worker=off'."\n";
