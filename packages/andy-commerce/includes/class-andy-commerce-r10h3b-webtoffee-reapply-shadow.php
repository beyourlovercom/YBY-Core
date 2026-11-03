<?php
/** H3-B source-only WebToffee 3.2 auto reapply guard, NOT BOOTSTRAPPED. */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_R10H3B_WebToffee_Reapply_Shadow {
    private static function matches($coupon, string $manual, string $auto, bool $trusted): bool {
        if (!$trusted || !is_object($coupon) || !($coupon instanceof WC_Coupon)
            || !function_exists('WC') || !is_object(WC()->cart)
            || !is_callable([WC()->cart,'has_discount'])
            || !WC()->cart->has_discount($manual)
            || $coupon->get_code() !== $auto || $manual === $auto
            || !class_exists('Wt_Smart_Coupon_Auto_Coupon_Common',false)
            || !is_callable(['Wt_Smart_Coupon_Auto_Coupon_Common','is_auto_coupon'])
            || !Wt_Smart_Coupon_Auto_Coupon_Common::is_auto_coupon($coupon)) return false;
        if ($coupon->get_free_shipping()) return false;
        if (!in_array($coupon->get_discount_type(),['percent','fixed_cart','fixed_product'],true)) return false;
        return (float)$coupon->get_amount()>0;
    }
    /** Does not bypass an existing vendor invalidity veto. */
    public static function is_valid($valid, $coupon, string $manual, string $auto, bool $trusted) {
        if ($valid!==true) return $valid;
        return self::matches($coupon,$manual,$auto,$trusted) ? false : $valid;
    }
    /** Selectively filters already-cached WebToffee auto coupon candidates. */
    public static function candidates($candidates,string $manual,string $auto,bool $trusted) {
        if (!is_array($candidates)) return $candidates;
        foreach($candidates as $key=>$coupon)
            if(self::matches($coupon,$manual,$auto,$trusted)) unset($candidates[$key]);
        return $candidates;
    }
}
