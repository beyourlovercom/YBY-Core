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

	public function redirect_legacy_inquiry_dock() {
		if ( ! is_admin() || ! isset( $_GET['page'], $_GET['tab'] ) || YBY_Helpers::admin_page_slug() !== sanitize_key( wp_unslash( $_GET['page'] ) ) || 'inquiry-dock' !== sanitize_key( wp_unslash( $_GET['tab'] ) ) || ! $this->security->can_manage_settings() ) { return; }
		wp_safe_redirect( add_query_arg( array( 'page' => 'yby-core-popups', 'tab' => 'floating_inquiry' ), admin_url( 'admin.php' ) ) );
		exit;
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
			'andy_core_settings_manage',
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
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$allowed_hooks = array(
			'toplevel_page_' . YBY_Project_Studio::menu_slug(),
			YBY_Project_Studio::menu_slug() . '_page_' . YBY_Helpers::admin_page_slug(),
		);

		// The Settings tab is identified by its page slug; this remains reliable
		// when WordPress supplies a different submenu hook suffix.
		if ( YBY_Helpers::admin_page_slug() !== $page && ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
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
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_media();
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

		$tab         = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tab         = in_array( $tab, array( 'general', 'inquiry', 'inquiry-notification', 'wp-api', 'system-status' ), true ) ? $tab : 'general';
		if ( 'inquiry' === $tab ) {
			$this->render_inquiry_settings();
			return;
		}
		if ( 'inquiry-notification' === $tab ) {
			$this->render_inquiry_notification_settings();
			return;
		}
		if ( 'wp-api' === $tab ) {
			( new YBY_Connector_Admin() )->render_page();
			return;
		}
		if ( 'system-status' === $tab ) {
			$this->render_system_status();
			return;
		}
		$notice      = '';
		$notice_type = 'success';

		if ( isset( $_POST['yby_core_submit'] ) ) {
			check_admin_referer( 'yby_core_save_settings', 'yby_core_nonce' );

			$raw_options = wp_unslash( $_POST['yby_core_options'] ?? array() );
			$raw_options = is_array( $raw_options ) ? $raw_options : array();
			$current_options = YBY_Config::get_options();
			foreach ( array( 'whatsapp_number', 'whatsapp_message_template', 'lead_notification_subject_template', 'inquiry_email_title', 'email_company_name', 'email_company_website' ) as $preserve_key ) {
				$raw_options[ $preserve_key ] = $current_options[ $preserve_key ];
			}
			$options = YBY_Config::sanitize( $raw_options );
			update_option( YBY_Helpers::option_key(), $options );
			$notice = __( 'Settings saved.', 'yby-core' );

		}

		$options = YBY_Config::get_options();

		include YBY_CORE_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	protected function render_inquiry_settings() {
		$tab = 'inquiry';
		$notice = '';
		if ( isset( $_POST['yby_inquiry_settings_submit'] ) ) {
			check_admin_referer( 'yby_inquiry_settings_save', 'yby_inquiry_settings_nonce' );
			$raw = wp_unslash( $_POST['yby_inquiry_settings'] ?? array() );
			$raw = is_array( $raw ) ? $raw : array();
			$options = array(
				'default_status' => in_array( $raw['default_status'] ?? '', YBY_Lead_Management::STATUSES, true ) ? $raw['default_status'] : 'new',
				'default_priority' => in_array( $raw['default_priority'] ?? '', YBY_Lead_Management::PRIORITIES, true ) ? $raw['default_priority'] : 'normal',
				'default_owner_user_id' => absint( $raw['default_owner_user_id'] ?? 0 ),
				'salespeople' => YBY_Security::active_owner_ids( $raw['salespeople'] ?? array() ),
				'leads_per_page' => in_array( absint( $raw['leads_per_page'] ?? 30 ), array( 30, 50, 100 ), true ) ? absint( $raw['leads_per_page'] ) : 30,
				'editors_can_manage' => ! empty( $raw['editors_can_manage'] ),
				'assignment_enabled' => ! empty( $raw['assignment_enabled'] ),
				'archive_behavior' => in_array( $raw['archive_behavior'] ?? 'soft', array( 'soft' ), true ) ? $raw['archive_behavior'] : 'soft',
			);
			if ( ! YBY_Security::is_assignable_owner( $options['default_owner_user_id'], $options['salespeople'] ) ) { $options['default_owner_user_id'] = 0; }
			update_option( 'yby_core_inquiry_salespeople', $options['salespeople'] );
			update_option( 'yby_core_inquiry_settings', $options );
			$notice = __( 'Inquiry settings saved.', 'yby-core' );
		}
		$options = wp_parse_args( get_option( 'yby_core_inquiry_settings', array() ), array( 'default_status' => 'new', 'default_priority' => 'normal', 'default_owner_user_id' => 0, 'leads_per_page' => 30, 'editors_can_manage' => false, 'assignment_enabled' => true, 'archive_behavior' => 'soft', 'salespeople' => YBY_Security::salesperson_ids() ) );
		$options['salespeople'] = YBY_Security::active_owner_ids( $options['salespeople'] );
		if ( ! YBY_Security::is_assignable_owner( $options['default_owner_user_id'], $options['salespeople'] ) ) { $options['default_owner_user_id'] = 0; }
		include YBY_CORE_PLUGIN_DIR . 'admin/views/inquiry-settings.php';
	}


	protected function render_inquiry_notification_settings() {
		$tab = 'inquiry-notification';
		$notice = '';
		if ( isset( $_POST['yby_inquiry_notification_submit'] ) ) {
			check_admin_referer( 'yby_inquiry_notification_save', 'yby_inquiry_notification_nonce' );
			$raw = wp_unslash( $_POST['yby_core_options'] ?? array() );
			$raw = is_array( $raw ) ? $raw : array();
			$current = YBY_Config::get_options();
			$options = YBY_Config::sanitize( array_merge( $current, $raw ) );
			update_option( YBY_Helpers::option_key(), $options );
			$primary = sanitize_email( wp_unslash( $_POST['yby_lead_notification_primary_recipient_email'] ?? '' ) );
			if ( '' === trim( (string) $primary ) ) {
				update_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), '' );
			} elseif ( is_email( $primary ) ) {
				update_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), $primary );
			}
			update_option( YBY_Helpers::lead_notification_cc_recipient_option_key(), YBY_Config::sanitize_email_list_value( wp_unslash( $_POST['yby_lead_notification_cc_recipient_emails'] ?? '' ) ) );
			update_option( YBY_Helpers::lead_notification_bcc_recipient_option_key(), YBY_Config::sanitize_email_list_value( wp_unslash( $_POST['yby_lead_notification_bcc_recipient_emails'] ?? '' ) ) );
			update_option( YBY_Helpers::lead_notification_reply_to_policy_option_key(), YBY_Config::sanitize_reply_to_policy( wp_unslash( $_POST['yby_lead_notification_reply_to_policy'] ?? 'auto' ) ) );
			$notice = '询盘通知设置已保存。';
		}
		$options = YBY_Config::get_options();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/inquiry-notification-settings.php';
	}

	protected function render_system_status() {
		$tab = 'system-status';
		$refresh = isset( $_GET['refresh'] ) && '1' === $_GET['refresh'];
		$cache_key = 'yby_core_system_status';
		$status = $refresh ? false : get_transient( $cache_key );
		if ( false === $status ) {
			$management_tables_ok = YBY_Database::management_tables_exist();
			$connector_tables_ok = YBY_Database::connector_tables_exist();
			$status = array(
				'plugin_version' => YBY_CORE_VERSION,
				'database_version' => get_option( YBY_Database::VERSION_OPTION, 'unknown' ),
				'lead_table' => YBY_Database::leads_table_exists(),
				'management_table' => $management_tables_ok,
				'activities_table' => $management_tables_ok,
				'index_status' => $management_tables_ok && $connector_tables_ok ? 'verified' : 'attention',
				'migration_status' => $management_tables_ok && $connector_tables_ok && YBY_DATABASE_VERSION === get_option( YBY_Database::VERSION_OPTION, '' ) ? 'ready' : 'attention',
				'recent_lead_metadata' => $this->recent_lead_metadata(),
				'management_consistency' => $management_tables_ok ? 'lazy management enabled' : 'attention',
				'preset_registry' => 'registered by inquiry preset manager',
				'thank_you_route' => YBY_Config::get_thank_you_url() ? 'configured' : 'attention',
				'mail_integration' => YBY_Config::get_lead_notification_primary_recipient_email() ? 'configured' : 'attention',
				'wordpress_php_database' => $this->environment_summary(),
				'status' => 'read-only',
			);
			set_transient( $cache_key, $status, 45 );
		}
		include YBY_CORE_PLUGIN_DIR . 'admin/views/system-status.php';
	}

	protected function recent_lead_metadata() {
		global $wpdb;
		if ( ! YBY_Database::leads_table_exists() ) { return 'unavailable'; }
		$row = $wpdb->get_row( 'SELECT created_at,source_preset,page_profile FROM ' . YBY_Database::leads_table_name() . ' ORDER BY created_at DESC LIMIT 1', ARRAY_A );
		return $row ? array( 'created_at' => $row['created_at'], 'source_preset' => $row['source_preset'], 'page_profile' => $row['page_profile'] ) : 'none';
	}

	protected function environment_summary() {
		global $wpdb;
		return array( 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'database' => method_exists( $wpdb, 'db_version' ) ? $wpdb->db_version() : 'unknown' );
	}
}
