<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class YBY_Popup_Admin {
	protected $hook;
	public function add_admin_menu() { $this->hook = add_submenu_page( YBY_Project_Studio::menu_slug(), 'Popups', 'Popups', 'manage_options', 'yby-core-popups', array( $this, 'render' ) ); }
	public function enqueue_assets( $hook ) { if ( $hook === $this->hook ) { wp_enqueue_media(); wp_enqueue_script( 'yby-core-admin', YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js', array(), YBY_CORE_VERSION, true ); } }
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'yby-core' ) ); }
		if ( isset( $_POST['yby_popup_submit'] ) ) { check_admin_referer( 'yby_popup_save', 'yby_popup_nonce' ); update_option( YBY_Global_Popup::OPTION, YBY_Global_Popup::sanitize( wp_unslash( $_POST['yby_popup_settings'] ?? array() ) ) ); echo '<div class="notice notice-success"><p>Popup settings saved.</p></div>'; }
		$settings = YBY_Global_Popup::settings(); include YBY_CORE_PLUGIN_DIR . 'admin/views/popup-settings.php';
	}
}
