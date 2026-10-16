<?php
/** P1 pure contract regression; no WordPress runtime, SMTP or database. */
define( 'ABSPATH', __DIR__ );
require_once dirname( __DIR__ ) . '/inc/class-yby-mail-v2-contract.php';
$checks = 0;
function p1_assert( $condition, $name ) {
    global $checks;
    ++$checks;
    if ( ! $condition ) {
        throw new RuntimeException( 'MAIL_V2_P1_TEST_FAIL: ' . $name );
    }
}
$hash = hash( 'sha256', 'synthetic-event-not-an-email' );
$key = YBY_Mail_V2_Contract::idempotency_key( 'newsletter', 'newsletter', $hash, 'confirm_subscription', 1 );
p1_assert( is_string( $key ) && 64 === strlen( $key ), 'stable digest' );
p1_assert( $key === YBY_Mail_V2_Contract::idempotency_key( 'newsletter', 'newsletter', $hash, 'confirm_subscription', 1 ), 'idempotent same event' );
p1_assert( $key !== YBY_Mail_V2_Contract::idempotency_key( 'newsletter', 'newsletter', $hash, 'confirm_subscription', 2 ), 'version changes key' );
p1_assert( $key !== YBY_Mail_V2_Contract::idempotency_key( 'newsletter', 'newsletter_alt', $hash, 'confirm_subscription', 1 ), 'sender changes key' );
p1_assert( null === YBY_Mail_V2_Contract::idempotency_key( 'newsletter', 'person@example.test', $hash, 'confirm_subscription', 1 ), 'no PII sender' );
$envelope = array(
    'idempotency_key' => $key,
    'event_hash' => $hash,
    'channel' => 'newsletter',
    'sender_key' => 'newsletter',
    'template_key' => 'confirm_subscription',
    'template_version' => 1,
    'consent_snapshot_hash' => hash( 'sha256', 'synthetic-consent' ),
    'priority' => 1,
    'state' => 'queued',
    'attempt' => 0,
    'max_attempts' => 3,
    'next_attempt_at' => '2026-10-13T12:34:56Z',
);
p1_assert( YBY_Mail_V2_Contract::validate_envelope( $envelope )['valid'], 'valid envelope' );
$forbidden_fields = array( 'recipient' => 'customer@example.test', 'password' => 'secret', 'body' => 'sensitive', 'to' => 'a@example.test', 'smtp_response' => 'raw' );
foreach ( $forbidden_fields as $field => $value ) {
    $bad = $envelope;
    $bad[ $field ] = $value;
    $result = YBY_Mail_V2_Contract::validate_envelope( $bad );
    p1_assert( ! $result['valid'] && in_array( 'unapproved_field', $result['errors'], true ), 'reject disallowed ' . $field );
    p1_assert( false === strpos( json_encode( $result ), $value ), 'never echo secret ' . $field );
}
foreach ( array(
    array( 'idempotency_key', str_repeat( '0', 64 ) ),
    array( 'sender_key', 'sales@example.test' ),
    array( 'event_hash', 'raw-user-42' ),
    array( 'priority', 2 ),
    array( 'state', 'sending' ),
    array( 'attempt', 1 ),
    array( 'max_attempts', 0 ),
    array( 'next_attempt_at', '2026-02-30T10:00:00Z' ),
    array( 'consent_snapshot_hash', null ),
) as $case ) {
    $bad = $envelope;
    $bad[ $case[0] ] = $case[1];
    p1_assert( ! YBY_Mail_V2_Contract::validate_envelope( $bad )['valid'], 'deny invalid ' . $case[0] );
}
$missing = $envelope;
unset( $missing['template_key'] );
p1_assert( ! YBY_Mail_V2_Contract::validate_envelope( $missing )['valid'], 'deny missing field' );
p1_assert( ! YBY_Mail_V2_Contract::validate_envelope( 'abc' )['valid'], 'deny non array' );
$system = $envelope;
$system['channel'] = 'critical_system';
$system['priority'] = 0;
$system['consent_snapshot_hash'] = null;
$system['idempotency_key'] = YBY_Mail_V2_Contract::idempotency_key( 'critical_system', 'newsletter', $hash, 'confirm_subscription', 1 );
p1_assert( YBY_Mail_V2_Contract::validate_envelope( $system )['valid'], 'system consent nullable' );
$steps = array(
    array( 'queued', 'leased', array(), true ),
    array( 'queued', 'cancelled', array(), true ),
    array( 'queued', 'accepted_by_provider', array(), false ),
    array( 'leased', 'sending', array(), true ),
    array( 'leased', 'deferred', array(), false ),
    array( 'leased', 'deferred', array( 'never_transmitted' => true ), true ),
    array( 'leased', 'uncertain', array( 'acceptance_unknown' => true ), true ),
    array( 'sending', 'accepted_by_provider', array(), false ),
    array( 'sending', 'accepted_by_provider', array( 'provider_ack' => true ), true ),
    array( 'sending', 'deferred', array( 'definite_preacceptance_failure' => true ), true ),
    array( 'sending', 'deferred', array( 'acceptance_unknown' => true ), false ),
    array( 'sending', 'uncertain', array( 'acceptance_unknown' => true ), true ),
    array( 'sending', 'permanent_failed', array( 'permanent_failure' => true ), true ),
    array( 'uncertain', 'queued', array(), false ),
    array( 'uncertain', 'deferred', array(), false ),
    array( 'uncertain', 'accepted_by_provider', array( 'provider_ack' => true ), false ),
    array( 'uncertain', 'accepted_by_provider', array( 'verified_provider_ack' => true ), true ),
    array( 'uncertain', 'permanent_failed', array( 'manual_review' => true ), true ),
    array( 'accepted_by_provider', 'queued', array(), false ),
    array( 'permanent_failed', 'queued', array(), false ),
    array( 'cancelled', 'leased', array(), false ),
    array( 'queued', 'queued', array(), false ),
);
foreach ( $steps as $i => $case ) {
    p1_assert( $case[3] === YBY_Mail_V2_Contract::permits_transition( $case[0], $case[1], $case[2] ), 'transition ' . $i );
}
p1_assert( null === YBY_Mail_V2_Contract::retry_delay_seconds( $key, 1, 3, false ), 'reject retry without preacceptance proof' );
p1_assert( null === YBY_Mail_V2_Contract::retry_delay_seconds( $key, 3, 3, true ), 'reject exhausted attempts' );
$d1 = YBY_Mail_V2_Contract::retry_delay_seconds( $key, 1, 3, true );
$d2 = YBY_Mail_V2_Contract::retry_delay_seconds( $key, 1, 3, true );
p1_assert( is_int( $d1 ) && $d1 >= 60 && $d1 <= 90, 'retry jitter bounded' );
p1_assert( $d1 === $d2, 'retry jitter deterministic' );
p1_assert( YBY_Mail_V2_Contract::ACTION_GROUP === 'andy_core_mail_v2', 'scoped action group constant' );
p1_assert( ! function_exists( 'wp_mail' ), 'no mail functions created by contract' );
echo 'MAIL_V2_P1_CONTRACT_PASS assertions=' . $checks . "\n";
