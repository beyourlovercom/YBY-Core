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
		// Andy Core shell is optional: Commerce remains an independent plugin.
		if ( class_exists( 'YBY_Andy_Admin_Shell_V22' ) ) {
			YBY_Andy_Admin_Shell_V22::render( self::PAGE_SLUG, 'Commerce OS',
				function () { $this->render_page_original(); } );
			return;
		}
		$this->render_page_original();
	}

	private function render_page_original() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Andy Commerce.', 'andy-commerce' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( ! in_array( $tab, array( 'overview', 'order-export', 'checkout-shipping', 'marketing-promotions', 'currency-os', 'settings' ), true ) ) { $tab = 'overview'; }

		// Dev-only M9: read-only multi-rule USD eligibility workbench.
		if ('marketing-promotions' === $tab && isset($_GET['m9_preview']) && $_GET['m9_preview'] === '1') {
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m9-usd-multirule-lab.php';
			return;
		}

		if ( 'marketing-promotions' === $tab ) {
			if ($this->m3_bridge && Andy_Commerce_M3_Dev_Bridge::enabled()) {
				if ( ! class_exists( 'YBY_Andy_Admin_Shell_V22' ) ||
				     ! YBY_Andy_Admin_Shell_V22::suppress_legacy_tabs( self::PAGE_SLUG ) ) {
				echo '<div class="wrap"><h1>Andy Commerce</h1><nav class="nav-tab-wrapper" aria-label="Commerce sections">';
				foreach (['overview'=>'Overview','order-export'=>'Order Export','checkout-shipping'=>'Checkout / Shipping','marketing-promotions'=>'Marketing / Promotions','settings'=>'Settings'] as $key=>$name) {
					$link=add_query_arg(['page'=>self::PAGE_SLUG,'tab'=>$key],admin_url('admin.php'));
					echo '<a class="nav-tab'.($key==='marketing-promotions'?' nav-tab-active':'').'" href="'.esc_url($link).'">'.esc_html($name).'</a>';
				}
				echo '</nav></div>';
				}
                // M4/M5 dedicated, read-only lab. Dev + manage_woocommerce
                // are checked above and also inside the view. Never touch
                // shipping policy, Woo hooks, Store API or campaign runtime.
                if (isset($_GET['m45_preview']) && $_GET['m45_preview'] === '1') {
                    include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m45-shadow-lab.php';
                    return;
                }
				$edit_id = isset($_GET['m3_edit']) && is_string($_GET['m3_edit'])
					? sanitize_key(wp_unslash($_GET['m3_edit'])) : '';
				if ($edit_id === '') {
					$m3_view = isset($_GET['m3_view']) && $_GET['m3_view']==='trash' ? 'trash':'all';
					$m3_search = isset($_GET['m3_q']) && is_string($_GET['m3_q'])
						? substr(sanitize_text_field(wp_unslash($_GET['m3_q'])),0,100) : '';
					$m3_page = isset($_GET['m3_page']) ? max(1,absint($_GET['m3_page'])) : 1;
					$m3_result = $this->m3_bridge->campaignListing($m3_view,$m3_search,$m3_page);
					$m3_counts = $this->m3_bridge->campaignCounts();
					include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-m3-draft-preview.php';
					return;
				}
				if ($edit_id !== '' && $edit_id !== 'new') {
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
			} else {
				// Original M1 read-only design shell when M3 is disabled.
				include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/marketing-promotions.php';
			}
			return;
		}

		// Dev-only standalone USD-first ledger preview, never a live Woo price hook.
		if ( 'currency-os' === $tab ) {
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/currency-os-v1-dev-lab.php';
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
