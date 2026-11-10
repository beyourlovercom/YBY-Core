<?php
/**
 * Last-resort guarded price recovery when deleted through WordPress Plugins UI.
 * Manual FTP deletion cannot execute this file. Always End & Restore first.
 */
if (!defined('WP_UNINSTALL_PLUGIN') || !defined('ABSPATH')) { exit; }
require_once __DIR__.'/includes/class-byl-bf2026-r10-price-policy.php';
require_once __DIR__.'/includes/class-byl-bf2026-r10-sale-manager.php';
$ledger=BYL_BF2026_R10_Sale_Manager::ledger();
if (in_array(($ledger['state']??''), ['active','applying','restoring','conflicts'],true)) {
    $result=BYL_BF2026_R10_Sale_Manager::restore('WordPress_uninstall');
    if (empty($result['ok'])) {
        // Never erase the only price snapshot or silently allow irrecoverable
        // pricing divergence when Woo/warehouse/other editors have changed data.
        if (function_exists('wp_die')) {
            wp_die(
                'Black Friday price recovery is incomplete. Do not uninstall until all '
                .'products have been restored and conflicts reviewed. The snapshot remains saved.',
                'Black Friday recovery blocked',
                ['response'=>409]
            );
        }
        exit;
    }
}
// Keep the audit snapshot as recovery evidence; do not touch categories,
// gift pages, or the already-designed /holiday/black-friday/ page.
