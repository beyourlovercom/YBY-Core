<?php
/**
 * Andy Core URL OS V1: pure, side-effect-free URL template / URI validation.
 *
 * No permalink filters, request rewrites, redirects or WordPress option writes.
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_URL_OS_Contract {
    /** Returns a normalized candidate for PREVIEW comparisons, never for writes. */
    public static function normalize_candidate( $input ) {
        if ( ! is_string( $input ) ) { return null; }
        $input = trim( $input );
        if ( '' === $input ) { return ''; }
        if ( preg_match( '/[\x00-\x1F\x7F\\\\?#]/u', $input ) || false !== strpos( $input, '://' ) ) { return null; }
        $path = trim( $input, '/' );
        if ( false !== strpos( $path, '//' ) || preg_match( '~(^|/)\\.{1,2}(/|$)~', $path ) ) { return null; }
        // Compare only. Do not case-fold or decode the stored permalink.
        return strtolower( $path );
    }

    /** Template placeholders use WordPress-style %token%; custom registered tokens are permitted. */
    public static function validate_template( $input, $allow_blank = false ) {
        $path = self::normalize_candidate( $input );
        if ( null === $path ) { return array( 'valid' => false, 'reason' => 'unsafe_path' ); }
        if ( '' === $path ) { return array( 'valid' => (bool) $allow_blank, 'reason' => $allow_blank ? '' : 'blank_template' ); }
        $without_tokens = preg_replace( '/%[A-Za-z_][A-Za-z0-9_\\-]*%/', '', $path );
        if ( false === $without_tokens || false !== strpos( $without_tokens, '%' ) ) {
            return array( 'valid' => false, 'reason' => 'invalid_placeholder' );
        }
        return array( 'valid' => true, 'reason' => '' );
    }

    /** Identifies protected route families; does not infer ownership of existing Woo pages. */
    public static function reserved_root( $uri ) {
        $path = self::normalize_candidate( $uri );
        if ( null === $path || '' === $path ) { return ''; }
        $root = explode( '/', $path, 2 )[0];
        return in_array( $root, array( 'wp-admin', 'wp-json', 'wp-content', 'wp-includes', 'cart', 'checkout', 'my-account', 'shop' ), true ) ? $root : '';
    }

    /** Compare distinct object IDs without changing, saving or regenerating any URI. */
    public static function collision_candidates( $records ) {
        $seen = array();
        $conflicts = array();
        foreach ( (array) $records as $record ) {
            if ( ! is_array( $record ) ) { continue; }
            $path = self::normalize_candidate( $record['uri'] ?? null );
            if ( null === $path || '' === $path ) { continue; } // Invalid/empty are separately audited.
            $kind = (string) ( $record['kind'] ?? '' );
            $id = (string) ( $record['id'] ?? '' );
            if ( '' === $kind || '' === $id ) { continue; }
            $identity = $kind . ':' . $id;
            if ( isset( $seen[ $path ] ) && $seen[ $path ] !== $identity ) {
                $conflicts[] = array( 'uri' => $path, 'first' => $seen[ $path ], 'second' => $identity );
            } else {
                $seen[ $path ] = $identity;
            }
        }
        return $conflicts;
    }
}
