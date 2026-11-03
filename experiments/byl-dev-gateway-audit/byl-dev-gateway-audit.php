<?php
/**
 * Plugin Name: BYL Dev Payment Gateway Read-only Diagnostics
 * Description: Dev-only admin diagnostic of WooCommerce payment gateways and their filter owners. No payment actions.
 * Version: 0.1.0
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'dev.beyourlover.com' ) { return; }
add_action( 'admin_menu', function () {
	add_management_page( 'BYL Dev Gateway Audit', 'BYL Dev Gateway Audit', 'manage_woocommerce', 'byl-dev-gateway-audit', 'byl_dev_gateway_audit_page' );
} );
function byl_dev_gateway_audit_file( $filename ) {
	if ( ! $filename ) { return 'internal'; }
	$p = str_replace( '\\', '/', (string) $filename );
	$i = strpos( $p, '/wp-content/' );
	return false === $i ? basename( $p ) : substr( $p, $i + 1 );
}
function byl_dev_gateway_audit_boolean_method( $obj, $method ) {
	if ( ! method_exists( $obj, $method ) ) { return null; }
	try { return (bool) $obj->{$method}(); }
	catch ( Throwable $e ) { return 'error:' . get_class( $e ); }
}
function byl_dev_gateway_audit_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Forbidden' ); }
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) { wp_die( 'WooCommerce unavailable' ); }
	global $wp_filter;
	$report = [ 'scope' => 'Dev admin read-only, not checkout availability', 'site' => 'dev.beyourlover.com', 'currency' => get_woocommerce_currency(), 'gateways' => [], 'callbacks' => [] ];
	$registered = WC()->payment_gateways()->payment_gateways();
	foreach ( $registered as $key => $gateway ) {
		$name = (string) $gateway->id;
		$wcpay = false !== strpos( get_class( $gateway ), 'WC_Payment_Gateway_WCPay' );
		$item = [
			'id' => $name, 'class' => get_class( $gateway ), 'enabled' => (string) $gateway->enabled,
			'available_admin_context' => byl_dev_gateway_audit_boolean_method( $gateway, 'is_available' ),
		];
		if ( $wcpay || 'woocommerce_payments' === $name ) {
			foreach ( [ 'is_connected', 'needs_setup', 'needs_https_setup', 'is_available_for_current_currency', 'is_enabled' ] as $method ) {
				$item[ $method ] = byl_dev_gateway_audit_boolean_method( $gateway, $method );
			}
			if ( method_exists( $gateway, 'get_upe_enabled_payment_method_statuses' ) ) {
				try {
					$raw = $gateway->get_upe_enabled_payment_method_statuses();
					$item['payment_method_statuses'] = [];
					if ( is_array( $raw ) ) {
						foreach ( $raw as $type => $info ) {
							$item['payment_method_statuses'][ (string) $type ] = is_array( $info ) ? (string) ( $info['status'] ?? 'unknown' ) : 'unknown';
						}
					}
				} catch ( Throwable $e ) { $item['payment_method_statuses'] = 'error:' . get_class( $e ); }
			}
		}
		$report['gateways'][] = $item;
	}
	$hook = $wp_filter['woocommerce_available_payment_gateways'] ?? null;
	if ( is_object( $hook ) && isset( $hook->callbacks ) ) {
		foreach ( $hook->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $entry ) {
				$fn = $entry['function'] ?? null;
				$label = 'unknown'; $origin = 'internal';
				try {
					if ( $fn instanceof Closure ) { $r = new ReflectionFunction( $fn ); $label = 'closure'; }
					elseif ( is_array( $fn ) ) { $r = new ReflectionMethod( $fn[0], $fn[1] ); $label = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : $fn[0] ) . '::' . $fn[1]; }
					elseif ( is_string( $fn ) && function_exists( $fn ) ) { $r = new ReflectionFunction( $fn ); $label = $fn; }
					else { continue; }
					$origin = byl_dev_gateway_audit_file( $r->getFileName() );
				} catch ( Throwable $e ) { $origin = 'reflection_error'; }
				$report['callbacks'][] = [ 'priority' => (int) $priority, 'name' => $label, 'origin' => $origin ];
			}
		}
	}
	$available = WC()->payment_gateways()->get_available_payment_gateways();
	$report['available_in_admin_context'] = array_keys( $available );
	echo '<div class="wrap"><h1>BYL Dev Payment Gateway Audit</h1><p>Read-only admin context; frontend Checkout checks still required.</p><pre style="white-space:pre-wrap;background:#fff;padding:16px;max-width:1100px;">';
	echo esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	echo '</pre></div>';
}
