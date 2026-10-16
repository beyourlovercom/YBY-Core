<?php
/** P1 pure governance/spec harness: no network, WP runtime or SQL. */
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/inc/class-yby-mail-v2-governance.php';
$tests = 0;
function gov_assert( $expr, $label ) {
    global $tests;
    ++$tests;
    if ( ! $expr ) {
        throw new RuntimeException( 'MAIL_V2_GOV_FAIL: ' . $label );
    }
}
$spec = YBY_Mail_V2_Governance::schema_contract();
gov_assert( 'yby_mail_v2_outbox' === $spec['logical_table'], 'logical table only' );
gov_assert( in_array( array( 'idempotency_key' ), $spec['unique_constraints'], true ), 'unique key required' );
gov_assert( in_array( 'state_version', $spec['columns'], true ), 'atomic state version' );
gov_assert( in_array( 'payload_ref', $spec['columns'], true ), 'opaque payload reference' );
gov_assert( in_array( 'consent_snapshot_hash', $spec['columns'], true ), 'consent version reference' );
gov_assert( in_array( 'lease_token_hash', $spec['columns'], true ), 'scoped lease token hash' );
gov_assert( in_array( array( 'state', 'priority', 'next_attempt_at' ), $spec['query_indexes'], true ), 'bounded due index' );
gov_assert( 'atomic_compare_and_swap_on_state_version' === $spec['state_locking'], 'state locking declared' );
foreach ( $spec['forbidden_columns'] as $field ) {
    gov_assert( ! in_array( $field, $spec['columns'], true ), 'no sensitive column ' . $field );
}
gov_assert( false === YBY_Mail_V2_Governance::QUEUE_ENABLED, 'queue default off' );
gov_assert( false === YBY_Mail_V2_Governance::OUTBOUND_ENABLED, 'outbound default off' );
gov_assert( false === YBY_Mail_V2_Governance::RETRY_ENABLED, 'retry default off' );
gov_assert( 'andy_core_settings_manage' === YBY_Mail_V2_Governance::CAPABILITY, 'reuse existing core capability' );
foreach ( array( 'overview', 'queue', 'failures', 'logs', 'health' ) as $action ) {
    gov_assert( YBY_Mail_V2_Governance::can_inspect_readonly( $action, true, true ), 'approved read action' );
    gov_assert( ! YBY_Mail_V2_Governance::can_inspect_readonly( $action, false, true ), 'capability required' );
    gov_assert( ! YBY_Mail_V2_Governance::can_inspect_readonly( $action, true, false ), 'nonce required' );
}
foreach ( array( 'enqueue', 'retry', 'send', 'cancel', 'delete', 'pause', 'drain', 'edit' ) as $action ) {
    gov_assert( ! YBY_Mail_V2_Governance::can_inspect_readonly( $action, true, true ), 'not a readonly action' );
    gov_assert( ! YBY_Mail_V2_Governance::can_mutate( $action, true, true ), 'no P1 mutations' );
}
gov_assert( ! YBY_Mail_V2_Governance::retention_policy_valid( 30, false ), 'owner retention gate' );
gov_assert( ! YBY_Mail_V2_Governance::retention_policy_valid( 0, true ), 'zero retention forbidden' );
gov_assert( ! YBY_Mail_V2_Governance::retention_policy_valid( 366, true ), 'unbounded retention forbidden' );
gov_assert( ! YBY_Mail_V2_Governance::retention_policy_valid( '30', true ), 'typed retention required' );
gov_assert( YBY_Mail_V2_Governance::retention_policy_valid( 30, true ), 'explicitly approved retention allowed in contract' );
gov_assert( ! function_exists( 'wp_mail' ), 'no runtime mail exposure' );
echo 'MAIL_V2_P1_GOVERNANCE_PASS assertions=' . $tests . "\n";
