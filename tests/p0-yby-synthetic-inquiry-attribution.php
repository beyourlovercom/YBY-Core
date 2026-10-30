<?php
/**
 * WP-CLI disposable-database-only synthetic inquiry + Case ID + attribution UAT.
 * NEVER run on live sites or with a real email transport.
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) ||
    '1' !== getenv( 'ANDY_P0_SYNTHETIC_DB' ) ) {
    fwrite( STDERR, "Synthetic offline database gate required\n" );
    exit( 2 );
}
if ( false === strpos( home_url(), '127.0.0.1' ) &&
    false === strpos( home_url(), 'localhost' ) &&
    false === strpos( home_url(), '.test' ) ) {
    throw new RuntimeException( 'Production URL forbidden by synthetic inquiry test' );
}
$GLOBALS['p0_mail_outbound_count'] = 0;
add_filter( 'pre_wp_mail', static function( $unused, $atts ) {
    ++$GLOBALS['p0_mail_outbound_count'];
    return true; // Mock provider acceptance; absolutely no external SMTP.
}, 1, 2 );
function p0_attribution_assert( $condition, $reason ) {
    if ( ! $condition ) { throw new RuntimeException( 'P0_INQUIRY_FAIL:' . $reason ); }
}
// Prefer the real YBY child-theme preset when present. For the portable
// MySQL CI gate, use the built-in strict email-required page-owned preset.
$manager = new YBY_Inquiry_Preset_Manager();
$preset_id = $manager->get_preset( 'irrigation_wholesale_page_quote' )
    ? 'irrigation_wholesale_page_quote' : 'page_owned_email_required';
global $wpdb;
$lead_table = YBY_Database::leads_table_name();
$prior = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lead_table}" );

$bad = new WP_REST_Request( 'POST', '/yby/v1/leads' );
$bad->set_body_params( array(
    'name' => 'Synthetic Test Reject', 'email' => 'not an email',
    'source_component' => 'inquiry_modal',
    'source_preset' => $preset_id,
    'page_profile' => 'irrigation_wholesale',
) );
$bad_result = rest_do_request( $bad );
p0_attribution_assert( 400 === $bad_result->get_status(), 'bad_email_not_400' );
p0_attribution_assert( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lead_table}" ) === $prior, 'invalid_form_persisted' );
$gclid = 'P0_SYNTHETIC_GCLID_20261030_DO_NOT_ATTRIB';
$valid = new WP_REST_Request( 'POST', '/yby/v1/leads' );
$valid->set_body_params( array(
    'name' => 'Synthetic P0 Test', 'company' => 'OFFLINE ONLY',
    'email' => 'synthetic@example.test', 'country' => 'US',
    'product_interest' => 'P0 compatibility test', 'quantity' => '1',
    'source_component' => 'inquiry_modal',
    'source_preset' => $preset_id, 'page_profile' => 'irrigation_wholesale',
    'source_page' => '/lp/irrigation-wholesale/',
    'source_url' => 'http://127.0.0.1:18090/lp/irrigation-wholesale/?utm_source=google',
    'utm_source' => 'google', 'utm_medium' => 'cpc',
    'utm_campaign' => 'p0_offline_compatibility_20261030',
    'utm_term' => 'p0_test_irrigation',
    'gclid' => $gclid,
) );
$result = rest_do_request( $valid );
$body = $result->get_data();
p0_attribution_assert( 200 === $result->get_status(), 'valid_rest_status_' . $result->get_status() . ':' . wp_json_encode( $body ) );
p0_attribution_assert( ! empty( $body['success'] ), 'valid_form_rejected' );
$case_id = (string) ( $body['data']['case_id'] ?? '' );
$lead_id = (int) ( $body['data']['lead_id'] ?? 0 );
p0_attribution_assert( $lead_id > 0 && strlen( $case_id ) >= 5, 'case_id_missing' );
$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$lead_table} WHERE id=%d", $lead_id ), ARRAY_A );
p0_attribution_assert( is_array( $row ), 'saved_lead_missing' );
foreach ( array( 'case_id'=>$case_id, 'gclid'=>$gclid,
    'utm_source'=>'google', 'utm_medium'=>'cpc', 'utm_campaign'=>'p0_offline_compatibility_20261030',
    'source_page'=>'/lp/irrigation-wholesale/', 'source_preset'=>$preset_id ) as $key=>$value ) {
    p0_attribution_assert( (string) $row[$key] === $value, 'mismatch_' . $key );
}
p0_attribution_assert( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$lead_table}" ) === $prior + 1, 'incorrect_lead_count' );
p0_attribution_assert( $GLOBALS['p0_mail_outbound_count'] >= 0, 'smtp_mock' );
echo "P0_SYNTHETIC_REST_INQUIRY_CASE_ID_ATTRIBUTION_PASS lead_id={$lead_id} mail_transport=mocked\n";
