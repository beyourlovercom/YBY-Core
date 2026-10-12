<?php
/**
 * M3 Draft-only admin-post controller.
 * INTENTIONALLY UNREGISTERED: no add_action() calls or runtime includes.
 * Integration must independently validate schema + Dev Owner UAT first.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M3_Draft_Request_Controller {
    private Andy_Commerce_M3_Draft_Admin_Adapter $adapter;
    public function __construct(Andy_Commerce_M3_Draft_Admin_Adapter $adapter) { $this->adapter = $adapter; }

    /**
     * Explicit route handler; never publish or activate.
     * Return sanitized results, never trust client status.
     */
    public function handle(array $post): array {
        if (!current_user_can('manage_woocommerce')) {
            throw new DomainException('Permission denied');
        }
        $nonce = isset($post['_wpnonce']) && is_string($post['_wpnonce']) ? $post['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'andy_commerce_m3_draft_write')) {
            throw new DomainException('Invalid nonce');
        }
        $action = isset($post['draft_action']) && is_string($post['draft_action']) ? $post['draft_action'] : '';
        if (!in_array($action, ['create','revise','archive'], true)) {
            throw new DomainException('Unsupported action');
        }
        $revision = filter_var($post['expected_revision'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'archive') {
            $id = $post['campaign_id'] ?? null;
            if (!is_string($id) || $revision === false || $revision < 1) {
                throw new DomainException('Invalid archive request');
            }
            $this->adapter->archive($id, $revision, $nonce);
            return ['status'=>'archived','active'=>false];
        }
        $json = $post['campaign_json'] ?? null;
        if (!is_string($json) || strlen($json) > 65536) {
            throw new DomainException('Invalid payload length');
        }
        try {
            $raw = json_decode($json, true, 40, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new DomainException('Invalid JSON', 0, $e);
        }
        if (!is_array($raw) || array_is_list($raw)) {
            throw new DomainException('Expected campaign object');
        }
        // Fail closed even if the caller attempts status or runtime escalation.
        if (($raw['status'] ?? null) !== 'draft' || ($raw['active'] ?? null) !== false) {
            throw new DomainException('Only inactive drafts can be saved');
        }
        if ($action === 'create') {
            return $this->adapter->create($raw, $nonce);
        }
        if ($revision === false || $revision < 1) {
            throw new DomainException('Invalid revision');
        }
        return $this->adapter->revise($raw, $revision, $nonce);
    }
}
