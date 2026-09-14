<?php
/**
 * Email Template OS runtime registry.
 *
 * Source-only foundation for Andy Core v1.5.8. No hooks are registered here.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers email template identities from runtime providers.
 */
class YBY_Email_Template_Registry {

	/**
	 * Return every currently discoverable template identity.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function discover() {
		$items = array();

		foreach ( $this->discover_woocommerce() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		foreach ( $this->wordpress_templates() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		foreach ( $this->andy_core_templates() as $item ) {
			$items[ $item['template_key'] ] = $item;
		}

		ksort( $items );

		return $items;
	}

	/**
	 * Discover WooCommerce and third-party mailer classes at runtime.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function discover_woocommerce() {
		if ( ! function_exists( 'WC' ) || ! WC() || ! is_object( WC()->mailer() ) ) {
			return array();
		}

		$emails = WC()->mailer()->get_emails();

		if ( ! is_array( $emails ) ) {
			return array();
		}

		$result = array();

		foreach ( $emails as $email ) {
			if ( ! is_object( $email ) ) {
				continue;
			}

			$id = isset( $email->id ) ? sanitize_key( (string) $email->id ) : '';

			if ( '' === $id ) {
				continue;
			}

			$label = isset( $email->title ) ? sanitize_text_field( (string) $email->title ) : $id;
			$description = isset( $email->description ) ? sanitize_text_field( (string) $email->description ) : '';
			$enabled = method_exists( $email, 'is_enabled' ) ? (bool) $email->is_enabled() : true;
			$is_customer = method_exists( $email, 'is_customer_email' ) ? (bool) $email->is_customer_email() : false;

			$result[] = $this->normalize_item(
				array(
					'template_key'       => 'woocommerce:' . $id,
					'provider'           => 'woocommerce',
					'source_id'          => $id,
					'source_class'       => get_class( $email ),
					'audience'           => $is_customer ? 'customer' : 'admin',
					'label'              => $label,
					'description'        => $description,
					'runtime_available'  => true,
					'runtime_enabled'    => $enabled,
					'legacy_template_ref'=> '',
					'capabilities'       => array( 'subject', 'preheader', 'body', 'cta', 'dynamic_sections' ),
				)
			);
		}

		return $result;
	}

	/**
	 * WordPress system-mail identities supported by V1 adapters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function wordpress_templates() {
		return array(
			$this->normalize_item( array(
				'template_key'      => 'wordpress:new_user',
				'provider'          => 'wordpress',
				'source_id'         => 'new_user',
				'source_class'      => '',
				'audience'          => 'customer',
				'label'             => 'New User / Account',
				'description'       => 'WordPress new-user notification adapter.',
				'runtime_available' => true,
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'body', 'cta' ),
			) ),
			$this->normalize_item( array(
				'template_key'      => 'wordpress:reset_password',
				'provider'          => 'wordpress',
				'source_id'         => 'reset_password',
				'source_class'      => '',
				'audience'          => 'customer',
				'label'             => 'Reset Password',
				'description'       => 'WordPress password-reset notification adapter.',
				'runtime_available' => true,
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'body', 'cta' ),
			) ),
		);
	}

	/**
	 * Andy Core business-mail identities supported by V1.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function andy_core_templates() {
		return array(
			$this->normalize_item( array(
				'template_key'      => 'andy_core:inquiry_internal_notification',
				'provider'          => 'andy_core',
				'source_id'         => 'inquiry_internal_notification',
				'source_class'      => 'YBY_Email_Notification_Provider',
				'audience'          => 'admin',
				'label'             => 'Inquiry Internal Notification',
				'description'       => 'Internal notification generated when a website inquiry is received.',
				'runtime_available' => class_exists( 'YBY_Email_Notification_Provider' ),
				'runtime_enabled'   => true,
				'capabilities'      => array( 'subject', 'body', 'dynamic_sections' ),
			) ),
		);
	}

	/**
	 * Normalize one registry row to a stable contract.
	 *
	 * @param array<string, mixed> $item Raw item.
	 * @return array<string, mixed>
	 */
	protected function normalize_item( $item ) {
		$item = is_array( $item ) ? $item : array();

		return array(
			'template_key'        => sanitize_key( str_replace( ':', '_', (string) ( $item['template_key'] ?? '' ) ) ) ? (string) ( $item['template_key'] ?? '' ) : '',
			'provider'            => sanitize_key( (string) ( $item['provider'] ?? '' ) ),
			'source_id'           => sanitize_key( (string) ( $item['source_id'] ?? '' ) ),
			'source_class'        => sanitize_text_field( (string) ( $item['source_class'] ?? '' ) ),
			'audience'            => in_array( (string) ( $item['audience'] ?? '' ), array( 'customer', 'admin', 'system' ), true ) ? (string) $item['audience'] : 'system',
			'label'               => sanitize_text_field( (string) ( $item['label'] ?? '' ) ),
			'description'         => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
			'runtime_available'   => ! empty( $item['runtime_available'] ),
			'runtime_enabled'     => ! empty( $item['runtime_enabled'] ),
			'legacy_template_ref' => sanitize_text_field( (string) ( $item['legacy_template_ref'] ?? '' ) ),
			'capabilities'        => array_values( array_unique( array_map( 'sanitize_key', (array) ( $item['capabilities'] ?? array() ) ) ) ),
		);
	}
}
