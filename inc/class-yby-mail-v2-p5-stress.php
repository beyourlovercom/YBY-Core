<?php
/**
 * Mail OS V2 P5 isolated load rehearsal.
 * Produces 10,000 purely synthetic opaque rows; P2 plans 10 sequential
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
        $seen_keys = array();
        $senders_by_id = array();
        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) || count( $row ) !== 10 ||
                 ! isset( $row['opaque_id'], $row['idempotency_key'] ) ||
                 ! is_string( $row['opaque_id'] ) || ! is_string( $row['idempotency_key'] ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['opaque_id'] ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['idempotency_key'] ) ||
                 isset( $seen[ $row['opaque_id'] ] ) ||
                 isset( $seen_keys[ $row['idempotency_key'] ] ) ||
                 ! isset( YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $row['channel'] ?? '' ] ) ||
                 $row['priority'] !== YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $row['channel'] ] ||
                 ! in_array( $row['sender_key'], array( 'system_tx', 'newsletter_bulk' ), true ) ||
                 ( 'campaign' === $row['channel'] ? 'newsletter_bulk' : 'system_tx' ) !== $row['sender_key'] ||
                 'queued' !== $row['state'] || 0 !== $row['state_version'] ||
                 0 !== $row['attempt'] || 3 !== $row['max_attempts'] ||
                 1 !== $row['next_attempt_at'] ||
                 array_diff( array_keys( $row ), array( 'opaque_id', 'idempotency_key',
                     'sender_key', 'channel', 'priority', 'state',
                     'state_version', 'attempt', 'max_attempts', 'next_attempt_at' ) ) ) {
                return $fail( 'untrusted_fixture' );
            }
            $seen[ $row['opaque_id'] ] = true;
            $seen_keys[ $row['idempotency_key'] ] = true;
            $senders_by_id[ $row['opaque_id'] ] = $row['sender_key'];
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
        // Same simulated instant across windows: do NOT mint new tokens or
        // release in-flight capacity between chunks without refill evidence.
        $remaining = $budgets;
        $consumed = array_fill_keys( array_keys( $budgets ), 0 );
        foreach ( array_chunk( $rows, self::WINDOW_SIZE ) as $batch ) {
            ++$windows;
            $plan = YBY_Mail_V2_P2_Planner::plan( $batch, $remaining, 100,
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
                $sender = $senders_by_id[ $claim['opaque_id'] ] ?? '';
                if ( ! isset( $remaining[ $sender ] ) ||
                     $remaining[ $sender ]['tokens'] <= 0 ||
                     $remaining[ $sender ]['inflight'] >= $remaining[ $sender ]['max_inflight'] ) {
                    return $fail( 'global_sender_budget_exhausted' );
                }
                --$remaining[ $sender ]['tokens'];
                ++$remaining[ $sender ]['inflight'];
                ++$consumed[ $sender ];
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
            'by_priority' => $priority_counts,
            'by_sender' => $consumed,
            'remaining_tokens' => array_map( static function ( $budget ) {
                return $budget['tokens'];
            }, $remaining ),
            'real_mail_sent' => 0,
            'worker_enabled' => false, 'db_mutations' => false,
            'action_group' => YBY_Mail_V2_P2_Scheduler::GROUP );
    }
}
