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

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Lead received', 'yby-core' ),
				'data'    => array(
					'status' => 'received',
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
			'brand'            => $this->limit_text( $request->get_param( 'brand' ), 120 ),
			'website'          => $this->limit_text( $request->get_param( 'website' ), 160 ),
			'page'             => $this->limit_text( $request->get_param( 'page' ), 240 ),
			'name'             => $this->limit_text( $request->get_param( 'name' ), 120 ),
			'company'          => $this->limit_text( $request->get_param( 'company' ), 160 ),
			'country'          => $this->limit_text( $request->get_param( 'country' ), 100 ),
			'email'            => sanitize_email( (string) $request->get_param( 'email' ) ),
			'whatsapp'         => $this->limit_text( $request->get_param( 'whatsapp' ), 60 ),
			'buyer_type'       => $this->limit_text( $request->get_param( 'buyer_type' ), 80 ),
			'product_interest' => $this->limit_text( $request->get_param( 'product_interest' ), 160 ),
			'project_details'  => $this->limit_textarea( $request->get_param( 'project_details' ), 3000 ),
			'utm_source'       => $this->limit_text( $request->get_param( 'utm_source' ), 500 ),
			'utm_medium'       => $this->limit_text( $request->get_param( 'utm_medium' ), 500 ),
			'utm_campaign'     => $this->limit_text( $request->get_param( 'utm_campaign' ), 500 ),
			'utm_term'         => $this->limit_text( $request->get_param( 'utm_term' ), 500 ),
			'gclid'            => $this->limit_text( $request->get_param( 'gclid' ), 500 ),
			'fbclid'           => $this->limit_text( $request->get_param( 'fbclid' ), 500 ),
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
