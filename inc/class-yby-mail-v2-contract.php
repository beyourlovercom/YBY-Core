<?php
/**
 * Andy Core Mail OS V2 P1 — inert, metadata-only queue contract.
 *
 * This file does not register hooks, create tables, enqueue actions, read WP
 * options, configure SMTP, or deliver mail. No production runtime loads it.
 * Outbox storage, scheduler and transport require separately approved gates.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class YBY_Mail_V2_Contract {
    const ACTION_GROUP = 'andy_core_mail_v2';
    const CHANNEL_PRIORITY = array(
        'critical_system' => 0,
        'account'         => 1,
        'newsletter'      => 1,
        'campaign'        => 2,
        'low_priority'    => 3,
    );
    const INITIAL_STATE = 'queued';
    const STATES = array(
        'queued', 'leased', 'sending', 'accepted_by_provider',
        'deferred', 'permanent_failed', 'uncertain', 'cancelled',
    );

    /**
     * Construct a stable, opaque deduplication key from non-PII identifiers.
     * event_hash MUST be a precomputed 64-character keyed HMAC digest, not a raw
     * address, customer identifier, order ID, subscriber token or payload.
     */
    public static function idempotency_key( $channel, $sender_key, $event_hash, $template_key, $template_version ) {
        if ( ! self::valid_channel( $channel ) || ! self::valid_slug( $sender_key ) ||
            ! self::valid_digest( $event_hash ) || ! self::valid_slug( $template_key ) ||
            ! is_int( $template_version ) || $template_version < 1 ) {
            return null;
        }
        $canonical = array( 'andy_core_mail_v2', $channel, $sender_key,
            $event_hash, $template_key, (string) $template_version );
        return hash( 'sha256', implode( "\0", $canonical ) );
    }

    /**
     * Validate an immutable, privacy-minimal NEW queue envelope (metadata).
     * Strict allowlist: recipient, headers, message/body, and credentials must
     * never be persisted in scheduler arguments or this queue envelope.
     * Returns only opaque field-name diagnostics; never echoes input values.
     */
    public static function validate_envelope( $envelope ) {
        if ( ! is_array( $envelope ) ) {
            return array( 'valid' => false, 'errors' => array( 'invalid_envelope' ) );
        }
        $keys = array(
            'idempotency_key', 'event_hash', 'channel', 'sender_key',
            'template_key', 'template_version', 'consent_snapshot_hash',
            'priority', 'state', 'attempt', 'max_attempts', 'next_attempt_at',
        );
        $errors = array();
        foreach ( array_keys( $envelope ) as $key ) {
            if ( ! in_array( $key, $keys, true ) ) {
                $errors[] = 'unapproved_field';
                break;
            }
        }
        foreach ( $keys as $key ) {
            if ( ! array_key_exists( $key, $envelope ) ) {
                $errors[] = 'missing_' . $key;
            }
        }
        if ( $errors ) {
            return array( 'valid' => false, 'errors' => array_values( array_unique( $errors ) ) );
        }
        if ( ! self::valid_channel( $envelope['channel'] ) ) {
            $errors[] = 'invalid_channel';
        }
        if ( ! self::valid_slug( $envelope['sender_key'] ) ) {
            $errors[] = 'invalid_sender_key';
        }
        if ( ! self::valid_slug( $envelope['template_key'] ) ) {
            $errors[] = 'invalid_template_key';
        }
        if ( ! self::valid_digest( $envelope['event_hash'] ) ) {
            $errors[] = 'invalid_event_hash';
        }
        if ( ! self::valid_digest( $envelope['idempotency_key'] ) ) {
            $errors[] = 'invalid_idempotency_key';
        }
        if ( ! is_int( $envelope['template_version'] ) || $envelope['template_version'] < 1 ) {
            $errors[] = 'invalid_template_version';
        }
        if ( ! is_int( $envelope['priority'] ) ||
            ! self::valid_channel( $envelope['channel'] ) ||
            $envelope['priority'] !== self::CHANNEL_PRIORITY[ $envelope['channel'] ] ) {
            $errors[] = 'invalid_priority';
        }
        if ( self::INITIAL_STATE !== $envelope['state'] || ! is_int( $envelope['attempt'] ) || 0 !== $envelope['attempt'] ) {
            $errors[] = 'invalid_initial_state';
        }
        if ( ! is_int( $envelope['max_attempts'] ) ||
            $envelope['max_attempts'] < 1 || $envelope['max_attempts'] > 5 ) {
            $errors[] = 'invalid_max_attempts';
        }
        if ( ! self::valid_utc_instant( $envelope['next_attempt_at'] ) ) {
            $errors[] = 'invalid_next_attempt_at';
        }
        $requires_consent = in_array( $envelope['channel'], array( 'newsletter', 'campaign' ), true );
        $consent = $envelope['consent_snapshot_hash'];
        if ( ( $requires_consent && ! self::valid_digest( $consent ) ) ||
            ( ! $requires_consent && null !== $consent && ! self::valid_digest( $consent ) ) ) {
            $errors[] = 'invalid_consent_snapshot_hash';
        }
        // The provided key must match the canonical non-PII metadata.
        $computed_key = self::idempotency_key( $envelope['channel'], $envelope['sender_key'],
            $envelope['event_hash'], $envelope['template_key'], $envelope['template_version'] );
        if ( null === $computed_key || ! is_string( $envelope['idempotency_key'] ) ||
            ! hash_equals( $computed_key, $envelope['idempotency_key'] ) ) {
            $errors[] = 'idempotency_mismatch';
        }
        return array( 'valid' => ! $errors, 'errors' => array_values( array_unique( $errors ) ) );
    }

    /**
     * A *policy* decision, not a transition executor. Caller must still use
     * atomic compare-and-swap, leases and immutable audit at P2/P3.
     * Evidence booleans must be verified upstream, not derived from a timeout.
     */
    public static function permits_transition( $from, $to, $proof = array() ) {
        if ( ! in_array( $from, self::STATES, true ) || ! in_array( $to, self::STATES, true ) ||
             ! is_array( $proof ) || $from === $to ) {
            return false;
        }
        $simple = array(
            'queued' => array( 'leased', 'cancelled' ),
            'deferred' => array( 'leased', 'cancelled' ),
            'leased' => array( 'sending' ),
        );
        if ( isset( $simple[ $from ] ) && in_array( $to, $simple[ $from ], true ) ) {
            return true;
        }
        if ( 'leased' === $from && 'deferred' === $to ) {
            return true === ( $proof['never_transmitted'] ?? false );
        }
        if ( 'leased' === $from && 'uncertain' === $to ) {
            return true === ( $proof['acceptance_unknown'] ?? false );
        }
        if ( 'sending' === $from ) {
            $requirements = array(
                'accepted_by_provider' => 'provider_ack',
                'deferred'             => 'definite_preacceptance_failure',
                'permanent_failed'     => 'permanent_failure',
                'uncertain'            => 'acceptance_unknown',
            );
            if ( isset( $requirements[ $to ] ) ) {
                return true === ( $proof[ $requirements[ $to ] ] ?? false );
            }
        }
        if ( 'uncertain' === $from ) {
            // Review resolves state; never releases a potentially accepted
            // message back to queue. False-positive ack would be unsafe.
            return ( 'accepted_by_provider' === $to && true === ( $proof['verified_provider_ack'] ?? false ) ) ||
                ( 'permanent_failed' === $to && true === ( $proof['manual_review'] ?? false ) );
        }
        return false;
    }

    /** Deterministic capped backoff for a proven pre-acceptance retry only. */
    public static function retry_delay_seconds( $key, $attempt, $max_attempts, $proof ) {
        if ( ! self::valid_digest( $key ) || ! is_int( $attempt ) || $attempt < 1 ||
            ! is_int( $max_attempts ) || $max_attempts < 1 || $max_attempts > 5 ||
            $attempt >= $max_attempts || true !== $proof ) {
            return null;
        }
        $base = min( 3600, 60 * ( 2 ** ( $attempt - 1 ) ) );
        $jitter = hexdec( substr( hash( 'sha256', $key . ':' . $attempt ), 0, 4 ) ) % 31;
        return min( 3600, $base + $jitter );
    }

    private static function valid_channel( $value ) {
        return is_string( $value ) && array_key_exists( $value, self::CHANNEL_PRIORITY );
    }

    private static function valid_slug( $value ) {
        return is_string( $value ) && 1 === preg_match( '/\A[a-z][a-z0-9_]{0,47}\z/D', $value );
    }

    private static function valid_digest( $value ) {
        return is_string( $value ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/D', $value );
    }

    private static function valid_utc_instant( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/D', $value ) ) {
            return false;
        }
        try {
            $parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone( 'UTC' ) );
            return false !== $parsed && $parsed->format( 'Y-m-d\TH:i:s\Z' ) === $value;
        } catch ( \Throwable $error ) {
            return false;
        }
    }
}
