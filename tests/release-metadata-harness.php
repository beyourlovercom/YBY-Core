<?php
$root = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/yby-core.php' );
$version = file_get_contents( $root . '/VERSION.md' );
$readme = file_get_contents( $root . '/README.md' );
$wp_readme = file_get_contents( $root . '/readme.txt' );
$changelog = file_get_contents( $root . '/CHANGELOG.md' );
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
		$root . '/scripts/build-v1.5.1-release.sh',
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
$assert( false !== strpos( $plugin, 'Version:           1.5.1' ), 'PLUGIN_HEADER_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_CORE_VERSION', '1.5.1' );" ), 'CORE_CONSTANT_VERSION_GATE failed.' );
$assert( false !== strpos( $plugin, "define( 'YBY_DATABASE_VERSION', '1.3.0' );" ), 'DATABASE_VERSION_GATE failed.' );
$assert( false !== strpos( $version, 'Stable Version: 1.5.1' ) && false !== strpos( $version, 'Development Version: None' ) && false !== strpos( $version, 'Stable Release Type: Stable' ), 'VERSION_MD_GATE failed.' );
$assert( false !== strpos( $readme, 'Product version: `1.5.1`' ) && false !== strpos( $readme, 'Database version: `1.3.0`' ), 'README_VERSION_GATE failed.' );
$assert( false !== strpos( $wp_readme, 'Stable tag: 1.5.1' ), 'README_STABLE_TAG_GATE failed.' );
$assert( false !== strpos( $changelog, '## v1.5.1 - 2026-08-07' ), 'CHANGELOG_RELEASE_GATE failed.' );
foreach ( $runtime_files as $file ) {
	if ( is_file( $file ) ) {
		$assert( false === strpos( file_get_contents( $file ), '1.5.1-dev' ), 'DEV_VERSION_STRING_GATE failed in ' . basename( $file ) );
	}
}
echo "PASS release-metadata-harness\n";
