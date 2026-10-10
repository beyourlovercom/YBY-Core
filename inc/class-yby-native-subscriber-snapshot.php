<?php
/**
 * M2 read-only native newsletter projection for the authenticated HMAC ERP endpoint.
 * Legacy Elementor snapshot, including its cursor and IDs, is untouched.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Native_Subscriber_Snapshot {
    const SOURCE = 'andy_core_newsletter';

    public static function snapshot( $query ) {
        global $wpdb;
        if ( ! class_exists( 'YBY_Newsletter_Store' ) || ! class_exists( 'YBY_Newsletter_Consent' ) ||
            ! is_object( $wpdb ) || ! method_exists( $wpdb, 'prepare' ) ||
            ! method_exists( $wpdb, 'get_results' ) || ! YBY_Newsletter_Store::exists() ) {
            return self::unavailable();
        }
        $site = YBY_Newsletter_Store::site_key();
        $table = YBY_Newsletter_Store::table_name();
        if ( '' === $site || ! preg_match( '/^[A-Za-z0-9_]+$/D', $table ) ) { return self::unavailable(); }

        $boundary = null;
        if ( null !== ( $query['updated_after'] ?? null ) ) {
            $epoch = YBY_Connector::timestamp( $query['updated_after'] );
            if ( false === $epoch ) { return self::invalid( 'updated_after must be ISO-8601.' ); }
            // Inclusive UTC-second boundary: same-second updates are replay-safe.
            $boundary = gmdate( 'Y-m-d H:i:s', $epoch );
        }
        $position = null;
        if ( null !== ( $query['cursor'] ?? null ) ) {
            $position = self::decode_cursor( $query['cursor'], $boundary );
            if ( null === $position ) { return self::invalid( 'Native subscriber cursor invalid or from another source/window.' ); }
        }
        $where = array( 'site_key = %s' );
        $args = array( $site );
        if ( null !== $boundary ) {
            $where[] = 'status_updated_at >= %s';
            $args[] = $boundary;
        }
        if ( null !== $position ) {
            $where[] = '(status_updated_at > %s OR (status_updated_at = %s AND id > %d))';
            array_push( $args, $position['changed_at'], $position['changed_at'], $position['id'] );
        }
        $limit = (int) ( $query['limit'] ?? 0 );
        if ( $limit < 1 || $limit > 100 ) { return self::invalid( 'Limit must be between 1 and 100.' ); }
        // Select only approved consent projection fields, never token hashes.
        $sql = 'SELECT id,email,external_subscription_id,site_key,status,consent_source,' .
            'subscribed_at,unsubscribed_at,status_updated_at FROM ' . $table . ' WHERE ' .
            implode( ' AND ', $where ) . ' ORDER BY status_updated_at ASC, id ASC LIMIT %d';
        $args[] = $limit + 1;
        $prepared = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $args ) );
        $wpdb->last_error = '';
        $rows = $wpdb->get_results( $prepared, ARRAY_A );
        if ( ! is_array( $rows ) || ! empty( $wpdb->last_error ) ) { return self::unavailable(); }

        $items = array();
        foreach ( array_slice( $rows, 0, $limit ) as $row ) {
            if ( ! is_array( $row ) ) { return self::unavailable(); }
            $email = YBY_Newsletter_Consent::normalize_email( $row['email'] ?? null );
            $status = $row['status'] ?? null;
            if ( null === $email || ! in_array( $status, array( 'pending', 'subscribed', 'unsubscribed', 'suppressed' ), true ) ||
                ( $row['site_key'] ?? null ) !== $site ||
                ! is_string( $row['external_subscription_id'] ?? null ) ||
                ! hash_equals( (string) YBY_Newsletter_Consent::external_id( $site, $email ), $row['external_subscription_id'] ) ) {
                return self::unavailable();
            }
            $updated = self::utc_timestamp( $row['status_updated_at'] ?? null );
            $confirmed = self::utc_timestamp( $row['subscribed_at'] ?? null, true );
            $revoked = self::utc_timestamp( $row['unsubscribed_at'] ?? null, true );
            if ( null === $updated || false === $updated || false === $confirmed || false === $revoked ) { return self::unavailable(); }
            $items[] = array(
                'external_subscription_id' => $row['external_subscription_id'],
                'email' => $email,
                'provider' => 'wordpress',
                'source_site' => $site,
                'status' => $status,
                'consent_source' => (string) ( $row['consent_source'] ?? YBY_Newsletter_Consent::CONSENT_SOURCE ),
                'subscribed_at' => $confirmed,
                'unsubscribed_at' => $revoked,
                'status_updated_at' => $updated,
            );
        }
        $cursor = null;
        if ( count( $rows ) > $limit && $items ) {
            $last = $rows[ count( $items ) - 1 ];
            if ( (int) ( $last['id'] ?? 0 ) < 1 || ! is_string( $last['status_updated_at'] ?? null ) ) { return self::unavailable(); }
            $cursor = self::encode_cursor( $last['status_updated_at'], (int) $last['id'], $boundary );
        }
        return array( 'items' => $items, 'next_cursor' => $cursor, 'source_site' => $site );
    }

    private static function utc_timestamp( $value, $nullable = false ) {
        if ( null === $value ) { return $nullable ? null : false; }
        if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/D', $value ) ) { return false; }
        $date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
        $errors = DateTimeImmutable::getLastErrors();
        if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) { return false; }
        return $date->format( DateTime::ATOM );
    }

    private static function encode_cursor( $changed_at, $id, $boundary ) {
        $json = wp_json_encode( array( 'v' => 1, 'resource' => 'subscribers', 'source' => self::SOURCE,
            'changed_at' => $changed_at, 'id' => $id, 'after' => $boundary ) );
        $payload = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
        return $payload . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
    }

    private static function decode_cursor( $raw, $boundary ) {
        if ( ! is_string( $raw ) || strlen( $raw ) > 2048 ||
            ! preg_match( '/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D', $raw, $parts ) ||
            ! hash_equals( hash_hmac( 'sha256', $parts[1], wp_salt( 'auth' ) ), $parts[2] ) ) { return null; }
        $json = base64_decode( strtr( $parts[1], '-_', '+/' ), true );
        $p = false === $json ? null : json_decode( $json, true );
        if ( ! is_array( $p ) || count( $p ) !== 6 ||
            ( $p['v'] ?? null ) !== 1 || ( $p['resource'] ?? null ) !== 'subscribers' ||
            ( $p['source'] ?? null ) !== self::SOURCE || ! array_key_exists( 'after', $p ) || $p['after'] !== $boundary ||
            ! is_string( $p['changed_at'] ?? null ) || false === self::utc_timestamp( $p['changed_at'] ) ||
            ! is_int( $p['id'] ?? null ) || $p['id'] < 1 ) { return null; }
        return $p;
    }

    private static function invalid( $message ) {
        return new WP_Error( 'VALIDATION_FAILED', $message, array( 'status' => 400, 'retryable' => false ) );
    }

    private static function unavailable() {
        return new WP_Error( 'PROVIDER_UNAVAILABLE', 'Core newsletter subscriber source is unavailable.', array( 'status' => 503, 'retryable' => true ) );
    }
}
