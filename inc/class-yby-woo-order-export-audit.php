<?php
/** Bounded non-PII Woo Order Export audit metadata. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_Export_Audit {
	const OPTION_KEY = 'yby_woo_order_export_audit_v1';
	const LIMIT = 50;

	public static function summarize_filters( $filters ) {
		$filters = YBY_Woo_Order_Query_Adapter::sanitize_filters( $filters );
		return array(
			'date_from' => $filters['date_from'], 'date_to' => $filters['date_to'], 'statuses' => $filters['statuses'],
			'order_ids_count' => count( $filters['order_ids'] ), 'has_customer_email_filter' => '' !== $filters['customer_email'],
			'currency' => $filters['currency'], 'payment_method' => $filters['payment_method'],
		);
	}

	public static function record( $entry ) {
		$entry = is_array( $entry ) ? $entry : array();
		$record = array(
			'export_id' => sanitize_text_field( $entry['export_id'] ?? '' ),
			'timestamp' => sanitize_text_field( $entry['timestamp'] ?? gmdate( 'c' ) ),
			'admin_user_id' => absint( $entry['admin_user_id'] ?? 0 ),
			'preset' => sanitize_key( $entry['preset'] ?? '' ),
			'filters' => is_array( $entry['filters'] ?? null ) ? $entry['filters'] : array(),
			'order_count' => absint( $entry['order_count'] ?? 0 ), 'row_count' => absint( $entry['row_count'] ?? 0 ),
			'duration_ms' => absint( $entry['duration_ms'] ?? 0 ),
			'status' => in_array( $entry['status'] ?? '', array( 'success', 'failure' ), true ) ? $entry['status'] : 'failure',
		);
		$existing = get_option( self::OPTION_KEY, array() );
		$existing = is_array( $existing ) ? $existing : array();
		array_unshift( $existing, $record );
		return update_option( self::OPTION_KEY, array_slice( $existing, 0, self::LIMIT ), false );
	}
}
