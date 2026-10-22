<?php
/**
 * Andy Commerce: HPOS native Order labels (read-only, owner-approved).
 *
 * Details: "订单 39936". Orders table: "39936 SKU1 SKU2".
 * Does not change order IDs, customer data, WooCommerce API, emails or invoices.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Order_Labels {
    /** Kept as a compatibility entry point for existing unit harnesses. */
    public static function boot() {
        self::boot_list();
        self::boot_detail();
    }

    public static function boot_list() {
        add_filter( 'woocommerce_admin_order_buyer_name', array( __CLASS__, 'list_skus' ), PHP_INT_MAX, 2 );
        add_filter( 'woocommerce_order_number', array( __CLASS__, 'native_numeric_number' ), PHP_INT_MAX, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'list_script' ), 45 );
    }

    public static function boot_detail() {
        add_filter( 'woocommerce_order_number', array( __CLASS__, 'native_numeric_number' ), PHP_INT_MAX, 2 );
        add_filter( 'gettext', array( __CLASS__, 'detail_heading_format' ), PHP_INT_MAX, 3 );
    }

    /**
     * Only the native WooCommerce HPOS editor/list, for authorized staff.
     * Filter callbacks may run long after plugins_loaded when WP screen is ready.
     */
    private static function context() {
        if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) return '';
        if ( ! ( current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' ) ) ) return '';
        $screen = get_current_screen();
        if ( ! is_object( $screen ) || ! isset( $screen->id ) || 'woocommerce_page_wc-orders' !== $screen->id ) return '';
        $page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'wc-orders' !== $page ) return '';
        $action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        if ( '' === $action ) return 'list';
        if ( 'edit' === $action && ! empty( $_GET['id'] ) && absint( $_GET['id'] ) > 0 ) return 'edit';
        return '';
    }

    /** Woo's native list column prints a link with "#NUMBER BUYER".
     * Replace BUYER *display only* with SKUs, in actual line-item order.
     */
    public static function list_skus( $buyer, $order ) {
        if ( 'list' !== self::context() || ! $order instanceof WC_Order ) return $buyer;
        $skus = array();
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! is_object( $item ) || ! method_exists( $item, 'get_product' ) ) continue;
            $product = $item->get_product();
            if ( ! $product || ! method_exists( $product, 'get_sku' ) ) continue;
            $sku = trim( (string) $product->get_sku() );
            if ( '' !== $sku ) $skus[] = $sku;
        }
        return implode( ' ', $skus );
    }

    /** Keep numeric ID on HPOS staff pages even if a theme customizes the public order number. */
    public static function native_numeric_number( $number, $order ) {
        if ( ! self::context() || ! $order instanceof WC_Order ) return $number;
        return (string) $order->get_id();
    }

    /**
     * WooCommerce prints its native detail heading using:
     * sprintf( esc_html__( '%1$s #%2$s', 'woocommerce' ), $type, $number )
     * Change only this one string on the HPOS order edit screen; first paint,
     * preserving the actual native editor rather than rewriting it with JS.
     */
    public static function detail_heading_format( $translation, $text, $domain ) {
        if ( 'edit' === self::context() && 'woocommerce' === $domain && '%1$s #%2$s' === $text ) {
            return '订单 %2$s';
        }
        return $translation;
    }

    /** WooCommerce's literal decorative "#" is not filterable on its list row. */
    public static function list_script() {
        if ( 'list' !== self::context() ) return;
        wp_enqueue_script(
            'andy-commerce-order-labels-v1',
            ANDY_COMMERCE_PLUGIN_URL . 'assets/js/orders-list-labels-v1.js',
            array(),
            ANDY_COMMERCE_VERSION,
            true
        );
    }
}
