<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
class YBY_Config { public static function is_tracking_enabled() { return true; } public static function get_default_product_interest() { return ''; } }
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-event-contract.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-tracking.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$expected = array( 'generate_lead','thank_you_page_view','click_whatsapp','click_whatsapp_after_lead','download_catalog','submit_project_details','return_to_lp','view_case_study' );
$assert( YBY_Analytics_Event_Contract::VERSION === '1', 'contract version' );
$assert( YBY_Analytics_Event_Contract::names() === $expected, 'canonical event names must preserve compatibility order' );
foreach ( $expected as $event ) {
    $assert( YBY_Analytics_Event_Contract::has( $event ), 'missing event ' . $event );
    $meta = YBY_Analytics_Event_Contract::describe( $event );
    $assert( isset( $meta['category'], $meta['dedupe'], $meta['pii'] ), 'metadata missing for ' . $event );
    $assert( false === $meta['pii'], 'business event contract must not authorize PII' );
}
$assert( ! YBY_Analytics_Event_Contract::has( 'arbitrary_event' ), 'arbitrary event must not enter canonical contract' );
$config = ( new YBY_Tracking() )->get_frontend_config();
$assert( $config['events'] === $expected, 'legacy tracking facade must source canonical events' );
$inquiry = file_get_contents( dirname( __DIR__ ) . '/public/js/yby-inquiry-components.js' );
$assert( false === strpos( $inquiry, 'window.dataLayer.push' ), 'Inquiry must not push directly to dataLayer' );
$assert( false !== strpos( $inquiry, 'window.YBYTracking.push' ), 'Inquiry must use tracking facade' );
echo "PASS v181-analytics-event-contract-harness\n";
