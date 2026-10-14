<?php
/** P4 pure offline routing and result classification. Never sends email. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-contract.php';
require dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php';
$t=0;
function ok4($b,$m){global $t;++$t;if(!$b)throw new RuntimeException('MAIL_P4_FAIL:'.$m);}
$catalog=array(
 'system_tx'=>array('transport'=>'existing_wp_mail','verified'=>true,'source'=>'synthetic_owner_review'),
 'newsletter_bulk'=>array('transport'=>'tencent_exmail_scoped','verified'=>true,'source'=>'synthetic_owner_review'),
);
$policy=array('synthetic_only'=>true,'owner_identity_reviewed'=>true);
foreach(YBY_Mail_V2_P4_Routing::CHANNEL_SENDER as $channel=>$sender){
 $route=YBY_Mail_V2_P4_Routing::route($channel,$sender,$catalog,$policy);
 ok4($route['status']==='dry_run','allow channel '.$channel);
 ok4($route['execute']===false&&$route['fallback']===false,'no runtime / fallback '.$channel);
 ok4(!isset($route['from_email'],$route['smtp_password'],$route['host']),'nonsecret route '.$channel);
}
foreach(array('critical_system'=>'newsletter_bulk','campaign'=>'system_tx','newsletter'=>'system_tx') as $ch=>$wrong){
 ok4(YBY_Mail_V2_P4_Routing::route($ch,$wrong,$catalog,$policy)['reason']==='sender_channel_mismatch','cross-mailbox reject '.$ch);
}
$bad=$policy;$bad['synthetic_only']=false;
ok4(YBY_Mail_V2_P4_Routing::route('newsletter','newsletter_bulk',$catalog,$bad)['reason']==='identity_policy_gate','live route blocked');
$bad=$policy;$bad['owner_identity_reviewed']=false;
ok4(YBY_Mail_V2_P4_Routing::route('newsletter','newsletter_bulk',$catalog,$bad)['reason']==='identity_policy_gate','unapproved identity blocked');
$bad=$catalog;$bad['newsletter_bulk']['smtp_password']='SECRET';
ok4(YBY_Mail_V2_P4_Routing::route('newsletter','newsletter_bulk',$bad,$policy)['reason']==='sender_not_verified','credential input rejected');
$bad=$catalog;unset($bad['system_tx']);
ok4(YBY_Mail_V2_P4_Routing::route('critical_system','system_tx',$bad,$policy)['reason']==='sender_not_registered','no silent fallback');
$bad=$catalog;$bad['newsletter_bulk']['verified']=false;
ok4(YBY_Mail_V2_P4_Routing::route('newsletter','newsletter_bulk',$bad,$policy)['reason']==='sender_not_verified','unverified sender blocked');
$signalCases=array(
 'provider_ack'=>'accepted_by_provider',
 'definite_preacceptance_failure'=>'deferred',
 'permanent_failure'=>'permanent_failed',
 'acceptance_unknown'=>'uncertain',
);
foreach($signalCases as $signal=>$state){
 $verified=array('synthetic_only'=>true,'signal_verified'=>true,'acceptance_known'=>$signal!=='acceptance_unknown');
 $res=YBY_Mail_V2_P4_Routing::classify($signal,$verified);
 ok4($res['to']===$state,'signal mapping '.$signal);
 ok4($res['execute']===false&&$res['retry_authorized']===false&&$res['delivery_proven']===false,'no live / resend / delivery proof '.$signal);
 ok4(!isset($res['recipient'],$res['smtp_response']),'no secret diagnostic '.$signal);
}
$known=array('synthetic_only'=>true,'signal_verified'=>true,'acceptance_known'=>true);
$unknown=array('synthetic_only'=>true,'signal_verified'=>true,'acceptance_known'=>false);
ok4(YBY_Mail_V2_P4_Routing::classify('acceptance_unknown',$known)['reason']==='contradictory_evidence','uncertain cannot claim known');
ok4(YBY_Mail_V2_P4_Routing::classify('provider_ack',$unknown)['reason']==='contradictory_evidence','ack requires evidence');
ok4(YBY_Mail_V2_P4_Routing::classify('provider_ack',$known,'uncertain')['reason']==='unverified_signal','cannot auto release uncertain');
$bad=$known;$bad['signal_verified']=false;
ok4(YBY_Mail_V2_P4_Routing::classify('provider_ack',$bad)['reason']==='unverified_signal','unverified ack denied');
$bad=$known;$bad['synthetic_only']=false;
ok4(YBY_Mail_V2_P4_Routing::classify('provider_ack',$bad)['reason']==='unverified_signal','live adapter denied');
$bad=$known;$bad['raw_smtp_response']='secret';
ok4(YBY_Mail_V2_P4_Routing::classify('provider_ack',$bad)['reason']==='unverified_signal','raw response forbidden');
ok4(YBY_Mail_V2_P4_Routing::classify('timeout',$known)['reason']==='unverified_signal','timeout without evidence blocked');
$source=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p4-routing.php');
ok4(!preg_match('/\b(?:wp_mail|add_action|add_filter|as_enqueue_async_action|dbDelta|update_option|insert|smtpconnect)\s*\(/i',$source),'no mail/scheduler/db operations');
echo 'MAIL_P4_ROUTING_PASS assertions='.$t."\n";
