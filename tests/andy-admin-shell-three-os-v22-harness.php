<?php
/** Real Dev Three OS controller + V2.2 shell integration, without a database. */
if (!defined('ABSPATH')) define('ABSPATH', __DIR__);
function is_admin() { return true; }
function esc_html($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function esc_html__($v,$domain='') { return esc_html($v); }
function esc_attr($v) { return esc_html($v); }
function esc_attr__($v,$domain='') { return esc_html($v); }
function esc_url($v) { return esc_html($v); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$v)); }
function wp_unslash($v) { return stripslashes((string)$v); }
function wp_parse_url($url,$part) { return parse_url($url,$part); }
function admin_url($path='') { return 'https://example.test/wp-admin/'.$path; }
function add_query_arg($args,$url) { return $url.'?'.http_build_query($args); }
class YBY_Andy_OS_Navigation {
	public static function tertiary_items($page) {
		if ('andy-core-email'!==$page) return array();
		return array(
			array('title'=>'邮件总览','url'=>admin_url('admin.php?page=andy-core-email&tab=overview')),
			array('title'=>'系统健康','url'=>admin_url('admin.php?page=andy-core-email&tab=health'))
		);
	}
}
require dirname(__DIR__).'/admin/class-yby-andy-admin-shell-v22.php';
require dirname(__DIR__).'/admin/class-yby-three-os-admin.php';
$assert=static function($ok,$what){if(!$ok){fwrite(STDERR,"FAIL $what\n");exit(1);}};
$tabs=array('overview'=>'邮件总览','health'=>'系统健康');
$_GET=array('page'=>'andy-core-email','tab'=>'overview');
// Fallback is exactly the old original WordPress tab UI, no blank page.
ob_start();
YBY_Three_OS_Admin::render_tabs('andy-core-email',$tabs,'overview','Email OS');
echo '<p>original content</p></div>';
$legacy=ob_get_clean();
$assert(1===substr_count($legacy,'nav-tab-wrapper'),'original tabs kept when adapter unavailable');
$assert(strpos($legacy,'original content')!==false,'original fallback content');
// New adapter renders one inner-left L3, suppresses legacy nav and retains data.
ob_start();
YBY_Andy_Admin_Shell_V22::render('andy-core-email','Email OS',static function() use ($tabs) {
	YBY_Three_OS_Admin::render_tabs('andy-core-email',$tabs,'overview','Email OS');
	echo '<form method="post"><input name="nonce" value="OLD_FORM_NONCE"></form></div>';
});
$html=ob_get_clean();
$assert(0===substr_count($html,'nav-tab-wrapper'),'old top nav hidden under server shell');
$assert(1===substr_count($html,'andy-core-shell-v22__rail"'),'one right-area feature rail');
$assert(1===substr_count($html,'OLD_FORM_NONCE'),'original form retained exactly');
$assert(1===substr_count($html,'aria-current="page"'),'selected link accessible');
$assert(!YBY_Andy_Admin_Shell_V22::rendering(),'shell context cleared');
// Page-specific other route cannot accidentally suppress legacy tabs.
$assert(!YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs('andy-core-leads'),'no cross-screen leak');
echo "PASS andy-admin-shell-three-os-v22-harness\n";
