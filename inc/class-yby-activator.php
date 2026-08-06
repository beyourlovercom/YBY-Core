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
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-social-login.php';

/**
 * Activation routines.
 */
class YBY_Activator {

	public static function sync_capabilities() {
		$administrator = get_role( 'administrator' );
		$editor = get_role( 'editor' );
		if ( $administrator ) { foreach ( array( 'andy_core_leads_view', 'andy_core_leads_manage', 'andy_core_leads_assign', 'andy_core_leads_archive', 'andy_core_settings_manage' ) as $capability ) { $administrator->add_cap( $capability ); } }
		if ( $editor ) { foreach ( array( 'andy_core_leads_view', 'andy_core_leads_manage', 'andy_core_leads_archive' ) as $capability ) { $editor->add_cap( $capability ); } }
	}

	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		self::sync_capabilities();
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
				get_option( YBY_Helpers::lead_notification_primary_recipient_option_key(), '' ),
				YBY_Config::sanitize_email_value( get_option( 'admin_email', '' ) )
			)
		);
		update_option(
			YBY_Helpers::lead_notification_cc_recipient_option_key(),
			YBY_Config::sanitize_email_list_value(
				get_option( YBY_Helpers::lead_notification_cc_recipient_option_key(), '' )
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
		$social_options           = YBY_Social_Login::get_options();
		$social_options['google'] = wp_parse_args(
			$social_options['google'],
			YBY_Social_Login::google_defaults()
		);
		YBY_Social_Login::save( $social_options );
		YBY_Database::install();
	}
}
