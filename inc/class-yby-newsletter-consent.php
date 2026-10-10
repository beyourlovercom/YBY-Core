<?php
/**
 * Newsletter M1 consent state and ERP projection contract.
 *
 * Pure domain logic only. No hooks, REST route, DB operation or email.
 * Activation requires the later storage/API/confirmation/ERP gateway gates.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Newsletter_Consent {
    const STATUS_PENDING = 'pending';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_SUPPRESSED = 'suppressed';
    const CONSENT_SOURCE = 'andy_core_newsletter_form';
    const EXTERNAL_PREFIX = 'andy_core_newsletter:';

    public static function normalize_email( $raw ) {
        if ( ! is_string( $raw ) ) { return null; }
        $email = strtolower( trim( $raw ) );
        if ( '' === $email || strlen( $email ) > 190 || false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            return null;
        }
        return $email;
    }

    public static function external_id( $site, $email ) {
        $normalized = self::normalize_email( $email );
        $site = is_string( $site ) ? strtolower( trim( $site ) ) : '';
        if ( null === $normalized || '' === $site || strlen( $site ) > 190 ||
            ! preg_match( '/^[a-z0-9.-]+$/D', $site ) ) {
            return null;
        }
        // Site-scoped and email-derived, but never embeds the plaintext address.
        return self::EXTERNAL_PREFIX . hash( 'sha256', $site . "\n" . $normalized );
    }

    /**
     * Newly submitted address is always pending, never EDM-ready.
     * Capturing a checked form is not equivalent to inbox ownership.
     */
    public static function create_pending( $site, $email, $time, $source = self::CONSENT_SOURCE ) {
        $id = self::external_id( $site, $email );
        if ( null === $id || ! self::valid_timestamp( $time ) || ! is_string( $source ) || '' === trim( $source ) ) {
            return null;
        }
        return array(
            'external_subscription_id' => $id,
            'email' => self::normalize_email( $email ),
            'source_site' => strtolower( trim( $site ) ),
            'provider' => 'wordpress',
            'status' => self::STATUS_PENDING,
            'consent_source' => trim( $source ),
            'submitted_at' => $time,
            'subscribed_at' => null,
            'unsubscribed_at' => null,
            'status_updated_at' => $time,
            'confirmation_token_hash' => null,
        );
    }

    /**
     * Confirm only via the later token-verifying controller, never directly
     * from a submitted email string. The state machine cannot validate tokens.
     *
     * Events: confirm, unsubscribe, resubscribe, suppress. A suppressed
     * address cannot be silently reactivated by a subsequent form submission.
     */
    public static function transition( $record, $event, $time ) {
        if ( ! is_array( $record ) || ! self::valid_timestamp( $time ) ||
            ! in_array( $event, array( 'confirm', 'unsubscribe', 'resubscribe', 'suppress' ), true ) ) {
            return null;
        }
        $status = isset( $record['status'] ) && is_string( $record['status'] ) ? $record['status'] : '';
        if ( ! in_array( $status, array( 'pending', 'subscribed', 'unsubscribed', 'suppressed' ), true ) ) {
            return null;
        }
        $new = $record;
        if ( 'suppressed' === $status ) {
            return $new; // Future manual suppression review is separately gated.
        }
        if ( 'suppress' === $event ) {
            $new['status'] = self::STATUS_SUPPRESSED;
            $new['unsubscribed_at'] = $time;
        } elseif ( 'unsubscribe' === $event ) {
            if ( 'unsubscribed' === $status ) { return $new; }
            $new['status'] = self::STATUS_UNSUBSCRIBED;
            $new['unsubscribed_at'] = $time;
        } elseif ( 'resubscribe' === $event ) {
            if ( 'unsubscribed' !== $status ) { return $new; }
            // Explicit user action still requires fresh inbox confirmation.
            $new['status'] = self::STATUS_PENDING;
            $new['subscribed_at'] = null;
            $new['unsubscribed_at'] = null;
        } elseif ( 'confirm' === $event ) {
            if ( 'pending' !== $status ) { return $new; }
            $new['status'] = self::STATUS_SUBSCRIBED;
            $new['subscribed_at'] = $time;
            $new['unsubscribed_at'] = null;
        }
        if ( $new['status'] !== $status ) {
            $new['status_updated_at'] = $time;
        }
        return $new;
    }

    /**
     * Allowlist safe facts only. Tokens/IP fingerprints never cross to ERP.
     * ERP existing MarketingSubscriberSyncWriter upserts by provider+external ID.
     */
    public static function erp_snapshot_item( $record ) {
        if ( ! is_array( $record ) || ! in_array( $record['status'] ?? null,
            array( 'pending', 'subscribed', 'unsubscribed', 'suppressed' ), true ) ||
            null === self::normalize_email( $record['email'] ?? null ) ||
            empty( $record['source_site'] ) ) {
            return null;
        }
        $id = self::external_id( $record['source_site'], $record['email'] );
        if ( null === $id || $id !== ( $record['external_subscription_id'] ?? null ) ) {
            return null;
        }
        return array(
            'external_subscription_id' => $id,
            'email' => self::normalize_email( $record['email'] ),
            'provider' => 'wordpress',
            'source_site' => $record['source_site'],
            'status' => $record['status'],
            'consent_source' => $record['consent_source'] ?? self::CONSENT_SOURCE,
            'subscribed_at' => $record['subscribed_at'] ?? null,
            'unsubscribed_at' => $record['unsubscribed_at'] ?? null,
            'status_updated_at' => $record['status_updated_at'] ?? null,
        );
    }

    private static function valid_timestamp( $value ) {
        if ( ! is_string( $value ) || '' === $value ) { return false; }
        try { return false !== ( new DateTimeImmutable( $value ) )->getTimestamp(); }
        catch ( Exception $exception ) { return false; }
    }
}
