<?php
/**
 * Email Template OS Settings UI skeleton.
 *
 * Source-only foundation for Andy Core v1.5.8. The class is intentionally not
 * loaded or hooked until the local runtime activation gate passes.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Email Template OS Settings tab foundation.
 */
class YBY_Email_Template_Admin {

	const SECTION_OVERVIEW = 'overview';
	const SECTION_BRAND = 'brand';
	const SECTION_WOOCOMMERCE = 'woocommerce';
	const SECTION_WORDPRESS = 'wordpress';
	const SECTION_ANDY_CORE = 'andy-core';
	const SECTION_RELEASE = 'release-test';

	/**
	 * Return internal section labels.
	 *
	 * @return array<string, string>
	 */
	public static function sections() {
		return array(
			self::SECTION_OVERVIEW    => '概览',
			self::SECTION_BRAND       => '品牌样式',
			self::SECTION_WOOCOMMERCE => 'WooCommerce',
			self::SECTION_WORDPRESS   => 'WordPress',
			self::SECTION_ANDY_CORE   => 'Andy Core',
			self::SECTION_RELEASE     => '发布与测试',
		);
	}

	/**
	 * Resolve current section from the request.
	 *
	 * @return string
	 */
	public function current_section() {
		$section = isset( $_GET['email_section'] ) ? sanitize_key( wp_unslash( $_GET['email_section'] ) ) : self::SECTION_OVERVIEW;
		return array_key_exists( $section, self::sections() ) ? $section : self::SECTION_OVERVIEW;
	}

	/**
	 * Render the Email Template OS Settings content.
	 *
	 * @return void
	 */
	public function render() {
		$registry = new YBY_Email_Template_Registry();
		$templates = $registry->discover();
		$design = YBY_Email_Design_Settings::get();
		$section = $this->current_section();
		$sections = self::sections();

		include YBY_CORE_PLUGIN_DIR . 'admin/views/email-template-settings.php';
	}
}
