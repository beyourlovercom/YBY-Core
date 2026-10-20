<?php
/** Wholesale Country Clone V2: Dev-only Project Studio admin view. */
if (!defined('ABSPATH')) { exit; }
?>
<div class="wrap yby-project-studio yby-wholesale-country-v2" style="max-width:1100px">
<h1><?php esc_html_e('Wholesale Country Clone V2', 'yby-core'); ?></h1>
<p class="description">B2B Dealer / Distributor landing only. Source: Dev Wholesale #215. Solution and 1-hectare pages are excluded. Production and Google Ads are not changed.</p>
<p><a class="button" href="<?php echo esc_url(YBY_Project_Studio::studio_url('list')); ?>">← Project Studio</a></p>
<?php if (is_wp_error($preflight)) : ?>
<div class="notice notice-error"><p><?php echo esc_html($preflight->get_error_message()); ?></p></div>
<?php else : ?>
<div class="card" style="max-width:100%;padding:16px">
  <h2>Country Draft — Source Check</h2>
  <p><strong>Bricks source:</strong> #215 · <strong>Native signed Code nodes expected:</strong> 2 · <strong>Source hash:</strong> <code><?php echo esc_html(substr($preflight['source_hash'], 0, 16)); ?>…</code></p>
  <p><strong>Historical source signatures needing replacement:</strong> <?php echo esc_html((string)$preflight['source_stale_signatures']); ?>; new Draft Code is always natively signed and verified.</p>
  <form method="get">
    <input type="hidden" name="page" value="<?php echo esc_attr(YBY_Project_Studio::studio_page_slug()); ?>">
    <input type="hidden" name="view" value="wholesale">
    <label for="yby-wholesale-country">Target country:</label>
    <select id="yby-wholesale-country" name="country">
      <?php foreach ($countries as $code=>$name) : ?>
      <option value="<?php echo esc_attr($code); ?>" <?php selected($country, $code); ?>><?php echo esc_html($name . ' (' . $code . ')'); ?></option>
      <?php endforeach; ?>
    </select>
    <?php submit_button('Check country', 'secondary', 'check', false); ?>
  </form>
  <p><strong>Planned Project:</strong> <code><?php echo esc_html($preflight['target_project_slug']); ?></code> · <strong>Landing:</strong> <code><?php echo esc_html($preflight['target_page_slug']); ?></code></p>
  <?php if ($may_write) : ?>
  <form method="post">
    <?php wp_nonce_field('yby_wholesale_clone_v2_'.$country); ?>
    <input type="hidden" name="country" value="<?php echo esc_attr($country); ?>">
    <input type="hidden" name="source_fingerprint" value="<?php echo esc_attr($preflight['fingerprint']); ?>">
    <?php submit_button('Create / Open ' . $preflight['country'] . ' Draft', 'primary', 'yby_wholesale_create'); ?>
  </form>
  <?php else : ?>
  <p><strong>Draft writes OFF.</strong> Operator must explicitly enable the isolated Dev environment flag; no Production action is possible through this view.</p>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php if (isset($clone_result) && is_wp_error($clone_result)) : ?>
<div class="notice notice-error"><p><?php echo esc_html($clone_result->get_error_message()); ?></p></div>
<?php elseif (!empty($clone_result['page_id'])) : ?>
<div class="notice notice-success"><p>
<?php echo esc_html(!empty($clone_result['reused']) ? 'Existing Draft reused. ' : 'New Draft created. '); ?>
<a href="<?php echo esc_url(get_edit_post_link($clone_result['page_id'])); ?>">Edit Landing #<?php echo esc_html((string)$clone_result['page_id']); ?></a> ·
<a href="<?php echo esc_url(get_preview_post_link($clone_result['page_id'])); ?>" target="_blank" rel="noopener">Preview Landing</a> ·
<a href="<?php echo esc_url(get_edit_post_link($clone_result['project_id'])); ?>">Project #<?php echo esc_html((string)$clone_result['project_id']); ?></a>
</p></div>
<?php endif; ?>
<h2>Existing country Drafts (Dev only)</h2>
<table class="widefat striped"><thead><tr><th>Country</th><th>Project</th><th>Landing</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($existing as $row) :
$page_id=(int)get_post_meta($row->ID,'yby_clone_v2_page_id',true);
$p=$page_id?get_post($page_id):null;
if (!$p || $p->post_type!=='landing_page') { continue; }
?>
<tr><td><?php echo esc_html((string)get_post_meta($row->ID,'yby_country',true)); ?></td>
<td>#<?php echo esc_html((string)$row->ID); ?></td><td>#<?php echo esc_html((string)$page_id); ?></td>
<td><?php echo esc_html((string)$p->post_status); ?></td>
<td><a href="<?php echo esc_url(get_preview_post_link($page_id)); ?>" target="_blank" rel="noopener">Preview</a> · <a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>">Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table>
<p class="description">UAT gate: Source #215, Thank You #310 and historical #699 remain unchanged. No automatic publication, email, real form submission or Ads activation.</p>
</div>
