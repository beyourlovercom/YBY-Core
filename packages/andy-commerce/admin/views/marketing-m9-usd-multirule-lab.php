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
    echo '<div class="notice notice-warning inline"><p>Multi-rule preview is restricted to authorized Dev administrators.</p></div>';
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
$zone = isset($_GET['m9_zone']) && is_string($_GET['m9_zone']) ? $_GET['m9_zone']:'5';
if (!in_array($zone,['0','1','3','4','5'],true)) $zone='5';
$zoneDescriptions=['5'=>'US / some Asia · USD $69','4'=>'Alaska / Hawaii · USD $129',
    '3'=>'High-cost · USD $169','0'=>'Rest of World · USD $69 / valid native coupon',
    '1'=>'Hong Kong · native no-threshold free method'];
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
    ['id'=>'gift_99','name'=>'Gift at USD threshold','type'=>'gift','active'=>false,
        'priority'=>100,'threshold_usd_minor'=>$minor($giftThreshold),'gift_product_id'=>123],
    ['id'=>'off_169','name'=>'Discount at USD threshold','type'=>'discount','active'=>false,
        'priority'=>100,'threshold_usd_minor'=>$minor($discountThreshold),
        'discount_usd_minor'=>$minor($bonusDiscount)],
    ['id'=>'off_extra','name'=>'Second independent discount','type'=>'discount','active'=>false,
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
            || !is_array($draft['rule'] ?? null)) continue;
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
        if ($type==='spend_save' && is_int($dRule['discount_minor']??null)
            && $dRule['discount_minor']>0 && $dRule['discount_minor']<=100000000) {
            $extra['type']='discount';$extra['discount_usd_minor']=$dRule['discount_minor'];
        } elseif ($type==='free_gift' && is_int($dRule['gift_product_id']??null)
            && $dRule['gift_product_id']>0) {
            $extra['type']='gift';$extra['gift_product_id']=$dRule['gift_product_id'];
        } else continue;
        $rules[]=$extra;++$loadedDrafts;
    }
}
try {
    // HK native free method is deliberately unverified in this lab.
    $preview=Andy_Commerce_M9_USD_Rule_Engine::preview($quote,$rules,(int)$zone,false);
} catch (Throwable $error) {
    echo '<div class="notice notice-error inline"><p>Simulation refused invalid rule data; no live promotion applied.</p></div>';return;
}
$usd=static function(int $x):string {return '$'.number_format($x/100,2);};
$symbols=['USD'=>'$','EUR'=>'€','GBP'=>'£','AUD'=>'A$','CAD'=>'C$'];
$local=$symbols[$currency].number_format($preview['qualified_after_one_discount_local_minor']/100,2);
$builderUrl=add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions','m9_preview'=>'1'],admin_url('admin.php'));
?>
<section class="andy-m9-lab" style="max-width:1160px;padding:4px 0 14px">
<style>
.andy-m9-lab *{box-sizing:border-box}
.andy-m9-lab .ac-panel{background:#fff;border:1px solid #dee7df;border-radius:9px;padding:17px;margin:14px 0}
.andy-m9-lab .ac-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:12px;margin-top:14px}
.andy-m9-lab .ac-fields label{display:block;font-size:12px;font-weight:600}
.andy-m9-lab .ac-fields input,.andy-m9-lab .ac-fields select{display:block;width:100%;margin-top:5px}
.andy-m9-lab .ac-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:12px;margin-top:15px}
.andy-m9-lab .ac-stat{padding:12px;background:#f6f8f5;border:1px solid #e1e8e2;border-radius:8px}
.andy-m9-lab .ac-stat small{color:#6f7e70;display:block;margin-bottom:7px}
.andy-m9-lab .ac-stat strong{font-size:21px}
.andy-m9-lab .ac-scroll{overflow-x:auto}
.andy-m9-lab table{min-width:660px}
.andy-m9-lab .ac-muted{color:#687869;font-size:12px}
.andy-m9-lab .ac-good{color:#187046;font-weight:700}
.andy-m9-lab .ac-bad{color:#975f22;font-weight:700}
</style>
<h2>Marketing OS · USD Multi-Rule Studio <small style="font-size:12px">M9 / Dev UAT</small></h2>
<p class="description">Three independent threshold rules plus eligible saved USD Drafts (<?php echo esc_html((string)$loadedDrafts); ?>). All promotions OFF. No Woo shipping rates, gift inventory, cart, order or payment is modified.</p>
<div class="ac-panel">
  <h3>USD-first order scenario</h3>
  <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
    <input type="hidden" name="page" value="andy-commerce">
    <input type="hidden" name="tab" value="marketing-promotions">
    <input type="hidden" name="m9_preview" value="1">
    <div class="ac-fields">
      <label>Qualifying gross (USD) <input type="number" min="0" max="100000" name="m9_gross" value="<?php echo esc_attr((string)$gross); ?>"></label>
      <label>Previous discounts (USD) <input type="number" min="0" max="100000" name="m9_discount" value="<?php echo esc_attr((string)$discount); ?>"></label>
      <label>Customer currency <select name="m9_currency"><?php foreach(Andy_Commerce_Currency_OS_V1::SUPPORTED as $code):?><option <?php selected($currency,$code); ?> value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code); ?></option><?php endforeach;?></select></label>
      <label>Delivery zone <select name="m9_zone"><?php foreach($zoneDescriptions as $code=>$text):?><option <?php selected($zone,$code); ?> value="<?php echo esc_attr($code); ?>"><?php echo esc_html($text); ?></option><?php endforeach;?></select></label>
    </div>
    <h4 style="margin:17px 0 0">Independent promotion rules (USD)</h4>
    <div class="ac-fields">
      <label>Gift threshold <input type="number" min="0" max="100000" name="m9_gift_threshold" value="<?php echo esc_attr((string)$giftThreshold); ?>"></label>
      <label>Discount threshold <input type="number" min="0" max="100000" name="m9_discount_threshold" value="<?php echo esc_attr((string)$discountThreshold); ?>"></label>
      <label>Discount amount <input type="number" min="1" max="100000" name="m9_reward" value="<?php echo esc_attr((string)$bonusDiscount); ?>"></label>
      <label>Second discount threshold <input type="number" min="0" max="100000" name="m9_extra_threshold" value="<?php echo esc_attr((string)$additionalThreshold); ?>"></label>
      <label>Second discount amount <input type="number" min="1" max="100000" name="m9_extra_reward" value="<?php echo esc_attr((string)$additionalReward); ?>"></label>
    </div>
    <p><button type="submit" class="button button-primary">Recalculate independent rules (simulation)</button></p>
  </form>
  <div class="ac-metrics">
    <div class="ac-stat"><small>After previous discounts · USD</small><strong><?php echo esc_html($usd($preview['qualifying_before_campaign_usd_minor'])); ?></strong></div>
    <div class="ac-stat"><small>After selected campaign · USD</small><strong><?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?></strong></div>
    <div class="ac-stat"><small>Customer merchandise subtotal · <?php echo esc_html($currency); ?></small><strong><?php echo esc_html($local); ?></strong></div>
  </div>
</div>
<div class="ac-panel">
  <h3>Independent Marketing OS rules</h3>
  <div class="ac-scroll"><table class="widefat striped"><thead><tr><th>Rule</th><th>USD threshold</th><th>Pre-campaign USD basis</th><th>Preview result</th><th>Fulfillment</th></tr></thead><tbody>
  <?php foreach($preview['evaluations'] as $entry): ?>
    <tr>
      <td><strong><?php echo esc_html($entry['name']); ?></strong><br><small><?php echo esc_html($entry['id']); ?></small></td>
      <td><?php echo esc_html($usd($entry['threshold_usd_minor'])); ?></td>
      <td><?php echo esc_html($usd($entry['qualification_usd_minor'])); ?></td>
      <td class="<?php echo $entry['qualified']?'ac-good':'ac-bad'; ?>"><?php echo $entry['qualified']?'Qualified (shadow)':'Below threshold'; ?></td>
      <td>OFF · never award</td>
    </tr>
  <?php endforeach;?>
  <tr><td><strong>Native Woo zone <?php echo esc_html($zone); ?> · ordinary free shipping</strong></td>
    <td><?php echo $preview['shipping']['threshold_usd_minor']===null?'Native availability':esc_html($usd($preview['shipping']['threshold_usd_minor'])); ?></td>
    <td><?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?> <small>after campaign discount</small></td>
    <td class="<?php echo $preview['shipping']['qualifies_by_policy_preview']?'ac-good':'ac-bad'; ?>"><?php echo $preview['shipping']['qualifies_by_policy_preview']?'Amount qualified (shadow)':'Not qualified / unverified'; ?></td>
    <td>No Woo rate changed</td></tr>
  </tbody></table></div>
  <p class="description">The USD gift threshold is evaluated on post-existing-discount merchandise before new campaign awards. Only ONE highest-benefit discount is projected. Native shipping is re-evaluated on the projected post-campaign discounted USD subtotal. All actual fulfillment remains disabled.</p>
</div>
<div class="ac-panel">
  <h3>Create independent inactive campaign draft</h3>
  <p class="description">Uses existing M7 Draft Studio's permission, nonce and optimistic-revision protections. This form only creates an OFF draft; no coupons, shipping or gifts can go live.</p>
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
      <label>Campaign name <input name="draft_name" maxlength="120" required value="New USD promotion"></label>
      <label>Reward type
        <select name="draft_type" id="andy-m9-new-type">
          <option value="spend_save">Spend &amp; Save · fixed USD discount</option>
          <option value="free_gift">Gift · product ID</option>
        </select>
      </label>
      <label>USD threshold <input type="number" id="andy-m9-new-threshold" min="1" max="100000" step="1" required value="99"></label>
      <label>USD discount <input type="number" id="andy-m9-new-saving" min="1" max="100000" step="1" value="10"></label>
      <label>Gift product ID <input type="number" id="andy-m9-new-gift" min="1" max="999999999" step="1" value="123"></label>
      <label>Eligible country codes (ISO2) <input name="draft_countries" maxlength="100" required value="US"></label>
      <label>Priority <input type="number" name="draft_priority" min="0" max="1000" step="1" value="100"></label>
    </div>
    <p class="description" id="andy-m9-new-feedback" role="status">The new campaign is saved as an inactive USD draft. No offer is activated automatically.</p>
    <button class="button button-primary" type="submit">Save inactive draft</button>
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
        status.textContent='Please supply valid USD threshold and reward.';
      }
    });
    update();
  })();
  </script>
  <?php else: ?><p class="description">Native M7 Dev draft write permission unavailable. View is read-only.</p><?php endif; ?>
</div>

<div class="ac-panel">
  <h3>Currency OS handoff</h3>
  <p>Eligible USD merchandise <?php echo esc_html($usd($preview['qualifying_before_campaign_usd_minor'])); ?>
     → selected shadow discount <?php echo esc_html($usd($preview['projected_discount_usd_minor'])); ?>
     → final qualifying USD <?php echo esc_html($usd($preview['qualified_after_one_discount_usd_minor'])); ?>
     → displayed <?php echo esc_html($local.' '.$currency); ?>.</p>
  <p><strong>Gift:</strong> qualified IDs <?php echo esc_html(implode(', ',$preview['eligible_gift_ids'])?:'none'); ?> (no free gifts added).
     <strong>Special freight:</strong> USD 30/parcel and PILLOW USD 30/unit are never waived by this simulation.</p>
  <p class="description">All displayed exchange rates are synthetic test values. No live checkout, payment charge, tax engine, native Woo shipping rate or order is touched.</p>
</div>
<p><a href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions'],admin_url('admin.php'))); ?>">← Return to Marketing Studio</a></p>
</section>
