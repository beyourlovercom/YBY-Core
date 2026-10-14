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
			self::ROOT_SLUG                 => '业务总览',
			'andy-business-applications'   => '合作申请',
			'andy-business-programs'       => '合作项目',
			'andy-business-partners'       => '合作客户',
			'andy-business-catalog'        => '产品与报价',
			'andy-business-fulfillment'    => '仓储与发货',
			'andy-business-pages'          => '招商页面',
			'andy-business-settings'       => '设置',
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
	 * Fail closed if the module, site or permission gate is absent.
	 *
	 * @return bool
	 */
	public static function can_access() {
		return YBY_Module_Registry::is_enabled( YBY_Business_OS_Module::MODULE_ID )
			&& YBY_Business_OS_Module::is_site_eligible()
			&& current_user_can( 'andy_core_settings_manage' );
	}

	/**
	 * Register the WP-admin first-level Business menu and its eight routes.
	 *
	 * @param array $module Registry metadata, unused.
	 * @return void
	 */
	public static function register_admin_menu( $module = array() ) {
		unset( $module );
		if ( ! self::can_access() ) { return; }

		add_menu_page(
			__( '商业合作 Business', 'yby-core' ),
			__( 'Business', 'yby-core' ),
			'andy_core_settings_manage',
			self::ROOT_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-store',
			57
		);
		foreach ( self::pages() as $slug => $label ) {
			add_submenu_page(
				self::ROOT_SLUG,
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
		return isset( self::pages()[ $page ] );
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
		if ( ! isset( $pages[ $page ] ) ) {
			wp_die( esc_html__( '未找到该 Business OS 页面。', 'yby-core' ), '', array( 'response' => 404 ) );
			return;
		}
		echo '<div class="wrap andy-business-os">';
		echo '<p class="andy-business-os__eyebrow">BEYOURLOVER · 商业合作管理</p>';
		echo '<h1>' . esc_html( $pages[ $page ] ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'B1 后台基础版本：尚未连接真实 B2B 数据或交易。', 'yby-core' ) . '</p>';

		if ( self::ROOT_SLUG === $page ) {
			echo '<div class="andy-business-os__cards">';
			foreach ( array( '新合作申请', '已审核合作客户', '待处理报价', '待处理履约' ) as $metric ) {
				echo '<section class="andy-business-os__card"><h2>' . esc_html( $metric ) . '</h2><p class="andy-business-os__missing">—</p><p>' . esc_html__( 'ERP 尚未连接', 'yby-core' ) . '</p></section>';
			}
			echo '</div>';
			self::render_status();
		} elseif ( 'andy-business-programs' === $page ) {
			echo '<div class="andy-business-os__cards">';
			foreach ( self::programs() as $name => $description ) {
				echo '<section class="andy-business-os__card"><h2>' . esc_html( $name ) . '</h2><p>' . esc_html( $description ) . '</p><span class="andy-business-os__pill">' . esc_html__( '规划中', 'yby-core' ) . '</span></section>';
			}
			echo '</div>';
		} elseif ( 'andy-business-applications' === $page ) {
			echo '<div class="andy-business-os__card">';
			echo '<h2>' . esc_html__( '复用现有询盘系统 Inquiry OS', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html__( 'B3 阶段将在现有 Inquiry OS 内增加 B2B 合作申请分类，不新建重复询盘数据库。', 'yby-core' ) . '</p>';
			if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) && YBY_Security::can_view_leads() ) {
				echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=andy-core-leads' ) ) . '">' . esc_html__( '查看现有询盘', 'yby-core' ) . '</a>';
			} else {
				echo '<p>' . esc_html__( '当前无权限访问询盘系统，或该模块未启用。', 'yby-core' ) . '</p>';
			}
			echo '</div>';
		} else {
			$explanations = array(
				'andy-business-partners'    => '合作客户资料和权限摘要由 ERP 提供，只读展示。',
				'andy-business-catalog'     => '产品可售范围及 B2B 专属报价以 ERP 为准。',
				'andy-business-fulfillment' => '仓储库存、履约及一件代发均由 ERP 统一管理。',
				'andy-business-pages'       => '招商页面须通过视觉验收后，由 BYL Theme OS 实现。',
				'andy-business-settings'    => 'B1 阶段 ERP 接口和 Business OS 业务配置保持关闭。',
			);
			echo '<div class="andy-business-os__card"><h2>' . esc_html__( '后续阶段集成', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html( $explanations[ $page ] ) . '</p>';
			echo '<span class="andy-business-os__pill">' . esc_html__( '尚未连接', 'yby-core' ) . '</span></div>';
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
		echo '<h2>' . esc_html__( 'Business 基础配置', 'yby-core' ) . '</h2>';
		echo '<p>' . esc_html__( '已规划六类合作项目；ERP 接口关闭；公开申请入口关闭。', 'yby-core' ) . '</p>';
		echo '</section>';
	}
}
