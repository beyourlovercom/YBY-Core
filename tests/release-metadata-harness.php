<?php
$root = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/yby-core.php' );
$version = file_get_contents( $root . '/VERSION.md' );
$readme = file_get_contents( $root . '/README.md' );
$wp_readme = file_get_contents( $root . '/readme.txt' );
$changelog = file_get_contents( $root . '/CHANGELOG.md' );
$release_notes = file_get_contents( $root . '/RELEASE_NOTES_v1.5.3.md' );
$upgrade_guide = file_get_contents( $root . '/UPGRADE_GUIDE_v1.5.3.md' );
$rollback_guide = file_get_contents( $root . '/ROLLBACK_GUIDE_v1.5.3.md' );
$tag_workflow = file_get_contents( $root . '/.github/workflows/release.yml' );
$builder = file_get_contents( $root . '/scripts/build-v1.5.3-release.sh' );
$collect_files = static function ( $paths ) {
	$files = array();
	foreach ( $paths as $path ) {
		if ( is_file( $path ) ) {
			$files[] = $path;
			continue;
		}
		if ( ! is_dir( $path ) ) {
			continue;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$files[] = $file->getPathname();
			}
		}
	}
	return $files;
};
$runtime_files = $collect_files(
	array(
		$root . '/yby-core.php',
		$root . '/README.md',
		$root . '/VERSION.md',
		$root . '/CHANGELOG.md',
		$root . '/readme.txt',
		$root . '/scripts/build-v1.5.3-release.sh',
		$root . '/admin',
		$root . '/assets',
		$root . '/inc',
		$root . '/languages',
		$root . '/modules',
		$root . '/public',
		$root . '/templates',
	)
);
$assert = static function ( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
};
$assert( false !== strpos( $plugin, 'Version:           1.5.3' ), 'PLUGIN_HEADER_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_CORE_VERSION', '1.5.3' );" ), 'CORE_CONSTANT_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_DATABASE_VERSION', '1.4.0' );" ), 'DATABASE_VERSION_GATE failed.' );
$assert( false !== strpos( $version, 'Stable Version: 1.5.3' ) && false !== strpos( $version, 'Development Version: None' ) && false !== strpos( $version, 'Stable Release Type: Stable' ), 'VERSION_MD_GATE failed.' );
$assert( false !== strpos( $readme, 'Product version: `1.5.3`' ) && false !== strpos( $readme, 'Database version: `1.4.0`' ), 'README_VERSION_GATE failed.' );
$assert( false !== strpos( $wp_readme, 'Stable tag: 1.5.3' ) && false !== strpos( $wp_readme, 'Tested up to: 7.1' ), 'README_METADATA_GATE failed.' );
$assert( false !== strpos( $changelog, '## v1.5.3 - 2026-08-31' ), 'CHANGELOG_RELEASE_GATE failed.' );
$assert( false !== strpos( $release_notes, 'Plugin version: 1.5.3' ) && false !== strpos( $release_notes, 'Database version: 1.4.0' ), 'RELEASE_NOTES_GATE failed.' );
$assert( false !== strpos( $upgrade_guide, 'Upgrade target: Andy Core 1.5.3' ) && false !== strpos( $upgrade_guide, 'Database target: 1.4.0' ), 'UPGRADE_GUIDE_GATE failed.' );
$assert( false !== strpos( $rollback_guide, 'Rollback target: last verified Andy Core v1.5.2 package' ), 'ROLLBACK_GUIDE_GATE failed.' );
$assert( false === strpos( $tag_workflow, 'softprops/action-gh-release' ), 'TAG_WORKFLOW_MUST_NOT_PUBLISH_GATE failed.' );
$assert( false !== strpos( $tag_workflow, 'actions/upload-artifact@v4' ) && false !== strpos( $tag_workflow, 'scripts/build-v${version}-release.sh' ) && false !== strpos( $tag_workflow, 'andy-core-v${version}.zip' ), 'TAG_BUILD_ARTIFACT_GATE failed.' );
$builder_metadata_checks = array(
	"if ! grep -Fq 'Version:           1.5.3' \"\${root_dir}/yby-core.php\"; then",
	"if ! grep -Fq \"define( 'YBY_CORE_VERSION', '1.5.3' );\" \"\${root_dir}/yby-core.php\"; then",
	"if ! grep -Fq \"define( 'YBY_DATABASE_VERSION', '1.4.0' );\" \"\${root_dir}/yby-core.php\"; then",
	"if ! grep -Fq 'Stable tag: 1.5.3' \"\${root_dir}/readme.txt\"; then",
);
$stage_position = strpos( $builder, 'stage_dir="$(mktemp -d)"' );
$staging_mkdir_position = strpos( $builder, 'mkdir -p "${stage_dir}/yby-core" "${output_dir}"' );
$assert( false !== $stage_position && false !== $staging_mkdir_position && $stage_position < $staging_mkdir_position, 'BUILDER_STAGING_ORDER_GATE failed.' );
foreach ( $builder_metadata_checks as $check ) {
	$check_position = strpos( $builder, $check );
	$assert( false !== $check_position, 'BUILDER_SOURCE_METADATA_GATE failed.' );
	$assert( $check_position < $stage_position && $check_position < $staging_mkdir_position, 'BUILDER_SOURCE_METADATA_ORDER_GATE failed.' );
}
foreach ( $runtime_files as $file ) {
	if ( is_file( $file ) ) {
		$assert( false === strpos( file_get_contents( $file ), '1.5.3-dev' ), 'DEV_VERSION_STRING_GATE failed in ' . basename( $file ) );
	}
}
echo "PASS release-metadata-harness\n";
