<?php
/** Durable, non-PII ERP-to-AffiliateWP binding authority. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Connector_Affiliate_Bindings {
	public static function by_kol( $connection_key, $erp_kol_id ) {
		return self::find( 'erp_kol_id', $connection_key, $erp_kol_id );
	}
	public static function by_affiliate( $connection_key, $affiliate_id ) {
		return self::find( 'affiliate_id', $connection_key, $affiliate_id );
	}
	private static function find( $field, $connection_key, $value ) {
		global $wpdb;
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		$sql = "SELECT connection_key, erp_kol_id, wp_user_id, affiliate_id, state, lease_expires_at, created_at, updated_at FROM {$table} WHERE connection_key = %s AND {$field} = %d LIMIT 1";
		$row = $wpdb->get_row( $wpdb->prepare( $sql, (string) $connection_key, absint( $value ) ), ARRAY_A );
		return is_array( $row ) ? $row : false;
	}
	public static function reserve( $connection_key, $erp_kol_id, $owner_hash, $lease_seconds = 300 ) {
		global $wpdb;
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		$now = gmdate( 'Y-m-d H:i:s' );
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 60, absint( $lease_seconds ) ) );
		$data = array( 'connection_key' => (string) $connection_key, 'erp_kol_id' => absint( $erp_kol_id ), 'wp_user_id' => null, 'affiliate_id' => null, 'state' => 'processing', 'lease_owner_hash' => (string) $owner_hash, 'lease_expires_at' => $expires, 'created_at' => $now, 'updated_at' => $now );
		$inserted = $wpdb->insert( $table, $data, array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		if ( false !== $inserted ) { return array( 'status' => 'acquired', 'row' => self::by_kol( $connection_key, $erp_kol_id ) ); }
		$row = self::by_kol( $connection_key, $erp_kol_id );
		if ( ! $row ) { return new WP_Error( 'AFFILIATE_BINDING_UNAVAILABLE', 'Could not safely acquire the affiliate provisioning reservation.', array( 'status' => 503, 'retryable' => true ) ); }
		if ( 'ready' === (string) ( $row['state'] ?? '' ) ) { return array( 'status' => 'ready', 'row' => $row ); }
		if ( ! empty( $row['lease_expires_at'] ) && strtotime( $row['lease_expires_at'] ) >= time() ) { return array( 'status' => 'busy', 'row' => $row ); }
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_owner_hash = %s, lease_expires_at = %s, updated_at = %s, state = 'processing', wp_user_id = NULL, affiliate_id = NULL WHERE connection_key = %s AND erp_kol_id = %d AND state = 'processing' AND lease_expires_at < UTC_TIMESTAMP()", (string) $owner_hash, $expires, $now, (string) $connection_key, absint( $erp_kol_id ) ) );
		if ( 1 === (int) $updated ) { return array( 'status' => 'acquired', 'row' => self::by_kol( $connection_key, $erp_kol_id ), 'recovered' => true ); }
		$row = self::by_kol( $connection_key, $erp_kol_id );
		return ( $row && 'ready' === (string) ( $row['state'] ?? '' ) ) ? array( 'status' => 'ready', 'row' => $row ) : array( 'status' => 'busy', 'row' => $row );
	}
	public static function renew( $connection_key, $erp_kol_id, $owner_hash, $lease_seconds = 300 ) {
		global $wpdb;
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 60, absint( $lease_seconds ) ) );
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_expires_at = %s, updated_at = %s WHERE connection_key = %s AND erp_kol_id = %d AND state = 'processing' AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP()", $expires, gmdate( 'Y-m-d H:i:s' ), (string) $connection_key, absint( $erp_kol_id ), (string) $owner_hash ) );
		if ( 1 === (int) $updated ) { return true; }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT connection_key, erp_kol_id, state, lease_owner_hash, lease_expires_at FROM {$table} WHERE connection_key = %s AND erp_kol_id = %d AND state = 'processing' AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP() LIMIT 1", (string) $connection_key, absint( $erp_kol_id ), (string) $owner_hash ), ARRAY_A );
		return is_array( $row ) && (string) ( $row['connection_key'] ?? '' ) === (string) $connection_key && (int) ( $row['erp_kol_id'] ?? 0 ) === absint( $erp_kol_id ) && 'processing' === (string) ( $row['state'] ?? '' ) && (string) ( $row['lease_owner_hash'] ?? '' ) === (string) $owner_hash && ! empty( $row['lease_expires_at'] ) && strtotime( (string) $row['lease_expires_at'] . ' UTC' ) >= time();
	}
	public static function finalize( $connection_key, $erp_kol_id, $owner_hash, $wp_user_id, $affiliate_id ) {
		global $wpdb;
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET wp_user_id = %d, affiliate_id = %d, state = 'ready', lease_owner_hash = NULL, lease_expires_at = NULL, updated_at = %s WHERE connection_key = %s AND erp_kol_id = %d AND state = 'processing' AND lease_owner_hash = %s", absint( $wp_user_id ), absint( $affiliate_id ), gmdate( 'Y-m-d H:i:s' ), (string) $connection_key, absint( $erp_kol_id ), (string) $owner_hash ) );
		return 1 === (int) $updated ? self::by_kol( $connection_key, $erp_kol_id ) : false;
	}
	public static function release( $connection_key, $erp_kol_id, $owner_hash ) {
		global $wpdb;
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		return $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_expires_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE connection_key = %s AND erp_kol_id = %d AND state = 'processing' AND lease_owner_hash = %s", (string) $connection_key, absint( $erp_kol_id ), (string) $owner_hash ) );
	}
	public static function add( $connection_key, $erp_kol_id, $wp_user_id, $affiliate_id ) {
		global $wpdb;
		$now = gmdate( 'Y-m-d H:i:s' );
		$table = YBY_Database::connector_affiliate_bindings_table_name();
		$ok = $wpdb->insert( $table, array( 'connection_key' => (string) $connection_key, 'erp_kol_id' => absint( $erp_kol_id), 'wp_user_id' => absint( $wp_user_id ), 'affiliate_id' => absint( $affiliate_id ), 'state' => 'ready', 'lease_owner_hash' => null, 'lease_expires_at' => null, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		return false !== $ok ? self::by_kol( $connection_key, $erp_kol_id ) : false;
	}
}
