<?php
/**
 * Andy Core Module Registry integration for the two Andy Commerce Orders features.
 * The same canonical option/state is used by Core and Commerce: no parallel toggles.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Andy_Commerce_Orders_Features {
    const LIST_MODULE = 'andy_commerce_orders_list_ui';
    const DETAIL_MODULE = 'andy_commerce_order_detail_label';
    const NONCE = 'andy_commerce_orders_modules_save';

    public static function register() {
        if ( ! class_exists( 'YBY_Module_Registry' ) ) return;
        $common = array(
            'version' => '1.0.0',
            'schema_version' => '1',
            'status' => 'ready',
            'default_enabled' => false, // Opt-in on each site, never silently affect WWW.
            'capability' => 'manage_woocommerce',
            'availability' => array( __CLASS__, 'available' ),
            'availability_message' => 'Requires WooCommerce HPOS Orders screen.',
            'settings' => array( 'page' => 'andy-commerce', 'tab' => 'overview' ),
            'dependencies' => array( 'core_runtime', 'module_registry' ),
        );
        YBY_Module_Registry::register( self::LIST_MODULE, array_merge( $common, array(
            'name' => 'Andy Commerce — Orders List UI',
            'description' => '原生订单列表：标准 / 紧凑 / 卡片、列管理、折扣码列及 ID + 商品 SKU。',
            'boot' => array( __CLASS__, 'boot_list' ),
        ) ) );
        YBY_Module_Registry::register( self::DETAIL_MODULE, array_merge( $common, array(
            'name' => 'Andy Commerce — Order Detail UI',
            'description' => 'Shopify 风格原生订单详情界面（双栏、顾客信息、付款与进度、原生操作）；标题「订单 39936」。',
            'boot' => array( __CLASS__, 'boot_detail' ),
        ) ) );
    }

    public static function available( $module = array() ) {
        return class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' );
    }

    public static function boot_list( $module = array() ) {
        require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-orders-ui.php';
        require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-order-labels.php';
        require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-order-coupon-column.php';
        Andy_Commerce_Order_Coupon_Column::boot();
        Andy_Commerce_Orders_UI::boot();
        Andy_Commerce_Order_Labels::boot_list();
    }

    public static function boot_detail( $module = array() ) {
        require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-order-labels.php';
        require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-native-orders-detail.php';
        Andy_Commerce_Order_Labels::boot_detail();
        Andy_Commerce_Native_Order_Detail_Skin::boot();
    }

    public static function statuses() {
        if ( ! class_exists( 'YBY_Module_Registry' ) ) return array( self::LIST_MODULE => false, self::DETAIL_MODULE => false );
        return array(
            self::LIST_MODULE => YBY_Module_Registry::is_enabled( self::LIST_MODULE ),
            self::DETAIL_MODULE => YBY_Module_Registry::is_enabled( self::DETAIL_MODULE ),
        );
    }

    public static function can_manage() {
        return current_user_can( 'manage_woocommerce' )
            && ( current_user_can( 'andy_core_settings_manage' ) || current_user_can( 'manage_options' ) );
    }

    /** Update only these two IDs in Andy Core's existing module registry. */
    public static function save() {
        if ( ! self::can_manage() || ! class_exists( 'YBY_Module_Registry' ) ) {
            wp_die( 'Insufficient permissions.', '', array( 'response' => 403 ) );
        }
        check_admin_referer( self::NONCE, 'andy_commerce_orders_modules_nonce' );
        $raw = isset( $_POST['andy_commerce_orders_modules'] ) && is_array( $_POST['andy_commerce_orders_modules'] )
            ? wp_unslash( $_POST['andy_commerce_orders_modules'] )
            : array();
        $requested = array_values( array_intersect(
            array_filter( array_map( 'sanitize_key', $raw ) ),
            array( self::LIST_MODULE, self::DETAIL_MODULE )
        ) );
        $other = array_values( array_diff(
            YBY_Module_Registry::enabled_modules(),
            array( self::LIST_MODULE, self::DETAIL_MODULE )
        ) );
        YBY_Module_Registry::save( array_merge( $other, $requested ) );
        wp_safe_redirect( add_query_arg(
            array( 'page' => 'andy-commerce', 'tab' => 'overview', 'orders_saved' => '1' ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }
}
