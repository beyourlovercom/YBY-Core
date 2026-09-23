<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['yby_consent_filters'] = array();
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function apply_filters( $tag, $value ) { return array_key_exists( $tag, $GLOBALS['yby_consent_filters'] ) ? $GLOBALS['yby_consent_filters'][ $tag ] : $value; }
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-consent-adapter.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$cfg = YBY_Analytics_Consent_Adapter::runtime_config();
$assert( 'respect_existing' === $cfg['mode'], 'consent mode' );
$assert( 'existing_runtime' === $cfg['source'], 'default consent source' );
$assert( false === $cfg['owns_consent_state'], 'Andy Core must not own consent' );
$assert( false === $cfg['writes_google_consent'], 'Andy Core must not write Google consent' );
$assert( false === $cfg['mutates_cmp'], 'Andy Core must not mutate CMP' );
foreach ( $cfg['state'] as $value ) { $assert( 'unknown' === $value, 'default consent must stay unknown' ); }
$GLOBALS['yby_consent_filters'][YBY_Analytics_Consent_Adapter::SOURCE_FILTER] = 'cookie_cmp_v1';
$GLOBALS['yby_consent_filters'][YBY_Analytics_Consent_Adapter::STATE_FILTER] = array(
    'analytics_storage' => 'granted',
    'ad_storage' => 'denied',
    'ad_user_data' => 'INVALID',
    'ad_personalization' => 'granted',
    'extra_key' => 'granted',
);
$cfg = YBY_Analytics_Consent_Adapter::runtime_config();
$assert( 'cookie_cmp_v1' === $cfg['source'], 'filtered source' );
$assert( 'granted' === $cfg['state']['analytics_storage'], 'granted analytics state' );
$assert( 'denied' === $cfg['state']['ad_storage'], 'denied ad state' );
$assert( 'unknown' === $cfg['state']['ad_user_data'], 'invalid state fails closed to unknown' );
$assert( ! isset( $cfg['state']['extra_key'] ), 'unknown consent keys rejected' );
$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$tracking = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-tracking.php' );
$assert( false !== strpos( $core, 'class-yby-analytics-consent-adapter.php' ), 'consent adapter bootstrap' );
$assert( false !== strpos( $tracking, "YBY_Analytics_Consent_Adapter::runtime_config()" ), 'tracking consent projection' );
echo "PASS v181-consent-adapter-harness\n";
