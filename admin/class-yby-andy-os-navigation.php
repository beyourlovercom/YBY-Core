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

	/** Legacy-only aliases registered in this request; not other plugins' pages. */
	private static $hidden_aliases = array();

	/**
	 * WordPress menus accept a single capability. Choose a capability the
	 * current user already has; each child checks its original capability.
	 */
	public static function root_capability() {
		foreach ( array( 'manage_options', 'andy_core_settings_manage', 'andy_core_leads_view',
			'andy_core_newsletter_view', 'andy_core_mail_view', 'manage_woocommerce' ) as $cap ) {
			if ( current_user_can( $cap ) ) { return $cap; }
		}
		if ( YBY_Module_Registry::is_enabled( 'landing_pages' ) && current_user_can( 'edit_pages' ) ) {
			return 'edit_pages';
		}
		if ( YBY_Module_Registry::is_enabled( 'docs_os' ) && current_user_can( 'edit_posts' ) ) {
			return 'edit_posts';
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
			$items['andy-core-leads'] = array( '询盘', 'andy_core_leads_view', 'native' );
		}
		if ( YBY_Module_Registry::is_enabled( 'newsletter_os' ) ) {
			$items['andy-core-newsletter'] = array( '订阅', 'andy_core_newsletter_view', 'native' );
		}
		if ( YBY_Module_Registry::is_enabled( 'email_os' ) ) {
			$items['andy-core-email'] = array( '邮件', 'andy_core_mail_view', 'native' );
		}
		$content_cap = '';
		foreach ( array( 'andy_core_settings_manage', 'edit_pages', 'edit_posts' ) as $cap ) {
			if ( ! current_user_can( $cap ) ) { continue; }
			if ( 'andy_core_settings_manage' === $cap ||
			     ( 'edit_pages' === $cap && YBY_Module_Registry::is_enabled( 'landing_pages' ) ) ||
			     ( 'edit_posts' === $cap && YBY_Module_Registry::is_enabled( 'docs_os' ) ) ) {
				$content_cap = $cap;
				break;
			}
		}
		if ( $content_cap ) {
			$items['andy-core-content'] = array( '内容', $content_cap, 'content' );
		}
		if ( ( class_exists( 'Andy_Campaign_Lite' ) && class_exists( 'WooCommerce' ) )
			|| class_exists( 'Andy_Commerce_Admin' ) ) {
			$items['andy-core-marketing'] = array( '营销', 'manage_woocommerce', 'marketing' );
		}
		if ( class_exists( 'Andy_Commerce_Admin' ) ) {
			$items['andy-core-commerce'] = array( '交易', 'manage_woocommerce', 'commerce' );
		}
		if ( current_user_can( 'andy_core_settings_manage' ) ) {
			$items['andy-core-system'] = array( '系统', 'andy_core_settings_manage', 'system' );
		}
		return $items;
	}

	/** Real, existing backend paths; never infer a route from a label. */
	public static function destination( $slug ) {
		switch ( $slug ) {
			case 'andy-core-content':
				if ( YBY_Module_Registry::is_enabled( 'landing_pages' ) && current_user_can( 'edit_pages' ) ) {
					return 'edit.php?post_type=landing_page';
				}
				if ( current_user_can( 'andy_core_settings_manage' ) ) {
					if ( YBY_Module_Registry::is_enabled( 'business_os' ) ) {
						return 'admin.php?page=andy-business';
					}
					// Content L2 must open a real enabled workbench, not a card-only hub.
					if ( YBY_Module_Registry::is_enabled( 'docs_os' ) ) {
						return 'admin.php?page=yby-docs-os';
					}
					return 'admin.php?page=yby-content';
				}
				if ( YBY_Module_Registry::is_enabled( 'landing_pages' ) && current_user_can( 'edit_pages' ) ) {
					return 'edit.php?post_type=landing_page';
				}
				if ( YBY_Module_Registry::is_enabled( 'docs_os' ) && current_user_can( 'edit_posts' ) ) {
					return 'admin.php?page=yby-docs-all';
				}
				return '';
			case 'andy-core-marketing':
				return class_exists( 'Andy_Campaign_Lite' ) && class_exists( 'WooCommerce' )
					? 'admin.php?page=andy-campaign&tab=campaigns'
					: 'admin.php?page=andy-commerce&tab=marketing-promotions';
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

		// V3: exactly seven Chinese native L2 entries, ordered by Owner.
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
		// Admin permissions and legacy route hooks must resolve before we clean
		// the hidden alias for menu rendering. admin_head runs after WordPress
		// page authorization, but before wp-admin/menu-header.php.
		add_action( 'admin_head', array( __CLASS__, 'preserve_native_parent' ), 1000 );
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
		self::$hidden_aliases[ $slug ] = true;
		if ( $new_hook !== $old_hook ) {
			add_action( $new_hook, static function () use ( $old_hook ) {
				do_action( $old_hook );
			} );
		}
		return true;
	}

	/**
	 * WordPress menu-header.php applies parent_file/submenu_file, then calls
	 * get_admin_page_parent() again. That second resolver sees our hidden
	 * add_submenu_page(null, ...) alias and overwrites the correct L1 parent
	 * with the empty hidden-menu parent. L2 is selected but remains collapsed.
	 *
	 * At admin_head page authorization has already run. Remove only the
	 * current Andy Core legacy alias from the hidden *menu list*; preserve
	 * registered hooks, access checks, original callbacks and all unrelated
	 * plugins' hidden pages. Native WordPress then renders wp-menu-open.
	 */
	public static function preserve_native_parent() {
		if ( ! is_admin() ) { return; }
		$group = self::active_second_level();
		$sections = self::sections();
		if ( ! $group || ! isset( $sections[ $group ] ) ||
			 ! current_user_can( $sections[ $group ][1] ) ) { return; }

		global $submenu;
		if ( empty( $submenu[''] ) || ! is_array( $submenu[''] ) ) { return; }
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! $page ) {
			$post_type = isset( $_GET['post_type'] ) && is_scalar( $_GET['post_type'] )
				? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
			global $pagenow;
			if ( $post_type && in_array( $pagenow, array( 'edit.php', 'edit-tags.php', 'post-new.php' ), true ) ) {
				$page = $pagenow . '?post_type=' . $post_type;
			}
		}
		if ( ! $page || ! isset( self::$hidden_aliases[ $page ] ) ) { return; }
		foreach ( $submenu[''] as $key => $item ) {
			if ( isset( $item[2] ) && $page === $item[2] ) {
				unset( $submenu[''][ $key ] );
			}
		}
	}

	/** Only allow existing, verified WordPress routes in right-side L3. */
	public static function tertiary_items( $page ) {
		$items = array();
		$add = static function ( $title, $url, $cap, $module = '', $group = '' ) use ( &$items ) {
			if ( ! current_user_can( $cap ) ) { return; }
			if ( $module && ! YBY_Module_Registry::is_enabled( $module ) ) { return; }
			$item = array( 'title' => $title, 'url' => admin_url( $url ) );
			if ( '' !== $group ) { $item['group'] = $group; }
			$items[] = $item;
		};
		if ( 'andy-core-leads' === $page ) {
			foreach ( array( 'leads'=>'列表', 'stats'=>'统计' ) as $tab=>$label ) {
				$add( $label, 'admin.php?page=andy-core-leads&tab='.$tab, 'andy_core_leads_view', 'inquiry_os' );
			}
			// The mail failure inbox is an independent, high-visibility L3 entry.
			// Its count is computed only for authorized lead managers.
			if ( current_user_can( 'andy_core_leads_view' ) &&
				current_user_can( 'andy_core_settings_manage' ) ) {
				$mail_title = '通知记录';
				if ( class_exists( 'YBY_Lead_Notification_Outbox' ) &&
					class_exists( 'YBY_Database' ) &&
					YBY_Database::notification_table_exists() ) {
					$attention = YBY_Lead_Notification_Outbox::actionable_count() +
						YBY_Lead_Notification_Outbox::unqueued_count();
					if ( $attention > 0 ) {
						$mail_title .= '（' . (int) $attention . ' 待处理）';
					}
				}
				$add( $mail_title, 'admin.php?page=andy-core-leads&tab=mail_delivery',
					'andy_core_settings_manage', 'inquiry_os' );
			}
			foreach ( array( 'floating_inquiry'=>'悬浮询盘', 'popup_inquiry'=>'弹窗',
				'shortcode_inquiry'=>'短代码', 'settings'=>'设置', 'notification'=>'通知' ) as $tab=>$label ) {
				if ( current_user_can( 'andy_core_leads_view' ) ) {
					$add( $label, 'admin.php?page=andy-core-leads&tab='.$tab, 'andy_core_settings_manage', 'inquiry_os' );
				}
			}
		} elseif ( 'andy-core-newsletter' === $page ) {
			foreach ( array( 'subscribers'=>'订阅用户', 'consent'=>'确认退订',
				'popup_subscribe'=>'订阅弹窗', 'shortcode_subscribe'=>'短代码' ) as $tab=>$label ) {
				if ( in_array( $tab, array( 'popup_subscribe','shortcode_subscribe' ), true ) &&
				     ( ! current_user_can( 'manage_options' ) || ! YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) ) { continue; }
				$add( $label, 'admin.php?page=andy-core-newsletter&tab='.$tab,
					'andy_core_newsletter_view', 'newsletter_os' );
			}
		} elseif ( 'andy-core-email' === $page ) {
			$tabs = array( 'overview'=>'总览', 'email_templates'=>'模板',
				'transport'=>'通道', 'health'=>'健康' );
			if ( class_exists( 'YBY_Mail_V2_P6_Dev_Preview' ) &&
			     YBY_Mail_V2_P6_Dev_Preview::eligible_site() && current_user_can( 'manage_options' ) ) {
				$tabs['preview_queue'] = '队列预览';
				$tabs['preview_failures'] = '失败预览';
				$tabs['mysql_snapshot'] = '验证快照';
			}
			// Preserve independently deployed P8 setup and read-only MySQL views.
			if ( current_user_can( 'manage_options' ) &&
			     class_exists( 'YBY_Mail_V2_P8_Setup' ) && YBY_Mail_V2_P8_Setup::available() ) {
				$tabs['p8_setup'] = '数据库连接';
			}
			if ( current_user_can( 'manage_options' ) &&
			     class_exists( 'YBY_Mail_V2_P8_Readonly' ) && YBY_Mail_V2_P8_Readonly::configured() ) {
				$url = YBY_Mail_V2_P8_Readonly::url();
				if ( is_string( $url ) && '' !== $url ) {
					$items[] = array( 'title' => '实时只读', 'url' => $url );
				}
			}
			foreach ( $tabs as $tab=>$label ) {
				if ( 'email_templates' === $tab && ! current_user_can( 'andy_core_settings_manage' ) ) { continue; }
				$add( $label, 'admin.php?page=andy-core-email&tab='.$tab, 'andy_core_mail_view', 'email_os' );
			}
		} elseif ( in_array( $page, array( 'andy-business', 'andy-business-programs',
			'andy-business-applications', 'andy-business-settings', 'yby-content',
			'yby-project-studio', 'yby-docs-os', 'yby-docs-all', 'yby-docs-directory',
			'yby-docs-faq', 'yby-docs-tutorial', 'yby-docs-settings' ), true ) ) {

			// V3 Owner-approved Content L3, existing WordPress controllers only.
			if ( YBY_Module_Registry::is_enabled( 'docs_os' ) ) {
				foreach ( array( 'yby-docs-os' => array('文档总览','andy_core_settings_manage'),
					'yby-docs-all' => array('全部文档','edit_posts'),
					'yby-docs-directory' => array('文档分类','manage_categories'),
					'yby-docs-faq' => array('常见问题 FAQ','edit_posts'),
					'yby-docs-tutorial' => array('教程 Tutorial','edit_posts'),
					'yby-docs-settings' => array('文档设置','andy_core_settings_manage') ) as $slug=>$info ) {
					$add( $info[0], 'admin.php?page='.$slug, $info[1], 'docs_os', '文档' );
				}
				$add( '文档标签', 'edit-tags.php?taxonomy=doc_tag&post_type=docs', 'manage_categories', 'docs_os', '文档' );
			}
			foreach ( array( 'andy-business'=>'招商总览','andy-business-programs'=>'合作项目',
				'andy-business-applications'=>'申请来源','andy-business-settings'=>'ERP 连接' ) as $slug=>$label ) {
				$add( $label, 'admin.php?page='.$slug, 'andy_core_settings_manage', 'business_os', '招商' );
			}
			$add( '全部落地页', 'edit.php?post_type=landing_page', 'edit_pages', 'landing_pages', '落地页' );
			$add( '新建落地页', 'post-new.php?post_type=landing_page', 'edit_pages', 'landing_pages', '落地页' );
			$add( '项目列表', 'edit.php?post_type=yby_project', 'edit_posts', 'project_studio', '项目' );
			$add( '项目工作台', 'admin.php?page=yby-project-studio', 'manage_options', 'project_studio', '项目' );
		} elseif ( 'andy-campaign' === $page && class_exists( 'Andy_Campaign_Lite' ) ) {
			foreach ( array( 'campaigns'=>'活动列表', 'logs'=>'执行记录', 'settings'=>'系统设置' ) as $tab=>$label ) {
				$add( $label, 'admin.php?page=andy-campaign&tab='.$tab, 'manage_woocommerce' );
			}
		} elseif ( 'andy-commerce' === $page ) {
			foreach ( array( 'overview'=>'总览', 'order-export'=>'订单导出',
				'checkout-shipping'=>'结账运费', 'marketing-promotions'=>'促销',
				'settings'=>'设置' ) as $tab=>$label ) {
				$add( $label, 'admin.php?page=andy-commerce&tab='.$tab, 'manage_woocommerce' );
			}
		} elseif ( in_array( $page, array( 'yby-core', 'yby-brand-os', 'yby-social-login' ), true ) ) {
			foreach ( array( 'general'=>'常规', 'modules'=>'模块', 'addons'=>'扩展',
				'wp-api'=>'接口', 'system-status'=>'状态', 'updates'=>'更新' ) as $tab=>$label ) {
				if ( 'wp-api' === $tab && ! YBY_Module_Registry::is_enabled( 'connector' ) ) { continue; }
				$add( $label, 'admin.php?page=yby-core&tab='.$tab, 'andy_core_settings_manage' );
			}
			if ( YBY_Module_Registry::is_enabled( 'article_toc' ) ) {
				$add( '文章目录', 'admin.php?page=yby-core&tab=article-toc', 'andy_core_settings_manage' );
			}
			$add( '品牌', 'admin.php?page=yby-brand-os', 'manage_options' );
			$add( '社交登录', 'admin.php?page=yby-social-login', 'manage_options', 'social_login' );
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

	/** Keep the native WordPress L2 group open for old L3 deep links. */
	public static function active_second_level() {
		$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] )
			? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'andy-core-leads' === $page ) { return 'andy-core-leads'; }
		if ( 'andy-core-newsletter' === $page ) { return 'andy-core-newsletter'; }
		if ( 'andy-core-email' === $page ) { return 'andy-core-email'; }
		if ( 'andy-campaign' === $page && class_exists( 'Andy_Campaign_Lite' ) ) {
			return 'andy-core-marketing';
		}
		if ( 'andy-commerce' === $page ) {
			$tab = isset( $_GET['tab'] ) && is_scalar( $_GET['tab'] )
				? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
			return 'marketing-promotions' === $tab && ! class_exists( 'Andy_Campaign_Lite' )
				? 'andy-core-marketing' : 'andy-core-commerce';
		}
		if ( in_array( $page, array( 'andy-business','andy-business-programs',
			'andy-business-applications','andy-business-settings','andy-business-pages',
			'andy-business-partners','andy-business-catalog','andy-business-fulfillment',
			'yby-content','yby-project-studio','yby-docs-os','yby-docs-all',
			'yby-docs-directory','yby-docs-editor','yby-docs-faq',
			'yby-docs-tutorial','yby-docs-settings' ), true ) ) {
			return 'andy-core-content';
		}
		if ( in_array( $page, array( 'yby-core','yby-brand-os','yby-social-login' ), true ) ) {
			return 'andy-core-system';
		}
		$post_type = isset( $_GET['post_type'] ) && is_scalar( $_GET['post_type'] )
			? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( ! $post_type && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( absint( $_GET['post'] ) );
		}
		if ( 'landing_page' === $post_type || 'yby_project' === $post_type ||
		     'docs' === $post_type ) { return 'andy-core-content'; }
		return '';
	}

	public static function filter_parent_file( $parent_file ) {
		$group = self::active_second_level();
		$sections = self::sections();
		return $group && isset( $sections[ $group ] ) && current_user_can( $sections[ $group ][1] )
			? self::ROOT : $parent_file;
	}

	public static function filter_submenu_file( $submenu_file ) {
		$group = self::active_second_level();
		$sections = self::sections();
		return $group && isset( $sections[ $group ] ) && current_user_can( $sections[ $group ][1] )
			? $group : $submenu_file;
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
