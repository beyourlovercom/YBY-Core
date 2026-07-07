<?php
/**
 * Helper utilities.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic helper methods.
 */
class YBY_Helpers {

	/**
	 * Return plugin option key.
	 *
	 * @return string
	 */
	public static function option_key() {
		return 'yby_core_options';
	}

	/**
	 * Get admin page slug.
	 *
	 * @return string
	 */
	public static function admin_page_slug() {
		return 'yby-core';
	}
}
