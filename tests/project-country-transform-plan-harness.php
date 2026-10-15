<?php
/**
 * Bricks Country Transform Registry — read-only categorization tests.
 */
define( 'ABSPATH', __DIR__ );
require_once dirname( __DIR__ ) . '/inc/class-yby-country-transform-registry.php';
function must( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL " . $message . "\n" ); exit( 1 ); }
}
$code = '<input name="selected_country" value="Tanzania" />'
	. '<input class="x" name="country" value="Tanzania" required />'
	. '<button class="yby-country-pill" data-yby-country="Tanzania">Tanzania</button>'
	. '<h3 data-project-country>Tanzania</h3>';
$elements = array(
	array( 'name' => 'code', 'settings' => array( 'code' => $code ) ),
	array( 'name' => 'section' ),
);
$p = YBY_Country_Transform_Registry::preview( $elements, 'Tanzania' );
must( 5 === $p['total_code_refs'], 'five country occurrences' );
must( 2 === $p['form_country_defaults'], 'typed country inputs' );
must( 1 === $p['country_pills'], 'typed button' );
must( 1 === $p['scenario_headings'], 'scenario heading manual review' );
must( 0 === $p['unclassified_code_refs'], 'known references accounted for' );
must( 1 === $p['code_nodes'], 'code node counted' );

$elements[0]['settings']['code'] .= 'var project=PROJECT_SCENARIOS.Tanzania;';
$p = YBY_Country_Transform_Registry::preview( $elements, 'Tanzania' );
must( 1 === $p['unclassified_code_refs'], 'unclassified JS must remain flagged' );

$p = YBY_Country_Transform_Registry::preview( $elements, '' );
must( 0 === $p['total_code_refs'], 'empty source must not guess' );
must( false === method_exists( 'YBY_Country_Transform_Registry', 'apply' ), 'no implicit executable transform' );
echo "PASS project-country-transform-plan-harness (read-only classifications)\n";
