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
#andy-commerce-m7-studio .ac-v2-form{border:1px solid #dcdcde;border-radius:12px;padding:16px;margin:14px 0;background:#fff;max-width:900px}
#andy-commerce-m7-studio .ac-v2-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:12px;margin:12px 0}
#andy-commerce-m7-studio .ac-v2-field{display:grid;gap:5px;min-width:0}
#andy-commerce-m7-studio .ac-v2-field input,#andy-commerce-m7-studio .ac-v2-field select{box-sizing:border-box;width:100%;min-width:0;max-width:none;height:38px}
#andy-commerce-m7-studio .ac-v2-field small{color:#646970;font-size:12px}
#andy-commerce-m7-studio .ac-v2-note{color:#646970;font-size:12px;margin:6px 0}
#andy-commerce-m7-studio .ac-v2-section[hidden],#andy-commerce-m7-studio .ac-v2-advanced[hidden]{display:none!important}
@media(max-width:700px){#andy-commerce-m7-studio .ac-v2-form{padding:12px}#andy-commerce-m7-studio .ac-v2-fields{grid-template-columns:1fr}}
</style>
<section class="ac-v2-form" id="andy-commerce-v2-rule-editor" aria-label="Marketing campaign rule editor">
    <h4 style="margin:0 0 10px;font-size:15px">Promotion rules <small style="font-weight:400;color:#646970">· Marketing V2 Preview</small></h4>
    <label class="ac-v2-field" style="max-width:330px"><span>Editing mode</span>
        <select name="draft_rule_editor" id="ac-v2-editor-mode">
            <option value="visual" selected>Visual fields (recommended)</option>
            <option value="advanced">Advanced JSON (existing format)</option>
        </select>
    </label>
    <div id="ac-v2-visual">
        <section class="ac-v2-section" data-rule-type="spend_save" <?php if($rule_type!=='spend_save')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('Spend at least', 'draft_rule_fields[spend_save][threshold]', $money($rule_type==='spend_save'?($rule['threshold_minor']??6900):6900), 'Store currency; discounted merchandise'); ?>
                <label class="ac-v2-field"><span>Discount kind</span>
                    <select name="draft_rule_fields[spend_save][discount_mode]">
                        <option value="fixed" <?php selected($rule_type==='spend_save'?$discount_mode:'fixed','fixed'); ?>>Fixed amount</option>
                        <option value="percent" <?php selected($rule_type==='spend_save'?$discount_mode:'fixed','percent'); ?>>Percentage</option>
                    </select>
                </label>
                <?php $label('Fixed discount', 'draft_rule_fields[spend_save][fixed]', $money($rule_type==='spend_save'?($rule['discount_minor']??500):500)); ?>
                <?php $label('Discount percentage', 'draft_rule_fields[spend_save][percent]', $percent($rule_type==='spend_save'?($rule['percent_bp']??1000):1000),'0–100%'); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="bogo" <?php if($rule_type!=='bogo')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('Buy product ID','draft_rule_fields[bogo][buy_product_id]',(string)($rule_type==='bogo'?($rule['buy_product_id']??101):101)); ?>
                <?php $label('Buy quantity','draft_rule_fields[bogo][buy_qty]',(string)($rule_type==='bogo'?($rule['buy_qty']??1):1)); ?>
                <?php $label('Reward product ID','draft_rule_fields[bogo][get_product_id]',(string)($rule_type==='bogo'?($rule['get_product_id']??202):202),'Must differ from buy product'); ?>
                <?php $label('Reward quantity','draft_rule_fields[bogo][get_qty]',(string)($rule_type==='bogo'?($rule['get_qty']??1):1)); ?>
                <label class="ac-v2-field"><span>Discount kind</span>
                    <select name="draft_rule_fields[bogo][discount_mode]"><option value="percent" <?php selected($rule_type==='bogo'?$discount_mode:'percent','percent'); ?>>Percentage</option><option value="fixed" <?php selected($rule_type==='bogo'?$discount_mode:'percent','fixed'); ?>>Fixed amount per unit</option></select>
                </label>
                <?php $label('Percentage','draft_rule_fields[bogo][percent]',$percent($rule_type==='bogo'?($rule['percent_bp']??5000):5000)); ?>
                <?php $label('Fixed discount','draft_rule_fields[bogo][fixed]',$money($rule_type==='bogo'?($rule['discount_minor']??500):500)); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="bundles" <?php if($rule_type!=='bundles')echo 'hidden'; ?>>
            <div class="ac-v2-fields">
                <?php $label('Included product IDs','draft_rule_fields[bundles][product_ids]',implode(',',$rule_type==='bundles'?($rule['product_ids']??[101,202]):[101,202]),'Separate 2–20 distinct product IDs with commas'); ?>
                <label class="ac-v2-field"><span>Discount kind</span><select name="draft_rule_fields[bundles][discount_mode]"><option value="fixed" <?php selected($rule_type==='bundles'?$discount_mode:'fixed','fixed'); ?>>Fixed amount</option><option value="percent" <?php selected($rule_type==='bundles'?$discount_mode:'fixed','percent'); ?>>Percentage</option></select></label>
                <?php $label('Fixed discount','draft_rule_fields[bundles][fixed]',$money($rule_type==='bundles'?($rule['discount_minor']??500):500)); ?>
                <?php $label('Percentage','draft_rule_fields[bundles][percent]',$percent($rule_type==='bundles'?($rule['percent_bp']??1000):1000)); ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="tiered" <?php if($rule_type!=='tiered')echo 'hidden'; ?>>
            <p class="ac-v2-note">Discount tiers by total quantity; each quantity must be greater than the previous tier.</p>
            <div class="ac-v2-fields">
                <?php
                $tiers=$rule_type==='tiered'?($rule['tiers']??[]):[];
                for($i=1;$i<=3;$i++):
                    $row=$tiers[$i-1]??[];
                    $qty=(string)($row['minimum_qty']??($i===1?2:''));
                    $pct=$percent($row['percent_bp']??($i===1?1000:null));
                ?>
                    <?php $label('Tier '.$i.' · Min quantity','draft_rule_fields[tiered][minimum_qty_'.$i.']',$qty); ?>
                    <?php $label('Tier '.$i.' · Discount %','draft_rule_fields[tiered][percent_'.$i.']',$pct); ?>
                <?php endfor; ?>
            </div>
        </section>
        <section class="ac-v2-section" data-rule-type="free_gift" <?php if($rule_type!=='free_gift')echo 'hidden'; ?>>
            <p><strong>Gift delivery remains disabled.</strong> This only saves a draft for future review; no gift can be awarded.</p>
            <div class="ac-v2-fields"><?php $label('Future gift product ID','draft_rule_fields[free_gift][product_id]',(string)($rule_type==='free_gift'?($rule['product_id']??999):999)); ?></div>
        </section>
    </div>
    <div class="ac-v2-advanced" hidden>
        <label class="ac-v2-field"><span>Rule JSON (advanced, currency minor units)</span>
            <textarea name="draft_rule_json" rows="6" maxlength="3000" style="width:100%;font-family:monospace"><?php echo esc_textarea(wp_json_encode($rule,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></textarea>
        </label>
        <p class="ac-v2-note">Advanced mode retains existing draft schemas. Only use if the visual controls do not express an existing rule.</p>
    </div>
    <p class="ac-v2-note"><strong>OFF by default.</strong> No discounts, gifts, Woo shipping changes or real order writes are possible from this editor.</p>
</section>
<script>
(function(){
    'use strict';
    var root=document.getElementById('andy-commerce-m7-studio');
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
