<?php
/**
 * M-P5: explicit Woo Checkout Classic / Store API bridge for M15/M16 quota.
 *
 * Source-only, never boots automatically. At present restricted to exact
 * disposable local Woo sandbox by P1::isolated + separate P4 switch.
 * #213 owns eventual audited Dev-only activation and shared bootstrap.
 */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;
require_once __DIR__.'/class-andy-commerce-marketing-p4-order-cap.php';
require_once __DIR__.'/class-andy-commerce-marketing-p4-gift-cap.php';

final class Andy_Commerce_Marketing_P5_Checkout_Hooks {
    private const SESSION_TOKEN='andy_commerce_marketing_p5_checkout_token';
    private static ?array $requestClaim=null;
    private static bool $registered=false;

    private static function enabled(): bool {
        return Andy_Commerce_Marketing_P1_Sandbox_Contract::isolated()
            && get_option('andy_commerce_marketing_p4_order_cap_enabled','no')==='yes';
    }

    public static function boot(): void {
        if(self::$registered||!class_exists('WooCommerce')
            ||!Andy_Commerce_Marketing_P1_Sandbox_Contract::disposable_origin())return;
        self::$registered=true;
        add_action('woocommerce_checkout_create_order',[self::class,'classic_before'],90,2);
        add_action('woocommerce_checkout_order_created',[self::class,'classic_after'],90,1);
        add_action('woocommerce_checkout_order_exception',[self::class,'classic_abort'],90,1);
        add_action('woocommerce_store_api_checkout_update_order_from_request',
            [self::class,'blocks_before'],90,2);
        add_action('woocommerce_store_api_checkout_order_processed',
            [self::class,'blocks_after'],90,1);
    }

    private static function token(array $draft): string {
        $session=WC()->session;
        if(!$session)throw new RuntimeException('M_P5_NO_WOO_SESSION');
        $stored=$session->get(self::SESSION_TOKEN);
        if(is_array($stored)
            &&($stored['campaign']??null)===$draft['id']
            &&($stored['revision']??null)===$draft['revision']
            &&is_string($stored['token']??null)
            &&preg_match('/^p5_[a-f0-9]{32}$/D',$stored['token']))
            return $stored['token'];
        $token='p5_'.str_replace('-','',wp_generate_uuid4());
        $session->set(self::SESSION_TOKEN,[
            'campaign'=>$draft['id'],'revision'=>$draft['revision'],'token'=>$token
        ]);
        return $token;
    }

    private static function reserve(WC_Order $order): void {
        if(!self::enabled())return;
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        if(!$draft)return;
        if($order->get_payment_method()!=='cod'
            ||$order->get_transaction_id()!==''
            ||!WC()->cart instanceof WC_Cart)
            throw new RuntimeException('M_P5_OFFLINE_COD_ONLY');
        if(self::$requestClaim!==null)return; // at most one reserve per request
        global $wpdb;
        $ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
        $claim=$draft['type']==='free_gift'
            ? Andy_Commerce_Marketing_P4_Gift_Cap::reserve(
                $ledger,self::token($draft),WC()->cart)
            : Andy_Commerce_Marketing_P4_Order_Cap::reserve(
                $ledger,self::token($draft),WC()->cart);
        if(($claim['accepted']??false)!==true
            ||($claim['status']??'')!=='reserved')
            throw new RuntimeException('M_P5_CAMPAIGN_CAP_EXHAUSTED_OR_REUSED');
        if($draft['type']==='free_gift')
            Andy_Commerce_Marketing_P4_Gift_Cap::stamp($order,$claim);
        self::$requestClaim=$claim;
    }

    private static function commit(WC_Order $order): void {
        if(!self::enabled()||self::$requestClaim===null)return;
        if($order->get_id()<1||$order->get_payment_method()!=='cod')
            throw new RuntimeException('M_P5_ORDER_NOT_DURABLE');
        global $wpdb;
        $ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        if(($draft['type']??null)==='free_gift') {
            Andy_Commerce_Marketing_P4_Gift_Cap::bind_saved_order(
                self::$requestClaim,$order->get_id());
            $result=Andy_Commerce_Marketing_P4_Gift_Cap::confirm(
                $ledger,self::$requestClaim,$order->get_id());
        } else {
            Andy_Commerce_Marketing_P4_Order_Cap::bind_saved_order(
                self::$requestClaim,$order->get_id());
            $result=Andy_Commerce_Marketing_P4_Order_Cap::confirm(
                $ledger,self::$requestClaim,$order->get_id());
        }
        if(($result['committed']??false)!==true)
            throw new RuntimeException('M_P5_CAP_CONFIRM_NOT_COMMITTED');
        self::$requestClaim=null;
        if(WC()->session) WC()->session->set(self::SESSION_TOKEN,null);
    }

    public static function classic_before($order,$data): void {
        if(!($order instanceof WC_Order))return;
        try { self::reserve($order); }
        catch(RuntimeException $e) {
            if($e->getMessage()==='M_P5_CAMPAIGN_CAP_EXHAUSTED_OR_REUSED')
                throw new RuntimeException(
                    'This promotion has reached its usage limit. Please refresh your cart.'
                );
            throw $e;
        }
    }
    public static function classic_after($order): void {
        if($order instanceof WC_Order)self::commit($order);
    }
    public static function blocks_before($order,$request): void {
        if(!($order instanceof WC_Order))return;
        try {
            self::reserve($order);
        }catch(RuntimeException $e){
            if($e->getMessage()==='M_P5_CAMPAIGN_CAP_EXHAUSTED_OR_REUSED'
                &&class_exists('\\Automattic\\WooCommerce\\StoreApi\\Exceptions\\RouteException')){
                throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                    'aicc_marketing_cap_exhausted',
                    'This promotion has reached its usage limit. Please refresh your cart.',
                    409
                );
            }
            throw $e;
        }
    }
    public static function blocks_after($order): void {
        if($order instanceof WC_Order)self::commit($order);
    }
    public static function classic_abort($order): void {
        if(!self::enabled()||self::$requestClaim===null)return;
        // Release only if no durable order exists, via M15's own locked ledger.
        // If already saved but unconfirmed, keep reservation held for recovery.
        if($order instanceof WC_Order && $order->get_id()>0)return;
        global $wpdb;
        $ledger=new Andy_Commerce_M15_Cap_Ledger($wpdb);
        $draft=Andy_Commerce_Marketing_P1_Sandbox_Contract::current();
        if(($draft['type']??null)==='free_gift')
            Andy_Commerce_Marketing_P4_Gift_Cap::abort_before_order(
                $ledger,self::$requestClaim);
        else
            Andy_Commerce_Marketing_P4_Order_Cap::abort_before_order(
                $ledger,self::$requestClaim);
        self::$requestClaim=null;
        if(WC()->session) WC()->session->set(self::SESSION_TOKEN,null);
    }
}
