<?php
/**
 * Email subject renderer.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render safe, plain-text email subjects.
 */
class YBY_Email_Subject_Renderer {

	/**
	 * Render a subject from a template and lead payload.
	 *
	 * @param string               $template Subject template.
	 * @param array<string, mixed> $lead Lead payload.
	 * @return string
	 */
	public function render( $template, $lead ) {
		$template = YBY_Config::sanitize_subject_template( $template );

		if ( '' === $template ) {
			$template = '[YBY New Lead] {country} | {farm_size} | {crop} | {case_id}';
		}

		$map = array(
			'{case_id}'           => $this->value( $lead, 'case_id' ),
			'{name}'              => $this->value( $lead, 'name' ),
			'{country}'           => $this->value( $lead, 'country' ),
			'{crop}'              => $this->value( $lead, 'crop' ),
			'{farm_size}'         => $this->value( $lead, 'farm_size' ),
			'{water_source}'      => $this->value( $lead, 'water_source' ),
			'{recommended_system}' => $this->value( $lead, 'recommended_system' ),
			'{project_id}'        => $this->value( $lead, 'project_id' ),
			'{product_interest}'   => $this->value( $lead, 'product_interest' ),
			'{source_component}'   => $this->value( $lead, 'source_component' ),
		);

		$subject = strtr( $template, $map );
		$subject = preg_replace( '/\{[a-z0-9_]+\}/i', '', $subject );
		$subject = preg_replace( '/[|]{2,}/', '|', (string) $subject );
		$subject = preg_replace( '/\s*\|\s*\|+/', ' | ', (string) $subject );
		$subject = preg_replace( '/\s{2,}/', ' ', (string) $subject );
		$subject = trim( trim( (string) $subject ), "|\t\n\r\0\x0B" );

		return sanitize_text_field( $subject );
	}

	/**
	 * Read a lead value safely.
	 *
	 * @param array<string, mixed> $lead Lead payload.
	 * @param string               $key Value key.
	 * @return string
	 */
	protected function value( $lead, $key ) {
		return YBY_Config::sanitize_display_text( $lead[ $key ] ?? '' );
	}
}
