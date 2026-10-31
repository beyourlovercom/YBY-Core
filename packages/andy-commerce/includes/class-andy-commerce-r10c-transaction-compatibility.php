<?php
/**
 * Andy Commerce R10-C — explicit, independently gated compatibility adapters.
 *
 * Source-only release candidate. Importing this class registers NOTHING.
 * WWW is intentionally unarmed until a separate Owner-approved server gate
 * and a per-module option are both present; observation always wins.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10C_Transaction_Compatibility {
    public const SHIPPING_OPTION = 'andy_commerce_r10c_shipping_enabled_v1';
    public const COUPON_OPTION = 'andy_commerce_r10c_coupon_enabled_v1';
    public const AUDIT_OPTION = 'andy_commerce_r10c_toggle_audit_v1';
    private const ORDINARY = array('percent', 'fixed_cart', 'fixed_product');
    private static bool $shipping_booted = false;
    private static bool $coupon_booted = false;

    /** Explicit two-key gate, no option writes or implicit activation. */
    public static function enabled(string $module): bool {
        if (!in_array($module, array('shipping','coupon'), true)) return false;
        $option = $module === 'shipping' ? self::SHIPPING_OPTION : self::COUPON_OPTION;
        return get_option($option,'no') === 'yes' && self::eligible($module);
    }

    /** Prerequisites for an Owner-initiated module ON action. */
    private static function eligible(string $module): bool {
        if (!in_array($module, array('shipping','coupon'), true)
            || !function_exists('get_option') || !function_exists('WC')
            || !defined('WC_VERSION') || WC_VERSION !== '11.2.1'
            || !class_exists('WC_Coupon')
            || (class_exists('Andy_Commerce_Observation_Mode')
                && Andy_Commerce_Observation_Mode::is_observing())) return false;
        $site = (string)get_option('siteurl','');
        $host = parse_url($site, PHP_URL_HOST);
        $port = parse_url($site, PHP_URL_PORT);
        $sandbox = $host === '127.0.0.1' && (int)$port === 14141
            && defined('ANDY_COMMERCE_R10C_ISOLATED_UAT')
            && ANDY_COMMERCE_R10C_ISOLATED_UAT === true
            && is_file(rtrim(ABSPATH,'/\\') . '/.andy-r10b-isolated-local');
        $production = $host === 'www.beyourlover.com'
            && defined('ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH')
            && ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH === 'OWNER_APPROVED_R10C_RELEASE'
            && function_exists('wp_get_environment_type')
            && wp_get_environment_type() === 'production';
        if (!$sandbox && !$production) return false;
        // Exact active dependency stack: no silent vendor substitution or update.
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!function_exists('is_plugin_active')) return false;
        $stack = array(
            'woocommerce/woocommerce.php' => '11.2.1',
            'yaycurrency-pro/yay-currency.php' => '3.3.5',
            'wt-smart-coupon-pro/wt-smart-coupon-pro.php' => '3.2.0',
            'woocommerce-smart-coupons/woocommerce-smart-coupons.php' => '9.51.0',
        );
        $plugins = get_plugins();
        foreach ($stack as $path => $version) {
            if (!is_plugin_active($path) ||
                ($plugins[$path]['Version'] ?? null) !== $version) return false;
        }
        return get_option('woocommerce_currency','') === 'USD'
            && function_exists('wc_get_price_decimals')
            && wc_get_price_decimals() === 2;
    }

    public static function boot(): void {
        if (!self::$shipping_booted && self::enabled('shipping')) {
            add_filter('woocommerce_shipping_free_shipping_is_available',
                array(self::class,'tiny_native_threshold_epsilon'), -100000, 3);
            self::$shipping_booted = true;
        }
        if (!self::$coupon_booted && self::enabled('coupon')) {
            add_filter('woocommerce_coupon_is_valid',
                array(self::class,'single_ordinary_discount'), 100000, 2);
            self::$coupon_booted = true;
        }
    }

    /** Local/active-only admin controls. Observation excludes this class. */
    public static function register_admin_menu(): void {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) return;
        global $menu;
        $has_core = false;
        foreach ((array)$menu as $entry) {
            if (isset($entry[2]) && $entry[2] === 'yby-os') { $has_core=true; break; }
        }
        $parent = $has_core ? 'yby-os' : 'andy-commerce';
        add_submenu_page($parent, 'R10-C 兼容性开关', 'R10-C 模块开关',
            'manage_options', 'andy-commerce-r10c', array(self::class,'render_admin_page'));
    }

    /** All writes are explicit POST + nonce + expected-state checks. */
    public static function handle_admin_toggle(): void {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            wp_die('Permission denied', '', array('response'=>403));
        }
        check_admin_referer('andy_commerce_r10c_toggle','andy_commerce_r10c_nonce');
        $module = isset($_POST['module']) && is_string($_POST['module'])
            ? wp_unslash($_POST['module']) : '';
        $target = isset($_POST['enabled']) && is_string($_POST['enabled'])
            ? wp_unslash($_POST['enabled']) : '';
        $expected = isset($_POST['expected']) && is_string($_POST['expected'])
            ? wp_unslash($_POST['expected']) : '';
        if (!in_array($module, array('shipping','coupon'), true)
            || !in_array($target, array('yes','no'),true)
            || !in_array($expected, array('yes','no'),true)) {
            wp_die('Invalid R10-C module request', '', array('response'=>400));
        }
        $key = $module === 'shipping' ? self::SHIPPING_OPTION : self::COUPON_OPTION;
        $current = get_option($key,'no');
        if ($current !== $expected || $current === $target) {
            wp_die('Stale or unchanged R10-C request', '', array('response'=>409));
        }
        if ($target === 'yes') {
            $ack = isset($_POST['owner_ack']) && is_string($_POST['owner_ack'])
                ? wp_unslash($_POST['owner_ack']) : '';
            if ($ack !== 'ENABLE_R10C_'.strtoupper($module) || !self::eligible($module)) {
                wp_die('R10-C Owner confirmation or preflight missing',
                    '', array('response'=>409));
            }
        }
        if (!update_option($key,$target,false)) {
            wp_die('R10-C module state not persisted', '', array('response'=>500));
        }
        $events = get_option(self::AUDIT_OPTION,array());
        if (!is_array($events)) $events=array();
        $events[] = array(
            'utc'=>gmdate('Y-m-d H:i:s'),
            'user_id'=>(int)get_current_user_id(),
            'module'=>$module, 'from'=>$current, 'to'=>$target,
            'version'=>defined('ANDY_COMMERCE_VERSION') ? ANDY_COMMERCE_VERSION : 'unknown',
        );
        $recorded=update_option(self::AUDIT_OPTION,array_slice($events,-20),false);
        wp_safe_redirect(admin_url('admin.php?page=andy-commerce-r10c&saved=1'
            .($recorded?'':'&audit_warning=1')));
        exit;
    }

    public static function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied', '', array('response'=>403));
        }
        echo '<div class="wrap"><h1>Andy Commerce — R10-C 兼容性模块</h1>';
        echo '<p>默认均为关闭。启用必须先获得 Owner 独立审批并配置服务器授权。观察模式优先，未退出前不能开启交易 Hook。</p>';
        echo '<table class="widefat striped"><thead><tr><th>模块</th><th>配置</th><th>运行门禁</th><th>操作</th></tr></thead><tbody>';
        foreach (array('shipping'=>'Shipping / 免邮精度','coupon'=>'Coupon / 单券限制') as $module=>$label) {
            $key=$module==='shipping' ? self::SHIPPING_OPTION : self::COUPON_OPTION;
            $current=get_option($key,'no')==='yes' ? 'yes' : 'no';
            $can_enable=self::eligible($module);
            $target=$current==='yes' ? 'no' : 'yes';
            echo '<tr><td>'.esc_html($label).'</td><td>'.esc_html($current==='yes'?'已启用':'关闭').'</td>';
            echo '<td>'.esc_html(self::enabled($module)?'已通过':'未通过 / 未授权').'</td><td>';
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            echo '<input type="hidden" name="action" value="andy_commerce_r10c_module_toggle">';
            echo '<input type="hidden" name="module" value="'.esc_attr($module).'">';
            echo '<input type="hidden" name="enabled" value="'.esc_attr($target).'">';
            echo '<input type="hidden" name="expected" value="'.esc_attr($current).'">';
            wp_nonce_field('andy_commerce_r10c_toggle','andy_commerce_r10c_nonce');
            if ($target === 'yes' && $can_enable) {
                echo '<label>确认输入 <code>'.esc_html('ENABLE_R10C_'.strtoupper($module)).'</code>：';
                echo '<input name="owner_ack" type="text" required autocomplete="off"></label> ';
            }
            echo '<button type="submit" class="button"'.($target==='yes'&&!$can_enable?' disabled':'').'>';
            echo esc_html($target==='yes'?'启用（需 Owner 审批）':'立即关闭').'</button></form></td></tr>';
        }
        echo '</tbody></table><p>紧急时，可分别关闭模块；也可使用全局观察模式立即停止所有 Andy Commerce 交易 Hook。</p></div>';
    }

    /** Readback for diagnostics. Never displays constants or other secrets. */
    public static function status(): array {
        return array(
            'global_observation' => class_exists('Andy_Commerce_Observation_Mode')
                ? Andy_Commerce_Observation_Mode::is_observing() : null,
            'shipping_option_enabled' => get_option(self::SHIPPING_OPTION,'no') === 'yes',
            'coupon_option_enabled' => get_option(self::COUPON_OPTION,'no') === 'yes',
            'shipping_gate_passed' => self::enabled('shipping'),
            'coupon_gate_passed' => self::enabled('coupon'),
        );
    }

    private static function ordinary_discount($coupon): bool {
        if (!($coupon instanceof WC_Coupon)) return false;
        try {
            if ($coupon->get_free_shipping()) return false;
            return in_array($coupon->get_discount_type(),self::ORDINARY,true)
                && is_numeric($coupon->get_amount())
                && (float)$coupon->get_amount() > 0.0;
        } catch (Throwable $error) { return false; }
    }

    /** Existing Woo/vendor veto is always preserved for incoming coupon. */
    public static function single_ordinary_discount($valid,$coupon) {
        if (!$valid || !self::enabled('coupon') ||
            !self::ordinary_discount($coupon)) return $valid;
        $cart = WC()->cart ?? null;
        if (!is_object($cart) || !method_exists($cart,'get_applied_coupons')) return $valid;
        $code = (string)$coupon->get_code();
        foreach ((array)$cart->get_applied_coupons() as $applied) {
            $applied = (string)$applied;
            if ($applied === $code) return $valid;
            try {
                if (self::ordinary_discount(new WC_Coupon($applied))) return false;
            } catch (Throwable $error) { return false; }
        }
        return $valid;
    }

    /**
     * Narrow binary-equality fix. No broad free-shipping policy replacement;
     * special shipping classes, gifts, unsupported zone/methods fail closed.
     * A later vendor filter can still veto a TRUE return.
     */
    public static function tiny_native_threshold_epsilon($available,$package,$method) {
        if ($available || !self::enabled('shipping')
            || !is_array($package) || !is_object($method)
            || ($method->id ?? '') !== 'free_shipping'
            || (int)($method->instance_id ?? 0) <= 0
            || ($method->enabled ?? '') !== 'yes'
            || !in_array($method->requires ?? null, array('either','min_amount'),true)
            || ($method->ignore_discounts ?? '') !== 'no'
            || get_option('woocommerce_prices_include_tax','no') !== 'no'
            || !class_exists('WC_Shipping_Zones')) return $available;
        $cart = WC()->cart ?? null;
        if (!is_object($cart)
            || !method_exists($cart,'get_displayed_subtotal')
            || !method_exists($cart,'get_discount_total')
            || !method_exists($cart,'get_cart')) return $available;
        $zone = WC_Shipping_Zones::get_zone_matching_package($package);
        if (!is_object($zone) || !method_exists($zone,'get_id')
            || (int)$zone->get_id() < 0
            || !method_exists($zone,'get_shipping_methods')) return $available;
        // Match the precise *active* native method of the zone Woo selected
        // for this shipping package; method IDs differ between installations.
        // Never substitute a guessed default zone or a hard-coded instance ID.
        $instance_id = (int)$method->instance_id;
        $match = false;
        foreach ($zone->get_shipping_methods(false) as $installed) {
            if (($installed->id ?? '') !== 'free_shipping'
                || (int)($installed->instance_id ?? 0) !== $instance_id
                || ($installed->enabled ?? '') !== 'yes') continue;
            if (($installed->requires ?? null) !== ($method->requires ?? null)
                || ($installed->ignore_discounts ?? null)
                   !== ($method->ignore_discounts ?? null)) return $available;
            $match = true;
            break;
        }
        if (!$match) return $available;
        // In the absence of a verified shipping-class exclusion policy,
        // no epsilon override on special classes or marked promotion gifts.
        $lines = $cart->get_cart();
        if (!is_array($lines) || !$lines || count($lines) > 200) return $available;
        foreach ($lines as $line) {
            $product = $line['data'] ?? null;
            if (!is_object($product) || !method_exists($product,'get_shipping_class_id')
                || (int)$product->get_shipping_class_id() !== 0
                || !empty($line['andy_commerce_free_gift'])
                || !empty($line['_byl_bf2026_gift'])
                || !empty($line['andy_campaign_pack_id'])) return $available;
        }
        try {
            $actual = (float)$cart->get_displayed_subtotal()
                - (float)$cart->get_discount_total();
            if ($cart->display_prices_including_tax()) {
                $actual -= (float)$cart->get_discount_tax();
            }
            $minimum = $method->min_amount ?? null;
            if (!is_numeric($minimum) || !is_finite($actual)
                || $actual < 0 || $actual > 1000000
                || !is_finite((float)$minimum)
                || (float)$minimum <= 0 || (float)$minimum > 1000000) return $available;
            $minimum = (float)$minimum;
            $delta = $minimum - $actual;
            if ((int)round($actual*100) !== (int)round($minimum*100)
                || $delta <= 0 || $delta >= 1e-9) return $available;
            return true;
        } catch (Throwable $error) { return $available; }
    }
}
