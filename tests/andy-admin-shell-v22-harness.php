<?php
/** Isolated contract of the V2.2 server-owned admin shell, zero WordPress I/O. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['is_admin_shell_test'] = true;
$GLOBALS['shell_permissions'] = true;
$GLOBALS['shell_items'] = array(
	array( 'title' => '询盘列表', 'url' => 'https://example.test/wp-admin/admin.php?page=andy-core-leads&tab=leads' ),
	array( 'title' => '询盘统计', 'url' => 'https://example.test/wp-admin/admin.php?page=andy-core-leads&tab=stats' ),
);
function is_admin() { return $GLOBALS['is_admin_shell_test']; }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_html__($s,$d='') { return esc_html($s); }
function esc_attr__($s,$d='') { return esc_html($s); }
function esc_url($s) { return esc_html($s); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$s)); }
function wp_unslash($s) { return stripslashes((string)$s); }
function wp_parse_url($u,$part) { return parse_url($u,$part); }
class YBY_Andy_OS_Navigation {
	public static function tertiary_items($page) {
		if (!$GLOBALS['shell_permissions'] || 'andy-core-leads' !== $page) return array();
		return $GLOBALS['shell_items'];
	}
}
require dirname(__DIR__) . '/admin/class-yby-andy-admin-shell-v22.php';
$assert = static function ($value,$label) { if(!$value){fwrite(STDERR,"FAIL: $label\n");exit(1);} };
$_GET = array( 'page' => 'andy-core-leads' );
$count = 0;
$render = static function () use (&$count) {
	++$count;
	echo '<form method="post" action="admin-post.php"><input type="hidden" name="nonce" value="OLD_NONCE"><button>Save</button></form>';
};
ob_start();
YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Inquiry OS', $render);
$html = ob_get_clean();
$assert(1 === $count,'original controller rendered once');
$assert(1 === substr_count($html, 'andy-core-shell-v22__rail"'), 'only one L3 rail');
$assert(2 === substr_count($html, 'class="andy-core-shell-v22__link"'), 'two real original deep-link tabs');
$assert(strpos($html,'value="OLD_NONCE"') !== false,'original POST and nonce markup preserved');
$assert(strpos($html,'action="admin-post.php"') !== false,'original handler preserved');
$assert(1 === substr_count($html, 'aria-current="page"'),'default existing inquiry tab active');
$assert(!YBY_Andy_Admin_Shell_V22::rendering(),'rendering scope cleared');
$GLOBALS['shell_permissions'] = false;
ob_start();YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Inquiry OS',$render);$fallback=ob_get_clean();
$assert(2 === $count,'unauthorized original callback still runs one time; original controller owns 403');
$assert(strpos($fallback,'andy-core-shell-v22') === false,'unauthorized shell cannot forge navigation');
$assert(strpos($fallback,'OLD_NONCE') !== false,'fail-open original HTML intact');
$GLOBALS['shell_permissions'] = true;
$_GET['tab'] = 'stats';
ob_start();YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Inquiry OS',static function() {
	$assert = function($b,$x){if(!$b)throw new Exception($x);};
	$assert(YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs('andy-core-leads'),'legacy tabs suppressed only during shell render');
	YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Nested',static function(){echo '<p>nested data</p>';});
});$nested=ob_get_clean();
$assert(1 === substr_count($nested, 'andy-core-shell-v22__rail"'),'nested shell blocked');
$assert(strpos($nested,'nested data') !== false,'nested original content preserved');
$assert(1 === substr_count($nested, 'aria-current="page"'),'active query-selected tab');
$assert(!YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs('andy-core-leads'),'do not hide legacy tabs without shell');
$thrown=false;
try { YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Error',static function(){throw new Exception('original handler exception');}); }
catch(Exception $e){$thrown=true;}
$assert($thrown && !YBY_Andy_Admin_Shell_V22::rendering(),'error cannot leave navigation context active');
$GLOBALS['is_admin_shell_test'] = false;
ob_start();YBY_Andy_Admin_Shell_V22::render('andy-core-leads','Original',$render);$off=ob_get_clean();
$assert(strpos($off,'andy-core-shell-v22') === false,'non-admin context does not render admin chrome');
echo "PASS andy-admin-shell-v22-harness\n";
