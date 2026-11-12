<?php
/**
 * Andy Core / WPForms Hostinger Contact First site-wide guard.
 * Applies ONLY to the vetted Hostinger rollout websites below.
 * Must load as an MU plugin before enabling Inquiry OS on any new site.
 *
 * Fail closed: public Inquiry OS remains disabled unless an explicit,
 * per-site enable option exists. Original WPForms never blocked.
 *
 * @package YBY_Core_Migration
 */
if (!defined('ABSPATH')) { exit; }
$andy_cf_allowed_hosts = array(
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
$andy_cf_current_host = strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!in_array($andy_cf_current_host,$andy_cf_allowed_hosts,true)) { return; }

if (!function_exists('andy_cf_site_ready')) {
	function andy_cf_site_ready() {
		$page = (int)get_option('andy_cf_contact_page_id',0);
		$slug = sanitize_title((string)get_option('andy_cf_contact_slug',''));
		return 1 === (int)get_option('andy_cf_site_enabled',0)
			&& $page > 0 && '' !== $slug && 'publish' === get_post_status($page);
	}
	function andy_cf_contact_slug() {
		return sanitize_title((string)get_option('andy_cf_contact_slug','contact'));
	}
	function andy_cf_path_is_contact($url_path) {
		return trim((string)$url_path,'/') === andy_cf_contact_slug();
	}
}
add_filter('pre_option_yby_core_enabled_modules_v1',static function($pre) {
	if ((defined('WP_CLI')&&WP_CLI)||is_admin()) { return $pre; }
	$path=wp_parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
	if (andy_cf_site_ready() && (andy_cf_path_is_contact($path)
		|| 0===strpos((string)$path,'/wp-json/yby/v1/leads'))) {
		return $pre;
	}
	return array();
},1);

add_filter('rest_pre_dispatch',static function($result,$server,$request) {
	if ('/yby/v1/leads'!==$request->get_route()) { return $result; }
	$deny=static function($code,$status) {
		return new WP_Error($code,'Please use the website Contact page form.',array('status'=>$status));
	};
	if (!andy_cf_site_ready()) { return $deny('andy_cf_disabled',403); }
	if ('POST'!==strtoupper($request->get_method())
		|| 'site_contact_v1'!==(string)$request->get_param('source_preset')
		|| 'inquiry_shortcode'!==(string)$request->get_param('source_component')) {
		return $deny('andy_cf_invalid_source',403);
	}
	$nonce=(string)$request->get_header('x-andy-contact-nonce');
	if (''===$nonce || !wp_verify_nonce($nonce,'andy_contact_first_v1')) {
		return $deny('andy_cf_nonce_missing',403);
	}
	$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
	$origin=(string)($_SERVER['HTTP_ORIGIN']??($_SERVER['HTTP_REFERER']??''));
	$origin_host=strtolower((string)wp_parse_url($origin,PHP_URL_HOST));
	$source=(string)$request->get_param('source_url');
	$source_host=strtolower((string)wp_parse_url($source,PHP_URL_HOST));
	$source_path=wp_parse_url($source,PHP_URL_PATH);
	if ($host!==$origin_host || $host!==$source_host || !andy_cf_path_is_contact($source_path)) {
		return $deny('andy_cf_origin_mismatch',403);
	}
	$fields=$request->get_param('fields');
	if (!is_array($fields) || '1'!==(string)($fields['site_contact_consent']??'')) {
		return $deny('andy_cf_consent_required',400);
	}
	$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
	$key='andy_cf_rate_'.substr(hash_hmac('sha256',$ip,wp_salt('nonce')),0,24);
	$count=(int)get_transient($key);
	if ($count>=6) { return $deny('andy_cf_rate_limited',429); }
	set_transient($key,$count+1,10*MINUTE_IN_SECONDS);
	return $result;
},5,3);

add_action('init',static function() {
	$path=wp_parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
	if (andy_cf_site_ready() && andy_cf_path_is_contact($path)) {
		if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE',true); }
		do_action('litespeed_control_set_nocache','andy_contact_first');
	}
},0);

add_action('wp',static function() {
	if (!andy_cf_site_ready() || !is_page((int)get_option('andy_cf_contact_page_id',0))) { return; }
	global $wp_filter;
	if (!isset($wp_filter['wp_footer'])) { return; }
	foreach($wp_filter['wp_footer']->callbacks as $priority=>$callbacks) {
		foreach($callbacks as $callback) {
			$fn=$callback['function']??null;
			if (is_array($fn) && isset($fn[0],$fn[1]) && is_object($fn[0])
				&& in_array(get_class($fn[0]),array('YBY_Global_Popup','YBY_Global_Inquiry_Dock'),true)) {
				remove_action('wp_footer',$fn,$priority);
			}
		}
	}
},20);

add_action('wp_enqueue_scripts',static function() {
	if (!andy_cf_site_ready() || !is_page((int)get_option('andy_cf_contact_page_id',0))) { return; }
	wp_register_script('andy-cf-nonce',false,array(),'1',false);
	wp_enqueue_script('andy-cf-nonce');
	$nonce=wp_json_encode(wp_create_nonce('andy_contact_first_v1'));
	wp_add_inline_script('andy-cf-nonce','(()=>{const orig=window.fetch;window.fetch=function(input,init){'
		.'const url=typeof input==="string"?input:(input&&input.url)||"";'
		.'if(url==="/wp-json/yby/v1/leads"){init=Object.assign({},init);'
		.'init.headers=Object.assign({},init.headers||{},{"X-Andy-Contact-Nonce":'.$nonce.'});}'
		.'return orig.call(this,input,init);};})();');
	wp_register_style('andy-cf-inline',false,array(),'1');
	wp_enqueue_style('andy-cf-inline');
	wp_add_inline_style('andy-cf-inline','
	.yby-shortcode-inquiry{max-width:1020px;margin:20px auto 40px;padding:10px 20px}
	.yby-shortcode-inquiry .yby-inquiry-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px}
	.yby-shortcode-inquiry .yby-inquiry-field{min-width:0;margin:0}
	.yby-shortcode-inquiry .yby-inquiry-field[data-yby-field-wrapper="message"],
	.yby-shortcode-inquiry .yby-inquiry-field[data-yby-field-wrapper="site_contact_consent"],
	.yby-shortcode-inquiry .yby-inquiry-form__status,
	.yby-shortcode-inquiry .yby-inquiry-form__error,
	.yby-shortcode-inquiry .yby-inquiry-form__submit{grid-column:1/-1}
	.yby-shortcode-inquiry input:not([type="checkbox"]),.yby-shortcode-inquiry textarea{width:100%;max-width:100%}
	.yby-shortcode-inquiry textarea{min-height:130px}
	.yby-shortcode-inquiry [data-yby-responsive="mobile"]{display:none}
	@media(max-width:700px){
	  .yby-shortcode-inquiry{padding:8px 14px}
	  .yby-shortcode-inquiry .yby-inquiry-form{grid-template-columns:minmax(0,1fr);gap:14px}
	  .yby-shortcode-inquiry [data-yby-responsive="desktop"]{display:none}
	  .yby-shortcode-inquiry [data-yby-responsive="mobile"]{display:block}
	}');
},1);

add_action('send_headers',static function() {
	if (andy_cf_site_ready() && is_page((int)get_option('andy_cf_contact_page_id',0))) {
		nocache_headers();
	}
},90);
