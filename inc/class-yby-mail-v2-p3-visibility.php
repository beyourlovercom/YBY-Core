<?php
/**
 * Mail OS V2 P3 — read-only, privacy-minimal inspection over supplied snapshots.
 * NEVER registers a WordPress page, reads a live table, enqueues mail, or mutates
 * a queue. A real repository/authorization adapter requires a separate gate.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P3_Visibility {
    const MAX_ROWS = 1000;
    const SAFE_DIAGNOSTICS = array(
        'none', 'preacceptance_timeout', 'preacceptance_reject',
        'permanent_reject', 'acceptance_unknown', 'provider_accepted',
        'manually_reviewed', 'expired_lease', 'rate_limited',
    );

    /**
     * $context is a verifiable caller attestation, NOT a permissions check.
     * A future WP controller must still verify current_user_can and real nonce.
     * Returning only metadata allows UI UAT without subscriber/recipient leaks.
     */
    public static function inspect( $rows, $context, $view, $page = 1, $per_page = 20 ) {
        $block = static function ( $code ) {
            return array( 'status' => 'blocked', 'reason' => $code,
                'source' => 'unavailable', 'total' => null, 'rows' => array(), 'counts' => array() );
        };
        if ( ! is_array( $context ) ||
             ! YBY_Mail_V2_Governance::can_inspect_readonly(
                 $view, $context['capability_verified'] ?? false, $context['nonce_verified'] ?? false
             ) ) { return $block( 'auth_gate' ); }
        if ( ! in_array( $view, array( 'overview', 'queue', 'failures', 'logs', 'health' ), true ) ) {
            return $block( 'unknown_view' );
        }
        if ( 'isolated_fixture' !== ( $context['source'] ?? null ) &&
             'verified_readonly_outbox' !== ( $context['source'] ?? null ) ) {
            return $block( 'unverified_source' );
        }
        if ( 'verified_readonly_outbox' === $context['source'] &&
             ( true !== ( $context['schema_verified'] ?? false ) ||
               true !== ( $context['db_readonly'] ?? false ) ) ) {
            return $block( 'source_gate' );
        }
        if ( ! is_array( $rows ) || count( $rows ) > self::MAX_ROWS ||
             ! is_int( $page ) || $page < 1 || $page > 100000 ||
             ! is_int( $per_page ) || ! in_array( $per_page, array( 10, 20, 50 ), true ) ) {
            return $block( 'invalid_request' );
        }
        $counts = array_fill_keys( YBY_Mail_V2_Contract::STATES, 0 );
        $seen = array();
        foreach ( $rows as $row ) {
            if ( ! self::valid_row( $row ) ) { return $block( 'invalid_snapshot' ); }
            if ( isset( $seen[ $row['opaque_id'] ] ) ) { return $block( 'duplicate_snapshot' ); }
            $seen[ $row['opaque_id'] ] = true;
            ++$counts[ $row['state'] ];
        }
        $filtered = $rows;
        if ( 'failures' === $view ) {
            $filtered = array_values( array_filter( $rows, static function ( $row ) {
                return in_array( $row['state'], array( 'deferred', 'permanent_failed', 'uncertain' ), true );
            } ) );
        }
        usort( $filtered, static function ( $a, $b ) {
            return ( $a['priority'] <=> $b['priority'] )
                ?: ( $a['next_attempt_at'] <=> $b['next_attempt_at'] )
                ?: strcmp( $a['opaque_id'], $b['opaque_id'] );
        } );
        $total = count( $filtered );
        $offset = ( $page - 1 ) * $per_page;
        return array(
            'status' => 'read_only',
            'source' => $context['source'],
            'note' => 'provider_accepted_not_inbox_delivered',
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'counts' => $counts,
            'rows' => array_slice( $filtered, $offset, $per_page ),
            'mutations_allowed' => false,
        );
    }

    public static function valid_row( $row ) {
        if ( ! is_array( $row ) ) { return false; }
        $keys = array(
            'opaque_id', 'state', 'channel', 'sender_key', 'priority',
            'attempt', 'max_attempts', 'next_attempt_at', 'diagnostic_code',
            'state_version',
        );
        if ( count( $row ) !== count( $keys ) || array_diff( array_keys( $row ), $keys ) ) {
            return false;
        }
        if ( ! is_string( $row['opaque_id'] ) ||
             ! preg_match( '/\A[a-f0-9]{64}\z/D', $row['opaque_id'] ) ||
             ! in_array( $row['state'], YBY_Mail_V2_Contract::STATES, true ) ||
             ! is_string( $row['channel'] ) ||
             ! array_key_exists( $row['channel'], YBY_Mail_V2_Contract::CHANNEL_PRIORITY ) ||
             ! is_string( $row['sender_key'] ) ||
             ! preg_match( '/\A[a-z][a-z0-9_]{0,47}\z/D', $row['sender_key'] ) ||
             ! is_int( $row['priority'] ) ||
             $row['priority'] !== YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $row['channel'] ] ||
             ! is_int( $row['attempt'] ) || $row['attempt'] < 0 ||
             ! is_int( $row['max_attempts'] ) || $row['max_attempts'] < 1 ||
             $row['max_attempts'] > 5 || $row['attempt'] > $row['max_attempts'] ||
             ! is_int( $row['state_version'] ) || $row['state_version'] < 0 ||
             ! is_int( $row['next_attempt_at'] ) || $row['next_attempt_at'] < 0 ||
             ! is_string( $row['diagnostic_code'] ) ||
             ! in_array( $row['diagnostic_code'], self::SAFE_DIAGNOSTICS, true ) ) {
            return false;
        }
        return true;
    }
}
