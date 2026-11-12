<?php
/**
 * ONE-SITE R2 Contact First reversible canary cutover.
 * Elementor WPForms widget 429 -> existing Andy Core inline shortcode.
 * WPForms plugin remains ACTIVE for rollback; no other site is affected.
 */
if (!defined('ABSPATH') || !class_exists('YBY_Popup_Field_Studio')) WP_CLI::error('Andy Core missing');
if('moccasin-badger-830954.hostingersite.com'!==wp_parse_url(home_url('/'),PHP_URL_HOST)) WP_CLI::error('Wrong site');
$path=WP_CONTENT_DIR.'/mu-plugins/andy-core-r2-moccasin-guard.php';
if(!is_file($path) || false===strpos(file_get_contents($path),'andy_cf_nonce_missing')) WP_CLI::error('Live anti-spam guard missing');
$backup='/home/u595431186/.andy-r2-backups/moccasin-20261112';
if(!is_file($backup.'/contact-213-content.before.txt') || !is_file($backup.'/contact-213-elementor.before.json')
	|| !is_file($backup.'/pre-r2.sql.gz')) WP_CLI::error('Missing rollback backups');
if(1===(int)get_option('andy_r2_contact_first_live',0)) WP_CLI::error('Already live; refuse repeat');
if(!YBY_Popup_Field_Studio::has_preset('site_contact_v1')) WP_CLI::error('Site contact preset absent');
$mail=get_option('andy_r2_contact_mail_smoke_v1',array());
if(empty($mail['wp_mail_return'])) WP_CLI::error('Outbound WordPress mail smoke not accepted');
$post=get_post(213);
if(!$post || 'contact'!==$post->post_name || 'builder'!==get_post_meta(213,'_elementor_edit_mode',true)) WP_CLI::error('Unexpected Contact page');
$original='[wpforms id="429" title="false" description="false"]';
$form='[yby_inquiry preset="site_contact_v1" id="andy-contact-first" title="Contact Us" submit_label="Send inquiry" '
	.'desktop_fields="name,email,country,product_interest,quantity,company,message,site_contact_consent" '
	.'mobile_fields="name,email,country,product_interest,quantity,company,message,site_contact_consent"]';
$original_content=$post->post_content;
$original_data=get_post_meta(213,'_elementor_data',true);
if(!is_string($original_data) || 1!==substr_count($original_content,$original)) WP_CLI::error('Source content changed');
$elements=json_decode($original_data,true);
if(!is_array($elements)) WP_CLI::error('Elementor JSON malformed');
$found=0;
$patch=function (&$nodes) use (&$patch,&$found,$form) {
	foreach($nodes as &$node){
		if(!is_array($node))continue;
		if(($node['widgetType']??'')==='wpforms' && (string)($node['settings']['form_id']??'')==='429'){
			$node['widgetType']='shortcode';
			$node['settings']=array('shortcode'=>$form);
			$found++;
		}
		if(isset($node['elements'])&&is_array($node['elements']))$patch($node['elements']);
	}
};
$patch($elements);
if($found!==1) WP_CLI::error('Expected exactly one Elementor WPForms widget; found='.$found);
$new_content=str_replace($original,$form,$original_content);
$new_json=wp_json_encode($elements,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
if(!is_string($new_json) || strpos($new_json,'site_contact_v1')===false) WP_CLI::error('New widget JSON invalid');
update_option('andy_r2_contact_first_live',1,false);
$updated=wp_update_post(array('ID'=>213,'post_content'=>$new_content),true);
if(is_wp_error($updated) || !$updated){
	update_option('andy_r2_contact_first_live',0,false);
	WP_CLI::error('Post update failed; live flag reverted');
}
$meta_update=update_post_meta(213,'_elementor_data',wp_slash($new_json));
$readback=get_post_meta(213,'_elementor_data',true);
if(!$meta_update || 1!==substr_count($readback,'site_contact_v1') || substr_count($readback,'wpforms')>0){
	wp_update_post(array('ID'=>213,'post_content'=>$original_content));
	update_post_meta(213,'_elementor_data',wp_slash($original_data));
	update_option('andy_r2_contact_first_live',0,false);
	WP_CLI::error('Elementor update failed; restored Contact and live flag');
}
clean_post_cache(213);
do_action('litespeed_purge_post',213);
do_action('litespeed_purge_url',get_permalink(213));
WP_CLI::log('CONTACT_PAGE_ID=213; URL='.get_permalink(213));
WP_CLI::log('ELEMENTOR_REPLACED=1; SOURCE_WPFORMS=429');
WP_CLI::log('LIVE_FLAG=1; WPFORMS_PLUGIN_RETAINED');
WP_CLI::success('R2_CANARY_CONTACT_SWITCHED: web/browser/REST readback still required. Inbox unverified.');
