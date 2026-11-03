<?php
/** P2: six visible types in the existing M9 Dev desktop preview, synthetic data only. */
if (!defined('ABSPATH')) exit;
require_once dirname(__DIR__,2).'/includes/class-andy-commerce-m10-six-type-simulator.php';
$intSynthetic=static function(string $name,int $default,int $max):?int {
    if(!array_key_exists($name,$_GET))return $default;
    $raw=$_GET[$name];
    if(!is_string($raw)||!preg_match('/^(?:0|[1-9][0-9]{0,5})$/D',$raw)
        ||(int)$raw>$max)return null;
    return (int)$raw;
};
$fixtures=[
 101=>['qty'=>$intSynthetic('m10_qty_101',2,1000),
       'usd'=>$intSynthetic('m10_amount_101',80,100000)],
 202=>['qty'=>$intSynthetic('m10_qty_202',1,1000),
       'usd'=>$intSynthetic('m10_amount_202',45,100000)],
 303=>['qty'=>$intSynthetic('m10_qty_303',1,1000),
       'usd'=>$intSynthetic('m10_amount_303',60,100000)],
];
$trusted=true;$syntheticLines=[];
// A malformed explicit GET value must never silently fall back to a qualifying fixture.
if (array_key_exists('m9_country',$_GET) &&
    (!is_string($_GET['m9_country']) ||
    !isset($countryLabels[strtoupper($_GET['m9_country'])])))$trusted=false;
if (array_key_exists('m9_currency',$_GET) &&
    (!is_string($_GET['m9_currency']) ||
    !in_array(strtoupper($_GET['m9_currency']),Andy_Commerce_Currency_OS_V1::SUPPORTED,true)))$trusted=false;
if (array_key_exists('m9_date',$_GET) &&
    (!is_string($_GET['m9_date']) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/D',$_GET['m9_date'])))$trusted=false;
if (array_key_exists('m9_coupon',$_GET) &&
    (!is_string($_GET['m9_coupon'])||strlen($_GET['m9_coupon'])>50))$trusted=false;
foreach($fixtures as $productId=>$f) {
 if($f['qty']===null||$f['usd']===null){$trusted=false;continue;}
 if($f['qty']===0)continue;
 $syntheticLines[]=['product_id'=>$productId,'quantity'=>$f['qty'],
   'discounted_line_minor'=>$f['usd']*100,'catalog_present'=>true];
}
$policy=['start_date'=>'','end_date'=>'','product_scope'=>'all',
         'product_ids'=>[],'stack_policy'=>'best_single'];
$demo=[
 ['id'=>'demo_fixed','type'=>'spend_save','rule'=>['threshold_minor'=>6900,'discount_minor'=>500]],
 ['id'=>'demo_percent','type'=>'spend_save','rule'=>['threshold_minor'=>6900,'percent_bp'=>1000]],
 ['id'=>'demo_gift','type'=>'free_gift','rule'=>['threshold_minor'=>9900,'product_id'=>404]],
 ['id'=>'demo_bogo','type'=>'bogo','rule'=>['buy_product_id'=>101,'buy_qty'=>2,
   'get_product_id'=>202,'get_qty'=>1,'percent_bp'=>5000]],
 ['id'=>'demo_bundle','type'=>'bundles','rule'=>['product_ids'=>[101,303],'discount_minor'=>1000]],
 ['id'=>'demo_tier','type'=>'tiered','rule'=>['tiers'=>[
   ['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>4,'percent_bp'=>2000]]]],
];
foreach($demo as &$d) {
 $d+=['priority'=>20,'active'=>false,'status'=>'draft','lifecycle'=>'draft',
       'currency'=>'USD','countries'=>['US','CA','GB','DE','FR','AU','HK'],'policy'=>$policy];
}
unset($d);
$stored=class_exists('Andy_Commerce_M7_Draft_Studio')
 ?array_values((array)Andy_Commerce_M7_Draft_Studio::rows()):[];
$six=Andy_Commerce_M10_Six_Type_Simulator::preview([
 'source'=>'DEV_SYNTHETIC','trusted'=>$trusted,
 'date_utc'=>$simDate,'country'=>$selectedCountry,'currency'=>$currency,
 'fx_rate_micros'=>$rates[$currency],'manual_coupon'=>$simCoupon!==''?$simCoupon:null,
 'items'=>$syntheticLines
],array_merge($demo,$stored));
$reasonLabels=[
 'preview_only'=>'仅模拟符合','gift_qualified_stock_unverified'=>'符合赠品资格 · 库存未验证',
 'below_threshold'=>'未达到门槛','rule_not_qualified'=>'数量或商品组合不达标',
 'invalid_draft'=>'草稿无效','invalid_rule'=>'规则参数无效','invalid_policy'=>'适用策略无效',
 'not_started'=>'尚未开始','expired'=>'已过结束日期','product_excluded'=>'商品不在范围',
 'unverified_products'=>'商品快照缺失','coupon_conflict'=>'与现有优惠券互斥',
 'not_inactive_draft'=>'非未启用草稿','country_excluded'=>'不适用当前国家',
 'currency_not_usd'=>'草稿非 USD 计价','lifecycle_not_eligible'=>'虚拟生命周期暂停或结束',
 'invalid_snapshot'=>'模拟输入无效','unverified_cart_line'=>'商品数据缺失或无效'
];
$typeLabels=[
 'spend_save'=>'满额满减','free_gift'=>'赠品资格','bogo'=>'买赠折扣',
 'bundles'=>'组合优惠','tiered'=>'阶梯折扣'
];
?>
<div class="ac-panel" id="andy-m10-six-type-preview">
 <h3>六类型统一规则模拟 · USD 基准</h3>
 <p class="ac-muted">下列 101／202／303 为固定合成商品编号，不是 Woo 库存或真实 SKU 验证。
 内置六条 OFF 样例与现有 Dev 草稿一起预览；仅最高一项折扣进入预测值。赠品只显示资格，库存未经核验。</p>
 <?php if($six['reason']==='invalid_snapshot'||$six['reason']==='unverified_cart_line'): ?>
 <p class="ac-bad">模拟已拒绝：<?php echo esc_html($reasonLabels[$six['reason']]??$six['reason']); ?>。没有回退成默认优惠。</p>
 <?php else: ?>
 <div class="ac-metrics">
  <div class="ac-stat"><small>合成商品折扣后合计（USD）</small>
    <strong><?php echo esc_html($usd($six['qualified_before_campaign_usd_minor'])); ?></strong></div>
  <div class="ac-stat"><small>预测最佳单项优惠（USD）</small>
    <strong><?php echo esc_html($usd($six['projected_discount_usd_minor'])); ?></strong></div>
  <div class="ac-stat"><small>预测活动后 USD</small>
    <strong><?php echo esc_html($usd($six['qualified_after_campaign_usd_minor'])); ?></strong></div>
  <div class="ac-stat"><small>活动后当地货币 <?php echo esc_html($currency); ?></small>
    <strong><?php echo esc_html($symbols[$currency].number_format(($six['qualified_after_campaign_local_minor']??0)/100,2)); ?></strong></div>
 </div>
 <div class="ac-scroll"><table class="widefat striped">
 <thead><tr><th>合成样例／Dev 草稿</th><th>玩法</th><th>合格 USD 基数</th><th>预计优惠</th><th>规则判断及原因</th></tr></thead><tbody>
 <?php foreach($six['evaluations'] as $e): ?>
 <tr><td><?php echo esc_html($e['id']); ?></td>
 <td><?php echo esc_html($e['type']==='spend_save'&&
     ($e['variant']??'fixed')==='percent'?'百分比满减':($typeLabels[$e['type']]??'未知类型')); ?></td>
 <td><?php echo isset($e['qualified_basis_usd_minor'])
     ?esc_html($usd($e['qualified_basis_usd_minor'])):'—'; ?></td>
 <td><?php echo esc_html($usd($e['discount_usd_minor'])); ?></td>
 <td class="<?php echo $e['eligible']?'ac-good':'ac-bad'; ?>">
 <?php echo esc_html($reasonLabels[$e['reason']]??$e['reason']); ?></td></tr>
 <?php endforeach; ?>
 </tbody></table></div>
 <p class="ac-muted">当前最佳折扣：<?php echo esc_html($six['selected_discount_id']??'无'); ?>。
  合格赠品条目：<?php echo esc_html((string)count($six['qualified_gifts'])); ?>（只核资格，未添加商品）。
  不触发 Woo 折扣、购物车、运费、邮件、支付或库存变更。</p>
 <?php endif; ?>
</div>
