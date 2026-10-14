<?php
/**
 * Site-level storefront Cart subtotal reminder: small, occasional text edit.
 * Andy Commerce owns the option. It never changes Woo shipping or tax.
 */
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_Cart_Notice_Settings {
    public const OPTION_NAME = 'andy_commerce_cart_subtotal_notice_v1';
    public const MAX_LENGTH = 240;
    public const DEFAULT_TEXT = 'Please check shipping costs, import duties and local taxes for your country or region. Final charges depend on your delivery address.';

    public static function defaults(): array {
        return ['enabled'=>true,'text'=>self::DEFAULT_TEXT];
    }

    public static function get(): array {
        $saved=get_option(self::OPTION_NAME, false);
        if (!is_array($saved)) return self::defaults();
        $text=$saved['text']??self::DEFAULT_TEXT;
        return [
            'enabled'=>isset($saved['enabled']) && $saved['enabled']===true,
            'text'=>is_string($text) && $text!=='' ? $text : self::DEFAULT_TEXT,
        ];
    }

    public static function sanitize($input): array {
        if (!is_array($input)) $input=[];
        $raw=$input['text']??'';
        $text=is_string($raw) ? sanitize_text_field($raw) : '';
        $text=trim(wp_html_excerpt($text, self::MAX_LENGTH, ''));
        if ($text==='') $text=self::DEFAULT_TEXT;
        return ['enabled'=>isset($input['enabled']) && (string)$input['enabled']==='1',
            'text'=>$text];
    }

    public static function save($input): void {
        update_option(self::OPTION_NAME,self::sanitize($input),false);
    }

    public static function reset(): void {
        delete_option(self::OPTION_NAME);
    }
}
