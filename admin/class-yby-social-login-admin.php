<?php
/**
 * Social Login admin controller.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Social Login overview and Google settings page.
 */
class YBY_Social_Login_Admin {

	/**
	 * Security helper.
	 *
	 * @var YBY_Security
	 */
	protected $security;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->security = new YBY_Security();
	}

	/**
	 * Return the admin page slug.
	 *
	 * @return string
	 */
	public static function page_slug() {
		return 'yby-social-login';
	}

	/**
	 * Register the single Social Login submenu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			__( 'Social Login', 'yby-core' ),
			__( 'Social Login', 'yby-core' ),
			'manage_options',
			self::page_slug(),
			array( $this, 'render_page' )
		);
	}

	/**
	 * Load the existing admin stylesheet only on this page.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( YBY_Project_Studio::menu_slug() . '_page_' . self::page_slug() !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'yby-core-admin',
			YBY_CORE_PLUGIN_URL . 'assets/css/yby-core-admin.css',
			array(),
			YBY_CORE_VERSION
		);
	}

	/**
	 * Render the overview or Google settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! $this->security->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}

		$provider    = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
		$provider    = 'google' === $provider ? 'google' : '';
		$notice      = '';
		$notice_type = 'success';

		if ( '' === $provider && isset( $_POST['yby_social_login_general_submit'] ) ) {
			check_admin_referer( 'yby_social_login_save_general', 'yby_social_login_general_nonce' );

			$raw_all   = wp_unslash( $_POST['yby_social_login_options'] ?? array() );
			$options   = YBY_Social_Login::get_options();
			$raw_value = is_array( $raw_all ) ? ( $raw_all['add_to_login_page'] ?? false ) : false;

			$options['add_to_login_page'] = in_array( $raw_value, array( true, 1, '1', 'on' ), true );
			YBY_Social_Login::save( $options );
			$notice = __( 'General Social Login settings saved.', 'yby-core' );
		}

		if ( 'google' === $provider && isset( $_POST['yby_social_login_submit'] ) ) {
			check_admin_referer( 'yby_social_login_save_google', 'yby_social_login_nonce' );

			$raw_all      = wp_unslash( $_POST['yby_social_login_options'] ?? array() );
			$raw_google   = is_array( $raw_all ) && isset( $raw_all['google'] ) && is_array( $raw_all['google'] ) ? $raw_all['google'] : array();
			$google       = YBY_Social_Login::sanitize_google( $raw_google );
			$raw_client_value   = $raw_google['client_id'] ?? '';
			$raw_redirect_value = $raw_google['redirect_url'] ?? '';
			$raw_client         = is_scalar( $raw_client_value ) ? trim( (string) $raw_client_value ) : '';
			$raw_redirect       = is_scalar( $raw_redirect_value ) ? trim( (string) $raw_redirect_value ) : '';
			$errors       = array();

			if ( '' !== $raw_client && '' === $google['client_id'] ) {
				$errors[] = __( 'Enter a valid Google client ID ending in .apps.googleusercontent.com.', 'yby-core' );
			}

			if ( isset( $raw_google['enabled'] ) && in_array( $raw_google['enabled'], array( true, 1, '1', 'on' ), true ) && '' === $google['client_id'] ) {
				$errors[] = __( 'A valid client ID is required before Google Login can be enabled.', 'yby-core' );
			}

			if ( empty( $errors ) ) {
				$options           = YBY_Social_Login::get_options();
				$options['google'] = $google;
				YBY_Social_Login::save( $options );
				$notice = __( 'Google Login settings saved.', 'yby-core' );

				if ( '' !== $raw_redirect && '' === $google['redirect_url'] ) {
					$notice      = __( 'Settings saved. The redirect was not on this site, so the homepage will be used.', 'yby-core' );
					$notice_type = 'warning';
				}
			} else {
				$notice      = implode( ' ', array_unique( $errors ) );
				$notice_type = 'error';
			}
		}

		$options        = YBY_Social_Login::get_options();
		$google_options = $options['google'];
		$roles          = YBY_Social_Login::registered_roles();
		$allowed_roles  = YBY_Social_Login::allowed_registration_roles();

		include YBY_CORE_PLUGIN_DIR . 'admin/views/social-login-page.php';
	}
}
