<?php
/** Verify a newly built package with an older updater verifier. */
if ( 1 === $argc ) {
	$root    = dirname( __DIR__ );
	$plugin  = file_get_contents( $root . '/yby-core.php' );
	$builder = file_get_contents( $root . '/scripts/build-v1.5.8-release.sh' );
	$ok      = false !== strpos( $plugin, "define( 'YBY_DATABASE_VERSION', '1.4.0' );" )
		&& false !== strpos( $plugin, "define( 'YBY_RUNTIME_DATABASE_VERSION', '1.5.0' );" )
		&& false !== strpos( $builder, 'runtime_database_version="1.5.0"' );
	if ( ! $ok ) { fwrite( STDERR, "FAIL updater backward-compat source contract\n" ); exit( 1 ); }
	echo "PASS updater backward-compat source contract\n";
	exit( 0 );
}
if ( 4 !== $argc ) { fwrite( STDERR, "usage: php updater-backward-compat-harness.php VERIFIER ZIP METADATA\n" ); exit( 2 ); }
define( 'ABSPATH', __DIR__ . '/' );
function __( $text, $domain = null ) { return $text; }
class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
require $argv[1];
$package  = $argv[2];
$metadata = file_get_contents( $argv[3] );
$version  = '1.5.8';
$name     = 'andy-core-v1.5.8.zip';
$sha      = hash_file( 'sha256', $package );
$meta_ok = YBY_Update_Verifier::metadata( $metadata, $version, $name, $sha );
$zip_ok  = YBY_Update_Verifier::validate_zip( $package, $version );
if ( ! $meta_ok || true !== $zip_ok ) {
	$error = is_wp_error( $zip_ok ) ? $zip_ok->code . ': ' . $zip_ok->message : 'zip_validation_failed';
	fwrite( STDERR, "FAIL v1.5.7 updater compatibility: metadata=" . ( $meta_ok ? 'PASS' : 'FAIL' ) . " zip={$error}\n" );
	exit( 1 );
}
echo "PASS v1.5.7 updater accepts v1.5.8 metadata + ZIP\n";
