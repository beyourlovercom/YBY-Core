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

		$this->dispatch_notification( $lead, $result );

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
	 * @return array<string, mixed>
	 */
	protected function sanitize_request_payload( \WP_REST_Request $request ) {
		$mapper          = new YBY_Inquiry_Lead_Mapper();
		$source_metadata = $mapper->normalize_source_metadata(
			array(
				'source_component' => $request->get_param( 'source_component' ),
				'source_preset'    => $request->get_param( 'source_preset' ),
				'source_page'      => $request->get_param( 'source_page' ),
				'form_version'     => $request->get_param( 'form_version' ),
			)
		);
		$custom_fields   = $mapper->sanitize_custom_fields( $request->get_param( 'fields' ), $source_metadata['source_preset'] );

		return array_merge(
			$source_metadata,
			array(
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
				'custom_fields'    => $custom_fields,
				'utm_source'       => $this->limit_text( $request->get_param( 'utm_source' ), 100 ),
				'utm_medium'       => $this->limit_text( $request->get_param( 'utm_medium' ), 100 ),
				'utm_campaign'     => $this->limit_text( $request->get_param( 'utm_campaign' ), 150 ),
				'utm_term'         => $this->limit_text( $request->get_param( 'utm_term' ), 150 ),
				'gclid'            => $this->limit_text( $request->get_param( 'gclid' ), 255 ),
				'fbclid'           => $this->limit_text( $request->get_param( 'fbclid' ), 255 ),
			)
		);
	}

	/**
	 * Validate a sanitized lead payload.
	 *
	 * @param array<string, mixed> $lead Sanitized lead payload.
	 * @return array<string, string>
	 */
	protected function validate_lead( $lead ) {
		$errors = array();

		if ( '' === trim( (string) $lead['name'] ) ) {
			$errors['name'] = __( 'Name is required', 'yby-core' );
		}

		$email    = trim( (string) $lead['email'] );
		$whatsapp = trim( (string) $lead['whatsapp'] );

		if ( '' === $email && '' === $whatsapp ) {
			$errors['email']    = __( 'Email or WhatsApp is required', 'yby-core' );
			$errors['whatsapp'] = __( 'Email or WhatsApp is required', 'yby-core' );
		} elseif ( '' !== $email && ! is_email( $email ) ) {
			$errors['email'] = __( 'Invalid email', 'yby-core' );
		}

		if ( '' !== $whatsapp && ! preg_match( '/^[+()\d\s-]{6,}$/', $whatsapp ) ) {
			$errors['whatsapp'] = __( 'Invalid WhatsApp', 'yby-core' );
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

	/**
	 * Dispatch a non-fatal notification after successful storage.
	 *
	 * @param array<string, mixed> $lead Sanitized lead payload.
	 * @param array<string, mixed> $result Storage result.
	 * @return void
	 */
	protected function dispatch_notification( $lead, $result ) {
		$mapper               = new YBY_Inquiry_Lead_Mapper();
		$custom_fields        = isset( $lead['custom_fields'] ) && is_array( $lead['custom_fields'] ) ? $lead['custom_fields'] : array();
		$notification_payload = array_merge(
			$lead,
			array(
				'lead_id'              => isset( $result['lead_id'] ) ? (int) $result['lead_id'] : 0,
				'case_id'              => isset( $result['case_id'] ) ? (string) $result['case_id'] : '',
				'status'               => isset( $result['status'] ) ? (string) $result['status'] : '',
				'custom_fields'        => $custom_fields,
				'custom_fields_json'   => $mapper->build_custom_fields_json( $custom_fields ),
				'contact_email'        => isset( $lead['email'] ) ? (string) $lead['email'] : '',
				'contact_whatsapp_url' => $this->build_whatsapp_url( isset( $lead['whatsapp'] ) ? $lead['whatsapp'] : '' ),
				'landing_page_url'     => isset( $lead['source_url'] ) ? (string) $lead['source_url'] : '',
			)
		);
		$notification_manager = new YBY_Notification_Manager();
		$notification_result  = array(
			'mail_sent'       => false,
			'mail_error_code' => '',
		);

		try {
			$notification_result = $notification_manager->sendLeadNotification( $notification_payload );
		} catch ( \Throwable $throwable ) {
			$notification_result = array(
				'mail_sent'       => false,
				'mail_error_code' => 'notification_exception',
			);
		}

		do_action(
			'yby_lead_notification_result',
			is_array( $notification_result ) ? $notification_result : array(),
			isset( $result['lead_id'] ) ? (int) $result['lead_id'] : 0,
			isset( $result['case_id'] ) ? (string) $result['case_id'] : ''
		);
	}

	/**
	 * Build a safe WhatsApp quick-action URL.
	 *
	 * @param mixed $value Raw WhatsApp value.
	 * @return string
	 */
	protected function build_whatsapp_url( $value ) {
		$number = preg_replace( '/[^\d+]/', '', (string) $value );

		if ( '' === $number ) {
			return '';
		}

		return 'https://wa.me/' . rawurlencode( ltrim( $number, '+' ) );
	}
}
