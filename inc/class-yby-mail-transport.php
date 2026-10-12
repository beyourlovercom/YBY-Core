<?php
/**
 * Newsletter-only WordPress Mail Transport adapter.
 *
 * No new plugin, no global SMTP override and no WordPress-stored credentials.
 * Transport credentials are supplied exclusively by an authorized host runtime.
 * This adapter does not waive any pre_wp_mail veto (including Dev Safety/G5).
 * A successful wp_mail() is NOT proof of inbox delivery.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_Transport {
    private static $active = false;
    private static $active_config = null;

    /**
     * Read only non-secret readiness fields. Never include a password,
     * username, recipient, token, SMTP response or provider error detail.
     */
    public static function readiness() {
        if ( self::synthetic_fixture() ) {
            return array( 'ready' => true, 'mode' => 'isolated_interception', 'reason' => 'fixture_only' );
        }
        $config = self::validated_config( self::server_config() );
        return array(
            'ready'  => null !== $config,
            'mode'   => 'smtp',
            'reason' => null === $config ? 'transport_unconfigured' : 'configured_not_delivery_verified',
        );
    }

    /**
     * The runtime may only read server-injected credentials, never WP options.
     * Do not define credentials inside source code, tests, GitHub or content DB.
     */
    private static function server_config() {
        return array(
            'enabled'   => defined( 'YBY_MAIL_TRANSPORT_ENABLED' ) && true === YBY_MAIL_TRANSPORT_ENABLED,
            'driver'    => defined( 'YBY_MAIL_TRANSPORT_DRIVER' ) ? YBY_MAIL_TRANSPORT_DRIVER : '',
            'provider'  => defined( 'YBY_MAIL_TRANSPORT_PROVIDER' ) ? YBY_MAIL_TRANSPORT_PROVIDER : '',
            'host'      => getenv( 'YBY_MAIL_SMTP_HOST' ),
            'port'      => getenv( 'YBY_MAIL_SMTP_PORT' ),
            'security'  => getenv( 'YBY_MAIL_SMTP_SECURITY' ),
            'user'      => getenv( 'YBY_MAIL_SMTP_USER' ),
            'password'  => getenv( 'YBY_MAIL_SMTP_PASSWORD' ),
            'from'      => getenv( 'YBY_MAIL_FROM_EMAIL' ),
            // Site-local approved sender, never a universal BYL value.
            'approved_from' => defined( 'YBY_MAIL_APPROVED_FROM_EMAIL' ) ? YBY_MAIL_APPROVED_FROM_EMAIL : '',
            'from_name' => getenv( 'YBY_MAIL_FROM_NAME' ),
        );
    }

    /**
     * Pure configuration validation; tests may provide synthetic input.
     * First live profile is Tencent Enterprise Mail only (SSL/465, exact sender).
     * No automatic PHP mail fallback and no insecure/no-TLS SMTP option.
     */
    public static function validated_config( $raw ) {
        if ( ! is_array( $raw ) || true !== ( $raw['enabled'] ?? false ) ||
             'smtp' !== ( $raw['driver'] ?? null ) ||
             'tencent_exmail' !== ( $raw['provider'] ?? null ) ) { return null; }

        foreach ( array( 'host', 'port', 'security', 'user', 'password', 'from', 'approved_from', 'from_name' ) as $key ) {
            if ( ! isset( $raw[ $key ] ) || ! is_string( $raw[ $key ] ) ||
                 '' === trim( $raw[ $key ] ) || strlen( $raw[ $key ] ) > 255 ) { return null; }
        }
        $host = $raw['host'];
        $port = $raw['port'];
        $security = $raw['security'];
        if ( ! preg_match( '/\A[a-zA-Z0-9](?:[a-zA-Z0-9.-]{0,251}[a-zA-Z0-9])?\z/D', $host ) ||
             str_contains( $host, '..' ) || ! ctype_digit( $port ) ||
             ! in_array( $security, array( 'tls', 'ssl' ), true ) ||
             ( 'tls' === $security && '587' !== $port ) ||
             ( 'ssl' === $security && '465' !== $port ) ) { return null; }
        // Tencent Enterprise Mail 2026: authenticated SSL/465 only. The
        // overseas relay is an explicit choice, never an automatic failover.
        $allowed_hosts = array( 'smtp.exmail.qq.com', 'hwsmtp.exmail.qq.com' );
        if ( ! in_array( $host, $allowed_hosts, true ) ||
             'ssl' !== $security || '465' !== $port ) { return null; }

        // Initial release permits only the authenticated mailbox as From.
        // Sending as an alias/delegated address requires a separate review.
        if ( false === filter_var( $raw['user'], FILTER_VALIDATE_EMAIL ) ||
             false === filter_var( $raw['from'], FILTER_VALIDATE_EMAIL ) ||
             ! hash_equals( strtolower( $raw['user'] ), strtolower( $raw['from'] ) ) ||
             false === filter_var( $raw['approved_from'], FILTER_VALIDATE_EMAIL ) ||
             ! hash_equals( strtolower( $raw['approved_from'] ), strtolower( $raw['from'] ) ) ||
             preg_match( '/[\r\n]/', $raw['user'] . $raw['from_name'] ) ||
             strlen( $raw['from_name'] ) > 90 ) { return null; }
        return array(
            'host'      => $host,
            'port'      => (int) $port,
            'security'  => $security,
            'user'      => $raw['user'],
            'password'  => $raw['password'],
            'from'      => $raw['from'],
            'from_name' => trim( $raw['from_name'] ),
        );
    }

    /**
     * Only GitHub's disposable newsletter-validation.test WP-CLI fixture can
     * use a synthetic "true" pre_wp_mail return with no transport credentials.
     * The fixture is never usable on Dev/WWW or from a public HTTP request.
     */
    private static function synthetic_fixture() {
        return defined( 'YBY_MAIL_TRANSPORT_TEST_INTERCEPT_ONLY' ) &&
            true === YBY_MAIL_TRANSPORT_TEST_INTERCEPT_ONLY &&
            defined( 'WP_CLI' ) && true === WP_CLI &&
            function_exists( 'home_url' ) && function_exists( 'wp_parse_url' ) &&
            'newsletter-validation.test' === wp_parse_url( home_url( '/' ), PHP_URL_HOST ) &&
            function_exists( 'has_filter' ) && false !== has_filter( 'pre_wp_mail' );
    }

    /** Confirmation mail only; never change Woo, reset-password or lead mail. */
    public static function send_newsletter_confirmation( $to, $subject, $message ) {
        if ( ! is_string( $to ) || false === filter_var( $to, FILTER_VALIDATE_EMAIL ) ||
             'Confirm your newsletter subscription' !== $subject ||
             ! is_string( $message ) || '' === $message || strlen( $message ) > 4096 ||
             self::$active ) { return false; }

        if ( self::synthetic_fixture() ) {
            return (bool) wp_mail( $to, $subject, $message );
        }

        $cfg = self::validated_config( self::server_config() );
        if ( null === $cfg ) { return false; }
        self::$active_config = $cfg;
        self::$active = true;
        add_action( 'phpmailer_init', array( __CLASS__, 'configure_active_mailer' ), 99999, 1 );
        add_filter( 'wp_mail_from', array( __CLASS__, 'scoped_from_email' ), 99999, 1 );
        add_filter( 'wp_mail_from_name', array( __CLASS__, 'scoped_from_name' ), 99999, 1 );
        try {
            // Existing Dev pre_wp_mail terminal veto remains authoritative.
            // A permit denial returns false without ever opening SMTP.
            return (bool) wp_mail( $to, $subject, $message );
        } finally {
            remove_action( 'phpmailer_init', array( __CLASS__, 'configure_active_mailer' ), 99999 );
            remove_filter( 'wp_mail_from', array( __CLASS__, 'scoped_from_email' ), 99999 );
            remove_filter( 'wp_mail_from_name', array( __CLASS__, 'scoped_from_name' ), 99999 );
            self::$active = false;
            self::$active_config = null;
        }
    }

    public static function scoped_from_email( $original ) {
        return self::$active && is_array( self::$active_config )
            ? self::$active_config['from'] : $original;
    }

    public static function scoped_from_name( $original ) {
        return self::$active && is_array( self::$active_config )
            ? self::$active_config['from_name'] : $original;
    }

    public static function configure_active_mailer( $mailer ) {
        if ( ! self::$active || ! is_array( self::$active_config ) ||
             ! is_object( $mailer ) || ! method_exists( $mailer, 'isSMTP' ) ) { return; }
        $cfg = self::$active_config;
        $mailer->isSMTP();
        $mailer->Host = $cfg['host'];
        $mailer->Port = $cfg['port'];
        $mailer->SMTPSecure = $cfg['security']; // 'tls' on 587, 'ssl' on 465 only.
        $mailer->SMTPAuth = true;
        $mailer->Username = $cfg['user'];
        $mailer->Password = $cfg['password'];
        $mailer->SMTPAutoTLS = true;
        $mailer->SMTPKeepAlive = false;
        $mailer->Timeout = 10;
        $mailer->SMTPOptions = array( 'ssl' => array(
            'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
        ) );
    }
}
