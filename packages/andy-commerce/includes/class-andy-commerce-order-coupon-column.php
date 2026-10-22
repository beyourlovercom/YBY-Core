<?php
/**
 * Read-only coupon code column for WooCommerce Orders (HPOS and legacy).
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Order_Coupon_Column {
	const COLUMN_KEY = 'andy_commerce_order_coupons';

	public static function boot() {
		// HPOS Orders table: WooCommerce passes a WC_Order object.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_hpos' ), 10, 2 );

		// Traditional shop_order post list: WordPress passes an order ID.
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_legacy' ), 10, 2 );
	}

	public static function add_column( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		// Smart Coupons or another extension may already provide this column.
		// Do not render two coupon-code columns when plugins coexist.
		foreach ( $columns as $key => $label ) {
			if ( self::COLUMN_KEY === $key ||
				false !== stripos( (string) $key, 'coupon' ) ||
				false !== stripos( strip_tags( (string) $label ), 'coupon' ) ) {
				return $columns;
			}
		}

		$result = array();
		$added  = false;
		foreach ( $columns as $key => $label ) {
			if ( ! $added && ( 'order_total' === $key || 'total' === $key ) ) {
				$result[ self::COLUMN_KEY ] = __( '折扣码', 'andy-commerce' );
				$added = true;
			}
			$result[ $key ] = $label;
		}
		if ( ! $added ) {
			$result[ self::COLUMN_KEY ] = __( '折扣码', 'andy-commerce' );
		}

		// Native WordPress Screen Options lets admins show/hide the column.
		return $result;
	}

	public static function render_hpos( $column, $order ) {
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}
		self::render_order( $order );
	}

	public static function render_legacy( $column, $order_id ) {
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}
		if ( ! is_numeric( $order_id ) || (int) $order_id < 1 ) {
			echo '&mdash;';
			return;
		}
		self::render_order( wc_get_order( (int) $order_id ) );
	}

	private static function render_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			echo '&mdash;';
			return;
		}

		$codes = $order->get_coupon_codes();
		if ( ! is_array( $codes ) || ! $codes ) {
			echo '&mdash;';
			return;
		}

		$rendered = 0;
		foreach ( $codes as $code ) {
			if ( ! is_scalar( $code ) || '' === (string) $code ) {
				continue;
			}
			printf(
				'<span class="andy-commerce-order-coupon" style="display:inline-block;margin:0 4px 4px 0;padding:2px 6px;border:1px solid #c3c4c7;border-radius:3px;background:#fff;">%s</span>',
				esc_html( (string) $code )
			);
			++$rendered;
		}
		if ( 0 === $rendered ) {
			echo '&mdash;';
		}
	}
}
