<?php
/**
 * Andy Commerce integrated Observation Mode.
 *
 * Never loads full Commerce or registers WooCommerce transaction hooks while
 * observing. The only write permitted in this class is the Owner-initiated
 * mode setting change from authenticated wp-admin.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Observation_Mode {
    const OPTION = 'andy_commerce_runtime_mode_v1';
    const PAGE = 'andy-commerce-observation';
    const ACTION = 'andy_commerce_observation_toggle';
    const NONCE = 'andy_commerce_observation_mode_v1';

    /** Production and unknown sites fail closed. Preserve prior Dev runtime. */
    private static function is_existing_dev(): bool {
        if ( ! function_exists( 'wp_get_environment_type' ) ||
             ! function_exists( 'home_url' ) ) { return false; }
        $host = parse_url( home_url( '/' ), PHP_URL_HOST );
        if ( ! is_string( $host ) ) { return false; }
        $host = strtolower( $host );
        // www must always start observational, even if WP_ENVIRONMENT_TYPE
        // was incorrectly set to local/staging.
        if ( in_array( $host, array( 'www.beyourlover.com', 'beyourlover.com' ), true ) ) {
            return false;
        }
        $environment = wp_get_environment_type();
        // Preserve existing licensed Dev and disposable local Woo test runs.
        return ( 'dev.beyourlover.com' === $host && 'staging' === $environment )
            || in_array( $environment, array( 'local', 'development' ), true );
    }

    public static function is_observing(): bool {
        $saved = get_option( self::OPTION, null );
        if ( 'observe' === $saved ) { return true; }
        if ( 'active' === $saved ) { return false; }
        // Malformed options must not activate Commerce. No database migration.
        if ( null !== $saved && false !== $saved ) { return true; }
        return ! self::is_existing_dev();
    }

    /** Register only admin UI/POST. Frontend gets ZERO Observation hooks. */
    public static function register(): void {
        if ( ! function_exists( 'is_admin' ) || ! is_admin() ) { return; }
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 99 );
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_toggle' ) );
    }

    public static function register_menu(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( self::is_observing() ) {
            add_menu_page(
                'Andy Commerce — 观察模式', 'Andy Commerce', 'manage_options',
                'andy-commerce', array( __CLASS__, 'render_page' ),
                'dashicons-visibility', 57
            );
        }
        // Active runtime's existing main menu is registered at priority 35.
        add_submenu_page(
            'andy-commerce', '观察模式', '观察模式', 'manage_options',
            self::PAGE, array( __CLASS__, 'render_page' )
        );
    }

    /** Pure transition contract: stale forms or unacknowledged exits fail. */
    public static function valid_transition( $target, $expected, $current, $ack ): bool {
        if ( ! in_array( $target, array( 'observe', 'active' ), true ) ||
             ! in_array( $expected, array( 'observe', 'active' ), true ) ||
             $target === $current || $expected !== $current ) { return false; }
        return 'observe' === $target || 'YES_RESTORE_CUSTOMER_HOOKS' === $ack;
    }

    /** POST/nonce/capability only. No GET switch or AJAX toggle. */
    public static function handle_toggle(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Only a site administrator can change Commerce runtime mode.', '', array( 'response' => 403 ) );
        }
        check_admin_referer( self::NONCE, 'andy_commerce_observation_nonce' );

        $target = isset( $_POST['mode'] ) && is_string( $_POST['mode'] )
            ? wp_unslash( $_POST['mode'] ) : '';
        $expected = isset( $_POST['expected_mode'] ) && is_string( $_POST['expected_mode'] )
            ? wp_unslash( $_POST['expected_mode'] ) : '';
        $ack = isset( $_POST['runtime_ack'] ) && is_string( $_POST['runtime_ack'] )
            ? wp_unslash( $_POST['runtime_ack'] ) : '';
        $current = self::is_observing() ? 'observe' : 'active';

        if ( ! self::valid_transition( $target, $expected, $current, $ack ) ) {
            wp_die( 'Invalid or stale Commerce mode request. Reload the page.', '', array( 'response' => 409 ) );
        }
        if ( ! update_option( self::OPTION, $target, false ) ) {
            wp_die( 'Commerce mode was not saved; no mode change is confirmed.', '', array( 'response' => 500 ) );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&mode_updated=1' ) );
        exit;
    }

    /** Reusable one-action entry on the standard Commerce Overview. */
    public static function render_panel(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $observing = self::is_observing();
        $current = $observing ? 'observe' : 'active';
        $target = $observing ? 'active' : 'observe';
        echo '<section class="notice notice-info" style="padding:14px 18px;max-width:780px;margin:18px 0">';
        echo '<h2 style="margin-top:0">Andy Commerce · 观察模式</h2>';
        echo '<p><strong>' . esc_html( $observing ? '当前：观察模式（交易 Hook 不加载）' : '当前：运行模式（交易 Hook 可加载）' ) . '</strong></p>';
        echo '<p>' . esc_html( $observing
            ? '保留后台只读状态页；禁用 Commerce 自身的运费、优惠券、价格、购物车、订单、支付和营销执行模块。WooCommerce 与其他插件继续正常运行。'
            : '点击进入观察模式后，下一次请求将不再加载 Commerce 交易模块。当前业务配置不会被删除。' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"'
            . ( $observing
                ? ' onsubmit="return confirm(&quot;退出观察模式会恢复 Andy Commerce 的交易 Hook。请确认已单独完成正式站发布审批。&quot;)"'
                : '' ) . '>';
        echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
        echo '<input type="hidden" name="mode" value="' . esc_attr( $target ) . '">';
        echo '<input type="hidden" name="expected_mode" value="' . esc_attr( $current ) . '">';
        if ( $observing ) {
            echo '<input type="hidden" name="runtime_ack" value="YES_RESTORE_CUSTOMER_HOOKS">';
        }
        wp_nonce_field( self::NONCE, 'andy_commerce_observation_nonce' );
        echo '<button class="button ' . ( $observing ? 'button-secondary' : 'button-primary' ) . '" type="submit">'
            . esc_html( $observing ? '退出观察模式（恢复运行）' : '进入观察模式' ) . '</button>';
        echo '</form></section>';
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied.', '', array( 'response' => 403 ) );
        }
        echo '<div class="wrap"><h1>Andy Commerce · 安全模式</h1>';
        if ( isset( $_GET['mode_updated'] ) && '1' === $_GET['mode_updated'] ) {
            echo '<div class="notice notice-success"><p>已保存模式。请使用新的独立请求验证实际 Hook。</p></div>';
        }
        self::render_panel();
        echo '<h2>环境状态（只读）</h2><table class="widefat striped" style="max-width:780px"><tbody>';
        $fields = array(
            'Andy Commerce' => defined( 'ANDY_COMMERCE_VERSION' ) ? ANDY_COMMERCE_VERSION : 'Unknown',
            'WordPress' => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'Unknown',
            'PHP' => PHP_VERSION,
            'Andy Core' => defined( 'YBY_CORE_VERSION' ) ? YBY_CORE_VERSION : 'Not loaded',
            'WooCommerce' => defined( 'WC_VERSION' ) ? WC_VERSION : 'Not loaded',
            'WooCommerce base currency' => get_option( 'woocommerce_currency', 'Unknown' ),
            'HPOS configuration' => get_option( 'woocommerce_custom_orders_table_enabled', 'Unknown' ),
        );
        foreach ( $fields as $label => $value ) {
            echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
        }
        echo '</tbody></table><p class="description">观察模式不执行 Cart/Checkout、优惠券、运费、订单及支付逻辑；以上信息只是配置读回，不能代替真实功能验收。</p></div>';
    }
}
