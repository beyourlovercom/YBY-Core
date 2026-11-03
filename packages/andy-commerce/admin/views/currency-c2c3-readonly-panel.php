<?php
/** C2/C3 explicitly synthetic display/marketing reconciliation on Dev admin. */
if (!defined('ABSPATH')) { exit; }
if (!class_exists('Andy_Commerce_Currency_V2_Dev') || !Andy_Commerce_Currency_V2_Dev::access()) { return; }
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-os-v1.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m9-usd-rule-engine.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-c2c3-preview.php';
$fxRates = ['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000];
$fixture = ['rate_source'=>'DEV_SYNTHETIC','rate_timestamp'=>1792600000,
    'threshold_usd_minor'=>9900,'shipping_usd_minor'=>1000,'tax_usd_minor'=>700,
    'lines'=>[['qty'=>1,'unit_usd_minor'=>12000,'discount_usd_minor'=>2500,
        'eligible_for_promotion'=>true,'free_gift'=>false]]];
$rules = [['id'=>'discount10','type'=>'discount','name'=>'USD 99 减 10（模拟）',
    'active'=>false,'priority'=>100,'threshold_usd_minor'=>9900,'discount_usd_minor'=>1000]];
$zone = isset($_GET['c3_zone']) && is_scalar($_GET['c3_zone'])
    ? (int)$_GET['c3_zone'] : 5;
if (!in_array($zone,[0,1,3,4,5],true)) { $zone=5; }
$money = static function(int $minor): string { return number_format($minor/100,2,'.',','); };
?>
<section class="acv2-card" aria-label="C2 C3 USD 多币种一致性对账">
  <h2>C2–C3 · 五币种 USD 对账（仅 Dev 模拟）</h2>
  <p class="muted">固定示例：商品 USD 120，已折扣 USD 25；营销资格按 USD 95 判断，USD 99 门槛不达标。运费 USD 10、税 USD 7，仅用于展示。不会读取、写入或修改 WooCommerce 真实购物车。</p>
  <form method="get" action="<?php echo esc_url(admin_url('admin.php'));?>" style="margin:12px 0;display:flex;align-items:center;gap:8px">
    <input type="hidden" name="page" value="andy-commerce"><input type="hidden" name="tab" value="currency-os">
    <input type="hidden" name="section" value="fx">
    <label for="ac-c3-zone">运费区域（只读示例）</label>
    <select name="c3_zone" id="ac-c3-zone">
      <?php foreach ([0=>'区域 0',1=>'区域 1（Woo 验证必需）',3=>'区域 3',4=>'区域 4',5=>'区域 5'] as $z=>$caption): ?>
        <option value="<?php echo esc_attr((string)$z);?>" <?php selected($zone,$z);?>><?php echo esc_html($caption);?></option>
      <?php endforeach;?>
    </select><button class="button" type="submit">预览核算</button>
  </form>
  <div class="acv2-table-scroll"><table class="acv2-table">
    <thead><tr><th>币种</th><th>模拟 USD 汇率</th><th>显示商品净额</th><th>营销资格（USD）</th><th>USD 99 营销</th><th>模拟含税运费合计</th><th>免邮判断（仅预览）</th></tr></thead><tbody>
    <?php foreach($fxRates as $code=>$micros):
      $report=Andy_Commerce_Currency_C2C3_Preview::calculate(
        array_merge($fixture,['currency'=>$code,'rate_micros'=>$micros]),$rules,$zone,false);
      $policy=$report['shipping_policy_preview'];
      $symbol=Andy_Commerce_Currency_V2_Dev::SYMBOLS[$code];
      $flag=Andy_Commerce_Currency_V2_Dev::FLAGS[$code];
    ?>
      <tr><td><?php echo esc_html($flag.' '.$symbol.' '.$code);?></td>
      <td><?php echo esc_html(number_format($micros/1000000,6,'.',''));?></td>
      <td><?php echo esc_html($symbol.$money($report['merchandise_display_minor']));?></td>
      <td><?php echo esc_html('$'.$money($report['eligible_subtotal_usd_minor']));?></td>
      <td>未达门槛</td>
      <td><?php echo esc_html($symbol.$money($report['order_display_total_minor']));?></td>
      <td><?php echo esc_html($policy['qualifies_by_policy_preview']?'预计符合':'不符合／待验证');?></td></tr>
    <?php endforeach;?>
    </tbody></table></div>
  <p class="warn">本表汇率是测试固定值，不是实时参考汇率、YayCurrency 报价或支付结算汇率。特殊商品运费仍由现有 Woo 运费规则负责；任何模拟免邮均不会改变实际费用。</p>
</section>
