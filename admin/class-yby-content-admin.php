<?php
/**
 * Canonical top-level shell for Andy-owned page/content products.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Content_Admin {
	const MENU_SLUG = 'yby-content';
	const CAPABILITY = 'andy_core_settings_manage';

	public function add_admin_menu() {
		add_menu_page(
			__( 'Andy Content', 'yby-core' ),
			__( 'Andy Content', 'yby-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_overview' ),
			'dashicons-layout',
			21
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Andy Content Overview', 'yby-core' ),
			__( 'Overview', 'yby-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_overview' )
		);
	}

	public function render_overview() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access Andy Content.', 'yby-core' ) );
		}

		$modules = array();
		foreach ( YBY_Module_Registry::modules() as $id => $module ) {
			if ( self::MENU_SLUG !== ( $module['admin_parent'] ?? '' ) ) { continue; }
			$modules[ $id ] = $module;
		}

		include YBY_CORE_PLUGIN_DIR . 'admin/views/content-overview.php';
	}
}
