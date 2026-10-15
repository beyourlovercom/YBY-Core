<?php
/**
 * P2 dry-run only: Action Scheduler isolation proposal and token accounting.
 * No Action Scheduler functions are invoked or hooked into WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P2_Scheduler {
    const GROUP = 'andy_core_mail_v2';
    const HOOK = 'andy_core_mail_v2_tick';

    /**
     * Compatibility review cannot enable an actual worker. An explicit
     * synthetic state is required even for preview; no global Woo controls.
     */
    public static function compatibility( $snapshot ) {
        if ( ! is_array( $snapshot ) || true !== ( $snapshot['synthetic_only'] ?? false ) ||
             ! is_string( $snapshot['as_version'] ?? null ) ||
             ! preg_match( '/\A\d+\.\d+\.\d+\z/D', $snapshot['as_version'] ) ||
             version_compare( $snapshot['as_version'], '3.0.0', '<' ) ||
             true !== ( $snapshot['db_stable'] ?? false ) ||
             true !== ( $snapshot['woo_jobs_isolated'] ?? false ) ) {
            return array( 'ready_to_preview' => false, 'runtime_enabled' => false );
        }
        return array( 'ready_to_preview' => true, 'runtime_enabled' => false );
    }

    /** Only plan harmless opaque args: never calls as_enqueue_* / WP-Cron. */
    public static function preview_actions( $claims, $snapshot ) {
        if ( ! self::compatibility( $snapshot )['ready_to_preview'] || ! is_array( $claims ) || count( $claims ) > 50 ) {
            return array();
        }
        $actions = array();
        $seen = array();
        foreach ( $claims as $claim ) {
            if ( ! is_array( $claim ) || count( $claim ) !== 6 ||
                 ! is_string( $claim['opaque_id'] ?? null ) ||
                 ! preg_match( '/\A[a-f0-9]{64}\z/D', $claim['opaque_id'] ) ||
                 ! is_int( $claim['expected_version'] ?? null ) || $claim['expected_version'] < 0 ||
                 ! in_array( $claim['expected_state'] ?? null, array( 'queued', 'deferred' ), true ) ||
                 'leased' !== ( $claim['next_state'] ?? null ) ||
                 self::GROUP !== ( $claim['action_group'] ?? null ) ||
                 ! is_int( $claim['priority'] ?? null ) || $claim['priority'] < 0 || $claim['priority'] > 3 ||
                 isset( $seen[ $claim['opaque_id'] ] ) ) {
                return array();
            }
            $seen[ $claim['opaque_id'] ] = true;
            $actions[] = array(
                'hook' => self::HOOK, 'group' => self::GROUP,
                'args' => array( 'opaque_id' => $claim['opaque_id'], 'version' => $claim['expected_version'] ),
                'executed' => false, 'scheduled' => false,
            );
        }
        return $actions;
    }

    /** Pure token refill in whole token units; never invent a provider limit. */
    public static function replenish( $state, $now ) {
        if ( ! is_array( $state ) || ! is_int( $now ) || $now < 0 ||
             ! isset( $state['capacity'], $state['tokens'], $state['refill_interval'], $state['last_refill'] ) ||
             ! is_int( $state['capacity'] ) || $state['capacity'] < 1 || $state['capacity'] > 10000 ||
             ! is_int( $state['tokens'] ) || $state['tokens'] < 0 || $state['tokens'] > $state['capacity'] ||
             ! is_int( $state['refill_interval'] ) || $state['refill_interval'] < 1 ||
             ! is_int( $state['last_refill'] ) || $state['last_refill'] < 0 || $now < $state['last_refill'] ||
             'synthetic_verified' !== ( $state['source'] ?? null ) ) {
            return null;
        }
        $elapsed = $now - $state['last_refill'];
        $steps = intdiv( $elapsed, $state['refill_interval'] );
        return array( 'tokens' => min( $state['capacity'], $state['tokens'] + $steps ),
            'last_refill' => $state['last_refill'] + $steps * $state['refill_interval'] );
    }
}
