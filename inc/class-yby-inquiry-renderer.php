<?php
/**
 * Inquiry modal renderer.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders neutral inquiry modal markup.
 */
class YBY_Inquiry_Renderer {
	public function render_inline( $preset, $fields, $attributes = array() ) {
		if ( ! is_array( $preset ) || empty( $preset['id'] ) ) { return ''; }
		$id = $this->normalize_modal_id( isset( $attributes['id'] ) ? $attributes['id'] : 'yby-inquiry-' . $preset['id'] );
		$title = isset( $attributes['title'] ) && '' !== $attributes['title'] ? $attributes['title'] : $preset['label'];
		$submit = isset( $attributes['submit_label'] ) && '' !== $attributes['submit_label'] ? $attributes['submit_label'] : 'Submit Inquiry';
		$classes = array_merge( array( 'yby-shortcode-inquiry' ), isset( $attributes['class'] ) && is_array( $attributes['class'] ) ? $attributes['class'] : array() );
		$form = '';
		foreach ( $fields as $field ) { $form .= $this->render_field( $field, array( 'modal_id' => $id ) ); }
		$form .= $this->render_metadata_fields( array_merge( $preset, array( 'source_component' => 'inquiry_shortcode' ) ) );
		$out  = '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-yby-inquiry-inline data-yby-preset="' . esc_attr( $preset['id'] ) . '" data-yby-source-component="inquiry_shortcode">';
		$out .= '<h2>' . esc_html( $title ) . '</h2><form class="yby-inquiry-form" data-yby-inquiry-form data-yby-preset="' . esc_attr( $preset['id'] ) . '" data-yby-contact-requirement="' . esc_attr( $preset['validation']['contact_requirement'] ?? 'none' ) . '" data-yby-form-version="' . esc_attr( $preset['version'] ) . '" novalidate>' . $form . '<div class="yby-inquiry-form__status" data-yby-inquiry-status role="status"></div><div class="yby-inquiry-form__error" data-yby-inquiry-error role="alert" hidden></div><button type="submit" class="yby-inquiry-form__submit" data-yby-inquiry-submit disabled>' . esc_html( $submit ) . '</button></form></div>';
		return $out;
	}

	/**
	 * Render an inquiry modal.
	 *
	 * @param array<string, mixed> $preset Sanitized preset.
	 * @param array<int, array<string, mixed>> $fields Ordered field definitions.
	 * @param array<string, mixed> $attributes Sanitized renderer attributes.
	 * @return string
	 */
	public function render_modal( $preset, $fields, $attributes = array(), $mobile_fields = array() ) {
		if ( ! is_array( $preset ) || empty( $preset['id'] ) ) {
			return '';
		}

		$modal_id      = $this->normalize_modal_id( isset( $attributes['id'] ) ? $attributes['id'] : '' );
		$title               = isset( $attributes['title'] ) && '' !== $attributes['title'] ? $attributes['title'] : $preset['label'];
		$mobile_title        = isset( $attributes['mobile_title'] ) && '' !== $attributes['mobile_title'] ? $attributes['mobile_title'] : $title;
		$subtitle            = isset( $attributes['subtitle'] ) ? (string) $attributes['subtitle'] : '';
		$mobile_subtitle     = isset( $attributes['mobile_subtitle'] ) && '' !== $attributes['mobile_subtitle'] ? (string) $attributes['mobile_subtitle'] : $subtitle;
		$eyebrow             = isset( $attributes['eyebrow'] ) ? (string) $attributes['eyebrow'] : '';
		$brand_mark          = isset( $attributes['brand_mark'] ) ? (string) $attributes['brand_mark'] : '';
		$brand_name          = isset( $attributes['brand_name'] ) ? (string) $attributes['brand_name'] : '';
		$brand_tagline       = isset( $attributes['brand_tagline'] ) ? (string) $attributes['brand_tagline'] : '';
		$privacy_note        = isset( $attributes['privacy_note'] ) ? (string) $attributes['privacy_note'] : '';
		$submit_label        = isset( $attributes['submit_label'] ) && '' !== $attributes['submit_label'] ? $attributes['submit_label'] : 'Submit Inquiry';
		$mobile_submit_label = isset( $attributes['mobile_submit_label'] ) && '' !== $attributes['mobile_submit_label'] ? $attributes['mobile_submit_label'] : $submit_label;
		$image         = isset( $attributes['image'] ) ? $attributes['image'] : '';
		$image_alt     = isset( $attributes['image_alt'] ) ? $attributes['image_alt'] : '';
		$layout        = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : '';
		$mobile_layout = isset( $attributes['mobile_layout'] ) ? sanitize_key( $attributes['mobile_layout'] ) : '';
		$extra_classes = isset( $attributes['class'] ) && is_array( $attributes['class'] ) ? $attributes['class'] : array();
		$title_id      = $modal_id . '-title';
		$form_markup   = '<div class="yby-inquiry-form__fields">';

		foreach ( $fields as $field ) {
			$field = $this->apply_field_presentation_overrides( $field, $attributes, false );
			$form_markup .= $this->render_field(
				$field,
				array(
					'modal_id' => $modal_id,
					'responsive' => 'desktop',
				)
			);
		}
		foreach ( $mobile_fields as $field ) {
			$field = $this->apply_field_presentation_overrides( $field, $attributes, true );
			$form_markup .= $this->render_field(
				$field,
				array(
					'modal_id' => $modal_id,
					'responsive' => 'mobile',
				)
			);
		}
		$form_markup .= '</div>';

		$form_markup .= $this->render_metadata_fields( $preset );

		$classes = array_merge( array( 'yby-inquiry-modal' ), $extra_classes );

		if ( '' !== $layout ) {
			$classes[] = 'yby-inquiry-modal--layout-' . str_replace( '_', '-', $layout );
		}
		if ( '' !== $mobile_layout ) {
			$classes[] = 'yby-inquiry-modal--mobile-' . str_replace( '_', '-', $mobile_layout );
		}
		if ( '' !== $image ) {
			$classes[] = 'yby-inquiry-modal--has-media';
		}

		$output  = '<div id="' . esc_attr( $modal_id ) . '" class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" data-yby-inquiry-modal data-yby-preset="' . esc_attr( $preset['id'] ) . '" data-yby-preset-version="' . esc_attr( $preset['version'] ) . '" data-yby-source-component="' . esc_attr( $preset['source_component'] ) . '" data-yby-source-page="' . esc_attr( sanitize_text_field( (string) $title ) ) . '"' . ( '' !== $layout ? ' data-yby-layout="' . esc_attr( $layout ) . '"' : '' ) . ( '' !== $mobile_layout ? ' data-yby-mobile-layout="' . esc_attr( $mobile_layout ) . '"' : '' ) . ' aria-hidden="true" hidden>';
		$output .= '<div class="yby-inquiry-modal__backdrop" data-yby-modal-close></div>';
		$output .= '<div class="yby-inquiry-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="' . esc_attr( $title_id ) . '" tabindex="-1">';
		$output .= '<button type="button" class="yby-inquiry-modal__close" data-yby-modal-close aria-label="' . esc_attr__( 'Close inquiry form', 'yby-core' ) . '"><span aria-hidden="true">&times;</span></button>';

		if ( '' !== $image ) {
			$output .= '<div class="yby-inquiry-modal__media"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $image_alt ) . '" loading="lazy" decoding="async"></div>';
		}

		$output .= '<div class="yby-inquiry-modal__content">';
		if ( '' !== $brand_mark || '' !== $brand_name || '' !== $brand_tagline ) {
			$output .= '<div class="yby-inquiry-modal__brandline"><div class="yby-inquiry-modal__brand"><span class="yby-inquiry-modal__brand-mark">' . esc_html( $brand_mark ) . '</span><span class="yby-inquiry-modal__brand-name">' . esc_html( $brand_name ) . '</span></div><span class="yby-inquiry-modal__brand-tagline">' . esc_html( $brand_tagline ) . '</span></div>';
		}
		if ( '' !== $eyebrow ) { $output .= '<p class="yby-inquiry-modal__eyebrow">' . esc_html( $eyebrow ) . '</p>'; }
		$output .= '<h2 id="' . esc_attr( $title_id ) . '"><span class="yby-inquiry-modal__title--desktop">' . esc_html( $title ) . '</span><span class="yby-inquiry-modal__title--mobile">' . esc_html( $mobile_title ) . '</span></h2>';
		if ( '' !== $subtitle || '' !== $mobile_subtitle ) {
			$output .= '<p class="yby-inquiry-modal__subtitle"><span class="yby-inquiry-modal__subtitle--desktop">' . esc_html( $subtitle ) . '</span><span class="yby-inquiry-modal__subtitle--mobile">' . esc_html( $mobile_subtitle ) . '</span></p>';
		}
		$output .= '<form class="yby-inquiry-form" data-yby-inquiry-form data-yby-preset="' . esc_attr( $preset['id'] ) . '" data-yby-contact-requirement="' . esc_attr( isset( $preset['validation']['contact_requirement'] ) ? $preset['validation']['contact_requirement'] : 'none' ) . '" data-yby-form-version="' . esc_attr( $preset['version'] ) . '" data-yby-source-page="' . esc_attr( sanitize_text_field( (string) $title ) ) . '" novalidate>';
		$output .= $form_markup;
		$output .= '<div class="yby-inquiry-form__status" data-yby-inquiry-status role="status" aria-live="polite"></div>';
		$output .= '<div class="yby-inquiry-form__error" data-yby-inquiry-error role="alert" aria-live="assertive" hidden></div>';
		$output .= '<button type="submit" class="yby-inquiry-form__submit" data-yby-inquiry-submit disabled><span class="yby-inquiry-form__submit-label--desktop">' . esc_html( $submit_label ) . '</span><span class="yby-inquiry-form__submit-label--mobile">' . esc_html( $mobile_submit_label ) . '</span></button>';
		if ( '' !== $privacy_note ) { $output .= '<p class="yby-inquiry-modal__privacy-note">' . esc_html( $privacy_note ) . '</p>'; }
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}


	/**
	 * Apply page-owned labels/placeholders without mutating the global field registry.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<string, mixed> $attributes Sanitized modal attributes.
	 * @param bool                 $mobile Whether mobile overrides should win.
	 * @return array<string, mixed>
	 */
	protected function apply_field_presentation_overrides( $field, $attributes, $mobile = false ) {
		if ( ! is_array( $field ) || empty( $field['id'] ) ) {
			return $field;
		}
		$field_id = sanitize_key( $field['id'] );
		$label_maps = array( isset( $attributes['field_labels'] ) && is_array( $attributes['field_labels'] ) ? $attributes['field_labels'] : array() );
		$placeholder_maps = array( isset( $attributes['field_placeholders'] ) && is_array( $attributes['field_placeholders'] ) ? $attributes['field_placeholders'] : array() );
		if ( $mobile ) {
			$label_maps[] = isset( $attributes['mobile_field_labels'] ) && is_array( $attributes['mobile_field_labels'] ) ? $attributes['mobile_field_labels'] : array();
			$placeholder_maps[] = isset( $attributes['mobile_field_placeholders'] ) && is_array( $attributes['mobile_field_placeholders'] ) ? $attributes['mobile_field_placeholders'] : array();
		}
		foreach ( $label_maps as $map ) {
			if ( isset( $map[ $field_id ] ) ) {
				$field['label'] = $map[ $field_id ];
			}
		}
		foreach ( $placeholder_maps as $map ) {
			if ( isset( $map[ $field_id ] ) ) {
				$field['placeholder'] = $map[ $field_id ];
			}
		}
		return $field;
	}

	/**
	 * Render one field definition.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param array<string, mixed> $context Render context.
	 * @return string
	 */
	public function render_field( $field, $context = array() ) {
		if ( ! is_array( $field ) || empty( $field['id'] ) || empty( $field['type'] ) ) {
			return '';
		}

		$type      = (string) $field['type'];
		$modal_id  = isset( $context['modal_id'] ) ? (string) $context['modal_id'] : 'yby-inquiry-modal';
		$responsive = isset( $context['responsive'] ) ? sanitize_key( $context['responsive'] ) : '';
		$dom_suffix = '' !== $responsive ? '-' . $responsive : '';
		$field_id  = $this->build_field_id( $modal_id, (string) $field['id'] ) . $dom_suffix;
		$error_id  = $field_id . '-error';
		$is_hidden = 'hidden' === $type;

		switch ( $type ) {
			case 'text':
			case 'email':
			case 'tel':
				$control = $this->render_input_field( $field, $field_id, $error_id, $type );
				break;
			case 'textarea':
				$control = $this->render_textarea_field( $field, $field_id, $error_id );
				break;
			case 'select':
				$control = $this->render_select_field( $field, $field_id, $error_id );
				break;
			case 'checkbox':
				$control = $this->render_checkbox_field( $field, $field_id, $error_id );
				break;
			case 'hidden':
				$control = $this->render_hidden_field( $field, $field_id );
				break;
			default:
				return '';
		}

		if ( 'mobile' === $responsive && '' !== $control ) {
			$control = preg_replace( '/^<(input|select|textarea)(\s)/', '<$1 disabled$2', $control );
		}

		if ( $is_hidden || '' === $control ) {
			return $control;
		}

		$output  = '<div class="yby-inquiry-field yby-inquiry-field--' . esc_attr( $type ) . '" data-yby-field-wrapper="' . esc_attr( $field['id'] ) . '"' . ( '' !== $responsive ? ' data-yby-responsive="' . esc_attr( $responsive ) . '"' : '' ) . '>';
		$output .= $this->render_field_label( $field, $field_id );
		$output .= $control;
		$output .= '<span id="' . esc_attr( $error_id ) . '" class="yby-inquiry-field__error" data-yby-field-error="' . esc_attr( $field['id'] ) . '" aria-live="polite"></span>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Normalize a modal ID.
	 *
	 * @param string $modal_id Modal ID.
	 * @return string
	 */
	public function normalize_modal_id( $modal_id ) {
		$modal_id = (string) $modal_id;

		return '' !== $modal_id ? $modal_id : 'yby-inquiry-modal';
	}

	/**
	 * Build a deterministic field DOM ID.
	 *
	 * @param string $modal_id Modal ID.
	 * @param string $field_id Field ID.
	 * @return string
	 */
	public function build_field_id( $modal_id, $field_id ) {
		return $this->normalize_modal_id( $modal_id ) . '-' . sanitize_key( $field_id );
	}

	/**
	 * Render a label for a field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @return string
	 */
	protected function render_field_label( $field, $field_dom_id ) {
		$label = isset( $field['label'] ) ? $field['label'] : '';
		$output  = '<label for="' . esc_attr( $field_dom_id ) . '">';
		$output .= esc_html( $label );

		if ( ! empty( $field['required'] ) ) {
			$output .= ' <span class="yby-inquiry-field__required" aria-hidden="true">*</span>';
		}

		$output .= '</label>';

		return $output;
	}

	/**
	 * Render a text-like input.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @param string               $error_id Error element ID.
	 * @param string               $type HTML input type.
	 * @return string
	 */
	protected function render_input_field( $field, $field_dom_id, $error_id, $type ) {
		$attributes = array(
			'id="' . esc_attr( $field_dom_id ) . '"',
			'name="' . esc_attr( sanitize_key( $field['id'] ) ) . '"',
			'type="' . esc_attr( $type ) . '"',
			'data-yby-field',
			'data-yby-field-id="' . esc_attr( $field['id'] ) . '"',
			'aria-describedby="' . esc_attr( $error_id ) . '"',
		);

		if ( ! empty( $field['placeholder'] ) ) {
			$attributes[] = 'placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		}

		if ( ! empty( $field['autocomplete'] ) ) {
			$attributes[] = 'autocomplete="' . esc_attr( $field['autocomplete'] ) . '"';
		}

		if ( ! empty( $field['required'] ) ) {
			$attributes[] = 'required';
			$attributes[] = 'aria-required="true"';
		}

		return '<input ' . implode( ' ', $attributes ) . '>';
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @param string               $error_id Error element ID.
	 * @return string
	 */
	protected function render_textarea_field( $field, $field_dom_id, $error_id ) {
		$attributes = array(
			'id="' . esc_attr( $field_dom_id ) . '"',
			'name="' . esc_attr( sanitize_key( $field['id'] ) ) . '"',
			'data-yby-field',
			'data-yby-field-id="' . esc_attr( $field['id'] ) . '"',
			'aria-describedby="' . esc_attr( $error_id ) . '"',
		);

		if ( ! empty( $field['placeholder'] ) ) {
			$attributes[] = 'placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		}

		if ( ! empty( $field['autocomplete'] ) ) {
			$attributes[] = 'autocomplete="' . esc_attr( $field['autocomplete'] ) . '"';
		}

		if ( ! empty( $field['required'] ) ) {
			$attributes[] = 'required';
			$attributes[] = 'aria-required="true"';
		}

		return '<textarea ' . implode( ' ', $attributes ) . '></textarea>';
	}

	/**
	 * Render a select field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @param string               $error_id Error element ID.
	 * @return string
	 */
	protected function render_select_field( $field, $field_dom_id, $error_id ) {
		$attributes = array(
			'id="' . esc_attr( $field_dom_id ) . '"',
			'name="' . esc_attr( sanitize_key( $field['id'] ) ) . '"',
			'data-yby-field',
			'data-yby-field-id="' . esc_attr( $field['id'] ) . '"',
			'aria-describedby="' . esc_attr( $error_id ) . '"',
		);

		if ( ! empty( $field['autocomplete'] ) ) {
			$attributes[] = 'autocomplete="' . esc_attr( $field['autocomplete'] ) . '"';
		}

		if ( ! empty( $field['required'] ) ) {
			$attributes[] = 'required';
			$attributes[] = 'aria-required="true"';
		}

		$output = '<select ' . implode( ' ', $attributes ) . '>';

		foreach ( isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array() as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$output .= '<option value="' . esc_attr( isset( $option['value'] ) ? $option['value'] : '' ) . '">' . esc_html( isset( $option['label'] ) ? $option['label'] : '' ) . '</option>';
		}

		$output .= '</select>';

		return $output;
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @param string               $error_id Error element ID.
	 * @return string
	 */
	protected function render_checkbox_field( $field, $field_dom_id, $error_id ) {
		$attributes = array(
			'id="' . esc_attr( $field_dom_id ) . '"',
			'name="' . esc_attr( sanitize_key( $field['id'] ) ) . '"',
			'type="checkbox"',
			'value="1"',
			'data-yby-field',
			'data-yby-field-id="' . esc_attr( $field['id'] ) . '"',
			'aria-describedby="' . esc_attr( $error_id ) . '"',
		);

		if ( ! empty( $field['required'] ) ) {
			$attributes[] = 'required';
			$attributes[] = 'aria-required="true"';
		}

		$output  = '<div class="yby-inquiry-field__checkbox">';
		$output .= '<input ' . implode( ' ', $attributes ) . '>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a hidden field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $field_dom_id DOM field ID.
	 * @return string
	 */
	protected function render_hidden_field( $field, $field_dom_id ) {
		return '<input id="' . esc_attr( $field_dom_id ) . '" name="' . esc_attr( sanitize_key( $field['id'] ) ) . '" type="hidden" value="" data-yby-field data-yby-field-id="' . esc_attr( $field['id'] ) . '">';
	}

	/**
	 * Render preset metadata fields.
	 *
	 * @param array<string, mixed> $preset Preset definition.
	 * @return string
	 */
	protected function render_metadata_fields( $preset ) {
		$output  = '<input type="hidden" name="source_component" value="' . esc_attr( $preset['source_component'] ) . '">';
		$output .= '<input type="hidden" name="source_preset" value="' . esc_attr( $preset['id'] ) . '">';
		$output .= '<input type="hidden" name="form_version" value="' . esc_attr( $preset['version'] ) . '">';

		return $output;
	}
}
