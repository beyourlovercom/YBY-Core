<?php
/** Currency OS V2 C1 Owner-approved layout; Dev-only draft settings. */
if (!defined('ABSPATH')) exit;
if (!Andy_Commerce_Currency_V2_Dev::access()){
    echo '<div class="notice notice-error"><p>Currency OS V2 仅允许在授权的 Dev 测试站预览。</p></div>';return;
}
$cfg=Andy_Commerce_Currency_V2_Dev::config();
$sections=['manage'=>'币种管理','checkout'=>'结账设置','display'=>'展示设置','advanced'=>'高级设置','ledger'=>'USD 营销核算','fx'=>'汇率核算'];
$section=!empty($currency_os_legacy_ledger)?'fx':(isset($_GET['section'])?sanitize_key(wp_unslash($_GET['section'])):'manage');
if(!isset($sections[$section]))$section='manage';
$root=add_query_arg(['page'=>'andy-commerce','tab'=>'currency-os'],admin_url('admin.php'));
$toggle=static function($key,$label,$value){
  echo '<label class="acv2-toggle"><span>'.esc_html($label).'</span><input type="checkbox" name="ac_currency['.esc_attr($key).']" value="1" '.checked($value,true,false).'><i aria-hidden="true"></i></label>';
};
?>
<style>
.acv2{font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",Arial,sans-serif;color:#262326;margin:20px 24px 30px 0;max-width:1390px}
.acv2 *{box-sizing:border-box}.acv2-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.acv2 h1{font-size:24px;font-weight:660;margin:0 0 4px}.acv2 h2{font-size:17px;margin:0 0 6px;font-weight:660}
.acv2 .muted{color:#757077;font-size:12px;line-height:1.5}
.acv2 .tag{font-size:11px;color:#713042;background:#f7eef1;border-radius:50px;padding:5px 10px;display:inline-block}
.acv2-nav{display:flex;gap:5px;overflow-x:auto;white-space:nowrap;border-bottom:1px solid #e0ddde;margin-bottom:17px}
.acv2-nav a{padding:13px 12px;border-bottom:3px solid transparent;text-decoration:none;color:#5d5659;font-weight:550;font-size:13px}
.acv2-nav a.active{border-bottom-color:#782d3f;color:#782d3f;font-weight:680}
.acv2-card{border:1px solid #e5e2e3;border-radius:12px;background:#fff;box-shadow:0 1px 3px #00000008;padding:20px;margin-bottom:16px}
.acv2-flex{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.acv2-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.acv2-table-scroll{overflow-x:auto}.acv2-table{width:100%;border-collapse:collapse;border:1px solid #e6e4e5;font-size:12px}
.acv2-table th,.acv2-table td{text-align:left;border-bottom:1px solid #eeecec;padding:12px 11px}
.acv2-table th{background:#faf9f9;font-weight:660}.acv2-table tr:last-child td{border-bottom:0}
.acv2-table td strong{font-size:13px}.acv2-table .flag{font-size:19px;margin-right:5px}
.acv2-table select,.acv2 select{border:1px solid #ded9db;border-radius:7px;max-width:100%;font-size:12px;min-height:34px}
.acv2-toggle{display:flex;align-items:center;justify-content:space-between;gap:15px;cursor:pointer;min-height:40px;font-size:13px;font-weight:500}
.acv2-toggle input{position:absolute;opacity:0;width:1px;height:1px}.acv2-toggle i{display:block;position:relative;width:37px;height:21px;flex:0 0 auto;background:#d5d4d4;border-radius:40px}
.acv2-toggle i:before{content:"";position:absolute;left:3px;top:3px;width:15px;height:15px;background:#fff;border-radius:50%;transition:transform .15s}
.acv2-toggle input:checked+i{background:#792f41}.acv2-toggle input:checked+i:before{transform:translateX(16px)}
.acv2-toggle input:focus-visible+i{outline:2px solid #792f41;outline-offset:2px}
.acv2-line{padding:11px 0;border-bottom:1px solid #eee}.acv2-line:last-child{border-bottom:0}
.acv2 code{background:#f7f4f5;border-radius:5px;padding:7px;display:inline-block;word-break:break-all;font-size:12px}
.acv2 .warn{border-left:3px solid #965e6d;background:#fbf7f8;color:#68555b;padding:11px;font-size:12px;line-height:1.65;margin-top:15px}
.acv2 .button-primary{background:#792f41!important;border-color:#792f41!important;border-radius:8px!important;padding:4px 15px!important}
.acv2-demo{border:1px dashed #cec0c4;border-radius:9px;padding:15px;margin-top:13px}
.acv2-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:13px}
@media(max-width:840px){.acv2{margin-right:8px}.acv2-card{padding:14px}.acv2-grid{grid-template-columns:1fr}.acv2-table{min-width:670px}.acv2-nav a{padding:11px 9px}}
</style>
<div class="acv2" id="andy-currency-v2-admin">
  <header class="acv2-head"><div><h1>Currency OS <span class="tag">V2 · Dev 测试后台</span></h1>
    <div class="muted">店铺基础币种：USD · 五币种管理 · 参考汇率只读</div></div>
    <span class="tag">设置与汇率核算一体化</span>
  </header>
  <?php if(isset($_GET['ac_saved'])&&$_GET['ac_saved']==='1'):?>
  <div class="notice notice-success"><p>Dev 预览设置已保存。WooCommerce 实际结账、运费与商品价格均未修改。</p></div><?php endif;?>
  <nav class="acv2-nav" aria-label="Currency OS 设置导航">
  <?php foreach($sections as $key=>$text): ?>
    <a class="<?php echo $key===$section?'active':'';?>" href="<?php echo esc_url(add_query_arg('section',$key,$root));?>"><?php echo esc_html($text);?></a>
  <?php endforeach;?></nav>
  <?php if($section!=='fx'): ?>
  <form action="<?php echo esc_url(admin_url('admin-post.php'));?>" method="post">
    <?php wp_nonce_field('andy_currency_v2_preview_save');?>
    <input type="hidden" name="action" value="andy_currency_v2_save">
    <input type="hidden" name="ac_section" value="<?php echo esc_attr($section);?>">
  <?php endif; ?>
<?php if($section==='fx'):?>
  <section class="acv2-card" aria-label="汇率核算">
    <h2>汇率核算</h2>
    <p class="muted">五币种参考汇率及 USD 财务核算只读预览；不会变更真实商品价格、运费或支付金额。</p>
    <?php $currency_os_v2_embedded=true; include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/currency-os-v1-dev-lab.php'; ?>
  </section>
<?php elseif($section==='manage'):?>
  <?php $currency_names=['USD'=>'美元','EUR'=>'欧元','GBP'=>'英镑','AUD'=>'澳元','CAD'=>'加元']; ?>
  <section class="acv2-card">
    <div class="acv2-flex"><div><h2>币种管理</h2><div class="muted">默认基础币种为美元（USD），财务核算基准已锁定。</div></div><span class="tag">迁移配置预览</span></div>
    <div class="acv2-table-scroll" style="margin-top:16px"><table class="acv2-table">
    <thead><tr><th>币种</th><th>价格示例</th><th>汇率 · 历史快照</th><th>更新方式</th><th>显示状态</th></tr></thead>
    <tbody><?php foreach(Andy_Commerce_Currency_V2_Dev::CODES as $c):
      $flag=Andy_Commerce_Currency_V2_Dev::FLAGS[$c];
      $name=$currency_names[$c];
      $rate=Andy_Commerce_Currency_V2_Dev::SNAPSHOT[$c];
      $symbol=Andy_Commerce_Currency_V2_Dev::SYMBOLS[$c];
    ?><tr>
      <td><strong><span class="flag"><?php echo esc_html($flag);?></span><?php echo esc_html($name);?></strong> <span class="muted">(<?php echo esc_html($c);?>)</span></td>
      <td><?php echo esc_html($symbol.number_format(1234.56*$rate,2,'.',','));?></td>
      <td><?php echo esc_html(number_format($rate,6,'.',','));?></td>
      <td><?php if($c==='USD'):?>基础币种 · 固定<?php else:?><select aria-label="<?php echo esc_attr($c);?> 汇率模式" name="ac_currency[mode][<?php echo esc_attr($c);?>]"><option value="auto" <?php selected($cfg['mode'][$c],'auto');?>>自动更新</option><option value="fixed" <?php selected($cfg['mode'][$c],'fixed');?>>固定汇率（草稿）</option></select><?php endif;?></td>
      <td><?php if($c==='USD'):?>始终启用<?php else:?>
        <label class="acv2-toggle"><input type="checkbox" name="ac_currency[enabled][<?php echo esc_attr($c);?>]" value="1" <?php checked($cfg['enabled'][$c]);?>><i></i></label>
      <?php endif;?></td>
    </tr><?php endforeach;?></tbody></table></div>
    <p class="warn">此处汇率仅用于复刻已批准的 YayCurrency 历史设置，不代表实时市场汇率；附加费为 0。实时参考汇率请在「汇率核算 V1」中查看。</p>
  </section>
<?php elseif($section==='checkout'):?>
  <section class="acv2-card"><h2>结账设置</h2><p class="muted">以下为 YayCurrency 原有设置的参考配置。Currency OS V2 尚未启用真实外币结账。</p>
    <div class="acv2-line"><?php $toggle('checkout_preview','允许不同币种结账（目标配置）',$cfg['checkout_preview']);?></div>
    <div class="acv2-table-scroll"><table class="acv2-table"><thead><tr><th>币种</th><th>原插件配置</th><th>支付网关验证</th></tr></thead><tbody>
      <?php foreach(Andy_Commerce_Currency_V2_Dev::CODES as $c):?><tr><td><?php echo esc_html(Andy_Commerce_Currency_V2_Dev::FLAGS[$c].' '.$c);?></td><td>全部支付方式</td><td>待沙盒验证</td></tr><?php endforeach;?>
    </tbody></table></div>
    <div class="acv2-line"><?php $toggle('force_payment','强制使用指定币种付款',$cfg['force_payment']);?></div>
    <div class="acv2-line"><?php $toggle('force_gateway','为指定支付方式强制设置币种',$cfg['force_gateway']);?></div>
    <p class="warn">以上开关仅保存 Dev 测试草稿，不会影响 Stripe、WooPayments、PayPal 的实际收款配置。</p>
  </section>
<?php elseif($section==='display'):?>
  <div class="acv2-grid">
    <section class="acv2-card"><h2>切换器位置</h2><p class="muted">通过 WordPress 短代码预览新版货币切换器。</p>
      <div class="acv2-line"><?php $toggle('product_switcher','在商品详情页自动显示',$cfg['product_switcher']);?></div>
      <p>默认短代码</p><code>[andy_currency_switcher]</code>
      <p>Header / Bricks 紧凑样式</p><code>[andy_currency_switcher style="compact" show_flag="yes" show_symbol="yes" show_code="yes"]</code>
      <p class="muted">使用 Bricks Footer 模板的「Shortcode」元素放入上方短代码。控件只在设置了短代码的位置显示，不再通过 wp_footer 注入右下角悬浮入口。Dev 测试期间仅有已登录管理员可见。</p>
    </section>
    <section class="acv2-card"><h2>切换器外观</h2><p class="muted">采用已审核通过的展示默认值。</p>
      <?php foreach(['flag'=>'显示国旗','name'=>'显示货币名称','symbol'=>'显示货币符号','code'=>'显示币种代码','small'=>'使用 Small 小尺寸'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <div class="acv2-demo"><p class="muted">后台短代码实际渲染预览</p><?php echo do_shortcode('[andy_currency_switcher style="compact"]');?></div>
    </section>
  </div>
<?php elseif($section==='advanced'):?>
  <div class="acv2-grid">
    <section class="acv2-card"><h2>汇率与国家设置</h2>
      <?php foreach(['fixed_prices'=>'为每种币种设置固定商品价格','auto_rate'=>'自动更新汇率（每日一次）','country_detect'=>'根据国家自动选择币种','bot_country'=>'对搜索引擎爬虫应用国家识别（待 SEO 验证）'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <p class="muted">参考汇率来源：Frankfurter（基准 USD）。「汇率核算 V1」仅执行只读获取。</p>
      <div class="acv2-table-scroll"><table class="acv2-table"><thead><tr><th>币种</th><th>默认国家或地区</th></tr></thead><tbody><tr><td>USD</td><td>美国／默认回退</td></tr><tr><td>EUR</td><td>欧元区 · 待验证</td></tr><tr><td>GBP</td><td>英国</td></tr><tr><td>AUD</td><td>澳大利亚</td></tr><tr><td>CAD</td><td>加拿大</td></tr></tbody></table></div>
    </section>
    <section class="acv2-card"><h2>展示安全与缓存</h2>
      <?php foreach(['product_notice'=>'在商品页显示币种提示','approximate'=>'显示近似价格','cache_compatible'=>'兼容缓存插件','loading_mask'=>'加载遮罩'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <p class="warn">按国家显示价格、缓存及 Google 爬虫规则须完成独立 UAT，之后才能用于真实商品价格。</p>
    </section>
  </div>
<?php else:?>
  <section class="acv2-card"><h2>USD 营销核算</h2><p class="muted">USD 是唯一核算基准，汇率转换不会改变免邮或优惠资格。</p>
    <div class="acv2-grid"><div class="acv2-card">财务核算基准<h2>USD</h2></div><div class="acv2-card">符合资格的商品小计（示例）<h2>$95.00</h2></div><div class="acv2-card">门槛示例（非实际规则）<h2>$99.00</h2></div><div class="acv2-card">实际收款与退款<h2>尚未接入</h2></div></div>
    <p class="warn">真实营销活动由 Marketing V2 执行，实际运费以 WooCommerce 所选方式为准。本页仅用于说明和预览。</p>
  </section>
<?php endif;?>
  <?php if($section!=='fx'): ?>
    <footer class="acv2-footer"><span class="muted">仅保存 Dev 预览草稿 · 不修改 WWW/YayCurrency、实际结账、订单或价格</span>
    <?php if($section!=='ledger'):?><button type="submit" class="button button-primary">保存 Dev 预览设置</button><?php endif;?></footer>
  </form>
  <?php endif; ?>
</div>
