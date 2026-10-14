<?php
/**
 * Business OS V1 B1: read-only admin navigation and program shell.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Business_OS_Admin {
	const ROOT_SLUG = 'andy-business';

	/**
	 * Ordered admin pages; all B1 pages are read-only.
	 *
	 * @return array<string,string>
	 */

	public static function pages() {
		return array(
			self::ROOT_SLUG                 => 'Business 招商页面',
			'andy-business-programs'       => '合作项目展示',
			'andy-business-applications'   => '申请表单与来源',
			'andy-business-settings'       => 'ERP 连接状态',
		);
	}

	/** Permission-gated legacy URLs, not visible menu entries. */
	public static function legacy_pages() {
		return array(
			'andy-business-pages'          => '招商页面（原入口）',
			'andy-business-partners'       => '合作客户（ERP 管理）',
			'andy-business-catalog'        => '产品与报价（ERP 管理）',
			'andy-business-fulfillment'    => '仓储与发货（ERP 管理）',
		);
	}

	/**
	 * Static product-program vocabulary, not customer or ERP data.
	 *
	 * @return array<string,string>
	 */
	public static function programs() {
		return array(
			'Wholesale'           => '批量采购、样品支持及阶梯批发报价。',
			'Dropshipping'        => '按授权仓库及 SKU 提供一件代发服务。',
			'Retail Partners'     => '线下零售展示、销售素材及产品培训支持。',
			'Distributors'        => '区域分销申请、资质审核及合作协议。',
			'OEM / Private Label' => '产品及包装定制合作申请。',
			'Corporate Orders'    => '企业项目与活动采购合作。',
		);
	}

	/**
	 * Admin-facing B2B program names (Chinese-first; stable English IDs).
	 *
	 * @return array<string,string>
	 */
	public static function program_labels() {
		return array(
			'Wholesale'           => '批发采购（Wholesale）',
			'Dropshipping'        => '一件代发（Dropshipping）',
			'Retail Partners'     => '零售门店合作（Retail Partners）',
			'Distributors'        => '区域经销合作（Distributors）',
			'OEM / Private Label' => '品牌定制（OEM / Private Label）',
			'Corporate Orders'    => '企业与活动采购（Corporate Orders）',
		);
	}

	/**
	 * Fail closed if the module, site or permission gate is absent.
	 *
	 * @return bool
	 */
	public static function can_access() {
		return YBY_Module_Registry::is_enabled( YBY_Business_OS_Module::MODULE_ID )
			&& YBY_Business_OS_Module::is_site_eligible()
			&& current_user_can( 'andy_core_settings_manage' );
	}


	/** Place website Business configuration under existing Andy Content. */
	public static function register_admin_menu( $module = array() ) {
		unset( $module );
		if ( ! self::can_access() ) { return; }
		foreach ( self::pages() as $slug => $label ) {
			add_submenu_page(
				YBY_Content_Admin::MENU_SLUG,
				__( $label, 'yby-core' ),
				__( $label, 'yby-core' ),
				'andy_core_settings_manage',
				$slug,
				array( __CLASS__, 'render_page' )
			);
		}
		foreach ( self::legacy_pages() as $slug => $label ) {
			add_submenu_page(
				null,
				__( $label, 'yby-core' ),
				__( $label, 'yby-core' ),
				'andy_core_settings_manage',
				$slug,
				array( __CLASS__, 'render_page' )
			);
		}
	}

	/**
	 * Load scoped styling only on Business OS routes.
	 *
	 * @param string $hook_suffix WordPress admin page hook.
	 * @param array  $module Registry metadata, unused.
	 * @return bool
	 */
	public static function is_business_screen( $hook_suffix, $module = array() ) {
		unset( $hook_suffix, $module );
		if ( ! self::can_access() ) { return false; }
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		return isset( self::pages()[ $page ] ) || isset( self::legacy_pages()[ $page ] );
	}

	/**
	 * Enqueue the isolated admin stylesheet, not global WP or public assets.
	 *
	 * @param string $hook_suffix Page hook, unused.
	 * @param array  $module Registry metadata, unused.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix, $module = array() ) {
		unset( $hook_suffix, $module );
		if ( ! self::is_business_screen( '' ) ) { return; }
		wp_enqueue_style(
			'andy-business-os-admin',
			YBY_CORE_PLUGIN_URL . 'assets/css/yby-business-os-admin.css',
			array(),
			YBY_CORE_VERSION
		);
	}

	/**
	 * Render only static program descriptors and safe integration state.
	 * No local pricing, applications, ERP calls, customer data or writes.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! self::can_access() ) {
			wp_die( esc_html__( '你没有权限查看 Business OS。', 'yby-core' ), '', array( 'response' => 403 ) );
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::ROOT_SLUG;
		$pages = self::pages();
		$legacy = self::legacy_pages();
		if ( ! isset( $pages[ $page ] ) && ! isset( $legacy[ $page ] ) ) {
			wp_die( esc_html__( '未找到该 Business OS 页面。', 'yby-core' ), '', array( 'response' => 404 ) );
			return;
		}
		echo '<div class="wrap andy-business-os">';
		echo '<p class="andy-business-os__eyebrow">BEYOURLOVER · 商业合作管理</p>';
		echo '<h1>' . esc_html( $pages[ $page ] ?? $legacy[ $page ] ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Andy Content 仅管理招商页面和来源；B2B / B2C 询盘与客户由 ERP 统一管理。', 'yby-core' ) . '</p>';


		if ( isset( $legacy[ $page ] ) ) {
			echo '<section class="andy-business-os__card"><h2>' . esc_html__( '原 Business 后台入口已调整', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html__( '不再在 WordPress 管理真实客户、报价和履约；业务工作台将位于 ERP。', 'yby-core' ) . '</p>';
			echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=' . self::ROOT_SLUG ) ) . '">' . esc_html__( '返回招商页面设置', 'yby-core' ) . '</a></section>';
		} elseif ( self::ROOT_SLUG === $page ) {
			echo '<section class="andy-business-os__card"><h2>' . esc_html__( 'Business Partnerships 英文招商前端', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html__( 'B2 Dev 前端 Owner 视觉验收已通过；申请表单仍为模拟模式，不提交或保存真实客户信息。', 'yby-core' ) . '</p>';
			echo '<a class="button button-secondary" href="' . esc_url( home_url( '/business/' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( '查看招商页面', 'yby-core' ) . '</a></section>';

		} elseif ( 'andy-business-programs' === $page ) {
			echo '<div class="andy-business-os__cards">';
			$program_labels = self::program_labels();
			foreach ( self::programs() as $name => $description ) {
				echo '<section class="andy-business-os__card"><h2>' . esc_html( $program_labels[ $name ] ?? $name ) . '</h2><p>' . esc_html( $description ) . '</p><span class="andy-business-os__pill">' . esc_html__( '只读展示', 'yby-core' ) . '</span></section>';
			}
			echo '</div>';
		} elseif ( 'andy-business-applications' === $page ) {
			echo '<div class="andy-business-os__card">';
			echo '<h2>' . esc_html__( '申请表单与来源配置', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html__( 'B2 招商表单仅供 Dev 演示，不保存客户申请；正式接收需 ERP 来源验证、签名传输、去重和失败重试。现有 Inquiry OS 保持原 B2C 询盘能力。', 'yby-core' ) . '</p>';
			if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) && YBY_Security::can_view_leads() ) {
				echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=andy-core-leads' ) ) . '">' . esc_html__( '查看原 Inquiry OS', 'yby-core' ) . '</a>';
			} else {
				echo '<p>' . esc_html__( '当前无权限访问询盘系统，或该模块未启用。', 'yby-core' ) . '</p>';
			}
			echo '</div>';

		} elseif ( 'andy-business-settings' === $page ) {
			self::render_status();

		}
		echo '</div>';
	}

	/**
	 * Render the non-transactional connection status.
	 *
	 * @return void
	 */
	private static function render_status() {
		echo '<section class="andy-business-os__card andy-business-os__status">';
		echo '<h2>' . esc_html__( 'ERP 连接状态：尚未连接', 'yby-core' ) . '</h2>';
		echo '<p>' . esc_html__( '六类合作项目页面保留；ERP 连接与真实申请提交均未启用。', 'yby-core' ) . '</p>';
		echo '</section>';
	}
}
