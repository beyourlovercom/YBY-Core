<?php
/**
 * Andy Core Newsletter M1 — site-local storage. No automatic migration.
 *
 * The table can ONLY be created from an approved WP-CLI/isolated installer
 * with YBY_NEWSLETTER_SCHEMA_APPLY explicitly true. Plugin activation or
 * upgrading Andy Core will never create/alter the table.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Newsletter_Store {
    const TABLE = 'yby_newsletter_subscriptions';
    const VERSION = '1';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function exists() {
        global $wpdb;
        $name = self::table_name();
        return $name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) );
    }

    /** Explicit DB approval; never hooked to activation/plugins_loaded. */
    public static function install_schema() {
        if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
            ! defined( 'YBY_NEWSLETTER_SCHEMA_APPLY' ) || true !== YBY_NEWSLETTER_SCHEMA_APPLY ) {
            return false;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $name = self::table_name();
        $collation = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $name (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          email varchar(190) NOT NULL,
          email_hash char(64) NOT NULL,
          external_subscription_id varchar(88) NOT NULL,
          site_key varchar(190) NOT NULL,
          status varchar(16) NOT NULL DEFAULT 'pending',
          consent_source varchar(80) NOT NULL,
          consent_policy varchar(100) NOT NULL,
          form_source varchar(80) NOT NULL,
          source_page varchar(255) NOT NULL DEFAULT '',
          locale varchar(20) NOT NULL DEFAULT '',
          submitted_at datetime NOT NULL,
          subscribed_at datetime DEFAULT NULL,
          unsubscribed_at datetime DEFAULT NULL,
          status_updated_at datetime NOT NULL,
          confirm_hash char(64) DEFAULT NULL,
          confirm_expires_at datetime DEFAULT NULL,
          unsubscribe_hash char(64) NOT NULL,
          created_at datetime NOT NULL,
          updated_at datetime NOT NULL,
          PRIMARY KEY  (id),
          UNIQUE KEY email_hash (email_hash),
          UNIQUE KEY external_subscription_id (external_subscription_id),
          KEY status_updated (status,status_updated_at)
        ) $collation;";
        dbDelta( $sql );
        return self::exists();
    }

    public static function site_key() {
        $host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
        $host = is_string( $host ) ? strtolower( rtrim( $host, '.' ) ) : '';
        return preg_match( '/^[a-z0-9.-]{1,190}$/D', $host ) ? $host : '';
    }

    public static function stamp( $epoch = null ) {
        return gmdate( 'Y-m-d H:i:s', null === $epoch ? time() : (int) $epoch );
    }

    public static function token_hash( $token ) {
        return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
    }

    public static function find_email( $normalized ) {
        global $wpdb;
        if ( ! self::exists() || null === YBY_Newsletter_Consent::normalize_email( $normalized ) ) {
            return null;
        }
        $hash = hash( 'sha256', $normalized );
        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . ' WHERE email_hash = %s LIMIT 1',
            $hash
        ), ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    /**
     * Called only after API, consent, rate, schema and outbound gates pass.
     * Returns a token only to the private mail sender, never REST callers.
     * Never overwrites an existing active or suppressed consent.
     */
    public static function begin_pending( $email, $policy, $source, $page, $locale ) {
        global $wpdb;
        $email = YBY_Newsletter_Consent::normalize_email( $email );
        $site = self::site_key();
        if ( null === $email || '' === $site || ! self::exists() ) {
            return array( 'outcome' => 'unavailable' );
        }
        $existing = self::find_email( $email );
        if ( $existing && in_array( $existing['status'], array( 'subscribed', 'suppressed' ), true ) ) {
            return array( 'outcome' => 'existing' );
        }
        $token = bin2hex( random_bytes( 32 ) );
        $unsub_token = bin2hex( random_bytes( 32 ) );
        $now = self::stamp();
        $fields = array(
            'status' => 'pending',
            'consent_source' => YBY_Newsletter_Consent::CONSENT_SOURCE,
            'consent_policy' => substr( $policy, 0, 100 ),
            'form_source' => substr( $source, 0, 80 ),
            'source_page' => substr( $page, 0, 255 ),
            'locale' => substr( $locale, 0, 20 ),
            'submitted_at' => $now,
            'subscribed_at' => null,
            'unsubscribed_at' => null,
            'status_updated_at' => $now,
            'confirm_hash' => self::token_hash( $token ),
            'confirm_expires_at' => self::stamp( time() + DAY_IN_SECONDS ),
            'unsubscribe_hash' => self::token_hash( $unsub_token ),
            'updated_at' => $now,
        );
        if ( $existing ) {
            // Atomic: only still-pending/unsubscribed rows may be reissued.
            $changed = $wpdb->update( self::table_name(), $fields,
                array( 'id' => (int) $existing['id'], 'status' => $existing['status'] ) );
            if ( 1 !== $changed ) { return array( 'outcome' => 'unavailable' ); }
        } else {
            $data = array_merge( $fields, array(
                'email' => $email,
                'email_hash' => hash( 'sha256', $email ),
                'site_key' => $site,
                'external_subscription_id' => YBY_Newsletter_Consent::external_id( $site, $email ),
                'created_at' => $now,
            ) );
            if ( 1 !== $wpdb->insert( self::table_name(), $data ) ) {
                // A competing insert won; do not leak existence or overwrite it.
                return array( 'outcome' => 'unavailable' );
            }
        }
        return array( 'outcome' => 'pending', 'email' => $email,
            'confirm_token' => $token, 'unsubscribe_token' => $unsub_token );
    }

    /**
     * Conditional SQL consumes confirmation token only once. No provider
     * retry, delayed message or concurrent request can revive an opt-out.
     */
    public static function confirm( $token ) {
        global $wpdb;
        if ( ! self::valid_token( $token ) || ! self::exists() ) { return false; }
        $sql = $wpdb->prepare(
            'UPDATE ' . self::table_name() .
            " SET status = 'subscribed', subscribed_at = UTC_TIMESTAMP(), unsubscribed_at = NULL,
                 status_updated_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP(),
                 confirm_hash = NULL, confirm_expires_at = NULL
             WHERE confirm_hash = %s AND status = 'pending'
               AND confirm_expires_at > UTC_TIMESTAMP()",
            self::token_hash( $token )
        );
        return 1 === $wpdb->query( $sql );
    }

    /**
     * Unsubscribe token remains private; only hash in DB. Consuming it
     * sets unsubscribed atomically; never emits or returns the email.
     */
    public static function unsubscribe( $token ) {
        global $wpdb;
        if ( ! self::valid_token( $token ) || ! self::exists() ) { return false; }
        $sql = $wpdb->prepare(
            'UPDATE ' . self::table_name() .
            " SET status = 'unsubscribed', unsubscribed_at = UTC_TIMESTAMP(),
                 status_updated_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP(),
                 confirm_hash = NULL, confirm_expires_at = NULL
             WHERE unsubscribe_hash = %s AND status IN ('pending','subscribed')",
            self::token_hash( $token )
        );
        return 1 === $wpdb->query( $sql );
    }

    private static function valid_token( $token ) {
        return is_string( $token ) && 1 === preg_match( '/^[a-f0-9]{64}$/D', $token );
    }
}
