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
$typeLabels=['spend_save'=>'满额立减','bogo'=>'买赠优惠','bundles'=>'组合优惠','tiered'=>'阶梯优惠','free_gift'=>'满额赠品'];
?>
<section id="andy-commerce-m7-studio" style="max-width:1360px;padding-right:18px">
<style>
#andy-commerce-m7-studio{font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",sans-serif;color:#263629;font-size:13px}
#andy-commerce-m7-studio h2{font-size:23px;font-weight:700;margin:15px 0 10px;color:#1d3427}
#andy-commerce-m7-studio h3{font-size:16px;font-weight:650;color:#1d4731}
#andy-commerce-m7-studio .postbox{border:1px solid #e1e8e2;border-radius:10px;box-shadow:0 1px 3px rgba(16,60,28,.05);background:#fff}
#andy-commerce-m7-studio .postbox p{line-height:1.7}
#andy-commerce-m7-studio .widefat th{background:#f2f7f3;padding:11px;color:#425d4b}
#andy-commerce-m7-studio .widefat td{padding:12px 11px;vertical-align:middle}
#andy-commerce-m7-studio label{font-size:12px;line-height:1.65}
#andy-commerce-m7-studio input,#andy-commerce-m7-studio select{max-width:100%;min-height:36px}
#andy-commerce-m7-studio .button-primary{background:#216948;border-color:#216948}
</style>
  <h2>营销 OS · 活动工作台</h2>
  <p class="description">开发环境草稿工作台。不会在前台执行折扣、优惠券、赠品或免邮，也不会修改订单。购物车、运费、税费及结账以 WooCommerce 为准。</p>
  <?php if (!$editable): ?>
    <div class="notice notice-warning inline"><p>当前为只读模式。仅授权 Dev 管理员可以保存草稿；所有活动仍未启用。</p></div>
  <?php endif; ?>
  <?php if (isset($_GET['m7_result'])): ?>
    <div class="notice notice-info inline"><p>草稿操作结果：<?php echo esc_html(['created'=>'已创建','saved'=>'已保存','archived'=>'已归档','conflict'=>'版本冲突','invalid'=>'数据无效','denied'=>'无操作权限'][$_GET['m7_result']]??sanitize_key(wp_unslash($_GET['m7_result']))); ?>。真实营销活动仍未启用。</p></div>
  <?php endif; ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,310px),1fr));gap:14px;margin:16px 0;">
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M4 · 运费政策对比</strong>
      <p>继续保留原有 BYL49 规则；折扣后基数仅作模拟，不自动将 $49 改为 $69。</p>
      <code>折扣前 → 折扣后（模拟）</code>
    </div>
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M5–M6 · 购物车与结账预览</strong>
      <p>只读读取 Woo 已选运费和折扣后商品金额；无法核实配送规则时不展示可能误导的免邮进度。</p>
      <code>赠品未启用 · 免邮奖励未启用</code>
    </div>
    <div class="postbox" style="padding:16px;margin:0">
      <strong>M7–M8 · 草稿与模拟</strong>
      <p>所有草稿均未启用，模拟购物车最多选用一项规则，不会实际发布优惠。</p>
      <code>买赠 · 组合优惠 · 满额立减 · 阶梯优惠</code>
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
  <h3>M4–M6 只读场景测试 <small>(模拟 $60 → $45，门槛 $49)</small></h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:12px;margin:12px 0 20px;">
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M4 · 金额达标对比</strong>
      <p>旧规则折扣前 $60：<strong><?php echo $shadow['legacy_eligible']?'模拟达标':'未达门槛'; ?></strong>.
         折扣后 $45：<strong><?php echo $shadow['proposed_eligible']?'模拟达标':'未达门槛'; ?></strong>.</p>
      <p><small>结果不同：<?php echo $shadow['disagreement']?'是':'否'; ?> · 实际规则修改：否</small></p>
    </div>
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M5 · 现行旧版运费政策</strong>
      <p>免邮进度：<strong><?php echo $legacyProgress['show_progress']?'显示':'隐藏'; ?></strong>.</p>
      <p><small>原因代码：<?php echo esc_html($legacyProgress['reason']??'n/a'); ?>. 不显示未经验证的免邮承诺。</small></p>
    </div>
    <div class="postbox" style="padding:14px;margin:0">
      <strong>M6 · 折扣后金额模拟</strong>
      <p>折扣后 $45 / 目标 $49：<strong><?php echo esc_html((string)($proposalProgress['percent']??0)); ?>%</strong>.
      还需：<strong>$<?php echo esc_html(number_format((($proposalProgress['remaining_minor']??0)/100),2)); ?></strong>.</p>
      <p><small>仅模拟 · 赠品及奖励未启用 · 不修改 Woo 已选运费</small></p>
    </div>
  </div>
  <div class="postbox" style="padding:16px;margin:14px 0;border-left:4px solid #176749">
    <strong>M9 · USD 多活动规则工作台</strong>
    <p>独立核算赠品、固定满减及各配送地区普通商品免邮资格，所有奖励保持关闭；货币换算由 Currency OS 负责。</p>
    <p><a class="button button-secondary" href="<?php echo esc_url(add_query_arg(['m9_preview'=>'1'],$view_url)); ?>">进入美元多规则工作台</a></p>
  </div>
  <h3>活动草稿 <small>(<?php echo esc_html((string)count($drafts)); ?> / 50 · 全部未启用)</small></h3>
  <div style="overflow-x:auto;">
    <table class="widefat striped">
      <thead><tr><th>活动名称</th><th>类型</th><th>国家／币种</th><th>优先级</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach($drafts as $id=>$draft): ?>
          <tr><td><strong><?php echo esc_html($draft['name']); ?></strong><br><small><?php echo esc_html($id); ?></small></td>
          <td><?php echo esc_html($typeLabels[$draft['type']]??$draft['type']); ?></td><td><?php echo esc_html(implode(', ',$draft['countries']).' / '.$draft['currency']); ?></td>
          <td><?php echo esc_html((string)$draft['priority']); ?></td><td><strong>未启用 · 草稿</strong></td>
          <td><a href="<?php echo esc_url(add_query_arg('m7_preview',$id,$view_url)); ?>">模拟预览</a>
          <?php if($editable): ?> · <a href="<?php echo esc_url(add_query_arg('m7_edit',$id,$view_url)); ?>">编辑</a>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
              <?php wp_nonce_field('andy_commerce_m7_archive_draft'); ?>
              <input type="hidden" name="action" value="andy_commerce_m7_archive_draft">
              <input type="hidden" name="draft_id" value="<?php echo esc_attr($id); ?>">
              <input type="hidden" name="draft_revision" value="<?php echo esc_attr((string)$draft['revision']); ?>">
              <button type="submit" class="button-link">归档</button>
            </form>
          <?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if(!$drafts): ?><tr><td colspan="6">暂无活动草稿；可在下方创建未启用草稿。</td></tr><?php endif; ?>
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
    <div class="notice notice-info inline"><p><strong>仅模拟：<?php echo esc_html($drafts[$preview_id]['name']); ?></strong><br>
      模拟购物车：商品 101 × 2、商品 202 × 1，折扣后商品金额 125.00（最小单位=12500）。
      预计减免（最小货币单位）：<strong><?php echo esc_html((string)$projection['projected_discount_minor']); ?></strong>.
      选中活动：<?php echo esc_html((string)($projection['selected_campaign']??'无')); ?>.
      实际优惠已执行：<strong>否</strong>； 赠品：<strong>未启用</strong>；运费：<strong>未修改</strong>.
    </p></div>
  <?php endif; ?>
  <?php if($editable): ?>
    <h3><?php echo $chosen?'编辑未启用草稿':'新建未启用草稿'; ?></h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:980px;">
      <?php wp_nonce_field('andy_commerce_m7_save_draft'); ?>
      <input type="hidden" name="action" value="andy_commerce_m7_save_draft">
      <input type="hidden" name="draft_id" value="<?php echo esc_attr($chosen['id']??''); ?>">
      <input type="hidden" name="draft_revision" value="<?php echo esc_attr((string)($chosen['revision']??0)); ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,235px),1fr));gap:10px;margin:10px 0;">
        <label>活动名称 <input class="regular-text" style="width:100%" required maxlength="120" name="draft_name" value="<?php echo esc_attr($chosen['name']??''); ?>"></label>
        <label>活动类型 <select name="draft_type" style="width:100%">
          <?php foreach(Andy_Commerce_M7_Draft_Studio::TYPES as $type): ?>
            <option value="<?php echo esc_attr($type); ?>" <?php selected($chosen['type']??'spend_save',$type); ?>><?php echo esc_html($typeLabels[$type]??$type); ?></option>
          <?php endforeach; ?></select></label>
        <label>活动币种 <input class="regular-text" style="width:100%" name="draft_currency" maxlength="3" value="<?php echo esc_attr($chosen['currency']??'USD'); ?>"></label>
        <label>适用国家（ISO，逗号分隔） <input class="regular-text" style="width:100%" name="draft_countries" value="<?php echo esc_attr(implode(',',$chosen['countries']??array('US'))); ?>"></label>
        <label>优先级（0–1000） <input type="number" min="0" max="1000" name="draft_priority" value="<?php echo esc_attr((string)($chosen['priority']??0)); ?>"></label>
      </div>
      <?php include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-v2-rule-editor.php'; ?>
      <p class="description">此处只能保存未启用草稿；不提供活动启用、真实折扣执行、赠品发放或运费修改接口。</p>
      <?php submit_button($chosen?'保存未启用草稿':'新建未启用草稿'); ?>
    </form>
  <?php endif; ?>
</section>
