<?php
/** P9-7 route-ownership and fail-closed tests, no actual SMTP or WP bootstrap. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-smtp-pro-coexistence.php';
$n=0;function co9($ok,$label){global $n;++$n;
  if(!$ok)throw new RuntimeException('P9_COEXISTENCE_FAIL:'.$label);
}
$base=array('synthetic_only'=>true,'worker_enabled'=>false,
  'queue_enabled'=>false,'outbound_enabled'=>false,
  'real_customer_data'=>false,'owner_cutover_approved'=>false,
  'smtp_pro_present'=>true,'p8_readonly_preserved'=>true,
  'v22_ui_preserved'=>true);
$r=YBY_Mail_V2_P9_Coexistence::review($base);
co9($r['status']==='review_only','inert-plan-only');
co9($r['can_activate']===false && $r['can_send']===false &&
   $r['can_register_hooks']===false && $r['can_disable_smtp_pro']===false,
   'activation-always-denied');
co9($r['requires_separate_future_owner_cutover_approval']===true,
  'new-authorization-required');
co9($r['provider_quota_verified']===false &&
    $r['delivery_proven']===false,'cannot-fabricate-provider-evidence');
co9($r['contains_pii']===false && $r['worker_enabled']===false,
    'no-sensitive-runtime');
co9(count($r['routes'])===7,'all-current-routes-covered');
foreach(YBY_Mail_V2_P9_Coexistence::PATHS as $route){
  co9($r['routes'][$route]==='incumbent_unchanged','incumbent-'.$route);
}
foreach(array('synthetic_only','worker_enabled','queue_enabled',
    'outbound_enabled','real_customer_data','owner_cutover_approved',
    'smtp_pro_present','p8_readonly_preserved','v22_ui_preserved') as $field){
  $bad=$base;$bad[$field]=!$base[$field];
  $o=YBY_Mail_V2_P9_Coexistence::review($bad);
  co9($o['status']==='blocked' && $o['can_activate']===false &&
      $o['can_send']===false,'forbid-'.$field);
}
$bad=$base;$bad['new_hook']='allowed';
co9(YBY_Mail_V2_P9_Coexistence::review($bad)['status']==='blocked',
    'reject-additional-hooks');
$bad=$base;$bad['worker_enabled']=1;
co9(YBY_Mail_V2_P9_Coexistence::review($bad)['status']==='blocked',
    'reject-nonboolean-worker');
$bad=$base;unset($bad['smtp_pro_present']);
co9(YBY_Mail_V2_P9_Coexistence::review($bad)['status']==='blocked',
    'reject-missing-incumbent');
$src=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-smtp-pro-coexistence.php');
co9(!preg_match('/\\b(?:wp_mail|add_action|add_filter|dbDelta|as_enqueue_async_action|as_schedule_single_action)\\s*\\(/i',$src),
    'no-global-mail-hook-or-sending');
echo "MAIL_P9_7_COEXISTENCE_REVIEW_PASS assertions=$n incumbent=retained outbound=off worker=off\\n";
