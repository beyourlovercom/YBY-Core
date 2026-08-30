<?php
/** Durable mutation idempotency storage for the ERP Connector. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Connector_Idempotency {

	const RETENTION_DAYS = 30;

	public static function fingerprint( $request_identity ) {
		$canonical = self::canonicalize( $request_identity );
		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $canonical ) : json_encode( $canonical );
		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	public static function key_hash( $idempotency_key ) {
		return hash( 'sha256', (string) $idempotency_key );
	}

	public static function begin( $connection_key, $action_key, $idempotency_key, $request_identity, $retention_days = self::RETENTION_DAYS ) {
		global $wpdb;
		if ( ! self::valid_scope_text( $connection_key, 128 ) || ! self::valid_scope_text( $action_key, 128 ) || ! self::valid_scope_text( $idempotency_key, 255 ) ) {
			return self::error( 'IDEMPOTENCY_INVALID', 'Mutation identity is invalid.', false );
		}
		$fingerprint = self::fingerprint( $request_identity );
		$key_hash    = self::key_hash( $idempotency_key );
		$identity    = hash( 'sha256', (string) $connection_key . "\n" . (string) $action_key . "\n" . $key_hash );
		$table       = YBY_Database::connector_idempotency_table_name();
		$now         = gmdate( 'Y-m-d H:i:s' );
		$expiry      = gmdate( 'Y-m-d H:i:s', time() + max( 1, absint( $retention_days ) ) * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
		$inserted    = $wpdb->insert( $table, array( 'connection_key' => self::bounded_text( $connection_key, 128 ), 'action_key' => self::bounded_text( $action_key, 128 ), 'idempotency_key_hash' => $key_hash, 'mutation_identity_hash' => $identity, 'request_fingerprint' => $fingerprint, 'state' => 'processing', 'created_at' => $now, 'updated_at' => $now, 'expires_at' => $expiry ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		if ( false !== $inserted ) { return array( 'status' => 'acquired', 'id' => (int) $wpdb->insert_id, 'fingerprint' => $fingerprint ); }

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE mutation_identity_hash = %s LIMIT 1", $identity ), ARRAY_A );
		if ( ! is_array( $row ) ) { return self::error( 'IDEMPOTENCY_UNAVAILABLE', 'Could not safely acquire mutation identity.', true ); }
		if ( ! hash_equals( (string) $row['request_fingerprint'], $fingerprint ) ) { return self::error( 'IDEMPOTENCY_CONFLICT', 'Idempotency key was used for a different request.', false ); }
		if ( 'succeeded' === $row['state'] ) { return array( 'status' => 'replay', 'record' => self::decode_result( $row ), 'id' => (int) $row['id'], 'fingerprint' => $fingerprint ); }
		if ( 'failed' === $row['state'] && ! empty( $row['retryable'] ) ) {
			$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET state = 'processing', updated_at = %s WHERE id = %d AND state = 'failed' AND retryable = 1", $now, (int) $row['id'] ) );
			if ( 1 === (int) $updated ) { return array( 'status' => 'acquired', 'id' => (int) $row['id'], 'fingerprint' => $fingerprint, 'retry' => true ); }
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $row['id'] ), ARRAY_A );
			if ( is_array( $row ) && 'succeeded' === $row['state'] ) { return array( 'status' => 'replay', 'record' => self::decode_result( $row ), 'id' => (int) $row['id'], 'fingerprint' => $fingerprint ); }
		}
		if ( 'failed' === $row['state'] ) { return array( 'status' => 'failed', 'record' => self::decode_result( $row ), 'id' => (int) $row['id'], 'fingerprint' => $fingerprint ); }
		return self::error( 'IDEMPOTENCY_IN_PROGRESS', 'An identical mutation is already processing.', true, array( 'id' => (int) $row['id'] ) );
	}

	public static function succeed( $id, $result ) { return self::complete( $id, 'succeeded', $result, '', false ); }
	public static function fail( $id, $failure_code, $retryable = false, $result = array() ) { return self::complete( $id, 'failed', $result, $failure_code, $retryable ); }

	public static function cleanup( $limit = 100 ) {
		global $wpdb;
		$limit = min( 1000, max( 1, absint( $limit ) ) );
		$table = YBY_Database::connector_idempotency_table_name();
		return (int) $wpdb->query( "DELETE FROM {$table} WHERE expires_at IS NOT NULL AND expires_at < UTC_TIMESTAMP() LIMIT {$limit}" );
	}

	private static function complete( $id, $state, $result, $failure_code, $retryable ) {
		global $wpdb;
		$encoded = self::safe_json( $result );
		if ( false === $encoded ) { return self::error( 'IDEMPOTENCY_RESULT_INVALID', 'Provider result was not safe to persist.', false ); }
		$table = YBY_Database::connector_idempotency_table_name();
		$updated = $wpdb->update( $table, array( 'state' => $state, 'safe_result' => $encoded, 'failure_code' => self::bounded_text( $failure_code, 80 ), 'retryable' => $retryable ? 1 : 0, 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => absint( $id ), 'state' => 'processing' ), array( '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%s' ) );
		return 1 === (int) $updated;
	}

	private static function decode_result( $row ) { $result = json_decode( (string) ( $row['safe_result'] ?? '' ), true ); return array( 'state' => $row['state'], 'result' => is_array( $result ) ? $result : array(), 'failure_code' => (string) ( $row['failure_code'] ?? '' ), 'retryable' => ! empty( $row['retryable'] ) ); }
	private static function error( $code, $message, $retryable, $data = array() ) { return new WP_Error( $code, $message, array_merge( array( 'retryable' => $retryable ), $data ) ); }
	private static function bounded_text( $value, $length ) { return substr( is_scalar( $value ) ? (string) $value : '', 0, $length ); }
	private static function canonicalize( $value ) {
		if ( is_array( $value ) ) {
			if ( self::is_assoc( $value ) ) { ksort( $value ); }
			foreach ( $value as $key => $item ) { $value[ $key ] = self::canonicalize( $item ); }
			return $value;
		}
		if ( is_object( $value ) ) { return self::canonicalize( get_object_vars( $value ) ); }
		if ( is_float( $value ) ) { return sprintf( '%.14F', $value ); }
		return is_scalar( $value ) || null === $value ? $value : null;
	}
	private static function is_assoc( $value ) { return array_keys( $value ) !== range( 0, count( $value ) - 1 ); }
	private static function safe_json( $value ) { $safe = self::safe_value( $value, 0 ); $json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $safe ) : json_encode( $safe ); return is_string( $json ) && strlen( $json ) <= 4096 ? $json : false; }
	private static function safe_value( $value, $depth ) {
		if ( $depth > 3 ) { return null; }
		if ( is_object( $value ) ) { $value = get_object_vars( $value ); }
		if ( is_array( $value ) ) {
			if ( ! self::is_assoc( $value ) ) {
				$out = array(); foreach ( array_slice( $value, 0, 50 ) as $item ) { $out[] = self::safe_value( $item, $depth + 1 ); } return $out;
			}
			$out = array(); $count = 0;
			foreach ( $value as $key => $item ) {
				$name = strtolower( (string) $key );
				if ( ! self::allowed_result_key( $name ) || self::forbidden_key( $name ) || $count >= 20 ) { continue; }
				$out[ self::bounded_text( $key, 64 ) ] = self::safe_value( $item, $depth + 1 ); ++$count;
			}
			return $out;
		}
		if ( is_scalar( $value ) || null === $value ) { return is_string( $value ) ? substr( $value, 0, 128 ) : $value; }
		return null;
	}
	private static function allowed_result_key( $key ) { return in_array( $key, array( 'id', 'user_id', 'wp_user_id', 'affiliate_id', 'coupon_id', 'linked_affiliate_id', 'referral_id', 'referral_ids', 'payout_id', 'status', 'affiliate_status', 'success', 'retryable', 'exists', 'code', 'normalized_code', 'amount', 'currency', 'payout_method', 'created_user', 'created_affiliate' ), true ); }
	private static function forbidden_key( $key ) { return (bool) preg_match( '/secret|password|authorization|token|credential|header|payload|email|display_name|payment/', $key ); }
	private static function valid_scope_text( $value, $max_length ) { return is_scalar( $value ) && '' !== trim( (string) $value ) && strlen( (string) $value ) <= $max_length; }
}
