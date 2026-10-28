<?php
/** Isolated process contract for alias dispatch, without real DB or credentials. */
$root = dirname( __DIR__ );
$dir = sys_get_temp_dir() . '/andy-login-os-v1-' . bin2hex( random_bytes( 6 ) );
if ( ! mkdir( $dir, 0700 ) ) { exit( 1 ); }
$source = realpath( $root . '/inc/class-yby-login-os.php' );
if ( ! $source ) { exit( 1 ); }
file_put_contents( $dir . '/wp-login.php', '<?php echo "WP_NATIVE_LOGIN_DISPATCHED";' );
$boot = <<<'PHP'
<?php
define( 'ABSPATH', __DIR__ . '/' );
function get_option( $name, $default = false ) {
	if ( 'yby_login_os_options_v1' === $name ) return array( 'url_enabled'=>true, 'slug'=>'secure-login' );
	return $default;
}
function is_multisite() { return false; }
function sanitize_hex_color( $s ) { return preg_match( '/^#[0-9a-f]{6}$/i', $s ) ? $s : null; }
function esc_url_raw( $v, $scheme = array() ) { return $v; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function wp_parse_url( $s, $part = -1 ) { return parse_url( $s, $part ); }
function get_page_by_path( $s ) { return false; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_unslash( $s ) { return $s; }
function nocache_headers() {}
require __SOURCE__;
$_SERVER['REQUEST_URI'] = $argv[1];
$_SERVER['REQUEST_METHOD'] = $argv[2];
YBY_Login_OS::maybe_load_alias();
echo 'UNCHANGED_REQUEST';
PHP;
$boot = str_replace( '__SOURCE__', var_export( $source, true ), $boot );
file_put_contents( $dir . '/entry.php', $boot );
$cases = array(
	array( '/secure-login/', 'GET', 'WP_NATIVE_LOGIN_DISPATCHED' ),
	array( '/secure-login/?action=lostpassword', 'POST', 'WP_NATIVE_LOGIN_DISPATCHED' ),
	array( '/secure-login-similar/', 'GET', 'UNCHANGED_REQUEST' ),
	array( '/wp-login.php', 'GET', 'UNCHANGED_REQUEST' ),
	array( '/secure-login/', 'PUT', 'UNCHANGED_REQUEST' ),
	array( '/my-account/', 'GET', 'UNCHANGED_REQUEST' ),
);
$ok = true;
foreach ( $cases as $case ) {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $dir . '/entry.php' ) .
		' ' . escapeshellarg( $case[0] ) . ' ' . escapeshellarg( $case[1] );
	$output = array();
	$status = 0;
	exec( $command . ' 2>&1', $output, $status );
	$actual = trim( implode( "\n", $output ) );
	if ( 0 !== $status || $case[2] !== $actual ) {
		fwrite( STDERR, "FAIL route {$case[0]} {$case[1]}: status=$status output=$actual\n" );
		$ok = false;
	}
}
foreach ( array( 'entry.php', 'wp-login.php' ) as $file ) { unlink( $dir . '/' . $file ); }
rmdir( $dir );
if ( ! $ok ) { exit( 1 ); }
echo "Login OS V1 alias dispatch: PASS\n";
