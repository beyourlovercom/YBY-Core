<?php
/** P9-6 grant parser mock matrix: no PDO, network, Dev, SMTP or WP. */
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/inc/class-yby-mail-v2-p9-grant-preflight.php';
$n=0;function gr9($yes,$label){global $n;++$n;
    if(!$yes)throw new RuntimeException('P9_GRANTS_FAIL_'.$label);
}
$base=array('synthetic_only'=>true,'schema'=>'p9_mail_synthetic_uat',
    'writer_user'=>'p9_writer','reader_user'=>'p9_reader',
    'writer_current_user'=>'p9_writer@localhost',
    'reader_current_user'=>'p9_reader@localhost',
    'writer_grants'=>array(
        'GRANT USAGE ON *.* TO `p9_writer`@`localhost`',
        'GRANT SELECT, INSERT, UPDATE ON `p9_mail_synthetic_uat`.* TO `p9_writer`@`localhost`'
    ),
    'reader_grants'=>array(
        'GRANT USAGE ON *.* TO `p9_reader`@`localhost`',
        'GRANT SELECT ON `p9_mail_synthetic_uat`.* TO `p9_reader`@`localhost`'
    ),
    'network_disabled'=>true,'wp_db_distinct'=>true,
    'p8_reader_unchanged'=>true);
$r=YBY_Mail_V2_P9_Grant_Preflight::verify($base);
gr9($r['status']==='synthetic_lab_grants_verified','valid_isolated');
gr9($r['can_provision']===false && $r['can_write']===false &&
    $r['can_deploy']===false && $r['can_send']===false,'inert_only');
gr9($r['real_dev_verified']===false &&
    $r['contains_credentials']===false,'lab_not_dev');
$variants=array();
foreach(array('synthetic_only','network_disabled','wp_db_distinct',
    'p8_reader_unchanged') as $name) {
    $v=$base;$v[$name]=false;$variants['deny_'.$name]=$v;
}
$v=$base;$v['reader_user']=$v['writer_user'];
$variants['reuse_p9_identity']=$v;
$v=$base;$v['writer_current_user']='p9_writer@remote';
$variants['unexpected_host']=$v;
$v=$base;$v['schema']='wordpress_business';
$variants['wrong_schema']=$v;
$v=$base;$v['reader_grants'][]='GRANT INSERT ON `p9_mail_synthetic_uat`.* TO `p9_reader`@`localhost`';
$variants['reader_insert']=$v;
$v=$base;$v['reader_grants'][1]='GRANT SELECT, DELETE HISTORY, SHOW CREATE ROUTINE ON `p9_mail_synthetic_uat`.* TO `p9_reader`@`localhost`';
$variants['p8_hostinger_exception_forbidden']=$v;
$v=$base;$v['writer_grants'][1]='GRANT ALL PRIVILEGES ON `p9_mail_synthetic_uat`.* TO `p9_writer`@`localhost`';
$variants['writer_all_grants']=$v;
$v=$base;$v['writer_grants'][1]='GRANT SELECT, INSERT, UPDATE, DELETE ON `p9_mail_synthetic_uat`.* TO `p9_writer`@`localhost`';
$variants['writer_delete']=$v;
$v=$base;$v['writer_grants'][1]='GRANT SELECT, INSERT, UPDATE ON *.* TO `p9_writer`@`localhost`';
$variants['global_dml']=$v;
$v=$base;$v['reader_grants'][1]='GRANT SELECT ON `wp_orders`.* TO `p9_reader`@`localhost`';
$variants['wrong_reader_schema']=$v;
$v=$base;$v['reader_grants'][0]='GRANT USAGE ON *.* TO `p9_reader`@`%`';
$variants['public_host']=$v;
$v=$base;$v['reader_grants'][1].=' WITH GRANT OPTION';
$variants['grant_option']=$v;
$v=$base;$v['writer_grants']=array($v['writer_grants'][1]);
$variants['usage_missing']=$v;
$v=$base;$v['reader_grants'][]=$v['reader_grants'][1];
$variants['duplicate_privilege']=$v;
$v=$base;$v['unapproved_secret']='secret';
$variants['extra_credential_key']=$v;
$v=$base;$v['reader_grants'][1]="GRANT SELECT ON p9_mail_synthetic_uat.* TO p9_reader@localhost\nGRANT DELETE ON *.*";
$variants['newline_injection']=$v;
foreach($variants as $reason=>$v) {
    $r=YBY_Mail_V2_P9_Grant_Preflight::verify($v);
    gr9($r['status']==='blocked'&&$r['can_send']===false&&
        $r['can_write']===false,$reason);
}
$src=file_get_contents(dirname(__DIR__).'/inc/class-yby-mail-v2-p9-grant-preflight.php');
gr9(!preg_match('/\\b(?:wp_mail|add_action|dbDelta|new PDO|mysqli_connect|as_enqueue_async_action)\\s*\\(/i',$src),
    'no_side_effects');
echo 'MAIL_P9_MYSQL_GRANT_SOURCE_ONLY_PASS assertions='.$n.
    ' writer_scoped=1 reader_select_only=1 dev=off mail=off'.PHP_EOL;
