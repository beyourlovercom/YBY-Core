<?php
/**
 * Read-only observation diagnostics, current wp-admin request only.
 * No cart, order, payment, shipping, HTTP or scheduled executions.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Observation_Diagnostics {
    const HOOKS = array(
        'woocommerce_shipping_free_shipping_is_available',
        'woocommerce_shipping_chosen_method',
        'woocommerce_get_shop_coupon_data',
        'woocommerce_coupon_get_individual_use',
        'woocommerce_apply_with_individual_use_coupon',
        'woocommerce_cart_loaded_from_session',
        'woocommerce_before_calculate_totals',
        'woocommerce_cart_calculate_fees',
        'woocommerce_available_payment_gateways',
        'woocommerce_checkout_update_order_meta',
        'woocommerce_order_status_changed',
    );

    public static function snapshot(): array {
        if ( ! current_user_can( 'manage_options' ) ) { return array(); }
        $active = get_option( 'active_plugins', array() );
        if ( ! is_array( $active ) ) { $active = array(); }
        if ( function_exists( 'get_site_option' ) ) {
            $network = get_site_option( 'active_sitewide_plugins', array() );
            if ( is_array( $network ) ) { $active = array_merge( $active, array_keys( $network ) ); }
        }
        $active = array_values( array_unique( array_filter( $active, 'is_string' ) ) );
        sort( $active );
        $relevant = array();
        foreach ( $active as $slug ) {
            if ( preg_match( '/(?:yaycurrency|yay-currency)/i', $slug ) ) {
                $relevant['YayCurrency'] = $slug;
            }
            if ( preg_match( '/(?:smart-coupons|smart_coupons|smartcoupon)/i', $slug ) ) {
                $relevant[$slug] = $slug;
            }
        }
        $rows = array();
        $commerce_total = 0;
        $unknown_total = 0;
        global $wp_filter;
        foreach ( self::HOOKS as $hook ) {
            $callbacks = isset( $wp_filter[$hook] ) && is_object( $wp_filter[$hook] )
                ? (array) ( $wp_filter[$hook]->callbacks ?? array() ) : array();
            $total = 0;
            $commerce = 0;
            $unknown = 0;
            foreach ( $callbacks as $priority_rows ) {
                if ( ! is_array( $priority_rows ) ) { continue; }
                foreach ( $priority_rows as $row ) {
                    if ( ! is_array( $row ) ) { continue; }
                    $total++;
                    $fn = $row['function'] ?? null;
                    $name = '';
                    if ( is_array( $fn ) ) {
                        $name = is_object( $fn[0] ?? null )
                            ? get_class( $fn[0] ) : (string) ( $fn[0] ?? '' );
                    } elseif ( is_string( $fn ) ) {
                        $name = $fn;
                    } else {
                        $unknown++;
                    }
                    if ( 0 === strpos( $name, 'Andy_Commerce_' ) ||
                         0 === strpos( $name, 'andy_commerce_' ) ||
                         0 === strpos( $name, 'YBY_Woo_Order_Export_' ) ) {
                        $commerce++;
                    }
                }
            }
            $commerce_total += $commerce;
            $unknown_total += $unknown;
            $rows[] = array( 'hook' => $hook, 'total' => $total,
                'commerce' => $commerce, 'unattributed' => $unknown );
        }
        $hpos = get_option( 'woocommerce_custom_orders_table_enabled', null );
        $sync = get_option( 'woocommerce_custom_orders_table_data_sync_enabled', null );
        return array(
            'mode' => Andy_Commerce_Observation_Mode::is_observing() ? 'observe' : 'active',
            'commerce' => defined( 'ANDY_COMMERCE_VERSION' ) ? ANDY_COMMERCE_VERSION : 'unknown',
            'core' => defined( 'YBY_CORE_VERSION' ) ? YBY_CORE_VERSION : 'not loaded',
            'woo' => defined( 'WC_VERSION' ) ? WC_VERSION : 'not loaded',
            'wp' => function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'version' ) : 'unknown',
            'php' => PHP_VERSION,
            'currency' => (string) get_option( 'woocommerce_currency', 'unknown' ),
            'hpos' => in_array( $hpos, array( 'yes', 'no' ), true ) ? $hpos : 'unknown',
            'hpos_sync' => in_array( $sync, array( 'yes', 'no' ), true ) ? $sync : 'unknown',
            'active_plugin_slugs' => $active,
            'relevant_plugins' => $relevant,
            'hook_rows' => $rows,
            'known_commerce_callbacks' => $commerce_total,
            'unattributed_callbacks' => $unknown_total,
            'audit' => Andy_Commerce_Observation_Mode::audit_events(),
        );
    }

    public static function render(): void {
        $d = self::snapshot();
        if ( ! $d ) { return; }
        $observing = 'observe' === $d['mode'];
        $coupons = array_filter( $d['relevant_plugins'], static function ( $key ) {
            return 'YayCurrency' !== $key;
        }, ARRAY_FILTER_USE_KEY );
        $fields = array(
            'Andy Commerce' => $d['commerce'],
            'WordPress / PHP' => $d['wp'] . ' / ' . $d['php'],
            'Andy Core' => $d['core'],
            'WooCommerce' => $d['woo'],
            '基础币种' => $d['currency'],
            'HPOS / 同步配置' => $d['hpos'] . ' / ' . $d['hpos_sync'],
            'YayCurrency 已启用插件匹配' => isset( $d['relevant_plugins']['YayCurrency'] )
                ? $d['relevant_plugins']['YayCurrency'] : '未识别（不代表未安装）',
            'Smart Coupons 已启用匹配数量' => (string) count( $coupons ),
            '当前请求识别的 Commerce 交易回调' => (string) $d['known_commerce_callbacks'],
            '当前请求未归属的回调' => (string) $d['unattributed_callbacks'],
            '观察隔离检查' => $observing
                ? ( 0 === $d['known_commerce_callbacks']
                    ? 'PASS：本次后台请求未发现已识别 Commerce 交易回调'
                    : 'WARNING：本次后台请求发现 Commerce 交易回调' )
                : '运行模式：仅展示注册快照',
        );
        echo '<h2>只读环境与兼容性检查</h2>';
        echo '<table class="widefat striped" style="max-width:960px"><tbody>';
        foreach ( $fields as $title => $value ) {
            echo '<tr><th>' . esc_html( $title ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '<p class="description">这是当前后台请求的静态状态。仅能发现已知回调和已启用插件，不代表购物车、Ajax、Store API、前台或支付网关已完成实际兼容验收；Smart Coupons 多插件共存须另行核对。</p>';

        echo '<h2>WooCommerce Hook 注册清单（只读）</h2>';
        echo '<table class="widefat striped" style="max-width:960px"><thead><tr>';
        foreach ( array( 'Hook', '总回调', '已识别 Commerce', '未归属（如匿名回调）' ) as $header ) {
            echo '<th>' . esc_html( $header ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $d['hook_rows'] as $row ) {
            echo '<tr><td><code>' . esc_html( $row['hook'] ) . '</code></td>';
            foreach ( array( 'total', 'commerce', 'unattributed' ) as $key ) {
                echo '<td>' . esc_html( (string) $row[$key] ) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<h2>模式切换审计（最近 20 条）</h2>';
        if ( ! $d['audit'] ) {
            echo '<p>目前没有 1.0.14 切换记录。历史版本的切换无法追溯。</p>';
            return;
        }
        echo '<table class="widefat striped" style="max-width:960px"><thead><tr><th>UTC</th><th>管理员 ID</th><th>从</th><th>到</th><th>版本</th></tr></thead><tbody>';
        foreach ( array_reverse( $d['audit'] ) as $event ) {
            if ( ! is_array( $event ) ) { continue; }
            echo '<tr>';
            foreach ( array( 'utc', 'user_id', 'from', 'to', 'version' ) as $key ) {
                echo '<td>' . esc_html( (string) ( $event[$key] ?? '' ) ) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
