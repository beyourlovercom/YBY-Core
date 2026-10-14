<?php
/**
 * Andy OS Unified Admin Navigation V1.
 *
 * Read-only navigation composition. Existing handlers, capabilities and
 * independent plugin lifecycles remain authoritative.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Andy_OS_Navigation {
	const ROOT = 'yby-os';

	/**
	 * WordPress menus accept a single capability. Choose a capability the
	 * current user already has; each child checks its original capability.
	 */
	public static function root_capability() {
		foreach ( array( 'manage_options', 'andy_core_settings_manage', 'manage_woocommerce', 'andy_core_leads_view' ) as $cap ) {
			if ( current_user_can( $cap ) ) { return $cap; }
		}
		return 'manage_options';
	}

	private static function section_capability( $slug ) {
		switch ( $slug ) {
			case 'andy-os-content': return 'andy_core_settings_manage';
			case 'andy-os-inquiry': return 'andy_core_leads_view';
			case 'andy-os-commerce': return 'manage_woocommerce';
			case 'andy-os-settings': return 'manage_options';
			case 'andy-os-marketing':
				return current_user_can( 'andy_core_settings_manage' ) ? 'andy_core_settings_manage' : 'manage_woocommerce';
			default: return self::root_capability();
		}
	}

	public static function sections() {
		return array(
			'andy-os-overview' => '总览',
			'andy-os-content' => 'Content 内容管理',
			'andy-os-inquiry' => 'Forms & Inquiry',
			'andy-os-marketing' => 'Marketing 营销管理',
			'andy-os-commerce' => 'Commerce 交易配置',
			'andy-os-settings' => '系统与连接',
		);
	}

	/**
	 * Migrate *registrations*, not just CSS visibility. WordPress must keep
	 * its legacy page hooks callable when a first-level menu is removed.
	 */
	public static function consolidate() {
		if ( ! is_admin() ) { return; }
		global $menu, $submenu;
		if ( ! is_array( $menu ) || ! is_array( $submenu ) ) { return; }

		// Capture existing Core routes before adding the six visible sections.
		// Preserve their handlers with hidden registered routes below.
		$core_existing = isset( $submenu[ self::ROOT ] ) && is_array( $submenu[ self::ROOT ] )
			? $submenu[ self::ROOT ] : array();
		foreach ( self::sections() as $slug => $title ) {
			$cap = self::section_capability( $slug );
			add_submenu_page( self::ROOT, $title, $title, $cap, $slug, array( __CLASS__, 'render_section' ) );
		}

		// A missing plugin leaves no matching top-level row and is untouched.
		foreach ( array( 'yby-content', 'andy-commerce' ) as $legacy_parent ) {
			$root = null;
			foreach ( $menu as $row ) {
				if ( isset( $row[2] ) && $legacy_parent === $row[2] ) { $root = $row; break; }
			}
			if ( null === $root ) { continue; }
			if ( ! self::transfer_alias( $legacy_parent, $root[0], $root[1], '' ) ) { continue; }

			// Preserve all registered child routes under their original slugs.
			$children = isset( $submenu[ $legacy_parent ] ) && is_array( $submenu[ $legacy_parent ] )
				? $submenu[ $legacy_parent ] : array();
			$all_safe = true;
			foreach ( $children as $item ) {
				if ( empty( $item[2] ) || $legacy_parent === $item[2] ) { continue; }
				if ( ! self::transfer_alias( $item[2], $item[0], $item[1], $legacy_parent ) ) {
					$all_safe = false;
					break;
				}
			}
			if ( $all_safe ) {
				remove_menu_page( $legacy_parent );
				// Invisible routes remain registered under WordPress' empty
				// parent; discard old menu inventory to prevent duplicate
				// parent resolution for legacy admin.php?page=... links.
				unset( $submenu[ $legacy_parent ] );
			} // Fail open to an extra menu if a legacy route cannot be preserved.
		}

		// Keep the WordPress sidebar compact: only the six section pages
		// are visible. Existing Core child-page links remain callable,
		// capability-gated and accessible from the section landing pages.
		foreach ( $core_existing as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( '' === $slug || self::ROOT === $slug || isset( self::sections()[ $slug ] ) ) { continue; }
			if ( self::transfer_alias( $slug, $item[0], $item[1], self::ROOT ) ) {
				remove_submenu_page( self::ROOT, $slug );
			}
		}
	}

	private static function transfer_alias( $slug, $label, $capability, $old_parent ) {
		if ( ! current_user_can( $capability ) ) { return true; }
		$old_hook = get_plugin_page_hookname( $slug, $old_parent );
		if ( ! is_string( $old_hook ) || '' === $old_hook ) { return false; }

		// WordPress built-in editor/taxonomy links (e.g. edit-tags.php) are
		// ordinary routes, not callback-driven plugin screens. Copy their
		// menu link without requiring a hook. Never relax this for unknown
		// plugin route slugs or actions.
		$is_native_link = (bool) preg_match( '/^(?:edit|edit-tags|post-new)\\.php\\?/', $slug );
		if ( ! has_action( $old_hook ) && ! $is_native_link ) { return false; }
		// Keep old handlers as invisible WordPress admin routes. A top-level
		// alias can retain its original hook identity after relocation.
		// Adding a wrapper to that same hook would recurse indefinitely.
		// WordPress derives a hidden page hook from the current request.
		// Make it deterministic on every admin.php?page=... request, not
		// only when the owner opens the dashboard first.
		global $plugin_page;
		$saved_plugin_page = $plugin_page ?? null;
		$plugin_page = null;
		try {
			$new_hook = add_submenu_page(
				null,
				wp_strip_all_tags( (string) $label ),
				wp_strip_all_tags( (string) $label ),
				$capability,
				$slug,
				null
			);
		} finally {
			$plugin_page = $saved_plugin_page;
		}
		if ( false === $new_hook ) { return false; }
		if ( $new_hook !== $old_hook ) {
			add_action( $new_hook, static function () use ( $old_hook ) {
				do_action( $old_hook );
			} );
		}
		return true;
	}

	private static function cards_for( $section ) {
		$cards = array(
			'andy-os-content' => array(
				array( 'Business 招商页面', 'andy-business', 'andy_core_settings_manage', 'business_os' ),
				array( '合作项目展示', 'andy-business-programs', 'andy_core_settings_manage', 'business_os' ),
				array( '落地页与内容', 'yby-content', 'andy_core_settings_manage', '' ),
			),
			'andy-os-inquiry' => array(
				array( 'Inquiry OS（过渡入口）', 'andy-core-leads', 'andy_core_leads_view', 'inquiry_os' ),
			),
			'andy-os-marketing' => array(
				array( '营销活动与折扣草稿', 'andy-commerce&tab=marketing-promotions', 'manage_woocommerce', '' ),
			),
			'andy-os-commerce' => array(
				array( 'Commerce 总览', 'andy-commerce', 'manage_woocommerce', '' ),
				array( 'Checkout / Shipping', 'andy-commerce&tab=checkout-shipping', 'manage_woocommerce', '' ),
				array( 'Order Export', 'andy-commerce&tab=order-export', 'manage_woocommerce', '' ),
				array( 'Marketing / Promotions', 'andy-commerce&tab=marketing-promotions', 'manage_woocommerce', '' ),
			),
			'andy-os-settings' => array(
				array( 'Andy Core 配置', 'yby-core&tab=modules', 'manage_options', '' ),
			),
		);
		return isset( $cards[ $section ] ) ? $cards[ $section ] : array();
	}

	public static function render_overview() {
		self::render_ui( 'andy-os-overview' );
	}

	public static function render_section() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'andy-os-overview';
		self::render_ui( $page );
	}

	private static function render_ui( $section ) {
		$sections = self::sections();
		if ( ! isset( $sections[ $section ] ) || ! current_user_can( self::section_capability( $section ) ) ) {
			wp_die( esc_html__( 'You do not have permission to access this Andy OS section.', 'yby-core' ), '', array( 'response' => 403 ) );
			return;
		}
		$title = $sections[ $section ];
		echo '<div class="wrap andy-os-unified"><h1>Andy OS · ' . esc_html( $title ) . '</h1>';
		echo '<p>' . esc_html__( '一个后台入口，两个独立插件。以下链接继续使用各功能既有权限与页面地址。', 'yby-core' ) . '</p>';
		echo '<nav class="nav-tab-wrapper" aria-label="Andy OS sections">';
		foreach ( $sections as $slug => $label ) {
			if ( ! current_user_can( self::section_capability( $slug ) ) ) { continue; }
			$url = admin_url( 'admin.php?page=' . $slug );
			echo '<a class="nav-tab' . ( $slug === $section ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav><div class="andy-os-unified__cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;max-width:1050px;margin:20px 0">';
		$groups = 'andy-os-overview' === $section ? array_keys( $sections ) : array( $section );
		foreach ( $groups as $group ) {
			if ( ! current_user_can( self::section_capability( $group ) ) ) { continue; }
			foreach ( self::cards_for( $group ) as $card ) {
				list( $name, $path, $cap, $module ) = $card;
				if ( ! current_user_can( $cap ) ) { continue; }
				if ( '' !== $module && ! YBY_Module_Registry::is_enabled( $module ) ) { continue; }
				if ( 0 === strpos( $path, 'andy-commerce' ) && ! class_exists( 'Andy_Commerce_Admin' ) ) { continue; }
				// All destinations are fixed, predefined administrator routes.
				$url = admin_url( 'admin.php?page=' . $path );
				echo '<a href="' . esc_url( $url ) . '" style="display:block;background:white;color:#222;border:1px solid #e6e8e6;border-radius:10px;padding:18px;text-decoration:none">';
				echo '<strong>' . esc_html( $name ) . '</strong><p style="color:#68736e;margin:9px 0 0">' . esc_html( $sections[ $group ] ) . ' · ' . esc_html__( '打开现有模块', 'yby-core' ) . '</p></a>';
			}
		}
		echo '</div><p class="description">' . esc_html__( '仅统一导航，不执行询盘、订单、付款或邮件操作。', 'yby-core' ) . '</p></div>';
	}
}
