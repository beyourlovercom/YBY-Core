<?php
/**
 * Plugin Name: Andy Commerce Observer (Read-only)
 * Description: Admin-only inventory for Andy Commerce production parity. No business hooks or writes.
 * Version: 0.1.1-rc2
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: YBY Global
 * Text Domain: andy-commerce-observer
 * Requires Plugins: yby-core, woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Standalone, opt-in observer. DO NOT load the full Andy Commerce bootstrap.
 * No activation, deactivation, uninstall, migration, cron, Store API, cart,
 * coupon, shipping, payment, order, product, customer, AJAX or REST hooks.
 * The only hook is admin_menu and it is attached only in wp-admin context.
 */
final class Andy_Commerce_Read_Only_Observer {
    const VERSION = '0.1.1-rc2';
    const PAGE_SLUG = 'andy-commerce-read-only-observer';

    public static function register() {
        if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
            return;
        }
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ), 99 );
    }

    public static function register_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        add_management_page(
            'Andy Commerce Observer',
            'Andy Commerce Observer',
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
    }

    private static function displayed_plugin_inventory() {
        // WordPress built-in read-only metadata inventory. Do NOT activate plugins.
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $inventory = array();
        $catalog   = get_plugins();
        $wanted    = array(
            'Andy Core', 'Andy Commerce', 'WooCommerce', 'YayCurrency',
            'Smart Coupons', 'Smart Coupon', 'WooPayments',
            'WooCommerce Payments', 'Stripe', 'Nextend Social Login',
            'WP Rocket', 'AfterShip',
        );
        foreach ( (array) $catalog as $basename => $details ) {
            $name = isset( $details['Name'] ) ? (string) $details['Name'] : '';
            $show = false;
            foreach ( $wanted as $candidate ) {
                if ( false !== stripos( $name, $candidate ) ) {
                    $show = true;
                    break;
                }
            }
            if ( ! $show ) {
                continue;
            }
            $inventory[] = array(
                'name'    => $name,
                'version' => isset( $details['Version'] ) ? (string) $details['Version'] : '',
                'active'  => function_exists( 'is_plugin_active' ) && is_plugin_active( $basename )
                    ? 'Active' : 'Not active',
            );
        }
        usort( $inventory, static function ( $a, $b ) {
            return strcasecmp( $a['name'], $b['name'] );
        } );
        return $inventory;
    }

    private static function hook_counts() {
        global $wp_filter;
        $names = array(
            'woocommerce_shipping_chosen_method',
            'woocommerce_shipping_free_shipping_is_available',
            'woocommerce_get_shop_coupon_data',
            'woocommerce_coupon_get_individual_use',
            'woocommerce_apply_with_individual_use_coupon',
            'woocommerce_cart_loaded_from_session',
            'woocommerce_before_calculate_totals',
            'woocommerce_cart_calculate_fees',
            'woocommerce_coupon_is_valid',
            'woocommerce_checkout_create_order',
            'woocommerce_payment_complete',
            'woocommerce_new_order',
            'woocommerce_update_order',
            'woocommerce_store_api_checkout_update_order_from_request',
        );
        $counts = array();
        foreach ( $names as $name ) {
            $count = 0;
            if ( isset( $wp_filter[ $name ] ) && is_object( $wp_filter[ $name ] ) ) {
                $callbacks = isset( $wp_filter[ $name ]->callbacks )
                    ? $wp_filter[ $name ]->callbacks : array();
                foreach ( (array) $callbacks as $priority_callbacks ) {
                    $count += count( (array) $priority_callbacks );
                }
            }
            $counts[ $name ] = $count;
        }
        return $counts;
    }

    private static function read_settings_presence() {
        $keys = array(
            'byl_shipping_promotion_settings',
            'andy_commerce_shipping_promotion_settings_v1',
            'andy_commerce_byl_promotion_handoff_v1',
        );
        $status = array();
        foreach ( $keys as $key ) {
            // No option values, secrets, discounts or PII displayed or stored.
            $status[ $key ] = null === get_option( $key, null ) ? 'Not present' : 'Present';
        }
        return $status;
    }

    /**
     * Pure, read-only reproduction of Commerce v1 schema acceptance.
     * Never calls Commerce runtime, its getters, shipping zone APIs, filters or cart.
     * This is evidence about persisted configuration only, not proof of checkout output.
     */
    private static function valid_promotion_option( $saved ) {
        if ( ! is_array( $saved ) ) { return false; }
        if ( 1 !== (int) ( $saved['schema_version'] ?? 0 ) ) { return false; }
        if ( ! in_array( $saved['mode'] ?? null, array( 'automatic', 'require_code' ), true ) ) { return false; }
        if ( ! is_string( $saved['free_shipping_code'] ?? null ) ||
            1 !== preg_match( '/^[A-Z0-9_-]{1,50}$/', $saved['free_shipping_code'] ) ) { return false; }
        $amount = $saved['global_threshold'] ?? null;
        if ( is_bool( $amount ) || ! is_numeric( $amount ) || ! is_finite( (float) $amount ) ||
            (float) $amount < 0 || (float) $amount > 999999.99 ) { return false; }
        return 'pre_discount_merchandise_subtotal' === ( $saved['eligibility_basis'] ?? null );
    }

    /**
     * Reads only known local option values. Does not display coupon codes, addresses,
     * user data, gateway keys, or order data. Does not evaluate/cart-apply promotion.
     */
    private static function shipping_policy_snapshot() {
        $legacy = get_option( 'byl_shipping_promotion_settings', null );
        $commerce = get_option( 'andy_commerce_shipping_promotion_settings_v1', null );
        $handoff = get_option( 'andy_commerce_byl_promotion_handoff_v1', null );
        $is_byl = false;
        if ( function_exists( 'home_url' ) && function_exists( 'wp_parse_url' ) ) {
            $host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
            $is_byl = is_string( $host ) && (
                'beyourlover.com' === strtolower( $host ) ||
                ( strlen( $host ) > strlen( '.beyourlover.com' ) &&
                    0 === substr_compare( strtolower( $host ), '.beyourlover.com', -strlen( '.beyourlover.com' ) ) )
            );
        }
        $legacy_valid = self::valid_promotion_option( $legacy );
        $commerce_valid = self::valid_promotion_option( $commerce );
        $legacy_wins = $is_byl && 'commerce' !== $handoff && $legacy_valid;
        $selected = $legacy_wins ? $legacy : ( $commerce_valid ? $commerce : null );
        $origin = $legacy_wins ? 'Legacy BYL policy' : ( $commerce_valid ? 'Andy Commerce policy' : 'No verified active policy' );
        $threshold = is_array( $selected ) ? (float) $selected['global_threshold'] : null;
        $overrides = array();
        if ( is_array( $selected ) && isset( $selected['zone_overrides'] ) && is_array( $selected['zone_overrides'] ) ) {
            foreach ( $selected['zone_overrides'] as $zone_id => $setting ) {
                if ( count( $overrides ) >= 25 ) { break; }
                // Do not call WC_Shipping_Zones or any potential third-party hooks.
                if ( ! ctype_digit( (string) $zone_id ) || ! is_array( $setting ) ||
                    'override' !== ( $setting['mode'] ?? null ) ) { continue; }
                $value = $setting['threshold'] ?? null;
                if ( is_bool( $value ) || ! is_numeric( $value ) || ! is_finite( (float) $value ) ||
                    (float) $value < 0 || (float) $value > 999999.99 ) { continue; }
                $overrides[] = 'Zone ' . (int) $zone_id . ': ' . number_format( (float) $value, 2, '.', '' );
            }
        }
        return array(
            'source' => $origin,
            'legacy_schema' => $legacy_valid ? 'Valid v1' : ( null !== $legacy ? 'Present but invalid' : 'Not present' ),
            'commerce_schema' => $commerce_valid ? 'Valid v1' : ( null !== $commerce ? 'Present but invalid' : 'Not present' ),
            'handoff' => 'commerce' === $handoff ? 'Explicit Commerce handoff' : 'Not granted',
            'global_threshold' => null === $threshold ? 'Unverified' : number_format( $threshold, 2, '.', '' ),
            'mode' => is_array( $selected ) ? (string) $selected['mode'] : 'Unverified',
            'overrides' => $overrides,
            'legacy_loaded' => class_exists( 'BYL_Shipping_Promotion_Runtime', false ) ? 'Yes' : 'No',
            'commerce_loaded' => class_exists( 'Andy_Commerce', false ) ? 'Yes' : 'No',
        );
    }

    /**
     * Inspect callable REGISTRATION METADATA only. No callback invocations,
     * reflection, autoload, filesystem paths or customer/business value reads.
     */
    private static function callback_source_rows() {
        global $wp_filter;
        $observed = array(
            'woocommerce_shipping_chosen_method',
            'woocommerce_shipping_free_shipping_is_available',
            'woocommerce_get_shop_coupon_data',
            'woocommerce_coupon_get_individual_use',
            'woocommerce_apply_with_individual_use_coupon',
            'woocommerce_cart_loaded_from_session',
            'woocommerce_before_calculate_totals',
            'woocommerce_cart_calculate_fees',
            'woocommerce_checkout_create_order',
            'woocommerce_store_api_checkout_update_order_from_request',
        );
        $rows = array();
        foreach ( $observed as $hook ) {
            if ( ! isset( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) ) { continue; }
            foreach ( (array) ( $wp_filter[ $hook ]->callbacks ?? array() ) as $priority => $entries ) {
                foreach ( (array) $entries as $entry ) {
                    if ( count( $rows ) >= 125 ) { break 3; }
                    $callback = is_array( $entry ) ? ( $entry['function'] ?? null ) : null;
                    $label = 'Unidentified callable';
                    if ( is_string( $callback ) ) { $label = $callback; }
                    elseif ( is_array( $callback ) && count( $callback ) >= 2 ) {
                        $class = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
                        $method = is_string( $callback[1] ) ? $callback[1] : 'unknown';
                        $label = $class . '::' . $method;
                    } elseif ( $callback instanceof Closure ) { $label = 'Closure (unknown provider)'; }
                    elseif ( is_object( $callback ) ) { $label = get_class( $callback ) . '::__invoke'; }
                    $owner = 'Other / unknown';
                    if ( 0 === strpos( $label, 'Andy_Commerce_' ) ) { $owner = 'Andy Commerce'; }
                    elseif ( 0 === strpos( $label, 'BYL_' ) ) { $owner = 'BYL legacy'; }
                    elseif ( 0 === strpos( $label, 'YBY_' ) ) { $owner = 'Andy Core / YBY'; }
                    elseif ( 0 === strpos( $label, 'WC_' ) || 0 === strpos( $label, 'Automattic\\WooCommerce\\' ) ) { $owner = 'WooCommerce'; }
                    $rows[] = array(
                        'hook' => $hook,
                        'priority' => (string) (int) $priority,
                        'source' => $owner,
                        'callback' => substr( $label, 0, 110 ),
                    );
                }
            }
        }
        return $rows;
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to view this page.', 'andy-commerce-observer' ) );
        }
        $plugins = self::displayed_plugin_inventory();
        $counts  = self::hook_counts();
        $options = self::read_settings_presence();
        $policy  = self::shipping_policy_snapshot();
        $owners  = self::callback_source_rows();
        $theme   = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
        $theme_name = $theme ? $theme->get( 'Name' ) : 'Unverified';
        $theme_version = $theme ? $theme->get( 'Version' ) : 'Unverified';
        $info = array(
            'Observer'           => self::VERSION,
            'WordPress'          => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'Unverified',
            'PHP'                => PHP_VERSION,
            'Andy Core runtime'  => defined( 'YBY_CORE_VERSION' ) ? YBY_CORE_VERSION : 'Not loaded',
            'WooCommerce runtime' => defined( 'WC_VERSION' ) ? WC_VERSION : 'Not loaded',
            'WP environment'     => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'Unverified',
            'Theme'              => $theme_name . ' ' . $theme_version,
            'Woo base currency'  => (string) get_option( 'woocommerce_currency', 'Unverified' ),
            'Woo HPOS option'    => (string) get_option( 'woocommerce_custom_orders_table_enabled', 'Unverified' ),
        );
        ?>
        <div class="wrap">
            <h1>Andy Commerce Observer — Read-only</h1>
            <p><strong>Observer only.</strong> No Commerce runtime, customer session, checkout,
                shipping, coupon, gift, currency, payment, order, inventory, webhook or scheduled actions are executed.</p>
            <p>This page reads high-level configuration, names, versions and counts only. Hook counts reflect other already-active plugins, not hooks registered by Observer.</p>
            <h2>Runtime (read-only)</h2>
            <table class="widefat striped"><tbody>
            <?php foreach ( $info as $name => $value ) : ?>
                <tr><th scope="row"><?php echo esc_html( $name ); ?></th><td><?php echo esc_html( (string) $value ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2>Relevant plugin inventory (read-only)</h2>
            <table class="widefat striped"><thead><tr><th>Plugin</th><th>Version</th><th>State</th></tr></thead><tbody>
            <?php foreach ( $plugins as $plugin ) : ?>
                <tr><td><?php echo esc_html( $plugin['name'] ); ?></td><td><?php echo esc_html( $plugin['version'] ); ?></td><td><?php echo esc_html( $plugin['active'] ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2>Shipping policy option presence (no values read out)</h2>
            <table class="widefat striped"><tbody>
            <?php foreach ( $options as $key => $presence ) : ?>
                <tr><th scope="row"><?php echo esc_html( $key ); ?></th><td><?php echo esc_html( $presence ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2>Effective shipping policy snapshot (read-only inference)</h2>
            <p><strong>Interpretation:</strong> Reproduces the current Commerce settings-precedence rule against saved option data. It does not execute shipping/coupon code or determine actual Woo shipping rates. Threshold units follow the stored policy; do not infer source USD for YayCurrency storefront totals.</p>
            <table class="widefat striped"><tbody>
                <tr><th scope="row">Likely settings authority</th><td><?php echo esc_html( $policy['source'] ); ?></td></tr>
                <tr><th scope="row">Legacy schema</th><td><?php echo esc_html( $policy['legacy_schema'] ); ?></td></tr>
                <tr><th scope="row">Commerce schema</th><td><?php echo esc_html( $policy['commerce_schema'] ); ?></td></tr>
                <tr><th scope="row">Ownership handoff</th><td><?php echo esc_html( $policy['handoff'] ); ?></td></tr>
                <tr><th scope="row">Global threshold (configured numeric)</th><td><?php echo esc_html( $policy['global_threshold'] ); ?></td></tr>
                <tr><th scope="row">Mode</th><td><?php echo esc_html( $policy['mode'] ); ?></td></tr>
                <tr><th scope="row">Zone override thresholds (bounded)</th><td><?php echo esc_html( implode( '; ', $policy['overrides'] ) ?: 'None in first 25 valid overrides' ); ?></td></tr>
                <tr><th scope="row">Legacy shipping runtime loaded</th><td><?php echo esc_html( $policy['legacy_loaded'] ); ?></td></tr>
                <tr><th scope="row">Andy Commerce class loaded</th><td><?php echo esc_html( $policy['commerce_loaded'] ); ?></td></tr>
            </tbody></table>
            <p><em>Scope: settings and class presence, not actual cart totals. Coupon code strings and any customer, payment, or order values are never displayed.</em></p>
            <h2>Existing WooCommerce hook counts (no callbacks executed)</h2>
            <table class="widefat striped"><thead><tr><th>Hook</th><th>Already registered callback count</th></tr></thead><tbody>
            <?php foreach ( $counts as $hook => $count ) : ?>
                <tr><td><code><?php echo esc_html( $hook ); ?></code></td><td><?php echo esc_html( (string) $count ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2>Registered checkout / shipping callback owners (read-only)</h2>
            <p>Names are read from already registered callbacks, never invoked. Labels are heuristic and do not verify the installed plugin of origin. No full server filesystem paths shown.</p>
            <table class="widefat striped"><thead><tr><th>Hook</th><th>Priority</th><th>Likely component</th><th>Callable label</th></tr></thead><tbody>
            <?php foreach ( $owners as $entry ) : ?>
                <tr><td><code><?php echo esc_html( $entry['hook'] ); ?></code></td><td><?php echo esc_html( $entry['priority'] ); ?></td><td><?php echo esc_html( $entry['source'] ); ?></td><td><code><?php echo esc_html( $entry['callback'] ); ?></code></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <p><em>No save buttons, no public endpoints, no PII, no database migrations, no settings changes.</em></p>
        </div>
        <?php
    }
}

Andy_Commerce_Read_Only_Observer::register();
