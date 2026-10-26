<?php
/**
 * Currency R8-R10: DEV-ONLY real shopper currency runtime.
 * Separate from isolated C-R1..C-R7 synthetic harness. USD is the store base.
 * OFF by default. Fails closed on WWW, missing/stale rates, YayCurrency overlap.
 * No provider settlement, gateway currency, order/refund writes or marketing.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_Runtime_Dev_V1 {
    public const OPTION='andy_commerce_currency_runtime_dev_v1';
    public const FX_OPTION='andy_commerce_currency_runtime_fx_snapshot_v1';
    public const CODES=['USD','EUR','GBP','AUD','CAD'];
    public const MAX_AGE=72*3600;
    private static bool $running=false;
    private static array $shippingConverted=[];
    public static function site(): bool {
        return function_exists('home_url')
            && wp_parse_url(home_url('/'),PHP_URL_HOST)==='dev.beyourlover.com'
            && function_exists('get_option')
            && strtoupper((string)get_option('woocommerce_currency',''))==='USD';
    }
    public static function legacy_conflict(): bool {
        if (!function_exists('is_plugin_active')) {
            if(defined('ABSPATH')) {
                $p=ABSPATH.'wp-admin/includes/plugin.php';
                if(is_file($p)) require_once $p;
            }
        }
        if (!function_exists('is_plugin_active')) return true;
        foreach(['yaycurrency/yaycurrency.php','yaycurrency-pro/yaycurrency.php',
                 'yaycurrency-premium/yaycurrency.php','yaycurrency-pro/yaycurrency-pro.php'] as $slug) {
            if(is_plugin_active($slug)) return true;
        }
        // A future extension can veto runtime if another currency engine exists.
        return function_exists('apply_filters')
            ? (bool)apply_filters('andy_commerce_currency_runtime_conflict',false) : true;
    }
    public static function settings(): array {
        $v=get_option(self::OPTION,[]);
        if(!is_array($v)) $v=[];
        return [
            'enabled'=>($v['enabled']??false)===true,
            'allowed'=>array_values(array_intersect(self::CODES,is_array($v['allowed']??null)?$v['allowed']:self::CODES)),
        ];
    }
    public static function rates(): ?array {
        $v=get_option(self::FX_OPTION,[]);
        if(!is_array($v)
          || ($v['base']??null)!=='USD'
          || ($v['rate_source']??null)!==Andy_Commerce_Currency_Reference_FX_V1::SOURCE
          || !is_int($v['retrieved_at_utc']??null)
          || $v['retrieved_at_utc']>time()+60
          || time()-$v['retrieved_at_utc']>self::MAX_AGE
          || !is_array($v['rate_micros']??null)) return null;
        foreach(self::CODES as $code) {
            $rate=$v['rate_micros'][$code]??null;
            if(!is_int($rate)||$rate<100000||$rate>10000000
              || ($code==='USD' && $rate!==1000000)) return null;
        }
        return $v;
    }
    public static function allowed(): bool {
        return self::site() && self::settings()['enabled']
            && self::rates()!==null && !self::legacy_conflict()
            && class_exists('WooCommerce') && class_exists('Andy_Commerce_Currency_OS_V1');
    }
    public static function selected(): string {
        if(!self::allowed()) return 'USD';
        $selected='USD';
        if(function_exists('WC') && WC()->session) {
            $candidate=WC()->session->get('ac_currency_runtime_dev_selected');
            if(is_string($candidate)) $selected=$candidate;
        }
        return in_array($selected,self::settings()['allowed'],true)?$selected:'USD';
    }
    public static function rate(): int {
        $rates=self::rates();
        if(!$rates) throw new LogicException('No fresh FX snapshot');
        return $rates['rate_micros'][self::selected()];
    }
    public static function convert($value) {
        if($value==='' || $value===null) return $value;
        $raw=(string)$value;
        if(!is_scalar($value)||!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D',$raw))
            throw new InvalidArgumentException('Invalid USD minor amount');
        [$whole,$fraction]=array_pad(explode('.',$raw,2),2,'');
        $minor=(int)$whole*100+(int)str_pad($fraction,2,'0');
        $local=Andy_Commerce_Currency_OS_V1::convert_usd_minor($minor,self::rate());
        return number_format($local/100,2,'.','');
    }
    public static function currency($code): string { return self::allowed()?self::selected():(string)$code; }
    public static function price($price,$product) {
        return self::allowed()?self::convert($price):$price;
    }
    public static function variations($price,$variation,$parent) {
        return self::allowed()?self::convert($price):$price;
    }
    public static function variation_hash($hash,$product,$display) {
        if(self::allowed()&&is_array($hash)) $hash['ac_currency_runtime_dev']=[
            self::selected(), self::rate(), (int)self::rates()['retrieved_at_utc']
        ];
        return $hash;
    }
    public static function coupon($amount,$coupon) {
        if(!self::allowed() || !($coupon instanceof WC_Coupon))return $amount;
        if(!in_array($coupon->get_discount_type('edit'),['fixed_cart','fixed_product'],true))return $amount;
        return self::convert($amount);
    }
    public static function shipping($rates,$package) {
        if(!self::allowed()||!is_array($rates))return $rates;
        foreach($rates as $rate) {
            if(!($rate instanceof WC_Shipping_Rate) ||
               !in_array($rate->get_method_id(),['flat_rate'],true))continue;
            // Shipping taxes require the full native WC_Tax route. Never rewrite
            // a rate with pre-existing tax allocations or unsupported methods.
            if($rate->get_taxes())continue;
            $obj=spl_object_id($rate);
            if(isset(self::$shippingConverted[$obj])) continue;
            $rate->set_cost(self::convert($rate->get_cost()));
            self::$shippingConverted[$obj]=true;
        }
        return $rates;
    }
    public static function boot(): void {
        if(!self::allowed() || self::$running)return;
        self::$running=true;
        add_filter('woocommerce_currency',[__CLASS__,'currency'],60,1);
        foreach(['woocommerce_product_get_price',
                  'woocommerce_product_get_regular_price',
                  'woocommerce_product_get_sale_price',
                  'woocommerce_product_variation_get_price',
                  'woocommerce_product_variation_get_regular_price',
                  'woocommerce_product_variation_get_sale_price'] as $hook)
            add_filter($hook,[__CLASS__,'price'],60,2);
        foreach(['price','regular_price','sale_price'] as $key)
            add_filter('woocommerce_variation_prices_'.$key,[__CLASS__,'variations'],60,3);
        add_filter('woocommerce_get_variation_prices_hash',[__CLASS__,'variation_hash'],60,3);
        add_filter('woocommerce_coupon_get_amount',[__CLASS__,'coupon'],60,2);
        add_filter('woocommerce_package_rates',[__CLASS__,'shipping'],60,2);
        add_action('wp_loaded',[__CLASS__,'switch_request'],25,0);
    }
    public static function switch_request(): void {
        if(!self::allowed() || ($_SERVER['REQUEST_METHOD']??'')!=='POST'
            || ($_POST['ac_currency_action']??'')!=='select')return;
        $nonce=isset($_POST['ac_currency_nonce']) ? sanitize_text_field(wp_unslash($_POST['ac_currency_nonce'])) : '';
        if(!wp_verify_nonce($nonce,'ac_currency_dev_select'))wp_die('币种切换验证失败','',['response'=>403]);
        $code=isset($_POST['ac_currency_code'])?sanitize_text_field(wp_unslash($_POST['ac_currency_code'])):'';
        if(!in_array($code,self::settings()['allowed'],true))wp_die('不支持的币种','',['response'=>400]);
        if(!function_exists('WC') || !WC()->session)wp_die('购物车会话不存在','',['response'=>409]);
        WC()->session->set('ac_currency_runtime_dev_selected',$code);
        if(WC()->cart) {
            // Currency changes invalidate native Woo shipping package session caches.
            $packages=WC()->shipping()?WC()->shipping()->get_packages():[];
            foreach(array_keys($packages) as $i) WC()->session->__unset('shipping_for_package_'.$i);
            WC()->cart->calculate_totals();
        }
        // Redirect to same-host request URI without accepting an external redirect.
        $referer=wp_get_referer();
        $back=$referer && wp_validate_redirect($referer,false)?$referer:home_url('/');
        wp_safe_redirect($back);exit;
    }
    public static function shortcode(): string {
        if(!self::allowed())return '';
        $current=self::selected();
        $symbols=['USD'=>'$','EUR'=>'€','GBP'=>'£','AUD'=>'A$','CAD'=>'C$'];
        $flags=['USD'=>'🇺🇸','EUR'=>'🇪🇺','GBP'=>'🇬🇧','AUD'=>'🇦🇺','CAD'=>'🇨🇦'];
        $html='<form class="ac-currency-runtime-dev" method="post" action="'.esc_url(home_url('/')).'" style="display:inline-flex;margin:0;padding:0;align-items:center">';
        $html.='<input type="hidden" name="ac_currency_action" value="select">';
        $html.='<input type="hidden" name="ac_currency_nonce" value="'.esc_attr(wp_create_nonce('ac_currency_dev_select')).'">';
        $html.='<select name="ac_currency_code" aria-label="Currency" onchange="this.form.submit()" style="max-width:100%;height:26px;min-height:26px;width:auto;padding:0 2px;font-size:11px">';
        foreach(self::settings()['allowed'] as $code) {
            $html.='<option value="'.esc_attr($code).'"'.selected($code,$current,false).'>'
                .esc_html($flags[$code].' '.$symbols[$code].' '.$code).'</option>';
        }
        return $html.'</select></form>';
    }
    public static function admin_boot(): void {
        if(!self::site())return;
        add_action('admin_post_ac_currency_runtime_dev_save',[__CLASS__,'admin_save']);
    }
    public static function admin_save(): void {
        if(!self::site()||!current_user_can('manage_woocommerce'))
            wp_die('仅允许 Dev 授权管理员','',['response'=>403]);
        check_admin_referer('ac_currency_runtime_dev_save');
        $action=isset($_POST['ac_runtime_action'])?sanitize_key(wp_unslash($_POST['ac_runtime_action'])):'';
        if($action==='refresh') {
            $snapshot=Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly(time());
            update_option(self::FX_OPTION,$snapshot,false);
        } else if($action==='enable') {
            // Cannot enable with stale/missing provider reference rates or YayCurrency.
            if(self::rates()===null||self::legacy_conflict())
                wp_die('必须先刷新有效汇率，并停用其他多币种引擎','',['response'=>409]);
            update_option(self::OPTION,['enabled'=>true,'allowed'=>self::CODES],false);
        } else if($action==='disable') {
            update_option(self::OPTION,['enabled'=>false,'allowed'=>self::CODES],false);
        } else wp_die('无效的操作','',['response'=>400]);
        wp_safe_redirect(admin_url('admin.php?page=andy-commerce&tab=currency-os&section=manage'));exit;
    }
}
