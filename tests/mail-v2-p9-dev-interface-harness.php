<?php
/** P9-6 source-only branch-aware interface safety harness (no runtime). */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-integration-plan.php';
$n=0;
function ipok($yes,$reason){global $n;++$n;if(!$yes)throw new RuntimeException('P9_INTEGRATION_FAIL:'.$reason);}
$heads=array('p9'=>str_repeat('a',40),'p8'=>str_repeat('b',40),'v22'=>str_repeat('c',40));
$gate=array('synthetic_only'=>true,'owner_deploy_approved'=>false,
    'smtp_enabled'=>false,'worker_enabled'=>false);
$states=array_fill_keys(YBY_Mail_V2_P9_Integration_Plan::STATES,0);
$states['queued']=98;$states['leased']=1;$states['uncertain']=1;
$health=array('status'=>'synthetic_snapshot','total'=>100,'states'=>$states,
    'contains_pii'=>false,'event_log_verified'=>false,'mail_sent'=>false);
$r=YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$gate);
ipok($r['status']==='source_only_plan','baseline-pass');
ipok($r['total']===100 && $r['uncertain_hold']===1,'counts');
ipok($r['can_deploy']===false && $r['can_send']===false &&
    $r['can_write']===false,'all-write-gates-off');
ipok($r['ui_owner']==='v22_plus_p8_existing_email_tabs' &&
    $r['p8_readonly_unchanged']===true,'no-duplicate-ui');
ipok($r['event_history_available']===false,'no-fake-audit-history');
ipok($r['owner_code_uat_required']===true &&
    $r['dev_writer_provisioning_requires_new_authorization']===true,'owner-gates');
$changed=$heads;$changed['v22']=str_repeat('d',40);
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$changed,$heads,$gate)['reason']===
    'head_drift_diff_required','v22-drift-block');
$changed=$heads;$changed['p8']=str_repeat('d',40);
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$changed,$heads,$gate)['reason']===
    'head_drift_diff_required','p8-drift-block');
$changed=$heads;$changed['p9']=str_repeat('d',40);
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$changed,$heads,$gate)['reason']===
    'head_drift_diff_required','p9-drift-block');
$invalid=$gate;$invalid['owner_deploy_approved']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$invalid)['status']===
    'blocked','unapproved-deployment');
$invalid=$gate;$invalid['smtp_enabled']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$invalid)['status']===
    'blocked','smtp-not-allowed');
$invalid=$gate;$invalid['worker_enabled']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$invalid)['status']===
    'blocked','worker-not-allowed');
$invalid=$gate;$invalid['synthetic_only']=false;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($health,$heads,$heads,$invalid)['status']===
    'blocked','real-data-not-allowed');
$bad=$health;$bad['contains_pii']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['status']===
    'blocked','pii-denied');
$bad=$health;$bad['mail_sent']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['status']===
    'blocked','sending-denied');
$bad=$health;$bad['event_log_verified']=true;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['status']===
    'blocked','fake-append-log-denied');
$bad=$health;$bad['states']['queued']=99;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['reason']===
    'count_mismatch','count-conservation');
$bad=$health;$bad['states']['retry_exhausted']=2;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['reason']===
    'count_mismatch','exhausted-subset');
$bad=$health;$bad['states']['queued']=-1;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['reason']===
    'invalid_count','negative-count');
$bad=$health;$bad['states']['customer_email']=1;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['status']===
    'blocked','unexpected-key');
$bad=$health;$bad['total']=10001;
ipok(YBY_Mail_V2_P9_Integration_Plan::preview($bad,$heads,$heads,$gate)['status']===
    'blocked','lab-cap');
$source=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-integration-plan.php');
ipok(!preg_match('/\\b(?:wp_mail|add_action|dbDelta|wp_enqueue_style|as_schedule_single_action)\\s*\\(/i',$source),
    'no-wordpress-hooks');
echo "MAIL_P9_DEV_INTERFACE_SOURCE_ONLY_PASS assertions=$n runtime=off DB=none mail=0\\n";
