<?php
/**
 * Plugin Name: BYL WooPayments / YayCurrency Conversion Lab
 * Description: Header-gated, Dev-only single-conversion experiment. Never install on production.
 * Version: 0.1.1-dev
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Only dev.beyourlover.com with an explicit per-request test header. */
function byl_currency_lab_request_enabled(): bool {
	$hostname = function_exists( 'home_url' ) ? wp_parse_url( home_url(), PHP_URL_HOST ) : '';
	$header   = isset( $_SERVER['HTTP_X_BYL_CURRENCY_LAB'] ) ? (string) $_SERVER['HTTP_X_BYL_CURRENCY_LAB'] : '';
	return 'dev.beyourlover.com' === $hostname && '1' === $header;
}

/** Only remove approved WooPayments price transforms; keep currency identity hooks; never modify other plugins. */
function byl_currency_lab_detach_callbacks(): array {
	if ( ! byl_currency_lab_request_enabled() ) {
		return [];
	}
	global $wp_filter;
	$allowed = [
		'WCPay\MultiCurrency\FrontendPrices' => [
			'woocommerce_product_get_price'                   => [ 'get_product_price_string' ],
			'woocommerce_product_get_regular_price'           => [ 'get_product_price_string' ],
			'woocommerce_product_get_sale_price'              => [ 'get_product_price_string' ],
			'woocommerce_product_variation_get_price'         => [ 'get_product_price_string' ],
			'woocommerce_product_variation_get_regular_price' => [ 'get_product_price_string' ],
			'woocommerce_product_variation_get_sale_price'    => [ 'get_product_price_string' ],
			'woocommerce_variation_prices'                    => [ 'get_variation_price_range' ],
			'woocommerce_get_variation_prices_hash'           => [ 'add_exchange_rate_to_variation_prices_hash' ],
			'woocommerce_shipping_zone_shipping_methods'     => [ 'convert_free_shipping_method_min_amount' ],
			'woocommerce_shipping_method_add_rate_args'      => [ 'convert_shipping_method_rate_cost' ],
			'woocommerce_coupon_get_amount'                   => [ 'get_coupon_amount' ],
			'woocommerce_coupon_get_minimum_amount'           => [ 'get_coupon_min_max_amount' ],
			'woocommerce_coupon_get_maximum_amount'           => [ 'get_coupon_min_max_amount' ],
			'rest_post_dispatch'                              => [ 'maybe_modify_price_ranges_rest_response' ],
			'query_loop_block_query_vars'                     => [ 'maybe_modify_price_ranges_query_var' ],
			'woocommerce_new_order'                           => [ 'add_order_meta' ],
		],

	];
	$removed = [];
	foreach ( $allowed as $class_name => $hooks ) {
		foreach ( $hooks as $tag => $methods ) {
			if ( ! isset( $wp_filter[ $tag ] ) || ! is_object( $wp_filter[ $tag ] ) || ! isset( $wp_filter[ $tag ]->callbacks ) ) {
				continue;
			}
			foreach ( $wp_filter[ $tag ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $data ) {
					$callback = isset( $data['function'] ) ? $data['function'] : null;
					if (
						! is_array( $callback ) || 2 !== count( $callback )
						|| ! is_object( $callback[0] )
						|| ! is_a( $callback[0], $class_name )
						|| ! in_array( $callback[1], $methods, true )
					) {
						continue;
					}
					if ( remove_filter( $tag, $callback, $priority ) ) {
						$removed[ $tag ] = ( $removed[ $tag ] ?? 0 ) + 1;
					}
				}
			}
		}
	}
	return $removed;
}

/** No database writes, no global change, no behavior without the test header. */
function byl_currency_lab_boot(): void {
	if ( ! byl_currency_lab_request_enabled() ) {
		return;
	}
	add_filter( 'wcpay_multi_currency_should_disable_currency_switching', '__return_true', 999 );
	add_filter( 'wcpay_multi_currency_should_convert_product_price', '__return_false', 999 );
	add_filter( 'wcpay_multi_currency_should_convert_coupon_amount', '__return_false', 999 );
	add_filter( 'wcpay_multi_currency_should_convert_shipping_amount', '__return_false', 999 );
	add_action( 'init', 'byl_currency_lab_detach_callbacks', PHP_INT_MAX );
	add_action( 'wp_loaded', 'byl_currency_lab_detach_callbacks', 0 );
	add_action( 'rest_api_init', 'byl_currency_lab_detach_callbacks', PHP_INT_MAX );
}
add_action( 'plugins_loaded', 'byl_currency_lab_boot', 999 );
