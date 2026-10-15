<?php
/**
 * Mail OS V2 P4 - side-effect-free sender identity + result decision.
 * Existing YBY_Mail_Transport already owns scoped Tencent SMTP;
 * this class NEVER invokes it, hooks wp_mail, reads env, or chooses credentials.
 * Do not interpret provider acceptance as successful inbox delivery.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P4_Routing {
    const CHANNEL_SENDER = array(
        'critical_system' => 'system_tx',
        'account' => 'system_tx',
        'newsletter' => 'newsletter_bulk',
        'campaign' => 'newsletter_bulk',
        'low_priority' => 'newsletter_bulk',
    );
    const TRANSPORTS = array( 'existing_wp_mail', 'tencent_exmail_scoped' );
    const SIGNALS = array(
        'provider_ack' => array( 'to' => 'accepted_by_provider', 'proof' => 'provider_ack',
            'diagnostic' => 'provider_accepted' ),
        'definite_preacceptance_failure' => array( 'to' => 'deferred',
            'proof' => 'definite_preacceptance_failure', 'diagnostic' => 'preacceptance_reject' ),
        'permanent_failure' => array( 'to' => 'permanent_failed',
            'proof' => 'permanent_failure', 'diagnostic' => 'permanent_reject' ),
        'acceptance_unknown' => array( 'to' => 'uncertain',
            'proof' => 'acceptance_unknown', 'diagnostic' => 'acceptance_unknown' ),
    );
    /** This describes an isolated proposed route, not authorization to send. */
    public static function route( $channel, $sender_key, $catalog, $policy ) {
        $blocked = static function ( $reason ) {
            return array( 'status' => 'blocked', 'reason' => $reason, 'execute' => false );
        };
        if ( ! is_array( $policy ) || true !== ( $policy['synthetic_only'] ?? false ) ||
             true !== ( $policy['owner_identity_reviewed'] ?? false ) ) {
            return $blocked( 'identity_policy_gate' );
        }
        if ( ! is_string( $channel ) || ! isset( self::CHANNEL_SENDER[ $channel ] ) ||
             ! is_string( $sender_key ) || self::CHANNEL_SENDER[ $channel ] !== $sender_key ) {
            return $blocked( 'sender_channel_mismatch' );
        }
        if ( ! is_array( $catalog ) || ! isset( $catalog[ $sender_key ] ) ||
             ! is_array( $catalog[ $sender_key ] ) ) { return $blocked( 'sender_not_registered' ); }
        $identity = $catalog[ $sender_key ];
        if ( count( $identity ) !== 3 || array_diff( array_keys( $identity ),
                array( 'transport', 'verified', 'source' ) ) ||
             ! in_array( $identity['transport'], self::TRANSPORTS, true ) ||
             true !== $identity['verified'] ||
             'synthetic_owner_review' !== $identity['source'] ) {
            return $blocked( 'sender_not_verified' );
        }
        return array( 'status' => 'dry_run', 'channel' => $channel,
            'sender_key' => $sender_key, 'transport' => $identity['transport'],
            'execute' => false, 'fallback' => false, 'credentials_loaded' => false );
    }

    /**
     * Classify a synthetic, independently attested provider outcome.
     * Does not retry, send, call SMTP or accept a raw exception as proof.
     * A timeout with unknown acceptance must become uncertain.
     */
    public static function classify( $signal, $evidence, $from = 'sending' ) {
        $deny = static function ( $reason ) {
            return array( 'status' => 'blocked', 'reason' => $reason, 'execute' => false );
        };
        if ( ! is_string( $signal ) || ! isset( self::SIGNALS[ $signal ] ) ||
             ! is_array( $evidence ) ||
             array_keys( $evidence ) !== array( 'synthetic_only', 'signal_verified', 'acceptance_known' ) ||
             true !== $evidence['synthetic_only'] || true !== $evidence['signal_verified'] ||
             ! is_bool( $evidence['acceptance_known'] ) || 'sending' !== $from ) {
            return $deny( 'unverified_signal' );
        }
        $known = in_array( $signal, array( 'provider_ack',
            'definite_preacceptance_failure', 'permanent_failure' ), true );
        if ( $known !== $evidence['acceptance_known'] ) { return $deny( 'contradictory_evidence' ); }
        $spec = self::SIGNALS[ $signal ];
        if ( ! YBY_Mail_V2_Contract::permits_transition(
             $from, $spec['to'], array( $spec['proof'] => true ) ) ) {
            return $deny( 'transition_blocked' );
        }
        return array( 'status' => 'dry_run', 'from' => $from, 'to' => $spec['to'],
            'diagnostic_code' => $spec['diagnostic'], 'execute' => false,
            'retry_authorized' => false,
            'delivery_proven' => false,
            'recipient_exposed' => false,
        );
    }
}
