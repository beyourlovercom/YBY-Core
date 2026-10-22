<?php
/** M7–M8 isolated Dev-only draft CRUD and simulation; NEVER publishes offers. */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) return;
$drafts=Andy_Commerce_M7_Draft_Studio::rows();
$editable=Andy_Commerce_M7_Draft_Studio::dev_only();
$edit_id=isset($_GET['m7_edit'])?sanitize_key(wp_unslash($_GET['m7_edit'])):'';
$chosen=$edit_id && isset($drafts[$edit_id]) ? $drafts[$edit_id] : null;
$rule=$chosen ? $chosen['rule'] : array('threshold_minor'=>6900,'discount_minor'=>500);
$view_url=add_query_arg(array('page'=>'andy-commerce','tab'=>'marketing-promotions'),admin_url('admin.php'));
$preview_id=isset($_GET['m7_preview'])?sanitize_key(wp_unslash($_GET['m7_preview'])):'';
?>
<section id="andy-commerce-m7-studio" style="max-width:1080px">
  <h2>Marketing Studio — M4–M8</h2>
  <p class="description">DEV draft workbench. No storefront discount, coupon, gift, free-shipping award or order mutation. WooCommerce remains the only cart, rate, tax and checkout authority.</p>
  <?php if (!$editable): ?>
    <div class="notice notice-warning inline"><p>Read-only here. Draft writes are available on an isolated Dev WordPress site only; all promotions stay OFF.</p></div>
  <?php endif; ?>
  <?php if (isset($_GET['m7_result'])): ?>
    <div class="notice notice-info inline"><p>Draft action: <?php echo esc_html(sanitize_key(wp_unslash($_GET['m7_result']))); ?>. Live promotions unchanged (OFF).</p></div>
  <?php endif; ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,310px),1fr));gap:14px;margin:16px 0;">
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M4 · Shipping policy comparison</strong>
      <p>Legacy BYL49 is authoritative. The proposed post-discount basis is shadow only. No automatic $49 → $69 switch.</p>
      <code>pre-discount → post-discount (SHADOW)</code>
    </div>
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M5–M6 · Cart / Checkout projection</strong>
      <p>Read-only Woo selected-rate adapter, with verified discounted lines. Free-shipping progress hides if policy or selected shipping rate is unconfirmed.</p>
      <code>Gift OFF · Shipping award OFF</code>
    </div>
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M7–M8 · Draft and preview</strong>
      <p>All drafts inactive. The discount evaluator chooses at most one deterministic campaign for a simulated cart. No Woo hooks publish discounts.</p>
      <code>BOGO · Bundles · Spend &amp; Save · Tiered</code>
    </div>
  </div>
  <?php
    // Server-rendered synthetic UAT fixtures. Never derive a shipping award
    // from these values; the current legacy Woo policy remains authoritative.
    $shadow=Andy_Commerce_M4_Shipping_Shadow::compare(
      array(array('line_subtotal_minor'=>6000,'line_total_minor'=>4500,
        'gift'=>false,'eligible'=>true)),4900);
    $fixtureCart=array(
      'items'=>array(array('totals'=>array('line_total'=>'4500'))),
      'totals'=>array('currency_minor_unit'=>2),
      'shipping_rates'=>array(array('shipping_rates'=>array(
        array('rate_id'=>'flat_rate:7','selected'=>true)
      ))),
    );
    $legacySource=Andy_Commerce_M5_Woo_Cart_Adapter::source(
      $fixtureCart,'pre_discount_merchandise_subtotal',4900,true);
    $proposalSource=Andy_Commerce_M5_Woo_Cart_Adapter::source(
      $fixtureCart,'post_discount_merchandise_subtotal_excluding_tax_and_shipping',4900,true);
    $legacyProgress=Andy_Commerce_M5_Progress_Projection::project($legacySource);
    $proposalProgress=Andy_Commerce_M5_Progress_Projection::project($proposalSource);
  ?>
  <h3>M4–M6 Read-only scenario test <small>(synthetic $60 → $45, threshold $49)</small></h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:12px;margin:12px 0 20px;">
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M4 · Qualification comparison</strong>
      <p>Legacy subtotal $60: <strong><?php echo $shadow['legacy_eligible']?'Eligible':'Not eligible'; ?></strong>.
         Discounted subtotal $45: <strong><?php echo $shadow['proposed_eligible']?'Eligible':'Not eligible'; ?></strong>.</p>
      <p><small>Disagreement: <?php echo $shadow['disagreement']?'YES':'NO'; ?> · Change applied: NO</small></p>
    </div>
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M5 · Existing live legacy policy</strong>
      <p>Shipping progress: <strong><?php echo $legacyProgress['show_progress']?'Displayed':'Hidden'; ?></strong>.</p>
      <p><small>Reason: <?php echo esc_html($legacyProgress['reason']??'n/a'); ?>. No misleading free-shipping claim.</small></p>
    </div>
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M6 · Future post-discount projection</strong>
      <p>Discounted $45 / target $49: <strong><?php echo esc_html((string)($proposalProgress['percent']??0)); ?>%</strong>.
      Remaining: <strong>$<?php echo esc_html(number_format((($proposalProgress['remaining_minor']??0)/100),2)); ?></strong>.</p>
      <p><small>Preview only · Gift OFF · Award OFF · Woo selected rate never rewritten</small></p>
    </div>
  </div>
  <h3>Campaign drafts <small>(<?php echo esc_html((string)count($drafts)); ?> / 50 · ALL OFF)</small></h3>
  <div style="overflow-x:auto;">
    <table class="widefat striped">
      <thead><tr><th>Campaign</th><th>Type</th><th>Countries / Currency</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($drafts as $id=>$draft): ?>
          <tr><td><strong><?php echo esc_html($draft['name']); ?></strong><br><small><?php echo esc_html($id); ?></small></td>
          <td><?php echo esc_html($draft['type']); ?></td><td><?php echo esc_html(implode(', ',$draft['countries']).' / '.$draft['currency']); ?></td>
          <td><?php echo esc_html((string)$draft['priority']); ?></td><td><strong>OFF — Draft</strong></td>
          <td><a href="<?php echo esc_url(add_query_arg('m7_preview',$id,$view_url)); ?>">Preview</a>
          <?php if($editable): ?> · <a href="<?php echo esc_url(add_query_arg('m7_edit',$id,$view_url)); ?>">Edit</a>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
              <?php wp_nonce_field('andy_commerce_m7_archive_draft'); ?>
              <input type="hidden" name="action" value="andy_commerce_m7_archive_draft">
              <input type="hidden" name="draft_id" value="<?php echo esc_attr($id); ?>">
              <input type="hidden" name="draft_revision" value="<?php echo esc_attr((string)$draft['revision']); ?>">
              <button type="submit" class="button-link">Archive</button>
            </form>
          <?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if(!$drafts): ?><tr><td colspan="6">No drafts. Create a Dev-only inactive draft below.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($preview_id && isset($drafts[$preview_id])): ?>
    <?php
      $snapshot=array('currency'=>$drafts[$preview_id]['currency'],
        'country'=>$drafts[$preview_id]['countries'][0]??'US','manual_coupon'=>null,
        'items'=>array(array('product_id'=>101,'quantity'=>2,'discounted_line_minor'=>8000),
                       array('product_id'=>202,'quantity'=>1,'discounted_line_minor'=>4500)));
      $projection=Andy_Commerce_M8_Promotion_Evaluator::preview($snapshot,array($drafts[$preview_id]));
    ?>
    <div class="notice notice-info inline"><p><strong>SIMULATION ONLY: <?php echo esc_html($drafts[$preview_id]['name']); ?></strong><br>
      Cart fixture: product 101 × 2 / product 202 × 1, discounted merchandise 125.00 (minor=12500).
      Projected discount (minor): <strong><?php echo esc_html((string)$projection['projected_discount_minor']); ?></strong>.
      Selected: <?php echo esc_html((string)($projection['selected_campaign']??'none')); ?>.
      Actual discount applied: <strong>NO</strong>. Gift: <strong>OFF</strong>. Shipping: <strong>UNCHANGED</strong>.
    </p></div>
  <?php endif; ?>
  <?php if($editable): ?>
    <h3><?php echo $chosen?'Edit inactive draft':'Create inactive draft'; ?></h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:980px;">
      <?php wp_nonce_field('andy_commerce_m7_save_draft'); ?>
      <input type="hidden" name="action" value="andy_commerce_m7_save_draft">
      <input type="hidden" name="draft_id" value="<?php echo esc_attr($chosen['id']??''); ?>">
      <input type="hidden" name="draft_revision" value="<?php echo esc_attr((string)($chosen['revision']??0)); ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,235px),1fr));gap:10px;margin:10px 0;">
        <label>Campaign name <input class="regular-text" style="width:100%" required maxlength="120" name="draft_name" value="<?php echo esc_attr($chosen['name']??''); ?>"></label>
        <label>Campaign type <select name="draft_type" style="width:100%">
          <?php foreach(Andy_Commerce_M7_Draft_Studio::TYPES as $type): ?>
            <option value="<?php echo esc_attr($type); ?>" <?php selected($chosen['type']??'spend_save',$type); ?>><?php echo esc_html($type); ?></option>
          <?php endforeach; ?></select></label>
        <label>Currency <input class="regular-text" style="width:100%" name="draft_currency" maxlength="3" value="<?php echo esc_attr($chosen['currency']??'USD'); ?>"></label>
        <label>Countries (ISO, commas) <input class="regular-text" style="width:100%" name="draft_countries" value="<?php echo esc_attr(implode(',',$chosen['countries']??array('US'))); ?>"></label>
        <label>Priority (0–1000) <input type="number" min="0" max="1000" name="draft_priority" value="<?php echo esc_attr((string)($chosen['priority']??0)); ?>"></label>
      </div>
      <?php include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-v2-rule-editor.php'; ?>
      <p class="description">Only drafts can be saved. Campaign activation, Store API discount execution, gift awards and real shipping changes have no write endpoints in this studio.</p>
      <?php submit_button($chosen?'Save inactive draft':'Create inactive draft'); ?>
    </form>
  <?php endif; ?>
</section>
