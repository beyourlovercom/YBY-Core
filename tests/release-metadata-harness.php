<?php
/** Release metadata contract for Andy Core v1.5.4. */
$root = dirname( __DIR__ );
$read = static function ( $file ) use ( $root ) {
	$path = $root . '/' . $file;
	if ( ! is_file( $path ) ) { throw new RuntimeException( 'Missing file: ' . $file ); }
	return file_get_contents( $path );
};
$assert = static function ( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } };

$plugin      = $read( 'yby-core.php' );
$version     = $read( 'VERSION.md' );
$readme      = $read( 'README.md' );
$wp_readme   = $read( 'readme.txt' );
$changelog   = $read( 'CHANGELOG.md' );
$workflow    = $read( '.github/workflows/release.yml' );
$validation  = $read( '.github/workflows/release-package-validation.yml' );
$builder     = $read( 'scripts/build-v1.5.4-release.sh' );
$signer      = $read( 'scripts/sign-update-metadata.php' );
$updater     = $read( 'inc/class-yby-updater.php' );
$verifier    = $read( 'inc/class-yby-update-verifier.php' );

$assert( false !== strpos( $plugin, 'Version:           1.5.4' ), 'PLUGIN_HEADER_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_CORE_VERSION', '1.5.4' );" ), 'CORE_CONSTANT_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_DATABASE_VERSION', '1.4.0' );" ), 'DATABASE_VERSION_GATE failed.' );
$assert( false !== strpos( $version, 'Stable Version: 1.5.4' ) && false !== strpos( $version, 'Database Version: 1.4.0' ), 'VERSION_MD_GATE failed.' );
$assert( false !== strpos( $readme, 'Product version: `1.5.4`' ) && false !== strpos( $readme, 'Database version: `1.4.0`' ), 'README_VERSION_GATE failed.' );
$assert( false !== strpos( $wp_readme, 'Stable tag: 1.5.4' ) && false !== strpos( $wp_readme, 'Tested up to: 7.1' ), 'README_METADATA_GATE failed.' );
$assert( false !== strpos( $changelog, '## v1.5.4 - 2026-09-05' ), 'CHANGELOG_RELEASE_GATE failed.' );

foreach ( array( 'RELEASE_NOTES_v1.5.4.md', 'UPGRADE_GUIDE_v1.5.4.md', 'ROLLBACK_GUIDE_v1.5.4.md', 'docs/v1.5.4/ANDY-CORE-SECURE-UPDATER-M1.md', 'docs/v1.5.4/ANDY-CORE-RELEASE-AUTOMATION-M1.md' ) as $file ) {
	$assert( is_file( $root . '/' . $file ), 'Release/WP documentation missing: ' . $file );
}
foreach ( array( 'RELEASE_NOTES_v1.5.3.md', 'UPGRADE_GUIDE_v1.5.3.md', 'ROLLBACK_GUIDE_v1.5.3.md', 'scripts/build-v1.5.3-release.sh' ) as $file ) {
	$assert( is_file( $root . '/' . $file ), 'Historical v1.5.3 evidence missing: ' . $file );
}

$assert( false !== strpos( $builder, 'andy-core-v${version}.zip' ), 'PACKAGE_NAME_GATE failed.' );
$assert( false !== strpos( $builder, 'SHA256.txt' ) && false !== strpos( $builder, 'update-metadata.json' ), 'UPDATER_EVIDENCE_BUILD_GATE failed.' );
$assert( false !== strpos( $builder, 'update-metadata.sig' ) && false !== strpos( $builder, 'ANDY_CORE_UPDATE_SIGNING_SECRET' ), 'ED25519_BUILD_GATE failed.' );
$assert( false !== strpos( $signer, 'sodium_crypto_sign_detached' ) && false !== strpos( $signer, 'sodium_crypto_sign_publickey_from_secretkey' ), 'ED25519_SIGNER_GATE failed.' );
$assert( false !== strpos( $verifier, "SIGNING_PUBLIC_KEY_B64 = 'LcPq5x+fNa96aC+cXyC1ZbwRoGXCihF9YSG+iMHtjKU='" ) && false !== strpos( $verifier, 'sodium_crypto_sign_verify_detached' ), 'ED25519_VERIFY_GATE failed.' );
$assert( false !== strpos( $builder, '"schema_version": 1' ) && false !== strpos( $builder, '"database_version": "%s"' ), 'UPDATER_METADATA_SCHEMA_GATE failed.' );
$assert( false !== strpos( $builder, "printf '%s  %s\\n'" ), 'SHA256_FILENAME_EVIDENCE_GATE failed.' );
$assert( false !== strpos( $builder, 'define( \'YBY_DATABASE_VERSION\', \'${database_version}\' );' ), 'BUILDER_DATABASE_COMPATIBILITY_GATE failed.' );

$publish_pos = strpos( $workflow, "  publish-release:\n" );
$assert( false !== $publish_pos, 'TAG_RELEASE_PUBLISH_JOB_GATE failed.' );
$build_section   = substr( $workflow, 0, $publish_pos );
$publish_section = substr( $workflow, $publish_pos );
$assert( false === strpos( $build_section, 'gh release' ) && false !== strpos( $workflow, "permissions:\n  contents: read" ), 'TAG_BUILD_READ_ONLY_GATE failed.' );
$assert( false !== strpos( $publish_section, 'needs: tagged-release-package' ) && false !== strpos( $publish_section, "permissions:\n      contents: write" ), 'TAG_PUBLISH_DEPENDENCY_PERMISSION_GATE failed.' );
$assert( false !== strpos( $publish_section, 'actions/download-artifact@v4' ) && false !== strpos( $publish_section, 'gh release create' ) && false !== strpos( $publish_section, '--draft' ), 'TAG_DRAFT_RELEASE_GATE failed.' );
$assert( false !== strpos( $publish_section, 'gh api' ) && false !== strpos( $publish_section, '.assets[].name' ) && false !== strpos( $publish_section, '.digest' ), 'TAG_RELEASE_ASSET_VERIFY_GATE failed.' );
$assert( false !== strpos( $publish_section, 'gh release edit' ) && false !== strpos( $publish_section, '--draft=false' ) && false !== strpos( $publish_section, '--latest' ), 'TAG_RELEASE_STABLE_PUBLISH_GATE failed.' );
$assert( false === strpos( $workflow, 'softprops/action-gh-release' ), 'TAG_RELEASE_UNAPPROVED_ACTION_GATE failed.' );
$assert( false !== strpos( $workflow, 'actions/upload-artifact@v4' ) && false !== strpos( $workflow, 'update-metadata.json' ) && false !== strpos( $workflow, 'update-metadata.sig' ) && false !== strpos( $workflow, 'ANDY_CORE_UPDATE_SIGNING_SECRET' ), 'TAG_BUILD_ARTIFACT_GATE failed.' );
$assert( false !== strpos( $validation, 'php tests/secure-updater-harness.php' ) && false !== strpos( $validation, 'andy-core-v1.5.4-release-candidate' ), 'PR_RELEASE_VALIDATION_GATE failed.' );

$assert( false !== strpos( $updater, 'const PENDING_KEY' ) && false !== strpos( $updater, "'target_version'" ), 'UPDATER_TARGET_STATE_GATE failed.' );
$assert( false !== strpos( $updater, 'YBY_Update_Backup::restore_record' ), 'UPDATER_EXACT_BACKUP_ROLLBACK_GATE failed.' );
$assert( false !== strpos( $verifier, "const DATABASE_VERSION = '1.4.0'" ) && false !== strpos( $verifier, "'schema_version'" ), 'VERIFIER_DATABASE_GATE failed.' );

echo "PASS release-metadata-harness\n";
