<?php
/**
 * Contact First R3: route only explicitly named enquiry CTA button widgets to
 * the site's one Contact page. Preserve all unrelated Elementor widgets.
 * Site/page allowlist; exact pre-write backup; no blanket string replacements.
 */
if (!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI) { exit(1); }
$allow=array(
	'blanchedalmond-flamingo-601606.hostingersite.com'=>array('contact',array(1588)),
	'darkgoldenrod-camel-347943.hostingersite.com'=>array('contact-us',array(698)),
	'lightsteelblue-caterpillar-245164.hostingersite.com'=>array('contact',array(1588)),
	'skyblue-gaur-779563.hostingersite.com'=>array('contact',array(623,625)),
);
$host=(string)wp_parse_url(home_url('/'),PHP_URL_HOST);
if (!isset($allow[$host])) WP_CLI::error('Site not allowed for CTA rewrite');
list($slug,$pages)=$allow[$host];
if (1!==(int)get_option('andy_cf_site_enabled',0)) WP_CLI::error('Andy Core Contact is not verified live');
$contact_url='/'.$slug.'/';
$backup_root='/home/u595431186/.andy-contact-first-backups/20261112/';
$total=0;
foreach($pages as $page_id) {
	$post=get_post($page_id);
	if (!$post||$post->post_status!=='publish'||$post->post_type!=='page') WP_CLI::error('Expected page missing '.$page_id);
	$original=get_post_meta($page_id,'_elementor_data',true);
	$elementor=is_string($original)?json_decode($original,true):null;
	if (!is_array($elementor)) WP_CLI::error('Malformed Elementor JSON '.$page_id);
	$counts=0;
	$patch=function(&$nodes) use(&$patch,$contact_url,&$counts) {
		foreach($nodes as &$node) {
			if (!is_array($node))continue;
			if (($node['widgetType']??'')==='button') {
				$settings=$node['settings']??array();
				$text=trim((string)($settings['text']??''));
				if (preg_match('/^(contact us|get in touch|get a quote|request a quote|send inquiry|send enquiry|enquire now)$/i',$text)) {
					if (!isset($node['settings']['link'])||!is_array($node['settings']['link'])) {
						$node['settings']['link']=array();
					}
					if (($node['settings']['link']['url']??'')!==$contact_url) {
						$node['settings']['link']['url']=$contact_url;
						++$counts;
					}
				}
			}
			if (isset($node['elements'])&&is_array($node['elements']))$patch($node['elements']);
		}
	};
	$patch($elementor);
	if ($counts===0) { WP_CLI::log('CTA_PAGE_UNCHANGED='.$page_id);continue; }
	$backup=$backup_root.$host.'-cta-'.$page_id.'-elementor.before.json';
	if (file_exists($backup)) WP_CLI::error('CTA backup already exists; refuse rerun');
	if (file_put_contents($backup,$original)===false) WP_CLI::error('CTA backup write failed');
	chmod($backup,0600);
	$updated=wp_json_encode($elementor,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	if (!is_string($updated)||!update_post_meta($page_id,'_elementor_data',wp_slash($updated))) {
		WP_CLI::error('CTA meta update failed; backup at '.$backup);
	}
	$read=get_post_meta($page_id,'_elementor_data',true);
	if (substr_count((string)$read,$contact_url)<$counts) {
		update_post_meta($page_id,'_elementor_data',wp_slash($original));
		WP_CLI::error('CTA readback mismatch; source restored');
	}
	clean_post_cache($page_id);
	do_action('litespeed_purge_post',$page_id);
	do_action('litespeed_purge_url',get_permalink($page_id));
	WP_CLI::log('CTA_PAGE='.$page_id.' UPDATED_BUTTONS='.$counts.' DEST='.$contact_url);
	$total+=$counts;
}
WP_CLI::success('CONTACT_FIRST_CTA_BUTTONS_UPDATED='.$total.'; unrelated buttons unchanged');
