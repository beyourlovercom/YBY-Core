<?php
/**
 * Independent Login OS V1 admin entry. Lives in the existing system L3 rail,
 * directly below Social Login; never replaces the existing Social Login UI.
 */
defined( 'ABSPATH' ) || exit;

final class YBY_Login_OS_Admin {
	public static function page_slug() {
		return 'yby-login-os';
	}

	public function add_admin_menu() {
		// Registered under the same original root as Social Login. Andy Core
		// consolidates the WP L2 menu; the visible entry is L3 only.
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			'后台登录',
			'后台登录',
			'manage_options',
			self::page_slug(),
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( class_exists( 'YBY_Andy_Admin_Shell_V22' ) ) {
			YBY_Andy_Admin_Shell_V22::render(
				self::page_slug(), 'Andy Core · 后台登录',
				array( $this, 'render_original' )
			);
			return;
		}
		$this->render_original();
	}

	public function render_original() {
		if ( ! current_user_can( 'manage_options' ) ||
			! ( new YBY_Security() )->can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'yby-core' ) );
		}
		$tab = isset( $_GET['tab'] ) && is_scalar( $_GET['tab'] )
			? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		if ( ! in_array( $tab, array( 'general', 'appearance', 'url', 'safety' ), true ) ) {
			$tab = 'general';
		}

		$notice = '';
		$notice_type = 'success';
		if ( in_array( $tab, array( 'appearance', 'url' ), true ) &&
			isset( $_POST['yby_login_os_submit'] ) ) {
			check_admin_referer( 'yby_login_os_save_' . $tab, 'yby_login_os_nonce' );
			$raw = isset( $_POST['yby_login_os'] ) ? wp_unslash( $_POST['yby_login_os'] ) : array();
			$result = YBY_Login_OS::save_section( $tab, $raw );
			if ( is_wp_error( $result ) ) {
				$notice = $result->get_error_message();
				$notice_type = 'error';
			} else {
				$notice = __( 'Login OS settings saved. Verify the secure login URL before closing your admin session.', 'yby-core' );
			}
		}
		$options = YBY_Login_OS::get_options();
		$conflicts = YBY_Login_OS::conflicting_plugins();
		$page_url = admin_url( 'admin.php?page=' . self::page_slug() );
		include YBY_CORE_PLUGIN_DIR . 'admin/views/login-os-page.php';
	}
}
