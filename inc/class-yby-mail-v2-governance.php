<?php
/**
 * Mail OS V2 P1 — inert governance and planned durable-outbox schema CONTRACT.
 * No add_action, hooks, WP option writes, DB migrations, cron or email sending.
 * This is never instantiated by the WordPress runtime in P1.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class YBY_Mail_V2_Governance {
    const CAPABILITY = 'andy_core_settings_manage';
    const QUEUE_ENABLED = false;
    const OUTBOUND_ENABLED = false;
    const RETRY_ENABLED = false;

    /**
     * Declarative durable outbox spec only; no CREATE TABLE or migration.
     * Sensitive delivery content is external to this metadata-only table.
     * Any future protected payload store requires an independent review.
     */
    public static function schema_contract() {
        return array(
            'logical_table' => 'yby_mail_v2_outbox',
            'columns' => array(
                'opaque_id', 'idempotency_key', 'event_hash', 'payload_ref',
                'channel', 'sender_key', 'template_key', 'template_version',
                'consent_snapshot_hash', 'priority', 'state', 'state_version',
                'attempt', 'max_attempts', 'next_attempt_at', 'lease_token_hash',
                'lease_expires_at', 'diagnostic_code', 'created_at', 'updated_at',
            ),
            'unique_constraints' => array( array( 'idempotency_key' ) ),
            'query_indexes' => array(
                array( 'state', 'priority', 'next_attempt_at' ),
                array( 'sender_key', 'state', 'next_attempt_at' ),
            ),
            'state_locking' => 'atomic_compare_and_swap_on_state_version',
            'payload_location' => 'separately_reviewed_protected_reference_only',
            'forbidden_columns' => array(
                'recipient', 'to_address', 'email_address', 'subject', 'body',
                'headers', 'password', 'smtp_password', 'smtp_response',
                'transport_secret', 'provider_token',
            ),
        );
    }

    /**
     * P1 has no live mutation access. A later authorized admin controller
     * must independently check current_user_can(), a real WordPress nonce,
     * and a specific action capability; boolean flags are not sufficient.
     */
    public static function can_inspect_readonly( $action, $capability_verified, $nonce_verified ) {
        return is_string( $action ) &&
            in_array( $action, array( 'overview', 'queue', 'failures', 'logs', 'health' ), true ) &&
            true === $capability_verified && true === $nonce_verified;
    }

    public static function can_mutate( $action, $capability_verified, $nonce_verified ) {
        // All enqueue, cancel, pause, retry, drain, delete and send actions
        // remain locked until their own P2/P3 permission and deployment gates.
        return false;
    }

    /**
     * Retention is a proposed configuration contract, not an active policy.
     * No retention setting exists and no deletion occurs in P1. Owner must
     * separately approve days before any later retention worker is designed.
     */
    public static function retention_policy_valid( $days, $owner_approved ) {
        return true === $owner_approved && is_int( $days ) &&
            $days >= 1 && $days <= 365;
    }
}
