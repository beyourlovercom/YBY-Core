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
			'caseIdRegex'  => $case_id->frontend_regex(),
			'caseIdSample' => $case_id->generate(),
			'thankYouUrl'  => YBY_Brand_Profile::get_thank_you_url(),
		);
	}
}
