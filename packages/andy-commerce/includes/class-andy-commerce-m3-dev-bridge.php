<?php
/**
 * Andy Commerce M3 draft-only Dev bridge.
 *
 * Explicit opt-in, NOT referenced from plugin bootstrap. Site owner must set
 * ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED on the isolated Dev site after schema UAT.
 * No campaign runtime integration, activation or auto migrations.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M3_Dev_Bridge {
    private Andy_Commerce_M3_Draft_Repository $repository;
    private Andy_Commerce_M3_Draft_Request_Controller $controller;

    public function __construct(object $wpdb) {
        $this->repository = new Andy_Commerce_M3_Draft_Repository($wpdb);
        $adapter = new Andy_Commerce_M3_Draft_Admin_Adapter($this->repository);
        $this->controller = new Andy_Commerce_M3_Draft_Request_Controller($adapter);
    }

    public static function enabled(): bool {
        return defined('ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED')
            && ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED === true
            && defined('WP_ENVIRONMENT_TYPE')
            && WP_ENVIRONMENT_TYPE === 'development';
    }

    public function register(): void {
        if (!self::enabled()) { return; }
        add_action('admin_post_andy_commerce_m3_draft_request', [$this, 'post']);
    }

    public function post(): void {
        if (!self::enabled() || !is_admin() || !current_user_can('manage_woocommerce')) {
            wp_die('Draft management unavailable', '', ['response'=>403]);
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            wp_die('POST required', '', ['response'=>405]);
        }
        // No unguarded admin-ajax, REST, public endpoint, schedule or publish path.
        $posted = wp_unslash($_POST);
        if (!is_array($posted)) { wp_die('Invalid request', '', ['response'=>400]); }
        try {
            $result = $this->controller->handle($posted);
            $revision = isset($result['revision']) ? (int)$result['revision'] : 0;
            $url = add_query_arg(
                ['page'=>'andy-commerce','tab'=>'marketing-promotions',
                 'm3_result'=>'draft_saved','m3_revision'=>$revision],
                admin_url('admin.php')
            );
            wp_safe_redirect($url, 303);
            exit;
        } catch (DomainException | InvalidArgumentException $e) {
            wp_die('Draft request rejected', '', ['response'=>400]);
        } catch (Throwable $e) {
            // Don't leak SQL diagnostics or stored payload to the user.
            wp_die('Draft storage unavailable', '', ['response'=>503]);
        }
    }
}
