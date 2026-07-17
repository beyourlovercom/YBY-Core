<?php
/**
 * Lead REST controller.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global lead submission endpoint.
 */
class YBY_Lead_REST_Controller {

	/**
	 * Register plugin REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'yby/v1',
			'/leads',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit_lead' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Allow public lead submissions.
	 *
	 * The endpoint accepts public website inquiries, so validation happens on
	 * the payload rather than WordPress authentication.
	 *
	 * @return bool
	 */
	public function permission_callback() {
		return true;
	}

	/**
	 * Handle a lead submission.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function submit_lead( \WP_REST_Request $request ) {
		$lead   = $this->sanitize_request_payload( $request );
		$errors = $this->validate_lead( $lead );

		if ( ! empty( $errors ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Validation failed', 'yby-core' ),
					'errors'  => $errors,
				),
				400
			);
		}

		$result = YBY_Lead_Service::create( $lead );

		if ( empty( $result['success'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Lead could not be saved', 'yby-core' ),
					'errors'  => array(
						'storage' => __( 'Lead storage failed', 'yby-core' ),
					),
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Lead received', 'yby-core' ),
				'data'    => array(
					'lead_id' => (int) $result['lead_id'],
					'case_id' => (string) $result['case_id'],
					'status'  => (string) $result['status'],
				),
			),
			200
		);
	}

	/**
	 * Sanitize all supported request fields.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array<string, string>
	 */
	protected function sanitize_request_payload( \WP_REST_Request $request ) {
		return array(
			'brand'            => $this->limit_text( $request->get_param( 'brand' ), 50 ),
			'website'          => $this->limit_text( $request->get_param( 'website' ), 255 ),
			'source_url'       => $this->limit_text( $request->get_param( 'source_url' ) ?: $request->get_param( 'page' ), 1000 ),
			'case_id'          => $this->limit_text( $request->get_param( 'case_id' ), 50 ),
			'name'             => $this->limit_text( $request->get_param( 'name' ), 100 ),
			'company'          => $this->limit_text( $request->get_param( 'company' ), 150 ),
			'country'          => $this->limit_text( $request->get_param( 'country' ), 100 ),
			'email'            => substr( sanitize_email( (string) $request->get_param( 'email' ) ), 0, 150 ),
			'whatsapp'         => $this->limit_text( $request->get_param( 'whatsapp' ), 50 ),
			'buyer_type'       => $this->limit_text( $request->get_param( 'buyer_type' ), 50 ),
			'product_interest' => $this->limit_text( $request->get_param( 'product_interest' ), 500 ),
			'quantity'         => $this->limit_text( $request->get_param( 'quantity' ), 100 ),
			'project_details'  => $this->limit_textarea( $request->get_param( 'project_details' ), 3000 ),
			'utm_source'       => $this->limit_text( $request->get_param( 'utm_source' ), 100 ),
			'utm_medium'       => $this->limit_text( $request->get_param( 'utm_medium' ), 100 ),
			'utm_campaign'     => $this->limit_text( $request->get_param( 'utm_campaign' ), 150 ),
			'utm_term'         => $this->limit_text( $request->get_param( 'utm_term' ), 150 ),
			'gclid'            => $this->limit_text( $request->get_param( 'gclid' ), 255 ),
			'fbclid'           => $this->limit_text( $request->get_param( 'fbclid' ), 255 ),
		);
	}

	/**
	 * Validate a sanitized lead payload.
	 *
	 * @param array<string, string> $lead Sanitized lead payload.
	 * @return array<string, string>
	 */
	protected function validate_lead( $lead ) {
		$errors = array();

		if ( '' === trim( (string) $lead['name'] ) ) {
			$errors['name'] = __( 'Name is required', 'yby-core' );
		}

		if ( '' === trim( (string) $lead['email'] ) ) {
			$errors['email'] = __( 'Email is required', 'yby-core' );
		} elseif ( ! is_email( $lead['email'] ) ) {
			$errors['email'] = __( 'Invalid email', 'yby-core' );
		}

		return $errors;
	}

	/**
	 * Limit a scalar string field safely.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	protected function limit_text( $value, $max_length ) {
		return substr( sanitize_text_field( (string) $value ), 0, $max_length );
	}

	/**
	 * Limit a textarea safely.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	protected function limit_textarea( $value, $max_length ) {
		return substr( sanitize_textarea_field( (string) $value ), 0, $max_length );
	}
}
