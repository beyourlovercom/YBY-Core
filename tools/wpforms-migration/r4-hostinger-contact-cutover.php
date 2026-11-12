<?php
/**
 * One-shot R4 Contact First site cutover for vetted ordinary WPForms CONTACT
 * forms ONLY. Other WPForms forms (newsletter, booking) are not altered.
 * Requires signed v2.2.11, site-specific MU guard and pre-cutover backup.
 * Never deletes WPForms or overwrites an existing Andy Core site configuration.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
	!class_exists('YBY_Popup_Field_Studio')) { exit(1); }
$targets=array(
	'blanchedalmond-flamingo-601606.hostingersite.com'=>array(1952,'contact',363),
	'darkgoldenrod-camel-347943.hostingersite.com'=>array(695,'contact-us',439),
	'darkturquoise-bee-276354.hostingersite.com'=>array(3410,'contact',156),
	'dodgerblue-mantis-171767.hostingersite.com'=>array(492,'contact',113),
	'funnyslip.com'=>array(1952,'contact',363),
	'lightcoral-weasel-975054.hostingersite.com'=>array(1361,'contact',182),
	'lightsteelblue-caterpillar-245164.hostingersite.com'=>array(1952,'contact',363),
	'mediumpurple-meerkat-978434.hostingersite.com'=>array(393,'contact',217),
	'skyblue-gaur-779563.hostingersite.com'=>array(616,'contact',67),
);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!isset($targets[$host])) WP_CLI::error('Not an approved ordinary Contact migration site');
list($page_id,$page_slug,$source_id)=$targets[$host];
$backup_dir='/home/u595431186/.andy-contact-first-backups/20261112';
$backup=$backup_dir.'/'.$host.'.sql.gz';
if (!is_file($backup) || filesize($backup)<10000) WP_CLI::error('Site DB backup missing');
if (!is_file(WP_CONTENT_DIR.'/mu-plugins/andy-contact-first-guard.php')) WP_CLI::error('Fail-closed MU guard missing');
if (!is_plugin_active('wpforms-lite/wpforms.php') || !is_plugin_active('yby-core/yby-core.php')) {
	WP_CLI::error('WPForms and Andy Core must both be active before cutover');
}
if (get_option('yby_popup_field_studio_v2','__missing__')!=='__missing__'
	|| get_option('andy_cf_site_enabled','__missing__')!=='__missing__'
	|| YBY_Module_Registry::enabled_modules()!==array()) {
	WP_CLI::error('Site already configured. Refusing to overwrite or rerun');
}
$post=get_post($page_id);
if (!$post || $post->post_type!=='page' || $post->post_status!=='publish'
	|| $post->post_name!==$page_slug || get_post_meta($page_id,'_elementor_edit_mode',true)!=='builder') {
	WP_CLI::error('Contact page identity/Elementor mode changed');
}
$source_content=(string)$post->post_content;
$source_meta=get_post_meta($page_id,'_elementor_data',true);
$elements=is_string($source_meta)?json_decode($source_meta,true):null;
if (!is_array($elements)) WP_CLI::error('Elementor JSON is missing or malformed');
require_once __DIR__.'/class-contact-first-template.php';
$draft=Andy_Contact_First_Template::draft();
$draft['presets']['site_contact_v1']['enabled']=true;
$clean=YBY_Popup_Field_Studio::sanitize($draft);
if (empty($clean['presets']['site_contact_v1']['enabled'])
	|| !isset($clean['presets']['site_contact_v1']['configs']['site_contact_consent'])) {
	WP_CLI::error('Site Field Studio V2 schema rejected Contact template');
}
$form=Andy_Contact_First_Template::shortcode();
$count=0;
$patch=function(&$nodes) use(&$patch,&$count,$source_id,$form) {
	foreach($nodes as &$node){
		if (!is_array($node)) continue;
		if (($node['widgetType']??'')==='wpforms'
			&& (string)($node['settings']['form_id']??'')===(string)$source_id) {
			$node['widgetType']='shortcode';
			$node['settings']=array('shortcode'=>$form);
			++$count;
		}
		if (isset($node['elements'])&&is_array($node['elements'])) $patch($node['elements']);
	}
};
$patch($elements);
if ($count!==1) WP_CLI::error('Expected exactly one Contact WPForms widget ID '.$source_id.'; found '.$count);
$regex='/\[wpforms\b(?=[^\]]*\bid\s*=\s*["\']?'.preg_quote((string)$source_id,'/').'(?=["\']|\s|\]))[^\]]*\]/i';
$raw_count=preg_match_all($regex,$source_content);
if ($raw_count!==1) WP_CLI::error('Expected one raw source Contact shortcode: found '.var_export($raw_count,true));
$replacement=preg_replace($regex,$form,$source_content,1);
$serialized=wp_json_encode($elements,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
if (!is_string($serialized) || substr_count($serialized,'site_contact_v1')!==1) {
	WP_CLI::error('New Elementor widget serialization failed');
}
$one=$backup_dir.'/'.$host.'-contact-'.$page_id.'-before.txt';
$two=$backup_dir.'/'.$host.'-contact-'.$page_id.'-elementor-before.json';
if (file_exists($one)||file_exists($two)) WP_CLI::error('Site snapshots already exist (possible prior run)');
if (file_put_contents($one,$source_content)===false || file_put_contents($two,$source_meta)===false) {
	WP_CLI::error('Could not preserve exact pre-cutover page snapshots');
}
chmod($one,0600);chmod($two,0600);

// Save configuration before enabling public runtime; fail closed until final flag.
if (!update_option(YBY_Popup_Field_Studio::OPTION,$clean,false)) WP_CLI::error('Cannot save Field Studio');
update_option('andy_cf_contact_page_id',$page_id,false);
update_option('andy_cf_contact_slug',$page_slug,false);
update_option('andy_cf_site_enabled',0,false);
YBY_Module_Registry::save(array('inquiry_os'));
$markup=do_shortcode($form);
if (''===$markup || false===strpos($markup,'data-yby-inquiry-inline')) {
	YBY_Module_Registry::save(array());
	delete_option(YBY_Popup_Field_Studio::OPTION);
	WP_CLI::error('Inline form renderer failed. Guard remains disabled.');
}
update_option('andy_cf_site_enabled',1,false);
$updated=wp_update_post(array('ID'=>$page_id,'post_content'=>$replacement),true);
if (is_wp_error($updated) || !$updated) {
	update_option('andy_cf_site_enabled',0,false);
	WP_CLI::error('Contact post update failed: keep disabled, old Elementor widget retained');
}
$write=update_post_meta($page_id,'_elementor_data',wp_slash($serialized));
$actual=get_post_meta($page_id,'_elementor_data',true);
if (!$write || substr_count((string)$actual,'site_contact_v1')!==1) {
	wp_update_post(array('ID'=>$page_id,'post_content'=>$source_content));
	update_post_meta($page_id,'_elementor_data',wp_slash($source_meta));
	update_option('andy_cf_site_enabled',0,false);
	WP_CLI::error('Elementor update readback failed; source page reverted');
}
clean_post_cache($page_id);
do_action('litespeed_purge_post',$page_id);
do_action('litespeed_purge_url',get_permalink($page_id));
WP_CLI::log('SITE='.$host.' CONTACT_ID='.$page_id.' FORM_ID='.$source_id);
WP_CLI::log('CONTACT_URL='.get_permalink($page_id));
WP_CLI::log('CONTACT_INQUIRY_PRESET=site_contact_v1');
WP_CLI::log('WPFORMS_RETAINED=YES; OTHER_FORMS_UNCHANGED');
WP_CLI::success('CONTACT_FIRST_CUTOVER_WRITTEN; must pass HTTP/JS/REST/mail smoke before acceptance');
