<?php
/** Bounded Woo order CSV streaming engine. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_CSV_Streamer {
	public function stream( $handle, $preset_id, $filters, $settings, $adapter = null ) {
		if ( ! is_resource( $handle ) ) { throw new InvalidArgumentException( 'CSV stream handle is required.' ); }
		$preset_id = sanitize_key( (string) $preset_id );
		$preset = YBY_Woo_Order_Export_Presets::get( $preset_id );
		if ( empty( $preset ) ) { $preset_id = 'default'; $preset = YBY_Woo_Order_Export_Presets::get( 'default' ); }
		$catalog = YBY_Woo_Order_Export_Presets::column_catalog();
		$columns = array_values( array_filter( (array) ( $preset['columns'] ?? array() ), static function ( $key ) use ( $catalog ) { return isset( $catalog[ $key ] ); } ) );
		if ( empty( $columns ) ) { throw new RuntimeException( 'Export preset has no valid columns.' ); }
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), YBY_Woo_Order_Export_Module::defaults() );
		if ( ! empty( $settings['bom'] ) ) { fwrite( $handle, "\xEF\xBB\xBF" ); }
		fputcsv( $handle, array_map( static function ( $key ) use ( $catalog ) { return $catalog[ $key ]['label']; }, $columns ) );
		$adapter = $adapter ?: new YBY_Woo_Order_Query_Adapter();
		$order_count = 0; $row_count = 0;
		foreach ( $adapter->iterate_orders( $filters, $settings['batch_size'] ) as $order ) {
			$order_count++;
			$items = $this->order_items( $order );
			if ( empty( $items ) ) { $items = array( null ); }
			foreach ( $items as $item ) {
				$row = $this->build_row( $order, $item );
				if ( function_exists( 'apply_filters' ) ) { $row = apply_filters( 'andy_core_order_export_row', $row, $order, $item, $preset_id ); }
				$out = array();
				foreach ( $columns as $key ) { $out[] = $this->csv_cell( $row[ $key ] ?? '', $catalog[ $key ]['type'] ?? 'text' ); }
				fputcsv( $handle, $out );
				$row_count++;
				if ( 0 === $row_count % 100 && function_exists( 'flush' ) ) { flush(); }
			}
		}
		return array( 'preset' => $preset_id, 'order_count' => $order_count, 'row_count' => $row_count );
	}

	protected function order_items( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) { return array(); }
		$items = $order->get_items( 'line_item' );
		return is_array( $items ) ? array_values( $items ) : array();
	}

	protected function call( $object, $method, $default = '' ) {
		return is_object( $object ) && method_exists( $object, $method ) ? $object->{$method}() : $default;
	}

	protected function date_value( $date ) {
		return is_object( $date ) && method_exists( $date, 'date' ) ? $date->date( 'Y-m-d H:i:s' ) : '';
	}

	protected function build_row( $order, $item ) {
		$product = $item && method_exists( $item, 'get_product' ) ? $item->get_product() : null;
		$shipping_phone = method_exists( $order, 'get_shipping_phone' ) ? $order->get_shipping_phone() : ( method_exists( $order, 'get_meta' ) ? $order->get_meta( '_shipping_phone', true ) : '' );
		return array(
			'order_id' => $this->call( $order, 'get_id', 0 ),
			'order_number' => $this->call( $order, 'get_order_number' ),
			'order_date' => $this->date_value( $this->call( $order, 'get_date_created', null ) ),
			'paid_date' => $this->date_value( $this->call( $order, 'get_date_paid', null ) ),
			'status' => $this->call( $order, 'get_status' ), 'currency' => $this->call( $order, 'get_currency' ),
			'subtotal' => $this->call( $order, 'get_subtotal', 0 ), 'discount' => $this->call( $order, 'get_discount_total', 0 ),
			'shipping' => $this->call( $order, 'get_shipping_total', 0 ), 'tax' => $this->call( $order, 'get_total_tax', 0 ), 'total' => $this->call( $order, 'get_total', 0 ),
			'payment_method' => $this->call( $order, 'get_payment_method' ), 'transaction_id' => $this->call( $order, 'get_transaction_id' ),
			'customer_id' => $this->call( $order, 'get_customer_id', 0 ), 'customer_email' => $this->call( $order, 'get_billing_email' ),
			'billing_first_name' => $this->call( $order, 'get_billing_first_name' ), 'billing_last_name' => $this->call( $order, 'get_billing_last_name' ), 'billing_company' => $this->call( $order, 'get_billing_company' ), 'billing_email' => $this->call( $order, 'get_billing_email' ), 'billing_phone' => $this->call( $order, 'get_billing_phone' ), 'billing_address_1' => $this->call( $order, 'get_billing_address_1' ), 'billing_address_2' => $this->call( $order, 'get_billing_address_2' ), 'billing_city' => $this->call( $order, 'get_billing_city' ), 'billing_state' => $this->call( $order, 'get_billing_state' ), 'billing_postcode' => $this->call( $order, 'get_billing_postcode' ), 'billing_country' => $this->call( $order, 'get_billing_country' ),
			'shipping_first_name' => $this->call( $order, 'get_shipping_first_name' ), 'shipping_last_name' => $this->call( $order, 'get_shipping_last_name' ), 'shipping_company' => $this->call( $order, 'get_shipping_company' ), 'shipping_phone' => $shipping_phone, 'shipping_address_1' => $this->call( $order, 'get_shipping_address_1' ), 'shipping_address_2' => $this->call( $order, 'get_shipping_address_2' ), 'shipping_city' => $this->call( $order, 'get_shipping_city' ), 'shipping_state' => $this->call( $order, 'get_shipping_state' ), 'shipping_postcode' => $this->call( $order, 'get_shipping_postcode' ), 'shipping_country' => $this->call( $order, 'get_shipping_country' ),
			'product_id' => $item ? $this->call( $item, 'get_product_id', 0 ) : '', 'variation_id' => $item ? $this->call( $item, 'get_variation_id', 0 ) : '', 'sku' => $product ? $this->call( $product, 'get_sku' ) : '', 'product_name' => $item ? $this->call( $item, 'get_name' ) : '', 'quantity' => $item ? $this->call( $item, 'get_quantity', 0 ) : '', 'line_subtotal' => $item ? $this->call( $item, 'get_subtotal', 0 ) : '', 'line_total' => $item ? $this->call( $item, 'get_total', 0 ) : '',
		);
	}

	protected function csv_cell( $value, $type ) {
		if ( is_bool( $value ) ) { $value = $value ? '1' : '0'; }
		if ( ! is_scalar( $value ) && null !== $value ) { $value = ''; }
		$value = null === $value ? '' : (string) $value;
		$value = str_replace( "\0", '', $value );
		if ( 'number' !== $type ) {
			$probe = ltrim( $value );
			if ( '' !== $probe && in_array( $probe[0], array( '=', '+', '-', '@' ), true ) ) { $value = "'" . $value; }
		}
		return $value;
	}
}
