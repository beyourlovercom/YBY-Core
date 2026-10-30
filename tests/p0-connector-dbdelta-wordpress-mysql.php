<?php
/**
 * Disposable WordPress DB integration guard. NEVER execute against a live site.
 * Run with ANDY_P0_SYNTHETIC_DB=1 only on an isolated synthetic database.
 */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'ANDY_P0_SYNTHETIC_DB' ) ) {
	fwrite( STDERR, "P0 isolated synthetic DB gate required\n" );
	exit( 2 );
}
if ( ! class_exists( 'YBY_Database' ) ) {
	throw new RuntimeException( 'Andy Core not loaded' );
}
global $wpdb;
$tables = array(
	$wpdb->prefix . 'yby_connector_payout_bindings' => array( 'PRIMARY', 'connection_request', 'payout_id', 'state_expires' ),
	$wpdb->prefix . 'yby_connector_payout_claims' => array( 'PRIMARY', 'referral_id', 'payout_request' ),
);
foreach ( array( 1, 2 ) as $pass ) {
	YBY_Database::install();
	foreach ( $tables as $table => $required_indexes ) {
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			throw new RuntimeException( "Missing table: {$table}" );
		}
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $table, ARRAY_A );
		$id = null;
		foreach ( $columns as $column ) {
			if ( 'id' === $column['Field'] ) { $id = $column; break; }
		}
		if ( ! $id || false === stripos( $id['Extra'], 'auto_increment' ) ) {
			throw new RuntimeException( "Missing auto_increment id: {$table}" );
		}
		$indexes = array_unique( $wpdb->get_col( 'SHOW INDEX FROM ' . $table, 2 ) );
		foreach ( $required_indexes as $index ) {
			if ( ! in_array( $index, $indexes, true ) ) {
				throw new RuntimeException( "Missing index {$index} in {$table}" );
			}
		}
		if ( $wpdb->last_error ) {
			throw new RuntimeException( "Database error on pass {$pass}: {$wpdb->last_error}" );
		}
	}
}
echo "P0_CONNECTOR_DBDELTA_IDEMPOTENCE_PASS\n";
