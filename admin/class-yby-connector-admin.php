<?php
/**
 * BYL ERP WordPress Connector admin page.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connector foundation admin controller.
 */
class YBY_Connector_Admin {
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '你没有权限访问此页面。', 'yby-core' ) );
		}

		$notice      = '';
		$notice_type = 'success';
		if ( isset( $_POST['yby_connector_submit'] ) ) {
			check_admin_referer( 'yby_connector_save', 'yby_connector_nonce' );
			$raw = wp_unslash( $_POST['yby_connector_options'] ?? array() );
			YBY_Connector::save( is_array( $raw ) ? $raw : array() );
			$notice = '设置已保存。';
		}
		if ( isset( $_POST['yby_connector_self_check'] ) ) {
			check_admin_referer( 'yby_connector_self_check', 'yby_connector_check_nonce' );
			$connector_status = YBY_Connector::status();
			$self_check_status = 'Ready' === $connector_status ? '基础配置正常' : YBY_Connector::status_label( $connector_status );
			$notice = '本地检查：' . $self_check_status . '。当前未连接 ERP。';
			$notice_type = 'Ready' === $connector_status ? 'success' : 'warning';
		}

		$options   = YBY_Connector::get_options();
		$status    = YBY_Connector::status();
		$providers = YBY_Connector::provider_statuses();
		$endpoints = YBY_Connector::endpoint_statuses();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/connector-page.php';
	}
}
