<?php
/**
 * Lead session helpers.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead session bridge.
 */
class YBY_Lead_Session {

	/**
	 * Return frontend config.
	 *
	 * @return array<string, mixed>
	 */
	public function get_frontend_config() {
		$case_id = new YBY_Case_ID();

		return array(
			'enableCaseId' => YBY_Config::is_case_id_enabled(),
			'caseIdRegex'  => '^YBY-IRR-\\d{8}-[A-HJ-NP-Z2-9]{6}$',
			'caseIdSample' => $case_id->generate(),
			'thankYouUrl'  => YBY_Config::get_thank_you_url(),
		);
	}
}
