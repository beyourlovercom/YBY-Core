<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};
$root = dirname( __DIR__ );
$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$project = file_get_contents( $root . '/admin/class-yby-project-studio.php' );
$admin = file_get_contents( $root . '/admin/class-yby-admin.php' );
$popup = file_get_contents( $root . '/admin/class-yby-popup-admin.php' );
$public = file_get_contents( $root . '/public/class-yby-public.php' );
foreach ( array( 'inquiry_os', 'email_os', 'project_studio', 'social_login', 'connector' ) as $id ) {
	$assert( false !== strpos( $core, "YBY_Module_Registry::is_enabled( '{$id}' )" ), 'core gate missing: ' . $id );
}
$assert( preg_match( '/is_enabled\( \'email_os\' \).*?wp_new_user_notification_email/s', $core ), 'Email OS system hooks must be gated' );
$assert( preg_match( '/if \( \$inquiry_enabled \).*?YBY_Lead_REST_Controller.*?YBY_Inquiry_Shortcodes/s', $core ), 'Inquiry REST/runtime must be gated' );
$assert( preg_match( '/if \( \$social_enabled \).*?YBY_Google_Auth_REST_Controller.*?YBY_Social_Login_Shortcodes/s', $core ), 'Social Login runtime must be gated' );
$assert( preg_match( '/if \( \$connector_enabled \).*?YBY_Connector\(\).*?register_routes/s', $core ), 'Connector route must be gated' );
$assert( false !== strpos( $project, "is_enabled( 'project_studio' )" ), 'Project Studio submenu/runtime gate missing' );
$assert( false !== strpos( $admin, "is_enabled( 'inquiry_os' )" ) && false !== strpos( $admin, "is_enabled( 'connector' )" ), 'Settings tabs must honor module gates' );
$assert( false !== strpos( $popup, "is_enabled( 'email_os' )" ) && false !== strpos( $popup, "is_enabled( 'inquiry_os' )" ), 'Email/Inquiry admin hub must honor module gates' );
$assert( false !== strpos( $public, "is_enabled( 'inquiry_os' )" ), 'Public assets must honor Inquiry OS gate' );
echo "PASS module-boot-gate-harness\n";
