<?php
/** R13 controlled Dev checkout canary; OFF for all users by default.
 * Never runs on WWW or an anonymous Dev shopper. No real payments in this class.
 */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
final class Andy_Commerce_Currency_R13_Dev_Canary {
    const OPTION='andy_commerce_currency_r13_dev_canary';
    const ACCOUNT_PROOF='andy_commerce_currency_r13_dev_stripe_test_proof';
    const EXPECTED_ACCOUNT='acct_1R57M7GR9MwFzg43';
    public static function site(): bool {
        return function_exists('home_url')
          && wp_parse_url(home_url('/'),PHP_URL_HOST)==='dev.beyourlover.com'
          && ($_SERVER['HTTP_HOST']??'')==='dev.beyourlover.com'
          && function_exists('is_ssl') && is_ssl()
          && strtoupper((string)get_option('woocommerce_currency',''))==='USD';
    }
    public static function armed(): bool {
        if(!self::site() || is_admin() || !function_exists('current_user_can')
           || !current_user_can('manage_woocommerce') || !get_current_user_id())
           return false;
        $o=get_option(self::OPTION,[]);
        return is_array($o)&&($o['enabled']??null)===true
         &&($o['user_id']??null)===get_current_user_id()
         &&is_int($o['expires_at']??null)
         &&$o['expires_at']>=time() && $o['expires_at']<=time()+900
         // The legacy Dev runtime remains globally enabled for other shoppers.
         // Runtime_Dev_V1::allowed() yields only inside THIS admin canary session.
         &&(!class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
             || !Andy_Commerce_Currency_Runtime_Dev_V1::legacy_conflict());
    }
    public static function quote(string $currency): array {
        if(!self::armed() || !class_exists('Andy_Commerce_Currency_R13_FX_Quote_Guard'))
          throw new InvalidArgumentException('R13 Dev quote cannot run');
        $snapshot=get_option('andy_commerce_currency_runtime_fx_snapshot_v1',[]);
        if(!is_array($snapshot)) throw new InvalidArgumentException('No R13 Dev quote');
        $s=Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($snapshot,time());
        if(!isset($s['rate_micros'][$currency]))
          throw new InvalidArgumentException('Currency not in verified quote');
        return ['currency'=>$currency,'rate_micros'=>$s['rate_micros'][$currency],
          'rate_source'=>$s['rate_source'],'retrieved_at_utc'=>$s['retrieved_at_utc'],
          'quote_date'=>$currency==='USD'?null:$s['quote_dates'][$currency],
          'snapshot_hash'=>$s['snapshot_hash'],'is_settlement_rate'=>false];
    }
    public static function test_gateway_state(): array {
        $p=get_option('woocommerce_stripe_settings',[]);
        if(!is_array($p))$p=[];
        $secret=(string)($p['test_secret_key']??'');
        return ['enabled'=>($p['enabled']??'')==='yes',
          'testmode'=>($p['testmode']??'')==='yes',
          'test_key'=>str_starts_with($secret,'sk_test_'),
          'key_fingerprint'=>$secret!==''?hash_hmac('sha256',$secret,wp_salt('auth')):null];
    }
    public static function payment_ready(): bool {
        if(!self::armed()) return false;
        $g=self::test_gateway_state();$s=get_option(self::ACCOUNT_PROOF,[]);
        return $g['enabled']&&$g['testmode']&&$g['test_key']
          &&is_array($s) && ($s['account']??null)===self::EXPECTED_ACCOUNT
          &&($s['key_fingerprint']??null)===$g['key_fingerprint']
          &&is_int($s['verified_at']??null) && time()-$s['verified_at']>=0
          &&time()-$s['verified_at']<=900;
    }
    public static function boot(): void {
        if(!self::site())return;
        if(is_admin()){
            add_action('admin_menu',[__CLASS__,'admin_menu'],50);
            add_action('admin_post_aicc_r13_dev_action',[__CLASS__,'admin_action']);
            return;
        }
        add_filter('rest_request_before_callbacks',[__CLASS__,'checkout_preflight'],3,3);
        add_filter('woocommerce_available_payment_gateways',[__CLASS__,'gateways'],99999);
        add_action('woocommerce_checkout_process',[__CLASS__,'classic_preflight'],1);
        add_action('wp_loaded',[__CLASS__,'switch_request'],24);
        add_action('wp_footer',[__CLASS__,'render_selector'],98);
    }
    private static function foreign_session(): ?string {
        if(!function_exists('WC') || !WC()->session)return null;
        $saved=WC()->session->get('aicc_currency_runtime_selected');
        return is_string($saved)&&in_array($saved,['EUR','GBP','AUD','CAD'],true)?$saved:null;
    }
    public static function checkout_preflight($response,$handler,$request) {
        if($response!==null||!($request instanceof WP_REST_Request)
          ||$request->get_method()!=='POST'
          ||$request->get_route()!=='/wc/store/v1/checkout')return $response;
        $active=self::armed();
        $selected=$active?Andy_Commerce_Currency_Runtime_C1::test_currency():self::foreign_session();
        if($selected===null||$selected==='USD')return $response;
        if(!$active)
          return new WP_Error('aicc_r13_dev_session_expired',
             'Your currency test session has expired. No payment was attempted.',
             ['status'=>503]);
        if(!self::payment_ready())
          return new WP_Error('aicc_stripe_test_not_ready',
              'This currency is in testing. Stripe TEST is not ready; no payment was attempted.',
              ['status'=>503]);
        if($request->get_param('payment_method')!=='stripe')
          return new WP_Error('aicc_stripe_test_only',
              'Only the verified Stripe TEST gateway is permitted for this test currency.',
              ['status'=>422]);
        return $response;
    }
    public static function classic_preflight(): void {
        $active=self::armed();
        if(($active && Andy_Commerce_Currency_Runtime_C1::test_currency()!=='USD')
           ||(!$active && self::foreign_session()!==null))
            wc_add_notice('This R13 Dev canary supports Blocks Checkout only. No payment was attempted.','error');
    }
    public static function gateways($gateways): array {
        $active=self::armed();
        // Expired authorization must not re-enable WooPayments in an old CAD cart.
        if(!$active) return self::foreign_session()!==null?[]:(array)$gateways;
        if(Andy_Commerce_Currency_Runtime_C1::test_currency()==='USD')
          return (array)$gateways;
        return self::payment_ready() && isset($gateways['stripe'])
          ? ['stripe'=>$gateways['stripe']] : [];
    }
    public static function switch_request(): void {
        if(!self::armed()||($_SERVER['REQUEST_METHOD']??'')!=='POST'
           ||($_POST['aicc_r13_dev_switch']??'')!=='yes')return;
        $nonce=sanitize_text_field(wp_unslash($_POST['aicc_r13_dev_nonce']??''));
        $currency=sanitize_text_field(wp_unslash($_POST['aicc_r13_dev_code']??''));
        if(!wp_verify_nonce($nonce,'aicc_r13_dev_switch')
           ||!in_array($currency,['USD','EUR','GBP','AUD','CAD'],true))
           wp_die('Invalid R13 canary currency switch','',['response'=>403]);
        try { self::quote($currency); }
        catch(Throwable $e) {wp_die('Reference exchange rate unavailable','',['response'=>503]);}
        if(!function_exists('WC')||!WC()->session
           ||!Andy_Commerce_Currency_Runtime_C1::select_for_sandbox($currency))
           wp_die('Canary Woo session unavailable','',['response'=>409]);
        WC()->session->set_customer_session_cookie(true);
        wp_safe_redirect(wp_get_referer()?:home_url('/cart/'));exit;
    }
    public static function render_selector(): void {
        if(!self::armed()||!function_exists('is_cart')||!(is_cart()||is_checkout()))return;
        echo '<form class="aicc-r13-dev-canary" method="post" action="'.esc_url(home_url('/')).'">';
        echo '<input type="hidden" name="aicc_r13_dev_switch" value="yes">';
        echo '<input type="hidden" name="aicc_r13_dev_nonce" value="'.esc_attr(wp_create_nonce('aicc_r13_dev_switch')).'">';
        echo '<label for="aicc-r13-dev-currency">R13 Dev Canary — TEST only</label>';
        echo '<select id="aicc-r13-dev-currency" name="aicc_r13_dev_code">';
        foreach(['USD','EUR','GBP','AUD','CAD'] as $code)
          echo '<option value="'.esc_attr($code).'"'.selected(
             $code,Andy_Commerce_Currency_Runtime_C1::test_currency(),false).'>'.$code.'</option>';
        echo '</select><button type="submit">Switch test currency</button></form>';
    }
    public static function admin_menu(): void {
        add_submenu_page('woocommerce','R13 Dev Canary','R13 Dev Canary',
          'manage_woocommerce','aicc-r13-dev-canary',[__CLASS__,'admin_page']);
    }
    public static function admin_action(): void {
        if(!self::site()||!current_user_can('manage_woocommerce'))
          wp_die('Not authorized for R13 Dev Canary','',['response'=>403]);
        check_admin_referer('aicc_r13_dev_admin');
        $operation=sanitize_key(wp_unslash($_POST['operation']??''));
        if($operation==='disable') {
            update_option(self::OPTION,['enabled'=>false],false);
        } elseif($operation==='refresh_fx') {
            $fx=Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly(time());
            Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($fx,time());
            update_option('andy_commerce_currency_runtime_fx_snapshot_v1',$fx,false);
        } elseif($operation==='verify_stripe_test') {
            $g=self::test_gateway_state();$settings=get_option('woocommerce_stripe_settings',[]);
            if(!$g['testmode']||!$g['test_key']||!is_array($settings))
              wp_die('Stripe Gateway is not in TEST mode','',['response'=>409]);
            $key=(string)$settings['test_secret_key'];
            $resp=wp_safe_remote_get('https://api.stripe.com/v1/account',[
                'timeout'=>12,'redirection'=>0,
                'headers'=>['Authorization'=>'Basic '.base64_encode($key.':')]]);
            $body=is_wp_error($resp)?null:json_decode(wp_remote_retrieve_body($resp),true);
            if(is_wp_error($resp)||wp_remote_retrieve_response_code($resp)!==200
               ||!is_array($body)||($body['id']??null)!==self::EXPECTED_ACCOUNT)
               wp_die('Stripe TEST account identity did not match the approved account','',['response'=>409]);
            update_option(self::ACCOUNT_PROOF,[
                'account'=>self::EXPECTED_ACCOUNT,
                'key_fingerprint'=>$g['key_fingerprint'],
                'verified_at'=>time()],false);
        } elseif($operation==='arm_preview') {
            if(class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
              &&Andy_Commerce_Currency_Runtime_Dev_V1::legacy_conflict())
              wp_die('A separate currency plugin conflicts with the R13 canary','',['response'=>409]);
            $fx=get_option('andy_commerce_currency_runtime_fx_snapshot_v1',[]);
            if(!is_array($fx))wp_die('Reference FX not ready','',['response'=>409]);
            Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($fx,time());
            update_option(self::OPTION,['enabled'=>true,
                'user_id'=>get_current_user_id(),'expires_at'=>time()+600],false);
        } else {
            wp_die('Unknown R13 Dev Canary operation','',['response'=>400]);
        }
        wp_safe_redirect(admin_url('admin.php?page=aicc-r13-dev-canary'));exit;
    }
    public static function admin_page(): void {
        if(!self::site()||!current_user_can('manage_woocommerce'))return;
        $state=get_option(self::OPTION,[]);
        $g=self::test_gateway_state();
        $proof=get_option(self::ACCOUNT_PROOF,[]);
        $fx=get_option('andy_commerce_currency_runtime_fx_snapshot_v1',[]);
        $fxValid=false;
        try {
            if(is_array($fx)) {
                Andy_Commerce_Currency_R13_FX_Quote_Guard::validate($fx,time());
                $fxValid=true;
            }
        } catch(Throwable $e) {}
        $armed=is_array($state)&&($state['enabled']??null)===true
          &&($state['expires_at']??0)>=time();
        $connected=is_array($proof)&&($proof['account']??null)===self::EXPECTED_ACCOUNT
          &&($proof['key_fingerprint']??null)===$g['key_fingerprint']
          &&time()-($proof['verified_at']??0)<=900;
        echo '<div class="wrap"><h1>R13 Currency — Dev Canary</h1>';
        echo '<p>Only a named logged-in Woo administrator can enable this 10-minute preview. Ordinary shoppers and WWW are unaffected.</p>';
        echo '<p>FX reference: <strong>'.($fxValid?'Fresh':'STALE / MISSING').'</strong></p>';
        echo '<p>Stripe Gateway enabled: <strong>'.($g['enabled']?'YES':'NO').'</strong> | TEST mode: '.($g['testmode']?'YES':'NO').'</p>';
        echo '<p>Approved TEST account identity: <strong>'.($connected?'MATCHED':'UNVERIFIED').'</strong></p>';
        echo '<p>Canary session: <strong>'.($armed?'ARMED':'OFF').'</strong></p>';
        foreach(['refresh_fx'=>'Refresh reference FX (read-only provider)',
                 'verify_stripe_test'=>'Verify Stripe TEST account (GET only)',
                 'arm_preview'=>'Arm my 10-minute Dev preview',
                 'disable'=>'Disable R13 Dev Canary'] as $action=>$label){
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin:6px">';
            echo '<input type="hidden" name="action" value="aicc_r13_dev_action">';
            echo '<input type="hidden" name="operation" value="'.esc_attr($action).'">';
            wp_nonce_field('aicc_r13_dev_admin');
            echo '<button class="button" type="submit">'.esc_html($label).'</button></form>';
        }
        echo '<p>Foreign-currency Stripe payment is unavailable unless Gateway TEST is enabled and account A has been reverified within 15 minutes.</p>';
        echo '</div>';
    }
}
