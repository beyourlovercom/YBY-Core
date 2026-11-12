<?php
/**
 * Generate an INACTIVE draft of Popup Field Studio V2 definitions for one form.
 *
 * The output is for offline review, never direct site application. There are
 * no WordPress side effects, recipient exports, form entries or network calls.
 *
 * @package YBY_Core
 */
require_once __DIR__ . '/class-inventory.php';

final class Andy_WPForms_Migration_Draft {
	/**
	 * Build a disabled per-form draft preserving order and required flags.
	 *
	 * @param int   $form_id WPForms post ID.
	 * @param array $source  Decoded WPForms JSON configuration.
	 * @return array Draft and fail-closed review requirements.
	 */
	public static function build_form( $form_id, $source ) {
		$inventory = Andy_WPForms_Migration_Inventory::inspect_form( $form_id, $source );
		$id        = $inventory['form_id'];
		$preset_id = $inventory['suggested_preset_id'];
		$fields    = array();
		$configs   = array();
		$bindings  = array();
		$blockers  = $inventory['blockers'];
		$seen      = array();
		$has_email = false;
		$type_map  = array(
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

		foreach ( $definitions as $key => $original ) {
			if ( ! is_array( $original ) ) {
				$blockers[] = 'invalid_field_definition';
				continue;
			}
			$source_id = isset( $original['id'] ) && is_scalar( $original['id'] )
				? (string) $original['id'] : (string) $key;
			$clean_id = strtolower( preg_replace( '/[^a-z0-9_]/i', '', $source_id ) );
			$type     = isset( $original['type'] ) && is_string( $original['type'] )
				? strtolower( trim( $original['type'] ) ) : 'unknown';
			if ( '' === $clean_id || strlen( $clean_id ) > 20 ) {
				$blockers[] = 'invalid_source_field_id';
				continue;
			}
			if ( 'name' === $type || 'email' === $type ) {
				$target_id = $type;
				if ( isset( $seen[ $type ] ) ) {
					$blockers[] = 'duplicate_builtin_field:' . $type;
					continue;
				}
				$seen[ $type ] = true;
				if ( 'email' === $type ) {
					$has_email = true;
				}
				if ( 'name' === $type && ! empty( $original['format'] )
					&& ! in_array( $original['format'], array( 'simple', 'single' ), true ) ) {
					$blockers[] = 'multipart_name_requires_review';
				}
			} elseif ( isset( $type_map[ $type ] ) ) {
				$target_id = 'site_wpf' . $id . '_f' . $clean_id;
				if ( strlen( $target_id ) > 60 || isset( $fields[ $target_id ] ) ) {
					$blockers[] = 'duplicate_or_oversized_custom_field_id';
					continue;
				}
				$label   = self::safe_text( $original['label'] ?? 'Field', 120 );
				$options = array();
				if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
					$choices = isset( $original['choices'] ) && is_array( $original['choices'] )
						? $original['choices'] : array();
					if ( count( $choices ) > 30 ) {
						$blockers[] = 'choice_capacity_exceeded';
					}
					foreach ( array_slice( $choices, 0, 30 ) as $choice ) {
						if ( ! is_array( $choice ) ) {
							$blockers[] = 'invalid_choice';
							continue;
						}
						$label_choice = self::safe_text( $choice['label'] ?? '', 100 );
						if ( '' === $label_choice ) {
							$blockers[] = 'blank_choice_label';
							continue;
						}
						if ( isset( $choice['value'] ) && (string) $choice['value'] !== (string) ( $choice['label'] ?? '' ) ) {
							$blockers[] = 'choice_value_semantics_requires_review';
						}
						// Only visible labels are placed in draft options.
						$options[] = array( 'label' => $label_choice, 'value' => $label_choice );
					}
					if ( empty( $options ) || ! empty( $original['dynamic_choices'] ) ) {
						$blockers[] = 'dynamic_or_empty_choices_require_review';
					}
				}
				$fields[ $target_id ] = array(
					'id'          => $target_id,
					'type'        => $type_map[ $type ],
					'label'       => $label,
					'placeholder' => '',
					'options'     => $options,
				);
			} else {
				$blockers[] = 'unsupported_field_type:' . preg_replace( '/[^a-z0-9_-]/', '', $type );
				continue;
			}

			$label = self::safe_text( $original['label'] ?? ucfirst( $type ), 120 );
			$mode  = array(
				'enabled'     => true,
				'required'    => ! empty( $original['required'] ),
				'label'       => $label,
				'placeholder' => '',
			);
			$configs[ $target_id ] = array( 'desktop' => $mode, 'mobile' => $mode );
			$bindings[ $source_id ] = $target_id;
		}
		if ( ! $has_email ) {
			$blockers[] = 'contact_destination_requires_review';
		}
		if ( count( $fields ) > 30 ) {
			$blockers[] = 'field_studio_capacity_exceeded';
		}
		if ( empty( $configs ) ) {
			$blockers[] = 'no_fields_mapped';
		}

		// No status can activate this draft; the site administrator must edit,
		// verify and opt in using the normal Andy Core settings/nonce flow.
		$preset = array(
			'id'            => $preset_id,
			'label'         => 'WPForms ' . $id . ' (review draft)',
			'enabled'       => false,
			'layout'        => 'classic',
			'title'         => '',
			'subtitle'      => '',
			'image'         => '',
			'page_profiles' => array(),
			'configs'       => $configs,
		);
		return array(
			'form_id'              => $id,
			'source_field_bindings'=> $bindings,
			'candidate_state'      => array(
				'version' => 2,
				'fields'  => $fields,
				'presets' => array( $preset_id => $preset ),
			),
			'blockers'             => array_values( array_unique( $blockers ) ),
			'ready_to_apply'       => false,
			'ready_to_uninstall'   => false,
			'manual_actions'      => array(
				'review_source_notifications_and_integrations',
				'confirm_contact_data_mapping_and_consent',
				'compare_choice_values_and_validation',
				'map_source_embeds_to_page_owned_inquiry',
				'validate_actual_submit_mail_mobile_and_spam_controls',
			),
		);
	}

	/**
	 * Strip markup from display labels, excluding values/defaults/addresses.
	 *
	 * @param mixed $value Label to normalize.
	 * @param int   $limit Maximum bytes.
	 * @return string Printable label.
	 */
	private static function safe_text( $value, $limit ) {
		return is_scalar( $value ) ? substr( trim( strip_tags( (string) $value ) ), 0, $limit ) : '';
	}
}
