<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_email( $v ) { return filter_var( $v, FILTER_SANITIZE_EMAIL ); }
function absint( $v ) { return abs( (int) $v ); }
function apply_filters( $tag, $value ) { return $value; }
function get_option( $key, $default = null ) { return $default; }
function __( $value ) { return $value; }
$dir = dirname( __DIR__ );
$main = file_get_contents( $dir . '/yby-core.php' );
$database = file_get_contents( $dir . '/inc/class-yby-database.php' );
require_once $dir . '/inc/class-yby-inquiry-field-manager.php';
require_once $dir . '/inc/class-yby-inquiry-preset-manager.php';
require_once $dir . '/inc/class-yby-inquiry-lead-mapper.php';
function bottle_assert( $test, $message ) { if ( ! $test ) { throw new RuntimeException( $message ); } }
bottle_assert(
	false !== strpos(
		$main,
		"define( 'YBY_DATABASE_VERSION', '1.4.0' );"
	),
	'Bottle page_profile migration must remain compatible with the current database migration.'
);
bottle_assert(
	false !== strpos(
		$database,
		"page_profile varchar(100) DEFAULT ''"
	),
	'Bottle page_profile column must exist in the leads schema.'
);
$fields = new YBY_Inquiry_Field_Manager();
$presets = new YBY_Inquiry_Preset_Manager( $fields );
$oem = $presets->get_preset( 'bottle_oem_inquiry' );
bottle_assert( is_array( $oem ) && $oem['page_profiles'] === array( 'bottle_oem' ), 'OEM preset must be registered and profile-bound.' );
foreach ( array( 'project_path', 'spirit_type', 'selected_components', 'estimated_quantity', 'target_launch_date', 'selected_model' ) as $id ) { bottle_assert( isset( $fields->get_fields()[ $id ] ), "Missing Bottle field: {$id}" ); }
$mapper = new YBY_Inquiry_Lead_Mapper( $fields, $presets );
$accepted = $mapper->sanitize_custom_fields( array( 'project_path' => 'new_custom_mold', 'spirit_type' => 'Brandy', 'selected_components' => 'Bottle, closure', 'selected_model' => 'M-12' ), 'bottle_oem_inquiry' );
bottle_assert( 4 === count( $accepted ), 'Registered OEM custom fields must be retained.' );
bottle_assert( empty( $mapper->sanitize_custom_fields( array( 'project_path' => 'unsafe' ), 'bottle_oem_inquiry' ) ), 'Select values must be allowlisted.' );
bottle_assert( empty( $mapper->validate_submission_context( 'bottle_oem_inquiry', 'bottle_oem' ) ), 'OEM preset/profile mapping must pass.' );
bottle_assert( ! empty( $mapper->validate_submission_context( 'bottle_oem_inquiry', 'irrigation' ) ), 'Mismatched OEM profile must fail.' );
bottle_assert( empty( $mapper->validate_submission_context( 'bottle_wholesale_inquiry', '' ) ), 'Existing wholesale preset must remain compatible.' );
echo "bottle-runtime-contract: PASS\n";
