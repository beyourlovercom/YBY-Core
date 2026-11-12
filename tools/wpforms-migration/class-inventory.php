<?php
/**
 * WPForms -> Andy Core migration inventory contracts.
 *
 * READ-ONLY: This library analyzes schema metadata. It does not migrate form
 * submissions, send emails, update options, alter pages, or remove plugins.
 *
 * @package YBY_Core
 */

final class Andy_WPForms_Migration_Inventory {
	/**
	 * Classify one WPForms definition without exporting recipient addresses,
	 * integrations, entry data, secrets, notification bodies or field defaults.
	 *
	 * @param int   $form_id WPForms post ID.
	 * @param array $source  Decoded WPForms JSON.
	 * @return array Schema and explicit manual-review blockers.
	 */
	public static function inspect_form( $form_id, $source ) {
		$form_id = abs( (int) $form_id );
		$fields  = array();
		$blocks  = array();
		$map     = array(
			'name'     => 'text',
			'email'    => 'email',
			'text'     => 'text',
			'textarea' => 'textarea',
			'phone'    => 'tel',
			'numbers'  => 'number',
			'number'   => 'number',
			'select'   => 'select',
			'radio'    => 'radio',
		);

		$definitions = isset( $source['fields'] ) && is_array( $source['fields'] )
			? $source['fields'] : array();

		foreach ( $definitions as $key => $field ) {
			if ( ! is_array( $field ) ) {
				$blocks[] = 'invalid_field_definition';
				continue;
			}

			$type = isset( $field['type'] ) && is_string( $field['type'] )
				? strtolower( trim( $field['type'] ) ) : 'unknown';
			$id = isset( $field['id'] ) && is_scalar( $field['id'] )
				? (string) $field['id'] : (string) $key;
			$supported = isset( $map[ $type ] );
			if ( ! $supported ) {
				$blocks[] = 'unsupported_field_type:' . preg_replace( '/[^a-z0-9_-]/', '', $type );
			}

			$choice_count = isset( $field['choices'] ) && is_array( $field['choices'] )
				? count( $field['choices'] ) : 0;
			$fields[] = array(
				'id'            => substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $id ), 0, 50 ),
				'source_type'   => $type,
				'target_type'   => $supported ? $map[ $type ] : null,
				'required'      => ! empty( $field['required'] ),
				'choice_count'  => $choice_count,
				'supported'     => $supported,
				// No field values, defaults, choices or notification content exported.
			);

			if ( $supported && in_array( $type, array( 'select', 'radio' ), true ) && 0 === $choice_count ) {
				$blocks[] = 'select_without_choices';
			}
		}

		$integrations = array();
		foreach ( array( 'providers', 'payments', 'conditional_logic' ) as $key ) {
			if ( ! empty( $source[ $key ] ) ) {
				$integrations[] = $key;
				$blocks[]       = 'integration_requires_review:' . $key;
			}
		}
		if ( ! empty( $source['settings']['notifications'] ) ) {
			$blocks[] = 'notification_routing_requires_review';
		}
		if ( ! empty( $source['settings']['confirmations'] ) ) {
			$blocks[] = 'confirmation_behavior_requires_review';
		}

		// The current per-site Field Studio allows at most 30 custom fields.
		if ( count( $fields ) > 30 ) {
			$blocks[] = 'field_studio_capacity_exceeded';
		}
		if ( empty( $fields ) ) {
			$blocks[] = 'empty_form_or_provider_only';
		}

		return array(
			'form_id'                 => $form_id,
			'suggested_preset_id'     => 'site_wpforms_' . $form_id,
			'fields'                  => $fields,
			'integration_categories'  => $integrations,
			'has_notifications'       => ! empty( $source['settings']['notifications'] ),
			'has_confirmations'       => ! empty( $source['settings']['confirmations'] ),
			'blockers'                => array_values( array_unique( $blocks ) ),
			'ready_for_uninstall'     => false,
			'next_step'               => 'review_field_mapping_and_validate_real_inquiry_delivery',
		);
	}

	/**
	 * Summarize one site's forms without suggesting destructive actions.
	 *
	 * @param array $forms Results of inspect_form().
	 * @return array Summary and safety contract.
	 */
	public static function summarize( $forms ) {
		$field_count = 0;
		$unsupported = 0;
		$needs_review = 0;
		foreach ( $forms as $form ) {
			$field_count += count( $form['fields'] );
			foreach ( $form['fields'] as $field ) {
				if ( ! $field['supported'] ) {
					++$unsupported;
				}
			}
			if ( ! empty( $form['blockers'] ) ) {
				++$needs_review;
			}
		}
		return array(
			'form_count'                    => count( $forms ),
			'field_count'                   => $field_count,
			'unsupported_field_count'       => $unsupported,
			'forms_with_explicit_blockers'  => $needs_review,
			'install_andy_core_now'         => false,
			'disable_wpforms_now'           => false,
			'delete_wpforms_data'           => false,
			'production_write_authorized'   => false,
		);
	}
}
