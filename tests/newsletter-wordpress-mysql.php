<?php
/**
 * Ephemeral WordPress/MySQL newsletter test (GitHub Actions only).
 * WP CLI and WP config are provisioned inside disposable CI MySQL.
 * All wp_mail calls intercepted. Never import actual emails/customers.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 8 ); }
if ( ! defined( 'YBY_NEWSLETTER_SCHEMA_APPLY' ) ) { define( 'YBY_NEWSLETTER_SCHEMA_APPLY', true ); }
if ( ! defined( 'YBY_NEWSLETTER_API_ENABLED' ) ) { define( 'YBY_NEWSLETTER_API_ENABLED', true ); }
if ( ! defined( 'YBY_NEWSLETTER_OUTBOUND_ENABLED' ) ) { define( 'YBY_NEWSLETTER_OUTBOUND_ENABLED', true ); }
$n = 0;
function nl_check( $yes, $label ) {
    global $n;
    if ( ! $yes ) { fwrite( STDERR, 'FAIL '.$label."\n" ); exit( 1 ); }
    $n++;
    echo 'PASS '.$label."\n";
}
global $wpdb;
nl_check( class_exists( 'YBY_Newsletter_REST_Controller' ), 'Core runtime loaded controller' );
nl_check( ! YBY_Newsletter_Store::exists(), 'No auto schema during WordPress plugin activation' );
nl_check( ! YBY_Newsletter_REST_Controller::enabled(), 'No public API before site option + schema' );
nl_check( YBY_Newsletter_Store::install_schema(), 'Explicit CLI-only MySQL schema install succeeds' );
nl_check( YBY_Newsletter_Store::exists(), 'New table physically exists' );
nl_check( ! YBY_Newsletter_REST_Controller::enabled(), 'Site gate still closed after schema install' );
update_option( 'yby_newsletter_enabled', '1' );
nl_check( YBY_Newsletter_REST_Controller::enabled(), 'Both API code and site gate enable readiness' );
nl_check( ! YBY_Newsletter_REST_Controller::outbound_enabled(), 'Email delivery disabled before separate option' );
update_option( 'yby_newsletter_outbound_enabled', '1' );
nl_check( YBY_Newsletter_REST_Controller::outbound_enabled(), 'Isolated mail gate allowed only by explicit setting' );

$captured = array();
add_filter( 'pre_wp_mail', function ( $pre, $atts ) use ( &$captured ) {
    $captured[] = $atts;
    return true; // Synthetic interception; absolutely no outbound email.
}, 1, 2 );
$controller = new YBY_Newsletter_REST_Controller();
$controller->register_routes();
$uri = '/andy-core/v1/newsletter/';
function nl_request( $action, $params = array(), $origin = '' ) {
    global $uri;
    $r = new WP_REST_Request( 'POST', $uri . $action );
    foreach ( $params as $key => $value ) { $r->set_param( $key, $value ); }
    if ( '' !== $origin ) { $r->set_header( 'origin', $origin ); }
    return rest_do_request( $r );
}
$payload = array(
    'email' => 'Synthetic+NL@example.test',
    'marketing_consent' => '1',
    'consent_policy' => 'newsletter-v1',
    'source' => 'shortcode',
    'source_page' => '/newsletter/',
    'locale' => 'en-US',
    'website' => '',
);
$bad = $payload; $bad['marketing_consent'] = '0';
$unchecked_response = nl_request( 'subscribe', $bad );
if ( 400 !== $unchecked_response->get_status() ) {
    echo 'DIAG_UNCHECKED_STATUS=' . $unchecked_response->get_status() . ' DATA=' .
        wp_json_encode( $unchecked_response->get_data() ) . "\n";
}
nl_check( 400 === $unchecked_response->get_status(), 'Unchecked consent rejected server side' );
$bad = $payload; $bad['email'] = 'invalid-mail';
nl_check( 400 === nl_request( 'subscribe', $bad )->get_status(), 'Invalid email rejected server side' );
nl_check( 400 === nl_request( 'subscribe', $payload, 'https://evil.test' )->get_status(), 'Cross-origin request rejected' );
$honeypot = $payload; $honeypot['website'] = 'bot';
nl_check( 200 === nl_request( 'subscribe', $honeypot )->get_status() && 0 === count( $captured ), 'Honeypot fakes acknowledgment but writes/sends nothing' );

$signup = nl_request( 'subscribe', $payload );
nl_check( 200 === $signup->get_status(), 'Valid explicit consent accepted with intercepted sender' );
nl_check( 1 === count( $captured ), 'Exactly one confirmation sent into test-only interception' );
$row = YBY_Newsletter_Store::find_email( 'synthetic+nl@example.test' );
nl_check( 'pending' === $row['status'] && null === $row['subscribed_at'], 'Pending is not an EDM subscriber' );
nl_check( 64 === strlen( $row['confirm_hash'] ) && 64 === strlen( $row['unsubscribe_hash'] ), 'Tokens only stored as hashes' );
nl_check( 1 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . YBY_Newsletter_Store::table_name() ), 'One local subscriber row' );
nl_check( '200' === (string) nl_request( 'subscribe', $payload )->get_status(), 'Repeat submission returns generic response' );
nl_check( 1 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . YBY_Newsletter_Store::table_name() ), 'Repeat submission did not create duplicate row' );

$email_body = $captured[0]['message'];
preg_match( '/yby_newsletter_action=confirm&token=([0-9a-f]{64})/', $email_body, $cm );
preg_match( '/yby_newsletter_action=unsubscribe&token=([0-9a-f]{64})/', $email_body, $um );
nl_check( isset( $cm[1], $um[1] ) && $cm[1] !== $um[1], 'Distinct random confirmation and unsubscribe links' );
nl_check( 400 === nl_request( 'confirm', array( 'token' => 'invalid' ) )->get_status(), 'Malformed confirmation token denied' );
nl_check( 200 === nl_request( 'confirm', array( 'token' => $cm[1] ) )->get_status(), 'Single-use confirmation changes state' );
nl_check( 'subscribed' === YBY_Newsletter_Store::find_email( 'synthetic+nl@example.test' )['status'], 'Confirmed status persisted in WP MySQL' );
nl_check( 400 === nl_request( 'confirm', array( 'token' => $cm[1] ) )->get_status(), 'Confirmation replay blocked' );
nl_check( 200 === nl_request( 'unsubscribe', array( 'token' => $um[1] ) )->get_status(), 'Unsubscribe link changes state' );
nl_check( 'unsubscribed' === YBY_Newsletter_Store::find_email( 'synthetic+nl@example.test' )['status'], 'Unsubscribe persisted in WP MySQL' );
nl_check( 400 === nl_request( 'confirm', array( 'token' => $cm[1] ) )->get_status(), 'Expired/consumed confirmation cannot reactivate opt-out' );
nl_check( 400 === nl_request( 'unsubscribe', array( 'token' => $um[1] ) )->get_status(), 'Duplicate opt-out is not a second state mutation' );

$resubmit = nl_request( 'subscribe', $payload );
nl_check( 200 === $resubmit->get_status(), 'Fresh explicit request after unsubscribe accepted' );
nl_check( 'pending' === YBY_Newsletter_Store::find_email( 'synthetic+nl@example.test' )['status'], 'Resubscribe requires fresh confirmation' );
nl_check( 1 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . YBY_Newsletter_Store::table_name() ), 'Reconsent did not create duplicate identity' );
nl_check( 0 === (int) $wpdb->get_var( $wpdb->prepare(
    'SELECT COUNT(*) FROM ' . YBY_Newsletter_Store::table_name() . " WHERE status = %s", 'subscribed'
) ), 'No unconfirmed subscriber eligible for EDM' );
nl_check( count( $captured ) >= 2, 'Reconsent generated a fresh intercepted confirmation, not real email' );
echo 'NEWSLETTER_M1_WP_MYSQL_E2E_PASS ' . $n . ' assertions (synthetic, no outbound)' . "\n";
