<?php
/**
 * Germany Country Clone in-memory content safety/attribution contract tests.
 */
define( 'ABSPATH', __DIR__ );
class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
require dirname( __DIR__ ) . '/inc/class-yby-germany-clone-transform.php';
function assert_ok( $ok, $reason ) {
	if ( ! $ok ) { fwrite( STDERR, 'FAIL: ' . $reason . "\n" ); exit( 1 ); }
}
$source_nodes = array(
	array( 'name' => 'code', 'settings' => array(
		'code' => '<input name="selected_country" value="Tanzania" /><input name="country" value="Tanzania" />',
		'signature' => 'old', 'user_id' => 12, 'time' => 55,
	) ),
	array( 'name' => 'code', 'settings' => array(
		'code' => '<button class="yby-country-pill yby-country-pill--active" type="button" data-yby-country="Tanzania">Tanzania</button>'
			. '<h3 data-project-country>Tanzania</h3>',
		'signature' => 'old',
	) ),
	array( 'name' => 'code', 'settings' => array(
		'code' => '<button class="yby-country-pill yby-country-pill--active" type="button" data-yby-country="Tanzania">Tanzania</button>'
			. '<h3 data-project-country>Tanzania</h3>',
		'signature' => 'old',
	) ),
);
$source_script = 'var PROJECT_SCENARIOS = { Tanzania: { status: "Example Project Scenario", crop: "Vegetables", area: "1-5 ha", system:"Drip", support:"Consult" } };'
	. 'state = { selectedCountry: "Tanzania" };'
	. 'var project = PROJECT_SCENARIOS[country] || PROJECT_SCENARIOS.Tanzania;'
	. 'var target = button.getAttribute("data-yby-country") || "Tanzania";';
$source_settings = array( 'customScriptsBodyFooter' => $source_script, 'customCss' => '.hero{color:black}' );

$result = YBY_Germany_Clone_Transform::build( $source_nodes, $source_settings );
assert_ok( is_array( $result ), 'reviewed source transforms safely' );
assert_ok( array_values( $result['counts'] ) === array( 2, 2, 2, 4 ), 'exact semantic counts' );
assert_ok( false !== strpos( $result['elements'][0]['settings']['code'], 'name="country" value="Germany"' ), 'country input Germany' );
assert_ok( false !== strpos( $result['elements'][0]['settings']['code'], 'selected_country" value="Germany"' ), 'selected country Germany' );
for ( $i = 1; $i <= 2; ++$i ) {
	$html = $result['elements'][ $i ]['settings']['code'];
	assert_ok( 1 === substr_count( $html, 'data-yby-country="Tanzania"' ), 'Tanzania remains available' );
	assert_ok( 1 === substr_count( $html, 'data-yby-country="Germany"' ), 'Germany added once to each selector' );
	assert_ok( 1 === substr_count( $html, 'yby-country-pill--active' ), 'exactly one active country button' );
	assert_ok( false !== strpos( $html, '<h3 data-project-country>Germany</h3>' ), 'Germany default heading' );
	assert_ok( ! isset( $result['elements'][ $i ]['settings']['signature'] ), 'stale signature stripped' );
}
assert_ok( false !== strpos( $result['settings']['customScriptsBodyFooter'], 'Tanzania: { status: "Example Project Scenario"' ), 'historic Tanzania example retained' );
assert_ok( false !== strpos( $result['settings']['customScriptsBodyFooter'], 'no completed Germany project claimed' ), 'Germany example truthful' );
assert_ok( false !== strpos( $result['settings']['customScriptsBodyFooter'], 'PROJECT_SCENARIOS.Germany' ), 'new Germany fallback' );
assert_ok( false === strpos( $result['settings']['customScriptsBodyFooter'], 'PROJECT_SCENARIOS.Tanzania' ), 'no source fallback bleed' );
assert_ok( false !== strpos( $source_script, 'PROJECT_SCENARIOS.Tanzania' ), 'source settings remain unchanged' );
assert_ok( isset( $source_nodes[1]['settings']['signature'] ), 'source signature intact' );

$bad_nodes = $source_nodes;
$bad_nodes[1]['settings']['code'] = '<h3 data-project-country>Revised</h3>';
$r = YBY_Germany_Clone_Transform::build( $bad_nodes, $source_settings );
assert_ok( $r instanceof WP_Error && 'clone_layout_mismatch' === $r->get_error_code(), 'changed layout blocked' );
$bad_settings = $source_settings;
$bad_settings['customScriptsBodyFooter'] = 'var PROJECT_SCENARIOS = {};';
$r = YBY_Germany_Clone_Transform::build( $source_nodes, $bad_settings );
assert_ok( $r instanceof WP_Error && 'clone_script_contract' === $r->get_error_code(), 'changed JS blocked' );
echo "PASS project-country-germany-transform-harness (target fields, source cases, signatures, strict source shape)\n";
