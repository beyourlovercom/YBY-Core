<?php
/** P9-7 inert WP Mail SMTP Pro coexistence review; never bootstrapped in WP. */
if(!defined('ABSPATH'))exit;
final class YBY_Mail_V2_P9_Coexistence {
    const VERSION='mail_p9_smtp_pro_coexistence_v1';
    const PATHS=array('woo_order','payment_security','password_reset',
      'inquiry_lead','account','newsletter_confirmation','newsletter_bulk');
    const INCUMBENT='incumbent_unchanged';
    /**
     * Propose a routing matrix only. NEVER a command to switch/disable senders.
     * A request for actual cutover fails closed, even if every evidence flag true.
     */
    public static function review($context) {
        $fail=static function($code){return array('status'=>'blocked',
          'reason'=>$code,'can_activate'=>false,'can_send'=>false,
          'can_disable_smtp_pro'=>false,'can_register_hooks'=>false);};
        $fields=array('synthetic_only','worker_enabled','queue_enabled',
          'outbound_enabled','real_customer_data','owner_cutover_approved',
          'smtp_pro_present','p8_readonly_preserved','v22_ui_preserved');
        if(!is_array($context)||array_keys($context)!==$fields) {
            return $fail('coexistence_input');
        }
        foreach($fields as $field)if(!is_bool($context[$field])){
            return $fail('coexistence_input');
        }
        if($context['synthetic_only']!==true||
           $context['worker_enabled']!==false||
           $context['queue_enabled']!==false||
           $context['outbound_enabled']!==false||
           $context['real_customer_data']!==false||
           $context['owner_cutover_approved']!==false||
           $context['smtp_pro_present']!==true||
           $context['p8_readonly_preserved']!==true||
           $context['v22_ui_preserved']!==true){
            return $fail('live_change_requires_new_gate');
        }
        $routes=array_fill_keys(self::PATHS,self::INCUMBENT);
        return array('status'=>'review_only','version'=>self::VERSION,
           'routes'=>$routes,'can_activate'=>false,'can_send'=>false,
           'can_disable_smtp_pro'=>false,'can_register_hooks'=>false,
           'requires_separate_future_owner_cutover_approval'=>true,
           'provider_quota_verified'=>false,'delivery_proven'=>false,
           'contains_pii'=>false,'worker_enabled'=>false);
    }
}
