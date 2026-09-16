<?php
/**
 * Email Template OS storage contract.
 *
 * Source-only foundation for Andy Core v1.5.8. This class does not install
 * tables or register hooks by itself.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical storage and payload constants for Email Template OS V1.
 */
class YBY_Email_Template_Schema {

	const DATABASE_VERSION = '1.5.0';
	const TEMPLATES_TABLE = 'yby_email_templates';
	const VERSIONS_TABLE = 'yby_email_template_versions';
	const DESIGN_OPTION = 'yby_email_design_settings';

	const STATUS_DRAFT = 'draft';
	const STATUS_PUBLISHED = 'published';
	const STATUS_DISABLED = 'disabled';

	/**
	 * Return allowed template states.
	 *
	 * @return array<int, string>
	 */
	public static function statuses() {
		return array(
			self::STATUS_DRAFT,
			self::STATUS_PUBLISHED,
			self::STATUS_DISABLED,
		);
	}

	/**
	 * Return the canonical structured payload shape.
	 *
	 * Values are intentionally presentation-neutral. Header/footer markup and
	 * shared Email VI are renderer-owned and never duplicated into this payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function payload_defaults() {
		return array(
			'subject'                    => '',
			'preheader'                  => '',
			'heading'                    => '',
			'intro_copy'                 => '',
			'dynamic_sections'           => array(),
			'primary_cta'                => array(
				'label' => '',
				'url'   => '',
			),
			'secondary_copy'             => '',
			'additional_content'         => '',
			'enabled_blocks'             => array(),
			'template_specific_settings' => array(),
		);
	}

	/**
	 * Return governed Email VI defaults approved for v1.5.8.
	 *
	 * @return array<string, mixed>
	 */
	public static function design_defaults() {
		return array(
			'canvas_color'       => '#F5F5F7',
			'surface_color'      => '#FFFFFF',
			'primary_color'      => '#9B3749',
			'primary_text_color' => '#FFFFFF',
			'text_color'         => '#17211B',
			'muted_color'        => '#6B746E',
			'border_color'       => '#E5E7E6',
			'content_width'      => 600,
			'card_radius'        => 12,
			'cta_radius'         => 8,
			'font_stack'         => 'Poppins, Arial, Helvetica, sans-serif',
			'logo_url'           => '',
			'footer_text'        => '',
		);
	}
}
