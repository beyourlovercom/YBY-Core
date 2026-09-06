<?php
/** Focused fail-closed contract harness for Andy Core Secure Updater M1. */

$root = dirname( __DIR__ );
$temp = sys_get_temp_dir() . '/andy-core-updater-harness-' . str_replace( '.', '', uniqid( '', true ) );
@mkdir( $temp . '/wp-content/plugins/yby-core/inc', 0777, true );
@mkdir( $temp . '/wp-content/plugins/yby-core/admin', 0777, true );

define( 'ABSPATH', $temp . '/' );
define( 'WP_CONTENT_DIR', $temp . '/wp-content' );
define( 'YBY_CORE_UPDATE_BACKUP_DIR', $temp . '-private-backups' );
define( 'YBY_CORE_VERSION', '1.5.4' );
define( 'MINUTE_IN_SECONDS', 60 );

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}

$GLOBALS['yby_test_options'] = array();
$GLOBALS['yby_filter_token'] = '';
function __( $text ) { return $text; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function trailingslashit( $value ) { return rtrim( str_replace( '\\', '/', $value ), '/' ) . '/'; }
function untrailingslashit( $value ) { return rtrim( str_replace( '\\', '/', $value ), '/' ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || @mkdir( $path, 0777, true ); }
function sanitize_file_name( $value ) { return preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $value ); }
function wp_generate_password() { return 'HarnessA1'; }
function update_option( $key, $value ) { $GLOBALS['yby_test_options'][ $key ] = $value; return true; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['yby_test_options'] ) ? $GLOBALS['yby_test_options'][ $key ] : $default; }
function apply_filters( $hook, $default ) { return 'yby_core_github_token' === $hook ? $GLOBALS['yby_filter_token'] : $default; }
function wp_parse_url( $url, $component = -1 ) { return -1 === $component ? parse_url( $url ) : parse_url( $url, $component ); }

require_once $root . '/inc/class-yby-update-verifier.php';
require_once $root . '/inc/class-yby-github-release-client.php';
require_once $root . '/inc/class-yby-update-backup.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};

// Stable semantic version / release gates.
$base_assets = array(
	array( 'name' => 'andy-core-v1.5.5.zip', 'url' => 'https://api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/101', 'digest' => 'sha256:' . str_repeat( 'a', 64 ) ),
	array( 'name' => 'SHA256.txt', 'url' => 'https://api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/102' ),
	array( 'name' => 'update-metadata.json', 'url' => 'https://api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/103' ),
	array( 'name' => 'update-metadata.sig', 'url' => 'https://api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/104' ),
);
$release = array( 'tag_name' => 'v1.5.5', 'draft' => false, 'prerelease' => false, 'assets' => $base_assets );
$assert( '1.5.5' === YBY_Update_Verifier::release_version( $release ), 'stable release parsing failed' );
$assert( YBY_Update_Verifier::is_newer( '1.5.5', '1.5.4' ), 'newer semantic version failed' );
$assert( ! YBY_Update_Verifier::is_newer( '1.5.5-beta', '1.5.4' ), 'prerelease semantic version accepted' );
$draft = $release; $draft['draft'] = true; $assert( '' === YBY_Update_Verifier::release_version( $draft ), 'draft release accepted' );
$pre = $release; $pre['prerelease'] = true; $assert( '' === YBY_Update_Verifier::release_version( $pre ), 'prerelease accepted' );
$bad_tag = $release; $bad_tag['tag_name'] = '1.5.5'; $assert( '' === YBY_Update_Verifier::release_version( $bad_tag ), 'non-v tag accepted' );
$assert( is_array( YBY_Update_Verifier::select_asset( $release, '1.5.5' ) ), 'required assets rejected' );
$duplicate = $release; $duplicate['assets'][] = $base_assets[0]; $assert( false === YBY_Update_Verifier::select_asset( $duplicate, '1.5.5' ), 'duplicate required asset accepted' );
$bad_digest = $release; $bad_digest['assets'][0]['digest'] = 'md5:bad'; $assert( false === YBY_Update_Verifier::select_asset( $bad_digest, '1.5.5' ), 'malformed digest accepted' );

// SHA256 and compatibility metadata are both mandatory.
$sha = str_repeat( 'a', 64 );
$checksum = $sha . "  andy-core-v1.5.5.zip\n";
$assert( $sha === YBY_Update_Verifier::checksum( $checksum, 'andy-core-v1.5.5.zip' ), 'checksum evidence rejected' );
$assert( '' === YBY_Update_Verifier::checksum( $sha, 'andy-core-v1.5.5.zip' ), 'bare checksum accepted' );
$assert( '' === YBY_Update_Verifier::checksum( $sha . "  other.zip\n", 'andy-core-v1.5.5.zip' ), 'wrong package checksum accepted' );
$metadata = json_encode( array( 'schema_version' => 1, 'version' => '1.5.5', 'database_version' => '1.4.0', 'package' => 'andy-core-v1.5.5.zip', 'sha256' => $sha ) );
$assert( YBY_Update_Verifier::metadata( $metadata, '1.5.5', 'andy-core-v1.5.5.zip', $sha ), 'valid update metadata rejected' );
$assert( ! YBY_Update_Verifier::metadata( str_replace( '1.4.0', '1.5.0', $metadata ), '1.5.5', 'andy-core-v1.5.5.zip', $sha ), 'database-changing release accepted' );
$assert( ! YBY_Update_Verifier::metadata( str_replace( '"schema_version":1', '"schema_version":2', $metadata ), '1.5.5', 'andy-core-v1.5.5.zip', $sha ), 'unknown metadata schema accepted' );
if ( function_exists( 'sodium_crypto_sign_keypair' ) ) {
	$keypair = sodium_crypto_sign_keypair();
	$test_secret = sodium_crypto_sign_secretkey( $keypair );
	$test_public = sodium_crypto_sign_publickey( $keypair );
	$test_signature = base64_encode( sodium_crypto_sign_detached( $metadata, $test_secret ) );
	$assert( YBY_Update_Verifier::signature( $metadata, $test_signature, base64_encode( $test_public ) ), 'valid Ed25519 metadata signature rejected' );
	$assert( ! YBY_Update_Verifier::signature( $metadata . 'x', $test_signature, base64_encode( $test_public ) ), 'tampered metadata signature accepted' );
	sodium_memzero( $test_secret );
}

// GitHub API authentication and redirect scope.
$client = new YBY_GitHub_Release_Client();
$assert( ! $client->has_auth(), 'missing token did not fail closed' );
$GLOBALS['yby_filter_token'] = 'test-token-harness';
$assert( $client->has_auth(), 'filter token fallback rejected' );
$asset_url = 'https://api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/123';
$assert( $client->is_api_asset_url( $asset_url ), 'canonical asset API URL rejected' );
$assert( ! $client->is_api_asset_url( 'https://evil@example.com@api.github.com/repos/beyourlovercom/YBY-Core/releases/assets/123' ), 'userinfo API URL accepted' );
$assert( ! $client->is_api_asset_url( 'https://api.github.com:444/repos/beyourlovercom/YBY-Core/releases/assets/123' ), 'non-default API port accepted' );
$assert( ! $client->is_api_asset_url( $asset_url . '?x=1' ), 'query-bearing API asset accepted' );
$assert( $client->is_asset_redirect_url( 'https://release-assets.githubusercontent.com/github-production-release-asset/x?sp=r' ), 'canonical release asset redirect rejected' );
$assert( ! $client->is_asset_redirect_url( 'https://github.com/beyourlovercom/YBY-Core/releases/download/x.zip' ), 'github.com redirect accepted' );
$assert( ! $client->is_asset_redirect_url( 'https://u:p@release-assets.githubusercontent.com/x' ), 'userinfo redirect accepted' );
$assert( ! $client->is_asset_redirect_url( 'https://release-assets.githubusercontent.com:444/x' ), 'non-default redirect port accepted' );

// Package structure validation when ZipArchive is available.
if ( class_exists( 'ZipArchive' ) ) {
	$valid_zip = $temp . '/valid.zip';
	$zip = new ZipArchive(); $zip->open( $valid_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE );
	$main = "<?php\n/**\n * Version:           1.5.5\n */\ndefine( 'YBY_CORE_VERSION', '1.5.5' );\ndefine( 'YBY_DATABASE_VERSION', '1.4.0' );\n";
	$zip->addFromString( 'yby-core/yby-core.php', $main );
	$zip->addFromString( 'yby-core/inc/class-yby-core.php', '<?php // core' );
	$zip->addFromString( 'yby-core/inc/class-yby-loader.php', '<?php // loader' );
	$zip->addFromString( 'yby-core/admin/class-yby-admin.php', '<?php // admin' );
	$zip->close();
	$assert( true === YBY_Update_Verifier::validate_zip( $valid_zip, '1.5.5' ), 'valid package rejected' );

	$bad_zip = $temp . '/bad.zip';
	$zip = new ZipArchive(); $zip->open( $bad_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE );
	$zip->addFromString( 'yby-core/yby-core.php', $main );
	$zip->addFromString( 'yby-core/inc/class-yby-core.php', '<?php // core' );
	$zip->addFromString( 'yby-core/inc/class-yby-loader.php', '<?php // loader' );
	$zip->addFromString( 'yby-core/admin/class-yby-admin.php', '<?php // admin' );
	$zip->addFromString( 'yby-core/tests/evil.php', '<?php // forbidden' );
	$zip->close();
	$assert( is_wp_error( YBY_Update_Verifier::validate_zip( $bad_zip, '1.5.5' ) ), 'development-only ZIP path accepted' );
}

// Backup/restore must remain inside the dedicated direct-child backup root.
$plugin_dir = $temp . '/wp-content/plugins/yby-core';
$fixture_main = "<?php\n/**\n * Version:           1.5.4\n */\ndefine( 'YBY_CORE_VERSION', '1.5.4' );\ndefine( 'YBY_DATABASE_VERSION', '1.4.0' );\n";
file_put_contents( $plugin_dir . '/yby-core.php', $fixture_main );
file_put_contents( $plugin_dir . '/inc/class-yby-core.php', '<?php // core' );
file_put_contents( $plugin_dir . '/inc/class-yby-loader.php', '<?php // loader' );
file_put_contents( $plugin_dir . '/inc/class-yby-github-release-client.php', '<?php // client' );
file_put_contents( $plugin_dir . '/inc/class-yby-update-verifier.php', '<?php // verifier' );
file_put_contents( $plugin_dir . '/inc/class-yby-update-backup.php', '<?php // backup' );
file_put_contents( $plugin_dir . '/inc/class-yby-updater.php', '<?php // updater' );
$backup_failure = YBY_Update_Backup::create( $temp . '/missing-plugin', '1.5.4' );
$assert( is_wp_error( $backup_failure ), 'unsafe/missing plugin backup did not fail closed' );
$backup = YBY_Update_Backup::create( $plugin_dir, '1.5.4' );
$assert( is_array( $backup ) && is_dir( $backup['path'] ), 'backup creation failed' );
file_put_contents( $plugin_dir . '/yby-core.php', 'broken' );
$assert( true === YBY_Update_Backup::restore_latest( $plugin_dir ), 'compatible code rollback failed' );
$assert( YBY_Update_Backup::health( $plugin_dir, '1.5.4' ), 'restored code failed health validation' );
$GLOBALS['yby_test_options'][ YBY_Update_Backup::OPTION ] = array( 'path' => $plugin_dir, 'version' => '1.5.4', 'database_version' => '1.4.0' );
$assert( is_wp_error( YBY_Update_Backup::restore_latest( $plugin_dir ) ), 'rollback escaped dedicated backup root' );

// Static orchestration safety gates.
$client_source  = file_get_contents( $root . '/inc/class-yby-github-release-client.php' );
$updater_source = file_get_contents( $root . '/inc/class-yby-updater.php' );
$backup_source  = file_get_contents( $root . '/inc/class-yby-update-backup.php' );
$assert( 1 === substr_count( $client_source, "'Authorization'" ), 'Authorization header is constructed in more than one code path' );
$assert( false !== strpos( $client_source, 'Deliberately omit Authorization' ), 'redirect authentication stripping contract missing' );
$assert( false === strpos( $updater_source, "\$_GET['yby_core_updater_action']" ), 'mutating updater GET action present' );
$assert( false !== strpos( $updater_source, "\$_POST['yby_core_check_updates']" ) && false !== strpos( $updater_source, 'check_admin_referer' ), 'POST/nonce admin gate missing' );
$assert( false !== strpos( $updater_source, "current_user_can( 'andy_core_settings_manage' )" ), 'updater capability gate missing' );
$assert( false !== strpos( $updater_source, 'set_site_transient(' ) && false !== strpos( $updater_source, "'target_version'" ) && false !== strpos( $updater_source, 'restore_record' ), 'persisted target-version rollback orchestration missing' );
$assert( false !== strpos( $updater_source, 'yby_update_pending_store_failed' ) && false !== strpos( $updater_source, 'return $release;' ), 'verified pending state/package failures are not fail-closed' );
$assert( false !== strpos( $updater_source, 'nav-tab-wrapper' ), 'updates page shared navigation missing' );
$assert( false !== strpos( $backup_source, 'YBY_CORE_UPDATE_BACKUP_DIR' ) && false !== strpos( $backup_source, 'public WordPress root' ), 'private backup-root guard missing' );
$assert( false !== strpos( $updater_source, 'validate_zip' ) && false !== strpos( $updater_source, "hash_file( 'sha256'" ) && false !== strpos( $updater_source, 'YBY_Update_Verifier::signature' ), 'pre-install package/signature verification missing' );
$assert( false === strpos( $updater_source, 'echo $this->client->token' ) && false === strpos( $client_source, 'set_site_transient( $key, $this->token()' ), 'token rendering/storage detected' );
$assert( false !== strpos( $backup_source, 'is_direct_backup_child' ) && false !== strpos( $backup_source, 'is_link' ) && false !== strpos( $backup_source, 'const RETAIN = 3' ), 'backup containment/symlink/retention contracts missing' );

// Cleanup test artifacts.
$remove = static function ( $path ) use ( &$remove ) {
	if ( is_link( $path ) || is_file( $path ) ) { @unlink( $path ); return; }
	if ( ! is_dir( $path ) ) { return; }
	foreach ( scandir( $path ) as $item ) { if ( '.' !== $item && '..' !== $item ) { $remove( $path . '/' . $item ); } }
	@rmdir( $path );
};
$remove( $temp );
$remove( YBY_CORE_UPDATE_BACKUP_DIR );

echo "PASS secure-updater-harness\n";
