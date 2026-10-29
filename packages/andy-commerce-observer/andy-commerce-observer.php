<?php
/**
 * Plugin Name: Andy Commerce Observer (Read-only)
 * Description: Admin-only inventory for Andy Commerce production parity. No business hooks or writes.
 * Version: 0.1.0-rc1
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
    const VERSION = '0.1.0-rc1';
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

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to view this page.', 'andy-commerce-observer' ) );
        }
        $plugins = self::displayed_plugin_inventory();
        $counts  = self::hook_counts();
        $options = self::read_settings_presence();
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
            <h2>Existing WooCommerce hook counts (no callbacks executed)</h2>
            <table class="widefat striped"><thead><tr><th>Hook</th><th>Already registered callback count</th></tr></thead><tbody>
            <?php foreach ( $counts as $hook => $count ) : ?>
                <tr><td><code><?php echo esc_html( $hook ); ?></code></td><td><?php echo esc_html( (string) $count ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <p><em>No save buttons, no public endpoints, no PII, no database migrations, no settings changes.</em></p>
        </div>
        <?php
    }
}

Andy_Commerce_Read_Only_Observer::register();
