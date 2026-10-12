<?php
/**
 * M3 admin preview, inert unless separately wired by an approved bootstrap.
 * Drafts never publish. All promotion types stay OFF.
 */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) {
    wp_die(esc_html__('Insufficient permissions.', 'andy-commerce'));
}
$drafts = isset($drafts) && is_array($drafts) ? $drafts : [];
$notice = isset($notice) ? sanitize_text_field((string)$notice) : '';
?>
<div class="wrap andy-commerce-m3-drafts">
    <h2><?php esc_html_e('Saved inactive campaign drafts', 'andy-commerce'); ?></h2>
    <div class="notice notice-warning inline"><p><strong><?php esc_html_e('Draft only — every promotion is OFF.', 'andy-commerce'); ?></strong> <?php esc_html_e('Saving a draft never publishes, applies a discount, changes shipping, adds a gift, or changes WooCommerce orders.', 'andy-commerce'); ?></p></div>
    <?php if ($notice !== '') : ?>
        <div class="notice notice-info inline"><p><?php echo esc_html($notice); ?></p></div>
    <?php endif; ?>
    <p><a class="button button-primary" href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m3_edit'=>'new'], admin_url('admin.php'))); ?>">Create draft</a></p>
    <div style="max-width:100%;overflow-x:auto">
      <table class="widefat striped">
        <thead><tr>
          <th scope="col"><?php esc_html_e('Campaign ID', 'andy-commerce'); ?></th>
          <th scope="col"><?php esc_html_e('Name', 'andy-commerce'); ?></th>
          <th scope="col"><?php esc_html_e('Type', 'andy-commerce'); ?></th>
          <th scope="col"><?php esc_html_e('Revision', 'andy-commerce'); ?></th>
          <th scope="col"><?php esc_html_e('Status', 'andy-commerce'); ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($drafts as $draft) : ?>
            <?php if (($draft['status'] ?? '') !== 'draft' || !empty($draft['active'])) { continue; } ?>
            <tr>
              <td><a href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m3_edit'=>(string)($draft['campaign_id'] ?? '')], admin_url('admin.php'))); ?>"><code><?php echo esc_html((string)($draft['campaign_id'] ?? '')); ?></code></a></td>
              <td><?php echo esc_html((string)($draft['name'] ?? '')); ?></td>
              <td><?php echo esc_html((string)($draft['campaign_type'] ?? '')); ?></td>
              <td><?php echo esc_html((string)($draft['revision'] ?? '')); ?></td>
              <td><strong><?php esc_html_e('OFF — Draft', 'andy-commerce'); ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$drafts) : ?>
            <tr><td colspan="5"><?php esc_html_e('No saved drafts. Campaign execution is disabled.', 'andy-commerce'); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="description"><?php esc_html_e('All drafts stay OFF. Create, edit and archive are only available in the separately enabled Dev environment.', 'andy-commerce'); ?></p>
</div>
