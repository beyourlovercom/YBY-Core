<?php
/**
 * Inspect country-specific Bricks Code slots without rewriting executable code.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Country_Transform_Registry {
	/**
	 * Produce counts of narrowly recognized country-specific contexts.
	 * This is an evidence plan, NOT an executable transformer.
	 *
	 * @param array<int,mixed> $elements Bricks page content elements.
	 * @param string           $source Source country literal.
	 * @return array<string,int>
	 */
	public static function preview( $elements, $source ) {
		$counts = array(
			'total_code_refs' => 0,
			'form_country_defaults' => 0,
			'country_pills' => 0,
			'scenario_headings' => 0,
			'unclassified_code_refs' => 0,
			'code_nodes' => 0,
		);
		$source = trim( (string) $source );
		if ( '' === $source || ! is_array( $elements ) ) { return $counts; }
		$country = preg_quote( $source, '~' );
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) { continue; }
			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			if ( 'code' !== ( $element['name'] ?? '' ) || ! isset( $settings['code'] ) || ! is_string( $settings['code'] ) ) {
				continue;
			}
			$code = $settings['code'];
			++$counts['code_nodes'];
			$counts['total_code_refs'] += preg_match_all( '~' . $country . '~i', $code );

			// Only a country form field whose name and value occur on ONE input.
			$form_pattern = '~<input\b[^>]*\bname=["\x27](?:selected_country|country)["\x27][^>]*\bvalue=["\x27]' . $country . '["\x27][^>]*>~i';
			$counts['form_country_defaults'] += preg_match_all( $form_pattern, $code );

			// Each matching country-pill includes two occurrences: data attribute and button text.
			$pill_pattern = '~<button\b[^>]*\bdata-yby-country=["\x27]' . $country . '["\x27][^>]*>\s*' . $country . '\s*</button>~i';
			$counts['country_pills'] += preg_match_all( $pill_pattern, $code );

			// Never recast Tanzania project examples as real German cases without evidence.
			$scenario_pattern = '~<h[1-6]\b[^>]*\bdata-project-country[^>]*>\s*' . $country . '\s*</h[1-6]>~i';
			$counts['scenario_headings'] += preg_match_all( $scenario_pattern, $code );
		}

		$classified = $counts['form_country_defaults'] + ( 2 * $counts['country_pills'] ) + $counts['scenario_headings'];
		$counts['unclassified_code_refs'] = max( 0, $counts['total_code_refs'] - $classified );
		return $counts;
	}
}
