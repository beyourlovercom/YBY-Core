<?php
/** P9-6 bridge UAT: 102-row *synthetic* reader DTO, no WP, MySQL or email. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-integration-plan.php';
$n=0;
function bridge_ok($yes,$why){global $n;++$n;
    if(!$yes)throw new RuntimeException('MAIL_P9_BRIDGE_FAIL_'.$why);
}
$expected=array('p9'=>'445365cd0cf43d856bf9ab7ef285593749e070a6',
    'p8'=>'803bfcc15f318cf6a6eda5e769080593c0eb5bf7',
    'v22'=>'faec2467277216872487a42744b9fa4b7a2779cb');
$states=array_fill_keys(YBY_Mail_V2_P9_Integration_Plan::STATES,0);
$states['queued']=98;$states['leased']=1;$states['uncertain']=2;
$states['deferred']=1;$states['retry_exhausted']=1; // subset of deferred
$health=array('status'=>'synthetic_snapshot','total'=>102,'states'=>$states,
    'contains_pii'=>false,'event_log_verified'=>false,'mail_sent'=>false);
$gate=array('synthetic_only'=>true,'owner_deploy_approved'=>false,
    'smtp_enabled'=>false,'worker_enabled'=>false);
$r=YBY_Mail_V2_P9_Integration_Plan::preview($health,$expected,$expected,$gate);
bridge_ok($r['status']==='source_only_plan','baseline');
bridge_ok($r['total']===102&&$r['queued']===98,'readback_counts');
bridge_ok($r['uncertain_hold']===2&&$r['retry_exhausted_hold']===1,
    'two_uncertain_and_one_exhausted');
bridge_ok($r['can_deploy']===false&&$r['can_write']===false&&
    $r['can_send']===false,'fail_closed');
bridge_ok($r['event_history_available']===false &&
    $r['p8_readonly_unchanged']===true,'no_false_proof');
bridge_ok($r['ui_owner']==='v22_plus_p8_existing_email_tabs',
    'existing_ui_only');
foreach(array('p9','p8','v22') as $key) {
    $heads=$expected;$heads[$key]=str_repeat('a',40);
    bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
        $health,$heads,$expected,$gate)['reason']==='head_drift_diff_required',
        'exact_head_drift_'.$key);
}
foreach(array('total'=>101,'contains_pii'=>true,
    'event_log_verified'=>true,'mail_sent'=>true) as $field=>$invalid){
    $bad=$health;$bad[$field]=$invalid;
    bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
        $bad,$expected,$expected,$gate)['status']==='blocked',
        'reject_false_'.$field);
}
$bad=$health;$bad['states']['deferred']=0;
bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
    $bad,$expected,$expected,$gate)['status']==='blocked','deferred_subset');
$bad=$health;$bad['states']['retry_exhausted']=2;
bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
    $bad,$expected,$expected,$gate)['status']==='blocked','exhausted_subset');
$bad=$gate;$bad['worker_enabled']=true;
bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
    $health,$expected,$expected,$bad)['status']==='blocked','no_worker');
$bad=$gate;$bad['smtp_enabled']=true;
bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
    $health,$expected,$expected,$bad)['status']==='blocked','no_smtp');
$bad=$gate;$bad['owner_deploy_approved']=true;
bridge_ok(YBY_Mail_V2_P9_Integration_Plan::preview(
    $health,$expected,$expected,$bad)['status']==='blocked','no_deploy');
echo 'MAIL_P9_102_SYNTHETIC_BRIDGE_PASS assertions='.$n.
    ' readback=102 ui=existing_v22_p8 privacy=pass dev=off mail=0'.PHP_EOL;
