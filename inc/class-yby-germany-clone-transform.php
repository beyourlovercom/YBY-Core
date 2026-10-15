<?php
/**
 * Germany country transform for approved Tanzania Bricks landing structure.
 *
 * Deliberately transforms only known slots. All other source country facts,
 * including actual Tanzania examples, remain intact. No WordPress writes.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Germany_Clone_Transform {

	/**
	 * Transform an in-memory source page after exact-source review.
	 *
	 * @param array $elements Bricks source elements.
	 * @param array $settings Bricks source page settings.
	 * @return array|WP_Error Updated elements/settings with review counts.
	 */
	public static function build( $elements, $settings ) {
		if ( ! is_array( $elements ) || ! is_array( $settings ) ) {
			return new WP_Error( 'clone_source_shape', 'Bricks source structure is unavailable.' );
		}
		$elements = unserialize( serialize( $elements ) );
		$settings = unserialize( serialize( $settings ) );
		$counts = array( 'form_defaults' => 0, 'country_pills' => 0, 'scenario_headings' => 0, 'page_scripts' => 0 );
		foreach ( $elements as &$element ) {
			if ( ! is_array( $element ) || 'code' !== ( $element['name'] ?? '' ) ) { continue; }
			if ( ! is_string( $element['settings']['code'] ?? null ) ) {
				return new WP_Error( 'clone_invalid_code', 'Code element is missing a readable code body.' );
			}
			$code = $element['settings']['code'];

			foreach ( array( 'selected_country', 'country' ) as $name ) {
				$source = 'name="' . $name . '" value="Tanzania"';
				$found = substr_count( $code, $source );
				if ( $found > 1 ) {
					return new WP_Error( 'clone_form_duplicate', 'Unexpected repeated country input in Code element.' );
				}
				if ( 1 === $found ) {
					$code = str_replace( $source, 'name="' . $name . '" value="Germany"', $code );
					++$counts['form_defaults'];
				}
			}

			$pattern = '~<button\b[^>]*\bdata-yby-country="Tanzania"[^>]*>\s*Tanzania\s*</button>~i';
			$found = preg_match_all( $pattern, $code, $matches );
			if ( $found > 1 ) {
				return new WP_Error( 'clone_pill_duplicate', 'Country selector contains duplicate Tanzania buttons.' );
			}
			if ( 1 === $found ) {
				$original = $matches[0][0];
				if ( false === strpos( $original, 'yby-country-pill--active' ) ||
					false !== strpos( $code, 'data-yby-country="Germany"' ) ) {
					return new WP_Error( 'clone_pill_state', 'Country selector requires exactly one active Tanzania and no Germany button.' );
				}
				$tanzania = str_replace( ' yby-country-pill--active', '', $original );
				$germany = str_replace( 'Tanzania', 'Germany', $original );
				$code = str_replace( $original, $tanzania . "\n" . $germany, $code );
				++$counts['country_pills'];
			}

			$heading = '<h3 data-project-country>Tanzania</h3>';
			$found = substr_count( $code, $heading );
			if ( $found > 1 ) {
				return new WP_Error( 'clone_scenario_duplicate', 'Unexpected repeated country scenario heading.' );
			}
			if ( 1 === $found ) {
				$code = str_replace( $heading, '<h3 data-project-country>Germany</h3>', $code );
				++$counts['scenario_headings'];
			}

			// Remove stale signatures on the COPY, never from source. The
			// authorized native Bricks signing flow must review/resign all Code.
			unset( $element['settings']['signature'], $element['settings']['user_id'], $element['settings']['time'] );
			$element['settings']['code'] = $code;
		}
		unset( $element );

		if ( 2 !== $counts['form_defaults'] || 2 !== $counts['country_pills'] ||
			2 !== $counts['scenario_headings'] ) {
			return new WP_Error( 'clone_layout_mismatch', 'The Tanzania Bricks node layout differs from the reviewed source.' );
		}

		$script = $settings['customScriptsBodyFooter'] ?? null;
		if ( ! is_string( $script ) ) {
			return new WP_Error( 'clone_script_missing', 'Source page scenario script is missing.' );
		}
		// New DE entry is an illustrative planning request, not a real case.
		// Tanzania data and its original scenario object are NOT rewritten.
		$scenario = '~(var\\s+PROJECT_SCENARIOS\\s*=\\s*\\{\\s*)Tanzania\\s*:~';
		if ( 1 !== preg_match_all( $scenario, $script ) ) {
			return new WP_Error( 'clone_script_contract', 'Source scenario registry does not match the reviewed Bricks JS.' );
		}
		$germany_entry = 'Germany: { status: "Illustrative planning scenario — no completed Germany project claimed", crop: "To be confirmed", area: "To be confirmed", system: "Engineering recommendation pending", support: "Request a technical proposal and quotation" }, Tanzania:';
		$script = preg_replace( $scenario, '$1' . $germany_entry, $script, 1 );
		++$counts['page_scripts'];
		$rules = array(
			array( 'selectedCountry: "Tanzania"', 'selectedCountry: "Germany"' ),
			array( 'PROJECT_SCENARIOS[country] || PROJECT_SCENARIOS.Tanzania', 'PROJECT_SCENARIOS[country] || PROJECT_SCENARIOS.Germany' ),
			array( 'button.getAttribute("data-yby-country") || "Tanzania"', 'button.getAttribute("data-yby-country") || "Germany"' ),
		);
		foreach ( $rules as $pair ) {
			if ( 1 !== substr_count( $script, $pair[0] ) ) {
				return new WP_Error( 'clone_script_contract', 'Source scenario JS does not match the reviewed Country Clone contract.' );
			}
			$script = str_replace( $pair[0], $pair[1], $script );
			++$counts['page_scripts'];
		}
		// A historical Tanzania scenario remains selectable. But Germany
		// must be the default and never inherit Tanzania as its fallback.
		if ( false !== strpos( $script, 'PROJECT_SCENARIOS.Tanzania' ) ||
			false !== strpos( $script, 'selectedCountry: "Tanzania"' ) ||
			false === strpos( $script, 'Germany: { status: "Illustrative planning scenario' ) ) {
			return new WP_Error( 'clone_scenario_fallback', 'Unsafe Tanzania fallback or missing illustrative Germany scenario.' );
		}

		$settings['customScriptsBodyFooter'] = $script;
		return array( 'elements' => $elements, 'settings' => $settings, 'counts' => $counts );
	}
}
