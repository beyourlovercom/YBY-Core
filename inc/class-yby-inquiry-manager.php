<?php
/**
 * Inquiry foundation manager.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates inquiry foundation registries.
 */
class YBY_Inquiry_Manager {

	/**
	 * Option name for inquiry foundation settings.
	 *
	 * @var string
	 */
	const SETTINGS_OPTION_KEY = 'yby_inquiry_settings';

	/**
	 * Current inquiry schema version.
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Install default inquiry options safely.
	 *
	 * @return void
	 */
	public static function install_defaults() {
		$manager        = new self();
		$field_manager  = $manager->get_field_manager();
		$preset_manager = $manager->get_preset_manager();

		$stored_fields = get_option( YBY_Inquiry_Field_Manager::OPTION_KEY, null );
		if ( false === $stored_fields || null === $stored_fields ) {
			add_option( YBY_Inquiry_Field_Manager::OPTION_KEY, $field_manager->defaults() );
		} else {
			update_option( YBY_Inquiry_Field_Manager::OPTION_KEY, $field_manager->sanitize_fields( $stored_fields ) );
		}

		$stored_presets = get_option( YBY_Inquiry_Preset_Manager::OPTION_KEY, null );
		if ( false === $stored_presets || null === $stored_presets ) {
			add_option( YBY_Inquiry_Preset_Manager::OPTION_KEY, $preset_manager->defaults() );
		} else {
			update_option( YBY_Inquiry_Preset_Manager::OPTION_KEY, $preset_manager->sanitize_presets( $stored_presets ) );
		}

		$stored_settings = get_option( self::SETTINGS_OPTION_KEY, null );
		if ( false === $stored_settings || null === $stored_settings ) {
			add_option( self::SETTINGS_OPTION_KEY, $manager->default_settings() );
		} else {
			update_option( self::SETTINGS_OPTION_KEY, $manager->sanitize_settings( $stored_settings ) );
		}
	}

	/**
	 * Return the active inquiry schema version.
	 *
	 * @return string
	 */
	public function get_schema_version() {
		$settings = $this->sanitize_settings( get_option( self::SETTINGS_OPTION_KEY, array() ) );

		return $settings['schema_version'];
	}

	/**
	 * Return the inquiry field manager.
	 *
	 * @return YBY_Inquiry_Field_Manager
	 */
	public function get_field_manager() {
		return new YBY_Inquiry_Field_Manager();
	}

	/**
	 * Return the inquiry preset manager.
	 *
	 * @return YBY_Inquiry_Preset_Manager
	 */
	public function get_preset_manager() {
		return new YBY_Inquiry_Preset_Manager( $this->get_field_manager() );
	}

	/**
	 * Return default inquiry settings.
	 *
	 * @return array<string, string>
	 */
	protected function default_settings() {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
		);
	}

	/**
	 * Sanitize inquiry settings.
	 *
	 * @param mixed $settings Raw settings.
	 * @return array<string, string>
	 */
	protected function sanitize_settings( $settings ) {
		$defaults = $this->default_settings();
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'schema_version' => isset( $settings['schema_version'] ) && '' !== sanitize_text_field( $settings['schema_version'] )
				? sanitize_text_field( $settings['schema_version'] )
				: $defaults['schema_version'],
		);
	}
}
