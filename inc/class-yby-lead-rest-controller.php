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
 * Governed public lead endpoint.
 */
class YBY_Lead_REST_Controller {

	/**
	 * Sent marker lifetime in seconds.
	 *
	 * @var int
	 */
	const SENT_WINDOW = 1800;

	/**
	 * Short send lock lifetime in seconds.
	 *
	 * @var int
	 */
	const SEND_LOCK_WINDOW = 90;

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
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle a lead submission.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function submit_lead( \WP_REST_Request $request ) {
		if ( ! $this->validate_request_origin( $request ) ) {
			return $this->error_response( 'invalid_source', __( 'We could not validate your request source.', 'yby-core' ), 400 );
		}

		$lead = $this->sanitize_request_payload( $request );

		if ( ! empty( $lead['company_website'] ) ) {
			return $this->error_response( 'invalid_submission', __( 'We could not process your request.', 'yby-core' ), 400 );
		}

		$validation_error = $this->validate_required_fields( $lead );

		if ( $validation_error instanceof \WP_REST_Response ) {
			return $validation_error;
		}

		$case_engine     = new YBY_Case_ID();
		$lead['case_id'] = $this->resolve_case_id( $case_engine, $lead['case_id'] );

		if ( $this->is_duplicate( $lead['case_id'] ) ) {
			return $this->success_response( $lead['case_id'], true, false );
		}

		if ( ! $this->acquire_send_lock( $lead['case_id'] ) ) {
			return $this->error_response( 'request_in_progress', __( 'Your request is being processed. Please wait a moment and try again.', 'yby-core' ), 409 );
		}

		try {
			if ( $this->is_duplicate( $lead['case_id'] ) ) {
				return $this->success_response( $lead['case_id'], true, false );
			}

			$notification_manager = new YBY_Notification_Manager();
			$result               = $notification_manager->sendLeadNotification( $lead );
			$sent                 = ! empty( $result['mail_sent'] );

			if ( ! $sent ) {
				return $this->error_response( 'mail_send_failed', __( 'We could not send your request. Please try again or contact us on WhatsApp.', 'yby-core' ), 500 );
			}

			$this->mark_sent( $lead['case_id'] );

			return $this->success_response( $lead['case_id'], false, true );
		} finally {
			$this->release_send_lock( $lead['case_id'] );
		}
	}

	/**
	 * Validate request origin.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return bool
	 */
	protected function validate_request_origin( \WP_REST_Request $request ) {
		$allowed_hosts = array( 'ybyirrigation.com', 'www.ybyirrigation.com' );
		$landing_url   = $this->sanitize_landing_page_url( $request->get_param( 'landing_url' ) ?: $request->get_param( 'landing_page_url' ) );
		$origin        = $this->sanitize_landing_page_url( $request->get_header( 'origin' ) );
		$referer       = $this->sanitize_landing_page_url( $request->get_header( 'referer' ) );

		if ( '' !== $landing_url && ! $this->is_allowed_host_url( $landing_url, $allowed_hosts ) ) {
			return false;
		}

		if ( '' !== $origin && ! $this->is_allowed_host_url( $origin, $allowed_hosts ) ) {
			return false;
		}

		if ( '' !== $referer && ! $this->is_allowed_host_url( $referer, $allowed_hosts ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize all supported request fields.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array<string, string>
	 */
	protected function sanitize_request_payload( $request ) {
		$contact        = $this->limit_text( $request->get_param( 'contact' ), 200 );
		$contact_email  = sanitize_email( $contact );
		$contact_digits = preg_replace( '/\D+/', '', $contact );

		return array(
			'name'                 => $this->limit_text( $request->get_param( 'name' ), 120 ),
			'company'              => $this->limit_text( $request->get_param( 'company' ), 160 ),
			'country'              => $this->limit_text( $request->get_param( 'country' ), 100 ),
			'crop'                 => $this->limit_text( $request->get_param( 'crop' ), 160 ),
			'farm_size'            => $this->limit_text( $request->get_param( 'farm_size' ), 100 ),
			'water_source'         => $this->limit_text( $request->get_param( 'water_source' ), 100 ),
			'contact'              => $contact,
			'contact_email'        => is_email( $contact_email ) ? $contact_email : '',
			'contact_whatsapp_url' => $this->build_whatsapp_url( $contact_digits ),
			'message'              => $this->limit_textarea( $request->get_param( 'message' ), 3000 ),
			'source_component'     => $this->limit_text( $request->get_param( 'source_component' ), 120 ),
			'recommended_system'   => $this->limit_text( $request->get_param( 'recommended_system' ), 200 ),
			'estimated_range'      => $this->limit_text( $request->get_param( 'estimated_range' ), 120 ),
			'selected_country'     => $this->limit_text( $request->get_param( 'selected_country' ), 100 ),
			'case_id'              => $this->limit_text( $request->get_param( 'case_id' ), 40 ),
			'project_id'           => $this->limit_text( $request->get_param( 'project_id' ), 120 ),
			'product_interest'     => $this->limit_text( $request->get_param( 'product_interest' ), 120 ),
			'tracking_group'       => $this->limit_text( $request->get_param( 'tracking_group' ), 120 ),
			'landing_page_url'     => $this->sanitize_landing_page_url( $request->get_param( 'landing_url' ) ?: $request->get_param( 'landing_page_url' ) ),
			'referrer'             => $this->sanitize_landing_page_url( $request->get_param( 'referrer' ) ),
			'utm_source'           => $this->limit_text( $request->get_param( 'utm_source' ), 500 ),
			'utm_medium'           => $this->limit_text( $request->get_param( 'utm_medium' ), 500 ),
			'utm_campaign'         => $this->limit_text( $request->get_param( 'utm_campaign' ), 500 ),
			'utm_content'          => $this->limit_text( $request->get_param( 'utm_content' ), 500 ),
			'utm_term'             => $this->limit_text( $request->get_param( 'utm_term' ), 500 ),
			'gclid'                => $this->limit_text( $request->get_param( 'gclid' ), 500 ),
			'yby_adgroup_id'       => $this->limit_text( $request->get_param( 'yby_adgroup_id' ), 500 ),
			'yby_matchtype'        => $this->limit_text( $request->get_param( 'yby_matchtype' ), 500 ),
			'yby_device'           => $this->limit_text( $request->get_param( 'yby_device' ), 500 ),
			'yby_network'          => $this->limit_text( $request->get_param( 'yby_network' ), 500 ),
			'company_website'      => $this->limit_text( $request->get_param( 'company_website' ), 500 ),
			'user_agent'           => $this->limit_text( $request->get_header( 'user_agent' ) ?: $request->get_header( 'user-agent' ), 1000 ),
		);
	}

	/**
	 * Validate required fields.
	 *
	 * @param array<string, string> $lead Lead payload.
	 * @return \WP_REST_Response|null
	 */
	protected function validate_required_fields( $lead ) {
		$required = array(
			'name'      => __( 'Name is required.', 'yby-core' ),
			'country'   => __( 'Country is required.', 'yby-core' ),
			'crop'      => __( 'Crop is required.', 'yby-core' ),
			'farm_size' => __( 'Farm size is required.', 'yby-core' ),
			'contact'   => __( 'Contact is required.', 'yby-core' ),
		);

		foreach ( $required as $key => $message ) {
			if ( '' === trim( (string) $lead[ $key ] ) ) {
				return $this->error_response( 'missing_required_field', $message, 400 );
			}
		}

		return null;
	}

	/**
	 * Resolve the canonical case ID for this submission.
	 *
	 * @param YBY_Case_ID $case_engine Case ID engine.
	 * @param string      $case_id Submitted case ID.
	 * @return string
	 */
	protected function resolve_case_id( $case_engine, $case_id ) {
		$normalized = $case_engine->normalize( $case_id );

		if ( $case_engine->validate( $normalized ) ) {
			return $normalized;
		}

		return $case_engine->generate();
	}

	/**
	 * Check whether the Case ID was already sent within the idempotency window.
	 *
	 * @param string $case_id Case ID.
	 * @return bool
	 */
	protected function is_duplicate( $case_id ) {
		return ! empty( get_transient( YBY_Helpers::sent_transient_key( $case_id ) ) );
	}

	/**
	 * Attempt to acquire a short send lock.
	 *
	 * @param string $case_id Case ID.
	 * @return bool
	 */
	protected function acquire_send_lock( $case_id ) {
		$key = YBY_Helpers::lock_transient_key( $case_id );

		if ( get_transient( $key ) ) {
			return false;
		}

		return set_transient( $key, 1, self::SEND_LOCK_WINDOW );
	}

	/**
	 * Release the send lock.
	 *
	 * @param string $case_id Case ID.
	 * @return void
	 */
	protected function release_send_lock( $case_id ) {
		delete_transient( YBY_Helpers::lock_transient_key( $case_id ) );
	}

	/**
	 * Mark the Case ID as sent.
	 *
	 * @param string $case_id Case ID.
	 * @return void
	 */
	protected function mark_sent( $case_id ) {
		set_transient( YBY_Helpers::sent_transient_key( $case_id ), 1, self::SENT_WINDOW );
	}

	/**
	 * Build a success response.
	 *
	 * @param string $case_id Case ID.
	 * @param bool   $duplicate Duplicate status.
	 * @return \WP_REST_Response
	 */
	protected function success_response( $case_id, $duplicate, $mail_sent ) {
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'case_id'   => $case_id,
				'duplicate' => (bool) $duplicate,
				'mail_sent' => (bool) $mail_sent,
			),
			200
		);
	}

	/**
	 * Build an error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param int    $status HTTP status code.
	 * @return \WP_REST_Response
	 */
	protected function error_response( $code, $message, $status ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
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
	 * Sanitize and validate a landing page URL.
	 *
	 * @param mixed $value Raw URL.
	 * @return string
	 */
	protected function sanitize_landing_page_url( $value ) {
		$url = esc_url_raw( (string) $value, array( 'https' ) );

		if ( '' === $url ) {
			return '';
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! in_array( $host, array( 'ybyirrigation.com', 'www.ybyirrigation.com' ), true ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Check whether a URL is on an allowed host.
	 *
	 * @param string             $url URL to check.
	 * @param array<int, string> $allowed_hosts Allowed hosts.
	 * @return bool
	 */
	protected function is_allowed_host_url( $url, $allowed_hosts ) {
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		return 'https' === strtolower( (string) $scheme ) && in_array( strtolower( (string) $host ), $allowed_hosts, true );
	}

	/**
	 * Build a WhatsApp URL when digits are valid.
	 *
	 * @param string $digits Digit-only contact string.
	 * @return string
	 */
	protected function build_whatsapp_url( $digits ) {
		$length = strlen( (string) $digits );

		if ( $length < 8 || $length > 15 ) {
			return '';
		}

		return 'https://wa.me/' . $digits;
	}
}
