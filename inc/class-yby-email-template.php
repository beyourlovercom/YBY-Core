<?php
/**
 * Safe email template generator.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email template helper for future use.
 */
class YBY_Email_Template {

	/**
	 * Build lead confirmation subject.
	 *
	 * @param string $case_id Case ID.
	 * @return string
	 */
	public function lead_confirmation_subject( $case_id ) {
		$case_engine = new YBY_Case_ID();
		$normalized  = $case_engine->normalize( $case_id );

		if ( ! $case_engine->validate( $normalized ) ) {
			$normalized = __( 'Pending', 'yby-core' );
		}

		return sprintf(
			/* translators: %s: Case ID. */
			__( 'YBY Irrigation Request Received - Case ID: %s', 'yby-core' ),
			$normalized
		);
	}

	/**
	 * Build lead confirmation body.
	 *
	 * Future v0.3 may connect this template to an approved sending workflow.
	 * MVP does not send email and only returns a safe rendered string.
	 *
	 * @param array<string, mixed> $data Email context.
	 * @return string
	 */
	public function lead_confirmation_body( $data ) {
		$data        = is_array( $data ) ? $data : array();
		$case_engine = new YBY_Case_ID();
		$first_name  = sanitize_text_field( $data['first_name'] ?? '' );
		$case_id     = $case_engine->normalize( $data['case_id'] ?? '' );
		$catalog_url = esc_url( YBY_Config::get_catalog_url() );
		$website_url = esc_url( YBY_Config::get_website_url() );
		$support     = sanitize_email( YBY_Config::get_support_email() );
		$whatsapp    = sanitize_text_field( YBY_Config::get_whatsapp_number() );

		if ( '' === $first_name ) {
			$first_name = __( 'Customer', 'yby-core' );
		}

		if ( ! $case_engine->validate( $case_id ) ) {
			$case_id = __( 'Pending', 'yby-core' );
		}

		$lines = array(
			sprintf(
				/* translators: %s: Customer first name. */
				__( 'Dear %s,', 'yby-core' ),
				$first_name
			),
			'',
			__( 'Thank you for contacting YBY Irrigation.', 'yby-core' ),
			sprintf(
				/* translators: %s: Case ID. */
				__( 'Your Case ID is: %s', 'yby-core' ),
				$case_id
			),
			sprintf(
				/* translators: %s: Catalog URL. */
				__( 'Catalog link: %s', 'yby-core' ),
				$catalog_url
			),
			sprintf(
				/* translators: %s: Website URL. */
				__( 'Website: %s', 'yby-core' ),
				$website_url
			),
			sprintf(
				/* translators: %s: Support email. */
				__( 'Support email: %s', 'yby-core' ),
				$support
			),
			sprintf(
				/* translators: %s: WhatsApp number. */
				__( 'WhatsApp contact: %s', 'yby-core' ),
				$whatsapp
			),
		);

		return implode( "\n", array_map( 'esc_html', $lines ) );
	}
}
