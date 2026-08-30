<?php
/** Safe durable mutation audit storage for the ERP Connector. */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Connector_Audit {

	public static function record( $fields ) {
		global $wpdb;
		$fields = is_array( $fields ) ? $fields : array();
		$targets = self::safe_targets( $fields['target_provider_ids'] ?? array() );
		$data = array( 'request_id' => self::text( $fields['request_id'] ?? '', 80 ), 'key_id' => self::text( $fields['key_id'] ?? '', 64 ), 'connection_key' => self::text( $fields['connection_key'] ?? '', 128 ), 'endpoint_action' => self::text( $fields['endpoint_action'] ?? '', 160 ), 'idempotency_key_hash' => self::hash_reference( $fields['idempotency_key_hash'] ?? '' ), 'actor' => 'ERP trusted system', 'target_provider_ids' => function_exists( 'wp_json_encode' ) ? wp_json_encode( $targets ) : json_encode( $targets ), 'result_code' => self::text( $fields['result_code'] ?? '', 80 ), 'success' => ! empty( $fields['success'] ) ? 1 : 0, 'retryable' => ! empty( $fields['retryable'] ) ? 1 : 0, 'created_at' => gmdate( 'Y-m-d H:i:s' ) );
		return false !== $wpdb->insert( YBY_Database::connector_audit_table_name(), $data, array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ) ) ? (int) $wpdb->insert_id : false;
	}

	public static function find( $request_id, $limit = 50 ) {
		global $wpdb;
		$limit = min( 100, max( 1, absint( $limit ) ) );
		$table = YBY_Database::connector_audit_table_name();
		return $wpdb->get_results( $wpdb->prepare( "SELECT id, request_id, key_id, connection_key, endpoint_action, idempotency_key_hash, actor, target_provider_ids, result_code, success, retryable, created_at FROM {$table} WHERE request_id = %s ORDER BY id DESC LIMIT {$limit}", self::text( $request_id, 80 ) ), ARRAY_A );
	}

	private static function hash_reference( $value ) { $value = (string) $value; return preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : ''; }
	private static function text( $value, $length ) { return substr( is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '', 0, $length ); }
	private static function safe_targets( $targets ) { $out = array(); $allowed = array( 'user_id', 'wp_user_id', 'affiliate_id', 'coupon_id', 'referral_id', 'referral_ids', 'payout_id' ); foreach ( is_array( $targets ) ? $targets : array() as $provider => $ids ) { $provider = strtolower( (string) $provider ); if ( ! in_array( $provider, $allowed, true ) ) { continue; } $clean = array(); foreach ( is_array( $ids ) ? $ids : array( $ids ) as $id ) { if ( is_scalar( $id ) && '' !== (string) $id ) { $clean[] = substr( sanitize_text_field( (string) $id ), 0, 64 ); } } if ( $clean ) { $out[ $provider ] = array_slice( $clean, 0, 20 ); } } return $out; }
}
