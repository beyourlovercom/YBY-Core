<?php
/**
 * Andy Core controlled L3 extensions V1.
 *
 * Extends only known, genuine WordPress admin page groups. The page controller
 * still owns route handling and its real authorization. This registry never
 * creates admin routes or changes L1/L2.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Andy_L3_Registry_V1 {
    const FILTER = 'andy_core_l3_external_items';

    /** Host page => [minimum controller capability, original WP menu parent]. */
    private static function hosts() {
        return array(
            'andy-commerce'         => array( 'manage_woocommerce', '' ),
            'andy-core-leads'       => array( 'andy_core_leads_view', 'yby-os' ),
            'andy-core-newsletter'  => array( 'andy_core_newsletter_view', 'yby-os' ),
            'andy-core-email'       => array( 'andy_core_mail_view', 'yby-os' ),
        );
    }

    private static function slug( $value, $max = 64 ) {
        return is_string( $value ) && strlen( $value ) <= $max
            && (bool) preg_match( '/^[a-z][a-z0-9_-]*$/D', $value );
    }

    private static function tab_of( $url, $page ) {
        if ( ! is_string( $url ) ) { return ''; }
        $parsed = parse_url( $url );
        if ( ! is_array( $parsed ) || ! isset( $parsed['query'] ) ) { return ''; }
        $args = array();
        parse_str( $parsed['query'], $args );
        return ( $args['page'] ?? null ) === $page && is_string( $args['tab'] ?? null )
            ? $args['tab'] : '';
    }

    /**
     * Consumer hook: apply_filters( 'andy_core_l3_external_items', [], $page ).
     * Declarations are data, not URLs: id, parent, page, title, tab,
     * capability, after (optional native tab), route_check (callable).
     * Return false from route_check when the actual controller/module is absent.
     */
    public static function extend( $native, $page ) {
        if ( ! is_array( $native ) || ! is_string( $page ) ||
            ! function_exists( 'apply_filters' ) || ! function_exists( 'is_admin' ) ||
            ! is_admin() ) { return $native; }

        $hosts = self::hosts();
        if ( ! isset( $hosts[ $page ] ) || ! current_user_can( $hosts[ $page ][0] ) ) {
            return $native;
        }
        // No phantom pages: the original page must have a registered WP callback.
        if ( ! function_exists( 'did_action' ) || ! did_action( 'admin_menu' ) ||
             ! function_exists( 'get_plugin_page_hookname' ) || ! function_exists( 'has_action' ) ||
             false === has_action( get_plugin_page_hookname( $page, $hosts[ $page ][1] ) ) ) {
            return $native;
        }

        $declared = apply_filters( self::FILTER, array(), $page );
        if ( ! is_array( $declared ) || count( $declared ) > 100 ) { return $native; }

        $native_tabs = array();
        foreach ( $native as $entry ) {
            $tab = self::tab_of( $entry['url'] ?? '', $page );
            if ( $tab !== '' ) { $native_tabs[ $tab ] = true; }
        }

        $known_ids = array();
        $known_tabs = $native_tabs;
        $buckets = array();
        $allowed_keys = array( 'id', 'parent', 'page', 'title', 'tab',
            'capability', 'after', 'route_check' );
        foreach ( $declared as $item ) {
            if ( ! is_array( $item ) ||
                 array_diff( array_keys( $item ), $allowed_keys ) ) { continue; }
            $id = $item['id'] ?? null;
            $title = $item['title'] ?? null;
            $tab = $item['tab'] ?? null;
            $cap = $item['capability'] ?? null;
            $after = $item['after'] ?? '';
            if ( ! is_string( $id ) || strlen( $id ) > 130 ||
                 ! preg_match( '/^[a-z][a-z0-9_-]*\.[a-z][a-z0-9_-]*$/D', $id ) ||
                 strpos( $id, $page . '.' ) !== 0 ||
                 ( $item['parent'] ?? null ) !== $page ||
                 ( $item['page'] ?? null ) !== $page ||
                 ! self::slug( $tab ) || ! self::slug( $cap ) ||
                 ! is_string( $title ) || trim( $title ) !== $title ||
                 $title === '' || strlen( $title ) > 120 ||
                 preg_match( '/[<>\x00-\x1f\x7f]/', $title ) ||
                 ( function_exists( 'wp_strip_all_tags' ) && wp_strip_all_tags( $title ) !== $title ) ||
                 ! is_string( $after ) ||
                 ( $after !== '' && ( ! self::slug( $after ) || ! isset( $native_tabs[ $after ] ) ) ) ||
                 ! isset( $item['route_check'] ) || ! is_callable( $item['route_check'] ) ||
                 ! current_user_can( $cap ) ||
                 isset( $known_ids[ $id ] ) || isset( $known_tabs[ $tab ] ) ) {
                continue;
            }
            // The actual plugin controller, not an L3 label, must validate tab availability.
            try {
                if ( true !== call_user_func( $item['route_check'], $tab, $page ) ) { continue; }
            } catch ( Throwable $e ) {
                continue; // A broken extension cannot break native admin navigation.
            }
            $known_ids[ $id ] = true;
            $known_tabs[ $tab ] = true;
            $buckets[ $after ][] = array(
                'title' => $title,
                'url'   => admin_url( 'admin.php?page=' . $page . '&tab=' . $tab ),
            );
        }
        $result = array();
        foreach ( $native as $entry ) {
            $result[] = $entry;
            $tab = self::tab_of( $entry['url'] ?? '', $page );
            if ( $tab !== '' && ! empty( $buckets[ $tab ] ) ) {
                foreach ( $buckets[ $tab ] as $addition ) { $result[] = $addition; }
            }
        }
        foreach ( $buckets[''] ?? array() as $addition ) { $result[] = $addition; }
        return $result;
    }
}
