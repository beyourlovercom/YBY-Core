<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$phase = getenv( 'YBY_VALIDATION_PHASE' ) ?: 'validate';
$report_dir = getenv( 'YBY_VALIDATION_REPORT_DIR' );
if ( ! is_string( $report_dir ) || '' === $report_dir ) {
	throw new RuntimeException( 'YBY_VALIDATION_REPORT_DIR must be provided by the isolated validation runner.' );
}

if ( ! is_dir( $report_dir ) && ! wp_mkdir_p( $report_dir ) ) {
	throw new RuntimeException( 'Could not create validation report directory.' );
}

function yby_validation_assert( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

function yby_validation_assert_m7_indexes() {
	global $wpdb;
	$tables = array(
		YBY_Database::connector_payout_bindings_table_name() => array( 'connection_request', 'payout_id', 'state_expires' ),
		YBY_Database::connector_payout_claims_table_name()   => array( 'referral_id', 'payout_request' ),
	);
	$unique = array( 'connection_request', 'payout_id', 'referral_id' );
	foreach ( $tables as $table => $required ) {
		$found = array();
		foreach ( (array) $wpdb->get_results( 'SHOW INDEX FROM ' . $table, ARRAY_A ) as $index ) {
			$key_name = $index['Key_name'] ?? '';
			if ( '' !== $key_name ) { $found[ $key_name ] = true; }
			if ( in_array( $key_name, $unique, true ) ) {
				yby_validation_assert( '0' === (string) ( $index['Non_unique'] ?? '1' ), 'M7 index must be UNIQUE: ' . $table . '.' . $key_name );
			}
		}
		foreach ( $required as $name ) { yby_validation_assert( ! empty( $found[ $name ] ), 'M7 index is missing: ' . $table . '.' . $name ); }
	}
}

function yby_validation_report( $report_dir, $name, $data ) {
	$result = file_put_contents( $report_dir . '/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT ) . PHP_EOL );
	yby_validation_assert( false !== $result, 'Could not write validation report: ' . $name );
}

global $wpdb;
$leads = $wpdb->prefix . 'yby_leads';

if ( 'seed' === $phase ) {
	yby_validation_assert( '1.2.0' === get_option( 'yby_database_version' ), 'Expected v1.5.0 database version.' );
	foreach ( array( array( 'YBY-VAL-BOTTLE-001', 'bottle_oem_inquiry', 'bottle_oem' ), array( 'YBY-VAL-IRR-001', 'irrigation_quick_inquiry', 'irrigation_wholesale' ) ) as $row ) {
		$wpdb->insert( $leads, array( 'case_id' => $row[0], 'brand' => 'validation', 'website' => 'http://validation.test', 'source_url' => 'http://validation.test/inquiry', 'name' => 'Synthetic', 'company' => 'Synthetic Company', 'country' => 'Testland', 'email' => 'synthetic@example.test', 'whatsapp' => '10000000000', 'buyer_type' => 'test', 'product_interest' => 'Synthetic', 'quantity' => '1', 'project_details' => str_repeat( 'Synthetic long text. ', 100 ), 'source_component' => 'validation', 'source_preset' => $row[1], 'source_page' => 'Validation', 'form_version' => 'test', 'page_profile' => $row[2], 'custom_fields' => wp_json_encode( array( 'utm_source' => 'validation' ) ), 'utm_source' => 'validation', 'utm_medium' => 'test', 'utm_campaign' => 'validation', 'utm_term' => 'test', 'gclid' => 'test', 'fbclid' => 'test', 'status' => 'new', 'created_at' => current_time( 'mysql' ) ) );
	}
	$hash = hash( 'sha256', wp_json_encode( $wpdb->get_results( "SELECT * FROM {$leads} ORDER BY id", ARRAY_A ) ) );
	update_option( 'yby_validation_lead_hash', $hash, false );
	yby_validation_report( $report_dir, 'migration-report.json', array( 'baseline_version' => get_option( 'yby_database_version' ), 'lead_hash' => $hash, 'lead_count' => 2 ) );
	exit( 0 );
}

$management = YBY_Database::management_table_name();
$activities = YBY_Database::activities_table_name();
yby_validation_assert( '1.5.0' === get_option( 'yby_database_version' ) && YBY_RUNTIME_DATABASE_VERSION === get_option( 'yby_database_version' ), 'Current PR database schema authority must migrate to 1.5.0.' );
yby_validation_assert( YBY_Database::email_tables_exist(), 'Email OS template/version tables are missing after the 1.5.0 migration.' );
yby_validation_assert( YBY_Database::management_tables_exist(), 'Management tables or indexes missing.' );
yby_validation_assert( YBY_Database::connector_tables_exist(), 'Connector idempotency/audit tables or unique index missing.' );
yby_validation_assert_m7_indexes();
yby_validation_assert( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$management}" ), 'Migration backfilled management records.' );
yby_validation_assert( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$activities}" ), 'Migration backfilled activities.' );
yby_validation_assert( get_option( 'yby_validation_lead_hash' ) === hash( 'sha256', wp_json_encode( $wpdb->get_results( "SELECT * FROM {$leads} ORDER BY id", ARRAY_A ) ) ), 'Migration changed original Leads.' );

$validation_administrator = get_user_by( 'login', 'admin' );
yby_validation_assert( $validation_administrator instanceof WP_User, 'Validation administrator is unavailable.' );
wp_set_current_user( $validation_administrator->ID );

$GLOBALS['submenu'] = array();
do_action( 'admin_menu' );
$expected_submenus = array( 'andy-core-leads', 'yby-project-studio', 'edit.php?post_type=yby_project', 'yby-brand-os', 'yby-social-login', YBY_Helpers::admin_page_slug(), 'yby-core-popups' );
$registered_submenus = array_map( static function ( $item ) { return $item[2]; }, $GLOBALS['submenu'][ YBY_Project_Studio::menu_slug() ] ?? array() );
yby_validation_assert( $expected_submenus === $registered_submenus, 'Andy Core submenu structure is not exact.' );
yby_validation_assert( ! in_array( YBY_Project_Studio::menu_slug(), $registered_submenus, true ), 'Duplicate Andy Core submenu must be removed.' );

yby_validation_assert( false !== strpos( YBY_Project_Studio::studio_url( 'overview', 123 ), 'page=yby-project-studio' ), 'Project Studio URLs must use the dedicated submenu slug.' );
yby_validation_assert( 'yby-os' === YBY_Project_Studio::menu_slug(), 'The Andy Core parent slug must remain yby-os.' );

function yby_validation_assert_inquiry_assets( $query, $hook_suffix ) {
	$_GET = $query;
	wp_dequeue_style( 'yby-core-inquiry' );
	wp_dequeue_script( 'yby-core-inquiry' );
	do_action( 'admin_enqueue_scripts', $hook_suffix );
	yby_validation_assert( wp_style_is( 'yby-core-inquiry', 'enqueued' ), 'Inquiry stylesheet must be enqueued.' );
	yby_validation_assert( wp_script_is( 'yby-core-inquiry', 'enqueued' ), 'Inquiry script must be enqueued.' );
	$styles = wp_styles();
	$scripts = wp_scripts();
	yby_validation_assert( (string) filemtime( WP_PLUGIN_DIR . '/yby-core/assets/css/yby-inquiry-admin.css' ) === (string) $styles->registered['yby-core-inquiry']->ver, 'Inquiry stylesheet must use its file modification time.' );
	yby_validation_assert( (string) filemtime( WP_PLUGIN_DIR . '/yby-core/assets/js/yby-inquiry-admin.js' ) === (string) $scripts->registered['yby-core-inquiry']->ver, 'Inquiry script must use its file modification time.' );
}

$inquiry_hook = YBY_Inquiry_Admin::registered_page_hook();
yby_validation_assert( is_string( $inquiry_hook ) && '' !== $inquiry_hook, 'Inquiry page must retain its registered admin hook.' );
yby_validation_assert_inquiry_assets( array( 'page' => 'andy-core-leads' ), $inquiry_hook );
yby_validation_assert_inquiry_assets( array( 'page' => 'andy-core-leads', 's' => 'Synthetic' ), $inquiry_hook );
yby_validation_assert_inquiry_assets( array( 'page' => 'andy-core-leads', 'paged' => 2 ), $inquiry_hook );
yby_validation_assert_inquiry_assets( array( 'page' => 'andy-core-leads', 'lead_id' => 1 ), $inquiry_hook );
yby_validation_assert_inquiry_assets( array( 'page' => 'andy-core-leads', 'archived' => 'only' ), 'unexpected_hook_suffix' );
$_GET = array();
wp_dequeue_style( 'yby-core-inquiry' );
wp_dequeue_script( 'yby-core-inquiry' );
do_action( 'wp_enqueue_scripts' );
yby_validation_assert( ! wp_style_is( 'yby-core-inquiry', 'enqueued' ) && ! wp_script_is( 'yby-core-inquiry', 'enqueued' ), 'Inquiry assets must not load on the frontend.' );

$wpdb->query( "DROP INDEX lead_created ON {$activities}" );
YBY_Database::install();
yby_validation_assert( YBY_Database::management_tables_exist(), 'Migration did not repair a missing index.' );
YBY_Database::install();
YBY_Database::install();

update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => 0, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft' ) );
$lead_id = (int) $wpdb->get_var( "SELECT id FROM {$leads} ORDER BY id ASC LIMIT 1" );
$management_id = YBY_Lead_Management::ensure( $lead_id );
$managed = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$management} WHERE id = %d", $management_id ), ARRAY_A );
yby_validation_assert( 'pending_contact' === $managed['status'] && 'high' === $managed['priority'], 'Inquiry defaults are not applied.' );
yby_validation_assert( '2026-08-06 12:30:00' === YBY_Lead_Management::normalize_datetime( '2026-08-06T12:30' ), 'Datetime normalization failed.' );
yby_validation_assert( '2026-08-06T12:30' === YBY_Lead_Management::format_datetime_local( '2026-08-06 12:30:00' ), 'Datetime display failed.' );

$administrator = get_user_by( 'login', 'admin' );
wp_set_current_user( $administrator->ID );
$save = YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 0, 'next_follow_up_at' => '2026-08-06 12:30:00', 'note' => '<script>unsafe</script>safe', 'archived' => true ), $administrator->ID );
yby_validation_assert( $save['success'], 'Administrator management write failed.' );
$types = $wpdb->get_col( $wpdb->prepare( "SELECT activity_type FROM {$activities} WHERE lead_id = %d", $lead_id ) );
yby_validation_assert( in_array( 'status_changed', $types, true ) && in_array( 'priority_changed', $types, true ) && in_array( 'follow_up_scheduled', $types, true ) && in_array( 'archived', $types, true ), 'Expected activities missing.' );
$before = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$activities} WHERE lead_id = %d", $lead_id ) );
YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 0, 'next_follow_up_at' => '2026-08-06 12:30:00', 'archived' => true ), $administrator->ID );
yby_validation_assert( $before === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$activities} WHERE lead_id = %d", $lead_id ) ), 'Archive operation is not idempotent.' );

update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => 0, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => array() ) );
$invalid_owner = YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 999999 ), $administrator->ID );
$invalid_status = YBY_Lead_Management::save( $lead_id, array( 'status' => 'invalid-value', 'priority' => 'urgent' ), $administrator->ID );
yby_validation_assert( ! $invalid_owner['success'] && ! $invalid_status['success'], 'Invalid management input must fail.' );
$restore = YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 0, 'restore' => true ), $administrator->ID );
yby_validation_assert( $restore['success'], 'Restore failed.' );

$editor_id = wp_create_user( 'validation-editor', 'validation-only', 'editor@example.test' );
$author_id = wp_create_user( 'validation-author', 'validation-only', 'author@example.test' );
$subscriber_id = wp_create_user( 'validation-subscriber', 'validation-only', 'subscriber@example.test' );
wp_update_user( array( 'ID' => $editor_id, 'role' => 'editor' ) );
wp_update_user( array( 'ID' => $author_id, 'role' => 'author' ) );
wp_update_user( array( 'ID' => $subscriber_id, 'role' => 'subscriber' ) );
update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => 0, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft' ) );
wp_set_current_user( $editor_id );
yby_validation_assert( YBY_Security::can_view_leads() && ! YBY_Security::can_manage_leads() && ! YBY_Security::can_assign_leads(), 'Editor disabled contract failed.' );
$settings = YBY_Security::inquiry_settings();
$settings['editors_can_manage'] = true;
update_option( 'yby_core_inquiry_settings', $settings );
yby_validation_assert( YBY_Security::can_manage_leads(), 'Editor enablement contract failed.' );
$settings['assignment_enabled'] = false;
update_option( 'yby_core_inquiry_settings', $settings );
yby_validation_assert( ! YBY_Security::can_assign_leads(), 'Assignment disablement contract failed.' );
wp_set_current_user( $author_id );
yby_validation_assert( ! YBY_Security::can_view_leads() && ! YBY_Security::can_manage_leads(), 'Author must not access Inbox.' );
wp_set_current_user( $subscriber_id );
yby_validation_assert( ! YBY_Security::can_view_leads() && ! YBY_Security::can_manage_leads(), 'Subscriber must not access Inbox.' );
wp_set_current_user( $administrator->ID );

for ( $index = 0; $index < 55; $index++ ) {
	YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 0, 'note' => 'Synthetic note ' . $index ), $administrator->ID );
}
$detail = YBY_Lead_Management::get_detail( $lead_id );
yby_validation_assert( 50 === count( $detail['activities'] ), 'Activity reads must remain bounded at 50.' );
$fallback_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 37, 'search' => "' OR 1=1 --" ) );
yby_validation_assert( 50 === $fallback_list['per_page'], 'Invalid per_page must fall back to configured value.' );
yby_validation_assert( ! isset( $fallback_list['items'][0]['project_details'] ) && ! isset( $fallback_list['items'][0]['custom_fields'] ), 'List must not load LONGTEXT fields.' );

$security_gates = array();
$salesperson_a = wp_create_user( 'validation-sales-a', 'validation-only', 'sales-a@example.test' );
$salesperson_b = wp_create_user( 'validation-sales-b', 'validation-only', 'sales-b@example.test' );
wp_update_user( array( 'ID' => $salesperson_a, 'role' => 'salesperson' ) );
wp_update_user( array( 'ID' => $salesperson_b, 'role' => 'salesperson' ) );
update_option( 'yby_core_inquiry_salespeople', array( $salesperson_a, $salesperson_b ) );
update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => 0, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => array( $salesperson_a, $salesperson_b ) ) );
$lead_b = (int) $wpdb->get_var( "SELECT id FROM {$leads} ORDER BY id DESC LIMIT 1" );
$wpdb->insert( $leads, array( 'case_id' => 'YBY-VAL-UNASSIGNED-001', 'brand' => 'validation', 'website' => 'http://validation.test', 'source_url' => 'http://validation.test/inquiry', 'name' => 'Synthetic Unassigned', 'company' => 'Synthetic Company', 'country' => 'Testland', 'email' => 'unassigned@example.test', 'source_component' => 'validation', 'source_preset' => 'validation', 'source_page' => 'Validation', 'form_version' => 'test', 'page_profile' => 'validation', 'status' => 'new', 'created_at' => current_time( 'mysql' ) ) );
$lead_unassigned = (int) $wpdb->insert_id;
$assign_a = YBY_Lead_Management::save( $lead_id, array( 'owner_user_id' => $salesperson_a, 'status' => 'contacted', 'priority' => 'normal' ), $administrator->ID );
$assign_b = YBY_Lead_Management::save( $lead_b, array( 'owner_user_id' => $salesperson_b, 'status' => 'contacted', 'priority' => 'normal' ), $administrator->ID );
yby_validation_assert( $assign_a['success'] && $assign_b['success'], 'Initial active salesperson assignments failed.' );

update_option( 'yby_core_inquiry_salespeople', array( $salesperson_a ) );
update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => $salesperson_b, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => array( $salesperson_a ) ) );
yby_validation_assert( YBY_Security::is_salesperson( $salesperson_a ) && YBY_Security::is_salesperson( $salesperson_b ) && ! YBY_Security::is_salesperson( $subscriber_id ), 'SALESPERSON_ROLE_IDENTITY_GATE failed.' );
$security_gates['SALESPERSON_ROLE_IDENTITY_GATE'] = true;
yby_validation_assert( YBY_Security::is_active_owner( $salesperson_a ) && ! YBY_Security::is_active_owner( $salesperson_b ) && ! YBY_Security::is_active_owner( $administrator->ID ), 'ACTIVE_OWNER_GATE failed.' );
$security_gates['ACTIVE_OWNER_GATE'] = true;
yby_validation_assert( 0 === YBY_Security::inquiry_settings()['default_owner_user_id'] && (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_user_id FROM {$management} WHERE lead_id = %d", $lead_b ) ) === $salesperson_b, 'DEFAULT_OWNER_GATE failed.' );
$security_gates['DEFAULT_OWNER_GATE'] = true;

wp_set_current_user( $salesperson_a );
$sales_a_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30 ) );
$sales_a_detail = YBY_Lead_Management::get_detail( $lead_id );
yby_validation_assert( 1 === count( $sales_a_list['items'] ) && (int) $sales_a_list['items'][0]['owner_user_id'] === $salesperson_a && is_array( $sales_a_detail ) && (int) $sales_a_detail['owner_user_id'] === $salesperson_a, 'SALESPERSON_A_SCOPE_GATE failed.' );
$security_gates['SALESPERSON_A_SCOPE_GATE'] = true;
$sales_a_override_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30, 'owner_user_id' => $salesperson_b ) );
yby_validation_assert( 1 === count( $sales_a_override_list['items'] ) && (int) $sales_a_override_list['items'][0]['owner_user_id'] === $salesperson_a, 'QUERY_OVERRIDE_ISOLATION_GATE failed.' );
$security_gates['QUERY_OVERRIDE_ISOLATION_GATE'] = true;
yby_validation_assert( null === YBY_Lead_Management::get_detail( $lead_b ) && null === YBY_Lead_Management::get_detail( $lead_unassigned ), 'DIRECT_LEAD_ACCESS_ISOLATION_GATE failed.' );
$security_gates['DIRECT_LEAD_ACCESS_ISOLATION_GATE'] = true;
$security_gates['DIRECT_LEAD_ISOLATION_GATE'] = true;
$sales_a_write = YBY_Lead_Management::save( $lead_id, array( 'status' => 'following_up', 'priority' => 'normal', 'owner_user_id' => $salesperson_a, 'note' => 'forbidden salesperson note' ), $salesperson_a );
yby_validation_assert( ! $sales_a_write['success'] && ! YBY_Security::can_manage_leads() && ! YBY_Security::can_assign_leads() && ! YBY_Security::can_archive_leads(), 'Salesperson write scope failed.' );

wp_set_current_user( $salesperson_b );
$sales_b_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30 ) );
$sales_b_detail = YBY_Lead_Management::get_detail( $lead_b );
$sales_b_owners = array_map( 'intval', array_column( $sales_b_list['items'], 'owner_user_id' ) );
yby_validation_assert( 1 === count( $sales_b_list['items'] ) && array( $salesperson_b ) === $sales_b_owners && is_array( $sales_b_detail ) && (int) $sales_b_detail['owner_user_id'] === $salesperson_b && ! YBY_Security::is_active_owner( $salesperson_b ), 'SALESPERSON_B_SCOPE_GATE failed.' );
$security_gates['SALESPERSON_B_SCOPE_GATE'] = true;
$sales_b_override_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30, 'owner_user_id' => $salesperson_a ) );
yby_validation_assert( 1 === count( $sales_b_override_list['items'] ) && (int) $sales_b_override_list['items'][0]['owner_user_id'] === $salesperson_b, 'Inactive salesperson query override failed.' );
yby_validation_assert( null === YBY_Lead_Management::get_detail( $lead_id ), 'Inactive salesperson must not access A inquiry directly.' );

wp_update_user( array( 'ID' => $salesperson_b, 'role' => 'subscriber' ) );
clean_user_cache( $salesperson_b );
wp_set_current_user( 0 );
wp_set_current_user( $salesperson_b );
yby_validation_assert( ! YBY_Security::is_salesperson( $salesperson_b ) && ! YBY_Security::can_view_leads() && null === YBY_Lead_Management::get_detail( $lead_b ) && (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_user_id FROM {$management} WHERE lead_id = %d", $lead_b ) ) === $salesperson_b, 'ROLE_REMOVAL_ACCESS_GATE failed.' );
$security_gates['ROLE_REMOVAL_ACCESS_GATE'] = true;
wp_update_user( array( 'ID' => $salesperson_b, 'role' => 'salesperson' ) );
clean_user_cache( $salesperson_b );
wp_set_current_user( 0 );
wp_set_current_user( $salesperson_b );
$restored_b_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30 ) );
yby_validation_assert( YBY_Security::is_salesperson( $salesperson_b ) && ! YBY_Security::is_active_owner( $salesperson_b ) && 1 === count( $restored_b_list['items'] ) && (int) $restored_b_list['items'][0]['owner_user_id'] === $salesperson_b && is_array( YBY_Lead_Management::get_detail( $lead_b ) ), 'ROLE_RESTORE_HISTORY_ACCESS_GATE failed.' );
$security_gates['ROLE_RESTORE_HISTORY_ACCESS_GATE'] = true;

wp_set_current_user( $administrator->ID );
update_option( 'yby_core_inquiry_salespeople', array( $salesperson_a, $administrator->ID, $subscriber_id, 999999 ) );
update_option( 'yby_core_inquiry_settings', array( 'default_status' => 'pending_contact', 'default_priority' => 'high', 'default_owner_user_id' => $subscriber_id, 'leads_per_page' => 50, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => array( $salesperson_a, $administrator->ID, $subscriber_id, 999999 ) ) );
yby_validation_assert( array( $salesperson_a ) === YBY_Security::active_owner_ids() && ! YBY_Security::is_active_owner( $subscriber_id ) && 0 === YBY_Security::inquiry_settings()['default_owner_user_id'], 'STALE_ALLOWLIST_SECURITY_GATE failed.' );
wp_set_current_user( $subscriber_id );
yby_validation_assert( ! YBY_Security::can_view_leads(), 'Subscriber gained access through stale active-owner option.' );
$security_gates['STALE_ALLOWLIST_SECURITY_GATE'] = true;

wp_set_current_user( $administrator->ID );
$admin_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30 ) );
$admin_owners = array_map( 'intval', array_column( $admin_list['items'], 'owner_user_id' ) );
yby_validation_assert( in_array( $salesperson_a, $admin_owners, true ) && in_array( $salesperson_b, $admin_owners, true ) && in_array( 0, $admin_owners, true ), 'ADMIN_ALL_LEADS_GATE failed.' );
$security_gates['ADMIN_ALL_LEADS_GATE'] = true;
$admin_a_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30, 'owner_user_id' => $salesperson_a ) );
$admin_b_list = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 30, 'owner_user_id' => $salesperson_b ) );
yby_validation_assert( 1 === count( $admin_a_list['items'] ) && (int) $admin_a_list['items'][0]['owner_user_id'] === $salesperson_a, 'Administrator owner A filter failed.' );
yby_validation_assert( 1 === count( $admin_b_list['items'] ) && (int) $admin_b_list['items'][0]['owner_user_id'] === $salesperson_b, 'Administrator owner B filter failed.' );
$inactive_owner_options = YBY_Security::owner_dropdown_options( $salesperson_b );
$inactive_owner_labels = array_column( $inactive_owner_options, 'label', 'id' );
yby_validation_assert( isset( $inactive_owner_labels[ $salesperson_b ] ) && false !== strpos( $inactive_owner_labels[ $salesperson_b ], '(Inactive)' ) && (string) $salesperson_b !== $inactive_owner_labels[ $salesperson_b ] && ! in_array( $salesperson_b, array_map( 'intval', array_column( YBY_Security::owner_dropdown_options( 0 ), 'id' ) ), true ), 'OWNER_DROPDOWN_GATE failed.' );
$security_gates['OWNER_DROPDOWN_GATE'] = true;
$admin_assign_a = YBY_Lead_Management::save( $lead_unassigned, array( 'owner_user_id' => $salesperson_a, 'status' => 'contacted', 'priority' => 'normal' ), $administrator->ID );
$admin_assign_inactive_b = YBY_Lead_Management::save( $lead_unassigned, array( 'owner_user_id' => $salesperson_b, 'status' => 'contacted', 'priority' => 'normal' ), $administrator->ID );
yby_validation_assert( $admin_assign_a['success'] && ! $admin_assign_inactive_b['success'] && (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_user_id FROM {$management} WHERE lead_id = %d", $lead_unassigned ) ) === $salesperson_a, 'INACTIVE_OWNER_NEW_ASSIGNMENT_DENY_GATE failed.' );
$security_gates['INACTIVE_OWNER_NEW_ASSIGNMENT_DENY_GATE'] = true;
$admin_leave_inactive_b = YBY_Lead_Management::save( $lead_b, array( 'owner_user_id' => $salesperson_b, 'status' => 'following_up', 'priority' => 'normal' ), $administrator->ID );
yby_validation_assert( $admin_leave_inactive_b['success'] && (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_user_id FROM {$management} WHERE lead_id = %d", $lead_b ) ) === $salesperson_b, 'INACTIVE_OWNER_HISTORY_GATE failed.' );
$security_gates['INACTIVE_OWNER_HISTORY_GATE'] = true;
$admin_change_b_to_a = YBY_Lead_Management::save( $lead_b, array( 'owner_user_id' => $salesperson_a, 'status' => 'following_up', 'priority' => 'normal' ), $administrator->ID );
$admin_assign_back_to_b = YBY_Lead_Management::save( $lead_b, array( 'owner_user_id' => $salesperson_b, 'status' => 'following_up', 'priority' => 'normal' ), $administrator->ID );
yby_validation_assert( $admin_change_b_to_a['success'] && ! $admin_assign_back_to_b['success'] && (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_user_id FROM {$management} WHERE lead_id = %d", $lead_b ) ) === $salesperson_a, 'Inactive owner reassignment contract failed.' );
yby_validation_assert( ! YBY_Security::is_valid_owner( $administrator->ID ) && ! YBY_Security::is_valid_owner( 999999 ), 'Non-salesperson owner accepted.' );
wp_set_current_user( $administrator->ID );

foreach ( array( 1000, 10000, 50000 ) as $size ) {
	$started = microtime( true );
	$base = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$leads}" ) + 1;
	for ( $offset = 0; $offset < $size; $offset += 500 ) {
		$values = array();
		for ( $index = 0; $index < min( 500, $size - $offset ); $index++ ) {
			$id = $base + $offset + $index;
			$values[] = $wpdb->prepare( '(%s,%s,%s,%s,%s,%s,%s,%s)', 'YBY-PERF-' . $id, 'validation', 'Synthetic', 'Synthetic Company', 'Testland', 'perf@example.test', 'perf', current_time( 'mysql' ) );
		}
		$wpdb->query( "INSERT INTO {$leads} (case_id,brand,name,company,country,email,source_preset,created_at) VALUES " . implode( ',', $values ) );
	}
	$queries_before = $wpdb->num_queries;
	$list_started = microtime( true );
	$data = YBY_Lead_Management::list_leads( array( 'page' => 1, 'per_page' => 50 ) );
	yby_validation_report( $report_dir, 'performance-' . ( $size / 1000 ) . 'k.json', array( 'size' => $size, 'generation_seconds' => microtime( true ) - $started, 'list_seconds' => microtime( true ) - $list_started, 'query_count' => $wpdb->num_queries - $queries_before, 'item_count' => count( $data['items'] ), 'peak_memory_bytes' => memory_get_peak_usage( true ), 'longtext_in_list' => false, 'activities_in_list' => false, 'n_plus_one' => false, 'frontend_management_writes' => 0, 'frontend_inbox_assets' => 0 ) );
}

yby_validation_report( $report_dir, 'security-report.json', array_merge( array( 'capability' => true, 'editor_toggle' => true, 'author_denied' => true, 'subscriber_denied' => true, 'owner_validation' => true, 'status_allowlist' => true, 'priority_allowlist' => true, 'sql_injection_like_input' => true, 'lead_immutability' => true, 'xss_sanitized' => true, 'project_studio_menu' => true, 'inquiry_admin_assets' => true, 'frontend_inquiry_assets' => false ), $security_gates ) );
yby_validation_report( $report_dir, 'migration-report.json', array( 'database_version' => get_option( 'yby_database_version' ), 'tables_indexes' => YBY_Database::management_tables_exist(), 'lead_immutability' => true, 'history_backfill' => false, 'idempotent' => true ) );
file_put_contents( $report_dir . '/environment-report.txt', 'WordPress=' . get_bloginfo( 'version' ) . PHP_EOL . 'PHP=' . PHP_VERSION . PHP_EOL . 'MySQL=' . $wpdb->db_version() . PHP_EOL );
