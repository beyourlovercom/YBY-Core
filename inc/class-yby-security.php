<?php
/**
 * Security helpers.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin security helpers.
 */
class YBY_Security {

	/**
	 * Verify admin capability.
	 *
	 * @return bool
	 */
	public function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}
}
