<?php
/**
 * Andy Commerce admin controller.
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Andy_Commerce_Admin {
	public function add_admin_menu() {
		add_menu_page(
			__( 'Andy Commerce', 'andy-commerce' ),
			__( 'Andy Commerce', 'andy-commerce' ),
			'manage_woocommerce',
			'andy-commerce',
			array( $this, 'render_overview' ),
			'dashicons-cart',
			57
		);

		add_submenu_page(
			'andy-commerce',
			__( 'Overview', 'andy-commerce' ),
			__( 'Overview', 'andy-commerce' ),
			'manage_woocommerce',
			'andy-commerce',
			array( $this, 'render_overview' )
		);
	}

	public function render_overview() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'andy-commerce' ) );
		}

		$status = andy_commerce_dependency_status();
		include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/overview.php';
	}
}
