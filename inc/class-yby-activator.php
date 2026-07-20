<?php
/**
 * Plugin activator.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-helpers.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-config.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-database.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-field-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-preset-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-manager.php';

/**
 * Activation routines.
 */
class YBY_Activator {

	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		$existing = get_option( YBY_Helpers::option_key(), array() );
		$options  = wp_parse_args( is_array( $existing ) ? $existing : array(), YBY_Config::defaults() );

		update_option( YBY_Helpers::option_key(), YBY_Config::sanitize( $options ) );
		update_option(
			YBY_Helpers::lead_recipient_option_key(),
			YBY_Config::sanitize_lead_recipient_email(
				get_option( YBY_Helpers::lead_recipient_option_key(), YBY_Config::default_lead_recipient_email() ),
				YBY_Config::default_lead_recipient_email()
			)
		);
		update_option(
			YBY_Helpers::lead_notification_primary_recipient_option_key(),
			YBY_Config::sanitize_email_value(
				get_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), 'sale@yby-irrigation.com' ),
				'sale@yby-irrigation.com'
			)
		);
		update_option(
			YBY_Helpers::lead_notification_cc_recipient_option_key(),
			YBY_Config::sanitize_email_list_value(
				get_option( YBY_Helpers::lead_notification_cc_recipient_option_key(), 'yishitongshop@gmail.com' )
			)
		);
		update_option(
			YBY_Helpers::lead_notification_bcc_recipient_option_key(),
			YBY_Config::sanitize_email_list_value(
				get_option( YBY_Helpers::lead_notification_bcc_recipient_option_key(), '' )
			)
		);
		update_option(
			YBY_Helpers::lead_notification_reply_to_policy_option_key(),
			YBY_Config::sanitize_reply_to_policy(
				get_option( YBY_Helpers::lead_notification_reply_to_policy_option_key(), 'auto' )
			)
		);

		YBY_Inquiry_Manager::install_defaults();
		YBY_Database::install();
	}
}
