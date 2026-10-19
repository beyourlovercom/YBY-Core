<?php
/** Andy Content / Docs OS L3 routing regression. No live WordPress or database. */
$base = dirname( __DIR__ );
$nav = file_get_contents( $base . '/admin/class-yby-andy-os-navigation.php' );
$content = file_get_contents( $base . '/admin/class-yby-content-admin.php' );
$docs = file_get_contents( $base . '/admin/class-yby-docs-os-admin.php' );
$core = file_get_contents( $base . '/inc/class-yby-core.php' );
$shell = file_get_contents( $base . '/admin/class-yby-andy-admin-shell-v22.php' );
$assert = static function ( $ok, $reason ) { if ( ! $ok ) { fwrite( STDERR, "FAIL $reason\n" ); exit( 1 ); } };
$assert( strpos( $nav, "'admin.php?page=yby-docs-os'" ) !== false, 'real Docs L2 default' );
$assert( strpos( $nav, "YBY_Module_Registry::is_enabled( 'business_os' )" ) !== false, 'Business priority preserved' );
foreach ( array( 'yby-docs-os', 'yby-docs-all', 'yby-docs-directory', 'yby-docs-faq', 'yby-docs-tutorial', 'yby-docs-settings' ) as $slug ) {
    $assert( strpos( $nav, "'" . $slug . "' => array(" ) !== false, 'Docs L3 entry ' . $slug );
}
$assert( strpos( $nav, "edit-tags.php?taxonomy=doc_tag&post_type=docs" ) !== false, 'Docs tag taxonomy L3 link' );
$assert( strpos( $content, 'YBY_Andy_Admin_Shell_V22::render' ) !== false, 'Content old URL gets native server L3 shell' );
foreach ( array( "self::PAGE_SLUG, 'Docs OS', 'docs-os-dashboard.php'",
                  "'docs-os-all-docs.php', \$data",
                  "self::DIRECTORY_PAGE_SLUG, 'Docs OS', 'docs-os-directory.php'",
                  "self::SETTINGS_PAGE_SLUG, 'Docs OS', 'docs-os-settings.php'" ) as $route ) {
    $assert( strpos( $docs, $route ) !== false, 'Docs original view still rendered inside L3 shell: ' . $route );
}
$assert( strpos( $docs, "isset( \$_POST['yby_docs_bulk_submit'] )" ) !== false, 'bulk handlers retained' );
$assert( strpos( $docs, "isset( \$_POST['yby_docs_directory_action'] )" ) !== false, 'category handlers retained' );
$assert( strpos( $docs, "isset( \$_POST['yby_docs_settings_submit'] )" ) !== false, 'settings handlers retained' );
$assert( strpos( $shell, "call_user_func( \$body );" ) !== false, 'original server controller once' );
$assert( strpos( $core, "'enqueue_styles'" ) !== false, 'native shell CSS registration retained' );
$assert( strpos( $core, "'enqueue_tertiary'" ) === false, 'old DOM mover not reinstated' );
echo "PASS andy-content-docs-l3-v2210-harness\n";
