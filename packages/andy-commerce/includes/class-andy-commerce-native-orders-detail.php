<?php
/**
 * Andy Commerce Native Orders Detail UI — source ported from Owner-PASS
 * BeYourLover Native Orders Shopify Skin Dev UAT v0.4.9.
 * All modifications scoped to the real WooCommerce HPOS admin editor.
 * No order, payment, shipping or invoice data is modified at bootstrap.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Native_Order_Detail_Skin {
    const VERSION = '1.0.14-native-detail-v1';
    const NONCE = 'andy_commerce_native_orders_pin_v1';
    const META = 'byl_native_order_pinned_boxes_v1';
    // Restrict writes to known order-screen metabox identifiers. Unknown plugin boxes retain native WP UI.
    const PIN_IDS = array(
        'woocommerce-order-data', 'woocommerce-order-items', 'woocommerce-order-notes',
        'woocommerce-order-actions', 'woocommerce-order-downloads',
        'woocommerce-order-attribution', 'order_attribution', 'byl-native-customer',
        'aftership', 'aftership_tracking', 'aftership-shipment-tracking',
        'affiliate-wp', 'affiliatewp', 'order_custom', 'woocommerce-order-data-customer'
    );
    private static $is_screen = null;
    private static $order_id = 0;
    private static $customer_box_registered = false;
    private static $notes_box_moved = false;
    private static $custom_fields_box_moved = false;

    public static function boot() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ), 40 );
        add_action( 'woocommerce_admin_order_data_header_right', array( __CLASS__, 'render_action_bar' ), 50 );
        add_action( 'woocommerce_admin_order_data_after_payment_info', array( __CLASS__, 'render_advanced_control' ), 100 );
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_boxes' ), 999 );
        add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( __CLASS__, 'register_boxes' ), 999 );
        // Only adjust the in-memory screen arrangement; never rewrite a user's saved WP screen options.
        add_action( 'woocommerce_admin_order_totals_after_total', array( __CLASS__, 'payment_method_row' ) );
        add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
        add_filter( 'postbox_classes_woocommerce_page_wc-orders_woocommerce-order-attribution', array( __CLASS__, 'attribution_default_classes' ) );
        add_filter( 'postbox_classes_woocommerce_page_wc-orders_order_attribution', array( __CLASS__, 'attribution_default_classes' ) );
        add_filter( 'postbox_classes_woocommerce_page_wc-orders_order_custom', array( __CLASS__, 'custom_field_default_classes' ) );
        add_filter( 'postbox_classes_woocommerce_page_wc-orders_woocommerce-order-notes', array( __CLASS__, 'timeline_default_classes' ) );
        add_action( 'wp_ajax_andy_commerce_native_orders_save_pin', array( __CLASS__, 'save_pin' ) );
    }

    private static function permitted() {
        return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
    }

    private static function active_edit_screen() {
        if ( null !== self::$is_screen ) { return self::$is_screen; }
        self::$is_screen = false;
        if ( ! is_admin() || ! self::permitted() || ! function_exists( 'get_current_screen' ) ) return false;
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'shop_order' ), true ) ) return false;
        if ( 'woocommerce_page_wc-orders' === $screen->id ) {
            $action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
            if ( 'edit' !== $action ) return false;
            self::$order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        } else {
            // Legacy Woo order editing is not modified in this initial HPOS-only pass.
            return false;
        }
        if ( ! self::$order_id || ! current_user_can( 'edit_shop_order', self::$order_id ) && ! self::permitted() ) return false;
        self::$is_screen = true;
        return true;
    }

    private static function pins() {
        $raw = get_user_meta( get_current_user_id(), self::META, true );
        if ( ! is_array( $raw ) ) return array();
        return array_values( array_intersect( self::PIN_IDS, array_filter( $raw, 'is_string' ) ) );
    }

    public static function enqueue() {
        if ( ! self::active_edit_screen() ) return;
        $url = ANDY_COMMERCE_PLUGIN_URL . 'assets/native-orders/';
        wp_enqueue_style( 'andy-commerce-native-order-detail', $url . 'native-orders.css', array(), self::VERSION );
        wp_enqueue_script( 'andy-commerce-native-order-detail', $url . 'native-orders.js', array( 'jquery', 'postbox' ), self::VERSION, true );
        $coupon_links = array();
        if ( function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( self::$order_id );
            if ( $order && is_a( $order, 'WC_Order' ) ) {
                foreach ( array_slice( $order->get_coupon_codes(), 0, 10 ) as $code ) {
                    $id = function_exists( 'wc_get_coupon_id_by_code' ) ? absint( wc_get_coupon_id_by_code( $code ) ) : 0;
                    $coupon_links[] = array( 'code' => (string) $code, 'url' => $id && current_user_can( 'edit_post', $id )
                        ? admin_url( 'post.php?post=' . $id . '&action=edit' ) : '' );
                }
            }
        }
        wp_localize_script( 'andy-commerce-native-order-detail', 'BYLNativeOrders', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( self::NONCE ),
            'pins' => self::pins(),
            'pinIds' => self::PIN_IDS,
            'orderId' => self::$order_id,
            'coupons' => $coupon_links,
            'labels' => array('copy' => 'Copy address', 'copied' => 'Copied!', 'failed' => 'Unable to copy', 'pin' => '固定模块', 'unpin' => '取消固定')
        ) );
        // Opening pinned native boxes is declarative on first paint. JS syncs native WP toggle attributes later.
        $pinned_css = '';
        foreach ( self::pins() as $box_id ) {
            $pinned_css .= 'body.byl-native-orders-shopify-v1 #' . sanitize_html_class( $box_id )
                . '.closed > .inside{display:block!important;}';
        }
        if ( $pinned_css ) wp_add_inline_style( 'andy-commerce-native-order-detail', $pinned_css );
    }

    public static function body_class( $class ) {
        if ( ! self::active_edit_screen() ) return $class;
        return $class . ' byl-native-orders-shopify-v1 byl-native-orders-shopify-v2';
    }

    /** Chronological WC_Order neighbours, not guessed integer IDs. No filtered-list claim. */
    private static function neighbor_url( $order, $direction ) {
        if ( ! function_exists( 'wc_get_orders' ) || ! $order || ! is_a( $order, 'WC_Order' ) ) return '';
        $created = $order->get_date_created();
        if ( ! $created ) return '';
        $before = 'previous' === $direction;
        // WC_Order_Query timestamp comparison, returns at most 8 IDs, including custom order statuses.
        $ids = wc_get_orders( array(
            'type' => 'shop_order',
            'limit' => 8,
            'return' => 'ids',
            'orderby' => 'date',
            'order' => $before ? 'DESC' : 'ASC',
            'date_created' => ( $before ? '<' : '>' ) . $created->getTimestamp(),
        ) );
        if ( ! is_array( $ids ) ) return '';
        foreach ( $ids as $candidate ) {
            $candidate = absint( $candidate );
            if ( ! $candidate || $candidate === self::$order_id ) continue;
            if ( current_user_can( 'edit_shop_order', $candidate ) || self::permitted() ) {
                return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $candidate );
            }
        }
        return '';
    }

    /** Initial WC postbox classes: keep native callbacks/forms untouched. */
    public static function custom_field_default_classes( $classes ) {
        if ( ! self::active_edit_screen() ) return $classes;
        if ( in_array( 'order_custom', self::pins(), true ) ) return array_values( array_diff( $classes, array( 'closed' ) ) );
        $classes[] = 'closed';
        return array_values( array_unique( $classes ) );
    }

    /** Native Timeline keeps its original metabox, callbacks and notes; only its first-paint state changes. */
    public static function timeline_default_classes( $classes ) {
        if ( ! self::active_edit_screen() ) return $classes;
        if ( in_array( 'woocommerce-order-notes', self::pins(), true ) ) {
            return array_values( array_diff( $classes, array( 'closed' ) ) );
        }
        $classes[] = 'closed';
        return array_values( array_unique( $classes ) );
    }

    public static function attribution_default_classes( $classes ) {
        if ( ! self::active_edit_screen() ) return $classes;
        return array_values( array_diff( $classes, array( 'closed' ) ) );
    }

    public static function render_action_bar( $order = null ) {
        if ( ! self::active_edit_screen() ) return;
        $url = '';
        if ( function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( self::$order_id );
            if ( $order && is_a( $order, 'WC_Order' ) ) {
                $url = $order->get_view_order_url();
            }
        }
        $status_map = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
        $current_status = $order && is_a( $order, 'WC_Order' ) ? 'wc-' . $order->get_status() : '';
        $current_status_label = isset( $status_map[ $current_status ] ) ? $status_map[ $current_status ] : 'Change status';
        $primary_label = 'processing' === ( $order ? $order->get_status() : '' ) ? 'Mark as Completed' : $current_status_label;
        $primary_status = 'processing' === ( $order ? $order->get_status() : '' ) && isset( $status_map['wc-completed'] ) ? 'completed' : '';
        echo '<div class="byl-native-toolbar" role="toolbar" aria-label="Order tools" data-order-id="' . esc_attr( self::$order_id ) . '">';
        echo '<div class="byl-native-crumb"><a href="' . esc_url( admin_url( 'admin.php?page=wc-orders' ) ) . '">Orders</a><span>/</span><strong>#' . esc_html( self::$order_id ) . '</strong></div>';
        echo '<div class="byl-native-actions">';
        echo '<div class="byl-native-status-wrap">';
        echo '<div class="byl-native-status-split">';
        echo '<button type="button" class="byl-native-pill byl-native-pill-primary byl-native-status-primary" data-byl-action="status-primary" data-primary-status="' . esc_attr( $primary_status ) . '" title="通过 WooCommerce 原生 Update 将订单状态设置为 Completed">' . esc_html( $primary_label ) . '</button>';
        echo '<button type="button" class="byl-native-pill byl-native-pill-primary byl-native-status-chevron" data-byl-action="status" aria-expanded="false" aria-controls="byl-native-status-menu" aria-label="选择订单状态">⌄</button>';
        echo '</div>';
        echo '<div id="byl-native-status-menu" class="byl-native-more-menu byl-native-status-menu" hidden role="menu">';
        foreach ( $status_map as $status_key => $status_label ) {
            echo '<button type="button" role="menuitem" data-byl-action="set-status" data-status="' . esc_attr( str_replace( 'wc-', '', (string) $status_key ) ) . '">' . esc_html( $status_label ) . '</button>';
        }
        echo '<div class="byl-native-status-help" id="byl-native-status-help" role="status">选择状态不会立即保存。</div>';
        echo '<button type="button" class="byl-native-status-save" data-byl-action="save-status" hidden>Save status</button>';
        echo '</div></div>';
        echo '<button type="button" class="byl-native-pill" data-byl-action="edit">Edit</button>';
        echo '<div class="byl-native-more-wrap">';
        echo '<button type="button" class="byl-native-pill" data-byl-action="more" aria-expanded="false" aria-controls="byl-native-more-menu">More actions <span aria-hidden="true">⌄</span></button>';
        echo '<div id="byl-native-more-menu" class="byl-native-more-menu" hidden role="menu">';
        echo '<button type="button" role="menuitem" data-byl-action="refund">↺ &nbsp; Refund</button>';
        echo '<button type="button" role="menuitem" disabled title="需要已安装且验证的订单复制功能">♧ &nbsp; Duplicate</button>';
        echo '<button type="button" role="menuitem" data-byl-action="cancel">× &nbsp; Cancel order</button>';
        echo '<button type="button" role="menuitem" disabled title="WooCommerce 无原生 Archive 操作">▤ &nbsp; Archive</button>';
        if ( $url ) {
            echo '<a role="menuitem" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">◉ &nbsp; View order status page</a>';
        } else {
            echo '<button type="button" role="menuitem" disabled>◉ &nbsp; View order status page</button>';
        }
        echo '<div class="byl-native-menu-section">Print</div>';
        echo '<button type="button" role="menuitem" data-byl-action="print">▣ &nbsp; Print order page</button>';
        echo '<button type="button" role="menuitem" disabled title="尚未检测到可验证的装箱单打印接口">▣ &nbsp; Print packing slips</button>';
        echo '<div class="byl-native-menu-section">Apps</div>';
        echo '<button type="button" role="menuitem" data-byl-action="metafields">▧ &nbsp; Edit Metafields</button>';
        $previous_url = self::neighbor_url( $order, 'previous' );
        $next_url = self::neighbor_url( $order, 'next' );
        echo '</div></div><div class="byl-native-navigate" aria-label="Order navigation">';
        if ( $previous_url ) echo '<a class="byl-native-order-neighbor" href="' . esc_url( $previous_url ) . '" aria-label="上一笔订单" title="较早一笔订单">⌃</a>';
        else echo '<span class="byl-native-order-neighbor is-disabled" aria-label="没有更早的订单" title="没有更早的订单">⌃</span>';
        if ( $next_url ) echo '<a class="byl-native-order-neighbor" href="' . esc_url( $next_url ) . '" aria-label="下一笔订单" title="较新一笔订单">⌄</a>';
        else echo '<span class="byl-native-order-neighbor is-disabled" aria-label="没有更新的订单" title="没有更新的订单">⌄</span>';
        echo '</div></div></div>'; // close navigate, actions, AND toolbar; critical to keep metaboxes as siblings.
    }

    /** WooCommerce renders this hook after gateway notices and before its native edit columns. */
    public static function render_advanced_control( $order = null ) {
        if ( ! self::active_edit_screen() ) return;
        // Native checkbox / label works without JS. Original WC form fields stay intact.
        echo '<div class="byl-native-advanced-host"><input type="checkbox" id="byl-native-advanced-check" class="byl-native-advanced-check" aria-controls="order_data" />';
        echo '<label class="byl-native-advanced-btn" for="byl-native-advanced-check">Advanced editing <span aria-hidden="true" class="byl-native-advanced-arrow">⌄</span></label></div>';
    }

    public static function payment_method_row( $order_id ) {
        if ( ! self::active_edit_screen() || absint( $order_id ) !== self::$order_id ) return;
        $order = function_exists( 'wc_get_order' ) ? wc_get_order( self::$order_id ) : null;
        if ( $order && is_a( $order, 'WC_Order' ) && $order->get_payment_method_title() ) {
            echo '<tr class="byl-native-payment-method"><td class="label">Payment method:</td><td></td><td class="total">' . esc_html( $order->get_payment_method_title() ) . '</td></tr>';
        }
    }

    /** Real Woo HPOS Custom Fields metabox is `order_custom`, NOT WordPress `postcustom`.
     * Native callback and action buttons remain intact. Do not force screen-order preferences:
     * dragging between the native sortable containers must keep working.
     */
    private static function move_custom_fields_box( $screen ) {
        if ( self::$custom_fields_box_moved ) return;
        global $wp_meta_boxes;
        if ( empty( $wp_meta_boxes[ $screen ] ) ) return;
        $existing = null;
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
                $box = $wp_meta_boxes[ $screen ][ $context ][ $priority ]['order_custom'] ?? null;
                if ( is_array( $box ) && ! empty( $box['callback'] ) && is_callable( $box['callback'] ) ) {
                    $existing = $box;
                    break 2;
                }
            }
        }
        if ( ! $existing ) return; // fail open; do not fabricate a form.
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            remove_meta_box( 'order_custom', $screen, $context );
        }
        add_meta_box(
            'order_custom',
            isset( $existing['title'] ) ? $existing['title'] : 'Custom Fields',
            $existing['callback'], $screen, 'side', 'low',
            isset( $existing['args'] ) ? $existing['args'] : null
        );
        self::$custom_fields_box_moved = true;
    }

    /** Native first-paint cards. Only the presentation is added; WooCommerce stays the editor. */
    public static function register_boxes() {
        if ( ! self::active_edit_screen() || ! function_exists( 'add_meta_box' ) ) return;
        $screen = 'woocommerce_page_wc-orders';
        if ( ! self::$customer_box_registered ) {
            add_meta_box( 'byl-native-customer', 'Customer', array( __CLASS__, 'customer_card' ), $screen, 'side', 'high' );
            self::$customer_box_registered = true;
        }
        self::move_custom_fields_box( $screen );
        if ( self::$notes_box_moved ) return;
        // Re-register the actual native notes callback in the main column, not a second notes editor.
        global $wp_meta_boxes;
        $callback = null;
        $args = null;
        foreach ( array( 'side', 'normal', 'advanced' ) as $context ) {
            foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
                $box = $wp_meta_boxes[$screen][$context][$priority]['woocommerce-order-notes'] ?? null;
                if ( $box && isset( $box['callback'] ) && is_callable( $box['callback'] ) ) {
                    $callback = $box['callback'];
                    $args = isset( $box['args'] ) ? $box['args'] : null;
                    break 2;
                }
            }
        }
        if ( $callback ) {
            foreach ( array( 'side', 'normal', 'advanced' ) as $context ) {
                remove_meta_box( 'woocommerce-order-notes', $screen, $context );
            }
            self::$notes_callback = $callback;
            add_meta_box( 'woocommerce-order-notes', 'Timeline', array( __CLASS__, 'timeline_card' ), $screen, 'normal', 'low', $args );
            self::$notes_box_moved = true;
        }
    }

    private static $notes_callback = null;

    public static function timeline_card( $post_or_order, $box = array() ) {
        $order = function_exists( 'wc_get_order' ) ? wc_get_order( self::$order_id ) : null;
        if ( $order && is_a( $order, 'WC_Order' ) ) {
            echo '<div class="byl-native-timeline-events">';
            $created = $order->get_date_created();
            $paid = $order->get_date_paid();
            if ( $paid ) {
                echo '<div class="byl-native-event"><strong>Payment received</strong><span>' . esc_html( wc_format_datetime( $paid ) ) . '</span></div>';
            }
            if ( $created ) {
                echo '<div class="byl-native-event"><strong>Order created</strong><span>' . esc_html( wc_format_datetime( $created ) ) . '</span></div>';
            }
            echo '</div>';
        }
        if ( is_callable( self::$notes_callback ) ) {
            call_user_func( self::$notes_callback, $post_or_order, $box );
        }
        // Use CSS :has() for a progressive, no-JS 'all activity' toggle.
        // This only limits visual rows; every WC note remains in the original native markup.
        echo '<div class="byl-native-activity-control"><input type="checkbox" id="byl-native-activity-expanded" class="byl-native-activity-checkbox" aria-controls="woocommerce-order-notes" />';
        echo '<label for="byl-native-activity-expanded"><span class="byl-native-activity-more">View all activity</span><span class="byl-native-activity-less">Show recent activity</span></label></div>';
    }

    public static function customer_card( $post_or_order = null ) {
        $order = function_exists( 'wc_get_order' ) ? wc_get_order( self::$order_id ) : null;
        if ( ! $order || ! is_a( $order, 'WC_Order' ) ) return;
        $shipping_name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
        $billing_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $shipping_name = $shipping_name ?: $billing_name;
        $customer_id = absint( $order->get_customer_id() );
        $customer = $customer_id ? get_userdata( $customer_id ) : false;
        echo '<div class="byl-native-customer-body">';
        echo '<div class="byl-native-customer-row"><strong>' . esc_html( $shipping_name ?: 'Guest' ) . '</strong>';
        if ( $customer && current_user_can( 'edit_user', $customer_id ) ) {
            echo '<div class="byl-native-links"><a href="' . esc_url( get_edit_user_link( $customer_id ) ) . '">Profile ↗</a></div>';
        }
        $email = $order->get_billing_email();
        if ( $email ) echo '<div><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>';
        echo '<button class="byl-native-textbtn" type="button" data-byl-native-edit="customer">Edit customer</button>';
        echo '</div>';
        echo '<div class="byl-native-customer-row byl-native-shipcol"><div class="byl-native-rowhead"><strong>Shipping address</strong><button type="button" class="byl-native-copy" aria-label="Copy address" title="Copy address"><span aria-hidden="true">▢</span><span class="byl-native-tooltip">Copy address</span></button></div>';
        echo '<div class="byl-native-address-lines">';
        $parts = array( $shipping_name, $order->get_shipping_company(), $order->get_shipping_address_1(), $order->get_shipping_address_2() );
        $cityline = trim( implode( ' ', array_filter( array( $order->get_shipping_city(), $order->get_shipping_state(), $order->get_shipping_postcode() ) ) ) );
        $parts[] = $cityline; $parts[] = $order->get_shipping_country(); $parts[] = $order->get_shipping_phone();
        foreach ( $parts as $line ) if ( trim( (string) $line ) ) echo '<div class="byl-native-address-line">' . esc_html( $line ) . '</div>';
        echo '</div><button type="button" class="byl-native-textbtn" data-byl-native-edit="shipping">Edit shipping</button></div>';
        echo '<div class="byl-native-customer-row byl-native-billing-row"><details><summary>Billing address</summary><div class="byl-native-billing-body">';
        $billing_parts = array( $billing_name, $order->get_billing_company(), $order->get_billing_address_1(), $order->get_billing_address_2(), trim( implode( ' ', array_filter( array( $order->get_billing_city(), $order->get_billing_state(), $order->get_billing_postcode() ) ) ) ), $order->get_billing_country(), $order->get_billing_phone() );
        foreach ( $billing_parts as $line ) if ( trim( (string) $line ) ) echo '<div>' . esc_html( $line ) . '</div>';
        echo '<button type="button" class="byl-native-textbtn" data-byl-native-edit="billing">Edit billing</button></div></details></div>';
        echo '</div>';
    }

    public static function save_pin() {
        if ( ! self::permitted() ) wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
        check_ajax_referer( self::NONCE, 'nonce' );
        $id = isset( $_POST['box'] ) && is_string( $_POST['box'] ) ? sanitize_key( wp_unslash( $_POST['box'] ) ) : '';
        $mode = isset( $_POST['pin'] ) && is_string( $_POST['pin'] ) ? sanitize_key( wp_unslash( $_POST['pin'] ) ) : '';
        if ( ! in_array( $id, self::PIN_IDS, true ) || ! in_array( $mode, array( '0', '1' ), true ) ) {
            wp_send_json_error( array( 'message' => 'invalid_preferences' ), 400 );
        }
        $current = self::pins();
        if ( '1' === $mode ) { $current[] = $id; }
        else { $current = array_diff( $current, array( $id ) ); }
        $current = array_values( array_intersect( self::PIN_IDS, array_unique( $current ) ) );
        update_user_meta( get_current_user_id(), self::META, $current );
        wp_send_json_success( array( 'box' => $id, 'pinned' => '1' === $mode ) );
    }
}