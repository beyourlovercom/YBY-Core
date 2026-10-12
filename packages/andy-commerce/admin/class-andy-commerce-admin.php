<?php
/**
 * Andy Commerce admin controller.
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Andy_Commerce_Admin {
	const PAGE_SLUG = 'andy-commerce';
	private $m3_bridge;
	public function __construct($m3_bridge = null) { $this->m3_bridge = $m3_bridge; }

	public function add_admin_menu() {
		add_menu_page(
			__( 'Andy Commerce', 'andy-commerce' ),
			__( 'Andy Commerce', 'andy-commerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-cart',
			57
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Overview', 'andy-commerce' ),
			__( 'Overview', 'andy-commerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Andy Commerce.', 'andy-commerce' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( ! in_array( $tab, array( 'overview', 'order-export', 'checkout-shipping', 'marketing-promotions', 'settings' ), true ) ) { $tab = 'overview'; }

		if ( 'marketing-promotions' === $tab ) {
			// Owner-gated M1 UI shell: no form handlers, options, coupons or runtime hooks.
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-promotions.php';
			if ($this->m3_bridge && Andy_Commerce_M3_Dev_Bridge::enabled()) {
				$drafts = $this->m3_bridge->drafts();
				include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m3-draft-preview.php';
				$edit_id = isset($_GET['m3_edit']) && is_string($_GET['m3_edit'])
					? sanitize_key(wp_unslash($_GET['m3_edit'])) : '';
				if ($edit_id !== '') {
					$row = $this->m3_bridge->draft($edit_id);
					if ($row) {
						$mode = 'revise';
						$revision = (int)$row['revision'];
						$stored = json_decode((string)$row['payload'], true);
						$allowed = ['schema_version','id','name','type','currency','status','active','priority',
							'countries','start_utc','end_utc','stack_with_coupon','rule','notes'];
						$campaign = array_intersect_key(is_array($stored)?$stored:[], array_flip($allowed));
					} else {
						echo '<div class="notice notice-error"><p>Draft not found or archived.</p></div>';
						return;
					}
				} else {
					$mode = 'create';
					$revision = 0;
					$campaign = [
						'schema_version'=>1,'id'=>'m3-'.strtolower(wp_generate_password(8,false,false)),
						'name'=>'New campaign draft','type'=>'spend_save','currency'=>'USD',
						'status'=>'draft','active'=>false,'priority'=>0,'countries'=>['US'],
						'rule'=>['threshold_minor'=>10000,'discount_minor'=>1000]
					];
				}
				include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m3-draft-editor.php';
			}
			return;
		}

		if ( 'checkout-shipping' === $tab ) {
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/checkout-shipping.php';
			return;
		}

		if ( 'overview' === $tab ) {
			$status = andy_commerce_dependency_status();
			$export_registered = ! empty( YBY_Module_Registry::module( YBY_Woo_Order_Export_Module::MODULE_ID ) );
			$export_enabled = $export_registered && YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID );
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/overview.php';
			return;
		}

		$notice = '';
		if ( 'settings' === $tab && isset( $_POST['yby_woo_export_settings_submit'] ) ) {
			check_admin_referer( 'yby_woo_export_settings_save', 'yby_woo_export_settings_nonce' );
			$raw = wp_unslash( $_POST['yby_woo_export_settings'] ?? array() );
			YBY_Woo_Order_Export_Module::save_settings( is_array( $raw ) ? $raw : array() );
			$notice = __( 'Commerce export settings saved.', 'andy-commerce' );
		}

		$module_enabled = YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID );
		$settings = YBY_Woo_Order_Export_Module::get_settings();
		$presets = YBY_Woo_Order_Export_Presets::presets();
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/order-export.php';
	}
}
