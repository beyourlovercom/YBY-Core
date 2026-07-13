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
	}
}
