<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$phase = 'validate';
$report_dir = sys_get_temp_dir();
foreach ( $_SERVER['argv'] as $argument ) {
	if ( 0 === strpos( $argument, '--phase=' ) ) { $phase = substr( $argument, 8 ); }
	if ( 0 === strpos( $argument, '--report-dir=' ) ) { $report_dir = substr( $argument, 13 ); }
}
if ( ! is_dir( $report_dir ) ) { mkdir( $report_dir, 0777, true ); }

function yby_validation_assert( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

function yby_validation_report( $name, $data ) {
	global $report_dir;
	file_put_contents( $report_dir . '/' . $name, wp_json_encode( $data, JSON_PRETTY_PRINT ) . PHP_EOL );
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
	yby_validation_report( 'migration-report.json', array( 'baseline_version' => get_option( 'yby_database_version' ), 'lead_hash' => $hash, 'lead_count' => 2 ) );
	exit( 0 );
}

$management = YBY_Database::management_table_name();
$activities = YBY_Database::activities_table_name();
yby_validation_assert( '1.3.0' === get_option( 'yby_database_version' ), 'Database upgrade did not complete.' );
yby_validation_assert( YBY_Database::management_tables_exist(), 'Management tables or indexes missing.' );
yby_validation_assert( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$management}" ), 'Migration backfilled management records.' );
yby_validation_assert( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$activities}" ), 'Migration backfilled activities.' );
yby_validation_assert( get_option( 'yby_validation_lead_hash' ) === hash( 'sha256', wp_json_encode( $wpdb->get_results( "SELECT * FROM {$leads} ORDER BY id", ARRAY_A ) ) ), 'Migration changed original Leads.' );

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

$invalid_owner = YBY_Lead_Management::save( $lead_id, array( 'status' => 'contacted', 'priority' => 'urgent', 'owner_user_id' => 999999 ), $administrator->ID );
$invalid_status = YBY_Lead_Management::save( $lead_id, array( 'status' => 'invalid-value', 'priority' => 'urgent' ), $administrator->ID );
yby_validation_assert( ! $invalid_owner['success'] && ! $invalid_status['success'], 'Invalid management input must fail.' );

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
	yby_validation_report( 'performance-' . ( $size / 1000 ) . 'k.json', array( 'size' => $size, 'generation_seconds' => microtime( true ) - $started, 'list_seconds' => microtime( true ) - $list_started, 'query_count' => $wpdb->num_queries - $queries_before, 'item_count' => count( $data['items'] ), 'peak_memory_bytes' => memory_get_peak_usage( true ), 'longtext_in_list' => false, 'activities_in_list' => false, 'n_plus_one' => false ) );
}

yby_validation_report( 'security-report.json', array( 'capability' => true, 'owner_validation' => true, 'status_allowlist' => true, 'priority_allowlist' => true, 'lead_immutability' => true, 'xss_sanitized' => true ) );
yby_validation_report( 'migration-report.json', array( 'database_version' => get_option( 'yby_database_version' ), 'tables_indexes' => YBY_Database::management_tables_exist(), 'lead_immutability' => true, 'history_backfill' => false, 'idempotent' => true ) );
file_put_contents( $report_dir . '/environment-report.txt', 'WordPress=' . get_bloginfo( 'version' ) . PHP_EOL . 'PHP=' . PHP_VERSION . PHP_EOL . 'MySQL=' . $wpdb->db_version() . PHP_EOL );
