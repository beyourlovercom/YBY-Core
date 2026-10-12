<?php
/**
 * M3 draft editor prototype. This template is NOT included by live bootstrap.
 * A separately approved WordPress controller must provide $campaign and $mode.
 */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) {
    wp_die(esc_html__('Insufficient permissions.', 'andy-commerce'));
}
$mode = isset($mode) && $mode === 'revise' ? 'revise' : 'create';
$campaign = isset($campaign) && is_array($campaign) ? $campaign : [];
$revision = isset($revision) ? max(0, (int)$revision) : 0;
$draft_json = wp_json_encode($campaign, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
<div class="wrap andy-commerce-m3-editor">
<h1><?php echo esc_html($mode === 'create' ? 'Create inactive campaign draft' : 'Edit inactive campaign draft'); ?></h1>
<div class="notice notice-warning inline"><p><strong>Draft only — OFF.</strong> Saving cannot publish discounts, gifts or shipping rules. BYL49 is not changed.</p></div>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
    <input type="hidden" name="draft_action" value="<?php echo esc_attr($mode); ?>">
    <?php wp_nonce_field('andy_commerce_m3_draft_write'); ?>
    <?php if ($mode === 'revise') : ?>
    <input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>">
    <?php endif; ?>
    <p><label for="m3-campaign-json"><strong>Draft configuration (JSON)</strong></label></p>
    <p class="description">Allowed types: BOGO, Bundle, Spend & Save, Tiered, Free Gift, Free Shipping. The campaign must contain status="draft" and active=false. JSON is validated on the server.</p>
    <textarea id="m3-campaign-json" name="campaign_json" rows="16" maxlength="65536" style="display:block;width:100%;max-width:920px;font-family:monospace" spellcheck="false"><?php echo esc_textarea((string)$draft_json); ?></textarea>
    <?php submit_button($mode === 'create' ? 'Save inactive draft' : 'Save draft revision', 'primary'); ?>
</form>
<?php if ($mode === 'revise' && isset($campaign['id']) && is_string($campaign['id'])) : ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
      <input type="hidden" name="draft_action" value="archive">
      <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign['id']); ?>">
      <input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>">
      <?php wp_nonce_field('andy_commerce_m3_draft_write'); ?>
      <?php submit_button('Archive draft', 'secondary'); ?>
    </form>
<?php endif; ?>
<p><a href="<?php echo esc_url(admin_url('admin.php?page=andy-commerce&tab=marketing-promotions')); ?>">Return to Marketing / Promotions</a></p>
</div>
