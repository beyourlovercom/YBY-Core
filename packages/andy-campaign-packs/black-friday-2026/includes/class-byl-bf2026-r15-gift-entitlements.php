<?php
/**
 * BF2026 R15: order-level surprise gift entitlements; warehouse chooses the SKU.
 * Paid US gift choices are actual Woo products, from the owner's 15-item list.
 * Source-only until the administrator-session Canary is explicitly enabled.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R15_Entitlements {
    private const PACK = 'byl-black-friday-2026';
    private static bool $booted = false;
    private static bool $mutating = false;
    /** Fixed owner-supplied gift upgrade choices, verified on WWW 2026-11-10. */
    private const TIERS = [
        9141 => ['TW158',1000], 32685 => ['DW01A',1000],
        9106 => ['VA560A',1000], 9103 => ['VA562A',1000],
        9123 => ['VT500A-G',1000],
        9095 => ['VX007H',1500], 9096 => ['BX033B',1500],
        9117 => ['BX288A',1500], 17625 => ['CA594B',1500],
        25358 => ['VX588A',1500],
        14188 => ['VX603A',2000], 9109 => ['S106B',2000],
        17767 => ['BA564A',2000], 34737 => ['VX607A',2000],
        9137 => ['TX218Y',2000]
    ];

    public static function ready(): bool {
        return class_exists('WooCommerce')
            && class_exists('BYL_BF2026_R9C_Woo_Adapter')
            && class_exists('BYL_BF2026_R13_Cart_Writer')
            && class_exists('BYL_BF2026_R14_Canary')
            && BYL_BF2026_R14_Canary::runtime_allowed()
            && BYL_BF2026_R13_Cart_Writer::campaign_open();
    }

    public static function boot(): void {
        if (self::$booted || !self::ready()) {return;}
        self::$booted=true;
        add_action('woocommerce_before_calculate_totals',[__CLASS__,'reprice'],90,1);
        add_action('woocommerce_after_calculate_totals',[__CLASS__,'reconcile'],95,1);
        add_action('woocommerce_after_shop_loop_item',[__CLASS__,'render_choice'],15);
        add_filter('woocommerce_coupon_is_valid_for_product',[__CLASS__,'coupon_product'],100,4);
        add_filter('woocommerce_coupon_get_items_to_apply',[__CLASS__,'coupon_items'],100,3);
        add_action('woocommerce_cart_totals_after_order_total',[__CLASS__,'render_random_row']);
        add_action('woocommerce_review_order_after_order_total',[__CLASS__,'render_random_row']);
        add_action('woocommerce_checkout_create_order',[__CLASS__,'order_meta'],95,2);
        add_action('woocommerce_store_api_checkout_update_order_from_request',[__CLASS__,'store_order_meta'],95,2);
        add_action('woocommerce_checkout_create_order_line_item',[__CLASS__,'order_item_meta'],95,4);
        add_action('woocommerce_after_checkout_validation',[__CLASS__,'validate_classic'],95,2);
        add_action('woocommerce_checkout_validate_order_before_payment',[__CLASS__,'validate_store_api'],95,2);
        add_action('woocommerce_admin_order_data_after_order_details',[__CLASS__,'admin_order_gifts']);
    }

    public static function gift_row(array $row): bool {
        return ($row['andy_campaign_pack_id']??null)===self::PACK
            && ($row['_byl_bf2026_gift']??null)===true
            && ($row['_byl_bf2026_paid_slot']??null)===true;
    }

    public static function upgrade(int $id): ?array {
        $t=self::TIERS[$id]??null;
        if (!$t || !function_exists('wc_get_product') || !function_exists('has_term')) {return null;}
        $p=wc_get_product($id);
        if (!$p || !$p->is_type('simple') || !$p->is_purchasable()
            || !$p->is_in_stock() || $p->backorders_allowed()
            || (string)$p->get_sku()!==$t[0]) {return null;}
        $tier=(int)($t[1]/100);
        if (!has_term('gift','product_cat',$id)
            || !has_term('gift-upgrade','product_cat',$id)
            || !has_term('gift-upgrade-'.$tier,'product_cat',$id)) {return null;}
        return ['id'=>$id,'sku'=>$t[0],'fee_minor'=>$t[1]];
    }

    private static function cart() {
        return function_exists('WC') && WC() ? WC()->cart : null;
    }
    private static function country(): string {
        if (!function_exists('WC') || !WC() || !WC()->customer) {return '';}
        $customer=WC()->customer;
        $ship=method_exists($customer,'get_shipping_country')
            ? (string)$customer->get_shipping_country() : '';
        if ($ship==='') {
            $ship=method_exists($customer,'get_billing_country')
                ? (string)$customer->get_billing_country() : '';
        }
        return strtoupper(trim($ship));
    }
    private static function quoted($cart,string $country): array {
        if (!$cart || strlen($country)!==2 || !class_exists('BYL_BF2026_R9C_Woo_Adapter')) {
            return ['ok'=>false,'reason'=>'shipping_country_required'];
        }
        return BYL_BF2026_R9C_Woo_Adapter::quote($cart,$country);
    }
    private static function paid_lines($cart): array {
        $found=[];
        if (!$cart || !method_exists($cart,'get_cart')) {return $found;}
        foreach($cart->get_cart() as $key=>$row) {
            if (is_array($row) && self::gift_row($row)) {$found[$key]=$row;}
        }
        return $found;
    }
    public static function entitlement($cart,string $country): array {
        $q=self::quoted($cart,strtoupper($country));
        if (empty($q['ok'])) {return $q;}
        $slots=(int)$q['entitlement']['gift_slots'];
        $paid=self::paid_lines($cart);
        $valid=0;$selectedId=0;
        foreach($paid as $row) {
            $id=(int)($row['product_id']??0);
            if ($country==='US' && !empty($q['entitlement']['premium_allowed'])
                && $row['quantity']===1 && self::upgrade($id)) {
                ++$valid;$selectedId=$id;
            }
        }
        if ($valid>1) {
            return ['ok'=>false,'reason'=>'multiple_paid_gifts_not_allowed'];
        }
        return [
            'ok'=>true,'basis_minor'=>$q['basis_minor'],
            'slots'=>$slots,'paid_count'=>$valid,
            'paid_product_id'=>$selectedId,
            'random_gift_qty'=>max(0,$slots-$valid),
            'premium_allowed'=>!empty($q['entitlement']['premium_allowed'])
                && $country==='US',
        ];
    }
    private static function converted(int $usdMinor): ?float {
        if (!function_exists('get_woocommerce_currency')) {return null;}
        $usd=$usdMinor/100;
        if (get_woocommerce_currency()==='USD') {return $usd;}
        if (!class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
            || !Andy_Commerce_Currency_Runtime_Dev_V1::allowed()
            || Andy_Commerce_Currency_Runtime_Dev_V1::selected()!==get_woocommerce_currency()) {
            return null;
        }
        try {
            return (float)Andy_Commerce_Currency_Runtime_Dev_V1::convert(number_format($usd,2,'.',''));
        } catch(Throwable $e) {return null;}
    }

    /** Product price changes apply ONLY to signed paid gift cart lines. */
    public static function reprice($cart): void {
        if (!self::ready() || !$cart) {return;}
        foreach(self::paid_lines($cart) as $row) {
            $id=(int)($row['product_id']??0);
            $approved=self::upgrade($id);
            if (!$approved || !isset($row['data']) || !is_object($row['data'])
                || !method_exists($row['data'],'set_price')) {continue;}
            $price=self::converted($approved['fee_minor']);
            if ($price!==null) {$row['data']->set_price($price);}
        }
    }

    /** Revoke paid choices when country, coupon threshold or stock changes. */
    public static function reconcile($cart): void {
        if (self::$mutating || !self::ready() || !$cart) {return;}
        $country=self::country();
        $q=self::quoted($cart,$country);
        $allowed=!empty($q['ok']) && $country==='US'
            && !empty($q['entitlement']['premium_allowed']);
        $seen=false;$changed=false;
        self::$mutating=true;
        try {
            foreach(self::paid_lines($cart) as $key=>$line) {
                $id=(int)($line['product_id']??0);
                if (!$allowed || $seen || ($line['quantity']??null)!==1
                    || !self::upgrade($id)) {
                    $cart->remove_cart_item($key);
                    $changed=true;
                } else {$seen=true;}
            }
            if ($changed) {$cart->calculate_totals();}
        } finally {self::$mutating=false;}
    }

    /** Only one selected paid gift; free surprise quantities are order metadata. */
    public static function choose_paid($cart,string $country,int $id): array {
        if (!self::ready() || !$cart || strtoupper($country)!=='US') {
            return ['ok'=>false,'reason'=>'us_only_canary_not_ready'];
        }
        $approved=self::upgrade($id);
        if (!$approved) {return ['ok'=>false,'reason'=>'sku_or_stock_unavailable'];}
        $q=self::quoted($cart,'US');
        if (empty($q['ok']) || empty($q['entitlement']['premium_allowed'])) {
            return ['ok'=>false,'reason'=>'usd99_threshold_not_met'];
        }
        if (self::converted($approved['fee_minor'])===null) {
            return ['ok'=>false,'reason'=>'foreign_currency_not_audited'];
        }
        $existing=self::paid_lines($cart);
        if (count($existing)===1 && (int)reset($existing)['product_id']===$id) {
            return ['ok'=>true,'changed'=>false,'id'=>$id];
        }
        if (self::$mutating) {return ['ok'=>false,'reason'=>'busy'];}
        self::$mutating=true;
        $removed=[];$added=null;
        try {
            foreach($existing as $key=>$line) {
                if (!$cart->remove_cart_item($key)) {
                    throw new RuntimeException('old_choice_remove_failed');
                }
                $removed[]=$line;
            }
            $meta=[
                'andy_campaign_pack_id'=>self::PACK,
                '_byl_bf2026_gift'=>true,
                '_byl_bf2026_paid_slot'=>true,
                '_byl_bf2026_source_usd_minor'=>$approved['fee_minor'],
                '_byl_bf2026_paid_sku'=>$approved['sku'],
            ];
            $added=$cart->add_to_cart($id,1,0,[],$meta);
            if (!is_string($added) || $added==='') {
                throw new RuntimeException('wc_add_paid_failed');
            }
            $cart->calculate_totals();
            $after=$cart->get_cart();
            $line=$after[$added]??null;
            $price=self::converted($approved['fee_minor']);
            if (!is_array($line) || !self::gift_row($line)
                || (int)$line['product_id']!==$id
                || abs((float)($line['line_total']??-1)-$price)>0.02) {
                throw new RuntimeException('paid_line_readback_failed');
            }
            return ['ok'=>true,'changed'=>true,'id'=>$id,'fee_minor'=>$approved['fee_minor']];
        } catch(Throwable $e) {
            if (is_string($added) && $added!=='') {$cart->remove_cart_item($added);}
            foreach($removed as $line) {
                $oldid=(int)($line['product_id']??0);
                $old=self::upgrade($oldid);
                if (!$old) {continue;}
                $key=$cart->add_to_cart($oldid,1,0,[],[
                    'andy_campaign_pack_id'=>self::PACK,
                    '_byl_bf2026_gift'=>true,
                    '_byl_bf2026_paid_slot'=>true,
                    '_byl_bf2026_source_usd_minor'=>$old['fee_minor'],
                    '_byl_bf2026_paid_sku'=>$old['sku'],
                ]);
                if (!$key) {return ['ok'=>false,'reason'=>'rollback_incomplete'];}
            }
            $cart->calculate_totals();
            return ['ok'=>false,'reason'=>'native_paid_choice_failed'];
        } finally {self::$mutating=false;}
    }

    public static function coupon_product($valid,$product,$coupon,$cartItem) {
        if (is_array($cartItem) && self::gift_row($cartItem)) {return false;}
        return class_exists('BYL_BF2026_R13_Cart_Writer')
            ? BYL_BF2026_R13_Cart_Writer::coupon_line($valid,$product,$coupon,$cartItem)
            : $valid;
    }
    public static function coupon_items($items,$coupon,$discounts) {
        if (!is_array($items)) {return $items;}
        $items=array_values(array_filter($items,static function($item) {
            $row=is_object($item)?($item->object??null):null;
            return !is_array($row) || !self::gift_row($row);
        }));
        return class_exists('BYL_BF2026_R13_Cart_Writer')
            ? BYL_BF2026_R13_Cart_Writer::coupon_items($items,$coupon,$discounts)
            : $items;
    }

    public static function render_choice(): void {
        if (!self::ready() || !function_exists('get_page_by_path')
            || !function_exists('get_queried_object_id')
            || !function_exists('wp_nonce_field')) {return;}
        $page=get_page_by_path('holiday/gift');
        if (!$page || get_queried_object_id()!==(int)$page->ID) {return;}
        global $product;
        if (!$product || !is_object($product) || !method_exists($product,'get_id')) {return;}
        $id=(int)$product->get_id();
        $approved=self::upgrade($id);
        if (!$approved || self::country()!=='US') {return;}
        $q=self::quoted(self::cart(),'US');
        if (empty($q['ok']) || empty($q['entitlement']['premium_allowed'])) {return;}
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="byl-bf-gift-choice">';
        wp_nonce_field('byl_bf2026_choose_gift');
        echo '<input type="hidden" name="action" value="byl_bf2026_choose_gift">';
        echo '<input type="hidden" name="gift_product_id" value="'.esc_attr((string)$id).'">';
        echo '<button type="submit" class="byl-bf-gift-select">Choose for +$'.
            esc_html(number_format($approved['fee_minor']/100,0)).'</button></form>';
    }

    public static function handle_post(): void {
        if (!self::ready()) {wp_die('Gift selection is not enabled.','',['response'=>403]);}
        check_admin_referer('byl_bf2026_choose_gift');
        if (!function_exists('wc_load_cart')) {wp_die('Woo cart unavailable.','',['response'=>503]);}
        if (!self::cart()) {wc_load_cart();}
        $id=absint($_POST['gift_product_id']??0);
        $res=self::choose_paid(self::cart(),self::country(),$id);
        if (function_exists('wc_add_notice')) {
            wc_add_notice(!empty($res['ok'])
                ? 'Black Friday gift choice updated.'
                : 'This gift requires a qualifying U.S. cart and current stock.',
                !empty($res['ok'])?'success':'error');
        }
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }

    public static function render_random_row(): void {
        if (!self::ready()) {return;}
        $q=self::entitlement(self::cart(),self::country());
        $qty=(int)($q['random_gift_qty']??0);
        if ($qty<=0) {return;}
        echo '<tr class="byl-bf-free-gift-row"><th>Random surprise gift'.
            ($qty===1?'':'s').'</th><td data-title="Surprise Gifts">'.
            esc_html($qty.' × FREE').'</td></tr>';
    }

    public static function order_meta($order,$data=null): void {
        if (!self::ready() || !is_object($order)
            || !method_exists($order,'update_meta_data')) {return;}
        $country=method_exists($order,'get_shipping_country')
            ? strtoupper((string)$order->get_shipping_country()) : self::country();
        if ($country==='') {$country=self::country();}
        $q=self::entitlement(self::cart(),$country);
        if (empty($q['ok'])) {return;}
        $order->update_meta_data('_byl_bf2026_random_gift_qty',(int)$q['random_gift_qty']);
        $order->update_meta_data('_byl_bf2026_total_gift_slots',(int)$q['slots']);
        $order->update_meta_data('_byl_bf2026_paid_gift_product_id',(int)$q['paid_product_id']);
    }
    public static function store_order_meta($order,$request): void {
        self::order_meta($order,$request);
    }
    public static function order_item_meta($item,$key,$line,$order): void {
        if (!is_array($line) || !self::gift_row($line)
            || !is_object($item) || !method_exists($item,'add_meta_data')) {return;}
        $item->add_meta_data('_byl_bf2026_paid_gift',1,true);
        $item->add_meta_data('_byl_bf2026_paid_sku',(string)($line['_byl_bf2026_paid_sku']??''),true);
        $item->add_meta_data('_byl_bf2026_upgrade_usd_minor',(int)($line['_byl_bf2026_source_usd_minor']??0),true);
    }
    public static function admin_order_gifts($order): void {
        if (!is_object($order) || !method_exists($order,'get_meta')) {return;}
        $qty=(int)$order->get_meta('_byl_bf2026_random_gift_qty',true);
        if ($qty>0) {
            echo '<p><strong>Black Friday 随机礼品：</strong>仓库配货 '.
                esc_html((string)$qty).' 件（SKU 由 ERP/仓库决定）</p>';
        }
    }

    private static function checkout_error($cart,string $country): string {
        $paid=self::paid_lines($cart);
        if (!$paid) {return '';}
        $q=self::entitlement($cart,strtoupper($country));
        return !empty($q['ok']) && $country==='US'
            && !empty($q['premium_allowed']) && count($paid)===1
            && (int)$q['paid_count']===1
            ? '' : 'Please update your cart to confirm the Black Friday selected gift for your shipping address.';
    }
    public static function validate_classic($posted,$errors): void {
        if (!self::ready() || !is_object($errors) || !method_exists($errors,'add')) {return;}
        $country=is_array($posted)?strtoupper((string)($posted['shipping_country']??'')):'';
        $message=self::checkout_error(self::cart(),$country);
        if ($message!=='') {$errors->add('bf2026_selected_gift',$message);}
    }
    public static function validate_store_api($order,$errors): void {
        if (!self::ready() || !is_object($errors) || !method_exists($errors,'add')) {return;}
        $country=is_object($order) && method_exists($order,'get_shipping_country')
            ? strtoupper((string)$order->get_shipping_country()):'';
        $message=self::checkout_error(self::cart(),$country);
        if ($message!=='') {$errors->add('bf2026_selected_gift',$message);}
    }
}
