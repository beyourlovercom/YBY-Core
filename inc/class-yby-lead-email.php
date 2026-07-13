<?php
/**
 * Legacy lead email compatibility wrapper.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backward-compatible alias for the email provider.
 */
class YBY_Lead_Email extends YBY_Email_Notification_Provider {}
