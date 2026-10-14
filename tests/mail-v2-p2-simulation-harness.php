<?php
/** P2 wholly offline / no WP, DB, Action Scheduler or SMTP test harness. */
define( 'ABSPATH', __DIR__ );
require_once dirname( __DIR__ ) . '/inc/class-yby-mail-v2-contract.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-mail-v2-governance.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-mail-v2-p2-planner.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-mail-v2-p2-scheduler.php';
$assertions = 0;
function p2_check( $ok, $name ) {
    global $assertions;
    ++$assertions;
    if ( ! $ok ) { throw new RuntimeException( 'MAIL_V2_P2_FAIL:' . $name ); }
}
function p2_item( $id, $sender, $channel, $due = 100, $state = 'queued', $attempt = 0, $version = 0 ) {
    return array( 'opaque_id' => hash( 'sha256', 'opaque:' . $id ),
        'idempotency_key' => hash( 'sha256', 'key:' . $id ),
        'sender_key' => $sender, 'channel' => $channel,
        'priority' => YBY_Mail_V2_Contract::CHANNEL_PRIORITY[ $channel ],
        'state' => $state, 'state_version' => $version,
        'attempt' => $attempt, 'max_attempts' => 3, 'next_attempt_at' => $due );
}
function p2_budget( $tokens = 5, $max = 5, $inflight = 0, $cooldown = 0 ) {
    return array( 'tokens' => $tokens, 'max_inflight' => $max,
        'inflight' => $inflight, 'cooldown_until' => $cooldown, 'source' => 'synthetic_verified' );
}
$limits = array( 'a' => p2_budget(), 'b' => p2_budget(), 'critical' => p2_budget() );
$cfg = array( 'synthetic_only' => true, 'health_ok' => true, 'max_batch' => 5, 'critical_reserve' => 2 );
$rows = array( p2_item( 'a1', 'a', 'campaign' ), p2_item( 'a2', 'a', 'campaign' ),
    p2_item( 'b1', 'b', 'campaign' ), p2_item( 'c1', 'critical', 'critical_system' ),
    p2_item( 'c2', 'critical', 'critical_system' ), p2_item( 'c3', 'critical', 'critical_system' ) );
$p = YBY_Mail_V2_P2_Planner::plan( $rows, $limits, 100, $cfg );
p2_check( 'dry_run' === $p['status'], 'dry run only' );
p2_check( 5 === count( $p['claims'] ), 'bounded maximum five' );
p2_check( 0 === $p['claims'][0]['priority'] && 0 === $p['claims'][1]['priority'], 'critical first' );
p2_check( 3 === count( array_filter( $p['claims'], static fn( $x ) => 0 === $x['priority'] ) ), 'reserved critical can fill three' );
p2_check( 2 === count( array_filter( $p['claims'], static fn( $x ) => 2 === $x['priority'] ) ), 'strict noncritical cap' );
p2_check( $p === YBY_Mail_V2_P2_Planner::plan( $rows, $limits, 100, $cfg ), 'deterministic decisions' );
$no_critical = array_slice( $rows, 0, 3 );
$p2 = YBY_Mail_V2_P2_Planner::plan( $no_critical, $limits, 100, $cfg );
p2_check( 3 === count( $p2['claims'] ), 'noncritical cap even if critical absent' );
$busy = array( 'a' => p2_budget( 5, 1, 1 ), 'b' => p2_budget( 1, 2, 1 ), 'critical' => p2_budget( 0 ) );
$p3 = YBY_Mail_V2_P2_Planner::plan( $rows, $busy, 100, $cfg );
p2_check( 1 === count( $p3['claims'] ), 'respect in-flight and tokens' );
$busy['b']['cooldown_until'] = 120;
p2_check( 0 === count( YBY_Mail_V2_P2_Planner::plan( $rows, $busy, 100, $cfg )['claims'] ), 'sender cooldown' );
$off = $cfg; $off['synthetic_only'] = false;
p2_check( 'blocked' === YBY_Mail_V2_P2_Planner::plan( $rows, $limits, 100, $off )['status'], 'no production plans' );
$off = $cfg; $off['health_ok'] = false;
p2_check( 'health_gate' === YBY_Mail_V2_P2_Planner::plan( $rows, $limits, 100, $off )['reason'], 'unhealthy worker fail closed' );
$bad_budget = $limits; unset( $bad_budget['a'] );
p2_check( 'unverified_sender_budget' === YBY_Mail_V2_P2_Planner::plan( $rows, $bad_budget, 100, $cfg )['reason'], 'no default sender quota' );
$bad_budget = $limits; $bad_budget['a']['source'] = 'unverified';
p2_check( 'invalid_sender_budget' === YBY_Mail_V2_P2_Planner::plan( $rows, $bad_budget, 100, $cfg )['reason'], 'unverified quota fails' );
$rows2 = array( p2_item( 'future', 'a', 'account', 101 ), p2_item( 'done', 'b', 'campaign', 100, 'accepted_by_provider' ),
    p2_item( 'unknown', 'a', 'account', 100, 'uncertain' ), p2_item( 'exhausted', 'b', 'campaign', 100, 'queued', 3 ) );
p2_check( 0 === count( YBY_Mail_V2_P2_Planner::plan( $rows2, $limits, 100, $cfg )['claims'] ), 'no premature/accepted/uncertain/exhausted item' );
$malformed = $rows; $malformed[0]['recipient'] = 'should-never-be-copied@example.test';
p2_check( 'invalid_row' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'recipient field fails whole plan' );
$malformed = $rows; $malformed[] = $rows[0];
p2_check( 'duplicate_identity' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'no duplicate idempotency key' );
$malformed = $rows; $malformed[0]['state_version'] = -1;
p2_check( 'invalid_row' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'invalid CAS version fails closed' );
$malformed = $rows; $malformed[0]['priority'] = 0;
p2_check( 'invalid_row' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'no forged elevated priority' );
$malformed = $rows; $malformed[0]['sender_key'] = 'person@example.test';
p2_check( 'invalid_row' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'no address sender' );
$malformed = $rows; $malformed[0]['opaque_id'] = 'plain-person-id';
p2_check( 'invalid_row' === YBY_Mail_V2_P2_Planner::plan( $malformed, $limits, 100, $cfg )['reason'], 'opaque IDs required' );
$one = $p['claims'][0];
$lease = YBY_Mail_V2_P2_Planner::lease_proposal( $one, 100, 60 );
p2_check( $one['expected_version'] === $lease['where_version'] && 'leased' === $lease['set_state'], 'CAS proposal only' );
p2_check( 160 === $lease['lease_expires_at'] && 1 === $lease['set_version'], 'lease expiry and next version' );
p2_check( null === YBY_Mail_V2_P2_Planner::lease_proposal( $one, 100, 20 ), 'short lease rejected' );
p2_check( null === YBY_Mail_V2_P2_Planner::lease_proposal( $one, 100, 301 ), 'long lease rejected' );
$copied = $one; $copied['action_group'] = 'woocommerce';
p2_check( null === YBY_Mail_V2_P2_Planner::lease_proposal( $copied, 100, 60 ), 'never claim Woo group' );
$stored_version = 0;
$attempts = array( $lease, $lease );
$winners = 0;
foreach ( $attempts as $candidate ) {
    if ( $candidate['where_version'] === $stored_version ) { $stored_version = $candidate['set_version']; ++$winners; }
}
p2_check( 1 === $winners, 'simulated atomic CAS one winner across workers' );
p2_check( null === YBY_Mail_V2_P2_Planner::recover_expired( 'sending', 120, 119, false ), 'not expired cannot recover' );
p2_check( 'uncertain' === YBY_Mail_V2_P2_Planner::recover_expired( 'sending', 120, 120, true ), 'crashed sending uncertain even with weak proof' );
p2_check( 'uncertain' === YBY_Mail_V2_P2_Planner::recover_expired( 'leased', 120, 121, false ), 'unknown lease cannot retry' );
p2_check( 'deferred' === YBY_Mail_V2_P2_Planner::recover_expired( 'leased', 120, 121, true ), 'explicit never-transmitted lease may defer' );
p2_check( null === YBY_Mail_V2_P2_Planner::recover_expired( 'accepted_by_provider', 100, 200, true ), 'accepted is terminal' );
$as = array( 'synthetic_only' => true, 'as_version' => '4.1.0', 'db_stable' => true, 'woo_jobs_isolated' => true );
p2_check( YBY_Mail_V2_P2_Scheduler::compatibility( $as )['ready_to_preview'], 'compat preview safe snapshot' );
p2_check( ! YBY_Mail_V2_P2_Scheduler::compatibility( $as )['runtime_enabled'], 'runtime always off' );
$actions = YBY_Mail_V2_P2_Scheduler::preview_actions( $p['claims'], $as );
p2_check( 5 === count( $actions ), 'five dry action proposals' );
foreach ( $actions as $act ) {
    p2_check( 'andy_core_mail_v2' === $act['group'] && !$act['scheduled'] && !$act['executed'], 'no scheduled Woo jobs' );
    p2_check( array( 'opaque_id', 'version' ) === array_keys( $act['args'] ), 'opaque args only' );
}
$as_off = $as; $as_off['synthetic_only'] = false;
p2_check( ! YBY_Mail_V2_P2_Scheduler::compatibility( $as_off )['ready_to_preview'], 'requires synthetic snapshot' );
p2_check( array() === YBY_Mail_V2_P2_Scheduler::preview_actions( $p['claims'], $as_off ), 'no production schedule' );
$as_bad = $as; $as_bad['db_stable'] = false;
p2_check( array() === YBY_Mail_V2_P2_Scheduler::preview_actions( $p['claims'], $as_bad ), 'unstable database no proposal' );
$as_bad = $as; $as_bad['woo_jobs_isolated'] = false;
p2_check( ! YBY_Mail_V2_P2_Scheduler::compatibility( $as_bad )['ready_to_preview'], 'woo isolation required' );
$bad_actions = $p['claims']; $bad_actions[0]['to'] = 'someone@example.test';
p2_check( array() === YBY_Mail_V2_P2_Scheduler::preview_actions( $bad_actions, $as ), 'no private scheduler args' );
$duplicate_actions = array( $p['claims'][0], $p['claims'][0] );
p2_check( array() === YBY_Mail_V2_P2_Scheduler::preview_actions( $duplicate_actions, $as ), 'dedupe planned actions' );
$bucket = array( 'capacity' => 4, 'tokens' => 1, 'refill_interval' => 60, 'last_refill' => 100, 'source' => 'synthetic_verified' );
p2_check( 1 === YBY_Mail_V2_P2_Scheduler::replenish( $bucket, 159 )['tokens'], 'no early replenishment' );
p2_check( 2 === YBY_Mail_V2_P2_Scheduler::replenish( $bucket, 160 )['tokens'], 'refill on interval' );
p2_check( 4 === YBY_Mail_V2_P2_Scheduler::replenish( $bucket, 1000 )['tokens'], 'capacity cap' );
p2_check( null === YBY_Mail_V2_P2_Scheduler::replenish( $bucket, 99 ), 'clock rollback fails closed' );
$bucket['source'] = 'unverified';
p2_check( null === YBY_Mail_V2_P2_Scheduler::replenish( $bucket, 220 ), 'no guessed provider budget' );
p2_check( false === YBY_Mail_V2_Governance::QUEUE_ENABLED && false === YBY_Mail_V2_Governance::OUTBOUND_ENABLED, 'inert P1 gates preserved' );
p2_check( ! function_exists( 'wp_mail' ) && ! function_exists( 'as_enqueue_async_action' ), 'no runtime functions defined' );
// High-volume synthetic fairness and isolation, no WordPress or MySQL runtime.
$load = array();
for ( $i = 0; $i < 1000; ++$i ) {
    $sender = ( $i % 2 ) ? 'a' : 'b';
    $load[] = p2_item( 'synthetic-' . $i, $sender, $i < 10 ? 'critical_system' : 'campaign' );
    if ( $i < 10 ) { $load[ $i ]['sender_key'] = 'critical'; }
}
$load_limits = array( 'a' => p2_budget( 50, 50 ), 'b' => p2_budget( 50, 50 ), 'critical' => p2_budget( 50, 50 ) );
$load_cfg = array( 'synthetic_only' => true, 'health_ok' => true, 'max_batch' => 50, 'critical_reserve' => 10 );
$load_plan = YBY_Mail_V2_P2_Planner::plan( $load, $load_limits, 200, $load_cfg );
p2_check( 'dry_run' === $load_plan['status'] && 50 === count( $load_plan['claims'] ), '1000 synthetic items capped at 50' );
p2_check( 10 === count( array_filter( $load_plan['claims'], static fn( $x ) => 0 === $x['priority'] ) ), 'critical capacity reserved under flood' );
$ids = array_column( $load_plan['claims'], 'opaque_id' );
p2_check( 50 === count( array_unique( $ids ) ), 'no duplicate claims under heavy load' );
p2_check( 40 === count( array_filter( $load_plan['claims'], static fn( $x ) => 2 === $x['priority'] ) ), 'bounded campaign capacity' );
$preview = YBY_Mail_V2_P2_Scheduler::preview_actions( $load_plan['claims'], $as );
p2_check( 50 === count( $preview ), '50 simulated actions and no scheduler enqueue' );
p2_check( ! preg_match( '/@|password|recipient/', json_encode( $preview ) ), 'no private data in action preview' );
echo 'MAIL_V2_P2_SIMULATION_PASS assertions=' . $assertions . "\n";
