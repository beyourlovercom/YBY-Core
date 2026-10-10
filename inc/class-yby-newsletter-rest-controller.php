<?php
/**
 * Andy Core Newsletter M1 safe, site-local REST controller.
 * Default OFF. No routes on an uninstalled site, no auto schema migration.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Newsletter_REST_Controller {
    const NAMESPACE = 'andy-core/v1';
    const MAX_BODY = 4096;

    /** Both code-level AND site-level explicit approval required. */
    public static function enabled() {
        return defined( 'YBY_NEWSLETTER_API_ENABLED' )
            && true === YBY_NEWSLETTER_API_ENABLED
            && '1' === (string) get_option( 'yby_newsletter_enabled', '0' )
            && YBY_Newsletter_Store::exists();
    }

    /** A separate two-key release gate is required to send any real email. */
    public static function outbound_enabled() {
        return defined( 'YBY_NEWSLETTER_OUTBOUND_ENABLED' )
            && true === YBY_NEWSLETTER_OUTBOUND_ENABLED
            && '1' === (string) get_option( 'yby_newsletter_outbound_enabled', '0' );
    }

    /** Keep one-time email-link tokens out of third-party Referer and caches. */
    public static function protect_token_landing() {
        if ( empty( $_GET['yby_newsletter_action'] ) || empty( $_GET['token'] ) ||
             ! is_string( $_GET['token'] ) ||
             ! preg_match( '/^[a-f0-9]{64}$/D', $_GET['token'] ) ) {
            return;
        }
        header( 'Referrer-Policy: no-referrer' );
        header( 'Cache-Control: no-store, private' );
        header( 'X-Robots-Tag: noindex, nofollow' );
    }

    public function register_routes() {
        // Confirm/unsubscribe remain possible after new signups are paused.
        if ( ! self::enabled() && ! YBY_Newsletter_Store::exists() ) { return; }
        register_rest_route( self::NAMESPACE, '/newsletter/subscribe', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'subscribe' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::NAMESPACE, '/newsletter/confirm', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'confirm' ),
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( self::NAMESPACE, '/newsletter/unsubscribe', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'unsubscribe' ),
            'permission_callback' => '__return_true',
        ) );
    }

    private static function respond( $ok, $message, $http ) {
        $response = new WP_REST_Response( array(
            'success' => (bool) $ok, 'message' => $message,
        ), (int) $http );
        $response->header( 'Cache-Control', 'no-store, private' );
        return $response;
    }

    private static function param_text( $request, $name, $max ) {
        $v = $request->get_param( $name );
        if ( ! is_string( $v ) || strlen( $v ) > $max ) { return null; }
        return trim( $v );
    }

    /** Same-site only when browsers supply Origin. No cookies required. */
    private static function valid_origin( $request ) {
        $origin = $request->get_header( 'origin' );
        if ( ! is_string( $origin ) || '' === $origin ) { return true; }
        $home = wp_parse_url( home_url( '/' ) );
        $from = wp_parse_url( $origin );
        return is_array( $home ) && is_array( $from )
            && strtolower( (string) ( $from['host'] ?? '' ) ) === strtolower( (string) ( $home['host'] ?? '' ) )
            && (string) ( $from['scheme'] ?? '' ) === (string) ( $home['scheme'] ?? '' )
            && (int) ( $from['port'] ?? ( 'https' === ( $from['scheme'] ?? '' ) ? 443 : 80 ) )
            === (int) ( $home['port'] ?? ( 'https' === ( $home['scheme'] ?? '' ) ? 443 : 80 ) );
    }

    private static function valid_size( $request ) {
        $header = $request->get_header( 'content-length' );
        if ( is_string( $header ) && ctype_digit( $header ) && (int) $header > self::MAX_BODY ) { return false; }
        $body = $request->get_body();
        return is_string( $body ) && strlen( $body ) <= self::MAX_BODY;
    }

    private static function throttle( $email ) {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
            ? $_SERVER['REMOTE_ADDR'] : '';
        // Salted, short-lived anti-abuse key. Never store/log the plain IP.
        $key = 'yby_nl_rate_' . substr( hash_hmac(
            'sha256', $ip . '|' . (string) $email, wp_salt( 'auth' )
        ), 0, 32 );
        $n = (int) get_transient( $key );
        if ( $n >= 5 ) { return false; }
        set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
        return true;
    }

    public function subscribe( $request ) {
        if ( ! self::enabled() || ! self::outbound_enabled() ) {
            return self::respond( false, 'Newsletter is not available yet.', 503 );
        }
        if ( ! self::valid_origin( $request ) || ! self::valid_size( $request ) ) {
            return self::respond( false, 'Unable to accept this request.', 400 );
        }
        $email = self::param_text( $request, 'email', 190 );
        $policy = self::param_text( $request, 'consent_policy', 100 );
        $source = self::param_text( $request, 'source', 80 );
        $page = self::param_text( $request, 'source_page', 255 );
        $locale = self::param_text( $request, 'locale', 20 );
        $consent = $request->get_param( 'marketing_consent' );
        $consent_ok = true === $consent || '1' === $consent || 1 === $consent;
        if ( null === $email || null === YBY_Newsletter_Consent::normalize_email( $email ) ||
             ! $consent_ok || null === $policy || '' === $policy ||
             null === $source || '' === $source || null === $page || null === $locale ) {
            return self::respond( false, 'Email and explicit marketing consent are required.', 400 );
        }
        if ( null !== self::param_text( $request, 'website', 120 ) &&
             '' !== self::param_text( $request, 'website', 120 ) ) {
            // Honeypot: do not store or send. Deliberately generic response.
            return self::respond( true, 'If eligible, please check your inbox.', 200 );
        }
        $email = YBY_Newsletter_Consent::normalize_email( $email );
        if ( ! self::throttle( $email ) ) {
            return self::respond( false, 'Please try again later.', 429 );
        }
        $result = YBY_Newsletter_Store::begin_pending( $email, $policy, $source, $page, $locale );
        if ( 'existing' === ( $result['outcome'] ?? '' ) ) {
            return self::respond( true, 'If eligible, please check your inbox.', 200 );
        }
        if ( 'pending' !== ( $result['outcome'] ?? '' ) ) {
            return self::respond( false, 'Newsletter is temporarily unavailable.', 503 );
        }
        $confirm_url = add_query_arg( array(
            'yby_newsletter_action' => 'confirm',
            'token' => $result['confirm_token'],
        ), home_url( '/' ) );
        $unsubscribe_url = add_query_arg( array(
            'yby_newsletter_action' => 'unsubscribe',
            'token' => $result['unsubscribe_token'],
        ), home_url( '/' ) );
        $message = "Please confirm your email subscription by visiting:\n" . $confirm_url .
            "\n\nIf you did not request this, ignore this email. You can unsubscribe using:\n" . $unsubscribe_url;
        // No external email unless BOTH explicit gates are set. No default
        // activation, and any sender failure returns a truthful 503.
        if ( ! wp_mail( $result['email'], 'Confirm your newsletter subscription', $message ) ) {
            return self::respond( false, 'Email confirmation is not available. Please try later.', 503 );
        }
        return self::respond( true, 'If eligible, please check your inbox.', 200 );
    }

    public function confirm( $request ) {
        if ( ! YBY_Newsletter_Store::exists() || ! self::valid_origin( $request ) ||
             ! self::valid_size( $request ) ) {
            return self::respond( false, 'Confirmation is unavailable.', 503 );
        }
        $token = self::param_text( $request, 'token', 64 );
        if ( null === $token || ! YBY_Newsletter_Store::confirm( $token ) ) {
            return self::respond( false, 'The confirmation link is invalid or expired.', 400 );
        }
        return self::respond( true, 'Subscription confirmed.', 200 );
    }

    public function unsubscribe( $request ) {
        if ( ! YBY_Newsletter_Store::exists() || ! self::valid_origin( $request ) ||
             ! self::valid_size( $request ) ) {
            return self::respond( false, 'Unsubscribe is unavailable.', 503 );
        }
        $token = self::param_text( $request, 'token', 64 );
        if ( null === $token || ! YBY_Newsletter_Store::unsubscribe( $token ) ) {
            return self::respond( false, 'This link is invalid or already used.', 400 );
        }
        return self::respond( true, 'You have been unsubscribed.', 200 );
    }
}
