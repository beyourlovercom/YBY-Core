<?php
/**
 * Admin behavior.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin controller.
 */
class YBY_Admin {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Security helper.
	 *
	 * @var YBY_Security
	 */
	protected $security;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->security    = new YBY_Security();
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			__( 'Settings', 'yby-core' ),
			__( 'Settings', 'yby-core' ),
			'manage_options',
			YBY_Helpers::admin_page_slug(),
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$allowed_hooks = array(
			'toplevel_page_' . YBY_Project_Studio::menu_slug(),
			YBY_Project_Studio::menu_slug() . '_page_' . YBY_Helpers::admin_page_slug(),
		);

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			YBY_CORE_PLUGIN_URL . 'assets/css/yby-core-admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			YBY_CORE_PLUGIN_URL . 'assets/js/yby-core-admin.js',
			array(),
			$this->version,
			true
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! $this->security->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}

		$notice      = '';
		$notice_type = 'success';

		if ( isset( $_POST['yby_core_submit'] ) ) {
			check_admin_referer( 'yby_core_save_settings', 'yby_core_nonce' );

			$raw_options  = wp_unslash( $_POST['yby_core_options'] ?? array() );
			$options      = YBY_Config::sanitize( is_array( $raw_options ) ? $raw_options : array() );
			$primary_email = sanitize_email( wp_unslash( $_POST['yby_lead_notification_primary_recipient_email'] ?? '' ) );
			$cc_emails     = wp_unslash( $_POST['yby_lead_notification_cc_recipient_emails'] ?? '' );
			$bcc_emails    = wp_unslash( $_POST['yby_lead_notification_bcc_recipient_emails'] ?? '' );
			$reply_policy  = wp_unslash( $_POST['yby_lead_notification_reply_to_policy'] ?? 'auto' );

			update_option( YBY_Helpers::option_key(), $options );

			$notice = __( 'Settings saved.', 'yby-core' );

			if ( is_email( $primary_email ) ) {
				update_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), $primary_email );
			}

			update_option(
				YBY_Helpers::lead_notification_cc_recipient_option_key(),
				YBY_Config::sanitize_email_list_value( $cc_emails )
			);
			update_option(
				YBY_Helpers::lead_notification_bcc_recipient_option_key(),
				YBY_Config::sanitize_email_list_value( $bcc_emails )
			);
			update_option(
				YBY_Helpers::lead_notification_reply_to_policy_option_key(),
				YBY_Config::sanitize_reply_to_policy( $reply_policy )
			);
		}

		$options = YBY_Config::get_options();

		include YBY_CORE_PLUGIN_DIR . 'admin/views/settings-page.php';
	}
}
