<?php
/**
 * Mail OS V2 P5 isolated load rehearsal.
 * Produces 10,000 purely synthetic opaque rows; P2 plans 10 independent
 * <=1,000-record windows. No WordPress runtime, Outbox, Action Scheduler
 * enqueue, SMTP or data writes. A "planned" claim is NEVER sent.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P5_Stress {
    const FIXTURE_SIZE = 10000;
    const WINDOW_SIZE = 1000;

    public static function fixture() {
        $rows = array();
        for ( $i = 0; $i < self::FIXTURE_SIZE; ++$i ) {
            $channel = 0 === $i % 25 ? 'critical_system'
                : ( 0 === $i % 5 ? 'account' : 'campaign' );
            $rows[] = array(
                'opaque_id' => hash( 'sha256', 'v2-p5-opaque-' . $i ),
                'idempotency_key' => hash( 'sha256', 'v2-p5-idem-' . $i ),
                'sender_key' => 'campaign' === $channel ? 'newsletter_bulk' : 'system_tx',
                'channel' => $channel,
                'priority' => YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $channel ],
                'state' => 'queued', 'state_version' => 0,
                'attempt' => 0, 'max_attempts' => 3, 'next_attempt_at' => 1,
            );
        }
        return $rows;
    }

    public static function budgets() {
        return array(
            'system_tx' => array( 'tokens' => 50, 'max_inflight' => 50,
                'inflight' => 0, 'cooldown_until' => 0, 'source' => 'synthetic_verified' ),
            'newsletter_bulk' => array( 'tokens' => 45, 'max_inflight' => 45,
                'inflight' => 0, 'cooldown_until' => 0, 'source' => 'synthetic_verified' ),
        );
    }

    public static function simulate( $rows, $budgets, $gates ) {
        $fail = static function ( $reason ) {
            return array( 'status' => 'blocked', 'reason' => $reason,
                'planned_claims' => 0, 'real_mail_sent' => 0, 'worker_enabled' => false );
        };
        if ( ! is_array( $gates ) || true !== ( $gates['synthetic_only'] ?? false ) ||
             true !== ( $gates['dev_isolated'] ?? false ) ||
             true !== ( $gates['health_ok'] ?? false ) ||
             ! is_array( $rows ) || self::FIXTURE_SIZE !== count( $rows ) ||
             ! is_array( $budgets ) ) {
            return $fail( 'synthetic_isolation_gate' );
        }
        // Validate the full dataset before sorting/slicing. No partial success.
        $seen = array();
        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) || count( $row ) !== 10 ||
                 ! isset( $row['opaque_id'], $row['idempotency_key'] ) ||
                 ! is_string( $row['opaque_id'] ) || ! is_string( $row['idempotency_key'] ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['opaque_id'] ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['idempotency_key'] ) ||
                 isset( $seen[ $row['opaque_id'] ] ) ||
                 array_diff( array_keys( $row ), array( 'opaque_id', 'idempotency_key',
                     'sender_key', 'channel', 'priority', 'state',
                     'state_version', 'attempt', 'max_attempts', 'next_attempt_at' ) ) ) {
                return $fail( 'untrusted_fixture' );
            }
            $seen[ $row['opaque_id'] ] = true;
        }
        // GLOBAL priority order prevents a late P0 item being skipped behind
        // large P2 backlog by a window boundary.
        usort( $rows, static function ( $a, $b ) {
            return ( $a['priority'] <=> $b['priority'] ) ?:
                ( $a['next_attempt_at'] <=> $b['next_attempt_at'] ) ?:
                strcmp( $a['opaque_id'], $b['opaque_id'] );
        } );
        $priority_counts = array_fill_keys( array( 0, 1, 2, 3 ), 0 );
        $selected = array();
        $windows = 0;
        foreach ( array_chunk( $rows, self::WINDOW_SIZE ) as $batch ) {
            ++$windows;
            $plan = YBY_Mail_V2_P2_Planner::plan( $batch, $budgets, 100,
                array( 'synthetic_only' => true, 'health_ok' => true,
                    'max_batch' => 50, 'critical_reserve' => 5 ) );
            if ( 'dry_run' !== ( $plan['status'] ?? '' ) ||
                 count( $plan['claims'] ) > 50 ) { return $fail( 'planner_failed' ); }
            $preview = YBY_Mail_V2_P2_Scheduler::preview_actions( $plan['claims'],
                array( 'synthetic_only' => true, 'as_version' => '3.3.0',
                    'db_stable' => true, 'woo_jobs_isolated' => true ) );
            if ( count( $preview ) !== count( $plan['claims'] ) ) { return $fail( 'preview_failed' ); }
            foreach ( $plan['claims'] as $claim ) {
                if ( isset( $selected[ $claim['opaque_id'] ] ) ||
                     ! isset( $priority_counts[ $claim['priority'] ] ) ) {
                    return $fail( 'claim_duplicate_or_unknown' );
                }
                ++$priority_counts[ $claim['priority'] ];
                $selected[ $claim['opaque_id'] ] = true;
            }
            foreach ( $preview as $action ) {
                if ( true !== ( ! $action['executed'] && ! $action['scheduled'] ) ) {
                    return $fail( 'unexpected_runtime_action' );
                }
            }
        }
        return array( 'status' => 'dry_run', 'fixture_total' => self::FIXTURE_SIZE,
            'windows' => $windows, 'max_window' => self::WINDOW_SIZE,
            'planned_claims' => count( $selected ),
            'by_priority' => $priority_counts, 'real_mail_sent' => 0,
            'worker_enabled' => false, 'db_mutations' => false,
            'action_group' => YBY_Mail_V2_P2_Scheduler::GROUP );
    }
}
