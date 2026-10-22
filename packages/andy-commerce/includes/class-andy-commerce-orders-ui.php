<?php
/**
 * Andy Commerce Orders UI V1.2 — native-rendered, no post-paint DOM rearrangement.
 *
 * The native WooCommerce Orders screen, pagination, screen-option checkboxes,
 * controls and list-table are preserved. Preferences do not touch order data.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Orders_UI {
    const META_KEY = 'andy_commerce_orders_ui_v1'; // Backward compatible with Dev V1.0/1.1.
    const NONCE_ACTION = 'andy_commerce_orders_ui_v1';

    public static function boot() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
        add_filter( 'screen_settings', array( __CLASS__, 'screen_settings' ), 20, 2 );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'order_columns' ), PHP_INT_MAX );
        add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'order_columns' ), PHP_INT_MAX );
        add_action( 'wp_ajax_andy_commerce_orders_ui_save', array( __CLASS__, 'save' ) );
    }

    public static function on_orders_screen( $screen ) {
        return is_object( $screen ) && isset( $screen->id )
            && in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order' ), true );
    }

    private static function allowed() {
        return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
    }

    public static function sanitize_preferences( $input ) {
        if ( ! is_array( $input ) ) return false;
        $layout = isset( $input['layout'] ) && is_string( $input['layout'] ) ? $input['layout'] : '';
        if ( ! in_array( $layout, array( 'standard', 'compact', 'cards' ), true ) ) return false;
        $columns = isset( $input['columns'] ) ? $input['columns'] : array();
        if ( ! is_array( $columns ) || count( $columns ) > 40 ) return false;
        $ordered = array();
        foreach ( $columns as $raw ) {
            if ( ! is_string( $raw ) || strlen( $raw ) > 64 ) return false;
            $key = sanitize_key( $raw );
            if ( ! $key || $key !== $raw || 'cb' === $key ) return false;
            if ( ! in_array( $key, $ordered, true ) ) $ordered[] = $key;
        }
        return array( 'layout' => $layout, 'columns' => $ordered );
    }

    private static function preferences() {
        $raw = get_user_meta( get_current_user_id(), self::META_KEY, true );
        $value = self::sanitize_preferences( $raw );
        return false === $value ? array( 'layout' => 'standard', 'columns' => array() ) : $value;
    }

    public static function body_class( $classes ) {
        if ( ! self::allowed() || ! self::on_orders_screen( get_current_screen() ) ) return $classes;
        $layout = self::preferences()['layout'];
        return $classes . ' ac-orders-ui-v12 ac-orders-layout-' . $layout;
    }

    /**
     * WooCommerce uses separate column filters for HPOS and legacy screens.
     * Ordering associative headers before native rendering moves all TD cells
     * through the existing WP_List_Table, not through delayed browser JS.
     */
    public static function order_columns( $columns ) {
        if ( ! is_array( $columns ) || ! self::allowed() ) return $columns;
        $preferred = self::preferences()['columns'];
        if ( ! $preferred ) return $columns;
        $result = array();
        if ( array_key_exists( 'cb', $columns ) ) $result['cb'] = $columns['cb'];
        foreach ( $preferred as $key ) {
            if ( isset( $columns[ $key ] ) && ! in_array( $key, array( 'cb', 'wc_actions', 'order_actions' ), true ) ) {
                $result[ $key ] = $columns[ $key ];
            }
        }
        foreach ( $columns as $key => $label ) {
            if ( ! array_key_exists( $key, $result ) && ! in_array( $key, array( 'wc_actions', 'order_actions' ), true ) ) {
                $result[ $key ] = $label;
            }
        }
        foreach ( array( 'wc_actions', 'order_actions' ) as $key ) {
            if ( array_key_exists( $key, $columns ) ) $result[ $key ] = $columns[ $key ];
        }
        return $result;
    }

    public static function enqueue() {
        if ( ! self::allowed() || ! self::on_orders_screen( get_current_screen() ) ) return;
        $url = ANDY_COMMERCE_PLUGIN_URL;
        $version = ANDY_COMMERCE_VERSION;
        wp_enqueue_style( 'andy-commerce-orders-ui-v12', $url . 'assets/css/orders-ui-v1-2.css', array(), $version );
        wp_enqueue_script( 'andy-commerce-orders-ui-v12', $url . 'assets/js/orders-ui-v1-2.js', array(), $version, true );
    }

    /**
     * Output inside the existing WordPress #screen-options-wrap form.
     * Its own native column visibility & per-page controls remain unchanged.
     */
    public static function screen_settings( $settings, $screen ) {
        if ( ! self::allowed() || ! self::on_orders_screen( $screen ) ) return $settings;
        $prefs = self::preferences();
        $layout_labels = array( 'standard' => '标准', 'compact' => '紧凑', 'cards' => '卡片' );
        $html = '<section class="ac-orders-native-options" id="ac-orders-native-options"'
            . ' data-endpoint="' . esc_attr( admin_url( 'admin-ajax.php' ) ) . '"'
            . ' data-nonce="' . esc_attr( wp_create_nonce( self::NONCE_ACTION ) ) . '">';
        $html .= '<fieldset><legend>订单展示方式</legend><div class="ac-orders-mode-choices">';
        foreach ( $layout_labels as $key => $label ) {
            $html .= '<label><input type="radio" name="ac_orders_layout" value="' . esc_attr( $key ) . '"'
                . ( $prefs['layout'] === $key ? ' checked="checked"' : '' ) . '>'
                . esc_html( $label ) . '</label>';
        }
        $html .= '</div></fieldset>';

        $headers = function_exists( 'get_column_headers' ) ? get_column_headers( $screen ) : array();
        $ordered = self::order_columns( is_array( $headers ) ? $headers : array() );
        $html .= '<fieldset><legend>订单列排序</legend><p class="description">勾选显示列请使用上方 WordPress 原生 Columns；下方用 ↑ ↓ 调整列顺序。</p>';
        $html .= '<ol class="ac-orders-column-sort">';
        foreach ( $ordered as $key => $label ) {
            if ( in_array( $key, array( 'cb', 'wc_actions', 'order_actions' ), true ) ) continue;
            $html .= '<li data-column="' . esc_attr( $key ) . '"><span class="ac-orders-sort-label">'
                . esc_html( wp_strip_all_tags( $label ) ) . '</span><button class="button button-small" type="button" data-move="-1" aria-label="上移">↑</button>'
                . '<button class="button button-small" type="button" data-move="1" aria-label="下移">↓</button></li>';
        }
        $html .= '</ol></fieldset>';
        $html .= '<div class="ac-orders-save-row"><button type="button" class="button button-primary ac-orders-save">保存布局并刷新</button>'
            . '<span class="ac-orders-result" role="status" aria-live="polite"></span></div></section>';
        return $settings . $html;
    }

    public static function save() {
        if ( ! self::allowed() ) wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
        if ( ! self::on_orders_screen( get_current_screen() ) ) {
            // Ajax has no current Orders screen: authenticated users are still gated by capability and nonce.
            // No order data write is ever allowed by this action.
        }
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        $input = array(
            'layout' => isset( $_POST['layout'] ) && is_string( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : '',
            'columns' => isset( $_POST['columns'] ) && is_array( $_POST['columns'] ) ? wp_unslash( $_POST['columns'] ) : array(),
        );
        $prefs = self::sanitize_preferences( $input );
        if ( false === $prefs ) wp_send_json_error( array( 'message' => 'invalid preferences' ), 400 );
        update_user_meta( get_current_user_id(), self::META_KEY, $prefs );
        wp_send_json_success( array( 'preferences' => $prefs ) );
    }
}
