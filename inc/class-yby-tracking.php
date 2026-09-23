<?php
/**
 * Tracking engine.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend tracking helper.
 */
class YBY_Tracking {

	/**
	 * Return tracking config.
	 *
	 * @return array<string, mixed>
	 */
	public function get_frontend_config() {
		return array(
			'enabled'           => YBY_Config::is_tracking_enabled(),
			'defaultProduct'    => YBY_Config::get_default_product_interest(),
			'consent'           => class_exists( 'YBY_Analytics_Consent_Adapter' )
				? YBY_Analytics_Consent_Adapter::runtime_config()
				: array(),
			'siteProfile'       => class_exists( 'YBY_Analytics_Site_Profile' )
				? YBY_Analytics_Site_Profile::current()
				: array(),
			'analytics'         => class_exists( 'YBY_Analytics_GTM4WP_Adapter' )
				? YBY_Analytics_GTM4WP_Adapter::runtime_config()
				: array(),
			'events'            => class_exists( 'YBY_Analytics_Event_Contract' )
				? YBY_Analytics_Event_Contract::names()
				: array(),
		);
	}
}
