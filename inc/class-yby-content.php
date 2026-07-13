<?php
/**
 * Content runtime engine.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content runtime helper.
 */
class YBY_Content {

	/**
	 * Standard section configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected static function section_definitions() {
		return array(
			'hero'          => array(
				'label'  => 'Hero',
				'order'  => 10,
				'blocks' => array(
					'main' => array(
						'label'  => 'Hero Main',
						'order'  => 10,
						'fields' => array(
							self::field_definition( 'eyebrow', 'text', 'Eyebrow', '', 'Free Plan and Quote' ),
							self::field_definition( 'title', 'text', 'Title', '', 'Ready to Build Your Irrigation System?' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Send your farm details and get a free irrigation plan, material list, and factory-direct quotation from YBY.' ),
							self::field_definition( 'primary_cta_label', 'text', 'Primary CTA Label', '', 'Get My Free Irrigation Plan' ),
							self::field_definition( 'primary_cta_target', 'text', 'Primary CTA Target', '', '#lead-form' ),
							self::field_definition( 'secondary_cta_label', 'text', 'Secondary CTA Label', '', 'Send on WhatsApp' ),
							self::field_definition( 'secondary_cta_target', 'text', 'Secondary CTA Target', '', '#lead-form' ),
						),
					),
				),
			),
			'pain_points'   => array(
				'label'  => 'Pain Points',
				'order'  => 20,
				'blocks' => array(
					'main' => array(
						'label'  => 'Pain Points Main',
						'order'  => 20,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Common Irrigation Challenges' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to explain the main buyer problems the page solves.' ),
						),
					),
				),
			),
			'solutions'     => array(
				'label'  => 'Solutions',
				'order'  => 30,
				'blocks' => array(
					'main' => array(
						'label'  => 'Solutions Main',
						'order'  => 30,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Recommended Solution Direction' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section for the core solution explanation.' ),
						),
					),
				),
			),
			'products'      => array(
				'label'  => 'Products',
				'order'  => 40,
				'blocks' => array(
					'main' => array(
						'label'  => 'Products Main',
						'order'  => 40,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Product Direction' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to summarize the relevant product package.' ),
							self::field_definition( 'catalog_url', 'url', 'Catalog URL', '', '' ),
						),
					),
				),
			),
			'configurator'  => array(
				'label'  => 'Configurator',
				'order'  => 50,
				'blocks' => array(
					'main' => array(
						'label'  => 'Configurator Main',
						'order'  => 50,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Quick Configurator' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to guide the user into the configurator flow.' ),
							self::field_definition( 'cta_label', 'text', 'CTA Label', '', 'Start Configurator' ),
						),
					),
				),
			),
			'advantages'    => array(
				'label'  => 'Advantages',
				'order'  => 60,
				'blocks' => array(
					'main' => array(
						'label'  => 'Advantages Main',
						'order'  => 60,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Why YBY' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section for value differentiators and support benefits.' ),
						),
					),
				),
			),
			'case_studies'  => array(
				'label'  => 'Case Studies',
				'order'  => 70,
				'blocks' => array(
					'main' => array(
						'label'  => 'Case Studies Main',
						'order'  => 70,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Case Study Highlights' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to describe example projects or proof points.' ),
						),
					),
				),
			),
			'testimonials'  => array(
				'label'  => 'Testimonials',
				'order'  => 80,
				'blocks' => array(
					'main' => array(
						'label'  => 'Testimonials Main',
						'order'  => 80,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Customer Feedback' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to summarize validated customer trust signals.' ),
						),
					),
				),
			),
			'faq'           => array(
				'label'  => 'FAQ',
				'order'  => 90,
				'blocks' => array(
					'main' => array(
						'label'  => 'FAQ Main',
						'order'  => 90,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Frequently Asked Questions' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section to define FAQ introduction and support text.' ),
						),
					),
				),
			),
			'cta'           => array(
				'label'  => 'CTA',
				'order'  => 100,
				'blocks' => array(
					'main' => array(
						'label'  => 'CTA Main',
						'order'  => 100,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Ready to Start?' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section for the primary conversion invitation.' ),
							self::field_definition( 'primary_cta_label', 'text', 'Primary CTA Label', '', 'Get My Free Irrigation Plan' ),
							self::field_definition( 'primary_cta_target', 'text', 'Primary CTA Target', '', '#lead-form' ),
							self::field_definition( 'secondary_cta_label', 'text', 'Secondary CTA Label', '', 'Send on WhatsApp' ),
							self::field_definition( 'secondary_cta_target', 'text', 'Secondary CTA Target', '', '#lead-form' ),
						),
					),
				),
			),
			'footer_cta'    => array(
				'label'  => 'Footer CTA',
				'order'  => 110,
				'blocks' => array(
					'main' => array(
						'label'  => 'Footer CTA Main',
						'order'  => 110,
						'fields' => array(
							self::field_definition( 'title', 'text', 'Title', '', 'Footer Call to Action' ),
							self::field_definition( 'body', 'textarea', 'Body', '', 'Use this section for the final footer-level CTA summary.' ),
							self::field_definition( 'primary_cta_label', 'text', 'Primary CTA Label', '', 'Get Free Plan' ),
							self::field_definition( 'primary_cta_target', 'text', 'Primary CTA Target', '', '#lead-form' ),
							self::field_definition( 'secondary_cta_label', 'text', 'Secondary CTA Label', '', 'WhatsApp' ),
							self::field_definition( 'secondary_cta_target', 'text', 'Secondary CTA Target', '', '#lead-form' ),
						),
					),
				),
			),
			'seo'           => array(
				'label'  => 'SEO',
				'order'  => 120,
				'blocks' => array(
					'main' => array(
						'label'  => 'SEO Main',
						'order'  => 120,
						'fields' => array(
							self::field_definition( 'meta_title', 'text', 'Meta Title', '', '' ),
							self::field_definition( 'meta_description', 'textarea', 'Meta Description', '', '' ),
							self::field_definition( 'og_title', 'text', 'OG Title', '', '' ),
							self::field_definition( 'og_description', 'textarea', 'OG Description', '', '' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Create field definition.
	 *
	 * @param string $id Field ID.
	 * @param string $type Field type.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $fallback Field fallback.
	 * @return array<string, string>
	 */
	protected static function field_definition( $id, $type, $label, $value, $fallback ) {
		return array(
			'id'         => (string) $id,
			'type'       => (string) $type,
			'label'      => (string) $label,
			'value'      => (string) $value,
			'visibility' => 'public',
			'locale'     => '',
			'fallback'   => (string) $fallback,
		);
	}

	/**
	 * Get current content object.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_current_content() {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_content() );
		}

		return self::get_content_by_post_id( $post_id );
	}

	/**
	 * Get content by post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_content_by_post_id( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return self::merge_with_global_config( self::get_default_content() );
		}

		$content = self::get_default_content();

		foreach ( self::section_definitions() as $section_id => $definition ) {
			$content['sections'][ $section_id ] = self::build_section( $post_id, $section_id, $definition );
		}

		$content = self::merge_with_page_profile( $content );
		$content = self::merge_with_global_config( $content );

		return self::normalize_content( $content );
	}

	/**
	 * Get default content structure.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_content() {
		$sections = array();

		foreach ( self::section_definitions() as $section_id => $definition ) {
			$sections[ $section_id ] = self::default_section_from_definition( $section_id, $definition );
		}

		return array(
			'projectId' => '',
			'sections'  => $sections,
		);
	}

	/**
	 * Merge with page profile compatibility values.
	 *
	 * @param array<string, mixed> $content Content object.
	 * @return array<string, mixed>
	 */
	public static function merge_with_page_profile( $content ) {
		$content      = is_array( $content ) ? $content : self::get_default_content();
		$page_profile = YBY_Page_Profile::get_current_profile();

		if ( ! isset( $content['sections'] ) || ! is_array( $content['sections'] ) ) {
			return $content;
		}

		$map = self::page_profile_field_map();

		foreach ( $map as $field_reference => $profile_key ) {
			$parts = explode( '.', $field_reference );

			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$section_id = $parts[0];
			$field_id   = $parts[1];

			if ( empty( $page_profile[ $profile_key ] ) ) {
				continue;
			}

			if ( empty( $content['sections'][ $section_id ]['fields'][ $field_id ]['value'] ) ) {
				$content['sections'][ $section_id ]['fields'][ $field_id ]['value'] = (string) $page_profile[ $profile_key ];
			}
		}

		return $content;
	}

	/**
	 * Merge with global config runtime values.
	 *
	 * @param array<string, mixed> $content Content object.
	 * @return array<string, mixed>
	 */
	public static function merge_with_global_config( $content ) {
		$content = is_array( $content ) ? $content : self::get_default_content();
		$runtime = YBY_Config::get_runtime_config();

		if ( ! isset( $content['sections'] ) || ! is_array( $content['sections'] ) ) {
			return $content;
		}

		$map = self::global_config_field_map( $runtime );

		foreach ( $map as $field_reference => $value ) {
			$parts = explode( '.', $field_reference );

			if ( 2 !== count( $parts ) || '' === (string) $value ) {
				continue;
			}

			$section_id = $parts[0];
			$field_id   = $parts[1];

			if ( empty( $content['sections'][ $section_id ]['fields'][ $field_id ]['value'] ) ) {
				$content['sections'][ $section_id ]['fields'][ $field_id ]['value'] = (string) $value;
			}
		}

		return self::normalize_content( $content );
	}

	/**
	 * Normalize content object.
	 *
	 * @param array<string, mixed> $content Raw content.
	 * @return array<string, mixed>
	 */
	public static function normalize_content( $content ) {
		$content           = is_array( $content ) ? $content : self::get_default_content();
		$defaults          = self::get_default_content();
		$output            = $defaults;
		$output['projectId'] = sanitize_text_field( (string) ( $content['projectId'] ?? '' ) );

		foreach ( self::section_definitions() as $section_id => $definition ) {
			$source_section = $content['sections'][ $section_id ] ?? array();
			$section        = self::default_section_from_definition( $section_id, $definition );

			$section['enabled'] = ! empty( $source_section['enabled'] );
			$section['order']   = absint( $source_section['order'] ?? $definition['order'] );

			foreach ( $section['fields'] as $field_id => $field ) {
				$source_field = $source_section['fields'][ $field_id ] ?? array();

				$section['fields'][ $field_id ] = self::normalize_field(
					$field,
					isset( $source_field['value'] ) ? $source_field['value'] : $field['value'],
					isset( $source_field['visibility'] ) ? $source_field['visibility'] : $field['visibility'],
					isset( $source_field['locale'] ) ? $source_field['locale'] : $field['locale'],
					isset( $source_field['fallback'] ) ? $source_field['fallback'] : $field['fallback']
				);
			}

			foreach ( $section['blocks'] as $block_id => $block ) {
				foreach ( $block['fields'] as $field_id => $field ) {
					$block['fields'][ $field_id ] = $section['fields'][ $field_id ];
				}

				$section['blocks'][ $block_id ] = $block;
			}

			$output['sections'][ $section_id ] = $section;
		}

		return $output;
	}

	/**
	 * Build section object from post and definition.
	 *
	 * @param int                  $post_id Post ID.
	 * @param string               $section_id Section ID.
	 * @param array<string, mixed> $definition Section definition.
	 * @return array<string, mixed>
	 */
	protected static function build_section( $post_id, $section_id, $definition ) {
		$section = self::default_section_from_definition( $section_id, $definition );

		$enabled_meta = self::get_meta_value( $post_id, 'yby_content_' . $section_id . '_enabled' );
		$order_meta   = self::get_meta_value( $post_id, 'yby_content_' . $section_id . '_order' );

		if ( '' !== (string) $enabled_meta ) {
			$section['enabled'] = ! in_array( strtolower( (string) $enabled_meta ), array( '0', 'false', 'no', 'off' ), true );
		}

		if ( '' !== (string) $order_meta ) {
			$section['order'] = absint( $order_meta );
		}

		foreach ( $section['fields'] as $field_id => $field ) {
			$base_key     = 'yby_content_' . $section_id . '_' . $field_id;
			$value        = self::get_meta_value( $post_id, $base_key );
			$visibility   = self::get_meta_value( $post_id, $base_key . '_visibility' );
			$locale       = self::get_meta_value( $post_id, $base_key . '_locale' );
			$fallback     = self::get_meta_value( $post_id, $base_key . '_fallback' );

			if ( '' !== (string) $value ) {
				$section['fields'][ $field_id ]['value'] = (string) $value;
			}

			if ( '' !== (string) $visibility ) {
				$section['fields'][ $field_id ]['visibility'] = (string) $visibility;
			}

			if ( '' !== (string) $locale ) {
				$section['fields'][ $field_id ]['locale'] = (string) $locale;
			}

			if ( '' !== (string) $fallback ) {
				$section['fields'][ $field_id ]['fallback'] = (string) $fallback;
			}
		}

		foreach ( $section['blocks'] as $block_id => $block ) {
			foreach ( $block['fields'] as $field_id => $field ) {
				$section['blocks'][ $block_id ]['fields'][ $field_id ] = $section['fields'][ $field_id ];
			}
		}

		return $section;
	}

	/**
	 * Build default section object.
	 *
	 * @param string               $section_id Section ID.
	 * @param array<string, mixed> $definition Section definition.
	 * @return array<string, mixed>
	 */
	protected static function default_section_from_definition( $section_id, $definition ) {
		$section = array(
			'id'      => (string) $section_id,
			'label'   => (string) $definition['label'],
			'enabled' => true,
			'order'   => absint( $definition['order'] ),
			'fields'  => array(),
			'blocks'  => array(),
		);

		foreach ( $definition['blocks'] as $block_id => $block_definition ) {
			$section['blocks'][ $block_id ] = array(
				'id'     => (string) $block_id,
				'label'  => (string) $block_definition['label'],
				'order'  => absint( $block_definition['order'] ),
				'fields' => array(),
			);

			foreach ( $block_definition['fields'] as $field_definition ) {
				$field_id                          = (string) $field_definition['id'];
				$section['fields'][ $field_id ]    = $field_definition;
				$section['blocks'][ $block_id ]['fields'][ $field_id ] = $field_definition;
			}
		}

		return $section;
	}

	/**
	 * Normalize field object.
	 *
	 * @param array<string, string> $field Field object.
	 * @param mixed                 $value Field value.
	 * @param mixed                 $visibility Field visibility.
	 * @param mixed                 $locale Field locale.
	 * @param mixed                 $fallback Field fallback.
	 * @return array<string, string>
	 */
	protected static function normalize_field( $field, $value, $visibility, $locale, $fallback ) {
		$field['value']      = sanitize_text_field( (string) $value );
		$field['visibility'] = sanitize_text_field( (string) $visibility );
		$field['locale']     = sanitize_text_field( (string) $locale );
		$field['fallback']   = sanitize_text_field( (string) $fallback );

		if ( 'textarea' === $field['type'] ) {
			$field['value']    = sanitize_textarea_field( (string) $value );
			$field['fallback'] = sanitize_textarea_field( (string) $fallback );
		}

		if ( 'url' === $field['type'] ) {
			$field['value']    = self::sanitize_relative_or_absolute_url( (string) $value );
			$field['fallback'] = self::sanitize_relative_or_absolute_url( (string) $fallback );
		}

		return $field;
	}

	/**
	 * Read ACF or post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @return string
	 */
	protected static function get_meta_value( $post_id, $meta_key ) {
		$value = '';

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $meta_key, $post_id );
		}

		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $post_id, $meta_key, true );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Page profile fallback map.
	 *
	 * @return array<string, string>
	 */
	protected static function page_profile_field_map() {
		return array(
			'hero.secondary_cta_target'       => 'returnPageUrl',
			'products.catalog_url'            => 'catalogUrl',
			'solutions.body'                  => 'productInterest',
			'case_studies.body'               => 'country',
			'cta.secondary_cta_target'        => 'returnPageUrl',
			'footer_cta.secondary_cta_target' => 'returnPageUrl',
		);
	}

	/**
	 * Global config fallback map.
	 *
	 * @param array<string, mixed> $runtime Runtime config.
	 * @return array<string, string>
	 */
	protected static function global_config_field_map( $runtime ) {
		return array(
			'products.catalog_url'            => (string) ( $runtime['catalogUrl'] ?? '' ),
			'hero.secondary_cta_target'       => (string) ( $runtime['returnPageUrl'] ?? '' ),
			'cta.secondary_cta_target'        => (string) ( $runtime['returnPageUrl'] ?? '' ),
			'footer_cta.secondary_cta_target' => (string) ( $runtime['returnPageUrl'] ?? '' ),
		);
	}

	/**
	 * Sanitize relative or absolute URL.
	 *
	 * @param string $value URL value.
	 * @return string
	 */
	protected static function sanitize_relative_or_absolute_url( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $value ) ) {
			return esc_url_raw( $value );
		}

		return YBY_Config::sanitize_path_value( $value );
	}
}
