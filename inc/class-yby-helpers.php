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
	 * Return the dedicated lead recipient option key.
	 *
	 * @return string
	 */
	public static function lead_recipient_option_key() {
		return 'yby_lead_recipient_email';
	}

	/**
	 * Get admin page slug.
	 *
	 * @return string
	 */
	public static function admin_page_slug() {
		return 'yby-core';
	}

	/**
	 * Build the sent transient key for a case ID.
	 *
	 * @param string $case_id Case ID.
	 * @return string
	 */
	public static function sent_transient_key( $case_id ) {
		return 'yby_lead_sent_' . md5( strtoupper( (string) $case_id ) );
	}

	/**
	 * Build the lock transient key for a case ID.
	 *
	 * @param string $case_id Case ID.
	 * @return string
	 */
	public static function lock_transient_key( $case_id ) {
		return 'yby_lead_lock_' . md5( strtoupper( (string) $case_id ) );
	}
}
