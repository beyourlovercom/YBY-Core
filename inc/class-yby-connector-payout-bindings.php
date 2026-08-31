<?php
/** Durable payout identity and referral claims for the ERP Connector. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Connector_Payout_Bindings {
	const LEASE_SECONDS = 300;

	public static function by_request( $connection, $request_id ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE connection_key = %s AND erp_payout_request_id = %s LIMIT 1", (string) $connection, (string) $request_id ), ARRAY_A );
	}

	public static function reserve( $connection, $request_id, $fingerprint, $owner ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name(); $now = gmdate( 'Y-m-d H:i:s' ); $expires = gmdate( 'Y-m-d H:i:s', time() + self::LEASE_SECONDS );
		$row = self::by_request( $connection, $request_id );
		if ( $row ) {
			if ( (string) $row['request_fingerprint'] !== (string) $fingerprint ) { return new WP_Error( 'PAYOUT_IDEMPOTENCY_CONFLICT', 'ERP payout request identity conflicts with its original canonical input.', array( 'status' => 409, 'retryable' => false ) ); }
			if ( ! empty( $row['payout_id'] ) ) {
				if ( 'ready' === (string) $row['state'] ) { return array( 'status' => 'ready', 'row' => $row ); }
				if ( ! empty( $row['lease_expires_at'] ) && strtotime( $row['lease_expires_at'] . ' UTC' ) >= time() && (string) $row['lease_owner_hash'] !== (string) $owner ) { return array( 'status' => 'busy', 'row' => $row ); }
				$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_owner_hash = %s, lease_expires_at = %s, updated_at = %s WHERE id = %d AND state = 'provider_created' AND (lease_expires_at < UTC_TIMESTAMP() OR lease_expires_at IS NULL)", $owner, $expires, $now, (int) $row['id'] ) );
				$row = self::by_request( $connection, $request_id );
				if ( ! $row || ( 0 === (int) $updated && (string) $row['lease_owner_hash'] !== (string) $owner ) ) { return array( 'status' => 'busy', 'row' => $row ); }
				return array( 'status' => 'provider_created', 'row' => $row, 'recovered' => true );
			}
			if ( 'processing' !== (string) $row['state'] ) { return new WP_Error( 'PAYOUT_IN_PROGRESS', 'Payout reservation is not safely actionable.', array( 'status' => 503, 'retryable' => true ) ); }
			if ( ! empty( $row['lease_expires_at'] ) && strtotime( $row['lease_expires_at'] . ' UTC' ) >= time() && (string) $row['lease_owner_hash'] !== (string) $owner ) { return array( 'status' => 'busy', 'row' => $row ); }
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_owner_hash = %s, lease_expires_at = %s, updated_at = %s WHERE id = %d AND state = 'processing' AND (lease_expires_at < UTC_TIMESTAMP() OR lease_expires_at IS NULL)", $owner, $expires, $now, (int) $row['id'] ) );
			$row = self::by_request( $connection, $request_id );
			if ( ! $row || ( 0 === (int) $updated && (string) $row['lease_owner_hash'] !== (string) $owner ) ) { return array( 'status' => 'busy', 'row' => $row ); }
			return array( 'status' => 'acquired', 'row' => $row, 'recovered' => true );
		}
		$ok = $wpdb->insert( $table, array( 'connection_key' => (string) $connection, 'erp_payout_request_id' => (string) $request_id, 'request_fingerprint' => (string) $fingerprint, 'state' => 'processing', 'payout_id' => null, 'lease_owner_hash' => (string) $owner, 'lease_expires_at' => $expires, 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) );
		if ( false === $ok ) { $row = self::by_request( $connection, $request_id ); return $row ? self::reserve( $connection, $request_id, $fingerprint, $owner ) : new WP_Error( 'PAYOUT_BINDING_UNAVAILABLE', 'Could not acquire payout identity.', array( 'status' => 503, 'retryable' => true ) ); }
		return array( 'status' => 'acquired', 'row' => self::by_request( $connection, $request_id ) );
	}

	public static function renew( $connection, $request_id, $owner ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name(); $expires = gmdate( 'Y-m-d H:i:s', time() + self::LEASE_SECONDS ); $now = gmdate( 'Y-m-d H:i:s' );
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET lease_expires_at = %s, updated_at = %s WHERE connection_key = %s AND erp_payout_request_id = %s AND state IN ('processing','provider_created') AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP()", $expires, $now, $connection, $request_id, $owner ) );
		$row = self::by_request( $connection, $request_id );
		return is_array( $row ) && in_array( (string) $row['state'], array( 'processing', 'provider_created' ), true ) && (string) $row['lease_owner_hash'] === (string) $owner && ! empty( $row['lease_expires_at'] ) && strtotime( $row['lease_expires_at'] . ' UTC' ) >= time();
	}

	public static function set_payout( $connection, $request_id, $owner, $payout_id ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name();
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET payout_id = %d, state = 'provider_created', lease_expires_at = lease_expires_at, updated_at = %s WHERE connection_key = %s AND erp_payout_request_id = %s AND state = 'processing' AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP()", absint( $payout_id ), gmdate( 'Y-m-d H:i:s' ), $connection, $request_id, $owner ) );
		$row = self::by_request( $connection, $request_id );
		return is_array( $row ) && (int) ( $row['payout_id'] ?? 0 ) === absint( $payout_id ) && in_array( (string) $row['state'], array( 'provider_created', 'ready' ), true );
	}

	public static function finalize( $connection, $request_id, $owner, $payout_id ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET state = 'ready', lease_owner_hash = NULL, lease_expires_at = NULL, updated_at = %s WHERE connection_key = %s AND erp_payout_request_id = %s AND state = 'provider_created' AND payout_id = %d AND lease_owner_hash = %s AND lease_expires_at >= UTC_TIMESTAMP()", gmdate( 'Y-m-d H:i:s' ), $connection, $request_id, absint( $payout_id ), $owner ) );
		$row = self::by_request( $connection, $request_id );
		return is_array( $row ) && 'ready' === (string) $row['state'] && (int) $row['payout_id'] === absint( $payout_id );
	}

	public static function claim( $connection, $request_id, $referral_id ) {
		global $wpdb; $table = YBY_Database::connector_payout_claims_table_name(); $now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( $table, array( 'referral_id' => absint( $referral_id ), 'connection_key' => (string) $connection, 'erp_payout_request_id' => (string) $request_id, 'created_at' => $now, 'updated_at' => $now ), array( '%d', '%s', '%s', '%s', '%s' ) );
		if ( false !== $ok ) { return true; }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT connection_key, erp_payout_request_id FROM {$table} WHERE referral_id = %d LIMIT 1", absint( $referral_id ) ), ARRAY_A );
		return is_array( $row ) && (string) $row['connection_key'] === (string) $connection && (string) $row['erp_payout_request_id'] === (string) $request_id;
	}

	public static function release_claims( $connection, $request_id, $referral_ids ) {
		global $wpdb; $table = YBY_Database::connector_payout_claims_table_name();
		foreach ( (array) $referral_ids as $referral_id ) { $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE referral_id = %d AND connection_key = %s AND erp_payout_request_id = %s", absint( $referral_id ), (string) $connection, (string) $request_id ) ); }
	}

	public static function release_processing( $connection, $request_id, $owner ) {
		global $wpdb; $table = YBY_Database::connector_payout_bindings_table_name();
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE connection_key = %s AND erp_payout_request_id = %s AND state = 'processing' AND lease_owner_hash = %s AND payout_id IS NULL", (string) $connection, (string) $request_id, (string) $owner ) );
	}
}
