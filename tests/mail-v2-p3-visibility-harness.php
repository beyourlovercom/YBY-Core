<?php
/** P3 pure-function privacy and management gate regression (no WP runtime). */
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/inc/class-yby-mail-v2-contract.php';
require dirname( __DIR__ ) . '/inc/class-yby-mail-v2-governance.php';
require dirname( __DIR__ ) . '/inc/class-yby-mail-v2-p3-visibility.php';
$n = 0;
function check_p3( $ok, $name ) { global $n; ++$n; if ( ! $ok ) { throw new RuntimeException( "MAIL_P3_FAIL:{$name}" ); } }
function p3row( $i, $state, $channel = 'campaign' ) {
    return array( 'opaque_id' => hash( 'sha256', 'p3-' . $i ),
        'state' => $state, 'channel' => $channel, 'sender_key' => 'marketing',
        'priority' => YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $channel ],
        'attempt' => 0, 'max_attempts' => 3, 'next_attempt_at' => 100 + $i,
        'diagnostic_code' => 'none', 'state_version' => 0 );
}
$ctx = array( 'capability_verified' => true, 'nonce_verified' => true, 'source' => 'isolated_fixture' );
$rows = array( p3row( 1, 'queued' ), p3row( 2, 'uncertain' ), p3row( 3, 'permanent_failed' ), p3row( 4, 'accepted_by_provider' ) );
$r = YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'queue', 1, 10 );
check_p3( 'read_only' === $r['status'], 'readonly inspect' );
check_p3( 4 === $r['total'], 'queue total' );
check_p3( 1 === $r['counts']['uncertain'], 'uncertain counted' );
check_p3( false === $r['mutations_allowed'], 'no mutation permission' );
check_p3( 'provider_accepted_not_inbox_delivered' === $r['note'], 'provider ack not delivery' );
$errors = YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'failures', 1, 10 );
check_p3( 2 === $errors['total'], 'failure inbox excludes accepted' );
check_p3( ! in_array( 'accepted_by_provider', array_column( $errors['rows'], 'state' ), true ), 'accepted cannot be failure' );
check_p3( 0 === count( YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'queue', 2, 10 )['rows'] ), 'page beyond rows empty' );
$bad = $ctx; $bad['nonce_verified'] = false;
check_p3( 'auth_gate' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $bad, 'queue' )['reason'], 'nonce gate' );
$bad = $ctx; $bad['capability_verified'] = false;
check_p3( 'auth_gate' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $bad, 'queue' )['reason'], 'capability gate' );
check_p3( 'auth_gate' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'transport' )['reason'], 'unknown view denied' );
$bad = $ctx; $bad['source'] = 'verified_readonly_outbox';
check_p3( 'source_gate' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $bad, 'queue' )['reason'], 'live cannot pretend ready' );
$bad['schema_verified'] = true; $bad['db_readonly'] = true;
check_p3( 'read_only' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $bad, 'queue' )['status'], 'future attested adapter shape' );
$bad = $ctx; $bad['source'] = 'guessed_provider_state';
check_p3( 'unverified_source' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $bad, 'queue' )['reason'], 'untrusted source blocked' );
$badrow = $rows; $badrow[0]['recipient'] = 'someone@example.test';
check_p3( 'invalid_snapshot' === YBY_Mail_V2_P3_Visibility::inspect( $badrow, $ctx, 'queue' )['reason'], 'recipient not selectable' );
$badrow = $rows; $badrow[0]['smtp_response'] = 'credentials';
check_p3( 'invalid_snapshot' === YBY_Mail_V2_P3_Visibility::inspect( $badrow, $ctx, 'logs' )['reason'], 'raw SMTP error forbidden' );
$badrow = $rows; $badrow[0]['diagnostic_code'] = 'user@example.test';
check_p3( 'invalid_snapshot' === YBY_Mail_V2_P3_Visibility::inspect( $badrow, $ctx, 'logs' )['reason'], 'diagnostic code allowlist' );
$badrow = $rows; $badrow[0]['priority'] = 0;
check_p3( 'invalid_snapshot' === YBY_Mail_V2_P3_Visibility::inspect( $badrow, $ctx, 'queue' )['reason'], 'priority fraud denied' );
$badrow = $rows; $badrow[] = $rows[0];
check_p3( 'duplicate_snapshot' === YBY_Mail_V2_P3_Visibility::inspect( $badrow, $ctx, 'queue' )['reason'], 'duplicate identity fails closed' );
check_p3( 'invalid_request' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'queue', 0, 10 )['reason'], 'invalid page' );
check_p3( 'invalid_request' === YBY_Mail_V2_P3_Visibility::inspect( $rows, $ctx, 'queue', 1, 500 )['reason'], 'bounded page size' );
$large = array();
for ( $i = 0; $i < 1001; ++$i ) { $large[] = p3row( $i, 'queued' ); }
check_p3( 'invalid_request' === YBY_Mail_V2_P3_Visibility::inspect( $large, $ctx, 'queue' )['reason'], 'snapshot memory bound' );
$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-mail-v2-p3-visibility.php' );
check_p3( ! preg_match( '/\b(?:wp_mail|add_action|as_enqueue_async_action|dbDelta|update_option|DELETE FROM|INSERT INTO)\s*\(/i', $source ), 'no runtime hooks or sends' );
check_p3( false === YBY_Mail_V2_Governance::QUEUE_ENABLED && false === YBY_Mail_V2_Governance::OUTBOUND_ENABLED, 'P1 gates preserved' );
echo 'MAIL_P3_READONLY_PASS assertions=' . $n . "\n";
