<?php
/**
 * BYL BF2026 R14 — strictly isolated single-operator Canary.
 * No public cart hooks, pricing or discounts until the operator opts in.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R14_Canary {
    private const OPTION = 'byl_bf2026_canary_v1';
    private const COOKIE = 'byl_bf26_canary';
    private const TTL = 1800;
    private static bool $booted = false;

    /** Local isolated WP-CLI verification ONLY, never a browser bypass. */
    private static function dev_cli_test(): bool {
        return defined('WP_CLI') && WP_CLI
            && defined('BYL_BF2026_DEV_CLI_TEST') && BYL_BF2026_DEV_CLI_TEST === true
            && function_exists('home_url')
            && function_exists('wp_parse_url')
            && wp_parse_url(home_url('/'),PHP_URL_HOST)==='dev.beyourlover.com';
    }

    /** Full release requires a separate reviewed release artifact and explicit constant. */
    public static function global_release_allowed(): bool {
        return defined('BYL_BF2026_ALLOW_GLOBAL_GIFTS')
            && BYL_BF2026_ALLOW_GLOBAL_GIFTS === true;
    }

    public static function granted(): bool {
        if (self::dev_cli_test()) { return true; }
        if (!function_exists('is_user_logged_in')
            || !function_exists('current_user_can')
            || !function_exists('get_current_user_id')) {return false;}
        $cfg=get_option(self::OPTION,[]);
        if (!is_array($cfg) || ($cfg['state']??null)!=='active'
            || !is_int($cfg['user_id']??null)
            || !is_int($cfg['expires_at']??null) || $cfg['expires_at']<=time()
            || !is_string($cfg['token_sha256']??null)
            || preg_match('/^[a-f0-9]{64}$/D',$cfg['token_sha256'])!==1) {
            return false;
        }
        // The normal WP principal is authoritative. Only if WordPress REST
        // explicitly dropped that principal (no wp_rest nonce) may an otherwise
        // valid logged-in administrator cookie prove the same exact identity.
        $logged_in=is_user_logged_in();
        $allowed=$logged_in
            ? current_user_can('manage_options')
                && (int)get_current_user_id()===$cfg['user_id']
            : self::rest_admin_cookie_valid($cfg['user_id']);
        if (!$allowed) {return false;}
        $token=$_COOKIE[self::COOKIE]??null;
        if (!is_string($token) || preg_match('/^[a-f0-9]{48}$/D',$token)!==1) {
            return false;
        }
        return hash_equals($cfg['token_sha256'],hash('sha256',$token));
    }

    /** Restore the operator identity ONLY for WordPress REST requests.

     * WP core's rest_cookie_check_errors() intentionally replaces the current
     * user with ID 0 when X-WP-Nonce is absent. Woo Store API relies on its own
     * Cart-Token/Nonce instead; merely reading an already-authorized cart may
     * therefore lose the admin capability during a later cart recalculation.
     *
     * Require BOTH the independently signed Canary cookie (checked by granted)
     * and the valid WordPress logged-in cookie belonging to the allowlisted
     * administrator. Never trust an unverified user ID or a Cart-Token.
     */
    private static function rest_admin_cookie_valid(int $expected_user): bool {
        if (!defined('REST_REQUEST') || REST_REQUEST !== true
            || !defined('LOGGED_IN_COOKIE')
            || !function_exists('wp_validate_auth_cookie')
            || !function_exists('user_can')
            || !isset($_COOKIE[LOGGED_IN_COOKIE])
            || !is_string($_COOKIE[LOGGED_IN_COOKIE])) {
            return false;
        }
        $id=wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
        return is_int($id) && $id===$expected_user
            && user_can($id,'manage_options');
    }

    /** Campaign hook readiness must never affect guests while Canary is enabled. */
    public static function runtime_allowed(): bool {
        return self::granted() || self::global_release_allowed();
    }

    public static function bootstrap(): void {
        $giftPost=function_exists('is_admin') && is_admin()
            && isset($_POST['action'])
            && $_POST['action']==='byl_bf2026_choose_gift';
        if (self::$booted || (function_exists('is_admin') && is_admin() && !$giftPost)
            || !self::granted() || !class_exists('Andy_Campaign_Lite')
            || !class_exists('BYL_BF2026_R15_Entitlements')
            || !class_exists('WooCommerce')
            || !BYL_BF2026_R15_Entitlements::ready()) {return;}

        // A global campaign is a separate release gate; a canary must never
        // silently turn on the host master for every visitor.
        if (get_option(Andy_Campaign_Lite::MASTER,'no')!=='no'
            || Andy_Campaign_Lite::legacy_runtime_requested()) {return;}
        $host=Andy_Campaign_Lite::boot();
        $states=$host->decisions();
        if (($states[BYL_BF2026_Gifts::ID]??null)!=='master_off') {return;}

        // Master OFF would normally delete all promotion lines from session.
        // Bypass this revocation ONLY for the allowlisted operator's signed
        // browser session. All other users retain host's default cleanup.
        if (!remove_action('woocommerce_cart_loaded_from_session',
            [$host,'revoke_stale_lines'],95)) {return;}
        self::$booted=true;
        if (function_exists('nocache_headers')) {nocache_headers();}
        BYL_BF2026_R15_Entitlements::boot();
    }

    public static function admin_menu(): void {
        if (!function_exists('add_submenu_page')) {return;}
        add_submenu_page('woocommerce','Black Friday 2026 测试','BF2026 测试',
            'manage_options','byl-bf2026-canary',[__CLASS__,'admin_page']);
    }

    public static function admin_page(): void {
        if (!current_user_can('manage_options')) {wp_die('Forbidden','',['response'=>403]);}
        $cfg=get_option(self::OPTION,[]);
        $active=is_array($cfg) && ($cfg['state']??'')==='active'
            && ($cfg['expires_at']??0)>time();
        echo '<div class="wrap"><h1>Black Friday 2026 · 受控测试</h1>';
        echo '<p>仅对当前管理员与当前浏览器开放，默认关闭。最长30分钟；不会开启全站 Andy Campaign。</p>';
        echo '<p><strong>当前状态：</strong>'.($active?'测试令牌有效（仅绑定账户及浏览器）':'关闭或已到期').'</p>';
        echo '<p>随机礼品由仓库配货，插件仅记录件数；美国选礼按 15 款 Gift 商品和实时库存验证。测试期间禁止真实付款。</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        wp_nonce_field('byl_bf2026_canary_control');
        echo '<input type="hidden" name="action" value="byl_bf2026_canary_control">';
        echo '<button class="button button-primary" name="mode" value="start">开启当前浏览器30分钟测试</button> ';
        echo '<button class="button" name="mode" value="stop">立即停止</button>';
        echo '</form></div>';
    }

    private static function session_cookie(string $token,int $expires): void {
        if (headers_sent()) {throw new RuntimeException('canary_cookie_headers_sent');}
        setcookie(self::COOKIE,$token,[
            'expires'=>$expires,'path'=>'/','secure'=>true,
            'httponly'=>true,'samesite'=>'Lax'
        ]);
    }

    public static function admin_post(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Forbidden','',['response'=>403]);
        }
        check_admin_referer('byl_bf2026_canary_control');
        $raw=$_POST['mode']??null;
        if (!is_string($raw) || !in_array($raw,['start','stop'],true)) {
            wp_die('Invalid canary action','',['response'=>422]);
        }
        if ($raw==='stop') {
            update_option(self::OPTION,['state'=>'off'],false);
            self::session_cookie('',time()-3600);
        } else {
            // One account + one browser token; no persistent global marketing.
            $token=bin2hex(random_bytes(24));
            $end=time()+self::TTL;
            self::session_cookie($token,$end);
            update_option(self::OPTION,[
                'state'=>'active',
                'user_id'=>(int)get_current_user_id(),
                'token_sha256'=>hash('sha256',$token),
                'expires_at'=>$end,
            ],false);
        }
        wp_safe_redirect(admin_url('admin.php?page=byl-bf2026-canary'));
        exit;
    }
}
