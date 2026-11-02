<?php
/** H3-B StoreApps reapply shadow; no runtime registration. */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_R10H3B_StoreApps_Reapply_Shadow {
    public static function filter($allow,$context,string $manual,string $auto,bool $trusted) {
        if ($allow!==true || !$trusted || !is_array($context)
            || !isset($context['coupon_obj']) || !($context['coupon_obj'] instanceof WC_Coupon)
            || !function_exists('WC') || !is_object(WC()->cart)
            || !is_callable([WC()->cart,'has_discount'])
            || !WC()->cart->has_discount($manual)
            || !class_exists('WC_SC_Auto_Apply_Coupon',false))return $allow;
        $coupon=$context['coupon_obj'];
        if ($manual===$auto || $coupon->get_code()!==$auto
            || $coupon->get_free_shipping()
            || !in_array($coupon->get_discount_type(),['percent','fixed_cart','fixed_product'],true)
            || (float)$coupon->get_amount()<=0) return $allow;
        return false;
    }
}
