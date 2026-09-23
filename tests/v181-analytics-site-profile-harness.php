<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
class YBY_Config {
    public static function get_site_brand_key() { return 'YBY-Irrigation'; }
    public static function get_site_brand_name() { return 'YBY Irrigation'; }
    public static function get_case_id_brand_code() { return 'IRR'; }
    public static function get_website_url() { return 'https://YBYIrrigation.com/'; }
    public static function sanitize_site_brand_key( $v ) { return sanitize_key($v); }
    public static function sanitize_site_brand_name( $v ) { return sanitize_text_field($v); }
    public static function sanitize_case_id_brand_code( $v ) { return strtoupper(sanitize_key($v)); }
    public static function sanitize_website_url( $v ) { return (string)$v; }
}
function apply_filters( $tag, $value ) { return $value; }
require_once dirname( __DIR__ ) . '/inc/class-yby-site-profile.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-analytics-site-profile.php';
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$project = array(
    'trackingGroup' => 'irrigation-leads',
    'ga4ContentGroup' => 'B2B Irrigation',
    'adsConversionGroup' => 'wholesale',
);
$p = YBY_Analytics_Site_Profile::current( $project );
$assert( '1' === $p['profile_version'], 'profile version' );
$assert( 'yby-irrigation' === $p['site_brand_key'], 'trusted site brand key' );
$assert( 'YBY Irrigation' === $p['site_brand_name'], 'trusted site brand name' );
$assert( 'ybyirrigation.com' === $p['website_host'], 'website host normalization' );
$assert( 'irrigation-leads' === $p['tracking_group'], 'legacy trackingGroup projection' );
$assert( 'B2B Irrigation' === $p['ga4_content_group'], 'legacy GA4 group projection' );
$assert( 'wholesale' === $p['ads_conversion_group'], 'legacy Ads group projection' );
$bad = YBY_Analytics_Site_Profile::sanitize( array( 'website_host' => 'https://bad host/', 'tracking_group' => '<b>x</b>' ) );
$assert( '' === $bad['website_host'], 'invalid host must fail closed' );
$assert( 'x' === $bad['tracking_group'], 'tracking context sanitation' );
$public = file_get_contents( dirname( __DIR__ ) . '/public/class-yby-public.php' );
$core = file_get_contents( dirname( __DIR__ ) . '/inc/class-yby-core.php' );
$js = file_get_contents( dirname( __DIR__ ) . '/public/assets/js/yby-core-public.js' );
$assert( false !== strpos( $public, "YBY_Module_Registry::is_enabled( 'analytics' )" ), 'Analytics-alone public runtime gate' );
$assert( false !== strpos( $core, 'Always register the public controller hook' ) && false !== strpos( $core, "add_action( 'wp_enqueue_scripts'" ), 'public runtime hook must always register before extension discovery' );
$assert( false === strpos( $core, '$inquiry_enabled || $project_enabled || $analytics_enabled' ), 'public hook must not depend on pre-discovery Analytics lookup' );
$assert( false !== strpos( $js, 'tracking.siteProfile' ), 'frontend canonical Site Profile read missing' );
$assert( false !== strpos( $js, 'analyticsSiteProfile.tracking_group || getCanonicalString("trackingGroup"' ), 'legacy trackingGroup fallback missing' );
echo "PASS v181-analytics-site-profile-harness\n";
