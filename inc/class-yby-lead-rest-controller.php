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

		$notification = $this->dispatch_notification( $lead, $result );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Lead received', 'yby-core' ),
				'data'    => array(
					'lead_id' => (int) $result['lead_id'],
					'case_id' => (string) $result['case_id'],
					'status'  => (string) $result['status'],
					'notification_status' => $notification['status'],
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
				'page_profile'     => $request->get_param( 'page_profile' ),
			)
		);
		$custom_fields   = $mapper->sanitize_custom_fields( $request->get_param( 'fields' ), $source_metadata['source_preset'] );

		return array_merge(
			$source_metadata,
			array(
				'brand'            => YBY_Site_Profile::get_brand_key(),
				'website'          => YBY_Site_Profile::get_website_url(),
				'source_url'       => $this->limit_text( $request->get_param( 'source_url' ) ?: $request->get_param( 'page' ), 1000 ),
				'case_id'          => '',
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
				'form_device'      => 'mobile' === sanitize_key( (string) $request->get_param( 'form_device' ) ) ? 'mobile' : 'desktop',
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
		$mapper = new YBY_Inquiry_Lead_Mapper();
		if ( method_exists( $mapper, 'validate_submission_context' ) ) {
			$errors = array_merge( $errors, $mapper->validate_submission_context( $lead['source_preset'], $lead['page_profile'] ) );
		}

		// Legacy forms still require Name. Studio-managed presets honor their
		// independently configured desktop/mobile required-state matrix instead.
		$studio_preset = class_exists( 'YBY_Popup_Field_Studio' ) && YBY_Popup_Field_Studio::has_preset( isset( $lead['source_preset'] ) ? $lead['source_preset'] : '' );
		if ( ! $studio_preset && '' === trim( (string) $lead['name'] ) ) {
			$errors['name'] = __( 'Name is required', 'yby-core' );
		}

		$email    = trim( (string) $lead['email'] );
		$whatsapp = trim( (string) $lead['whatsapp'] );

		// Resolve validation from the trusted site preset. Never trust a client
		// supplied contact requirement to weaken backend validation.
		$preset = class_exists( 'YBY_Inquiry_Preset_Manager' )
			? ( new YBY_Inquiry_Preset_Manager() )->get_preset( isset( $lead['source_preset'] ) ? $lead['source_preset'] : '' )
			: null;
		$contact_requirement = is_array( $preset ) && isset( $preset['validation']['contact_requirement'] )
			? sanitize_key( $preset['validation']['contact_requirement'] )
			: 'email_or_whatsapp';

		if ( 'email' === $contact_requirement ) {
			if ( '' === $email ) {
				$errors['email'] = __( 'Email is required', 'yby-core' );
			} elseif ( ! is_email( $email ) ) {
				$errors['email'] = __( 'Invalid email', 'yby-core' );
			}
		} elseif ( 'whatsapp' === $contact_requirement ) {
			if ( '' === $whatsapp ) {
				$errors['whatsapp'] = __( 'WhatsApp is required', 'yby-core' );
			}
		} elseif ( 'email_or_whatsapp' === $contact_requirement && '' === $email && '' === $whatsapp ) {
			$errors['email']    = __( 'Email or WhatsApp is required', 'yby-core' );
			$errors['whatsapp'] = __( 'Email or WhatsApp is required', 'yby-core' );
		}

		// All modes validate any email that the visitor elects to provide.
		if ( '' !== $email && ! is_email( $email ) ) {
			$errors['email'] = __( 'Invalid email', 'yby-core' );
		}

		if ( '' !== $whatsapp && ! preg_match( '/^[+()\d\s-]{6,}$/', $whatsapp ) ) {
			$errors['whatsapp'] = __( 'Invalid WhatsApp', 'yby-core' );
		}

		if ( class_exists( 'YBY_Popup_Field_Studio' ) ) {
			$errors = array_merge( $errors, YBY_Popup_Field_Studio::required_errors( $lead ) );
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
	 * @return array{status:string, error_code:string} Notification transport status (not mailbox delivery).
	 */
	protected function dispatch_notification( $lead, $result ) {
		return YBY_Lead_Notification_Outbox::enqueue_and_attempt( $lead, $result );
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
