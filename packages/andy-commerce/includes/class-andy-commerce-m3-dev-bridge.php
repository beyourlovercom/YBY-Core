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
            && in_array(WP_ENVIRONMENT_TYPE, ['development','staging'], true)
            && parse_url(home_url(), PHP_URL_HOST) === 'dev.beyourlover.com';
    }

    /** Read-only admin list; no promotion hooks. */
    public function drafts(): array {
        return self::enabled() ? $this->repository->listDrafts() : [];
    }

    public function campaignListing(string $view,string $search,int $page):array {
        if (!self::enabled()) { return ['items'=>[],'total'=>0,'page'=>1,'per_page'=>25]; }
        return $this->repository->listCampaigns($view==='trash'?'archived':'draft',$search,$page);
    }

    public function campaignCounts():array {
        if (!self::enabled()) { return ['draft'=>0,'archived'=>0]; }
        return ['draft'=>$this->repository->countByStatus('draft'),
                'archived'=>$this->repository->countByStatus('archived')];
    }

    public function draft(string $id): ?array {
        if (!self::enabled() || !preg_match('/^[a-z0-9][a-z0-9_-]{2,59}$/D', $id)) { return null; }
        $row = $this->repository->find($id);
        if (!$row || $row['status'] !== 'draft' || (int)$row['active'] !== 0) { return null; }
        return $row;
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
            $campaignId = isset($result['id']) && is_string($result['id']) ? $result['id'] : '';
            $success = in_array(($result['status'] ?? ''),['archived','bulk_archived'],true)
                ? (string)$result['status'] : (($posted['draft_action']??'')==='duplicate'?'duplicated':'draft_saved');
            $url = add_query_arg(
                ['page'=>'andy-commerce','tab'=>'marketing-promotions',
                 'm3_result'=>$success,'m3_revision'=>$revision,
                 'm3_edit'=>in_array($success,['archived','bulk_archived'],true) ? '' : $campaignId],
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
