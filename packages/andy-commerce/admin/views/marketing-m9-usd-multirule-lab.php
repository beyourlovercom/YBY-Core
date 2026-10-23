<?php
/**
 * Marketing OS M9 — Dev-only USD-first multi-rule admin lab.
 * GET-only synthetic previews plus native M7 nonce-guarded inactive draft creation.
 * Never alters Woo coupons, gifts, shipping rates, checkout or payments.
 */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('manage_woocommerce')
    || parse_url(home_url('/'), PHP_URL_HOST) !== 'dev.beyourlover.com'
    || !class_exists('Andy_Commerce_M9_USD_Rule_Engine')
    || !class_exists('Andy_Commerce_Currency_OS_V1')) {
    echo '<div class="notice notice-warning inline"><p>多规则预览仅限获得授权的 Dev 管理员使用。</p></div>';
    return;
}
$intField = static function(string $key,int $fallback,int $max=100000):int {
    $v = $_GET[$key] ?? null;
    if (!is_string($v) || !preg_match('/^[0-9]{1,6}$/D',$v)) return $fallback;
    return min($max,(int)$v);
};
$gross = $intField('m9_gross',180);
$discount = min($gross,$intField('m9_discount',15));
$giftThreshold = $intField('m9_gift_threshold',99);
$discountThreshold = $intField('m9_discount_threshold',169);
$bonusDiscount = $intField('m9_reward',20);
$additionalThreshold = $intField('m9_extra_threshold',129);
$additionalReward = max(1,$intField('m9_extra_reward',10));
$bonusDiscount = max(1,$bonusDiscount);
$currency = isset($_GET['m9_currency']) && is_string($_GET['m9_currency'])
    ? strtoupper(sanitize_key(wp_unslash($_GET['m9_currency']))) : 'EUR';
if (!in_array($currency,Andy_Commerce_Currency_OS_V1::SUPPORTED,true)) $currency='EUR';
$rates=['USD'=>1000000,'EUR'=>890000,'GBP'=>746500,'AUD'=>1421700,'CAD'=>1404200];
// The simulation country is explicitly chosen, never inferred from a Woo zone.
$selectedCountry=isset($_GET['m9_country']) && is_string($_GET['m9_country'])
    ? strtoupper(sanitize_key(wp_unslash($_GET['m9_country']))) : 'US';
if (!preg_match('/^[A-Z]{2}$/D',$selectedCountry)) $selectedCountry='US';
$countryLabels=['US'=>'美国 US','CA'=>'加拿大 CA','GB'=>'英国 GB',
    'DE'=>'德国 DE','FR'=>'法国 FR','AU'=>'澳大利亚 AU','HK'=>'香港 HK'];
if (!isset($countryLabels[$selectedCountry])) $selectedCountry='US';
$zone = isset($_GET['m9_zone']) && is_string($_GET['m9_zone']) ? $_GET['m9_zone']:'5';
if (!in_array($zone,['0','1','3','4','5'],true)) $zone='5';
$zoneDescriptions=['5'=>'美国本土及部分亚洲地区 · USD $69','4'=>'阿拉斯加／夏威夷 · USD $129',
    '3'=>'高运费地区 · USD $169','0'=>'其他地区 · USD $69（或有效原生免邮券）',
    '1'=>'香港 · 以 Woo 原生免邮方式为准'];
$minor = static function(int $whole):int { return $whole * 100; };
$lines=[['qty'=>1,'unit_usd_minor'=>$minor($gross),'discount_usd_minor'=>$minor($discount),
    'eligible_for_promotion'=>true,'free_gift'=>false]];
$quote=Andy_Commerce_Currency_OS_V1::quote([
    'currency'=>$currency,'rate_micros'=>$rates[$currency],
    'rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
    'threshold_usd_minor'=>$minor($giftThreshold),'shipping_usd_minor'=>0,
    'tax_usd_minor'=>0,'lines'=>$lines
]);
$rules=[
    ['id'=>'gift_99','name'=>'满额赠品','type'=>'gift','active'=>false,
        'priority'=>100,'threshold_usd_minor'=>$minor($giftThreshold),'gift_product_id'=>123],
    ['id'=>'off_169','name'=>'第一档固定满减','type'=>'discount','active'=>false,
        'priority'=>100,'threshold_usd_minor'=>$minor($discountThreshold),
        'discount_usd_minor'=>$minor($bonusDiscount)],
    ['id'=>'off_extra','name'=>'第二档固定满减','type'=>'discount','active'=>false,
        'priority'=>80,'threshold_usd_minor'=>$minor($additionalThreshold),
        'discount_usd_minor'=>$minor($additionalReward)],
];
// Reuse existing Dev M7 draft definitions as OFF-only preview inputs if
// strictly compatible with canonical USD minor-unit contracts.
$loadedDrafts=0;
if (class_exists('Andy_Commerce_M7_Draft_Studio')) {
    foreach ((array)Andy_Commerce_M7_Draft_Studio::rows() as $draft) {
        if ($loadedDrafts >= 40 || !is_array($draft)
            || ($draft['currency'] ?? null)!=='USD'
            || ($draft['active'] ?? null)!==false
            || ($draft['status'] ?? null)!=='draft'
            || !is_array($draft['rule'] ?? null)
            || !is_array($draft['countries']??null)
            || !in_array($selectedCountry,$draft['countries'],true)) continue;
        $dRule=$draft['rule'];$type=$draft['type']??'';
        if ($type!=='spend_save' && $type!=='free_gift') continue;
        if (!is_int($dRule['threshold_minor']??null)
            || $dRule['threshold_minor'] < 0 || $dRule['threshold_minor'] > 100000000)continue;
        $id=$draft['id']??'';
        if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,50}$/D',$id))continue;
        $extra=[
            'id'=>'draft_'.$id,'name'=>substr((string)($draft['name']??'Draft'),0,120),
            'active'=>false,'priority'=>(int)($draft['priority']??0),
            'threshold_usd_minor'=>$dRule['threshold_minor'],
        ];
        if ($type==='spend_save') {
            $fixed=$dRule['discount_minor']??null;
            $percent=$dRule['percent_bp']??null;
            if (is_int($fixed) && $fixed>0 && $fixed<=100000000 &&
                $percent===null) {
                $extra['type']='discount';$extra['discount_usd_minor']=$fixed;
            } elseif (is_int($percent) && $percent>0 && $percent<=10000 &&
                      $fixed===null) {
                $extra['type']='discount';$extra['discount_percent_bp']=$percent;
            } else continue;
        } elseif ($type==='free_gift') {
            // V2 uses product_id; pre-V2 JSON may contain gift_product_id.
            // No explicit USD threshold => never grant simulated gift status.
            $giftId=$dRule['product_id']??($dRule['gift_product_id']??null);
            if (!is_int($giftId) || $giftId<=0 ||
                $dRule['threshold_minor']<=0) continue;
            $extra['type']='gift';$extra['gift_product_id']=$giftId;
        } else continue;
        $rules[]=$extra;++$loadedDrafts;
    }
}
try {
    // HK native free method is deliberately unverified in this lab.
    $preview=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,$rules,(int)$zone,false);
} catch (Throwable $error) {
    echo '<div class="notice notice-error inline"><p>活动模拟失败：规则数据无效，未执行任何优惠。</p></div>';return;
}
$usd=static function(int $x):string {return '$'.number_format($x/100,2);};
$symbols=['USD'=>'$','EUR'=>'€','GBP'=>'£','AUD'=>'A$','CAD'=>'C$'];
$local=$symbols[$currency].number_format($preview['qualified_after_one_discount_local_minor']/100,2);
$marketingTab=isset($_GET['tab'])&&$_GET['tab']==='marketing-simulate'?
    'marketing-simulate':'marketing-promotions';
$builderUrl=add_query_arg(['page'=>'andy-commerce','tab'=>$marketingTab,
    'm9_preview'=>'1'],admin_url('admin.php'));
?>
<section class="andy-m9-lab" style="max-width:1360px;padding:10px 18px 24px 0">
<style>
.andy-m9-lab *{box-sizing:border-box}
.andy-m9-lab .ac-panel{background:#fff;border:1px solid #dee7df;border-radius:9px;padding:20px 22px;margin:16px 0}
.andy-m9-lab .ac-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
.andy-m9-lab .ac-fields label{display:block;font-size:12px;font-weight:600}
.andy-m9-lab .ac-fields input,.andy-m9-lab .ac-fields select{display:block;width:100%;margin-top:5px}
.andy-m9-lab .ac-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:15px}
.andy-m9-lab .ac-stat{padding:12px;background:#f7faf7;border:1px solid #e1e8e2;border-radius:8px}
.andy-m9-lab .ac-stat small{color:#6f7e70;display:block;margin-bottom:7px}
.andy-m9-lab .ac-stat strong{font-size:23px;color:#1b3a28}
.andy-m9-lab .ac-scroll{overflow-x:auto}
.andy-m9-lab table{min-width:850px}
.andy-m9-lab .ac-muted{color:#687869;font-size:12px}
.andy-m9-lab .ac-good{color:#187046;font-weight:700}
.andy-m9-lab .ac-bad{color:#975f22;font-weight:700}

.andy-m9-lab{font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",sans-serif;color:#202822;font-size:13px}
.andy-m9-lab .ac-head{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:7px 0 11px;border-bottom:1px solid #dce6df}
.andy-m9-lab .ac-head h2{font-size:23px;line-height:1.3;font-weight:700;margin:0 0 7px;color:#1d2921}
.andy-m9-lab .ac-tag{font-size:12px;color:#186849;background:#e6f1e9;padding:8px 12px;border-radius:24px;white-space:nowrap}
.andy-m9-lab .ac-intro{font-size:12px;color:#637567;padding:9px 1px 0;line-height:1.65}
.andy-m9-lab .ac-panel h3{color:#223a2d;margin:0 0 9px;font-size:16px;font-weight:700}
.andy-m9-lab .ac-panel .description{line-height:1.7}
.andy-m9-lab .ac-fields label{font-size:12px;line-height:1.6;color:#485c4e}
.andy-m9-lab .ac-fields input,.andy-m9-lab .ac-fields select{height:37px;max-width:100%;min-width:0;border-color:#ccd9d0}
.andy-m9-lab .ac-stat{border-radius:9px}
.andy-m9-lab .ac-stat small{line-height:1.65}
.andy-m9-lab .ac-scroll .widefat th,.andy-m9-lab .ac-scroll .widefat td{padding:12px 10px;vertical-align:middle}
.andy-m9-lab .ac-scroll .widefat th{background:#f2f7f3;color:#496052;font-weight:600}
.andy-m9-lab .ac-state-off{display:inline-block;background:#eef2ef;color:#5b715f;padding:5px 9px;border-radius:16px;font-size:12px;white-space:nowrap}
.andy-m9-lab .ac-good{color:#1e704d!important;font-weight:700}
.andy-m9-lab .ac-bad{color:#9b6c30!important;font-weight:700}
.andy-m9-lab button.button-primary{background:#216948!important;border-color:#216948!important;font-weight:600;min-height:36px;border-radius:6px}
.andy-m9-lab button.button-primary:hover{background:#145436!important}
.andy-m9-lab .ac-panel{box-shadow:0 1px 4px rgba(22,69,40,.05)}
.andy-m9-lab .ac-subheading{border-top:1px solid #e9efeb;padding-top:15px;margin-top:18px}
</style>
<header class="ac-head"><div><h2>营销 OS · 美元多规则工作台</h2><p class="ac-muted">按照折扣后的合格商品 USD 金额判断营销资格，客户币种仅用于展示换算结果。</p></div><span class="ac-tag">Dev 环境 · 仅模拟 · 活动未启用</span></header>
<p class="description">三条独立示例规则与符合条件的未启用 USD 草稿（<?php echo esc_html((string)$loadedDrafts); ?>）。不修改 Woo 运费、商品库存、购物车、订单或支付。</p>
<div class="ac-panel">
  <h3>订单场景模拟</h3><p class="ac-muted">输入示例商品金额，分别核对多条活动规则；重新计算不会保存活动。</p>
  <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
    <input type="hidden" name="page" value="andy-commerce">
    <input type="hidden" name="tab" value="<?php echo esc_attr($marketingTab); ?>">
    <input type="hidden" name="m9_preview" value="1">
    <div class="ac-fields">
      <label>符合条件的商品原价（USD） <input type="number" min="0" max="100000" name="m9_gross" value="<?php echo esc_attr((string)$gross); ?>"></label>
      <label>已有折扣（USD） <input type="number" min="0" max="100000" name="m9_discount" value="<?php echo esc_attr((string)$discount); ?>"></label>
      <label>客户币种 <select name="m9_currency"><?php foreach(Andy_Commerce_Currency_OS_V1::SUPPORTED as $code):?><option <?php selected($currency,$code); ?> value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code); ?></option><?php endforeach;?></select></label>
      <label>模拟国家 <select name="m9_country"><?php foreach($countryLabels as $code=>$label):?><option <?php selected($selectedCountry,$code); ?> value="<?php echo esc_attr($code); ?>"><?php echo esc_html($label); ?></option><?php endforeach;?></select></label>
      <label>配送区域 <select name="m9_zone"><?php foreach($zoneDescriptions as $code=>$text):?><option <?php selected($zone,$code); ?> value="<?php echo esc_attr($code); ?>"><?php echo esc_html($text); ?></option><?php endforeach;?></select></label>
    </div>
    <h4 class="ac-subheading">模拟活动参数（USD）</h4>
    <div class="ac-fields">
      <label>赠品门槛（USD） <input type="number" min="0" max="100000" name="m9_gift_threshold" value="<?php echo esc_attr((string)$giftThreshold); ?>"></label>
      <label>第一档满减门槛（USD） <input type="number" min="0" max="100000" name="m9_discount_threshold" value="<?php echo esc_attr((string)$discountThreshold); ?>"></label>
      <label>第一档固定减免（USD） <input type="number" min="1" max="100000" name="m9_reward" value="<?php echo esc_attr((string)$bonusDiscount); ?>"></label>
      <label>第二档满减门槛（USD） <input type="number" min="0" max="100000" name="m9_extra_threshold" value="<?php echo esc_attr((string)$additionalThreshold); ?>"></label>
      <label>第二档固定减免（USD） <input type="number" min="1" max="100000" name="m9_extra_reward" value="<?php echo esc_attr((string)$additionalReward); ?>"></label>
    </div>
    <p><button type="submit" class="button button-primary">重新计算规则</button></p>
  </form>
  <div class="ac-metrics">
    <div class="ac-stat"><small>已有折扣后 · USD</small><strong><?php echo esc_html($usd($preview['qualifying_before_campaign_usd_minor'])); ?></strong></div>
    <div class="ac-stat"><small>模拟满减后 · USD</small><strong><?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?></strong></div>
    <div class="ac-stat"><small>客户商品小计 · <?php echo esc_html($currency); ?></small><strong><?php echo esc_html($local); ?></strong></div>
  </div>
</div>
<div class="ac-panel">
  <h3>独立营销规则</h3><p class="ac-muted">分别判断活动资格；模拟达标不代表真实优惠已生效。</p>
  <div class="ac-scroll"><table class="widefat striped"><thead><tr><th>规则名称</th><th>活动门槛（USD）</th><th>判定金额（USD）</th><th>模拟结果</th><th>执行状态</th></tr></thead><tbody>
  <?php foreach($preview['evaluations'] as $entry): ?>
    <tr>
      <td><strong><?php echo esc_html($entry['name']); ?></strong><br><small><?php echo esc_html($entry['id']); ?></small></td>
      <td><?php echo esc_html($usd($entry['threshold_usd_minor'])); ?></td>
      <td><?php echo esc_html($usd($entry['qualification_usd_minor'])); ?></td>
      <td class="<?php echo $entry['qualified']?'ac-good':'ac-bad'; ?>"><?php echo $entry['qualified']?'模拟达标':'未达到门槛'; ?></td>
      <td><span class="ac-state-off">未启用 · 不发放</span></td>
    </tr>
  <?php endforeach;?>
  <tr><td><strong>Woo 配送区 <?php echo esc_html($zone); ?> · 普通商品免邮</strong></td>
    <td><?php echo $preview['shipping']['threshold_usd_minor']===null?'Woo 原生方式待验证':esc_html($usd($preview['shipping']['threshold_usd_minor'])); ?></td>
    <td><?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?> <small>活动减免后</small></td>
    <td class="<?php echo $preview['shipping']['qualifies_by_policy_preview']?'ac-good':'ac-bad'; ?>"><?php echo $preview['shipping']['qualifies_by_policy_preview']?'模拟达到免邮金额':'未达标或待验证'; ?></td>
    <td><span class="ac-state-off">未修改 Woo 运费</span></td></tr>
  </tbody></table></div>
  <p class="description">赠品资格仅计算具备有效门槛及商品 ID 的草稿；草稿国家限制仅按本次选择的模拟国家验证，真实地址仍未验证。赠品资格按已有折扣后的 USD 商品金额判断；只模拟选用一项最优惠的固定满减。普通商品免邮在该减免后重新核对，所有实际活动仍未启用。</p>
</div>
<div class="ac-panel">
  <h3>新建未启用活动草稿</h3>
  <p class="description">沿用既有草稿系统的管理员权限与安全校验。保存后保持未启用，不会实际发放赠品、优惠券或免邮。</p>
  <?php if (class_exists('Andy_Commerce_M7_Draft_Studio')
      && Andy_Commerce_M7_Draft_Studio::dev_only()): ?>
  <form id="andy-m9-new-draft" method="post"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        style="max-width:100%">
    <?php wp_nonce_field('andy_commerce_m7_save_draft'); ?>
    <input type="hidden" name="action" value="andy_commerce_m7_save_draft">
    <input type="hidden" name="draft_id" value="">
    <input type="hidden" name="draft_revision" value="0">
    <input type="hidden" name="draft_currency" value="USD">
    <input type="hidden" name="draft_rule_json" value="">
    <div class="ac-fields">
      <label>活动名称 <input name="draft_name" maxlength="120" required value="" placeholder="例如：夏季赠礼活动"></label>
      <label>奖励类型
        <select name="draft_type" id="andy-m9-new-type">
          <option value="spend_save">满额立减（固定 USD 金额）</option>
          <option value="free_gift">满额赠品（商品 ID）</option>
        </select>
      </label>
      <label>活动门槛（USD） <input type="number" id="andy-m9-new-threshold" min="1" max="100000" step="1" required value="99"></label>
      <label>固定减免金额（USD） <input type="number" id="andy-m9-new-saving" min="1" max="100000" step="1" value="10"></label>
      <label>赠品商品 ID <input type="number" id="andy-m9-new-gift" min="1" max="999999999" step="1" value=""></label>
      <label>适用国家代码（ISO2） <input name="draft_countries" maxlength="100" required value="US"></label>
      <label>优先级 <input type="number" name="draft_priority" min="0" max="1000" step="1" value="100"></label>
    </div>
    <p class="description" id="andy-m9-new-feedback" role="status">保存后保持未启用状态；不会修改实际商品价格、订单或运费。</p>
    <button class="button button-primary" type="submit">保存为未启用草稿</button>
  </form>
  <script>
  (function () {
    var form=document.getElementById('andy-m9-new-draft');
    if (!form || form.dataset.initialized) return;
    form.dataset.initialized='1';
    var type=form.querySelector('#andy-m9-new-type');
    var threshold=form.querySelector('#andy-m9-new-threshold');
    var saving=form.querySelector('#andy-m9-new-saving');
    var gift=form.querySelector('#andy-m9-new-gift');
    var rule=form.querySelector('[name="draft_rule_json"]');
    var status=form.querySelector('#andy-m9-new-feedback');
    function validInteger(input) {
      var value=Number(input.value);
      return Number.isSafeInteger(value)&&value>=Number(input.min||1)&&
        value<=Number(input.max||100000);
    }
    function update(){
      saving.disabled=type.value!=='spend_save';
      gift.disabled=type.value!=='free_gift';
      gift.required=type.value==='free_gift';
      saving.required=type.value==='spend_save';
      var valid=validInteger(threshold)&&
        (type.value==='spend_save'?validInteger(saving):validInteger(gift));
      if (!valid) {rule.value='';return false;}
      var value={threshold_minor:Number(threshold.value)*100};
      if(type.value==='spend_save')value.discount_minor=Number(saving.value)*100;
      else value.gift_product_id=Number(gift.value);
      rule.value=JSON.stringify(value);
      return true;
    }
    [type,threshold,saving,gift].forEach(function(field){
      field.addEventListener('change',update);
      field.addEventListener('input',update);
    });
    form.addEventListener('submit',function(event){
      if(!update()){
        event.preventDefault();
        status.textContent='请填写有效的 USD 门槛及奖励参数。';
      }
    });
    update();
  })();
  </script>
  <?php else: ?><p class="description">当前无权保存草稿；本页仅支持查看和模拟。</p><?php endif; ?>
</div>

<div class="ac-panel">
  <h3>币种换算结果</h3>
  <p>已有折扣后合格商品金额 <?php echo esc_html($usd($preview['qualifying_before_campaign_usd_minor'])); ?>
     → 本次模拟减免 <?php echo esc_html($usd($preview['projected_discount_usd_minor'])); ?>
     → 模拟减免后合格商品金额 <?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?>
     → 客户币种显示 <?php echo esc_html($local.' '.$currency); ?>.</p>
  <p><strong>赠品资格：</strong>符合条件的规则 ID：<?php echo esc_html(implode(', ',$preview['eligible_gift_ids'])?:'无'); ?> （不实际添加赠品）。
     <strong>特殊运费：</strong> 特殊商品每包 USD $30、PILLOW 每件 USD $30，均不会因模拟免邮而取消。</p>
  <p class="description">本页面使用非实时模拟汇率，不接入实际结账、支付、税费、Woo 运费修改或订单写入。</p>
</div>
<p><a href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions'],admin_url('admin.php'))); ?>">← 返回营销工作台</a></p>
</section>
