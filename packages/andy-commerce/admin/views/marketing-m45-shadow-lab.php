<?php
/** Andy Commerce M4–M5 laboratory view: admin-only, Dev-only, GET-only. */
if (!defined('ABSPATH') || !current_user_can('manage_woocommerce')
    || !class_exists('Andy_Commerce_M3_Dev_Bridge')
    || !Andy_Commerce_M3_Dev_Bridge::enabled()) {
    wp_die('Marketing diagnostics unavailable', '', ['response'=>403]);
}
require_once ANDY_COMMERCE_PLUGIN_DIR.'includes/class-andy-commerce-m4-shipping-shadow.php';
require_once ANDY_COMMERCE_PLUGIN_DIR.'includes/class-andy-commerce-m5-progress-projection.php';
$cases = [
    'discount' => [
        'title'=>'折扣后不足门槛',
        'description'=>'商品 $60，优惠 $15，商品折后 $45；门槛 $49。',
        'threshold'=>4900,
        'lines'=>[['line_subtotal_minor'=>6000,'line_total_minor'=>4500,'gift'=>false,'eligible'=>true]],
    ],
    'exact' => [
        'title'=>'恰好达到门槛',
        'description'=>'商品 $49，无折扣；两种口径均满足 $49。',
        'threshold'=>4900,
        'lines'=>[['line_subtotal_minor'=>4900,'line_total_minor'=>4900,'gift'=>false,'eligible'=>true]],
    ],
    'gift' => [
        'title'=>'赠品不能凑门槛',
        'description'=>'有效商品 $49 + 自动赠品 $60；模拟门槛 $100，赠品不计入。',
        'threshold'=>10000,
        'lines'=>[
            ['line_subtotal_minor'=>4900,'line_total_minor'=>4900,'gift'=>false,'eligible'=>true],
            ['line_subtotal_minor'=>6000,'line_total_minor'=>6000,'gift'=>true,'eligible'=>true],
        ],
    ],
    'ineligible' => [
        'title'=>'不合格商品不参与',
        'description'=>'有效商品 $45 + 不合格商品 $99；门槛 $49。',
        'threshold'=>4900,
        'lines'=>[
            ['line_subtotal_minor'=>4500,'line_total_minor'=>4500,'gift'=>false,'eligible'=>true],
            ['line_subtotal_minor'=>9900,'line_total_minor'=>9900,'gift'=>false,'eligible'=>false],
        ],
    ],
];
$selected=isset($_GET['m45_case']) && is_string($_GET['m45_case'])
    ? sanitize_key(wp_unslash($_GET['m45_case'])) : 'discount';
if (!isset($cases[$selected])) { $selected='discount'; }
$case=$cases[$selected];
$shadow=Andy_Commerce_M4_Shipping_Shadow::compare($case['lines'],$case['threshold']);
$real_basis=Andy_Commerce_Shipping_Promotion_Policy::ELIGIBILITY_BASIS;
$progress=Andy_Commerce_M5_Progress_Projection::project([
    'effective_policy_basis'=>$real_basis,
    'woocommerce_rate_confirmed'=>false,
    'source'=>'unverified',
    'discounted_merchandise_minor'=>$shadow['proposed_merchandise_minor'],
    'threshold_minor'=>$case['threshold']
]);
$money=static fn(int $minor):string=>'$'.number_format($minor/100,2,'.',',');
$preview_url=static fn(string $key):string=>add_query_arg([
    'page'=>'andy-commerce','tab'=>'marketing-promotions','m45_preview'=>'1','m45_case'=>$key,
],admin_url('admin.php'));
?>
<style>
.m45-lab{max-width:1120px;margin:24px 20px 32px 0;color:#172b23;font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",sans-serif}
.m45-lab *{box-sizing:border-box}.m45-title{font-size:27px;line-height:1.25;margin:0 0 8px}
.m45-kicker{letter-spacing:.07em;color:#006241;font-size:12px;font-weight:800;text-transform:uppercase}
.m45-sub{color:#52685d;line-height:1.6;margin:0 0 17px;max-width:830px}
.m45-warning{border-left:4px solid #996c00;background:#fff9e7;padding:13px 18px;border-radius:6px;line-height:1.6;font-size:13px}
.m45-controls{display:flex;gap:8px;flex-wrap:wrap;margin:21px 0}
.m45-pill{display:inline-block;text-decoration:none;border:1px solid #b8d0c3;border-radius:30px;padding:10px 16px;background:#fff;color:#00583a;font-weight:650}
.m45-pill[aria-current="true"]{background:#006241;color:white;border-color:#006241}
.m45-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:12px 0}
.m45-panel{background:#fff;border:1px solid #dce7df;border-radius:14px;padding:22px;min-width:0;box-shadow:0 2px 11px rgba(0,0,0,.04)}
.m45-panel h2{font-size:16px;margin:0 0 12px;color:#264a3a}
.m45-amount{font-weight:800;font-size:33px;line-height:1.3;letter-spacing:-.02em}
.m45-badge{display:inline-block;padding:5px 9px;border-radius:5px;background:#f4f0e5;color:#6c5100;font-size:12px;margin-top:8px}
.m45-badge.no{background:#f9e8e5;color:#7c372b}.m45-row{display:flex;justify-content:space-between;gap:12px;align-items:center;border-top:1px solid #e6eee8;padding:13px 0}
.m45-row span{color:#557264}.m45-row strong{text-align:right}
.m45-meter{height:8px;border-radius:20px;background:#e5eee7;margin:14px 0;overflow:hidden}
.m45-meter span{display:block;height:100%;background:#006241;border-radius:20px}
.m45-link{color:#006241;text-decoration:underline;font-weight:650}
.m45-note{font-size:12px;line-height:1.6;color:#5f7469;margin-top:16px}
@media(max-width:700px){.m45-lab{margin:12px 10px 30px 0}.m45-title{font-size:23px}.m45-grid{grid-template-columns:1fr}.m45-panel{padding:17px}.m45-amount{font-size:29px}.m45-pill{padding:9px 12px}}
</style>
<section class="m45-lab" aria-labelledby="m45-heading">
  <p class="m45-kicker">Andy Commerce · M4/M5 · Dev laboratory</p>
  <h1 class="m45-title" id="m45-heading">免邮计算规则：新旧口径对比</h1>
  <p class="m45-sub">纯模拟预览：直观检查折扣后的商品金额如何影响门槛。与顾客真实免邮资格、运费选项或结账金额无关。</p>
  <div class="m45-warning" role="note"><strong>安全状态：模拟环境，所有优惠 OFF。</strong>
    现行 BYL49 使用折扣前商品小计，未迁移。新规则和 M5 进度条尚未启用；此页面不会保存设置、发送请求或更改 Woo 购物车。
  </div>
  <nav class="m45-controls" aria-label="模拟测试情形">
  <?php foreach($cases as $key=>$fixture): ?>
    <a class="m45-pill" href="<?php echo esc_url($preview_url($key)); ?>"
       aria-current="<?php echo $key===$selected?'true':'false'; ?>"><?php echo esc_html($fixture['title']); ?></a>
  <?php endforeach; ?>
  </nav>
  <p class="m45-sub"><?php echo esc_html($case['description']); ?></p>
  <div class="m45-grid">
    <article class="m45-panel" aria-label="现行旧规则">
      <h2>现行 BYL49（折扣前）</h2>
      <div class="m45-amount"><?php echo esc_html($money($shadow['legacy_merchandise_minor'])); ?></div>
      <span class="m45-badge <?php echo $shadow['legacy_eligible']?'':'no'; ?>"><?php echo $shadow['legacy_eligible']?'模拟满足门槛':'模拟未满足门槛'; ?></span>
      <p class="m45-note">现行计算口径仅供比较；真实资格依旧由 Woo 运费规则决定。</p>
    </article>
    <article class="m45-panel" aria-label="提议新规则">
      <h2>未来新规则（折扣后，不含税、运费和赠品）</h2>
      <div class="m45-amount"><?php echo esc_html($money($shadow['proposed_merchandise_minor'])); ?></div>
      <span class="m45-badge <?php echo $shadow['proposed_eligible']?'':'no'; ?>"><?php echo $shadow['proposed_eligible']?'模拟满足门槛':'模拟未满足门槛'; ?></span>
      <p class="m45-note">这是待授权的新口径模拟结果，不代表购物车已执行此规则。</p>
    </article>
  </div>
  <div class="m45-panel">
    <h2>M5 新免邮进度 · 设计预览（模拟）</h2>
    <div class="m45-row"><span>模拟门槛</span><strong><?php echo esc_html($money($shadow['threshold_minor'])); ?></strong></div>
    <div class="m45-row"><span>折扣后商品金额</span><strong><?php echo esc_html($money($shadow['proposed_merchandise_minor'])); ?></strong></div>
    <div class="m45-row"><span>距离门槛（仅数学计算）</span><strong><?php echo esc_html($money(max(0,$shadow['threshold_minor']-$shadow['proposed_merchandise_minor']))); ?></strong></div>
    <div class="m45-meter" aria-hidden="true"><span style="width:<?php echo esc_attr((string)min(100,intdiv($shadow['proposed_merchandise_minor']*100,$shadow['threshold_minor']))); ?>%"></span></div>
    <p class="m45-note"><strong>真实 M5 进度状态：<?php echo $progress['show_progress']?'READY':'HIDDEN'; ?></strong>
      — 原因 <?php echo esc_html((string)($progress['reason']??'unknown')); ?>。只有切换到经过批准的新口径且 Woo 实际运费结果已验证后，才可显示真实进度；绝不将数学门槛等同免邮已生效。</p>
  </div>
  <p class="m45-note"><a class="m45-link" href="<?php echo esc_url(add_query_arg(['page'=>'andy-commerce','tab'=>'marketing-promotions'],admin_url('admin.php'))); ?>">返回 Marketing 草稿列表</a>
  · GET-only，无 JavaScript 修改购物车，无优惠启用，无后台数据库写入。</p>
</section>
