<?php
/** Verify hidden-mode dispatch in separate PHP processes; no real WordPress users. */
$root = dirname( __DIR__ );
$dir = sys_get_temp_dir() . '/andy-login-stealth-' . bin2hex( random_bytes( 5 ) );
mkdir( $dir, 0700 );
$source = realpath( $root . '/inc/class-yby-login-os.php' );
$fixture = <<<'PHP'
<?php
define( 'ABSPATH', __DIR__ . '/' );
function get_option( $key, $default = false ) {
	if ( 'yby_login_os_options_v1' === $key ) {
		return array( 'url_enabled'=> true, 'hide_legacy' => getenv('HIDE_LOGIN') !== '0', 'slug'=>'secure-login' );
	}
	return $default;
}
function is_multisite() { return false; }
function sanitize_hex_color( $s ) { return preg_match('/^#[a-f0-9]{6}$/i', $s) ? $s : null; }
function esc_url_raw( $v, $scheme = array() ) { return $v; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function wp_parse_url( $s, $part = -1 ) { return parse_url( $s, $part ); }
function get_page_by_path( $s ) { return false; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_unslash( $s ) { return $s; }
function is_user_logged_in() { return getenv('IS_AUTH') === '1'; }
function nocache_headers() {}
function status_header( $code ) { echo "STATUS=$code;"; }
require __SOURCE__;
$_SERVER['REQUEST_URI'] = $argv[1];
YBY_Login_OS::maybe_block_legacy();
echo 'ALLOWED';
PHP;
file_put_contents( $dir . '/case.php', str_replace('__SOURCE__', var_export( $source, true ), $fixture) );
$cases = array(
	array('/wp-login.php', '1', '0', 'STATUS=404;404 Not Found'),
	array('/wp-login%2ephp?action=login', '1', '0', 'STATUS=404;404 Not Found'),
	array('/admin', '1', '0', 'STATUS=404;404 Not Found'),
	array('/admin/', '1', '0', 'STATUS=404;404 Not Found'),
	array('/wp-admin/', '1', '0', 'STATUS=404;404 Not Found'),
	array('/wp-admin/edit.php', '1', '0', 'STATUS=404;404 Not Found'),
	array('/wp-admin/admin.php?page=dashboard', '1', '0', 'STATUS=404;404 Not Found'),
	array('/wp-admin/admin-ajax.php', '1', '0', 'ALLOWED'),
	array('/wp-admin/admin-post.php', '1', '0', 'ALLOWED'),
	array('/wp-admin/load-scripts.php', '1', '0', 'ALLOWED'),
	array('/wp-admin/', '1', '1', 'ALLOWED'),
	array('/admin/', '1', '1', 'ALLOWED'),
	array('/my-account/', '1', '0', 'ALLOWED'),
	array('/checkout/', '1', '0', 'ALLOWED'),
	array('/secure-login/', '1', '0', 'ALLOWED'),
	array('/wp-login.php', '0', '0', 'ALLOWED'),
);
$passed = 0;
foreach ($cases as $case) {
	[$url, $enabled, $auth, $expected] = $case;
	$cmd = 'HIDE_LOGIN=' . escapeshellarg($enabled) . ' IS_AUTH=' . escapeshellarg($auth) .
		' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/case.php') . ' ' . escapeshellarg($url);
	exec($cmd . ' 2>&1', $lines, $status);
	$got = trim(implode("\n", $lines));
	$lines = array();
	if ($status !== 0 || $got !== $expected) {
		fwrite(STDERR, "STEALTH_FAIL url=$url auth=$auth hide=$enabled got=$got expected=$expected\n");
		unlink($dir.'/case.php');
		rmdir($dir);
		exit(1);
	}
	$passed++;
}
unlink($dir.'/case.php');
rmdir($dir);
echo "Login OS V1 stealth routing: PASS ($passed cases)\n";
