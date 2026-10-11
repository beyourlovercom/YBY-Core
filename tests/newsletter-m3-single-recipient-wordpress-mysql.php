<?php
/**
 * M3 single-mailbox double-opt-in E2E: disposable WP/MySQL, intercepted mail.
 * Run ONLY inside the existing GitHub Actions newsletter-validation.test
 * WordPress/MySQL fixture, after the M1/M2 fixture tests.
 *
 * All wp_mail attempts are intercepted by pre_wp_mail. Never contacts any
 * mailbox, never creates a Dev or Production subscriber, never uses live data.
 */
if ( ! defined( 'WP_CLI' ) || true !== WP_CLI ) { exit( 8 ); }
if ( ! defined( 'ABSPATH' ) || 'newsletter-validation.test' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
    fwrite( STDERR, "M3_E2E_REFUSED_NON_DISPOSABLE_WP\n" );
    exit( 8 );
}
if ( ! defined( 'YBY_NEWSLETTER_API_ENABLED' ) ) { define( 'YBY_NEWSLETTER_API_ENABLED', true ); }
if ( ! defined( 'YBY_NEWSLETTER_OUTBOUND_ENABLED' ) ) { define( 'YBY_NEWSLETTER_OUTBOUND_ENABLED', true ); }

function m3e_assert( $test, $description ) {
    $GLOBALS['m3e_assertions'] = (int) ( $GLOBALS['m3e_assertions'] ?? 0 ) + 1;
    if ( ! $test ) { throw new RuntimeException( 'M3_E2E_FAIL ' . $description ); }
    echo 'PASS ' . $description . "\n";
}
function m3e_request( $method, $action, $data = array() ) {
    $request = new WP_REST_Request( $method, '/andy-core/v1/newsletter/' . $action );
    $request->set_header( 'content-type', 'application/json' );
    $request->set_header( 'origin', 'http://newsletter-validation.test' );
    $request->set_body( wp_json_encode( $data ) );
    foreach ( $data as $key => $value ) { $request->set_param( $key, $value ); }
    return rest_do_request( $request );
}
global $wpdb;
$table = YBY_Newsletter_Store::table_name();
m3e_assert( YBY_Newsletter_Store::exists(), 'M1 isolated MySQL schema available' );
$email = 'm3-owner-only-' . bin2hex( random_bytes( 6 ) ) . '@example.test';
$email_hash = hash( 'sha256', $email );
$site = YBY_Newsletter_Store::site_key();
$before_rows = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table );
m3e_assert( null === YBY_Newsletter_Store::find_email( $email ), 'Unique synthetic owner mailbox absent' );
m3e_assert( isset( rest_get_server()->get_routes()['/andy-core/v1/newsletter/subscribe'] ), 'Native WordPress REST subscribe route exists' );

$api_option = get_option( 'yby_newsletter_enabled', '__M3_NOT_SET__' );
$mail_option = get_option( 'yby_newsletter_outbound_enabled', '__M3_NOT_SET__' );
// Earlier disposable M1 tests intentionally exhaust their own IP rate limit.
// Give M3 a unique RFC5737 documentation-only fixture client IP, and restore.
$prior_ip = $_SERVER['REMOTE_ADDR'] ?? null;
$_SERVER['REMOTE_ADDR'] = '192.0.2.' . random_int( 10, 200 );
$mail_attempts = 0;
$captured = array();
$rejects = 0;
$payload = array(
    'email' => $email,
    'marketing_consent' => '1',
    'consent_policy' => 'newsletter-v1',
    'source' => 'shortcode',
    'source_page' => '/newsletter/',
    'locale' => 'en-US',
    'website' => '',
);

// This test's terminal handler denies every unexpected recipient/payload,
// even if an earlier WP hook unexpectedly returns a nonnull result.
add_filter( 'pre_wp_mail', static function ( $pre, $mail ) use ( &$mail_attempts, &$captured, &$rejects, $email ) {
    $mail_attempts++;
    $to = $mail['to'] ?? null;
    if ( is_array( $to ) ) { $to = count( $to ) === 1 ? reset( $to ) : null; }
    $message = $mail['message'] ?? null;
    $exact = null === $pre
        && $mail_attempts === 1
        && is_string( $to ) && hash_equals( $email, strtolower( trim( $to ) ) )
        && ( $mail['subject'] ?? null ) === 'Confirm your newsletter subscription'
        && is_string( $message ) && strlen( $message ) <= 4096
        && empty( $mail['headers'] ) && empty( $mail['attachments'] ) && empty( $mail['embeds'] )
        && preg_match( '~\APlease confirm your email subscription by visiting:\n'
            . 'http://newsletter-validation\.test/\?yby_newsletter_action=confirm&token=[a-f0-9]{64}'
            . '\n\nIf you did not request this, ignore this email\. You can unsubscribe using:\n'
            . 'http://newsletter-validation\.test/\?yby_newsletter_action=unsubscribe&token=[a-f0-9]{64}\z~D', $message );
    if ( ! $exact ) { $rejects++; return false; }
    $captured[] = $mail;
    return true; // Interception: WordPress will never instantiate/send PHPMailer.
}, PHP_INT_MAX, 2 );

// Fail closed for any WP HTTP calls in the isolated fixture.
add_filter( 'pre_http_request', static function () {
    return new WP_Error( 'm3_e2e_no_network', 'External HTTP forbidden in synthetic newsletter E2E' );
}, PHP_INT_MAX, 3 );

try {
    // Both site options must be OFF for the negative case.
    update_option( 'yby_newsletter_enabled', '0' );
    update_option( 'yby_newsletter_outbound_enabled', '0' );
    m3e_assert( ! YBY_Newsletter_REST_Controller::enabled() &&
        ! YBY_Newsletter_REST_Controller::outbound_enabled(), 'Both public runtime gates start CLOSED' );
    m3e_assert( 503 === m3e_request( 'POST', 'subscribe', $payload )->get_status(),
        'Public REST signup denied while closed, before any DB insert or email' );
    m3e_assert( null === YBY_Newsletter_Store::find_email( $email ) && 0 === $mail_attempts,
        'Closed signup has no synthetic record or mail attempt' );

    // Enabled ONLY inside disposable WP-CLI fixture, with a guaranteed no-mail
    // interception installed BEFORE enabling these fixture-local options.
    update_option( 'yby_newsletter_enabled', '1' );
    update_option( 'yby_newsletter_outbound_enabled', '1' );
    m3e_assert( YBY_Newsletter_REST_Controller::enabled() &&
        YBY_Newsletter_REST_Controller::outbound_enabled(), 'Double release gates open in disposable fixture ONLY' );

    $bad = $payload;
    $bad['marketing_consent'] = '0';
    m3e_assert( 400 === m3e_request( 'POST', 'subscribe', $bad )->get_status() &&
        null === YBY_Newsletter_Store::find_email( $email ), 'Unconsented form never records a subscriber' );
    $bad = $payload;
    $bad['email'] = 'not-an-email';
    m3e_assert( 400 === m3e_request( 'POST', 'subscribe', $bad )->get_status(),
        'Malformed mailbox rejected without email' );

    $result = m3e_request( 'POST', 'subscribe', $payload );
    m3e_assert( 200 === $result->get_status() && 1 === count( $captured ) &&
        1 === $mail_attempts && 0 === $rejects,
        'Exactly one approved synthetic confirmation intercepted, no transport'
        . ' (REST=' . (int) $result->get_status() . ', attempts=' . (int) $mail_attempts
        . ', accepted=' . count( $captured ) . ', rejected=' . (int) $rejects . ')' );
    m3e_assert( $captured[0]['to'] === $email &&
        $captured[0]['subject'] === 'Confirm your newsletter subscription', 'Synthetic mail strictly bound to one mailbox and subject' );
    $row = YBY_Newsletter_Store::find_email( $email );
    m3e_assert( is_array( $row ) && $row['status'] === 'pending' &&
        null === $row['subscribed_at'], 'Owner-only fixture persists pending, not subscribed' );
    m3e_assert( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table ) === $before_rows + 1,
        'One new isolated row, existing synthetic fixtures unchanged' );

    $body = $captured[0]['message'];
    preg_match( '/yby_newsletter_action=confirm&token=([a-f0-9]{64})/', $body, $confirm );
    preg_match( '/yby_newsletter_action=unsubscribe&token=([a-f0-9]{64})/', $body, $unsubscribe );
    m3e_assert( isset( $confirm[1], $unsubscribe[1] ) && $confirm[1] !== $unsubscribe[1],
        'Distinct 64-character confirm and unsubscribe links captured only in memory' );
    m3e_assert( hash_equals( $row['confirm_hash'], YBY_Newsletter_Store::token_hash( $confirm[1] ) ) &&
        hash_equals( $row['unsubscribe_hash'], YBY_Newsletter_Store::token_hash( $unsubscribe[1] ) ),
        'MySQL stores HMAC token hashes, never raw links' );

    // Link scanner GET is inert, even if the token is valid.
    $get = m3e_request( 'GET', 'confirm', array( 'token' => $confirm[1] ) );
    m3e_assert( 200 !== $get->get_status() &&
        YBY_Newsletter_Store::find_email( $email )['status'] === 'pending',
        'Scanner GET cannot confirm subscription' );
    m3e_assert( 400 === m3e_request( 'POST', 'confirm', array( 'token' => str_repeat( '0', 64 ) ) )->get_status(),
        'Invalid confirmation token rejected' );
    m3e_assert( 200 === m3e_request( 'POST', 'confirm', array( 'token' => $confirm[1] ) )->get_status(),
        'Deliberate confirm POST changes pending to subscribed' );
    $subscribed = YBY_Newsletter_Store::find_email( $email );
    m3e_assert( $subscribed['status'] === 'subscribed' &&
        null !== $subscribed['subscribed_at'] && null === $subscribed['confirm_hash'],
        'Confirmed state persisted and one-time token burned' );
    m3e_assert( 400 === m3e_request( 'POST', 'confirm', array( 'token' => $confirm[1] ) )->get_status(),
        'Replay confirm POST denied' );
    m3e_assert( 200 === m3e_request( 'POST', 'subscribe', $payload )->get_status() &&
        1 === count( $captured ) && 1 === $mail_attempts,
        'Already subscribed is idempotent without second email' );

    // Existing Core opt-out contract is exercised on the same test row.
    m3e_assert( 200 !== m3e_request( 'GET', 'unsubscribe', array( 'token' => $unsubscribe[1] ) )->get_status() &&
        YBY_Newsletter_Store::find_email( $email )['status'] === 'subscribed',
        'Unsubscribe GET cannot mutate consent' );
    m3e_assert( 200 === m3e_request( 'POST', 'unsubscribe', array( 'token' => $unsubscribe[1] ) )->get_status() &&
        YBY_Newsletter_Store::find_email( $email )['status'] === 'unsubscribed',
        'Explicit unsubscribe POST accepted' );
    m3e_assert( 400 === m3e_request( 'POST', 'unsubscribe', array( 'token' => $unsubscribe[1] ) )->get_status(),
        'Unsubscribe replay denied' );
    m3e_assert( 1 === $mail_attempts && 0 === $rejects,
        'One synthetic mailbox, one intercepted message throughout the entire E2E' );
} finally {
    // Never touch real user or other fixture rows. Cleanup is scoped by all
    // three identifiers and occurs even if a test assertion raises.
    $wpdb->delete( $table, array(
        'email' => $email, 'email_hash' => $email_hash, 'site_key' => $site,
    ), array( '%s', '%s', '%s' ) );
    if ( $api_option === '__M3_NOT_SET__' ) { delete_option( 'yby_newsletter_enabled' ); }
    else { update_option( 'yby_newsletter_enabled', $api_option ); }
    if ( $mail_option === '__M3_NOT_SET__' ) { delete_option( 'yby_newsletter_outbound_enabled' ); }
    else { update_option( 'yby_newsletter_outbound_enabled', $mail_option ); }
    if ( null === $prior_ip ) { unset( $_SERVER['REMOTE_ADDR'] ); }
    else { $_SERVER['REMOTE_ADDR'] = $prior_ip; }
}
m3e_assert( null === YBY_Newsletter_Store::find_email( $email ) &&
    (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table ) === $before_rows,
    'Exact synthetic owner row deleted; baseline row count restored' );
m3e_assert( get_option( 'yby_newsletter_enabled', '__M3_NOT_SET__' ) === $api_option &&
    get_option( 'yby_newsletter_outbound_enabled', '__M3_NOT_SET__' ) === $mail_option,
    'All fixture-local Newsletter site options restored' );
echo 'NEWSLETTER_M3_SINGLE_RECIPIENT_E2E_PASS '
    . (int) $GLOBALS['m3e_assertions']
    . " assertions; 1 fake mail; 0 real mail; 0 external HTTP; 0 residual test records\n";
