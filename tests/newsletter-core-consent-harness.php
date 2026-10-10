<?php
/** Offline Newsletter M1 consent/ERP contract, no DB or mail. */
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/inc/class-yby-newsletter-consent.php';
$passed = 0;
function newsletter_check( $truth, $label ) {
    global $passed;
    if ( ! $truth ) { fwrite( STDERR, 'FAIL ' . $label . PHP_EOL ); exit( 1 ); }
    ++$passed;
    echo 'PASS ' . $label . PHP_EOL;
}
$base = '2026-10-10T01:00:00Z';
$confirm = '2026-10-10T01:05:00Z';
$unsubscribe = '2026-10-10T02:00:00Z';
$late = '2026-10-10T03:00:00Z';
newsletter_check( 'user@example.com' === YBY_Newsletter_Consent::normalize_email( ' User@EXAMPLE.com ' ), 'normalized exact email' );
newsletter_check( null === YBY_Newsletter_Consent::normalize_email( 'not an email' ), 'invalid email denied' );
newsletter_check( null === YBY_Newsletter_Consent::normalize_email( str_repeat( 'a', 192 ).'@example.com' ), 'oversized email denied' );
$id = YBY_Newsletter_Consent::external_id( 'site-a.example', 'User@EXAMPLE.com' );
newsletter_check( strlen( $id ) > 64 && strpos( $id, 'user@example.com' ) === false, 'external ID is hashed, no plain email' );
newsletter_check( $id === YBY_Newsletter_Consent::external_id( 'site-a.example', 'user@example.com' ), 'site + email ID is repeatable' );
newsletter_check( $id !== YBY_Newsletter_Consent::external_id( 'site-b.example', 'user@example.com' ), 'same email on another site gets distinct source fact' );
newsletter_check( null === YBY_Newsletter_Consent::external_id( 'https://site-a.example/path', 'user@example.com' ), 'unsafe source identity denied' );
$pending = YBY_Newsletter_Consent::create_pending( 'site-a.example', 'USER@example.com', $base );
newsletter_check( $pending['status'] === 'pending' && $pending['subscribed_at'] === null, 'form submission is not subscribed' );
newsletter_check( $pending['email'] === 'user@example.com' && $pending['consent_source'] === 'andy_core_newsletter_form', 'source provenance preserved' );
newsletter_check( null === YBY_Newsletter_Consent::create_pending( 'site-a.example', 'invalid', $base ), 'invalid address cannot register' );
newsletter_check( null === YBY_Newsletter_Consent::create_pending( 'site-a.example', 'user@example.com', 'not a time' ), 'invalid timestamp denied' );
$projectPending = YBY_Newsletter_Consent::erp_snapshot_item( $pending );
newsletter_check( $projectPending['status'] === 'pending' && $projectPending['provider'] === 'wordpress', 'ERP projection pending, not EDM eligible' );
$pending['confirmation_token_hash'] = 'secret-hash-never-exported';
$pending['ip_fingerprint'] = 'private-hash';
$projectPending = YBY_Newsletter_Consent::erp_snapshot_item( $pending );
newsletter_check( ! isset( $projectPending['confirmation_token_hash'], $projectPending['ip_fingerprint'] )
    && ! array_key_exists( 'ip_fingerprint', $projectPending ), 'ERP projection excludes token and IP metadata' );
newsletter_check( $projectPending['external_subscription_id'] === $id, 'stable ERP external subscription key' );
$confirmed = YBY_Newsletter_Consent::transition( $pending, 'confirm', $confirm );
newsletter_check( $confirmed['status'] === 'subscribed' && $confirmed['subscribed_at'] === $confirm, 'only confirmed event activates marketing consent' );
newsletter_check( $confirmed === YBY_Newsletter_Consent::transition( $confirmed, 'confirm', $late ), 'duplicate confirm idempotent' );
$unsub = YBY_Newsletter_Consent::transition( $confirmed, 'unsubscribe', $unsubscribe );
newsletter_check( $unsub['status'] === 'unsubscribed' && $unsub['unsubscribed_at'] === $unsubscribe, 'unsubscribe stored as explicit authority' );
newsletter_check( $unsub === YBY_Newsletter_Consent::transition( $unsub, 'confirm', $late ), 'replayed confirmation cannot resubscribe' );
newsletter_check( $unsub === YBY_Newsletter_Consent::transition( $unsub, 'unsubscribe', $late ), 'duplicate unsubscribe idempotent' );
newsletter_check( $unsub === YBY_Newsletter_Consent::transition( $unsub, 'resubscribe', $base ), 'out-of-order earlier request ignored' );
$again = YBY_Newsletter_Consent::transition( $unsub, 'resubscribe', $late );
newsletter_check( $again['status'] === 'pending' && $again['subscribed_at'] === null, 'resubscribe needs new inbox confirmation' );
newsletter_check( YBY_Newsletter_Consent::transition( $again, 'confirm', '2026-10-10T03:10:00Z' )['status'] === 'subscribed', 'new confirmation may reactivate' );
$suppressed = YBY_Newsletter_Consent::transition( $unsub, 'suppress', $late );
newsletter_check( $suppressed['status'] === 'suppressed', 'suppressed state takes precedence' );
newsletter_check( $suppressed === YBY_Newsletter_Consent::transition( $suppressed, 'resubscribe', '2026-10-11T01:00:00Z' ), 'suppression not reactivated by signup form' );
newsletter_check( $suppressed === YBY_Newsletter_Consent::transition( $suppressed, 'confirm', '2026-10-11T01:00:00Z' ), 'suppression not bypassed by inbox confirm' );
$spoof = $pending;
$spoof['external_subscription_id'] = 'forged';
newsletter_check( null === YBY_Newsletter_Consent::erp_snapshot_item( $spoof ), 'forged provider ID cannot be imported' );
newsletter_check( null === YBY_Newsletter_Consent::transition( $pending, 'force_subscribe', $late ), 'unsupported event denied' );
$source = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-newsletter-consent.php' );
newsletter_check( strpos( $source, 'wp_mail(' ) === false && strpos( $source, 'dbDelta(' ) === false && strpos( $source, 'register_rest_route(' ) === false, 'domain module has no implicit writes or outbound effects' );
echo 'NEWSLETTER_CORE_ERP_CONSENT_CONTRACT_PASS ' . $passed . ' assertions' . PHP_EOL;
