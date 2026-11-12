<?php
/**
 * R4 pre-activation guard for signed v2.2.11 on eligible Hostinger WPForms sites.
 * Refuse if missing backup, existing Core installation, or pre-existing options.
 * Does not activate plugin or modify WPForms.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
$allow = array(
	'blanchedalmond-flamingo-601606.hostingersite.com',
	'darkgoldenrod-camel-347943.hostingersite.com',
	'darkturquoise-bee-276354.hostingersite.com',
	'dodgerblue-mantis-171767.hostingersite.com',
	'fondomanpowergroup.com',
	'funnyslip.com',
	'gsfdcy.com',
	'lightcoral-weasel-975054.hostingersite.com',
	'lightsteelblue-caterpillar-245164.hostingersite.com',
	'mediumpurple-meerkat-978434.hostingersite.com',
	'skyblue-gaur-779563.hostingersite.com',
);
if (!in_array($host, $allow, true)) WP_CLI::error('Host not explicitly allowed');
$backup='/home/u595431186/.andy-contact-first-backups/20261112/'.$host.'.sql.gz';
if (!is_file($backup) || filesize($backup)<10000) WP_CLI::error('Verified DB backup missing');
if (file_exists(WP_PLUGIN_DIR.'/yby-core/yby-core.php')) WP_CLI::error('Andy Core plugin files already exist');
if (get_option('yby_core_enabled_modules_v1','__missing__')!=='__missing__') WP_CLI::error('Module options already exist');
if (get_option('yby_core_module_adoptions_v1','__missing__')!=='__missing__') WP_CLI::error('Adoptions already exist');
if (!is_plugin_active('wpforms-lite/wpforms.php')) WP_CLI::error('Source WPForms not active');
if (!update_option('yby_core_enabled_modules_v1',array(),false)) WP_CLI::error('Module preseed failed');
if (!update_option('yby_core_module_adoptions_v1',array('v170_landing_pages','v1_three_os_newsletter_menu'),false)) {
	delete_option('yby_core_enabled_modules_v1');
	WP_CLI::error('Adoptions preseed failed and module option reverted');
}
WP_CLI::success('PREACTIVATION_DISABLED_MODULES_SEEDED; WPForms still active; no plugin activated');
