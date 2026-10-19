<?php
/** Existing Global Popup and dock settings controller, shared by legacy and Three OS routes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Popup_Admin {
	protected $plugin;
	protected $version;
	protected $hook;

	public function __construct( $plugin, $version ) {
		$this->plugin = $plugin;
		$this->version = $version;
	}

	public function add_admin_menu() {
		$this->hook = add_submenu_page(
			YBY_Project_Studio::menu_slug(), '邮件', '邮件',
			'manage_options', 'yby-core-popups', array( $this, 'render' )
		);
	}

	public function enqueue_assets( $hook ) {
		$embedded_hook = class_exists( 'YBY_Inquiry_Admin' ) ? YBY_Inquiry_Admin::registered_page_hook() : '';
		$is_popup = isset( $_GET['tab'] ) && is_scalar( $_GET['tab'] ) && in_array( sanitize_key( wp_unslash( $_GET['tab'] ) ), array( 'popup_inquiry', 'inquiry' ), true );
		if ( $is_popup && ( $hook === $this->hook || ( $embedded_hook && $hook === $embedded_hook ) ) ) {
			wp_enqueue_style( 'yby-popup-field-studio', YBY_CORE_PLUGIN_URL . 'assets/css/yby-popup-field-studio.css', array(), $this->version );
			wp_enqueue_script( 'yby-popup-field-studio', YBY_CORE_PLUGIN_URL . 'assets/js/yby-popup-field-studio.js', array(), $this->version, true );
		}
		if ( $hook !== $this->hook ) { return; }
		wp_enqueue_media();
		wp_enqueue_style( 'yby-global-inquiry-dock-preview', YBY_CORE_PLUGIN_URL . 'public/css/yby-global-inquiry-dock.css', array(), $this->version );
		wp_enqueue_style( 'yby-email-shortcodes', YBY_CORE_PLUGIN_URL . 'public/css/yby-email-shortcodes.css', array(), $this->version );
		wp_enqueue_script( 'yby-core-admin', YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js', array( 'jquery' ), $this->version, true );
	}

	/** Original hidden legacy handler. GET redirects happen before rendering; POSTs remain here. */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
			return;
		}
		$tab = isset( $_GET['tab'] ) && is_scalar( $_GET['tab'] )
			? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$this->render_screen( $tab, false );
	}

	/**
	 * Called by the original Inquiry / Newsletter controllers, retaining their
	 * own server-side capability checks. No new route, no duplicate POST engine.
	 */
	public function render_embedded( $tab ) {
		$tab = is_scalar( $tab ) ? sanitize_key( (string) $tab ) : '';
		$inquiry = in_array( $tab, array( 'floating_inquiry', 'popup_inquiry', 'shortcode_inquiry' ), true );
		$newsletter = in_array( $tab, array( 'popup_subscribe', 'shortcode_subscribe' ), true );
		if ( ( ! $inquiry && ! $newsletter ) ||
		     ( $inquiry && ! current_user_can( 'andy_core_settings_manage' ) ) ||
		     ( $newsletter && ! current_user_can( 'manage_options' ) ) ) {
			wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
			return;
		}
		$this->render_screen( $tab, true );
	}

	/** Shared original option handlers and view: render exactly once. */
	private function render_screen( $requested_tab, $embedded ) {
		$inquiry_enabled = YBY_Module_Registry::is_enabled( 'inquiry_os' );
		$email_enabled = YBY_Module_Registry::is_enabled( 'email_os' );
		$tabs = array();
		if ( $inquiry_enabled ) {
			$tabs += array(
				'floating_inquiry' => '悬浮询盘',
				'popup_inquiry' => '弹窗询盘',
				'popup_subscribe' => '弹窗订阅',
				'popup_lottery' => '弹窗抽奖',
				'shortcode_inquiry' => '短代码询盘',
				'shortcode_subscribe' => '短代码订阅',
			);
		}
		if ( $email_enabled ) {
			$tabs += array( 'bulk_marketing' => '营销群发', 'email_templates' => '邮件模板' );
		}
		if ( empty( $tabs ) ) {
			wp_die( esc_html__( 'No Email OS or Inquiry OS modules are enabled.', 'yby-core' ) );
			return;
		}
		$tab = $requested_tab;
		if ( '' === $tab ) { $tab = $inquiry_enabled ? 'floating_inquiry' : 'email_templates'; }
		$tab = array( 'inquiry' => 'popup_inquiry', 'subscribe' => 'popup_subscribe' )[ $tab ] ?? $tab;
		if ( ! isset( $tabs[ $tab ] ) ) {
			if ( $embedded ) {
				wp_die( esc_html__( 'Unavailable popup section.', 'yby-core' ) );
				return;
			}
			$tab = $inquiry_enabled ? 'floating_inquiry' : 'email_templates';
		}

		// The existing popup tab owns this editor; enqueue assets in both legacy
		// and embedded Three OS routes, without registering an additional page.
		if ( $inquiry_enabled && 'popup_inquiry' === $tab ) {
			wp_enqueue_style( 'yby-popup-field-studio', YBY_CORE_PLUGIN_URL . 'assets/css/yby-popup-field-studio.css', array(), $this->version );
			wp_enqueue_script( 'yby-popup-field-studio', YBY_CORE_PLUGIN_URL . 'assets/js/yby-popup-field-studio.js', array(), $this->version, true );
		}

		// Exactly the original nonce-protected settings and dock writes.
		if ( $inquiry_enabled && 'floating_inquiry' === $tab && isset( $_POST['yby_inquiry_dock_submit'] ) ) {
			check_admin_referer( 'yby_inquiry_dock_save', 'yby_inquiry_dock_nonce' );
			$raw = wp_unslash( $_POST['yby_inquiry_dock'] ?? array() );
			update_option( YBY_Global_Inquiry_Dock::OPTION, YBY_Global_Inquiry_Dock::sanitize( is_array( $raw ) ? $raw : array() ) );
			echo '<div class="notice notice-success"><p>Floating inquiry settings saved.</p></div>';
		}
		if ( $inquiry_enabled && isset( $_POST['yby_popup_submit'] ) ) {
			check_admin_referer( 'yby_popup_save', 'yby_popup_nonce' );
			// Atomic opt-in settings save. The legacy form keeps its original nonce,
			// permissions, and POST handler; no site config is changed by viewing.
			if ( 'popup_inquiry' === $tab && isset( $_POST['yby_popup_field_studio_payload'] ) ) {
				if ( ! current_user_can( 'andy_core_settings_manage' ) ) {
					wp_die( esc_html__( 'Permission denied.', 'yby-core' ) );
				}
				$payload = wp_unslash( $_POST['yby_popup_field_studio_payload'] );
				if ( ! is_string( $payload ) || strlen( $payload ) > 180000 ) {
					wp_die( esc_html__( 'Invalid Popup Studio payload.', 'yby-core' ) );
				}
				$decoded = json_decode( $payload, true );
				if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
					wp_die( esc_html__( 'Invalid Popup Studio JSON.', 'yby-core' ) );
				}
				update_option( YBY_Popup_Field_Studio::OPTION, YBY_Popup_Field_Studio::sanitize( $decoded ), false );
			}
			$existing = YBY_Global_Popup::settings();
			$submitted = wp_unslash( $_POST['yby_popup_settings'] ?? array() );
			update_option( YBY_Global_Popup::OPTION, YBY_Global_Popup::sanitize( array_merge( $existing, is_array( $submitted ) ? $submitted : array() ) ) );
			echo '<div class="notice notice-success"><p>Popup settings saved.</p></div>';
		}
		$settings = $inquiry_enabled ? YBY_Global_Popup::sanitize( YBY_Global_Popup::settings() ) : array();
		$dock_settings = $inquiry_enabled ? YBY_Global_Inquiry_Dock::sanitize( YBY_Global_Inquiry_Dock::settings() ) : array();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/popup-settings.php';
	}
}
