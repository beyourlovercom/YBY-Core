<?php
/**
 * V2.2.11 combined-contract static guard; no WordPress or filesystem writes.
 * Require one installable Andy Core that contains both prior Owner branches.
 */
$root = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/yby-core.php' );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$control = file_get_contents( $root . '/inc/class-yby-update-control.php' );
$updater = file_get_contents( $root . '/inc/class-yby-updater.php' );
$nav = file_get_contents( $root . '/admin/class-yby-andy-os-navigation.php' );
$assert = static function ( $ok, $label ) {
    if ( ! $ok ) { fwrite( STDERR, 'FAIL V2211_COMBINED ' . $label . PHP_EOL ); exit( 1 ); }
};
$assert( strpos( $plugin, "define( 'YBY_CORE_VERSION', '2.2.11' );" ) !== false, 'candidate_version' );
$assert( strpos( $core, "'inc/class-yby-update-control.php'" ) !== false, 'control_loaded_by_single_plugin' );
$assert( strpos( $core, 'YBY_Update_Control( $updater )' ) !== false, 'shared_existing_updater' );
foreach ( array( 'hide_plugin_notices', 'block_plugin_auto', 'prune_update_cron' ) as $key ) {
    $assert( strpos( $control, $key ) !== false, 'required_control_' . $key );
}
$assert( strpos( $control, "'wp_version_check'" ) !== false, 'core_security_check_preserved' );
$assert( strpos( $control, "'wp_maybe_auto_update'" ) !== false, 'scoped_wp_cron_policy' );
$assert( strpos( $updater, 'signed_background_auto_enabled' ) !== false, 'signed_self_update_opt_in' );
$assert( strpos( $updater, 'YBY_Update_Verifier::validate_zip' ) !== false, 'signed_package_verifier' );
foreach ( array( '文档', '招商', '落地页', '项目' ) as $name ) {
    $assert( strpos( $nav, $name ) !== false, 'V3_content_group_' . $name );
}
$assert( strpos( $nav, '$items[\'edit.php?post_type=landing_page\']' ) === false, 'no_duplicate_landing_L2' );
echo 'PASS v2.2.11 V3 + Update Control unified plugin contract' . PHP_EOL;
