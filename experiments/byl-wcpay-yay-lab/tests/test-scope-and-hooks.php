<?php
namespace WCPay\MultiCurrency {
	class FrontendPrices {
		public function get_product_price_string() {}
		public function convert_shipping_method_rate_cost() {}
	}
	class FrontendCurrencies {
		public function get_woocommerce_currency() {}
	}
}
namespace {
	define( 'ABSPATH', __DIR__ );
	$_SERVER['HTTP_X_BYL_CURRENCY_LAB'] = '0';
	$GLOBALS['site_host'] = 'dev.beyourlover.com';
	$GLOBALS['wp_filter'] = [];
	function home_url() { return 'https://' . $GLOBALS['site_host'] . '/'; }
	function wp_parse_url( $url, $part ) { return parse_url( $url, $part ); }
	class TestHook { public $callbacks = []; }
	function add_filter( $name, $cb, $priority = 10, $accepted_args = 1 ) {
		if ( ! isset( $GLOBALS['wp_filter'][ $name ] ) ) { $GLOBALS['wp_filter'][ $name ] = new TestHook(); }
		$GLOBALS['wp_filter'][ $name ]->callbacks[ $priority ][] = [ 'function' => $cb, 'accepted_args' => $accepted_args ];
		return true;
	}
	function add_action( $name, $cb, $priority = 10 ) { return add_filter( $name, $cb, $priority ); }
	function remove_filter( $name, $cb, $priority = 10 ) {
		if ( empty( $GLOBALS['wp_filter'][ $name ]->callbacks[ $priority ] ) ) { return false; }
		foreach ( $GLOBALS['wp_filter'][ $name ]->callbacks[ $priority ] as $k => $x ) {
			if ( $x['function'] === $cb ) {
				unset( $GLOBALS['wp_filter'][ $name ]->callbacks[ $priority ][ $k ] );
				return true;
			}
		}
		return false;
	}
	function __return_true() { return true; }
	function __return_false() { return false; }
	require __DIR__ . '/../byl-wcpay-yay-lab.php';
	function check( $value, $name ) {
		if ( ! $value ) { echo "FAIL $name\n"; exit( 1 ); }
		echo "PASS $name\n";
	}
	$price = new \WCPay\MultiCurrency\FrontendPrices();
	$currency = new \WCPay\MultiCurrency\FrontendCurrencies();
	$other = new class { public function get_product_price_string() {} };
	add_filter( 'woocommerce_product_get_price', [ $price, 'get_product_price_string' ], 99 );
	add_filter( 'woocommerce_product_get_price', [ $other, 'get_product_price_string' ], 100 );
	add_filter( 'woocommerce_currency', [ $currency, 'get_woocommerce_currency' ], 900 );
	add_filter( 'woocommerce_shipping_method_add_rate_args', [ $price, 'convert_shipping_method_rate_cost' ], 99 );
	check( ! byl_currency_lab_request_enabled(), 'no header disabled' );
	check( [] === byl_currency_lab_detach_callbacks(), 'no header leaves hooks' );
	$_SERVER['HTTP_X_BYL_CURRENCY_LAB'] = '1';
	$GLOBALS['site_host'] = 'www.beyourlover.com';
	check( ! byl_currency_lab_request_enabled(), 'WWW blocked even with header' );
	$GLOBALS['site_host'] = 'dev.beyourlover.com';
	check( byl_currency_lab_request_enabled(), 'Dev with header enabled' );
	$removed = byl_currency_lab_detach_callbacks();
	check( ($removed['woocommerce_product_get_price'] ?? 0) === 1, 'WooPayments product hook removed' );
	check( ! isset( $removed['woocommerce_currency'] ) && count( $GLOBALS['wp_filter']['woocommerce_currency']->callbacks[900] ) === 1, 'WooPayments currency identity preserved for Store API' );
	check( ($removed['woocommerce_shipping_method_add_rate_args'] ?? 0) === 1, 'WooPayments shipping hook removed' );
	check( count( $GLOBALS['wp_filter']['woocommerce_product_get_price']->callbacks[100] ) === 1, 'other plugins untouched' );
	check( [] === byl_currency_lab_detach_callbacks(), 'repeated removal idempotent' );
	byl_currency_lab_boot();
	check( isset( $GLOBALS['wp_filter']['wcpay_multi_currency_should_convert_product_price'] ), 'fallback filter installed' );
	echo "LOCAL_TESTS_PASS\n";
}
