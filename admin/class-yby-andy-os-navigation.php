<?php
/**
 * Andy Core Native WordPress L1/L2 Navigation V2.
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
		foreach ( array( 'manage_options', 'andy_core_settings_manage', 'andy_core_leads_view',
			'andy_core_newsletter_view', 'andy_core_mail_view', 'manage_woocommerce', 'edit_posts' ) as $cap ) {
			if ( current_user_can( $cap ) ) { return $cap; }
		}
		return 'manage_options'; // No capability is granted by returning a string.
	}

	/**
	 * The ONLY visible native WordPress second-level items.
	 * Conditional groups are excluded if the module or plugin is missing.
	 */
	public static function sections() {
		$items = array();
		if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) {
			$items['andy-core-leads'] = array( 'Inquiry OS', 'andy_core_leads_view', 'native' );
		}
		if ( YBY_Module_Registry::is_enabled( 'newsletter_os' ) ) {
			$items['andy-core-newsletter'] = array( 'Newsletter OS', 'andy_core_newsletter_view', 'native' );
		}
		if ( YBY_Module_Registry::is_enabled( 'email_os' ) ) {
			$items['andy-core-email'] = array( 'Email OS', 'andy_core_mail_view', 'native' );
		}
		if ( current_user_can( 'andy_core_settings_manage' ) ) {
			$items['andy-core-content'] = array( 'Content OS', 'andy_core_settings_manage', 'content' );
		}
		if ( class_exists( 'Andy_Commerce_Admin' ) ) {
			$items['andy-core-marketing'] = array( 'Marketing OS', 'manage_woocommerce', 'marketing' );
			$items['andy-core-commerce'] = array( 'Commerce OS', 'manage_woocommerce', 'commerce' );
		}
		if ( current_user_can( 'andy_core_settings_manage' ) ) {
			$items['andy-core-system'] = array( 'System & Connectors', 'andy_core_settings_manage', 'system' );
		}
		return $items;
	}

	/** Real, existing backend paths; never infer a route from a label. */
	public static function destination( $slug ) {
		switch ( $slug ) {
			case 'andy-core-content':
				return YBY_Module_Registry::is_enabled( 'business_os' )
					? 'admin.php?page=andy-business' : 'admin.php?page=yby-content';
			case 'andy-core-marketing': return 'admin.php?page=andy-commerce&tab=marketing-promotions';
			case 'andy-core-commerce': return 'admin.php?page=andy-commerce';
			case 'andy-core-system': return 'admin.php?page=yby-core&tab=modules';
			case 'andy-core-leads': return 'admin.php?page=andy-core-leads';
			case 'andy-core-newsletter': return 'admin.php?page=andy-core-newsletter';
			case 'andy-core-email': return 'admin.php?page=andy-core-email';
		}
		return '';
	}

	/**
	 * Only GET navigation may redirect. Original POST handlers and old
	 * direct links remain untouched, including their nonces and capabilities.
	 */
	public static function redirect_entry() {
		if ( ! is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { return; }
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::ROOT === $page ) {
			foreach ( self::sections() as $slug => $group ) {
				if ( ! current_user_can( $group[1] ) ) { continue; }
				$destination = self::destination( $slug );
				if ( $destination ) { wp_safe_redirect( admin_url( $destination ) ); exit; }
			}
			return;
		}
		$groups = self::sections();
		if ( ! isset( $groups[ $page ] ) || 'native' === $groups[ $page ][2] ||
		     ! current_user_can( $groups[ $page ][1] ) ) { return; }
		$destination = self::destination( $page );
		if ( $destination ) { wp_safe_redirect( admin_url( $destination ) ); exit; }
	}

	/**
	 * Keep ONE Andy Core parent. Existing Inquiry/Newsletter/Email handlers
	 * stay registered at their original WordPress hooks; no surrogate pages.
	 */
	public static function consolidate() {
		if ( ! is_admin() ) { return; }
		global $menu, $submenu;
		if ( ! is_array( $menu ) || ! is_array( $submenu ) ) { return; }
		$existing = $submenu[ self::ROOT ] ?? array();
		$groups = self::sections();
		$native = array();
		foreach ( $existing as $item ) {
			if ( isset( $item[2], $groups[ $item[2] ] ) && 'native' === $groups[ $item[2] ][2] ) {
				$item[0] = $groups[ $item[2] ][0];
				$native[ $item[2] ] = $item;
			}
		}

		// Original Content / Commerce page callbacks are NOT replaced.
		// Fail open (keep old first-level) if any authorized route cannot
		// be safely registered as a hidden legacy page.
		foreach ( array( 'yby-content', 'andy-commerce' ) as $legacy_parent ) {
			$root = null;
			foreach ( $menu as $row ) {
				if ( isset( $row[2] ) && $legacy_parent === $row[2] ) { $root = $row; break; }
			}
			if ( null === $root || ! self::transfer_alias( $legacy_parent, $root[0], $root[1], '' ) ) { continue; }
			$children = $submenu[ $legacy_parent ] ?? array();
			$safe = true;
			foreach ( $children as $item ) {
				if ( empty( $item[2] ) || $legacy_parent === $item[2] ) { continue; }
				if ( ! self::transfer_alias( $item[2], $item[0], $item[1], $legacy_parent ) ) {
					$safe = false;
					break;
				}
			}
			if ( $safe ) {
				remove_menu_page( $legacy_parent );
				unset( $submenu[ $legacy_parent ] );
			}
		}

		foreach ( $existing as $item ) {
			$slug = $item[2] ?? '';
			if ( ! $slug || self::ROOT === $slug || isset( $native[ $slug ] ) ) { continue; }
			if ( self::transfer_alias( $slug, $item[0], $item[1], self::ROOT ) ) {
				remove_submenu_page( self::ROOT, $slug );
			}
		}

		// Exactly seven possible native L2 module entries, ordered by Owner.
		$ordered = array();
		foreach ( $groups as $slug => $group ) {
			if ( ! current_user_can( $group[1] ) ) { continue; }
			if ( 'native' === $group[2] ) {
				if ( isset( $native[ $slug ] ) ) { $ordered[] = $native[ $slug ]; }
			} else {
				$hook = add_submenu_page( self::ROOT, $group[0], $group[0],
					$group[1], $slug, array( __CLASS__, 'render_fallback' ) );
				if ( false !== $hook ) {
					$ordered[] = array( $group[0], $group[1], $slug );
				}
			}
		}
		$submenu[ self::ROOT ] = $ordered;
	}

	/** If admin_init redirect is unavailable, show only a safe direct link. */
	public static function render_fallback() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$groups = self::sections();
		if ( ! isset( $groups[ $page ] ) || ! current_user_can( $groups[ $page ][1] ) ) {
			wp_die( esc_html__( 'You do not have permission to use this section.', 'yby-core' ) );
		}
		$target = self::destination( $page );
		echo '<div class="wrap"><h1>' . esc_html( $groups[ $page ][0] ) . '</h1>';
		echo '<p><a class="button button-primary" href="' . esc_url( admin_url( $target ) ) . '">'
			. esc_html__( '打开现有功能', 'yby-core' ) . '</a></p></div>';
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

	/** Only allow existing, verified WordPress routes in right-side L3. */
	public static function tertiary_items( $page ) {
		$items = array();
		$add = static function ( $title, $url, $cap, $module = '' ) use ( &$items ) {
			if ( ! current_user_can( $cap ) ) { return; }
			if ( $module && ! YBY_Module_Registry::is_enabled( $module ) ) { return; }
			$items[] = array( 'title' => $title, 'url' => admin_url( $url ) );
		};
		if ( 'andy-core-leads' === $page ) {
			foreach ( array( 'leads'=>'询盘列表', 'stats'=>'询盘统计' ) as $tab=>$label ) {
				$add( $label, 'admin.php?page=andy-core-leads&tab='.$tab, 'andy_core_leads_view', 'inquiry_os' );
			}
			foreach ( array( 'floating_inquiry'=>'悬浮询盘', 'popup_inquiry'=>'询盘弹窗',
				'shortcode_inquiry'=>'询盘短代码', 'settings'=>'询盘设置', 'notification'=>'询盘通知' ) as $tab=>$label ) {
				if ( current_user_can( 'andy_core_leads_view' ) ) {
					$add( $label, 'admin.php?page=andy-core-leads&tab='.$tab, 'andy_core_settings_manage', 'inquiry_os' );
				}
			}
		} elseif ( 'andy-core-newsletter' === $page ) {
			foreach ( array( 'subscribers'=>'订阅者列表', 'consent'=>'双重确认与退订',
				'popup_subscribe'=>'订阅弹窗', 'shortcode_subscribe'=>'订阅短代码' ) as $tab=>$label ) {
				if ( in_array( $tab, array( 'popup_subscribe','shortcode_subscribe' ), true ) &&
				     ( ! current_user_can( 'manage_options' ) || ! YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) ) { continue; }
				$add( $label, 'admin.php?page=andy-core-newsletter&tab='.$tab,
					'andy_core_newsletter_view', 'newsletter_os' );
			}
		} elseif ( 'andy-core-email' === $page ) {
			$tabs = array( 'overview'=>'邮件总览', 'email_templates'=>'邮件模板中心',
				'transport'=>'发送通道', 'health'=>'系统健康' );
			if ( class_exists( 'YBY_Mail_V2_P6_Dev_Preview' ) &&
			     YBY_Mail_V2_P6_Dev_Preview::eligible_site() && current_user_can( 'manage_options' ) ) {
				$tabs['preview_queue'] = '队列（Dev 预览）';
				$tabs['preview_failures'] = '失败处理（Dev 预览）';
			}
			foreach ( $tabs as $tab=>$label ) {
				if ( 'email_templates' === $tab && ! current_user_can( 'andy_core_settings_manage' ) ) { continue; }
				$add( $label, 'admin.php?page=andy-core-email&tab='.$tab, 'andy_core_mail_view', 'email_os' );
			}
		} elseif ( in_array( $page, array( 'andy-business', 'andy-business-programs',
			'andy-business-applications', 'andy-business-settings', 'yby-content',
			'yby-project-studio', 'yby-docs-os', 'yby-docs-all', 'yby-docs-directory',
			'yby-docs-faq', 'yby-docs-tutorial', 'yby-docs-settings' ), true ) ) {
			foreach ( array( 'andy-business'=>'Business 招商页面', 'andy-business-programs'=>'合作项目展示',
				'andy-business-applications'=>'申请表单与来源', 'andy-business-settings'=>'ERP 连接状态' ) as $slug=>$label ) {
				$add( $label, 'admin.php?page='.$slug, 'andy_core_settings_manage', 'business_os' );
			}
			$add( 'Landing Pages', 'edit.php?post_type=landing_page', 'edit_pages', 'landing_pages' );
			$add( 'Project Studio', 'admin.php?page=yby-project-studio', 'manage_options', 'project_studio' );
			if ( YBY_Module_Registry::is_enabled( 'docs_os' ) ) {
				foreach ( array( 'yby-docs-os'=>'Docs', 'yby-docs-all'=>'全部文档',
					'yby-docs-directory'=>'文档分类', 'yby-docs-faq'=>'FAQ',
					'yby-docs-tutorial'=>'Tutorial', 'yby-docs-settings'=>'Docs 设置' ) as $slug=>$label ) {
					$add( $label, 'admin.php?page='.$slug, 'andy_core_settings_manage', 'docs_os' );
				}
			}
		} elseif ( 'andy-commerce' === $page ) {
			foreach ( array( 'overview'=>'Commerce 总览', 'order-export'=>'Order Export',
				'checkout-shipping'=>'Checkout / Shipping', 'marketing-promotions'=>'Marketing / Promotions',
				'settings'=>'Commerce 设置' ) as $tab=>$label ) {
				$add( $label, 'admin.php?page=andy-commerce&tab='.$tab, 'manage_woocommerce' );
			}
		} elseif ( in_array( $page, array( 'yby-core', 'yby-brand-os', 'yby-social-login' ), true ) ) {
			foreach ( array( 'general'=>'常规设置', 'modules'=>'模块管理', 'addons'=>'Addons',
				'wp-api'=>'连接器', 'system-status'=>'系统状态', 'updates'=>'插件更新' ) as $tab=>$label ) {
				if ( 'wp-api' === $tab && ! YBY_Module_Registry::is_enabled( 'connector' ) ) { continue; }
				$add( $label, 'admin.php?page=yby-core&tab='.$tab, 'andy_core_settings_manage' );
			}
			$add( 'Brand', 'admin.php?page=yby-brand-os', 'manage_options' );
			$add( 'Social Login', 'admin.php?page=yby-social-login', 'manage_options', 'social_login' );
		}
		return $items;
	}

	/** Scoped presentation only. Controllers remain independent. */
	public static function enqueue_tertiary( $hook = '' ) {
		unset( $hook );
		if ( ! is_admin() ) { return; }
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$items = self::tertiary_items( $page );
		if ( ! $items ) { return; }
		wp_enqueue_style( 'andy-core-native-l3-v2',
			YBY_CORE_PLUGIN_URL . 'assets/css/andy-core-native-l3-v2.css',
			array(), YBY_CORE_VERSION );
		wp_enqueue_script( 'andy-core-native-l3-v2',
			YBY_CORE_PLUGIN_URL . 'assets/js/andy-core-native-l3-v2.js',
			array(), YBY_CORE_VERSION, true );
		wp_localize_script( 'andy-core-native-l3-v2', 'AndyCoreNativeL3V2',
			array( 'items' => $items, 'page' => $page ) );
	}

	/**
	 * Existing controllers own every business screen. No fake dashboards.
	 * Root is resolved by admin_init before the browser page is rendered.
	 */
	public static function render_overview() {
		echo '<div class="wrap"><h1>Andy Core</h1><p>'
			. esc_html__( '请在 WordPress 左侧选择有权限的二级模块。', 'yby-core' )
			. '</p></div>';
	}
}
