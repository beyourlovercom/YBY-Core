<?php
/**
 * Andy Campaign Lite 0.1: operator-owned registry, no dynamic PHP editor and no promotion hooks.
 * A pack can only attach business hooks from its boot callback after the host eligibility gate.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Campaign_Lite {
    const SETTINGS = 'andy_campaign_lite_campaigns';
    const MASTER = 'andy_campaign_lite_master';
    const EVENTS = 'andy_campaign_lite_events';
    private static $instance = null;
    private $packs = array();
    private $launched = false;

    public static function boot() {
        if ( self::$instance ) { return self::$instance; }
        self::$instance = new self();
        // Every active WordPress pack is loaded before this hook; only trusted PHP may register.
        do_action( 'andy_campaign_register', self::$instance );
        add_action( 'init', array( self::$instance, 'launch' ), 20 );
        // Cleanup is deliberately unconditional: OFF/expired/uninstalled packs
        // must never leave promotional Woo cart lines to be repriced as purchases.
        add_action( 'woocommerce_cart_loaded_from_session',
            array( self::$instance, 'revoke_stale_lines' ), 95, 1 );
        if ( is_admin() ) {
            add_action( 'admin_menu', array( self::$instance, 'admin_menu' ), 25 );
            add_action( 'admin_post_andy_campaign_lite_save', array( self::$instance, 'save' ) );
        }
        return self::$instance;
    }

    public function register( $spec ) {
        if ( ! is_array( $spec ) ) { return false; }
        $id = $spec['id'] ?? '';
        $name = $spec['name'] ?? '';
        $version = $spec['version'] ?? '';
        $callback = $spec['boot'] ?? null;
        $resources = $spec['resources'] ?? array();
        $ready = $spec['ready'] ?? null;
        if ( ! is_string( $id ) || ! preg_match( '/^[a-z][a-z0-9-]{2,63}$/D', $id )
          || ! is_string( $name ) || '' === trim( $name ) || strlen( $name ) > 120
          || ! is_string( $version ) || ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:-[a-z0-9.-]+)?$/D', $version )
          || ! is_callable( $callback ) || ( null !== $ready && ! is_callable( $ready ) )
          || ! is_array( $resources )
          || isset( $this->packs[ $id ] ) ) { return false; }
        $allowed = array( 'cart-discount', 'gift', 'shipping', 'coupon', 'checkout-display' );
        if ( count( $resources ) > count( $allowed ) ) { return false; }
        foreach ( $resources as $resource ) {
            if ( ! is_string( $resource ) || ! in_array( $resource, $allowed, true ) ) { return false; }
        }
        if ( count( array_unique( $resources ) ) !== count( $resources ) ) { return false; }
        $this->packs[ $id ] = array(
            'id' => $id, 'name' => $name, 'version' => $version,
            'resources' => $resources, 'boot' => $callback, 'ready' => $ready,
        );
        ksort( $this->packs, SORT_STRING );
        return true;
    }

    public function packs() { return $this->packs; }

    /** Optional pack-owned readiness guard; never executes customer-facing mutations. */
    private static function is_ready( array $pack ): bool {
        if ( ! isset( $pack['ready'] ) ) { return true; }
        try { return call_user_func( $pack['ready'] ) === true; }
        catch ( Throwable $error ) {
            error_log( 'ANDY_CAMPAIGN_READINESS_ERROR id=' . $pack['id'] );
            return false;
        }
    }

    private function configuration() {
        $raw = get_option( self::SETTINGS, array() );
        return is_array( $raw ) ? $raw : array();
    }

    /** Both legacy V1 runtime switches must be OFF before Campaign may run. */
    public static function legacy_runtime_requested(): bool {
        return get_option( 'andy_commerce_marketing_dev_uat_enabled', 'no' ) === 'yes'
            || get_option( 'andy_commerce_marketing_p1_sandbox_enabled', 'no' ) === 'yes';
    }

    public function decisions( $now = null ) {
        $now = is_int( $now ) ? $now : time();
        $config = $this->configuration();
        $master = get_option( self::MASTER, 'no' ) === 'yes';
        // Legacy V1 switches are treated as authoritative OFF-vetoes, even if partial.
        $legacy = self::legacy_runtime_requested();
        $used = array();
        $out = array();
        foreach ( $this->packs as $id => $pack ) {
            $entry = $config[ $id ] ?? array();
            $enabled = is_array( $entry ) && ( $entry['enabled'] ?? false ) === true;
            $start = is_array( $entry ) ? (int) ( $entry['start'] ?? 0 ) : 0;
            $end = is_array( $entry ) ? (int) ( $entry['end'] ?? 0 ) : 0;
            $reason = 'eligible';
            if ( ! $master ) { $reason = 'master_off'; }
            elseif ( $legacy ) { $reason = 'legacy_engine_active'; }
            elseif ( ! $enabled ) { $reason = 'campaign_off'; }
            elseif ( $start > 0 && $now < $start ) { $reason = 'not_started'; }
            elseif ( $end > 0 && $now >= $end ) { $reason = 'expired'; }
            elseif ( ! self::is_ready( $pack ) ) { $reason = 'pack_not_ready'; }
            else {
                foreach ( $pack['resources'] as $resource ) {
                    if ( isset( $used[ $resource ] ) ) { $reason = 'resource_conflict'; break; }
                }
            }
            $out[ $id ] = $reason;
            if ( 'eligible' === $reason ) {
                foreach ( $pack['resources'] as $resource ) { $used[ $resource ] = $id; }
            }
        }
        return $out;
    }

    /** Hooks are attached only for approved active packs. Callback itself must not bypass this gate. */
    public function launch() {
        if ( $this->launched ) { return; }
        $this->launched = true;
        foreach ( $this->decisions() as $id => $reason ) {
            if ( 'eligible' !== $reason ) { continue; }
            try {
                call_user_func( $this->packs[ $id ]['boot'], $id );
            } catch ( Throwable $e ) {
                error_log( 'ANDY_CAMPAIGN_BOOT_ERROR id=' . $id . ' type=' . get_class( $e ) );
            }
        }
    }

    /** Remove only lines bearing this host's reserved pack marker. Never touch normal products. */
    public function revoke_stale_lines( $cart ): void {
        if ( ! $cart instanceof WC_Cart ) { return; }
        $states = $this->decisions();
        foreach ( $cart->get_cart() as $key => $line ) {
            if ( ! is_array( $line ) ) { continue; }
            $id = $line['andy_campaign_pack_id'] ?? null;
            if ( ! is_string( $id ) || '' === $id ) { continue; }
            if ( ( $states[ $id ] ?? 'unregistered' ) !== 'eligible' ) {
                $cart->remove_cart_item( $key );
            }
        }
    }

    public function admin_menu() {
        // Andy Core alone renders the L2/L3 navigation; no duplicate top-level menu.
        add_submenu_page( null, 'Andy Campaign', 'Andy Campaign',
            'manage_woocommerce', 'andy-campaign', array( $this, 'page' ) );
    }

    private static function parse_utc( $raw ) {
        if ( '' === $raw ) { return 0; }
        if ( ! is_string( $raw ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $raw ) ) {
            return false;
        }
        $dt = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $raw, new DateTimeZone( 'UTC' ) );
        return $dt && $dt->format( 'Y-m-d\TH:i' ) === $raw ? $dt->getTimestamp() : false;
    }

    private static function log_event( $id, $event ) {
        $items = get_option( self::EVENTS, array() );
        if ( ! is_array( $items ) ) { $items = array(); }
        $items[] = array( 'at' => gmdate( 'c' ), 'campaign' => $id, 'event' => $event );
        update_option( self::EVENTS, array_slice( $items, -100 ), false );
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden', '', array( 'response' => 403 ) ); }
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'POST required', '', array( 'response' => 405 ) ); }
        $scalar_fields = array( '_wpnonce', 'scope', 'campaign_id', 'start', 'end', 'landing_page_ids' );
        foreach ( $scalar_fields as $field ) {
            if ( isset( $_POST[ $field ] ) && ! is_string( $_POST[ $field ] ) ) {
                wp_die( 'Invalid input field', '', array( 'response' => 422 ) );
            }
        }
        check_admin_referer( 'andy_campaign_lite_save' );
        $scope = sanitize_key( wp_unslash( $_POST['scope'] ?? '' ) );
        if ( 'master' === $scope ) {
            $state = isset( $_POST['enabled'] ) ? 'yes' : 'no';
            if ( 'yes' === $state && self::legacy_runtime_requested() ) {
                wp_die( 'Disable the legacy Andy Commerce Marketing V1 engine before enabling Andy Campaign.', '', array( 'response' => 409 ) );
            }
            update_option( self::MASTER, $state, false );
            self::log_event( 'kernel', 'master_' . $state );
        } elseif ( 'campaign' === $scope ) {
            $id = sanitize_key( wp_unslash( $_POST['campaign_id'] ?? '' ) );
            if ( ! isset( $this->packs[ $id ] ) ) { wp_die( 'Unknown campaign', '', array( 'response' => 422 ) ); }
            $start = self::parse_utc( sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) ) );
            $end = self::parse_utc( sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) ) );
            if ( false === $start || false === $end || ( $start > 0 && $end > 0 && $start >= $end ) ) {
                wp_die( 'Invalid UTC window', '', array( 'response' => 422 ) );
            }
            $raw = sanitize_text_field( wp_unslash( $_POST['landing_page_ids'] ?? '' ) );
            if ( ! preg_match( '/^[0-9, ]{0,130}$/D', $raw ) ) { wp_die( 'Invalid page IDs', '', array( 'response' => 422 ) ); }
            $ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );
            if ( count( $ids ) > 10 ) { wp_die( 'Too many linked pages', '', array( 'response' => 422 ) ); }
            foreach ( $ids as $pid ) {
                if ( 'landing_page' !== get_post_type( $pid ) ) {
                    wp_die( 'Only existing Andy Core landing pages can be linked', '', array( 'response' => 422 ) );
                }
            }
            $requested_enabled = isset( $_POST['enabled'] );
            if ( $requested_enabled && self::legacy_runtime_requested() ) {
                wp_die( 'Legacy Marketing V1 must be OFF before configuring an active campaign.', '', array( 'response' => 409 ) );
            }
            if ( $requested_enabled && ! self::is_ready( $this->packs[ $id ] ) ) {
                wp_die( 'Campaign cannot be enabled: gift pool or required configuration is unavailable', '', array( 'response' => 422 ) );
            }
            $config = $this->configuration();
            $config[ $id ] = array(
                'enabled' => $requested_enabled, 'start' => $start, 'end' => $end,
                'landing_page_ids' => $ids,
            );
            update_option( self::SETTINGS, $config, false );
            self::log_event( $id, $config[ $id ]['enabled'] ? 'configured_on' : 'configured_off' );
        } else {
            wp_die( 'Invalid request', '', array( 'response' => 422 ) );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=andy-campaign&tab=campaigns&saved=1' ) );
        exit;
    }

    public function page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Forbidden', '', array( 'response' => 403 ) ); }
        $raw_tab = $_GET['tab'] ?? 'campaigns';
        $tab = is_string( $raw_tab ) ? sanitize_key( wp_unslash( $raw_tab ) ) : 'campaigns';
        if ( ! in_array( $tab, array( 'campaigns', 'logs', 'settings' ), true ) ) { $tab = 'campaigns'; }
        echo '<div class="wrap"><h1>Andy Campaign · Lite</h1>';
        echo '<p>Code-first packs. Andy Core owns all landing page content and editing. No PHP editor, no discount engine.</p>';
        if ( self::legacy_runtime_requested() ) {
            echo '<div class="notice notice-warning"><p>Legacy Andy Commerce Marketing V1 is ON. Andy Campaign runtime is blocked until both legacy switches are OFF; do not run two marketing engines.</p></div>';
        }
        if ( 'logs' === $tab ) {
            echo '<h2>操作记录（最多最近100条）</h2><table class="widefat striped"><thead><tr><th>UTC</th><th>活动</th><th>事件</th></tr></thead><tbody>';
            $events = get_option( self::EVENTS, array() );
            foreach ( array_reverse( is_array( $events ) ? $events : array() ) as $event ) {
                echo '<tr><td>' . esc_html( $event['at'] ?? '' ) . '</td><td>' . esc_html( $event['campaign'] ?? '' ) . '</td><td>' . esc_html( $event['event'] ?? '' ) . '</td></tr>';
            }
            echo '</tbody></table>';
        } elseif ( 'settings' === $tab ) {
            echo '<h2>总开关</h2><p>默认 OFF。只影响遵守注册协议的活动包，不删除已存在的订单或历史优惠记录。</p>';
            if ( current_user_can( 'manage_options' ) ) {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'andy_campaign_lite_save' );
                echo '<input type="hidden" name="action" value="andy_campaign_lite_save"><input type="hidden" name="scope" value="master">';
                echo '<label><input type="checkbox" name="enabled" value="1" ' . checked( get_option( self::MASTER, 'no' ), 'yes', false ) . '> 启用营销运行内核</label> ';
                submit_button( '保存', 'primary', 'submit', false );
                echo '</form>';
            }
        } else {
            $config = $this->configuration();
            $decisions = $this->decisions();
            if ( ! $this->packs ) { echo '<p>没有已注册的活动包。安装独立、经审核的 PHP 活动插件后会显示在这里。</p>'; }
            foreach ( $this->packs as $id => $pack ) {
                $entry = $config[ $id ] ?? array();
                echo '<hr><h2>' . esc_html( $pack['name'] ) . ' · ' . esc_html( $pack['version'] ) . '</h2>';
                echo '<p>Code: <code>' . esc_html( $id ) . '</code> · State: <strong>' . esc_html( $decisions[ $id ] ?? 'off' ) . '</strong> · Resources: ' . esc_html( implode( ', ', $pack['resources'] ) ) . '</p>';
                $pages = is_array( $entry ) ? ( $entry['landing_page_ids'] ?? array() ) : array();
                foreach ( (array) $pages as $pid ) {
                    if ( 'landing_page' === get_post_type( (int) $pid ) ) {
                        $url = get_edit_post_link( (int) $pid, '' );
                        if ( $url ) { echo '<p><a href="' . esc_url( $url ) . '">编辑 Andy Core Landing Page #' . esc_html( (string) $pid ) . '</a></p>'; }
                    }
                }
                if ( ! current_user_can( 'manage_options' ) ) { continue; }
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'andy_campaign_lite_save' );
                echo '<input type="hidden" name="action" value="andy_campaign_lite_save"><input type="hidden" name="scope" value="campaign"><input type="hidden" name="campaign_id" value="' . esc_attr( $id ) . '">';
                echo '<p><label><input type="checkbox" name="enabled" value="1" ' . checked( is_array( $entry ) && ( $entry['enabled'] ?? false ), true, false ) . '> 活动启用</label></p>';
                foreach ( array( 'start' => '开始时间 UTC', 'end' => '结束时间 UTC' ) as $key => $label ) {
                    $stamp = is_array( $entry ) ? (int) ( $entry[ $key ] ?? 0 ) : 0;
                    echo '<p><label>' . esc_html( $label ) . ' <input type="datetime-local" name="' . esc_attr( $key ) . '" value="' . esc_attr( $stamp ? gmdate( 'Y-m-d\TH:i', $stamp ) : '' ) . '"></label></p>';
                }
                echo '<p><label>关联落地页 ID（仅引用，最多10个）：<input type="text" name="landing_page_ids" value="' . esc_attr( implode( ',', (array) $pages ) ) . '"></label></p>';
                submit_button( '保存活动', 'secondary', 'submit', false );
                echo '</form>';
            }
        }
        echo '</div>';
    }
}
