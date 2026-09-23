<?php
/** Woo Order Export preset registry. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_Export_Presets {
	public static function column_catalog() {
		$columns = array(
			'order_id' => array( 'label' => 'Order ID', 'type' => 'number' ),
			'order_number' => array( 'label' => 'Order Number', 'type' => 'text' ),
			'order_date' => array( 'label' => 'Order Date', 'type' => 'text' ),
			'paid_date' => array( 'label' => 'Paid Date', 'type' => 'text' ),
			'status' => array( 'label' => 'Status', 'type' => 'text' ),
			'currency' => array( 'label' => 'Currency', 'type' => 'text' ),
			'subtotal' => array( 'label' => 'Subtotal', 'type' => 'number' ),
			'discount' => array( 'label' => 'Discount', 'type' => 'number' ),
			'shipping' => array( 'label' => 'Shipping', 'type' => 'number' ),
			'tax' => array( 'label' => 'Tax', 'type' => 'number' ),
			'total' => array( 'label' => 'Total', 'type' => 'number' ),
			'payment_method' => array( 'label' => 'Payment Method', 'type' => 'text' ),
			'transaction_id' => array( 'label' => 'Transaction ID', 'type' => 'text' ),
			'customer_id' => array( 'label' => 'Customer ID', 'type' => 'number' ),
			'customer_email' => array( 'label' => 'Customer Email', 'type' => 'text' ),
			'billing_first_name' => array( 'label' => 'Billing First Name', 'type' => 'text' ),
			'billing_last_name' => array( 'label' => 'Billing Last Name', 'type' => 'text' ),
			'billing_company' => array( 'label' => 'Billing Company', 'type' => 'text' ),
			'billing_email' => array( 'label' => 'Billing Email', 'type' => 'text' ),
			'billing_phone' => array( 'label' => 'Billing Phone', 'type' => 'text' ),
			'billing_address_1' => array( 'label' => 'Billing Address 1', 'type' => 'text' ),
			'billing_address_2' => array( 'label' => 'Billing Address 2', 'type' => 'text' ),
			'billing_city' => array( 'label' => 'Billing City', 'type' => 'text' ),
			'billing_state' => array( 'label' => 'Billing State', 'type' => 'text' ),
			'billing_postcode' => array( 'label' => 'Billing Postcode', 'type' => 'text' ),
			'billing_country' => array( 'label' => 'Billing Country', 'type' => 'text' ),
			'shipping_first_name' => array( 'label' => 'Shipping First Name', 'type' => 'text' ),
			'shipping_last_name' => array( 'label' => 'Shipping Last Name', 'type' => 'text' ),
			'shipping_company' => array( 'label' => 'Shipping Company', 'type' => 'text' ),
			'shipping_phone' => array( 'label' => 'Shipping Phone', 'type' => 'text' ),
			'shipping_address_1' => array( 'label' => 'Shipping Address 1', 'type' => 'text' ),
			'shipping_address_2' => array( 'label' => 'Shipping Address 2', 'type' => 'text' ),
			'shipping_city' => array( 'label' => 'Shipping City', 'type' => 'text' ),
			'shipping_state' => array( 'label' => 'Shipping State', 'type' => 'text' ),
			'shipping_postcode' => array( 'label' => 'Shipping Postcode', 'type' => 'text' ),
			'shipping_country' => array( 'label' => 'Shipping Country', 'type' => 'text' ),
			'product_id' => array( 'label' => 'Product ID', 'type' => 'number' ),
			'variation_id' => array( 'label' => 'Variation ID', 'type' => 'number' ),
			'sku' => array( 'label' => 'SKU', 'type' => 'text' ),
			'product_name' => array( 'label' => 'Product Name', 'type' => 'text' ),
			'quantity' => array( 'label' => 'Quantity', 'type' => 'number' ),
			'line_subtotal' => array( 'label' => 'Line Subtotal', 'type' => 'number' ),
			'line_total' => array( 'label' => 'Line Total', 'type' => 'number' ),

			// Audited BYL compatibility fields. These extend the catalog only; generic Full stays frozen below.
			'shipping_total' => array( 'label' => 'Shipping Total', 'type' => 'number' ),
			'shipping_tax_total' => array( 'label' => 'Shipping Tax Total', 'type' => 'number' ),
			'fee_total' => array( 'label' => 'Fee Total', 'type' => 'number' ),
			'fee_tax_total' => array( 'label' => 'Fee Tax Total', 'type' => 'number' ),
			'tax_total' => array( 'label' => 'Tax Total', 'type' => 'number' ),
			'cart_discount' => array( 'label' => 'Cart Discount', 'type' => 'number' ),
			'order_discount' => array( 'label' => 'Order Discount', 'type' => 'number' ),
			'discount_total' => array( 'label' => 'Discount Total', 'type' => 'number' ),
			'order_total' => array( 'label' => 'Order Total', 'type' => 'number' ),
			'order_subtotal' => array( 'label' => 'Order Subtotal', 'type' => 'number' ),
			'order_key' => array( 'label' => 'Order Key', 'type' => 'text' ),
			'order_currency' => array( 'label' => 'Order Currency', 'type' => 'text' ),
			'payment_method_title' => array( 'label' => 'Payment Method Title', 'type' => 'text' ),
			'customer_ip_address' => array( 'label' => 'Customer IP Address', 'type' => 'text' ),
			'customer_user_agent' => array( 'label' => 'Customer User Agent', 'type' => 'text' ),
			'shipping_method' => array( 'label' => 'Shipping Method', 'type' => 'text' ),
			'customer_user' => array( 'label' => 'Customer User', 'type' => 'text' ),
			'customer_note' => array( 'label' => 'Customer Note', 'type' => 'text' ),
			'wt_import_key' => array( 'label' => 'WT Import Key', 'type' => 'text' ),
			'tax_items' => array( 'label' => 'Tax Items', 'type' => 'text' ),
			'shipping_items' => array( 'label' => 'Shipping Items', 'type' => 'text' ),
			'fee_items' => array( 'label' => 'Fee Items', 'type' => 'text' ),
			'coupon_items' => array( 'label' => 'Coupon Items', 'type' => 'text' ),
			'refund_items' => array( 'label' => 'Refund Items', 'type' => 'text' ),
			'order_notes' => array( 'label' => 'Order Notes', 'type' => 'text' ),
			'download_permissions' => array( 'label' => 'Download Permissions', 'type' => 'text' ),
		);
		foreach ( array( 'device_type','referrer','session_count','session_entry','session_pages','session_start_time','source_type','user_agent','utm_source' ) as $attribution ) {
			$columns[ 'meta:_wc_order_attribution_' . $attribution ] = array( 'label' => 'WC Order Attribution ' . ucwords( str_replace( '_', ' ', $attribution ) ), 'type' => 'text' );
		}
		return function_exists( 'apply_filters' ) ? apply_filters( 'andy_core_order_export_columns', $columns ) : $columns;
	}

	public static function presets() {
		$generic_full = array(
			'order_id','order_number','order_date','paid_date','status','currency','subtotal','discount','shipping','tax','total','payment_method','transaction_id','customer_id','customer_email',
			'billing_first_name','billing_last_name','billing_company','billing_email','billing_phone','billing_address_1','billing_address_2','billing_city','billing_state','billing_postcode','billing_country',
			'shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postcode','shipping_country',
			'product_id','variation_id','sku','product_name','quantity','line_subtotal','line_total',
		);
		$processing = array( 'order_number','order_date','status','order_total','order_currency','customer_email','billing_email','billing_phone','shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_postcode','shipping_city','shipping_state','shipping_country' );
		$byl_full = array( 'order_id','order_number','order_date','paid_date','status','shipping_total','shipping_tax_total','fee_total','fee_tax_total','tax_total','cart_discount','order_discount','discount_total','order_total','order_subtotal','order_key','order_currency','payment_method','payment_method_title','transaction_id','customer_ip_address','customer_user_agent','shipping_method','customer_id','customer_user','customer_email','billing_first_name','billing_last_name','billing_company','billing_email','billing_phone','billing_address_1','billing_address_2','billing_postcode','billing_city','billing_state','billing_country','shipping_first_name','shipping_last_name','shipping_company','shipping_phone','shipping_address_1','shipping_address_2','shipping_postcode','shipping_city','shipping_state','shipping_country','customer_note','wt_import_key','tax_items','shipping_items','fee_items','coupon_items','refund_items','order_notes','download_permissions','meta:_wc_order_attribution_device_type','meta:_wc_order_attribution_referrer','meta:_wc_order_attribution_session_count','meta:_wc_order_attribution_session_entry','meta:_wc_order_attribution_session_pages','meta:_wc_order_attribution_session_start_time','meta:_wc_order_attribution_source_type','meta:_wc_order_attribution_user_agent','meta:_wc_order_attribution_utm_source' );
		$presets = array(
			'default' => array(
				'label' => 'Default',
				'row_mode' => 'line_item',
				'columns' => array( 'order_id','order_number','order_date','status','currency','total','payment_method','customer_email','shipping_first_name','shipping_last_name','shipping_phone','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postcode','shipping_country','sku','product_name','quantity','line_total' ),
			),
			'full' => array( 'label' => 'Full Export', 'row_mode' => 'line_item', 'columns' => $generic_full ),
			'byl_processing_orders' => array( 'label' => 'BYL Processing Orders', 'row_mode' => 'order_row', 'columns' => $processing, 'header_labels' => array_combine( $processing, $processing ) ),
			'byl_full_order_report' => array( 'label' => 'BYL Full Report', 'row_mode' => 'order_row', 'columns' => $byl_full, 'header_labels' => array_combine( $byl_full, $byl_full ) ),
		);
		return function_exists( 'apply_filters' ) ? apply_filters( 'andy_core_order_export_presets', $presets ) : $presets;
	}

	public static function get( $id ) {
		$id = sanitize_key( (string) $id );
		$presets = self::presets();
		return isset( $presets[ $id ] ) ? $presets[ $id ] : array();
	}
}
