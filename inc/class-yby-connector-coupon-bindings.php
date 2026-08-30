<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Durable global normalized-code reservation for Connector coupon mutations. */
class YBY_Connector_Coupon_Bindings {
	const LEASE_SECONDS = 300;

	public static function by_code( $normalized ) {
		global $wpdb; $table = YBY_Database::connector_coupon_bindings_table_name();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE normalized_code = %s LIMIT 1", $normalized ), ARRAY_A );
	}

	public static function reserve( $connection, $normalized, $exact, $owner ) {
		global $wpdb; $table = YBY_Database::connector_coupon_bindings_table_name(); $now = gmdate( 'Y-m-d H:i:s' ); $expires = gmdate( 'Y-m-d H:i:s', time() + self::LEASE_SECONDS );
		$row = self::by_code( $normalized );
		if ( $row && 'ready' === $row['state'] ) { return array( 'status' => 'ready', 'row' => $row ); }
		if ( $row && 'processing' === $row['state'] && ! empty( $row['lease_expires_at'] ) && strtotime( $row['lease_expires_at'] ) >= time() ) { return array( 'status' => 'busy', 'row' => $row ); }
		if ( $row ) {
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET exact_code = %s, lease_owner_hash = %s, lease_expires_at = %s, updated_at = %s, state = 'processing', coupon_id = NULL, affiliate_id = NULL, erp_kol_id = NULL WHERE id = %d AND state = 'processing' AND lease_expires_at < UTC_TIMESTAMP()", $exact, $owner, $expires, $now, (int) $row['id'] ) );
			return 1 === (int) $updated ? array( 'status' => 'acquired', 'row' => self::by_code( $normalized ), 'recovered' => true ) : array( 'status' => 'busy' );
		}
		$ok = $wpdb->insert( $table, array( 'connection_key' => $connection, 'normalized_code' => $normalized, 'exact_code' => $exact, 'state' => 'processing', 'lease_owner_hash' => $owner, 'lease_expires_at' => $expires, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		if ( false !== $ok ) { return array( 'status' => 'acquired', 'row' => self::by_code( $normalized ) ); }
		$row = self::by_code( $normalized );
		return $row && 'ready' === $row['state'] ? array( 'status' => 'ready', 'row' => $row ) : array( 'status' => 'busy', 'row' => $row );
	}

	public static function finalize( $connection, $normalized, $owner, $coupon, $affiliate, $kol ) {
		global $wpdb; $table = YBY_Database::connector_coupon_bindings_table_name();
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET coupon_id = %d, affiliate_id = %d, erp_kol_id = %d, state = 'ready', lease_owner_hash = NULL, lease_expires_at = NULL, updated_at = %s WHERE normalized_code = %s AND state = 'processing' AND lease_owner_hash = %s", $coupon, $affiliate, $kol, gmdate( 'Y-m-d H:i:s' ), $normalized, $owner ) );
		return 1 === (int) $updated ? self::by_code( $normalized ) : false;
	}

	public static function renew( $normalized, $owner_hash, $lease_seconds = 300 ) {
		global $wpdb; $table = YBY_Database::connector_coupon_bindings_table_name();
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 60, absint( $lease_seconds ) ) );
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_expires_at = %s, updated_at = %s WHERE normalized_code = %s AND state = 'processing' AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP()", $expires, gmdate( 'Y-m-d H:i:s' ), $normalized, $owner_hash ) );
		if ( 1 === (int) $updated ) { return true; }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT normalized_code, state, lease_owner_hash, lease_expires_at FROM {$table} WHERE normalized_code = %s AND state = 'processing' AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP() LIMIT 1", $normalized, $owner_hash ), ARRAY_A );
		return is_array( $row ) && (string) ( $row['normalized_code'] ?? '' ) === (string) $normalized && 'processing' === (string) ( $row['state'] ?? '' ) && (string) ( $row['lease_owner_hash'] ?? '' ) === (string) $owner_hash && ! empty( $row['lease_expires_at'] ) && strtotime( (string) $row['lease_expires_at'] . ' UTC' ) >= time();
	}

	public static function release( $connection, $normalized, $owner ) {
		global $wpdb; $table = YBY_Database::connector_coupon_bindings_table_name();
		return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE normalized_code = %s AND state = 'processing' AND lease_owner_hash = %s", $normalized, $owner ) );
	}
}
