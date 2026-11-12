<?php
/**
 * Read-only Contact Page First server-side validation UAT on approved pilot.
 * No database insert, REST dispatch, wp_mail, or plugin modification.
 */
if ( ! defined('ABSPATH') || ! class_exists('YBY_Popup_Field_Studio') ) {
	WP_CLI::error('Andy Core missing');
}
if ('moccasin-badger-830954.hostingersite.com' !== wp_parse_url(home_url('/'),PHP_URL_HOST)) {
	WP_CLI::error('Wrong site');
}
$tests=array(
	'desktop_empty'=>array('desktop','','','','',array(),array('name','email','country','message','site_contact_consent')),
	'mobile_empty'=>array('mobile','','','','',array(),array('name','email','country','message','site_contact_consent')),
	'invalid_email'=>array('desktop','Example','bad','Japan','Need price',array('site_contact_consent'=>'1'),array('email')),
	'no_consent'=>array('desktop','Example','r2@example.invalid','Japan','Need price',array(),array('site_contact_consent')),
	'no_requirements'=>array('mobile','Example','r2@example.invalid','Japan','',array('site_contact_consent'=>'1'),array('message')),
	'valid_desktop'=>array('desktop','Example','r2@example.invalid','Japan','Need price',array('site_contact_consent'=>'1'),array()),
	'valid_mobile'=>array('mobile','Example','r2@example.invalid','Japan','Need price',array('site_contact_consent'=>'1'),array()),
);
foreach($tests as $label=>$v){
	$errors=YBY_Popup_Field_Studio::required_errors(array('source_preset'=>'site_contact_v1','form_device'=>$v[0],'name'=>$v[1],'email'=>$v[2],'country'=>$v[3],'project_details'=>$v[4],'custom_fields'=>$v[5]));
	$actual=array_keys($errors);sort($actual);$expect=$v[6];sort($expect);
	if($actual!==$expect){WP_CLI::error('Mismatch '.$label.' expected '.implode(',',$expect).' actual '.implode(',',$actual));}
	WP_CLI::log('PASS '.$label.' (' . count($actual) .' expected errors)');
}
WP_CLI::success('CONTACT_FIRST_REQUIRED_7_OF_7_PASS; no writes, no mail.');
