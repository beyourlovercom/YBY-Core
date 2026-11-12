<?php
/** One-time real wp_mail transport smoke, approved R2 pilot ONLY.
 *  Mailbox delivery MUST be independently confirmed; wp_mail(true) is not proof.
 */
if (!defined('ABSPATH') || !class_exists('YBY_Config')) WP_CLI::error('Andy Core missing');
if('moccasin-badger-830954.hostingersite.com'!==wp_parse_url(home_url('/'),PHP_URL_HOST)) WP_CLI::error('Wrong R2 site');
if(get_option('andy_r2_contact_mail_smoke_v1',false)) WP_CLI::error('Already attempted; refuse duplicate');
$to=YBY_Config::get_lead_notification_primary_recipient_email();
if(!is_email($to)) WP_CLI::error('No valid destination');
if(strtolower(substr(strrchr($to,'@'),1))!=='qq.com') WP_CLI::error('Destination domain changed; refusing');
$marker='R2-CF-20261112-MOC-429';
update_option('andy_r2_contact_mail_smoke_v1',array('marker'=>$marker,'attempted_at'=>current_time('mysql'),'result'=>'attempting'),false);
$ok=wp_mail($to,'[R2 Test Only] Contact First verification '.$marker,
	'This is one controlled R2 Contact First test from the Hostinger pilot. No customer inquiry was submitted. Marker: '.$marker);
update_option('andy_r2_contact_mail_smoke_v1',array('marker'=>$marker,'attempted_at'=>current_time('mysql'),'wp_mail_return'=>(bool)$ok),false);
WP_CLI::log('DESTINATION_DOMAIN=qq.com (recipient address omitted)');
WP_CLI::log('MARKER='.$marker);
WP_CLI::log('WORDPRESS_TRANSPORT_RETURN='.($ok?'TRUE':'FALSE'));
WP_CLI::log('MAILBOX_RECEIPT=UNVERIFIED');
if(!$ok) WP_CLI::error('WordPress mail transport rejected test; no WPForms cutover');
WP_CLI::success('WordPress accepted send call. Mailbox delivery remains unverified.');
