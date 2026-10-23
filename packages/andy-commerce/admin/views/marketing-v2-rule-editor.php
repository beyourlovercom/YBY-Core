<?php
/** M1 visual draft-only rule editor. $rule and $chosen supplied by Studio form. */
if (!defined('ABSPATH')) exit;
$rule_type = $chosen['type']??'spend_save';
$money = static fn($v)=>Andy_Commerce_Marketing_V2_Rule_Fields::major($v);
$percent = static fn($v)=>Andy_Commerce_Marketing_V2_Rule_Fields::percentage($v);
$discount_mode=isset($rule['percent_bp'])?'percent':'fixed';
$label = static function(string $title,string $field,string $value,string $hint='') {
    echo '<label class="ac-v2-field"><span>'.esc_html($title).'</span><input type="text" inputmode="decimal" name="'.esc_attr($field).'" value="'.esc_attr($value).'">';
    if($hint!=='')echo '<small>'.esc_html($hint).'</small>';
    echo '</label>';
};
?>
<style>
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-form{border:1px solid #dbe6de;border-radius:10px;padding:16px;margin:14px 0;background:#fff;max-width:1280px;box-shadow:0 1px 3px rgba(25,60,32,.05)}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:12px 0}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-field{display:grid;gap:5px;min-width:0}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-field input,:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-field select{box-sizing:border-box;width:100%;min-width:0;max-width:none;height:37px}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-field small{color:#646970;font-size:12px}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-note{color:#646970;font-size:12px;margin:6px 0}
:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-section[hidden],:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-advanced[hidden]{display:none!important}
@media(max-width:700px){:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-form{padding:12px}:is(#andy-commerce-m7-studio,#andy-commerce-m10-workbench) .ac-v2-fields{grid-template-columns:1fr}}
</style>
<section class="ac-v2-form" id="andy-commerce-v2-rule-editor" aria-label="活动规则可视化编辑器">
    <h4 style="margin:0 0 10px;font-size:16px;color:#1e5437">活动规则 <small style="font-weight:400;color:#646970">· 营销 V2 预览</small></h4>
    <label class="ac-v2-field" style="max-width:330px"><span>编辑方式</span>
        <select name="draft_rule_editor" id="ac-v2-editor-mode">
            <option value="visual" selected>可视化字段（推荐）</option>
            <option value="advanced">高级 JSON（兼容旧草稿）</option>
        </select>
    </label>
    <div id="ac-v2-visual">
        <section class="ac-v2-section" data-rule-type="spend_save" <?php if($rule_type!=='spend_save')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('满额门槛（USD）', 'draft_rule_fields[spend_save][threshold]', $money($rule_type==='spend_save'?($rule['threshold_minor']??6900):6900), '按折扣后的商品金额判断；统一以 USD 为基准'); ?>
                <label class="ac-v2-field"><span>优惠类型</span>
                    <select name="draft_rule_fields[spend_save][discount_mode]">
                        <option value="fixed" <?php selected($rule_type==='spend_save'?$discount_mode:'fixed','fixed'); ?>>固定金额</option>
                        <option value="percent" <?php selected($rule_type==='spend_save'?$discount_mode:'fixed','percent'); ?>>百分比</option>
                    </select>
                </label>
                <?php $label('固定减免金额（USD）', 'draft_rule_fields[spend_save][fixed]', $money($rule_type==='spend_save'?($rule['discount_minor']??500):500)); ?>
                <?php $label('优惠百分比', 'draft_rule_fields[spend_save][percent]', $percent($rule_type==='spend_save'?($rule['percent_bp']??1000):1000),'0–100%'); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="bogo" <?php if($rule_type!=='bogo')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('购买商品 ID','draft_rule_fields[bogo][buy_product_id]',(string)($rule_type==='bogo'?($rule['buy_product_id']??101):101)); ?>
                <?php $label('购买数量','draft_rule_fields[bogo][buy_qty]',(string)($rule_type==='bogo'?($rule['buy_qty']??1):1)); ?>
                <?php $label('奖励商品 ID','draft_rule_fields[bogo][get_product_id]',(string)($rule_type==='bogo'?($rule['get_product_id']??202):202),'不能与购买商品相同'); ?>
                <?php $label('奖励数量','draft_rule_fields[bogo][get_qty]',(string)($rule_type==='bogo'?($rule['get_qty']??1):1)); ?>
                <label class="ac-v2-field"><span>优惠类型</span>
                    <select name="draft_rule_fields[bogo][discount_mode]"><option value="percent" <?php selected($rule_type==='bogo'?$discount_mode:'percent','percent'); ?>>百分比</option><option value="fixed" <?php selected($rule_type==='bogo'?$discount_mode:'percent','fixed'); ?>>每件固定减免</option></select>
                </label>
                <?php $label('百分比','draft_rule_fields[bogo][percent]',$percent($rule_type==='bogo'?($rule['percent_bp']??5000):5000)); ?>
                <?php $label('固定减免金额（USD）','draft_rule_fields[bogo][fixed]',$money($rule_type==='bogo'?($rule['discount_minor']??500):500)); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="bundles" <?php if($rule_type!=='bundles')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('组合商品 ID','draft_rule_fields[bundles][product_ids]',implode(',',$rule_type==='bundles'?($rule['product_ids']??[101,202]):[101,202]),'填写 2–20 个不同商品 ID，使用半角逗号分隔'); ?>
                <label class="ac-v2-field"><span>优惠类型</span><select name="draft_rule_fields[bundles][discount_mode]"><option value="fixed" <?php selected($rule_type==='bundles'?$discount_mode:'fixed','fixed'); ?>>固定金额</option><option value="percent" <?php selected($rule_type==='bundles'?$discount_mode:'fixed','percent'); ?>>百分比</option></select></label>
                <?php $label('固定减免金额（USD）','draft_rule_fields[bundles][fixed]',$money($rule_type==='bundles'?($rule['discount_minor']??500):500)); ?>
                <?php $label('百分比','draft_rule_fields[bundles][percent]',$percent($rule_type==='bundles'?($rule['percent_bp']??1000):1000)); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="tiered" <?php if($rule_type!=='tiered')echo 'hidden'; ?>>
            <p class="ac-v2-note">按购买总件数设置阶梯，后一档数量必须高于前一档。</p>
            <div class="ac-v2-fields">
                <?php
                $tiers=$rule_type==='tiered'?($rule['tiers']??[]):[];
                for($i=1;$i<=3;$i++):
                    $row=$tiers[$i-1]??[];
                    $qty=(string)($row['minimum_qty']??($i===1?2:''));
                    $pct=$percent($row['percent_bp']??($i===1?1000:null));
                ?>
                    <?php $label('第 '.$i.' · 最低数量','draft_rule_fields[tiered][minimum_qty_'.$i.']',$qty); ?>
                    <?php $label('第 '.$i.' · 优惠百分比','draft_rule_fields[tiered][percent_'.$i.']',$pct); ?>
                <?php endfor; ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="free_gift" <?php if($rule_type!=='free_gift')echo 'hidden'; ?>>
            <p><strong>赠品发放仍未启用。</strong> 当前仅保存供后续审核的草稿，不会实际赠送商品。</p>
            <div class="ac-v2-fields">
                <?php $label('赠品门槛（USD）','draft_rule_fields[free_gift][threshold]',
                    $money($rule_type==='free_gift'?($rule['threshold_minor']??9900):9900),
                    '只模拟资格；不会向 Woo 购物车添加赠品'); ?>
                <?php $label('赠品商品 ID','draft_rule_fields[free_gift][product_id]',
                    (string)($rule_type==='free_gift'?($rule['product_id']??''):''),
                    '实际发布前需验证商品库存'); ?>
            </div>
        </section>
    </div>
    <div class="ac-v2-advanced" hidden>
        <label class="ac-v2-field"><span>规则 JSON（高级模式，以最小货币单位表示）</span>
            <textarea name="draft_rule_json" rows="6" maxlength="3000" style="width:100%;font-family:monospace"><?php echo esc_textarea(wp_json_encode($rule,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></textarea>
        </label>
        <p class="ac-v2-note">高级模式保留旧草稿结构。仅在可视化表单无法表达现有规则时使用。</p>
    </div>
    <p class="ac-v2-note"><strong>默认未启用。</strong> 此编辑器不会产生真实优惠、赠品、运费变化或订单写入。</p>
</section>
<script>
(function(){
    'use strict';
    var root=document.getElementById('andy-commerce-m7-studio')||document.getElementById('andy-commerce-m10-workbench');
    var panel=document.getElementById('andy-commerce-v2-rule-editor');
    if(!root||!panel)return;
    var form=panel.closest('form');
    var type=form&&form.querySelector('select[name="draft_type"]');
    var editor=form&&form.querySelector('select[name="draft_rule_editor"]');
    var visual=panel.querySelector('#ac-v2-visual');
    var advanced=panel.querySelector('.ac-v2-advanced');
    if(!type||!editor||!visual||!advanced)return;
    function sync(){
        var isVisual=editor.value==='visual';
        visual.hidden=!isVisual;
        advanced.hidden=isVisual;
        panel.querySelectorAll('.ac-v2-section').forEach(function(section){
            section.hidden=section.getAttribute('data-rule-type')!==type.value;
        });
    }
    type.addEventListener('change',sync);
    editor.addEventListener('change',sync);
    sync();
})();
</script>
