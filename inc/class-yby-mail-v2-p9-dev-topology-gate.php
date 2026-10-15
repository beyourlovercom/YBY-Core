<?php
/**
 * Mail P9-6 DEV writer topology: pure planning guard, never a provisioner.
 * WordPress, SQL connections, SMTP, WP-Cron and actual deployment are absent.
 */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P9_Dev_Topology_Gate {
    public static function assess($s) {
        $deny=static function($why) {
            return array('status'=>'blocked','reason'=>$why,
                'can_provision'=>false,'can_write'=>false,
                'can_deploy'=>false,'can_send'=>false);
        };
        $keys=array('source_only','site','provider_model',
            'different_from_wordpress_db','p8_reader_unchanged',
            'two_distinct_db_identities','writer_grants_scoped',
            'reader_select_only','network_private','backup_verified',
            'schema_verified','owner_dev_writer_approved',
            'worker_enabled','smtp_enabled');
        if(!is_array($s)||array_keys($s)!==$keys){return $deny('exact_input_required');}
        foreach($keys as $key){
            if($key==='site'||$key==='provider_model')continue;
            if(!is_bool($s[$key]))return $deny('invalid_boolean');
        }
        if($s['source_only']!==true||
           $s['site']!=='dev.beyourlover.com'||
           $s['owner_dev_writer_approved']!==false||
           $s['worker_enabled']!==false||
           $s['smtp_enabled']!==false){
            return $deny('source_only_scope');
        }
        if($s['provider_model']==='hostinger_one_account_per_db'){
            return $deny('provider_does_not_support_distinct_reader_writer');
        }
        if(!in_array($s['provider_model'],array(
                'private_multirole_mysql','reviewed_managed_multirole_mysql'
            ),true)){
            return $deny('provider_model_unverified');
        }
        if($s['different_from_wordpress_db']!==true ||
           $s['p8_reader_unchanged']!==true){
            return $deny('business_or_p8_isolation');
        }
        if($s['two_distinct_db_identities']!==true||
           $s['writer_grants_scoped']!==true||
           $s['reader_select_only']!==true){
            return $deny('identity_separation');
        }
        if($s['network_private']!==true){
            return $deny('network_gate');
        }
        if($s['backup_verified']!==true||$s['schema_verified']!==true){
            return $deny('backup_or_schema_gate');
        }
        return array('status'=>'architecture_review_only',
            'reason'=>'independent_owner_scope_and_dev_real_checks_still_required',
            'candidate_provider'=>$s['provider_model'],
            'existing_p8_reader_preserved'=>true,
            'can_provision'=>false,'can_write'=>false,
            'can_deploy'=>false,'can_send'=>false,
            'requires_distinct_owner_approval'=>true,
            'requires_dev_exact_head_ci'=>true,
            'requires_real_dev_screenshot_uat'=>true);
    }
}
