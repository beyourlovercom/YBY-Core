<?php
/**
 * V2.2 Dev-only release preflight — validates source-byte integrity and fail-closed flags.
 * Never reads/writes the deployed Dev filesystem and never declares Dev Owner UAT PASS.
 */
$root = dirname( __DIR__ );
$fail = static function( $valid, $reason ) {
    if ( ! $valid ) { fwrite( STDERR, "FAIL v22-dev-release-gate: $reason\n" ); exit( 1 ); }
};
$path = $root . '/deploy/hostinger-dev-andy-core-v22/allowlist.json';
$manifest = json_decode( file_get_contents( $path ), true );
$fail( is_array( $manifest ), 'manifest JSON must parse' );
$fail( ( $manifest['schema_version'] ?? null ) === 1, 'manifest schema v1' );
$fail( ( $manifest['source_head'] ?? '' ) === '669576d457371ad102c4be50073a7f331013af40', 'immutable source exact HEAD' );
$fail( ( $manifest['status'] ?? '' ) === 'BLOCKED_PENDING_LIVE_DEV_HASHES_COMMERCE_DRIFT_AND_ROLLBACK', 'release must fail closed' );
$fail( ( $manifest['dev_origin'] ?? '' ) === 'https://dev.beyourlover.com', 'only Dev origin allowed' );
$fail( ( $manifest['dev_paths']['commerce'] ?? '' ) === 'UNVERIFIED_ACTUAL_DEV_PLUGIN_ROOT', 'do not guess Commerce installation path' );
$fail( strpos( (string) ( $manifest['release_type'] ?? '' ), 'no-whole-plugin-zip' ) !== false, 'no old ZIP install' );
$files = $manifest['files'] ?? null;
$fail( is_array( $files ) && count( $files ) === 26, 'exactly 26 independently listed candidate files' );
$seen = array();
$counts = array( 'yby-core' => 0, 'andy-commerce' => 0 );
foreach ( $files as $file ) {
    $name = $file['source_path'] ?? '';
    $plugin = $file['plugin'] ?? '';
    $relative = $file['destination_relative_path'] ?? '';
    $fail( is_string( $name ) && preg_match( '~^(admin|assets|inc|packages/andy-commerce)/[a-zA-Z0-9._/-]+\\.(php|css|js)$~', $name ) && strpos( $name, '..' ) === false, 'safe source path' );
    $fail( ! isset( $seen[ $name ] ), 'no duplicate paths' );
    $seen[ $name ] = true;
    $fail( isset( $counts[ $plugin ] ), 'only two independent plugins' );
    ++$counts[ $plugin ];
    $expected_relative = 'andy-commerce' === $plugin
        ? substr( $name, strlen( 'packages/andy-commerce/' ) ) : $name;
    $fail( $relative === $expected_relative, 'plugin-scoped relative target must match source' );
    $contents = file_get_contents( $root . '/' . $name );
    $fail( is_string( $contents ), 'source must exist' );
    $hash = sha1( 'blob ' . strlen( $contents ) . "\0" . $contents );
    $fail( hash_equals( (string) ( $file['source_git_blob_sha1'] ?? '' ), $hash ), 'Git blob source byte mismatch: ' . $name );
    $fail( array_key_exists( 'dev_current_blob_sha1', $file ) && $file['dev_current_blob_sha1'] === null, 'no invented Dev source checksum' );
    $fail( ( $file['backup_verified'] ?? null ) === false, 'no invented Dev rollback verification' );
}
$fail( $counts['yby-core'] === 21 && $counts['andy-commerce'] === 5, 'Core/Commerce release isolation' );
echo "PASS andy-core-v22-dev-release-gate-harness\n";
