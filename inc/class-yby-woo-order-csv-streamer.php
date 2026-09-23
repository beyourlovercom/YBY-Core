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
		$adapter = $adapter ?: new YBY_Woo_Order_Query_Adapter();
		$row_mode = isset( $preset['row_mode'] ) ? $preset['row_mode'] : 'line_item';
		$order_mode = 'order_row' === $row_mode;
		$dynamic_columns = array();
		if ( 'order_row' === $row_mode ) {
			$max_items = 0;
			foreach ( $adapter->iterate_orders( $filters, $settings['batch_size'] ) as $order ) { $max_items = max( $max_items, count( $this->order_items( $order ) ) ); }
			for ( $index = 1; $index <= $max_items; $index++ ) {
				$dynamic_columns[] = array( 'key' => 'line_item_' . $index, 'label' => 'line_item_' . $index, 'type' => 'text' );
				foreach ( array( 'name', 'product_id', 'sku', 'quantity', 'total', 'subtotal' ) as $suffix ) {
					$label = 'name' === $suffix ? 'Name' : ( 'product_id' === $suffix ? 'id' : ucfirst( $suffix ) );
					$dynamic_columns[] = array( 'key' => 'line_item_' . $index . '_' . $suffix, 'label' => 'Product Item ' . $index . ' ' . $label, 'type' => 'text' );
				}
			}
		}
		if ( ! empty( $settings['bom'] ) ) { fwrite( $handle, "\xEF\xBB\xBF" ); }
		$labels = (array) ( $preset['header_labels'] ?? array() );
		$headers = array_map( static function ( $key ) use ( $catalog, $labels ) { return $labels[ $key ] ?? $catalog[ $key ]['label']; }, $columns );
		foreach ( $dynamic_columns as $dynamic ) { $headers[] = $dynamic['label']; }
		fputcsv( $handle, $headers );
		$order_count = 0; $row_count = 0;
		foreach ( $adapter->iterate_orders( $filters, $settings['batch_size'] ) as $order ) {
			$order_count++;
			if ( $order_mode ) {
				$row = $this->build_row( $order, null, 'order_row' );
				foreach ( $this->order_items( $order ) as $index => $item ) { $this->add_dynamic_item_values( $row, $index + 1, $item ); }
				if ( function_exists( 'apply_filters' ) ) { $row = apply_filters( 'andy_core_order_export_row', $row, $order, null, $preset_id ); }
				$out = array();
				foreach ( $columns as $key ) { $out[] = $this->csv_cell( $row[ $key ] ?? '', $catalog[ $key ]['type'] ?? 'text' ); }
				foreach ( $dynamic_columns as $dynamic ) { $out[] = $this->csv_cell( $row[ $dynamic['key'] ] ?? '', $dynamic['type'] ); }
				fputcsv( $handle, $out ); $row_count++;
				if ( 0 === $row_count % 100 && function_exists( 'flush' ) ) { flush(); }
				continue;
			}
			$items = $this->order_items( $order );
			if ( empty( $items ) ) { $items = array( null ); }
			foreach ( $items as $item ) {
				$row = $this->build_row( $order, $item, 'line_item' );
				if ( function_exists( 'apply_filters' ) ) { $row = apply_filters( 'andy_core_order_export_row', $row, $order, $item, $preset_id ); }
				$out = array();
				foreach ( $columns as $key ) { $out[] = $this->csv_cell( $row[ $key ] ?? '', $catalog[ $key ]['type'] ?? 'text' ); }
				fputcsv( $handle, $out ); $row_count++;
				if ( 0 === $row_count % 100 && function_exists( 'flush' ) ) { flush(); }
			}
		}
		return array( 'preset' => $preset_id, 'order_count' => $order_count, 'row_count' => $row_count );
	}

	protected function order_items( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) { return array(); }
		try { $items = $order->get_items( 'line_item' ); } catch ( Throwable $e ) { return array(); }
		return is_array( $items ) ? array_values( $items ) : array();
	}

	protected function call( $object, $method, $default = '' ) {
		if ( ! is_object( $object ) || ! method_exists( $object, $method ) ) { return $default; }
		try { return $object->{$method}(); } catch ( Throwable $e ) { return $default; }
	}
	protected function date_value( $date ) { return is_object( $date ) && method_exists( $date, 'date' ) ? $date->date( 'Y-m-d H:i:s' ) : ''; }
	protected function meta( $order, $key ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) { return ''; }
		try { return $order->get_meta( $key, true ); } catch ( Throwable $e ) { return ''; }
	}

	protected function order_collection( $order, $type ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) { return array(); }
		try { $items = $order->get_items( $type ); } catch ( Throwable $e ) { return array(); }
		return is_array( $items ) ? $items : array();
	}

	protected function stable_normalize( $value ) {
		if ( is_object( $value ) ) { $value = method_exists( $value, 'get_data' ) ? $value->get_data() : array(); }
		if ( is_array( $value ) ) {
			if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) { ksort( $value ); }
			foreach ( $value as $key => $child ) { $value[ $key ] = $this->stable_normalize( $child ); }
		}
		return $value;
	}

	protected function stable_json( $value ) {
		$value = $this->stable_normalize( $value );
		return function_exists( 'wp_json_encode' ) ? wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	protected function collection_value( $order, $type ) { return $this->stable_json( array_slice( $this->order_collection( $order, $type ), 0, 100 ) ); }
	protected function refund_value( $order ) {
		$refunds = $this->call( $order, 'get_refunds', array() );
		return $this->stable_json( array_slice( is_array( $refunds ) ? $refunds : array(), 0, 100 ) );
	}
	protected function fee_total( $order ) {
		$total = 0;
		foreach ( array_slice( $this->order_collection( $order, 'fee' ), 0, 100 ) as $fee ) { $total += (float) $this->call( $fee, 'get_total', 0 ); }
		return $total;
	}
	protected function fee_tax_total( $order ) {
		$total = 0;
		foreach ( array_slice( $this->order_collection( $order, 'fee' ), 0, 100 ) as $fee ) { $total += (float) $this->call( $fee, 'get_total_tax', 0 ); }
		return $total;
	}
	protected function registered_customer_email( $customer_id ) {
		if ( ! $customer_id || ! function_exists( 'get_userdata' ) ) { return ''; }
		try { $user = get_userdata( $customer_id ); } catch ( Throwable $e ) { return ''; }
		return $user && isset( $user->user_email ) ? (string) $user->user_email : '';
	}

	protected function order_notes_value( $order ) {
		if ( ! function_exists( 'wc_get_order_notes' ) || ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) { return ''; }
		try { $notes = wc_get_order_notes( array( 'order_id' => (int) $order->get_id(), 'order_by' => 'date_created', 'order' => 'ASC' ) ); } catch ( Throwable $e ) { return ''; }
		if ( ! is_array( $notes ) ) { return ''; }
		$serialized = array();
		foreach ( array_slice( $notes, 0, 100 ) as $note ) {
			if ( ! is_object( $note ) ) { continue; }
			$content = isset( $note->content ) ? $note->content : $this->call( $note, 'get_content', '' );
			$date_raw = isset( $note->date_created ) ? $note->date_created : $this->call( $note, 'get_date_created', null );
			$date = is_string( $date_raw ) ? $date_raw : $this->date_value( $date_raw );
			$customer_raw = isset( $note->customer_note ) ? $note->customer_note : $this->call( $note, 'is_customer_note', false );
			$customer = $customer_raw ? '1' : '0';
			$added_by = isset( $note->added_by ) ? $note->added_by : $this->call( $note, 'get_added_by', '' );
			$flatten = static function ( $value ) { return preg_replace( '/\s+/', ' ', trim( (string) $value ) ); };
			$serialized[] = 'content:' . $flatten( $content ) . '|date:' . $flatten( $date ) . '|customer:' . $customer . '|added_by:' . $flatten( $added_by );
		}
		return implode( '||', $serialized );
	}

	protected function build_row( $order, $item, $row_mode = 'line_item' ) {
		$product = $item && method_exists( $item, 'get_product' ) ? $item->get_product() : null;
		$customer_id = 'order_row' === $row_mode ? $this->call( $order, 'get_user_id', 0 ) : $this->call( $order, 'get_customer_id', 0 );
		$shipping_phone = $this->call( $order, 'get_shipping_phone', $this->meta( $order, '_shipping_phone' ) );
		$discount = $this->call( $order, 'get_discount_total', 0 );
		$byl_discount = $this->call( $order, 'get_total_discount', $discount );
		$customer_email = 'order_row' === $row_mode ? $this->registered_customer_email( $customer_id ) : $this->call( $order, 'get_billing_email' );
		$row = array(
			'order_id' => $this->call( $order, 'get_id', 0 ), 'order_number' => $this->call( $order, 'get_order_number' ), 'order_date' => $this->date_value( $this->call( $order, 'get_date_created', null ) ), 'paid_date' => $this->date_value( $this->call( $order, 'get_date_paid', null ) ), 'status' => $this->call( $order, 'get_status' ),
			'currency' => $this->call( $order, 'get_currency' ), 'subtotal' => $this->call( $order, 'get_subtotal', 0 ), 'discount' => $discount, 'shipping' => $this->call( $order, 'get_shipping_total', 0 ), 'tax' => $this->call( $order, 'get_total_tax', 0 ), 'total' => $this->call( $order, 'get_total', 0 ),
			'shipping_total' => $this->call( $order, 'get_shipping_total', 0 ), 'shipping_tax_total' => $this->call( $order, 'get_shipping_tax', 0 ), 'fee_total' => $this->fee_total( $order ), 'fee_tax_total' => $this->fee_tax_total( $order ), 'tax_total' => $this->call( $order, 'get_total_tax', 0 ), 'cart_discount' => $byl_discount, 'order_discount' => $byl_discount, 'discount_total' => $byl_discount, 'order_total' => $this->call( $order, 'get_total', 0 ), 'order_subtotal' => $this->call( $order, 'get_subtotal', 0 ),
			'order_key' => $this->call( $order, 'get_order_key' ), 'order_currency' => $this->call( $order, 'get_currency' ), 'payment_method' => $this->call( $order, 'get_payment_method' ), 'payment_method_title' => $this->call( $order, 'get_payment_method_title' ), 'transaction_id' => $this->call( $order, 'get_transaction_id' ), 'customer_ip_address' => $this->call( $order, 'get_customer_ip_address' ), 'customer_user_agent' => $this->call( $order, 'get_customer_user_agent' ), 'shipping_method' => $this->call( $order, 'get_shipping_method' ), 'customer_id' => $customer_id, 'customer_user' => $customer_id ? (string) $customer_id : '', 'customer_email' => $customer_email,
			'billing_first_name' => $this->call( $order, 'get_billing_first_name' ), 'billing_last_name' => $this->call( $order, 'get_billing_last_name' ), 'billing_company' => $this->call( $order, 'get_billing_company' ), 'billing_email' => $this->call( $order, 'get_billing_email' ), 'billing_phone' => $this->call( $order, 'get_billing_phone' ), 'billing_address_1' => $this->call( $order, 'get_billing_address_1' ), 'billing_address_2' => $this->call( $order, 'get_billing_address_2' ), 'billing_postcode' => $this->call( $order, 'get_billing_postcode' ), 'billing_city' => $this->call( $order, 'get_billing_city' ), 'billing_state' => $this->call( $order, 'get_billing_state' ), 'billing_country' => $this->call( $order, 'get_billing_country' ),
			'shipping_first_name' => $this->call( $order, 'get_shipping_first_name' ), 'shipping_last_name' => $this->call( $order, 'get_shipping_last_name' ), 'shipping_company' => $this->call( $order, 'get_shipping_company' ), 'shipping_phone' => $shipping_phone, 'shipping_address_1' => $this->call( $order, 'get_shipping_address_1' ), 'shipping_address_2' => $this->call( $order, 'get_shipping_address_2' ), 'shipping_postcode' => $this->call( $order, 'get_shipping_postcode' ), 'shipping_city' => $this->call( $order, 'get_shipping_city' ), 'shipping_state' => $this->call( $order, 'get_shipping_state' ), 'shipping_country' => $this->call( $order, 'get_shipping_country' ), 'customer_note' => $this->call( $order, 'get_customer_note' ), 'wt_import_key' => $this->call( $order, 'get_order_number' ),
			'tax_items' => $this->collection_value( $order, 'tax' ), 'shipping_items' => $this->collection_value( $order, 'shipping' ), 'fee_items' => $this->collection_value( $order, 'fee' ), 'coupon_items' => $this->collection_value( $order, 'coupon' ), 'refund_items' => $this->refund_value( $order ), 'order_notes' => $this->order_notes_value( $order ), 'download_permissions' => $this->call( $order, 'is_download_permitted', false ) ? '1' : '0',
		);
		foreach ( array( 'device_type','referrer','session_count','session_entry','session_pages','session_start_time','source_type','user_agent','utm_source' ) as $attribution ) { $row[ 'meta:_wc_order_attribution_' . $attribution ] = $this->meta( $order, '_wc_order_attribution_' . $attribution ); }
		$row['product_id'] = $item ? $this->call( $item, 'get_product_id', 0 ) : ''; $row['variation_id'] = $item ? $this->call( $item, 'get_variation_id', 0 ) : ''; $row['sku'] = $product ? $this->call( $product, 'get_sku' ) : ''; $row['product_name'] = $item ? $this->call( $item, 'get_name' ) : ''; $row['quantity'] = $item ? $this->call( $item, 'get_quantity', 0 ) : ''; $row['line_subtotal'] = $item ? $this->call( $item, 'get_subtotal', 0 ) : ''; $row['line_total'] = $item ? $this->call( $item, 'get_total', 0 ) : '';
		return $row;
	}

	protected function add_dynamic_item_values( &$row, $index, $item ) {
		$product = $item && method_exists( $item, 'get_product' ) ? $item->get_product() : null; $name = $this->call( $item, 'get_name' ); $id = $this->call( $item, 'get_product_id', 0 ); $sku = $product ? $this->call( $product, 'get_sku' ) : ''; $quantity = $this->call( $item, 'get_quantity', 0 ); $total = $this->call( $item, 'get_total', 0 ); $subtotal = $this->call( $item, 'get_subtotal', 0 );
		$row[ 'line_item_' . $index ] = implode( ' | ', array( 'name=' . $name, 'product_id=' . $id, 'sku=' . $sku, 'quantity=' . $quantity, 'total=' . $total, 'subtotal=' . $subtotal ) );
		$row[ 'line_item_' . $index . '_name' ] = $name; $row[ 'line_item_' . $index . '_product_id' ] = $id; $row[ 'line_item_' . $index . '_sku' ] = $sku; $row[ 'line_item_' . $index . '_quantity' ] = $quantity; $row[ 'line_item_' . $index . '_total' ] = $total; $row[ 'line_item_' . $index . '_subtotal' ] = $subtotal;
	}

	protected function csv_cell( $value, $type ) {
		if ( is_bool( $value ) ) { $value = $value ? '1' : '0'; } if ( ! is_scalar( $value ) && null !== $value ) { $value = ''; } $value = null === $value ? '' : (string) $value; $value = str_replace( "\0", '', $value );
		if ( 'number' !== $type ) { $probe = ltrim( $value ); if ( '' !== $probe && in_array( $probe[0], array( '=', '+', '-', '@' ), true ) ) { $value = "'" . $value; } } return $value;
	}
}
