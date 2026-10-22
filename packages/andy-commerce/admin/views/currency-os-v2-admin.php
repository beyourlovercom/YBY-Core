<?php
/** Currency OS V2 C1 Owner-approved layout; Dev-only draft settings. */
if (!defined('ABSPATH')) exit;
if (!Andy_Commerce_Currency_V2_Dev::access()){
    echo '<div class="notice notice-error"><p>Currency OS V2 preview available only on authorized Dev.</p></div>';return;
}
$cfg=Andy_Commerce_Currency_V2_Dev::config();
$sections=['manage'=>'Manage Currency','checkout'=>'Checkout Options','display'=>'Display Options','advanced'=>'Advanced Settings','ledger'=>'USD Marketing Ledger'];
$section=isset($_GET['section'])?sanitize_key(wp_unslash($_GET['section'])):'manage';
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
  <header class="acv2-head"><div><h1>Currency OS <span class="tag">V2 · Dev Studio</span></h1>
    <div class="muted">Store base: USD · 5 currencies · reference FX read-only</div></div>
    <a class="button" href="<?php echo esc_url(add_query_arg(['currency_screen'=>'ledger'],$root)); ?>">Open FX Ledger V1</a>
  </header>
  <?php if(isset($_GET['ac_saved'])&&$_GET['ac_saved']==='1'):?>
  <div class="notice notice-success"><p>Dev display draft saved. Live Woo checkout, shipping and prices remain unchanged.</p></div><?php endif;?>
  <nav class="acv2-nav" aria-label="Currency OS sections">
  <?php foreach($sections as $key=>$text): ?>
    <a class="<?php echo $key===$section?'active':'';?>" href="<?php echo esc_url(add_query_arg('section',$key,$root));?>"><?php echo esc_html($text);?></a>
  <?php endforeach;?></nav>
  <form action="<?php echo esc_url(admin_url('admin-post.php'));?>" method="post">
    <?php wp_nonce_field('andy_currency_v2_preview_save');?>
    <input type="hidden" name="action" value="andy_currency_v2_save">
    <input type="hidden" name="ac_section" value="<?php echo esc_attr($section);?>">
<?php if($section==='manage'):?>
  <section class="acv2-card">
    <div class="acv2-flex"><div><h2>Manage Currency</h2><div class="muted">Your default currency is United States dollar (USD). Base ledger is locked.</div></div><span class="tag">Migration preview</span></div>
    <div class="acv2-table-scroll" style="margin-top:16px"><table class="acv2-table">
    <thead><tr><th>Currency</th><th>Preview</th><th>Rate · historical snapshot</th><th>Mode</th><th>Display</th></tr></thead>
    <tbody><?php foreach(Andy_Commerce_Currency_V2_Dev::CODES as $c):
      $flag=Andy_Commerce_Currency_V2_Dev::FLAGS[$c];
      $name=Andy_Commerce_Currency_V2_Dev::NAMES[$c];
      $rate=Andy_Commerce_Currency_V2_Dev::SNAPSHOT[$c];
      $symbol=Andy_Commerce_Currency_V2_Dev::SYMBOLS[$c];
    ?><tr>
      <td><strong><span class="flag"><?php echo esc_html($flag);?></span><?php echo esc_html($name);?></strong> <span class="muted">(<?php echo esc_html($c);?>)</span></td>
      <td><?php echo esc_html($symbol.number_format(1234.56*$rate,2,'.',','));?></td>
      <td><?php echo esc_html(number_format($rate,6,'.',','));?></td>
      <td><?php if($c==='USD'):?>Base · Fixed<?php else:?><select aria-label="<?php echo esc_attr($c);?> rate mode" name="ac_currency[mode][<?php echo esc_attr($c);?>]"><option value="auto" <?php selected($cfg['mode'][$c],'auto');?>>Auto</option><option value="fixed" <?php selected($cfg['mode'][$c],'fixed');?>>Fixed draft</option></select><?php endif;?></td>
      <td><?php if($c==='USD'):?>Always on<?php else:?>
        <label class="acv2-toggle"><input type="checkbox" name="ac_currency[enabled][<?php echo esc_attr($c);?>]" value="1" <?php checked($cfg['enabled'][$c]);?>><i></i></label>
      <?php endif;?></td>
    </tr><?php endforeach;?></tbody></table></div>
    <p class="warn">Exchange rates in this table are your approved YayCurrency screenshot baseline, not current market rates. No fee (0). Live FX reference comparison remains in FX Ledger V1.</p>
  </section>
<?php elseif($section==='checkout'):?>
  <section class="acv2-card"><h2>Checkout Options</h2><p class="muted">Legacy settings for reference only. Real cross-currency checkout is not enabled by Currency OS V2.</p>
    <div class="acv2-line"><?php $toggle('checkout_preview','Checkout in different currency — target configuration',$cfg['checkout_preview']);?></div>
    <div class="acv2-table-scroll"><table class="acv2-table"><thead><tr><th>Currency</th><th>Legacy setting</th><th>Gateway test</th></tr></thead><tbody>
      <?php foreach(Andy_Commerce_Currency_V2_Dev::CODES as $c):?><tr><td><?php echo esc_html(Andy_Commerce_Currency_V2_Dev::FLAGS[$c].' '.$c);?></td><td>All payment methods</td><td>Pending sandbox verification</td></tr><?php endforeach;?>
    </tbody></table></div>
    <div class="acv2-line"><?php $toggle('force_payment','Force payment in a specific currency',$cfg['force_payment']);?></div>
    <div class="acv2-line"><?php $toggle('force_gateway','Force currency for a specific payment method',$cfg['force_gateway']);?></div>
    <p class="warn">These are editable Dev draft switches only. Stripe, WooPayments and PayPal operations are unchanged.</p>
  </section>
<?php elseif($section==='display'):?>
  <div class="acv2-grid">
    <section class="acv2-card"><h2>Switcher Location</h2><p class="muted">Use the existing WordPress shortcode interface for the new demo.</p>
      <div class="acv2-line"><?php $toggle('product_switcher','Show automatically on product page',$cfg['product_switcher']);?></div>
      <p>Default shortcode</p><code>[andy_currency_switcher]</code>
      <p>Header / Bricks compact</p><code>[andy_currency_switcher style="compact" show_flag="yes" show_symbol="yes" show_code="yes"]</code>
      <p><a target="_blank" rel="noopener" href="<?php echo esc_url(add_query_arg('ac_currency_preview','1',home_url('/')));?>">Open real Dev frontend shortcode preview ↗</a></p>
    </section>
    <section class="acv2-card"><h2>Switcher Customizer</h2><p class="muted">Owner-approved display defaults.</p>
      <?php foreach(['flag'=>'Show flag','name'=>'Show currency name','symbol'=>'Show currency symbol','code'=>'Show currency code','small'=>'Small switcher size'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <div class="acv2-demo"><p class="muted">Live registered shortcode in WP Admin</p><?php echo do_shortcode('[andy_currency_switcher style="compact"]');?></div>
    </section>
  </div>
<?php elseif($section==='advanced'):?>
  <div class="acv2-grid">
    <section class="acv2-card"><h2>Rate & Country Settings</h2>
      <?php foreach(['fixed_prices'=>'Fixed product price for each currency','auto_rate'=>'Update exchange rate automatically · daily','country_detect'=>'Auto-select currency by countries','bot_country'=>'Apply country logic to crawlers (pending SEO verification)'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <p class="muted">Reference provider: Frankfurter (USD base). Current FX reads are GET-only in Ledger V1.</p>
      <div class="acv2-table-scroll"><table class="acv2-table"><thead><tr><th>Currency</th><th>Country defaults</th></tr></thead><tbody><tr><td>USD</td><td>United States / fallback</td></tr><tr><td>EUR</td><td>Eurozone · to validate</td></tr><tr><td>GBP</td><td>United Kingdom</td></tr><tr><td>AUD</td><td>Australia</td></tr><tr><td>CAD</td><td>Canada</td></tr></tbody></table></div>
    </section>
    <section class="acv2-card"><h2>Display Safety & Cache</h2>
      <?php foreach(['product_notice'=>'Display currency notice on product page','approximate'=>'Show approximate price','cache_compatible'=>'Compatible with cache plugins','loading_mask'=>'Loading mask'] as $k=>$label):?><div class="acv2-line"><?php $toggle($k,$label,$cfg[$k]);?></div><?php endforeach;?>
      <p class="warn">Country-aware cache and Google crawler behavior need dedicated UAT before enabling any real product price localization.</p>
    </section>
  </div>
<?php else:?>
  <section class="acv2-card"><h2>USD Marketing Ledger</h2><p class="muted">USD is authoritative; conversion never unlocks a shipping discount.</p>
    <div class="acv2-grid"><div class="acv2-card">Accounting basis<h2>USD</h2></div><div class="acv2-card">Example eligible merchandise<h2>$95.00</h2></div><div class="acv2-card">Example threshold (not a rule)<h2>$99.00</h2></div><div class="acv2-card">Real payment / refunds<h2>Not connected</h2></div></div>
    <p class="warn">Marketing V2 owns actual campaign execution. WooCommerce owns real selected shipping rates. This page is explanatory only.</p>
  </section>
<?php endif;?>
    <footer class="acv2-footer"><span class="muted">Dev UI Draft · no WWW/YayCurrency, checkout, order or price changes</span>
    <?php if($section!=='ledger'):?><button type="submit" class="button button-primary">Save Dev Draft</button><?php endif;?></footer>
  </form>
</div>
