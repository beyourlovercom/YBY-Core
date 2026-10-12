<?php
/** Andy Commerce Marketing M3: operator-friendly, OFF-only draft form. */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')) { wp_die(esc_html__('Insufficient permissions.', 'andy-commerce')); }
$mode = isset($mode) && $mode === 'revise' ? 'revise' : 'create';
$campaign = isset($campaign) && is_array($campaign) ? $campaign : array();
$revision = isset($revision) ? max(0, (int)$revision) : 0;
$rule = isset($campaign['rule']) && is_array($campaign['rule']) ? $campaign['rule'] : array();
$type = (string)($campaign['type'] ?? 'spend_save');
$currency = (string)($campaign['currency'] ?? 'USD');
$currency_options = array('USD'=>'USD · 美元','EUR'=>'EUR · 欧元','GBP'=>'GBP · 英镑','CAD'=>'CAD · 加元','AUD'=>'AUD · 澳元');
$type_options = array(
    'spend_save'=>'满减优惠 · Spend & Save',
    'bogo'=>'买赠 / 第二件优惠 · BOGO',
    'bundle'=>'组合捆绑优惠 · Bundle',
    'tiered'=>'多买多折 · Tiered',
    'free_gift'=>'满额赠礼 · Free Gift',
    'free_shipping'=>'满额包邮 · Free Shipping'
);
$minor = static function($v):string { return Andy_Commerce_M3_Human_Form::moneyLabel((int)$v); };
$pct = static function($v):string { return Andy_Commerce_M3_Human_Form::percentLabel((int)$v); };
$option = static function(int $id):void {
    $product = function_exists('wc_get_product') ? wc_get_product($id) : null;
    $title = $product && method_exists($product,'get_name') ? $product->get_name() : ('Product #'.$id);
    echo '<option value="'.esc_attr((string)$id).'" selected="selected">'.esc_html($title).' (#'.esc_html((string)$id).')</option>';
};
$product_select = static function(string $name,array $selected,bool $multiple=true) use ($option):void {
    printf(
        '<select class="wc-product-search m3-product" style="width:100%%;max-width:100%%" data-placeholder="%s" data-allow_clear="true" data-action="woocommerce_json_search_products_and_variations" name="%s"%s>',
        esc_attr__('搜索商品名称或 SKU…','andy-commerce'),
        esc_attr($name),
        $multiple ? ' multiple="multiple"' : ''
    );
    foreach ($selected as $id) { $option((int)$id); }
    echo '</select>';
};
$award_mode = isset($rule['discount_bps']) ? 'percent' : 'fixed';
$award_value = isset($rule['discount_bps']) ? $pct($rule['discount_bps']) : $minor($rule['discount_minor'] ?? 1000);
$countries = function_exists('WC') && WC() && WC()->countries ? WC()->countries->get_countries() : array('US'=>'United States','CA'=>'Canada','GB'=>'United Kingdom','DE'=>'Germany','FR'=>'France','AU'=>'Australia');
$shipping_choices = array();
if (class_exists('WC_Shipping_Zones')) {
    $zones = WC_Shipping_Zones::get_zones();
    $zones[0] = array('zone_name'=>'Rest of World','shipping_methods'=>WC_Shipping_Zones::get_zone(0)->get_shipping_methods());
    foreach ($zones as $zone) {
        if (!is_array($zone)) { continue; }
        foreach (($zone['shipping_methods'] ?? array()) as $method) {
            if (!is_object($method) || $method->id !== 'free_shipping') { continue; }
            $value = 'free_shipping:' . $method->instance_id;
            $shipping_choices[$value] = (string)($zone['zone_name'] ?? 'Zone').' / '.(string)$method->get_title();
        }
    }
}
?>
<style>
.m3-operator{max-width:1050px;font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif;color:#171717}
.m3-operator .m3-card{padding:20px 24px;border:1px solid #dadfda;background:#fff;border-radius:12px;margin:16px 0}
.m3-operator .m3-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.m3-operator .m3-field{min-width:0}
.m3-operator label.m3-label{display:block;font-size:14px;font-weight:600;margin-bottom:7px;color:#202820}
.m3-operator input:not([type=checkbox]),.m3-operator select:not(.wc-product-search),.m3-operator textarea{width:100%;max-width:100%;min-height:40px}
.m3-operator .m3-help{font-size:12px;color:#57635b;margin:5px 0 0}
.m3-operator .m3-summary{border:1px solid #c5d9cb;border-left:4px solid #006241;background:#f4faf5;padding:14px 18px;border-radius:7px;margin-top:16px}
.m3-operator .m3-rule[hidden]{display:none!important}
.m3-operator h3{margin:0 0 16px;font-size:16px}
.m3-operator .m3-soft{font-size:13px;color:#56625a}
.m3-operator .m3-primary{background:#006241!important;border-color:#006241!important;color:#fff!important}
.m3-operator .m3-repeat{border:1px solid #e0e4e1;border-radius:8px;padding:12px;margin-bottom:10px}
.m3-operator .m3-btn-row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.m3-operator .m3-rule{margin-top:8px}
@media(max-width:782px){.m3-operator .m3-grid{grid-template-columns:1fr}.m3-operator .m3-card{padding:16px}.m3-operator input,.m3-operator select{font-size:16px!important}}
</style>
<div class="m3-operator" id="m3-operator-form">
    <div class="m3-card">
      <h2 style="margin:0 0 6px"><?php echo esc_html($mode === 'create' ? '创建营销活动' : '编辑营销活动'); ?></h2>
      <p class="m3-soft">填写运营规则并保存草稿。当前仅支持草稿管理，所有优惠保持关闭，不影响现有 BYL49、WooCommerce 订单和支付。</p>
      <strong>状态：OFF · 草稿</strong>
      <?php if ($mode==='revise'): ?><p class="m3-soft">草稿编号 <?php echo esc_html((string)$campaign['id']); ?> · 版本 <?php echo esc_html((string)$revision); ?></p><?php endif; ?>
    </div>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="m3-draft-form">
      <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
      <input type="hidden" name="draft_action" value="<?php echo esc_attr($mode); ?>">
      <input type="hidden" name="campaign_fields[id]" value="<?php echo esc_attr((string)($campaign['id']??'')); ?>">
      <?php if ($mode==='revise'): ?><input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>"><?php endif; ?>
      <?php wp_nonce_field('andy_commerce_m3_draft_write'); ?>
      <div class="m3-card">
        <h3>1. 活动基本信息</h3>
        <div class="m3-grid">
          <div class="m3-field"><label class="m3-label" for="m3-name">活动名称</label>
            <input id="m3-name" type="text" name="campaign_fields[name]" value="<?php echo esc_attr((string)($campaign['name']??'')); ?>" maxlength="120" required placeholder="例如：黑五满100减10"></div>
          <div class="m3-field"><label class="m3-label" for="m3-type">优惠类型</label>
            <select id="m3-type" name="campaign_fields[type]" <?php echo $mode==='revise'?'disabled':''; ?>>
              <?php foreach($type_options as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($key,$type); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
            </select>
            <?php if ($mode==='revise'): ?><input type="hidden" name="campaign_fields[type]" value="<?php echo esc_attr($type); ?>"><p class="m3-help">已创建的活动不能更换优惠类型；可创建新的草稿。</p><?php endif; ?>
          </div>
          <div class="m3-field"><label class="m3-label" for="m3-currency">结算币种</label>
            <select name="campaign_fields[currency]" id="m3-currency">
              <?php foreach($currency_options as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($key,$currency); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
            </select></div>
          <div class="m3-field"><label class="m3-label" for="m3-priority">优先级</label>
            <input type="number" min="-1000" max="1000" step="1" name="campaign_fields[priority]" id="m3-priority" value="<?php echo esc_attr((string)($campaign['priority']??0)); ?>"><p class="m3-help">仅保存配置，目前不会影响现有优惠计算。</p></div>
        </div>
      </div>
      <div class="m3-card">
        <h3>2. 优惠规则 <span class="m3-soft">· 系统自动换算金额，无需填写 JSON</span></h3>
        <div class="m3-rule" data-m3-rule="spend_save">
          <div class="m3-grid">
            <div class="m3-field"><label class="m3-label">商品金额满（<?php echo esc_html($currency); ?>）</label>
              <input type="number" min="0.01" max="9999999999" step="0.01" name="campaign_fields[rule][threshold]" value="<?php echo esc_attr($minor($type==='spend_save'?($rule['threshold_minor']??10000):10000)); ?>" placeholder="100.00"></div>
            <div class="m3-field"><label class="m3-label">给予优惠</label>
              <div class="m3-grid" style="gap:8px;grid-template-columns:minmax(0,1fr) minmax(0,1fr)">
                <select name="campaign_fields[rule][award_mode]" data-m3-award><option value="fixed" <?php selected($award_mode,'fixed'); ?>>减固定金额</option><option value="percent" <?php selected($award_mode,'percent'); ?>>打折（%）</option></select>
                <input type="number" min="0.01" step="0.01" name="campaign_fields[rule][award_value]" data-m3-award-value value="<?php echo esc_attr($type==='spend_save'?$award_value:'10.00'); ?>">
              </div></div>
          </div>
          <p class="m3-help">按照折扣后、未含运费和税费的商品小计判断；现有 BYL49 免邮不受此草稿影响。</p>
          <details style="margin-top:12px"><summary>限定参与商品（可选，默认所有符合条件的商品）</summary><div style="padding:12px 0"><?php $product_select('campaign_fields[rule][product_ids][]',$type==='spend_save'?($rule['product_ids']??[]):[]); ?></div></details>
        </div>
        <div class="m3-rule" data-m3-rule="bogo" hidden>
          <div class="m3-grid">
            <div class="m3-field"><label class="m3-label">顾客需要购买的商品</label><?php $product_select('campaign_fields[rule][buy_ids][]',$type==='bogo'?($rule['buy_product_ids']??[]):[]); ?></div>
            <div class="m3-field"><label class="m3-label">获得优惠的商品</label><?php $product_select('campaign_fields[rule][get_ids][]',$type==='bogo'?($rule['get_product_ids']??[]):[]); ?></div>
            <div class="m3-field"><label class="m3-label">购买数量</label><input type="number" min="1" max="100" name="campaign_fields[rule][buy_qty]" value="<?php echo esc_attr((string)($type==='bogo'?($rule['buy_qty']??1):1)); ?>"></div>
            <div class="m3-field"><label class="m3-label">优惠件数</label><input type="number" min="1" max="100" name="campaign_fields[rule][get_qty]" value="<?php echo esc_attr((string)($type==='bogo'?($rule['get_qty']??1):1)); ?>"></div>
            <div class="m3-field"><label class="m3-label">优惠商品折扣比例（%）</label><input type="number" min="0.01" max="100" step="0.01" name="campaign_fields[rule][reward_percent]" value="<?php echo esc_attr($pct($type==='bogo'?($rule['reward_bps']??10000):10000)); ?>"><p class="m3-help">100% = 赠送，50% = 半价</p></div>
          </div>
        </div>
        <div class="m3-rule" data-m3-rule="bundle" hidden>
          <p class="m3-soft">选择至少两件不同商品组成组合，设定固定套装价或折扣百分比。</p>
          <div id="m3-bundle-rows">
          <?php $bundle_rows=$type==='bundle'?($rule['requires']??[]):[['product_id'=>0,'quantity'=>1],['product_id'=>0,'quantity'=>1]];foreach($bundle_rows as $entry): ?>
             <div class="m3-repeat m3-grid" data-m3-bundle-row>
               <div><label class="m3-label">组合商品</label><?php $product_select('campaign_fields[rule][product_ids][]',empty($entry['product_id'])?[]:[(int)$entry['product_id']],false); ?></div>
               <div><label class="m3-label">数量</label><input type="number" name="campaign_fields[rule][quantities][]" min="1" max="100" value="<?php echo esc_attr((string)($entry['quantity']??1)); ?>"></div>
             </div>
          <?php endforeach; ?>
          </div>
          <button type="button" class="button" id="m3-add-bundle">+ 添加组合商品</button>
          <div class="m3-grid" style="margin-top:16px">
            <div><label class="m3-label">优惠方式</label><select name="campaign_fields[rule][award_mode]" data-m3-award>
              <option value="bundle_price" <?php selected(isset($rule['bundle_price_minor']),'1'); ?>>固定套装价</option>
              <option value="percent" <?php selected(isset($rule['discount_bps']),'1'); ?>>组合打折 %</option>
            </select></div>
            <div><label class="m3-label">套装价 / 折扣 %</label><input type="number" min="0" step="0.01" name="campaign_fields[rule][award_value]" value="<?php echo esc_attr($type==='bundle'?(isset($rule['bundle_price_minor'])?$minor($rule['bundle_price_minor']):$pct($rule['discount_bps']??1000)):'99.00'); ?>"></div>
          </div>
        </div>
        <div class="m3-rule" data-m3-rule="tiered" hidden>
          <p class="m3-soft">每行填写一档数量优惠，按购买数量从小到大排列。</p>
          <div id="m3-tier-rows">
          <?php $tier_rows=$type==='tiered'?($rule['tiers']??[]):[['min_qty'=>2,'discount_bps'=>1000],['min_qty'=>3,'discount_bps'=>1500]];foreach($tier_rows as $tier): ?>
          <div class="m3-repeat m3-grid" data-m3-tier-row>
            <div><label class="m3-label">达到购买件数</label><input type="number" min="1" max="5000" name="campaign_fields[rule][tier_quantities][]" value="<?php echo esc_attr((string)$tier['min_qty']); ?>"></div>
            <div><label class="m3-label">奖励方式及金额</label><div class="m3-grid" style="gap:8px;grid-template-columns:1fr 1fr">
              <select name="campaign_fields[rule][tier_modes][]"><option value="percent" <?php selected(isset($tier['discount_bps'])); ?>>折扣 %</option><option value="fixed" <?php selected(isset($tier['discount_minor'])); ?>>固定减免</option></select>
              <input type="number" min="0.01" step="0.01" name="campaign_fields[rule][tier_values][]" value="<?php echo esc_attr(isset($tier['discount_bps'])?$pct($tier['discount_bps']):$minor($tier['discount_minor']??0)); ?>">
            </div></div>
          </div>
          <?php endforeach; ?>
          </div><button type="button" class="button" id="m3-add-tier">+ 添加数量档位</button>
          <details style="margin-top:12px"><summary>限定参与商品（可选）</summary><div style="padding:12px 0"><?php $product_select('campaign_fields[rule][product_ids][]',$type==='tiered'?($rule['product_ids']??[]):[]); ?></div></details>
        </div>
        <div class="m3-rule" data-m3-rule="free_gift" hidden>
          <div class="m3-grid">
            <div><label class="m3-label">商品金额满</label><input type="number" min="0.01" step="0.01" name="campaign_fields[rule][threshold]" value="<?php echo esc_attr($minor($type==='free_gift'?($rule['threshold_minor']??10000):10000)); ?>"></div>
            <div><label class="m3-label">赠品商品</label><?php $product_select('campaign_fields[rule][gift_id]',($type==='free_gift' && isset($rule['reward_product_id']))?[(int)$rule['reward_product_id']]:[],false); ?></div>
            <div><label class="m3-label">最多赠送数量</label><input type="number" min="1" max="5" name="campaign_fields[rule][gift_qty]" value="<?php echo esc_attr((string)($type==='free_gift'?($rule['max_gift_qty']??1):1)); ?>"></div>
          </div><p class="m3-help">这里只能保存草稿；不会实际赠送或占用库存。</p>
        </div>
        <div class="m3-rule" data-m3-rule="free_shipping" hidden>
          <div class="m3-grid">
            <div><label class="m3-label">商品金额满</label><input type="number" min="0.01" step="0.01" name="campaign_fields[rule][threshold]" value="<?php echo esc_attr($minor($type==='free_shipping'?($rule['threshold_minor']??4900):4900)); ?>"></div>
            <div><label class="m3-label">WooCommerce 免邮服务</label>
              <select name="campaign_fields[rule][shipping_method]">
              <option value="">请选择已配置的免邮方式</option>
              <?php foreach($shipping_choices as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected($rule['shipping_method_id']??'',$value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
              </select>
            </div>
          </div><p class="m3-help">受 BYL49 旧规则保护，仅保存草稿，不修改配送设置。</p>
        </div>
        <div class="m3-summary"><strong id="m3-human-summary">活动仅保存草稿，不会对顾客生效</strong><p class="m3-help">状态：OFF；不会改动真实购物车、订单、赠品或运费。</p></div>
      </div>
      <div class="m3-card">
        <h3>3. 活动适用范围（可选）</h3>
        <div class="m3-grid">
          <div><label class="m3-label">适用国家 / 地区</label>
            <select name="campaign_fields[countries][]" multiple="multiple" size="5" style="height:126px">
              <?php foreach($countries as $code=>$country): ?><option value="<?php echo esc_attr((string)$code); ?>" <?php selected(in_array($code,$campaign['countries']??[],true)); ?>><?php echo esc_html($country); ?></option><?php endforeach; ?>
            </select><p class="m3-help">不选择表示所有国家。最多选择 30 个。</p></div>
          <div><label class="m3-label">备注</label>
            <textarea name="campaign_fields[notes]" rows="4" maxlength="1000" placeholder="例如：黑五预案，等待审核"><?php echo esc_textarea((string)($campaign['notes']??'')); ?></textarea></div>
          <div><label class="m3-label">预计开始时间（UTC，可留空）</label><input type="datetime-local" name="campaign_fields[start_utc]" value="<?php echo esc_attr(substr((string)($campaign['start_utc']??''),0,16)); ?>"></div>
          <div><label class="m3-label">预计结束时间（UTC，可留空）</label><input type="datetime-local" name="campaign_fields[end_utc]" value="<?php echo esc_attr(substr((string)($campaign['end_utc']??''),0,16)); ?>"></div>
        </div>
        <p><label><input type="checkbox" name="campaign_fields[stack_with_coupon]" value="1" <?php checked($campaign['stack_with_coupon']??false); ?>> 将来允许与优惠码叠加（仅记录设置，尚不生效）</label></p>
      </div>
      <div class="m3-card m3-btn-row">
        <button type="submit" class="button button-primary button-hero m3-primary"><?php echo esc_html($mode==='create'?'保存为草稿':'保存修改'); ?></button>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=andy-commerce&tab=marketing-promotions')); ?>">返回活动列表</a>
        <span class="m3-soft">所有活动默认 OFF，发布功能尚未开放</span>
      </div>
    </form>
    <?php if ($mode==='revise' && !empty($campaign['id'])): ?>
    <div class="m3-card">
      <h3>归档该草稿</h3>
      <p class="m3-soft">归档后不在正常草稿列表中显示，保留历史记录。</p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="andy_commerce_m3_draft_request">
        <input type="hidden" name="draft_action" value="archive">
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr((string)$campaign['id']); ?>">
        <input type="hidden" name="expected_revision" value="<?php echo esc_attr((string)$revision); ?>">
        <?php wp_nonce_field('andy_commerce_m3_draft_write'); ?>
        <button type="submit" class="button" onclick="return confirm('确定归档此草稿？');">归档草稿</button>
      </form>
    </div>
    <?php endif; ?>
</div>
<script>
(function(){
  const form=document.getElementById('m3-draft-form');
  const type=document.getElementById('m3-type');
  const groups=Array.from(form.querySelectorAll('[data-m3-rule]'));
  const summary=document.getElementById('m3-human-summary');
  function currentType(){return type.disabled ? <?php echo wp_json_encode($type); ?> : type.value;}
  function refresh(){
    const t=currentType();groups.forEach(g=>{
      const active=g.dataset.m3Rule===t;g.hidden=!active;
      g.querySelectorAll('input,select,textarea').forEach(el=>el.disabled=!active);
    });
    const group=groups.find(g=>g.dataset.m3Rule===t);
    const val=(name)=>group?.querySelector('[name="campaign_fields[rule]['+name+']"]')?.value||'';
    const labels={spend_save:'满减',bogo:'买赠',bundle:'组合优惠',tiered:'阶梯优惠',free_gift:'满额赠礼',free_shipping:'满额包邮'};
    if(t==='spend_save') {
      const mode=val('award_mode'),amount=val('award_value'),currency=document.getElementById('m3-currency').value;
      summary.textContent='顾客商品金额满 '+currency+' '+val('threshold')+'，'+(mode==='percent'?'享 '+amount+'% 优惠':'减 '+currency+' '+amount)+' · 仅保存草稿';
    } else {summary.textContent=(labels[t]||'营销活动')+' · 未启用，仅保存草稿';}
  }
  type.addEventListener('change',refresh);form.addEventListener('input',refresh);
  const moneyLabel=()=>{};
  function addRow(buttonId,wrapperId,rowSelector){
    const button=document.getElementById(buttonId);const wrap=document.getElementById(wrapperId);
    if (!button || !wrap) return;
    button.addEventListener('click',()=>{
      if(wrap.children.length>=20) return;
      const first=wrap.querySelector(rowSelector);if(!first)return;
      const row=first.cloneNode(true);
      row.querySelectorAll('input').forEach(input=>input.value=input.type==='number'?'1':'');
      row.querySelectorAll('select').forEach(select=>{select.value='';select.querySelectorAll('option').forEach(o=>o.removeAttribute('selected'));});
      row.querySelectorAll('.select2,.select2-container').forEach(e=>e.remove());
      row.querySelectorAll('select').forEach(e=>{e.classList.remove('select2-hidden-accessible');e.removeAttribute('data-select2-id');e.removeAttribute('aria-hidden');});
      wrap.appendChild(row);
      if(window.jQuery)window.jQuery(document.body).trigger('wc-enhanced-select-init');
      refresh();
    });
  }
  addRow('m3-add-bundle','m3-bundle-rows','[data-m3-bundle-row]');
  addRow('m3-add-tier','m3-tier-rows','[data-m3-tier-row]');
  refresh();
})();
</script>
