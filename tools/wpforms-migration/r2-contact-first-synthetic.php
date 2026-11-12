<?php
/**
 * R2 site-locked test: one synthetic Andy Core lead with WordPress mail fully
 * intercepted. Validates lead storage/outbox without any outbound message.
 *
 * Requires that public REST remains 403 by R2 MU guard. No real email is sent.
 */
if (!defined('ABSPATH') || !class_exists('YBY_Lead_REST_Controller')) {
	WP_CLI::error('Andy Core not loaded');
}
if('moccasin-badger-830954.hostingersite.com'!==wp_parse_url(home_url('/'),PHP_URL_HOST)){
	WP_CLI::error('Wrong site');
}
if(!is_file(WP_CONTENT_DIR.'/mu-plugins/andy-core-r2-moccasin-guard.php')){
	WP_CLI::error('Missing public REST block; refuse test');
}
global $wpdb;
$lead_table=YBY_Database::leads_table_name();
$before=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$lead_table);
$mails=0;
$intercept=static function($pre,$args) use (&$mails){ ++$mails; return true; };
add_filter('pre_wp_mail',$intercept,1,2);
$request=new WP_REST_Request('POST','/yby/v1/leads');
foreach(array(
	'name'=>'R2 Synthetic Test (Do Not Reply)',
	'email'=>'r2-contact-first@example.invalid',
	'company'=>'R2 Automated QA',
	'country'=>'Test only',
	'product_interest'=>'WordPress Site Form',
	'quantity'=>'1',
	'project_details'=>'R2 internal synthetic test: verify form storage and notification outbox only. No actual mail is sent.',
	'fields'=>array('site_contact_consent'=>'1'),
	'form_device'=>'desktop',
	'source_preset'=>'site_contact_v1',
	'source_component'=>'inquiry_shortcode',
	'source_page'=>'R2 Contact First QA',
	'form_version'=>'2.0',
) as $key=>$value){$request->set_param($key,$value);}
$controller=new YBY_Lead_REST_Controller();
try{$response=$controller->submit_lead($request);}finally{remove_filter('pre_wp_mail',$intercept,1);}
$after=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$lead_table);
$body=$response->get_data();$status=$response->get_status();
if(200!==$status||empty($body['success'])||$after!==$before+1||$mails!==1){
	WP_CLI::error('Synthetic lead failed. status='.$status.' delta='.($after-$before).' mail_intercepts='.$mails.' error_keys='.implode(',',array_keys($body['errors']??array())));
}
$id=(int)$body['data']['lead_id'];
$outbox=$wpdb->prefix.'yby_lead_notification_outbox';
$receipt=$wpdb->get_row($wpdb->prepare('SELECT status, attempts FROM '.$outbox.' WHERE lead_id=%d',$id),ARRAY_A);
WP_CLI::log('SYNTHETIC_LEAD_ID='.$id);
WP_CLI::log('REST_LOGIC_STATUS='.$status);
WP_CLI::log('LEAD_STORAGE_DELTA='.($after-$before));
WP_CLI::log('WP_MAIL_INTERCEPT_COUNT='.$mails.' (never sent)');
WP_CLI::log('NOTIFICATION_RECEIPT='.json_encode($receipt));
WP_CLI::success('R2_CORE_STORAGE_AND_OUTBOX_PASS; ZERO ACTUAL MAIL');
