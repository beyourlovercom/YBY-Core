<?php
/**
 * Mail OS P9-6 standalone source-only integration contract for P8/V2.2.
 * No WordPress includes, no database connectors, no HTML/CSS or sender code.
 * Never registered in any plugin bootstrap or shipped into Dev by this PR.
 */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P9_Integration_Plan {
    const VERSION = 'mail_p9_health_contract_v1';
    const STATES = array('queued','leased','sending','deferred','uncertain',
        'accepted_by_provider','permanent_failed','cancelled','retry_exhausted');

    /**
     * Draft a versioned, non-executable DTO for an already verified synthetic
     * health snapshot. Every branch HEAD must be independently read from GitHub.
     * A mismatch halts planning, rather than composing divergent PHP/CSS.
     */
    public static function preview($health,$current_heads,$expected_heads,$gate) {
        $deny=static function($why){return array('status'=>'blocked','reason'=>$why,
            'can_deploy'=>false,'can_send'=>false,'can_write'=>false);};
        if(!is_array($gate)||array_keys($gate)!==array(
            'synthetic_only','owner_deploy_approved','smtp_enabled','worker_enabled')||
            $gate['synthetic_only']!==true||$gate['owner_deploy_approved']!==false||
            $gate['smtp_enabled']!==false||$gate['worker_enabled']!==false) {
            return $deny('source_only_gate');
        }
        $keys=array('p9','p8','v22');
        foreach(array($current_heads,$expected_heads) as $heads) {
            if(!is_array($heads)||array_keys($heads)!==$keys){return $deny('head_contract');}
            foreach($heads as $sha){
                if(!is_string($sha)||!preg_match('/\\A[a-f0-9]{40}\\z/D',$sha)){
                    return $deny('head_contract');
                }
            }
        }
        if($current_heads!==$expected_heads){return $deny('head_drift_diff_required');}
        if(!is_array($health)||array_keys($health)!==array(
                'status','total','states','contains_pii','event_log_verified','mail_sent')||
            $health['status']!=='synthetic_snapshot'||$health['contains_pii']!==false||
            $health['event_log_verified']!==false||$health['mail_sent']!==false||
            !is_int($health['total'])||$health['total']<0||$health['total']>10000||
            !is_array($health['states'])||array_keys($health['states'])!==self::STATES){
            return $deny('unverified_health');
        }
        $sum=0;
        foreach($health['states'] as $state=>$n){
            if(!is_int($n)||$n<0||$n>10000){return $deny('invalid_count');}
            if($state!=='retry_exhausted')$sum+=$n;
        }
        if($sum!==$health['total']||
            $health['states']['retry_exhausted']>$health['states']['deferred']){
            return $deny('count_mismatch');
        }
        return array('status'=>'source_only_plan','version'=>self::VERSION,
            'source'=>'p9_isolated_synthetic_lab',
            'read_target'=>'separate_future_dev_writer_read_replica',
            'ui_owner'=>'v22_plus_p8_existing_email_tabs',
            'p8_readonly_unchanged'=>true,
            'total'=>$health['total'],
            'queued'=>$health['states']['queued'],
            'deferred'=>$health['states']['deferred'],
            'uncertain_hold'=>$health['states']['uncertain'],
            'retry_exhausted_hold'=>$health['states']['retry_exhausted'],
            'branch_heads'=>$current_heads,
            'contains_pii'=>false,'event_history_available'=>false,
            'can_deploy'=>false,'can_send'=>false,'can_write'=>false,
            'owner_code_uat_required'=>true,
            'dev_writer_provisioning_requires_new_authorization'=>true);
    }
}
