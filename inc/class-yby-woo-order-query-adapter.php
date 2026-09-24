<?php
/** WooCommerce order query adapter. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_Query_Adapter {
	const MAX_BATCH_SIZE = 500;
	const MIN_BATCH_SIZE = 25;

	public static function sanitize_filters( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$date_from = self::sanitize_date( $raw['date_from'] ?? '' );
		$date_to = self::sanitize_date( $raw['date_to'] ?? '' );
		if ( $date_from && $date_to && $date_from > $date_to ) { $tmp = $date_from; $date_from = $date_to; $date_to = $tmp; }

		$statuses = isset( $raw['statuses'] ) && is_array( $raw['statuses'] ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw['statuses'] ) ) ) ) : array();
		$order_ids_source = $raw['order_ids'] ?? array();
		$order_ids_parts = is_array( $order_ids_source ) ? $order_ids_source : preg_split( '/[\s,;]+/', (string) $order_ids_source, -1, PREG_SPLIT_NO_EMPTY );
		$order_ids = array();
		foreach ( $order_ids_parts as $id ) { $id = absint( $id ); if ( $id ) { $order_ids[] = $id; } }
		$order_ids = array_values( array_unique( $order_ids ) );

		$currency = isset( $raw['currency'] ) ? strtoupper( sanitize_text_field( $raw['currency'] ) ) : '';
		if ( $currency && ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { $currency = ''; }

		return array(
			'date_from' => $date_from,
			'date_to' => $date_to,
			'statuses' => $statuses,
			'order_ids' => $order_ids,
			'customer_email' => isset( $raw['customer_email'] ) ? sanitize_email( $raw['customer_email'] ) : '',
			'currency' => $currency,
			'payment_method' => isset( $raw['payment_method'] ) ? sanitize_key( $raw['payment_method'] ) : '',
		);
	}

	protected static function sanitize_date( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) { return ''; }
		$dt = DateTime::createFromFormat( '!Y-m-d', $value );
		return $dt && $dt->format( 'Y-m-d' ) === $value ? $value : '';
	}

	public static function normalize_batch_size( $value ) {
		$value = absint( $value );
		return min( self::MAX_BATCH_SIZE, max( self::MIN_BATCH_SIZE, $value ?: 200 ) );
	}

	public static function build_query_args( $filters, $page, $limit ) {
		$filters = self::sanitize_filters( $filters );
		$args = array(
			'limit' => self::normalize_batch_size( $limit ),
			'page' => max( 1, absint( $page ) ),
			'paginate' => true,
			'return' => 'objects',
			'orderby' => 'date',
			'order' => 'ASC',
		);
		if ( $filters['statuses'] ) { $args['status'] = $filters['statuses']; }
		if ( $filters['order_ids'] ) { $args['post__in'] = $filters['order_ids']; }
		if ( $filters['date_from'] && $filters['date_to'] ) { $args['date_created'] = $filters['date_from'] . '...' . $filters['date_to']; }
		elseif ( $filters['date_from'] ) { $args['date_created'] = '>=' . $filters['date_from']; }
		elseif ( $filters['date_to'] ) { $args['date_created'] = '<=' . $filters['date_to']; }
		if ( $filters['customer_email'] ) { $args['billing_email'] = $filters['customer_email']; }
		if ( $filters['currency'] ) { $args['currency'] = $filters['currency']; }
		if ( $filters['payment_method'] ) { $args['payment_method'] = $filters['payment_method']; }
		return function_exists( 'apply_filters' ) ? apply_filters( 'andy_core_order_export_query_args', $args, $filters ) : $args;
	}

	public function iterate_orders( $filters, $batch_size = 200 ) {
		if ( ! function_exists( 'wc_get_orders' ) ) { return; }
		$page = 1;
		$batch_size = self::normalize_batch_size( $batch_size );
		do {
			$result = wc_get_orders( self::build_query_args( $filters, $page, $batch_size ) );
			$orders = is_object( $result ) && isset( $result->orders ) && is_array( $result->orders ) ? $result->orders : ( is_array( $result ) ? $result : array() );
			foreach ( $orders as $order ) { yield $order; }
			$max_pages = is_object( $result ) && isset( $result->max_num_pages ) ? max( 1, absint( $result->max_num_pages ) ) : 1;
			$page++;
		} while ( $orders && $page <= $max_pages );
	}
}
