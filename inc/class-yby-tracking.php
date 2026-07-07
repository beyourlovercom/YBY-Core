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
			'enabled' => YBY_Config::is_tracking_enabled(),
			'events'  => array(
				'generate_lead',
				'thank_you_page_view',
				'click_whatsapp',
				'click_whatsapp_after_lead',
				'download_catalog',
				'submit_project_details',
			),
		);
	}
}
