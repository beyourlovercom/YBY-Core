<?php
$root = dirname( __DIR__ );
$database = file_get_contents( $root . '/inc/class-yby-database.php' );
$management = file_get_contents( $root . '/inc/class-yby-lead-management.php' );
$admin = file_get_contents( $root . '/admin/class-yby-inquiry-admin.php' );
$settings = file_get_contents( $root . '/admin/class-yby-admin.php' );
$view = file_get_contents( $root . '/admin/views/settings-page.php' );
$assert = static function ( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
};
$assert( strpos( $database, "const MANAGEMENT_TABLE = 'yby_lead_management'" ) !== false, 'Management table contract missing.' );
$assert( strpos( $database, "const ACTIVITIES_TABLE = 'yby_lead_activities'" ) !== false, 'Activities table contract missing.' );
$assert( substr_count( $database, 'dbDelta(' ) >= 3, 'Migration must use idempotent dbDelta.' );
$assert( strpos( $database, 'create_management_tables' ) !== false, 'Management migration is not wired.' );
$assert( strpos( $database, 'SHOW INDEX FROM' ) !== false, 'Migration must verify required indexes.' );
$assert( strpos( $management, 'UNIQUE' ) === false, 'Management service must not recreate schema.' );
$assert( strpos( $management, 'LIMIT 50' ) !== false, 'Activities must be bounded.' );
$assert( strpos( $management, 'project_details' ) === false, 'Inbox list must not load full project details.' );
$assert( strpos( $admin, "return 'andy-core-leads'" ) !== false, 'Inquiry slug missing.' );
$assert( strpos( $settings, 'system-status' ) !== false, 'Settings tab allowlist missing.' );
$assert( substr_count( $view, 'nav-tab') >= 3, 'General Settings tabs missing.' );
$assert( strpos( $admin, "'owner_user_id'" ) !== false, 'Owner filtering contract missing.' );
$assert( strpos( $admin, "'page_profile'" ) !== false, 'Page profile filtering contract missing.' );
$assert( strpos( $admin, "'archived'" ) !== false, 'Archive filtering contract missing.' );
$assert( strpos( $management, 'archived_at IS NULL' ) !== false, 'Archive filtering SQL missing.' );
$assert( strpos( $management, 'follow_up_scheduled' ) !== false, 'Follow-up activity contract missing.' );
$assert( strpos( $view, 'paginate_links' ) !== false, 'Inquiry list pagination UI missing.' );
echo "PASS inquiry-inbox-contract-harness\n";
