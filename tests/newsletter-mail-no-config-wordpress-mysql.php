<?php
/**
 * Disposable WordPress/MySQL negative test: API and outbound options are set
 * but SMTP transport is UNCONFIGURED, so REST rejects before pending insert.
 * Run after M1 MySQL schema test; real host/dev execution is prohibited.
 */
if ( ! defined( 'WP_CLI' ) || WP_CLI !== true ||
     ! defined( 'ABSPATH' ) || 'newsletter-validation.test' !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) {
    fwrite( STDERR, "TRANSPORT_NO_CONFIG_REFUSED_NON_DISPOSABLE_WP\n" ); exit( 8 );
}
if ( ! defined( 'YBY_NEWSLETTER_API_ENABLED' ) ) { define( 'YBY_NEWSLETTER_API_ENABLED', true ); }
if ( ! defined( 'YBY_NEWSLETTER_OUTBOUND_ENABLED' ) ) { define( 'YBY_NEWSLETTER_OUTBOUND_ENABLED', true ); }
if ( ! YBY_Newsletter_Store::exists() || YBY_Mail_Transport::readiness()['ready'] ) {
    fwrite( STDERR, "TRANSPORT_NO_CONFIG_FIXTURE_NOT_READY\n" ); exit( 9 );
}
global $wpdb;
$table = YBY_Newsletter_Store::table_name();
$baseline = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table );
$old_api = get_option( 'yby_newsletter_enabled', '__missing__' );
$old_mail = get_option( 'yby_newsletter_outbound_enabled', '__missing__' );
$attempts = 0;
add_filter( 'pre_wp_mail', function () use ( &$attempts ) { $attempts++; return false; }, PHP_INT_MAX, 2 );
$email = 'no-config-' . bin2hex( random_bytes( 4 ) ) . '@example.test';
try {
    update_option( 'yby_newsletter_enabled', '1' );
    update_option( 'yby_newsletter_outbound_enabled', '1' );
    if ( ! YBY_Newsletter_REST_Controller::enabled() || ! YBY_Newsletter_REST_Controller::outbound_enabled() ) {
        throw new RuntimeException( 'Core options were not enabled in isolated WP fixture' );
    }
    $request = new WP_REST_Request( 'POST', '/andy-core/v1/newsletter/subscribe' );
    $payload = array(
        'email' => $email,
        'marketing_consent' => '1',
        'consent_policy' => 'newsletter-v1',
        'source' => 'shortcode',
        'source_page' => '/newsletter/',
        'locale' => 'en-US',
        'website' => '',
    );
    $request->set_header( 'origin', 'http://newsletter-validation.test' );
    $request->set_header( 'content-type', 'application/json' );
    $request->set_body( wp_json_encode( $payload ) );
    foreach ( $payload as $key => $value ) { $request->set_param( $key, $value ); }
    $response = rest_do_request( $request );
    if ( 503 !== $response->get_status() ||
         null !== YBY_Newsletter_Store::find_email( $email ) ||
         (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table ) !== $baseline ||
         $attempts !== 0 ) {
        throw new RuntimeException( 'Unconfigured SMTP attempted mail or inserted pending subscriber' );
    }
} finally {
    if ( '__missing__' === $old_api ) { delete_option( 'yby_newsletter_enabled' ); }
    else { update_option( 'yby_newsletter_enabled', $old_api ); }
    if ( '__missing__' === $old_mail ) { delete_option( 'yby_newsletter_outbound_enabled' ); }
    else { update_option( 'yby_newsletter_outbound_enabled', $old_mail ); }
}
if ( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table ) !== $baseline ) {
    throw new RuntimeException( 'Disposable MySQL baseline row drifted' );
}
echo "NEWSLETTER_TRANSPORT_UNCONFIGURED_REST_FAIL_CLOSED_PASS; zero row and zero email\n";
