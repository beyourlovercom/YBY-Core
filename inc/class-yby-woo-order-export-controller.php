<?php
/** Authenticated Woo Order Export controller. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_Export_Controller {
	const ACTION = 'yby_woo_order_export';
	const NONCE_ACTION = 'yby_woo_order_export';
	const NONCE_FIELD = 'yby_woo_order_export_nonce';

	public function handle_export() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { status_header( 405 ); wp_die( esc_html__( 'POST required.', 'yby-core' ) ); }
		if ( ! current_user_can( 'manage_woocommerce' ) ) { status_header( 403 ); wp_die( esc_html__( 'You do not have permission to export orders.', 'yby-core' ) ); }
		if ( ! YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID ) || ! YBY_Woo_Order_Export_Module::woocommerce_available() ) { status_header( 403 ); wp_die( esc_html__( 'Woo Order Export is not enabled.', 'yby-core' ) ); }
		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$settings = YBY_Woo_Order_Export_Module::get_settings();
		$preset = sanitize_key( wp_unslash( $_POST['preset'] ?? $settings['default_preset'] ) );
		if ( empty( YBY_Woo_Order_Export_Presets::get( $preset ) ) ) { $preset = $settings['default_preset']; }
		$filters = YBY_Woo_Order_Query_Adapter::sanitize_filters( wp_unslash( $_POST ) );
		$export_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'export_', true );
		$started = microtime( true );

		if ( function_exists( 'nocache_headers' ) ) { nocache_headers(); }
		while ( ob_get_level() > 0 ) { ob_end_clean(); }
		$filename = 'woo-orders-' . $preset . '-' . gmdate( 'Ymd-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

		$handle = fopen( 'php://output', 'wb' );
		if ( false === $handle ) { wp_die( esc_html__( 'Unable to open export stream.', 'yby-core' ) ); }
		try {
			$stats = ( new YBY_Woo_Order_CSV_Streamer() )->stream( $handle, $preset, $filters, $settings );
			$status = 'success';
		} catch ( Throwable $e ) {
			$stats = array( 'order_count' => 0, 'row_count' => 0, 'preset' => $preset );
			$status = 'failure';
		}
		fclose( $handle );

		if ( ! empty( $settings['audit_enabled'] ) ) {
			YBY_Woo_Order_Export_Audit::record( array(
				'export_id' => $export_id, 'timestamp' => gmdate( 'c' ), 'admin_user_id' => get_current_user_id(), 'preset' => $preset,
				'filters' => YBY_Woo_Order_Export_Audit::summarize_filters( $filters ), 'order_count' => $stats['order_count'] ?? 0, 'row_count' => $stats['row_count'] ?? 0,
				'duration_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ), 'status' => $status,
			) );
		}
		exit;
	}
}
