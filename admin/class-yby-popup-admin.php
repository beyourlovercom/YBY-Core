<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class YBY_Popup_Admin {
	protected $plugin; protected $version; protected $hook;
	public function __construct( $plugin, $version ) { $this->plugin = $plugin; $this->version = $version; }
	public function add_admin_menu() { $this->hook = add_submenu_page( YBY_Project_Studio::menu_slug(), 'Email OS', 'Email OS', 'manage_options', 'yby-core-popups', array( $this, 'render' ) ); }
	public function enqueue_assets( $hook ) { if ( $hook === $this->hook ) { wp_enqueue_media(); wp_enqueue_style( 'yby-global-inquiry-dock-preview', YBY_CORE_PLUGIN_URL . 'public/css/yby-global-inquiry-dock.css', array(), $this->version ); wp_enqueue_style( 'yby-email-shortcodes', YBY_CORE_PLUGIN_URL . 'public/css/yby-email-shortcodes.css', array(), $this->version ); wp_enqueue_script( 'yby-core-admin', YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js', array( 'jquery' ), $this->version, true ); } }
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'yby-core' ) ); }
		$inquiry_enabled = YBY_Module_Registry::is_enabled( 'inquiry_os' );
		$email_enabled = YBY_Module_Registry::is_enabled( 'email_os' );
		$tabs = array();
		if ( $inquiry_enabled ) {
			$tabs += array( 'floating_inquiry' => '悬浮询盘', 'popup_inquiry' => '弹窗询盘', 'popup_subscribe' => '弹窗订阅', 'popup_lottery' => '弹窗抽奖', 'shortcode_inquiry' => '短代码询盘', 'shortcode_subscribe' => '短代码订阅' );
		}
		if ( $email_enabled ) { $tabs += array( 'bulk_marketing' => '营销群发', 'email_templates' => '邮件模板' ); }
		if ( empty( $tabs ) ) { wp_die( esc_html__( 'No Email OS or Inquiry OS modules are enabled.', 'yby-core' ) ); }

		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'floating_inquiry' ) );
		if ( ! isset( $_GET['tab'] ) && ! $inquiry_enabled ) { $tab = 'email_templates'; }
		$tab = array( 'inquiry' => 'popup_inquiry', 'subscribe' => 'popup_subscribe' )[ $tab ] ?? $tab;
		if ( ! isset( $tabs[ $tab ] ) ) { $tab = $inquiry_enabled ? 'floating_inquiry' : 'email_templates'; }

		if ( $inquiry_enabled && 'floating_inquiry' === $tab && isset( $_POST['yby_inquiry_dock_submit'] ) ) { check_admin_referer( 'yby_inquiry_dock_save', 'yby_inquiry_dock_nonce' ); $raw = wp_unslash( $_POST['yby_inquiry_dock'] ?? array() ); update_option( YBY_Global_Inquiry_Dock::OPTION, YBY_Global_Inquiry_Dock::sanitize( is_array( $raw ) ? $raw : array() ) ); echo '<div class="notice notice-success"><p>Floating inquiry settings saved.</p></div>'; }
		if ( $inquiry_enabled && isset( $_POST['yby_popup_submit'] ) ) { check_admin_referer( 'yby_popup_save', 'yby_popup_nonce' ); $existing = YBY_Global_Popup::settings(); $submitted = wp_unslash( $_POST['yby_popup_settings'] ?? array() ); update_option( YBY_Global_Popup::OPTION, YBY_Global_Popup::sanitize( array_merge( $existing, is_array( $submitted ) ? $submitted : array() ) ) ); echo '<div class="notice notice-success"><p>Email settings saved.</p></div>'; }
		$settings = $inquiry_enabled ? YBY_Global_Popup::sanitize( YBY_Global_Popup::settings() ) : array();
		$dock_settings = $inquiry_enabled ? YBY_Global_Inquiry_Dock::sanitize( YBY_Global_Inquiry_Dock::settings() ) : array();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/popup-settings.php';
	}
}
