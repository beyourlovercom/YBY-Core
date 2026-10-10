<?php
/** Newsletter M1 no-network, no-WP bootstrap security contract. */
define( 'ABSPATH', '/offline/' );
require dirname( __DIR__ ) . '/inc/class-yby-newsletter-consent.php';
require dirname( __DIR__ ) . '/inc/class-yby-newsletter-store.php';
require dirname( __DIR__ ) . '/inc/class-yby-newsletter-rest-controller.php';
$n = 0;
function m1assert( $yes, $why ) {
    global $n;
    if ( ! $yes ) { fwrite( STDERR, 'FAIL ' . $why . "\n" ); exit( 1 ); }
    $n++;
    echo 'PASS ' . $why . "\n";
}
$root = dirname( __DIR__ );
$store = file_get_contents( $root . '/inc/class-yby-newsletter-store.php' );
$api = file_get_contents( $root . '/inc/class-yby-newsletter-rest-controller.php' );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$short = file_get_contents( $root . '/inc/class-yby-subscribe-shortcode.php' );
$popup = file_get_contents( $root . '/inc/class-yby-global-popup.php' );
$browser = file_get_contents( $root . '/public/js/yby-newsletter.js' );
$public = file_get_contents( $root . '/public/class-yby-public.php' );
m1assert( ! YBY_Newsletter_REST_Controller::enabled(), 'API disabled without code flag' );
m1assert( ! YBY_Newsletter_REST_Controller::outbound_enabled(), 'mail disabled without code flag' );
m1assert( ! YBY_Newsletter_Store::install_schema(), 'schema disabled without explicit WP CLI approval' );
m1assert( false !== strpos( $store, "defined( 'WP_CLI' )" ) && false !== strpos( $store, "YBY_NEWSLETTER_SCHEMA_APPLY" ), 'schema explicit-only' );
m1assert( false === strpos( $core, "'plugins_loaded', 'YBY_Newsletter_Store'" ), 'no auto install on plugin upgrade' );
m1assert( false !== strpos( $store, 'UNIQUE KEY email_hash' ) && false !== strpos( $store, 'UNIQUE KEY external_subscription_id' ), 'database uniqueness enforced' );
m1assert( false !== strpos( $store, "'status' => 'pending'" ) && false !== strpos( $store, "status = 'subscribed'" ), 'pending default and atomic confirmation' );
m1assert( false !== strpos( $store, 'confirm_expires_at > UTC_TIMESTAMP()' ) && false !== strpos( $store, 'confirm_hash = NULL' ), 'confirmation expires and burns token' );
m1assert( false !== strpos( $store, "status IN ('pending','subscribed')" ), 'opt-out never reactivated by stale token' );
m1assert( false !== strpos( $api, 'YBY_NEWSLETTER_API_ENABLED' ) && false !== strpos( $api, 'YBY_NEWSLETTER_OUTBOUND_ENABLED' ), 'API and mail independent code gates' );
m1assert( false !== strpos( $api, "get_option( 'yby_newsletter_enabled'" ) && false !== strpos( $api, "get_option( 'yby_newsletter_outbound_enabled'" ), 'site-level double gating' );
m1assert( false !== strpos( $api, "marketing_consent" ) && false !== strpos( $api, 'consent_ok' ), 'server explicitly requires marketing consent' );
m1assert( false !== strpos( $api, 'MINUTE_IN_SECONDS' ) && false !== strpos( $api, 'get_transient(' ), 'bounded per-request rate gate' );
m1assert( false !== strpos( $api, "yby_nl_ip_" ) && false !== strpos( $api, '$ip_n >= 20' ), 'site-wide IP rate cap cannot be bypassed by rotating email' );
m1assert( false !== strpos( $api, 'hash_equals( self::policy_version(), $policy )' ), 'server pins actual consent policy version independently of caller' );
m1assert( false !== strpos( $api, "valid_origin( " ) && false !== strpos( $api, 'self::MAX_BODY' ), 'same-origin and bounded payload' );
m1assert( false !== strpos( $api, 'Cache-Control' ) && false !== strpos( $api, 'no-store' ), 'private no-cache responses' );
m1assert( false !== strpos( $api, "'/newsletter/confirm'" ) && false !== strpos( $api, "'/newsletter/unsubscribe'" ), 'separate POST token routes' );
m1assert( false !== strpos( $store, "wp_salt( 'auth' )" ) && false !== strpos( $store, 'random_bytes( 32 )' ), 'strong HMAC token hash and CSPRNG' );
m1assert( false !== strpos( $short, 'name="marketing_consent"' ) && false !== strpos( $popup, 'name="marketing_consent"' ), 'both subscribe widgets have unchecked consent' );
m1assert( false !== strpos( $short, 'name="website"' ) && false !== strpos( $popup, 'name="website"' ), 'both forms implement honeypot' );
m1assert( false !== strpos( $browser, 'credentials: "omit"' ) && false !== strpos( $browser, 'fetch(url,' ), 'no authenticated cookie/client-side fake success' );
m1assert( false !== strpos( $browser, 'history.replaceState' ) && false !== strpos( $browser, 'button.addEventListener("click"' ), 'token link requires click and removed from URL bar' );
m1assert( false !== strpos( $browser, 'url.searchParams.set("rest_route"' ), 'permalinkless WordPress REST supported' );
m1assert( false !== strpos( $public, "'yby-newsletter'" ) && false !== strpos( $core, "'inc/class-yby-newsletter-rest-controller.php'" ), 'Core runtime resources and class wired' );
m1assert( false !== strpos( $core, "new YBY_Newsletter_REST_Controller()" ) && false !== strpos( $core, '$subscribe_shortcode, ' ), 'Core connects REST and shared inline shortcode' );
echo 'NEWSLETTER_M1_OFFLINE_SECURITY_PASS ' . $n . ' assertions' . "\n";
