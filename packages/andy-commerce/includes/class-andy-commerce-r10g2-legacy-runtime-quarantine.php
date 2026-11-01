<?php
/**
 * R10-G2: fail-closed legacy Woo transaction-runtime quarantine.
 *
 * No WordPress option or external side effects. Even leaving global OBSERVE
 * does NOT implicitly re-enable old Shipping/Currency/Marketing hooks.
 * This is a source-only pre-approval candidate; Dev stays OBSERVE/OFF.
 */
declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10G2_Legacy_Runtime_Quarantine {
    private const MODULE_OPTIONS = [
        'shipping'  => 'andy_commerce_r10g2_legacy_shipping_enabled_v1',
        'currency'  => 'andy_commerce_r10g2_legacy_currency_enabled_v1',
        'marketing' => 'andy_commerce_r10g2_legacy_marketing_enabled_v1',
    ];

    public static function allowed(string $module): bool {
        if (!array_key_exists($module, self::MODULE_OPTIONS)
            || !function_exists('get_option')
            || !function_exists('wp_get_environment_type')
            || (class_exists('Andy_Commerce_Observation_Mode', false)
                && Andy_Commerce_Observation_Mode::is_observing())) return false;

        // Existing C-R1..R13 native Woo tests intentionally arm Currency
        // with their own isolated wp-config flag. Keep that regression path
        // ONLY in its fixed disposable database/hostname, with no network
        // or Cron; no legacy Shipping/Marketing exception is allowed.
        if ($module === 'currency'
            && defined('AICC_CURRENCY_RUNTIME_SANDBOX')
            && AICC_CURRENCY_RUNTIME_SANDBOX === true
            && defined('DB_NAME') && DB_NAME === 'commerce_p5_isolated'
            && get_option('siteurl', '') === 'http://commerce-p5.invalid'
            && wp_get_environment_type() === 'local'
            && defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL === true
            && defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true) return true;

        // No permission for WWW or any unexpected host, even if a stray
        // deployment accidentally carries a configuration from Dev.
        if (get_option('siteurl', '') !== 'https://dev.beyourlover.com'
            || !in_array(wp_get_environment_type(), ['staging', 'development'], true)) return false;

        // Separate *server* and *WordPress per-module* approvals; neither is
        // derived from the existing R10-C switches or a previous release PASS.
        if (!defined('ANDY_COMMERCE_R10G2_OWNER_LEGACY_AUTH')
            || ANDY_COMMERCE_R10G2_OWNER_LEGACY_AUTH !== 'OWNER_APPROVED_R10G2_DEV_LEGACY') return false;

        return get_option(self::MODULE_OPTIONS[$module], 'no') === 'yes';
    }
}
