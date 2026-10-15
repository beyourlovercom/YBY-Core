<?php
/** P9-6 Dev topology review. Pure offline synthetic plan, no SQL or WP. */
define('ABSPATH', __DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-dev-topology-gate.php';
$n=0;function gate_ok($condition,$why){global $n;$n++;
    if(!$condition)throw new RuntimeException('P9_TOPOLOGY_FAIL_'.$why);
}
$base=array('source_only'=>true,'site'=>'dev.beyourlover.com',
    'provider_model'=>'private_multirole_mysql',
    'different_from_wordpress_db'=>true,
    'p8_reader_unchanged'=>true,
    'two_distinct_db_identities'=>true,
    'writer_grants_scoped'=>true,
    'reader_select_only'=>true,
    'network_private'=>true,
    'backup_verified'=>true,
    'schema_verified'=>true,
    'owner_dev_writer_approved'=>false,
    'worker_enabled'=>false,
    'smtp_enabled'=>false);
$r=YBY_Mail_V2_P9_Dev_Topology_Gate::assess($base);
gate_ok($r['status']==='architecture_review_only','inert_positive');
gate_ok($r['can_deploy']===false && $r['can_write']===false &&
    $r['can_provision']===false && $r['can_send']===false,
    'never_grants_execution');
gate_ok($r['requires_distinct_owner_approval']===true,
    'owner_gate_retained');
foreach(array('hostinger_one_account_per_db',
    'unknown_host','hostinger_shared_with_same_account') as $provider){
    $s=$base;$s['provider_model']=$provider;
    $o=YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s);
    gate_ok($o['status']==='blocked','reject_provider_'.$provider);
}
foreach(array('different_from_wordpress_db',
    'p8_reader_unchanged','two_distinct_db_identities',
    'writer_grants_scoped','reader_select_only',
    'network_private','backup_verified','schema_verified') as $field){
    $s=$base;$s[$field]=false;
    gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
        'reject_missing_'.$field);
}
foreach(array('worker_enabled','smtp_enabled','owner_dev_writer_approved') as $field){
    $s=$base;$s[$field]=true;
    gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
        'reject_implicit_activation_'.$field);
}
$s=$base;$s['site']='www.beyourlover.com';
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
    'reject_production');
$s=$base;$s['source_only']=false;
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
    'reject_runtime');
$s=$base;$s['password']='sensitive';
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
    'reject_extra_credentials');
$s=$base;unset($s['p8_reader_unchanged']);
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
    'reject_missing_p8_guard');
$s=$base;$s['reader_select_only']='yes';
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='blocked',
    'reject_non_boolean');
$s=$base;$s['provider_model']='reviewed_managed_multirole_mysql';
gate_ok(YBY_Mail_V2_P9_Dev_Topology_Gate::assess($s)['status']==='architecture_review_only',
    'alternate_architecture_reviewable_not_executable');
$src=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-dev-topology-gate.php');
gate_ok(!preg_match('/\\b(?:wp_mail|add_action|as_enqueue_async_action|dbDelta|new PDO|mysqli_connect)\\s*\\(/i',$src),
    'no_side_effects');
echo 'MAIL_P9_DEV_TOPOLOGY_SOURCE_ONLY_PASS assertions='.$n.
    ' provision=off worker=off smtp=off'.PHP_EOL;
