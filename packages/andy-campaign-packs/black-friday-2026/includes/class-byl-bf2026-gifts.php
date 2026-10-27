<?php
/**
 * Code-first 2026 promotion: Woo native sale prices + one selectable gift;
 * merchandise net USD >= 199.00 unlocks a second; premium gift has upgrade price.
 * No other coupons, shipping, FX, payment, page editing or discount engines are replaced.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class BYL_BF2026_Gifts {
    public const ID = 'byl-black-friday-2026';
    public const CART_FLAG = 'andy_campaign_pack_id';
    private static $busy = false;

    /** Gift catalog is code-configured and deliberately empty by default. */
    public static function catalog(): array {
        $source = apply_filters( 'byl_bf2026_gift_catalog', array() );
        if ( ! is_array( $source ) || count( $source ) > 30 ) { return array(); }
        $result = array();
        foreach ( $source as $id => $settings ) {
            $id = filter_var( $id, FILTER_VALIDATE_INT );
            if ( ! $id || $id < 1 || ! is_array( $settings ) ) { continue; }
            $amount = $settings['upgrade_minor'] ?? null;
            if ( ! is_int( $amount ) || $amount < 0 || $amount > 100000 ) { continue; }
            $result[ $id ] = $amount;
        }
        return $result;
    }

    public static function boot(): void {
        if ( ! class_exists( 'WooCommerce' ) ) { return; }
        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'reprice' ), 80 );
        add_action( 'woocommerce_after_calculate_totals', array( __CLASS__, 'reconcile' ), 80 );
        add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate' ), 80 );
        // Native coupons may discount merchandise but never the promotional gift upgrade line.
        add_filter( 'woocommerce_coupon_is_valid_for_product', array( __CLASS__, 'exclude_gift_coupon' ), 90, 4 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_line' ), 80, 4 );
        add_shortcode( 'byl_bf2026_gifts', array( __CLASS__, 'render' ) );
        add_action( 'wp_loaded', array( __CLASS__, 'handle_post' ), 25 );
    }

    private static function valid_currency(): bool {
        return function_exists( 'get_woocommerce_currency' )
            && get_woocommerce_currency() === 'USD'
            && function_exists( 'wc_get_price_decimals' )
            && wc_get_price_decimals() === 2;
    }

    public static function is_gift( array $line ): bool {
        return ( $line[ self::CART_FLAG ] ?? null ) === self::ID
            && ( $line['_byl_bf2026_gift'] ?? null ) === true;
    }

    /** Woo native line_total already includes cart coupon allocation, excludes tax and shipping. */
    public static function eligibility( $cart ): array {
        if ( ! $cart instanceof WC_Cart || ! self::valid_currency() ) {
            return array( 'basis_minor' => 0, 'slots' => 0 );
        }
        $basis = 0;
        foreach ( $cart->get_cart() as $line ) {
            if ( self::is_gift( $line ) ) { continue; }
            $value = $line['line_total'] ?? null;
            if ( ! is_numeric( $value ) || (float) $value < 0 ) {
                return array( 'basis_minor' => 0, 'slots' => 0 );
            }
            $basis += (int) round( (float) $value * 100 );
        }
        return array( 'basis_minor' => $basis, 'slots' => $basis <= 0 ? 0 : ( $basis >= 19900 ? 2 : 1 ) );
    }

    public static function reprice( $cart ): void {
        if ( ! $cart instanceof WC_Cart ) { return; }
        $catalog = self::catalog();
        foreach ( $cart->get_cart() as $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            $id = (int) ( $line['product_id'] ?? 0 );
            if ( ! isset( $catalog[ $id ] ) || ! self::valid_currency()
                 || ! isset( $line['data'] ) || ! $line['data'] instanceof WC_Product ) {
                continue; // reconcile removes invalid/unsupported gift lines.
            }
            $line['data']->set_price( wc_format_decimal( $catalog[ $id ] / 100 ) );
        }
    }

    /** Any cart edits, expiration or coupon changes must revoke unearned bonus lines. */
    public static function reconcile( $cart ): void {
        if ( self::$busy || ! $cart instanceof WC_Cart ) { return; }
        $catalog = self::catalog();
        $slots = self::eligibility( $cart )['slots'];
        $kept = 0; $remove = array();
        foreach ( $cart->get_cart() as $key => $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            $id = (int) ( $line['product_id'] ?? 0 );
            if ( ! isset( $catalog[ $id ] ) || ! self::valid_currency()
                 || (int) ( $line['quantity'] ?? 0 ) !== 1 || $kept >= $slots ) {
                $remove[] = $key;
            } else { ++$kept; }
        }
        if ( ! $remove ) { return; }
        self::$busy = true;
        try {
            foreach ( $remove as $key ) { $cart->remove_cart_item( $key ); }
            $cart->calculate_totals();
        } finally { self::$busy = false; }
    }

    public static function validate(): void {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
        $cart = WC()->cart;
        $catalog = self::catalog();
        $slots = self::eligibility( $cart )['slots'];
        $count = 0;
        foreach ( $cart->get_cart() as $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            $id = (int) ( $line['product_id'] ?? 0 );
            ++$count;
            if ( ! isset( $catalog[ $id ] ) || (int) ( $line['quantity'] ?? 0 ) !== 1 ) {
                wc_add_notice( 'Invalid promotional gift; please update your cart.', 'error' );
                return;
            }
        }
        if ( $count > $slots ) { wc_add_notice( 'Gift selection exceeds your eligible allowance.', 'error' ); }
    }

    public static function exclude_gift_coupon( $valid, $product, $coupon, $cart_item ) {
        return is_array( $cart_item ) && self::is_gift( $cart_item ) ? false : $valid;
    }

    public static function order_line( $item, $cart_item_key, $values, $order ): void {
        if ( ! is_array( $values ) || ! self::is_gift( $values ) ) { return; }
        $id = (int) ( $values['product_id'] ?? 0 );
        $catalog = self::catalog();
        if ( ! isset( $catalog[ $id ] ) ) { return; }
        $item->add_meta_data( '_andy_campaign_id', self::ID, true );
        $item->add_meta_data( '_byl_bf2026_upgrade_usd_minor', $catalog[ $id ], true );
    }

    public static function handle_post(): void {
        $mode = sanitize_key( wp_unslash( $_POST['byl_bf2026_action'] ?? '' ) );
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' )
            || ! in_array( $mode, array( 'select', 'remove' ), true )
            || ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
        $token = sanitize_text_field( wp_unslash( $_POST['byl_bf2026_nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $token, 'byl_bf2026_gifts' ) ) {
            wp_die( 'Invalid gift selection nonce', '', array( 'response' => 403 ) );
        }
        if ( 'remove' === $mode ) {
            $key = sanitize_text_field( wp_unslash( $_POST['gift_cart_key'] ?? '' ) );
            $line = WC()->cart->get_cart_item( $key );
            if ( is_array( $line ) && self::is_gift( $line ) ) {
                WC()->cart->remove_cart_item( $key );
            }
            WC()->cart->calculate_totals();
            wp_safe_redirect( wp_get_referer() ?: wc_get_cart_url() );
            exit;
        }
        $id = absint( $_POST['gift_product_id'] ?? 0 );
        $catalog = self::catalog();
        WC()->cart->calculate_totals();
        $slots = self::eligibility( WC()->cart )['slots'];
        $selected = 0;
        foreach ( WC()->cart->get_cart() as $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            if ( (int) ( $line['product_id'] ?? 0 ) === $id ) { $slots = 0; }
            ++$selected;
        }
        $product = isset( $catalog[ $id ] ) ? wc_get_product( $id ) : false;
        if ( $slots <= $selected || ! $product || ! $product->is_purchasable()
            || ! $product->is_in_stock() || $product->is_type( 'variable' ) ) {
            wc_add_notice( 'This promotional gift is unavailable or you have reached the gift limit.', 'error' );
        } else {
            $key = WC()->cart->add_to_cart( $id, 1, 0, array(), array(
                self::CART_FLAG => self::ID, '_byl_bf2026_gift' => true,
            ) );
            if ( ! $key ) { wc_add_notice( 'Unable to add the selected gift.', 'error' ); }
        }
        WC()->cart->calculate_totals();
        wp_safe_redirect( wp_get_referer() ?: wc_get_cart_url() );
        exit;
    }

    public static function render(): string {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return ''; }
        WC()->cart->calculate_totals();
        $eligible = self::eligibility( WC()->cart );
        $catalog = self::catalog();
        $selected = 0;
        $selected_ids = array();
        $selected_keys = array();
        foreach ( WC()->cart->get_cart() as $key => $line ) {
            if ( self::is_gift( $line ) ) {
                ++$selected;
                $selected_ids[] = (int) ( $line['product_id'] ?? 0 );
                $selected_keys[] = $key;
            }
        }
        ob_start();
        echo '<section class="byl-bf2026-gifts"><h2>Choose your Black Friday gift</h2>';
        echo '<p>Eligible gifts: ' . esc_html( (string) $eligible['slots'] ) . ' · Selected: ' . esc_html( (string) $selected ) . '</p>';
        if ( ! self::valid_currency() ) {
            echo '<p>Gift selection is currently unavailable in this currency.</p>';
        } elseif ( ! $catalog ) {
            echo '<p>No gift items are currently configured.</p>';
        } else {
            foreach ( $catalog as $id => $upgrade ) {
                $p = wc_get_product( $id );
                if ( ! $p || ! $p->is_purchasable() || ! $p->is_in_stock() ) { continue; }
                echo '<form method="post">';
                wp_nonce_field( 'byl_bf2026_gifts', 'byl_bf2026_nonce' );
                echo '<input type="hidden" name="byl_bf2026_action" value="select">';
                echo '<input type="hidden" name="gift_product_id" value="' . esc_attr( (string) $id ) . '">';
                echo '<span>' . esc_html( $p->get_name() ) . ( $upgrade > 0 ? ' · Upgrade +$' . esc_html( number_format( $upgrade / 100, 2 ) ) : ' · FREE' ) . '</span> ';
                $blocked = $selected >= $eligible['slots'] || in_array( $id, $selected_ids, true );
                echo '<button type="submit"' . ( $blocked ? ' disabled' : '' ) . '>'
                    . ( in_array( $id, $selected_ids, true ) ? 'Selected' : 'Choose' ) . '</button></form>';
            }
            foreach ( $selected_keys as $key ) {
                echo '<form method="post">';
                wp_nonce_field( 'byl_bf2026_gifts', 'byl_bf2026_nonce' );
                echo '<input type="hidden" name="byl_bf2026_action" value="remove">';
                echo '<input type="hidden" name="gift_cart_key" value="' . esc_attr( (string) $key ) . '">';
                echo '<button type="submit">Remove selected gift</button></form>';
            }
        }
        echo '</section>';
        return (string) ob_get_clean();
    }
}
