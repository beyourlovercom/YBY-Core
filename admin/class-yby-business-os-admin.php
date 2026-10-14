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
			self::ROOT_SLUG                 => 'Overview',
			'andy-business-applications'   => 'Applications',
			'andy-business-programs'       => 'Partnership Programs',
			'andy-business-partners'       => 'Business Partners',
			'andy-business-catalog'        => 'Catalog & Pricing',
			'andy-business-fulfillment'    => 'Fulfillment',
			'andy-business-pages'          => 'Business Pages',
			'andy-business-settings'       => 'Settings',
		);
	}

	/**
	 * Static product-program vocabulary, not customer or ERP data.
	 *
	 * @return array<string,string>
	 */
	public static function programs() {
		return array(
			'Wholesale'           => 'Bulk orders, samples and volume-based wholesale requests.',
			'Dropshipping'        => 'Subject-to-approval single-order fulfillment.',
			'Retail Partners'     => 'Store displays, sales materials and product education.',
			'Distributors'        => 'Regional distribution applications and agreement review.',
			'OEM / Private Label' => 'Custom product and packaging requests.',
			'Corporate Orders'    => 'Project-based corporate and event procurement.',
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
			__( 'Business', 'yby-core' ),
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
			wp_die( esc_html__( 'You do not have permission to view Business OS.', 'yby-core' ), '', array( 'response' => 403 ) );
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::ROOT_SLUG;
		$pages = self::pages();
		if ( ! isset( $pages[ $page ] ) ) {
			wp_die( esc_html__( 'Business OS page not found.', 'yby-core' ), '', array( 'response' => 404 ) );
			return;
		}
		echo '<div class="wrap andy-business-os">';
		echo '<p class="andy-business-os__eyebrow">BEYOURLOVER BUSINESS</p>';
		echo '<h1>' . esc_html( $pages[ $page ] ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'B1 admin shell · No real B2B data or transactions are connected.', 'yby-core' ) . '</p>';

		if ( self::ROOT_SLUG === $page ) {
			echo '<div class="andy-business-os__cards">';
			foreach ( array( 'New Applications', 'Approved Partners', 'Pending Quotes', 'Fulfillment' ) as $metric ) {
				echo '<section class="andy-business-os__card"><h2>' . esc_html( $metric ) . '</h2><p class="andy-business-os__missing">—</p><p>' . esc_html__( 'ERP not connected', 'yby-core' ) . '</p></section>';
			}
			echo '</div>';
			self::render_status();
		} elseif ( 'andy-business-programs' === $page ) {
			echo '<div class="andy-business-os__cards">';
			foreach ( self::programs() as $name => $description ) {
				echo '<section class="andy-business-os__card"><h2>' . esc_html( $name ) . '</h2><p>' . esc_html( $description ) . '</p><span class="andy-business-os__pill">' . esc_html__( 'Planned', 'yby-core' ) . '</span></section>';
			}
			echo '</div>';
		} elseif ( 'andy-business-applications' === $page ) {
			echo '<div class="andy-business-os__card">';
			echo '<h2>' . esc_html__( 'Reuse Inquiry OS', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html__( 'Business applications will be filtered through the existing Inquiry OS in B3. No duplicate lead database is created.', 'yby-core' ) . '</p>';
			if ( YBY_Module_Registry::is_enabled( 'inquiry_os' ) && YBY_Security::can_view_leads() ) {
				echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'admin.php?page=andy-core-leads' ) ) . '">' . esc_html__( 'Open existing inquiries', 'yby-core' ) . '</a>';
			} else {
				echo '<p>' . esc_html__( 'Inquiry OS access is unavailable.', 'yby-core' ) . '</p>';
			}
			echo '</div>';
		} else {
			$explanations = array(
				'andy-business-partners'    => 'Approved partner and permission summaries will be read from ERP.',
				'andy-business-catalog'     => 'Product availability and partner-specific pricing remain authoritative in ERP.',
				'andy-business-fulfillment' => 'Warehouse capacity, fulfillment and dropshipping remain governed by ERP.',
				'andy-business-pages'       => 'Public Business pages will be implemented with BYL Theme OS after visual approval.',
				'andy-business-settings'    => 'ERP connection and Business OS configuration remain disabled in B1.',
			);
			echo '<div class="andy-business-os__card"><h2>' . esc_html__( 'Planned integration', 'yby-core' ) . '</h2>';
			echo '<p>' . esc_html( $explanations[ $page ] ) . '</p>';
			echo '<span class="andy-business-os__pill">' . esc_html__( 'Not connected', 'yby-core' ) . '</span></div>';
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
		echo '<h2>' . esc_html__( 'Business Setup', 'yby-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'Six partnership programs planned. ERP bridge disabled. Public applications disabled.', 'yby-core' ) . '</p>';
		echo '</section>';
	}
}
