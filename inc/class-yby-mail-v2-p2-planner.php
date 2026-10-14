<?php
/**
 * Mail OS V2 P2: offline-only, side-effect-free scheduler/rate/lease proposals.
 * No WordPress hooks, Action Scheduler API calls, SQL, credentials or mail.
 * Production worker remains disabled by the P1 governance contract.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P2_Planner {
    const GROUP = 'andy_core_mail_v2';
    const HOOK = 'andy_core_mail_v2_tick'; // Documentation only; never registered.

    /**
     * Dry-run eligible dispatch with bounded priority, per-sender tokens and
     * inflight caps. Capacity inputs must be separately verified before any
     * future runtime usage; synthetic_only keeps this API offline in P2.
     * Invalid queue input fails the ENTIRE plan closed rather than skipping it.
     */
    public static function plan( $rows, $budgets, $now, $config ) {
        $deny = static function ( $reason ) {
            return array( 'status' => 'blocked', 'reason' => $reason, 'claims' => array() );
        };
        if ( ! is_array( $config ) || true !== ( $config['synthetic_only'] ?? false ) ) {
            return $deny( 'synthetic_only' );
        }
        if ( true !== ( $config['health_ok'] ?? false ) ) {
            return $deny( 'health_gate' );
        }
        if ( ! is_int( $now ) || $now < 0 || ! is_array( $rows ) || ! is_array( $budgets ) ||
             ! isset( $config['max_batch'], $config['critical_reserve'] ) ||
             ! is_int( $config['max_batch'] ) || $config['max_batch'] < 1 || $config['max_batch'] > 50 ||
             ! is_int( $config['critical_reserve'] ) || $config['critical_reserve'] < 0 ||
             $config['critical_reserve'] > $config['max_batch'] || count( $rows ) > 1000 ) {
            return $deny( 'invalid_plan_input' );
        }
        foreach ( $budgets as $sender => $budget ) {
            if ( ! self::slug( $sender ) || ! is_array( $budget ) ||
                 ! array_key_exists( 'tokens', $budget ) || ! is_int( $budget['tokens'] ) || $budget['tokens'] < 0 ||
                 ! isset( $budget['max_inflight'], $budget['inflight'], $budget['cooldown_until'] ) ||
                 ! is_int( $budget['max_inflight'] ) || $budget['max_inflight'] < 1 || $budget['max_inflight'] > 50 ||
                 ! is_int( $budget['inflight'] ) || $budget['inflight'] < 0 ||
                 ! is_int( $budget['cooldown_until'] ) || $budget['cooldown_until'] < 0 ||
                 'synthetic_verified' !== ( $budget['source'] ?? null ) ) {
                return $deny( 'invalid_sender_budget' );
            }
        }
        $seen_keys = array();
        $seen_ids = array();
        $ready = array();
        foreach ( $rows as $row ) {
            if ( ! self::valid_row( $row ) ) { return $deny( 'invalid_row' ); }
            if ( isset( $seen_keys[ $row['idempotency_key'] ] ) || isset( $seen_ids[ $row['opaque_id'] ] ) ) {
                return $deny( 'duplicate_identity' );
            }
            $seen_keys[ $row['idempotency_key'] ] = true;
            $seen_ids[ $row['opaque_id'] ] = true;
            if ( ! isset( $budgets[ $row['sender_key'] ] ) ) { return $deny( 'unverified_sender_budget' ); }
            if ( ( 'queued' === $row['state'] || 'deferred' === $row['state'] ) &&
                 $row['next_attempt_at'] <= $now && $row['attempt'] < $row['max_attempts'] ) {
                $ready[] = $row;
            }
        }
        // Deterministic order inside a sender lane. Fairness chooses the sender
        // with fewest selected claims *within each priority*, not just oldest.
        usort( $ready, static function ( $a, $b ) {
            return ( $a['priority'] <=> $b['priority'] ) ?:
                ( $a['next_attempt_at'] <=> $b['next_attempt_at'] ) ?:
                strcmp( $a['opaque_id'], $b['opaque_id'] );
        } );
        $selected = array();
        $counts = array();
        $taken = array();
        $noncritical = 0;
        $noncritical_cap = $config['max_batch'] - $config['critical_reserve'];
        while ( count( $selected ) < $config['max_batch'] ) {
            $choice = null;
            $best = null;
            foreach ( $ready as $row ) {
                $id = $row['opaque_id'];
                if ( isset( $taken[ $id ] ) ) { continue; }
                $sender = $row['sender_key'];
                $budget = $budgets[ $sender ];
                $used = $counts[ $sender ] ?? 0;
                if ( $budget['cooldown_until'] > $now || $budget['tokens'] <= $used ||
                     $budget['inflight'] + $used >= $budget['max_inflight'] ||
                     ( $row['priority'] !== 0 && $noncritical >= $noncritical_cap ) ) { continue; }
                $score = array( $row['priority'], $used, $row['next_attempt_at'], $id );
                if ( null === $best || self::score_before( $score, $best ) ) {
                    $best = $score;
                    $choice = $row;
                }
            }
            if ( null === $choice ) { break; }
            $sender = $choice['sender_key'];
            $counts[ $sender ] = ( $counts[ $sender ] ?? 0 ) + 1;
            $taken[ $choice['opaque_id'] ] = true;
            if ( $choice['priority'] !== 0 ) { ++$noncritical; }
            $selected[] = array(
                'opaque_id' => $choice['opaque_id'],
                'expected_version' => $choice['state_version'],
                'expected_state' => $choice['state'],
                'next_state' => 'leased',
                'action_group' => self::GROUP,
                'priority' => $choice['priority'],
            );
        }
        return array( 'status' => 'dry_run', 'reason' => 'synthetic_only', 'claims' => $selected );
    }

    /** Return an inert optimistic-CAS proposal, NOT a database mutation. */
    public static function lease_proposal( $claim, $now, $ttl_seconds ) {
        if ( ! is_array( $claim ) || ! self::digest( $claim['opaque_id'] ?? null ) ||
             ! is_int( $claim['expected_version'] ?? null ) || $claim['expected_version'] < 0 ||
             ! in_array( $claim['expected_state'] ?? null, array( 'queued', 'deferred' ), true ) ||
             'leased' !== ( $claim['next_state'] ?? null ) ||
             self::GROUP !== ( $claim['action_group'] ?? null ) ||
             ! is_int( $now ) || $now < 0 || ! is_int( $ttl_seconds ) ||
             $ttl_seconds < 30 || $ttl_seconds > 300 || $now > PHP_INT_MAX - $ttl_seconds ) {
            return null;
        }
        return array( 'opaque_id' => $claim['opaque_id'],
            'where_version' => $claim['expected_version'],
            'where_state' => $claim['expected_state'],
            'set_version' => $claim['expected_version'] + 1,
            'set_state' => 'leased',
            'lease_expires_at' => $now + $ttl_seconds,
            'action_group' => self::GROUP );
    }

    /**
     * An expired sending lease is UNCERTAIN, never automatically requeued.
     * Expired leased-but-not-started items need explicit trustworthy evidence.
     */
    public static function recover_expired( $state, $lease_expires_at, $now, $proved_never_sent ) {
        if ( ! in_array( $state, array( 'leased', 'sending' ), true ) ||
             ! is_int( $lease_expires_at ) || ! is_int( $now ) ||
             $lease_expires_at < 0 || $now < $lease_expires_at ) { return null; }
        if ( 'sending' === $state ) { return 'uncertain'; }
        return true === $proved_never_sent ? 'deferred' : 'uncertain';
    }

    /** Reject all sensitive/malformed queue payloads, including extra fields. */
    private static function valid_row( $row ) {
        if ( ! is_array( $row ) ) { return false; }
        $allowed = array( 'opaque_id', 'idempotency_key', 'sender_key', 'channel',
            'priority', 'state', 'state_version', 'attempt', 'max_attempts', 'next_attempt_at' );
        if ( count( $row ) !== count( $allowed ) || array_diff( array_keys( $row ), $allowed ) ||
             ! self::digest( $row['opaque_id'] ?? null ) ||
             ! self::digest( $row['idempotency_key'] ?? null ) ||
             ! self::slug( $row['sender_key'] ?? null ) ||
             ! is_string( $row['channel'] ?? null ) ||
             ! array_key_exists( $row['channel'], YBY_Mail_V2_Contract::CHANNEL_PRIORITY ) ||
             ! is_int( $row['priority'] ) ||
             $row['priority'] !== YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $row['channel'] ] ||
             ! in_array( $row['state'], YBY_Mail_V2_Contract::STATES, true ) ||
             ! is_int( $row['state_version'] ) || $row['state_version'] < 0 ||
             ! is_int( $row['attempt'] ) || $row['attempt'] < 0 ||
             ! is_int( $row['max_attempts'] ) || $row['max_attempts'] < 1 || $row['max_attempts'] > 5 ||
             ! is_int( $row['next_attempt_at'] ) || $row['next_attempt_at'] < 0 ) { return false; }
        return true;
    }
    private static function digest( $v ) {
        return is_string( $v ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/D', $v );
    }
    private static function slug( $v ) {
        return is_string( $v ) && 1 === preg_match( '/\A[a-z][a-z0-9_]{0,47}\z/D', $v );
    }
    private static function score_before( $a, $b ) {
        foreach ( $a as $i => $value ) {
            $cmp = is_int( $value ) ? $value <=> $b[ $i ] : strcmp( $value, $b[ $i ] );
            if ( $cmp < 0 ) { return true; }
            if ( $cmp > 0 ) { return false; }
        }
        return false;
    }
}
