<?php
/**
 * Currency OS V2 C1: Dev-only visual administration and explicit shortcode.
 * The Footer template controls shortcode placement; no automatic injection.
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
        if(function_exists('add_action') && function_exists('is_admin') && is_admin()){
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
        // Content-sized native select: current flag + symbol + ISO code + arrow.
        // No fixed width, UAT card, sample price, outer margin or padding.
        // field-sizing:content is supported by the Dev Chrome browser; on
        // browsers without it, width:auto remains a native-select fallback.
        return '<style>'
            .'.ac-demo-switcher{display:inline-flex!important;vertical-align:middle;'
            .'align-items:center;flex:0 0 auto;width:max-content;max-width:100%;'
            .'height:auto;min-height:0!important;line-height:normal;'
            .'margin:0!important;padding:0!important;border:0!important;'
            .'background:transparent!important;box-shadow:none!important;gap:0!important}'
            .'.ac-demo-switcher select,.ac-demo-switcher.ac-compact select{'
            .'display:inline-block!important;box-sizing:border-box!important;'
            .'field-sizing:content!important;width:auto!important;max-width:100%!important;min-width:0!important;'
            .'height:26px!important;min-height:26px!important;max-height:26px!important;'
            .'margin:0!important;padding:0 2px!important;'
            .'border:1px solid #d8d3d4!important;border-radius:5px!important;'
            .'background:#fff!important;box-shadow:none!important;'
            .'font-size:11px!important;font-weight:400!important;line-height:20px!important;'
            .'color:#353135!important;vertical-align:middle!important}'
            .'.ac-demo-switcher select:focus-visible{outline:2px solid #96394b;outline-offset:1px}'

            .'</style>';
    }
    static function shortcode($atts=[]): string {
        // Real Dev shopper selector takes precedence only after explicit R8 gate.
        if(class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
            && Andy_Commerce_Currency_Runtime_Dev_V1::allowed()) {
            return Andy_Commerce_Currency_Runtime_Dev_V1::shortcode();
        }
        if(!self::access())return '';
        $atts=shortcode_atts(['style'=>'default','show_flag'=>'yes','show_symbol'=>'yes','show_code'=>'yes'],(array)$atts,'andy_currency_switcher');
        $cfg=self::config();$id='ac-currency-demo-'.wp_unique_id();
        $compact=$atts['style']==='compact';
        $display=[];
        foreach(self::CODES as $c)if(!empty($cfg['enabled'][$c])){
            $name=($cfg['flag']&&$atts['show_flag']==='yes'?self::FLAGS[$c].' ':'')
               .($cfg['symbol']&&$atts['show_symbol']==='yes'?self::SYMBOLS[$c].' ':'')
               .($cfg['code']&&$atts['show_code']==='yes'?$c:'')
               .($cfg['name']?' · '.self::NAMES[$c]:'');
            $display[$c]=trim($name);
        }
        ob_start();
        if(!self::$css_sent){self::$css_sent=true;echo self::widget_css();}
        echo '<span data-ac-c3-preview="1" class="ac-demo-switcher'.($compact?' ac-compact':'').'" id="'.esc_attr($id).'">';
        // The native select itself shows the selected currency. No demo-price,
        // heading, label, extra description or explanatory card on storefront.
        echo '<select id="'.esc_attr($id.'-select').'" '
           .'aria-label="Preview currency selection only; does not change actual prices or checkout" '
           .'title="Preview only - no WooCommerce price or checkout changes">';
        foreach($display as $code=>$label)
            echo '<option value="'.esc_attr($code).'">'.esc_html($label).'</option>';
        echo '</select></span>';
        // Enqueue only with an explicit Dev admin shortcode render. Never add
        // a Woo pricing/filter hook and never expose preview rates to shoppers.
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script(
                'andy-currency-c3-dev-shadow',
                plugins_url('assets/currency-c3-dev-preview.js', ANDY_COMMERCE_PLUGIN_DIR . 'andy-commerce.php'),
                [], '1.0.8-c3', true
            );
        }
        return (string)ob_get_clean();
    }
}
