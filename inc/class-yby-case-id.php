<?php
/**
 * Case ID engine.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Case ID utility.
 */
class YBY_Case_ID {

	/**
	 * Backward-compatible case ID validation pattern.
	 *
	 * Accepts the historic IRR prefix and newer server-generated brand segments
	 * such as CORE without changing generation behavior.
	 *
	 * @var string
	 */
	const VALIDATION_PATTERN = '/^YBY-[A-Z0-9]+-\d{8}-[A-Z0-9]{6}$/';

	/**
	 * Generate a case ID.
	 *
	 * @return string
	 */
	public function generate() {
		$date = gmdate( 'Ymd' );
		$code = $this->random_code( 6 );

		return 'YBY-' . YBY_Site_Profile::get_case_id_code() . '-' . $date . '-' . $code;
	}

	/**
	 * Validate a case ID.
	 *
	 * @param string $case_id Case ID.
	 * @return bool
	 */
	public function validate( $case_id ) {
		$case_id = $this->normalize( $case_id );

		return 1 === preg_match( self::VALIDATION_PATTERN, $case_id );
	}

	/**
	 * Return the frontend-compatible validation regex without delimiters.
	 *
	 * @return string
	 */
	public function frontend_regex() {
		return '^YBY-[A-Z0-9]+-\\d{8}-[A-HJ-NP-Z2-9]{6}$';
	}

	/**
	 * Normalize a case ID.
	 *
	 * @param string $case_id Raw case ID.
	 * @return string
	 */
	public function normalize( $case_id ) {
		$case_id = strtoupper( (string) $case_id );
		$case_id = preg_replace( '/[^A-Z0-9-]/', '', $case_id );

		return (string) $case_id;
	}

	/**
	 * Generate random readable code.
	 *
	 * @param int $length Code length.
	 * @return string
	 */
	protected function random_code( $length ) {
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$max_index  = strlen( $characters ) - 1;
		$output     = '';

		for ( $index = 0; $index < $length; $index++ ) {
			$output .= $characters[ wp_rand( 0, $max_index ) ];
		}

		return $output;
	}
}
