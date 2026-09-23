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
		);
		return function_exists( 'apply_filters' ) ? apply_filters( 'andy_core_order_export_columns', $columns ) : $columns;
	}

	public static function presets() {
		$full = array_keys( self::column_catalog() );
		$presets = array(
			'default' => array(
				'label' => 'Default',
				'row_mode' => 'line_item',
				'columns' => array( 'order_id','order_number','order_date','status','currency','total','payment_method','customer_email','shipping_first_name','shipping_last_name','shipping_phone','shipping_address_1','shipping_address_2','shipping_city','shipping_state','shipping_postcode','shipping_country','sku','product_name','quantity','line_total' ),
			),
			'full' => array( 'label' => 'Full Export', 'row_mode' => 'line_item', 'columns' => $full ),
		);
		return function_exists( 'apply_filters' ) ? apply_filters( 'andy_core_order_export_presets', $presets ) : $presets;
	}

	public static function get( $id ) {
		$id = sanitize_key( (string) $id );
		$presets = self::presets();
		return isset( $presets[ $id ] ) ? $presets[ $id ] : array();
	}
}
