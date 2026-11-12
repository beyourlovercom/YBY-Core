<?php
/**
 * R4 site-local rollback from Contact First Elementor shortcode to original
 * saved WPForms widget. Never restores entire DB or deletes new lead records.
 * Keep installed Andy Core inert, preserve WPForms and backups.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
$allow=array(
	'blanchedalmond-flamingo-601606.hostingersite.com'=>1952,
	'darkgoldenrod-camel-347943.hostingersite.com'=>695,
	'darkturquoise-bee-276354.hostingersite.com'=>3410,
	'dodgerblue-mantis-171767.hostingersite.com'=>492,
	'funnyslip.com'=>1952,
	'lightcoral-weasel-975054.hostingersite.com'=>1361,
	'lightsteelblue-caterpillar-245164.hostingersite.com'=>1952,
	'mediumpurple-meerkat-978434.hostingersite.com'=>393,
	'skyblue-gaur-779563.hostingersite.com'=>616,
);
$host=(string)wp_parse_url(home_url('/'),PHP_URL_HOST);
if (!isset($allow[$host])) WP_CLI::error('Site not authorized for automated rollback');
$id=$allow[$host];
$root='/home/u595431186/.andy-contact-first-backups/20261112/';
$content=$root.$host.'-contact-'.$id.'-before.txt';
$meta=$root.$host.'-contact-'.$id.'-elementor-before.json';
if (!is_file($content) || !is_file($meta)) WP_CLI::error('Exact site backups missing');
if (!is_plugin_active('wpforms-lite/wpforms.php')) WP_CLI::error('WPForms fallback no longer active');
$post=get_post($id);
if (!$post || !str_contains($post->post_content,'site_contact_v1')
	|| !str_contains((string)get_post_meta($id,'_elementor_data',true),'site_contact_v1')) {
	WP_CLI::error('Current site is not the known Contact First cutover; refuse rollback');
}
$before_content=file_get_contents($content);
$before_meta=file_get_contents($meta);
if (!str_contains($before_meta,'"widgetType":"wpforms"')) WP_CLI::error('Unexpected backup widget');
update_option('andy_cf_site_enabled',0,false);
$w=wp_update_post(array('ID'=>$id,'post_content'=>$before_content),true);
if (is_wp_error($w)||!$w) WP_CLI::error('Cannot restore original Contact post');
update_post_meta($id,'_elementor_data',wp_slash($before_meta));
YBY_Module_Registry::save(array());
clean_post_cache($id);
do_action('litespeed_purge_post',$id);
do_action('litespeed_purge_url',get_permalink($id));
WP_CLI::success('CONTACT_FIRST_ROLLBACK_RESTORED_WPFORMS; Andy Core inert, no records deleted');
