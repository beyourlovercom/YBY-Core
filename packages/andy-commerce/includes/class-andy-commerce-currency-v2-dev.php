<?php
/**
 * Currency OS V2 C1: Dev-only visual administration & demo shortcode.
 * Not a real WooCommerce multi-currency checkout, pricing, or settlement engine.
 */
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_V2_Dev {
    const OPTION = 'andy_commerce_currency_v2_preview_draft';
    const CODES = ['USD','EUR','GBP','AUD','CAD'];
    const FLAGS = ['USD'=>'🇺🇸','EUR'=>'🇪🇺','GBP'=>'🇬🇧','AUD'=>'🇦🇺','CAD'=>'🇨🇦'];
    const SYMBOLS = ['USD'=>'$','EUR'=>'€','GBP'=>'£','AUD'=>'A$','CAD'=>'C$'];
    const NAMES = ['USD'=>'United States dollar','EUR'=>'Euro','GBP'=>'Pound sterling','AUD'=>'Australian dollar','CAD'=>'Canadian dollar'];
    const SNAPSHOT = ['USD'=>1.0,'EUR'=>0.89,'GBP'=>0.7465,'AUD'=>1.4217,'CAD'=>1.4042];
    private static $css_sent = false;
    static function dev(): bool {
        return function_exists('home_url') && parse_url(home_url('/'),PHP_URL_HOST)==='dev.beyourlover.com';
    }
    static function access(): bool {
        return self::dev() && function_exists('current_user_can') && current_user_can('manage_woocommerce');
    }
    static function defaults(): array {
        return [
            'enabled'=>['USD'=>true,'EUR'=>true,'GBP'=>true,'AUD'=>true,'CAD'=>true],
            'mode'=>['EUR'=>'auto','GBP'=>'auto','AUD'=>'auto','CAD'=>'auto'],
            'flag'=>true,'symbol'=>true,'code'=>true,'name'=>false,'small'=>true,
            'checkout_preview'=>true,'force_payment'=>false,'force_gateway'=>false,
            'product_switcher'=>false,'auto_rate'=>true,'country_detect'=>true,
            'fixed_prices'=>false,'product_notice'=>true,'approximate'=>false,
            'cache_compatible'=>true,'loading_mask'=>false,'bot_country'=>true
        ];
    }
    static function config(): array {
        $default=self::defaults();
        if (!self::dev()) return $default;
        $option=get_option(self::OPTION,[]);
        return is_array($option)?array_replace_recursive($default,$option):$default;
    }
    static function boot(): void {
        // WP harnesses sometimes omit optional hook APIs; full WP always has them.
        if(function_exists('add_shortcode'))add_shortcode('andy_currency_switcher',[__CLASS__,'shortcode']);
        if(function_exists('add_action')){
            add_action('wp_footer',[__CLASS__,'dev_frontend_preview'],99);
            if(function_exists('is_admin')&&is_admin())
                add_action('admin_post_andy_currency_v2_save',[__CLASS__,'save']);
        }
    }
    static function save(): void {
        if (!self::access() || ($_SERVER['REQUEST_METHOD']??'')!=='POST')
            wp_die('仅授权的 Dev 测试站管理员可以修改预览设置。','',['response'=>403]);
        check_admin_referer('andy_currency_v2_preview_save');
        $in=isset($_POST['ac_currency'])?wp_unslash($_POST['ac_currency']):[];
        if(!is_array($in)||count($in)>26)wp_die('无效的预览配置','',['response'=>400]);
        $section=isset($_POST['ac_section'])?sanitize_key(wp_unslash($_POST['ac_section'])):'manage';
        $out=self::config();
        // Save only the visible tab: switching tabs never resets other preferences.
        if($section==='manage') {
            foreach(self::CODES as $code) {
                $out['enabled'][$code]=$code==='USD'||(($in['enabled'][$code]??'')==='1');
                if($code!=='USD')$out['mode'][$code]=($in['mode'][$code]??'')==='fixed'?'fixed':'auto';
            }
        }
        $per_tab=[
            'checkout'=>['checkout_preview','force_payment','force_gateway'],
            'display'=>['flag','name','symbol','code','small','product_switcher'],
            'advanced'=>['auto_rate','country_detect','fixed_prices','product_notice',
                         'approximate','cache_compatible','loading_mask','bot_country']
        ];
        foreach($per_tab[$section]??[] as $key)$out[$key]=($in[$key]??'')==='1';
        update_option(self::OPTION,$out,false);
        if(!in_array($section,['manage','checkout','display','advanced','ledger'],true))$section='manage';
        wp_safe_redirect(add_query_arg(['page'=>'andy-commerce','tab'=>'currency-os','section'=>$section,'ac_saved'=>1],admin_url('admin.php')));
        exit;
    }
    static function widget_css(): string {
        return '<style>.ac-demo-switcher{font-family:-apple-system,BlinkMacSystemFont,"PingFang SC",Arial,sans-serif;display:inline-grid;gap:7px;color:#292327;max-width:100%;font-size:12px}.ac-demo-switcher select{border:1px solid #d8d3d4;border-radius:8px;background:#fff;padding:9px 11px;font-size:13px;color:#2d2b2b;width:100%;max-width:260px;min-width:130px;height:auto}.ac-demo-switcher.ac-compact select{min-width:112px;padding:7px 9px}.ac-demo-switcher strong{font-size:22px}.ac-demo-switcher small{font-size:11px;color:#766c70;max-width:260px}.ac-currency-front-test{position:fixed;bottom:20px;right:20px;z-index:99999999;background:#fff;border:1px solid #ddd5d7;box-shadow:0 10px 30px #0002;border-radius:15px;padding:17px;width:min(330px,calc(100vw - 32px));box-sizing:border-box}.ac-currency-front-test h3{font-size:16px;margin:0 0 10px!important;color:#262124}.ac-currency-front-test p{font-size:12px;margin:0 0 10px!important;color:#685e62}@media(max-width:600px){.ac-currency-front-test{left:12px;right:12px;bottom:12px;width:auto}}</style>';
    }
    static function shortcode($atts=[]): string {
        if(!self::access())return '';
        $atts=shortcode_atts(['style'=>'default','show_flag'=>'yes','show_symbol'=>'yes','show_code'=>'yes'],(array)$atts,'andy_currency_switcher');
        $cfg=self::config();$id='ac-currency-demo-'.wp_unique_id();
        $compact=$atts['style']==='compact';$display=[];
        $admin_labels=function_exists('is_admin')&&is_admin();
        foreach(self::CODES as $c)if(!empty($cfg['enabled'][$c])){
            $name=($cfg['flag']&&$atts['show_flag']==='yes'?self::FLAGS[$c].' ':'')
               .($cfg['symbol']&&$atts['show_symbol']==='yes'?self::SYMBOLS[$c].' ':'')
               .($cfg['code']&&$atts['show_code']==='yes'?$c:'')
               .($cfg['name']?' · '.self::NAMES[$c]:'');
            $display[$c]=['label'=>trim($name),'amount'=>self::SYMBOLS[$c].number_format(round(1234.56*self::SNAPSHOT[$c],2),2,'.',',')];
        }
        ob_start();
        if(!self::$css_sent){self::$css_sent=true;echo self::widget_css();}
        echo '<div class="ac-demo-switcher'.($compact?' ac-compact':'').'" id="'.esc_attr($id).'">';
        echo '<label for="'.esc_attr($id.'-select').'">'.esc_html($admin_labels?'货币切换器 · Dev 预览':'Currency Switcher · Dev').'</label>';
        echo '<select id="'.esc_attr($id.'-select').'" aria-label="Preview currency selection">';
        foreach($display as $code=>$v)echo '<option value="'.esc_attr($code).'">'.esc_html($v['label']).'</option>';
        echo '</select><strong class="ac-preview-amount" aria-live="polite">$1,234.56</strong>';
        echo '<small>'.esc_html($admin_labels?'模拟基准 USD $1,234.56 · 仅展示效果，不修改真实商品价格、购物车、运费或结账。':'Sample USD $1,234.56 · display only. No Woo prices, cart, shipping or checkout change.').'</small></div>';
        echo '<script>(function(){var el=document.getElementById('.wp_json_encode($id).');if(!el)return;var a='.wp_json_encode($display).';var s=el.querySelector("select");s.addEventListener("change",function(){if(a[s.value])el.querySelector(".ac-preview-amount").textContent=a[s.value].amount;});})();</script>';
        return (string)ob_get_clean();
    }
    static function dev_frontend_preview(): void {
        if(!self::access() || !isset($_GET['ac_currency_preview'])
            || sanitize_key(wp_unslash($_GET['ac_currency_preview']))!=='1')return;
        echo '<aside class="ac-currency-front-test" aria-label="Currency OS Dev-only storefront preview">';
        echo '<h3>Currency OS · Shortcode UAT</h3><p>Real WordPress front-end rendering. Preview currencies only.</p>';
        echo do_shortcode('[andy_currency_switcher style="compact" show_flag="yes" show_symbol="yes" show_code="yes"]');
        echo '</aside>';
    }
}
