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

    /** Owner-reviewed code-only ERP, cost and destination approvals. */
    public static function gift_approvals(): array {
        $items = apply_filters( 'byl_bf2026_gift_approvals', array() );
        return is_array( $items ) && count( $items ) <= 30 ? $items : array();
    }

    private static function approved_gift( int $id ): bool {
        $catalog = self::catalog();
        if ( ! isset( $catalog[ $id ] )
             || ! class_exists( 'BYL_BF2026_R8_Readiness' ) ) { return false; }
        $review = BYL_BF2026_R8_Readiness::inspect(
            array( $id => array( 'upgrade_minor' => (int) $catalog[ $id ] ) ),
            self::gift_approvals()
        );
        return ( $review['rows'][0]['status'] ?? 'BLOCKED' ) === 'APPROVED';
    }

    /** Shipping destination is verified at checkout, after shopper supplies
     * an address; do not guess from browser locale or billing IP.
     */
    private static function destination_allowed( int $id ): bool {
        $customer = function_exists( 'WC' ) && WC() ? WC()->customer : null;
        if ( ! $customer || ! method_exists( $customer, 'get_shipping_country' ) ) {
            return false;
        }
        $country = strtoupper( trim( (string) $customer->get_shipping_country() ) );
        if ( ! preg_match( '/^[A-Z]{2}$/D', $country ) ) { return false; }
        $approved = self::gift_approvals();
        $allowed = $approved[ $id ]['allowed_countries'] ?? null;
        return is_array( $allowed ) && in_array( $country, $allowed, true );
    }

    private static function gift_product( int $id ) {
        if ( ! function_exists( 'wc_get_product' ) || $id <= 0 ) { return false; }
        $product = wc_get_product( $id );
        if ( ! self::approved_gift( $id ) ) { return false; }
        if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' )
             || ! $product->is_purchasable() || ! $product->is_in_stock()
             || 'instock' !== $product->get_stock_status()
             || $product->backorders_allowed()
             || ( $product->managing_stock() && (int) $product->get_stock_quantity() < 1 ) ) {
            return false;
        }
        return $product;
    }

    /** Minimum two free options; the USD199 promise must never require paid upgrades. */
    public static function ready(): bool {
        if ( ! class_exists( 'WooCommerce' ) || ! self::valid_currency()
             || ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '8.8.0', '<' ) ) {
            return false;
        }
        if ( ! class_exists( 'BYL_BF2026_R8_Readiness' ) ) { return false; }
        $products = array();
        foreach ( self::catalog() as $id => $upgrade ) {
            $products[ (int) $id ] = array( 'upgrade_minor' => (int) $upgrade );
        }
        $review = BYL_BF2026_R8_Readiness::inspect( $products, self::gift_approvals() );
        if ( ! ( $review['release_ready'] ?? false ) ) { return false; }
        $free = 0;
        foreach ( self::catalog() as $id => $upgrade ) {
            if ( 0 === $upgrade && self::gift_product( (int) $id ) ) {
                ++$free;
                if ( $free >= 2 ) { return true; }
            }
        }
        return false;
    }

    public static function boot(): void {
        if ( ! class_exists( 'WooCommerce' ) ) { return; }
        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'reprice' ), 80 );
        add_action( 'woocommerce_after_calculate_totals', array( __CLASS__, 'reconcile' ), 80 );
        add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate' ), 80 );
        // Store API / Blocks validates an order through a separate WooCommerce hook.
        add_action( 'woocommerce_checkout_validate_order_before_payment', array( __CLASS__, 'validate_store_api' ), 80, 2 );
        // Native coupons may discount merchandise but never the promotional gift upgrade line.
        add_filter( 'woocommerce_coupon_is_valid_for_product', array( __CLASS__, 'exclude_gift_coupon' ), 90, 4 );
        // Fixed-cart coupons validate for the whole cart and bypass product eligibility.
        // Woo 8.8+ exposes the correct pre-allocation item list for all coupon types.
        add_filter( 'woocommerce_coupon_get_items_to_apply', array( __CLASS__, 'exclude_gift_discount_items' ), 90, 3 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_line' ), 80, 4 );
        add_action( 'wp_loaded', array( __CLASS__, 'handle_post' ), 25 );
    }

    /**
     * Deliberately USD-only until Commerce can supply an audited post-coupon
     * base-USD entitlement. FX display totals alone cannot prove the $199
     * threshold: Dev GBP rate 0.749160 rounds USD198.99 and 199.00 to the
     * same local penny. Never infer gift entitlements from inverse FX.
     */
    private static function valid_currency(): bool {
        if ( ! function_exists( 'get_woocommerce_currency' )
             || ! function_exists( 'wc_get_price_decimals' )
             || wc_get_price_decimals() !== 2 ) { return false; }
        $currency = get_woocommerce_currency();
        if ( 'USD' === $currency ) { return true; }
        if ( ! in_array( $currency, array( 'EUR', 'GBP', 'AUD', 'CAD' ), true ) ) {
            return false;
        }
        // Only the Commerce-owned, read-only, source-USD quote may approve
        // foreign entitlement. Never reverse-convert shopper-visible totals.
        return class_exists( 'Andy_Commerce_Campaign_USD_Basis_V1' )
            && class_exists( 'Andy_Commerce_Currency_Runtime_Dev_V1' )
            && Andy_Commerce_Currency_Runtime_Dev_V1::allowed()
            && Andy_Commerce_Currency_Runtime_Dev_V1::selected() === $currency;
    }

    private static function local_upgrade_price( int $usd_minor ): ?string {
        $base = number_format( $usd_minor / 100, 2, '.', '' );
        if ( 'USD' === get_woocommerce_currency() ) { return $base; }
        if ( ! self::valid_currency() ) { return null; }
        try {
            return Andy_Commerce_Currency_Runtime_Dev_V1::convert( $base );
        } catch ( Throwable $error ) { return null; }
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
        if ( 'USD' !== get_woocommerce_currency() ) {
            $quote = Andy_Commerce_Campaign_USD_Basis_V1::quote( $cart );
            if ( ( $quote['ok'] ?? false ) !== true
                || ! is_int( $quote['basis_minor'] ?? null ) ) {
                return array( 'basis_minor' => 0, 'slots' => 0 );
            }
            $basis = $quote['basis_minor'];
            return array( 'basis_minor' => $basis,
                'slots' => $basis <= 0 ? 0 : ( $basis >= 19900 ? 2 : 1 ) );
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
            // Currency Runtime's Woo price getter converts source USD once.
            // Writing local currency here would cause a second FX conversion.
            if ( null === self::local_upgrade_price( $catalog[ $id ] ) ) { continue; }
            $line['data']->set_price( wc_format_decimal( $catalog[ $id ] / 100 ) );
        }
    }

    /** Any cart edits, expiration or coupon changes must revoke unearned bonus lines. */
    public static function reconcile( $cart ): void {
        if ( self::$busy || ! $cart instanceof WC_Cart ) { return; }
        $catalog = self::catalog();
        $slots = self::eligibility( $cart )['slots'];
        $kept = 0; $remove = array(); $seen = array();
        foreach ( $cart->get_cart() as $key => $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            $id = (int) ( $line['product_id'] ?? 0 );
            if ( ! isset( $catalog[ $id ] ) || ! self::valid_currency()
                 || (int) ( $line['quantity'] ?? 0 ) !== 1 || $kept >= $slots
                 || isset( $seen[ $id ] ) || ! self::gift_product( $id )
                 || null === self::local_upgrade_price( (int) ( $catalog[ $id ] ?? 0 ) ) ) {
                $remove[] = $key;
            } else { ++$kept; $seen[ $id ] = true; }
        }
        if ( ! $remove ) { return; }
        self::$busy = true;
        try {
            foreach ( $remove as $key ) { $cart->remove_cart_item( $key ); }
            $cart->calculate_totals();
        } finally { self::$busy = false; }
    }

    /** One server-side validation result shared by Classic and Store API. */
    private static function cart_validation_issue( $cart ): string {
        if ( ! $cart instanceof WC_Cart ) { return ''; }
        $catalog = self::catalog();
        $slots = self::eligibility( $cart )['slots'];
        $count = 0; $seen = array();
        foreach ( $cart->get_cart() as $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            $id = (int) ( $line['product_id'] ?? 0 );
            ++$count;
            if ( ! isset( $catalog[ $id ] ) || (int) ( $line['quantity'] ?? 0 ) !== 1
                 || isset( $seen[ $id ] ) || ! self::gift_product( $id )
                 || null === self::local_upgrade_price( (int) ( $catalog[ $id ] ?? 0 ) ) ) {
                return 'Invalid promotional gift; please update your cart.';
            }
            if ( ! self::destination_allowed( $id ) ) {
                return 'This promotional gift cannot ship to the selected country.';
            }
            $seen[ $id ] = true;
        }
        if ( $count > $slots ) { return 'Gift selection exceeds your eligible allowance.'; }
        return '';
    }

    public static function validate(): void {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
        $reason = self::cart_validation_issue( WC()->cart );
        if ( '' !== $reason ) { wc_add_notice( $reason, 'error' ); }
    }

    /** Store API checkout uses WP_Error before payment, not Classic cart notices. */
    public static function validate_store_api( $order, $errors ): void {
        if ( ! $errors instanceof WP_Error || ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
        $reason = self::cart_validation_issue( WC()->cart );
        if ( '' !== $reason ) { $errors->add( 'byl_bf2026_invalid_gift', $reason ); }
    }

    public static function exclude_gift_coupon( $valid, $product, $coupon, $cart_item ) {
        return is_array( $cart_item ) && self::is_gift( $cart_item ) ? false : $valid;
    }

    /** Exclude any paid/free promotional gift before fixed-cart coupon allocation. */
    public static function exclude_gift_discount_items( $items, $coupon, $discounts ) {
        if ( ! is_array( $items ) ) { return $items; }
        return array_values( array_filter( $items, static function ( $item ) {
            $cart_item = is_object( $item ) ? ( $item->object ?? null ) : null;
            return ! is_array( $cart_item ) || ! self::is_gift( $cart_item );
        } ) );
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
        // Reject array-shaped POST values before WordPress scalar sanitizers.
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { return; }
        $raw_mode = $_POST['byl_bf2026_action'] ?? '';
        if ( ! is_string( $raw_mode ) ) { return; }
        $mode = sanitize_key( wp_unslash( $raw_mode ) );
        if ( ! in_array( $mode, array( 'select', 'remove' ), true )
            || ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
        $raw_nonce = $_POST['byl_bf2026_nonce'] ?? '';
        if ( ! is_string( $raw_nonce ) ) {
            wp_die( 'Invalid gift selection nonce', '', array( 'response' => 403 ) );
        }
        $token = sanitize_text_field( wp_unslash( $raw_nonce ) );
        if ( ! wp_verify_nonce( $token, 'byl_bf2026_gifts' ) ) {
            wp_die( 'Invalid gift selection nonce', '', array( 'response' => 403 ) );
        }
        if ( 'remove' === $mode ) {
            $raw_key = $_POST['gift_cart_key'] ?? '';
            if ( ! is_string( $raw_key ) ) {
                wp_die( 'Invalid gift cart key', '', array( 'response' => 422 ) );
            }
            $key = sanitize_text_field( wp_unslash( $raw_key ) );
            $line = WC()->cart->get_cart_item( $key );
            if ( is_array( $line ) && self::is_gift( $line ) ) {
                WC()->cart->remove_cart_item( $key );
            }
            WC()->cart->calculate_totals();
            wp_safe_redirect( wp_get_referer() ?: wc_get_cart_url() );
            exit;
        }
        $raw_id = $_POST['gift_product_id'] ?? '';
        if ( ! is_scalar( $raw_id ) ) {
            wp_die( 'Invalid gift product ID', '', array( 'response' => 422 ) );
        }
        $id = absint( $raw_id );
        $catalog = self::catalog();
        WC()->cart->calculate_totals();
        $slots = self::eligibility( WC()->cart )['slots'];
        $selected = 0;
        foreach ( WC()->cart->get_cart() as $line ) {
            if ( ! self::is_gift( $line ) ) { continue; }
            if ( (int) ( $line['product_id'] ?? 0 ) === $id ) { $slots = 0; }
            ++$selected;
        }
        $product = isset( $catalog[ $id ] ) ? self::gift_product( $id ) : false;
        if ( $slots <= $selected || ! $product ) {
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
        // Rendering a draft or preview must not activate promotional hooks or
        // mutate the cart when the approved Campaign runtime is OFF.
        $state = class_exists( 'Andy_Campaign_Lite' )
            ? ( Andy_Campaign_Lite::boot()->decisions()[ self::ID ] ?? 'unregistered' )
            : 'unregistered';
        if ( 'eligible' !== $state ) {
            return '<section class="byl-bf2026-gifts"><h2>Choose your Black Friday gift</h2>'
                . '<p>Gift selection is not currently available.</p></section>';
        }
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
                $p = self::gift_product( (int) $id );
                if ( ! $p ) { continue; }
                echo '<form method="post">';
                wp_nonce_field( 'byl_bf2026_gifts', 'byl_bf2026_nonce' );
                echo '<input type="hidden" name="byl_bf2026_action" value="select">';
                echo '<input type="hidden" name="gift_product_id" value="' . esc_attr( (string) $id ) . '">';
                $local = self::local_upgrade_price( (int) $upgrade );
                if ( null === $local ) { continue; }
                $symbol = function_exists( 'get_woocommerce_currency_symbol' )
                    ? get_woocommerce_currency_symbol( get_woocommerce_currency() ) : '';
                $label = $upgrade > 0 && null !== $local
                    ? ' · Upgrade +' . $symbol . $local : ' · FREE';
                echo '<span>' . esc_html( $p->get_name() . $label ) . '</span> ';
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
