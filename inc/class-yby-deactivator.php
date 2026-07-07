<?php
/**
 * Plugin deactivator.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation routines.
 */
class YBY_Deactivator {

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// MVP intentionally preserves options and does not delete plugin data on deactivation.
	}
}
