<?php
/**
 * Mail P9 isolated audit V2: inert event manifest ONLY.
 *
 * Never loaded in WordPress; no schema execution, no credentials and no SMTP.
 * Future durable history requires a NEW owner-approved isolated database.
 * Current P9 20-column Outbox/P8 Dev reader must remain unchanged.
 */
if (!defined('ABSPATH')) { exit; }
final class YBY_Mail_V2_P9_Audit_Contract {
    const VERSION = 'mail_p9_append_only_audit_v2_proposal';
    const EVENTS = array(
        'enqueued' => array('none', 'queued'),
        'claimed' => array('queued', 'leased'),
        'preacceptance_deferred' => array('leased', 'deferred'),
        'uncertain_hold' => array('leased', 'uncertain'),
        'retry_exhausted' => array('leased', 'deferred'),
    );
    const DIAGNOSTICS = array(
        'enqueued' => 'none',
        'claimed' => 'none',
        'preacceptance_deferred' => 'preacceptance_reject',
        'uncertain_hold' => 'acceptance_unknown',
        'retry_exhausted' => 'retry_exhausted',
    );

    /**
     * Supply only synthetic, nonidentifying metadata in exact key order.
     * Return a *proposal*, never a query or authorization to INSERT.
     */
    public static function proposed_event($data) {
        $deny=static function($reason){
            return array('status'=>'blocked','reason'=>$reason,'can_write'=>false);
        };
        if (!is_array($data) || array_keys($data)!==array(
            'synthetic_only','opaque_id','state_version','event_code',
            'from_state','to_state','diagnostic_code','prev_event_hash')
            || $data['synthetic_only']!==true) {
            return $deny('event_contract');
        }
        $event=$data['event_code'];
        if (!is_string($event)||!isset(self::EVENTS[$event])||
            !is_string($data['opaque_id'])||
            !preg_match('/\\A[a-f0-9]{64}\\z/D',$data['opaque_id'])||
            !is_int($data['state_version'])||$data['state_version']<0||
            $data['state_version']>PHP_INT_MAX-1||
            !is_string($data['prev_event_hash'])||
            !preg_match('/\\A[a-f0-9]{64}\\z/D',$data['prev_event_hash'])||
            $data['from_state']!==self::EVENTS[$event][0]||
            $data['to_state']!==self::EVENTS[$event][1]||
            $data['diagnostic_code']!==self::DIAGNOSTICS[$event]) {
            return $deny('transition_or_identity');
        }
        // Digest is a deterministic test proposal, NOT keyed authenticity.
        // Production signing/independent anchoring requires its own design.
        $canonical=implode("\0",array(self::VERSION,$data['opaque_id'],
            (string)$data['state_version'],$event,$data['from_state'],
            $data['to_state'],$data['diagnostic_code'],$data['prev_event_hash']));
        return array('status'=>'review_only',
            'version'=>self::VERSION,'event_code'=>$event,
            'opaque_id'=>$data['opaque_id'],
            'state_version'=>$data['state_version'],
            'from_state'=>$data['from_state'],'to_state'=>$data['to_state'],
            'diagnostic_code'=>$data['diagnostic_code'],
            'prev_event_hash'=>$data['prev_event_hash'],
            'event_hash'=>hash('sha256',$canonical),
            'contains_pii'=>false,'delivery_proven'=>false,
            'tamper_proof'=>false,'can_write'=>false);
    }

    /**
     * No Outbox+audit atomicity without a separately authorized V2 schema,
     * transaction and INSERT-only principal. Always false in current P9.
     */
    public static function can_install_or_activate($configuration=array()) { return false; }
}
