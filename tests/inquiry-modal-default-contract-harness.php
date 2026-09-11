<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . DIRECTORY_SEPARATOR );
}

$GLOBALS['harness_options'] = array();

function sanitize_key( $value ) {
	$value = strtolower( (string) $value );
	return preg_replace( '/[^a-z0-9_\-]/', '', $value );
}

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $value ) ) );
}

function get_option( $key, $default = null ) {
	return array_key_exists( $key, $GLOBALS['harness_options'] )
		? $GLOBALS['harness_options'][ $key ]
		: $default;
}

function apply_filters( $hook, $value ) {
	unset( $hook );
	return $value;
}

function absint( $value ) {
	return max( 0, (int) $value );
}

function harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

require_once dirname( __DIR__ ) . '/inc/class-yby-inquiry-field-manager.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-inquiry-preset-manager.php';

$field_manager  = new YBY_Inquiry_Field_Manager();
$preset_manager = new YBY_Inquiry_Preset_Manager( $field_manager );
$preset         = $preset_manager->get_preset( 'used_forklift_inquiry' );
$page_owned     = $preset_manager->get_preset( 'page_owned_inquiry' );

harness_assert( is_array( $page_owned ), 'Generic page-owned inquiry preset must exist in core.' );
harness_assert( true === $page_owned['enabled'], 'Generic page-owned inquiry preset must be enabled.' );
harness_assert( empty( $page_owned['page_profiles'] ), 'Generic page-owned inquiry preset must not be product-scoped.' );

harness_assert( is_array( $preset ), 'Used forklift preset must exist in core.' );
harness_assert( true === $preset['enabled'], 'Used forklift preset must be enabled.' );
harness_assert(
	array( 'name', 'contact', 'message' ) === $preset['mobile_fields'],
	'Used forklift mobile fields must match Inquiry Modal Default v1.'
);
harness_assert(
	array( 'used_forklifts' ) === $preset['page_profiles'],
	'Used forklift preset must remain scoped to the used_forklifts profile.'
);

$contact = $field_manager->get_field( 'contact' );
harness_assert( is_array( $contact ), 'Combined contact field must exist.' );
harness_assert( 'Email / WhatsApp' === $contact['label'], 'Combined contact label must stay canonical.' );

$legacy_fields = $field_manager->defaults();
unset( $legacy_fields['contact'] );
$GLOBALS['harness_options'][ YBY_Inquiry_Field_Manager::OPTION_KEY ] = $legacy_fields;
$legacy_field_manager = new YBY_Inquiry_Field_Manager();
harness_assert(
	is_array( $legacy_field_manager->get_field( 'contact' ) ),
	'Legacy stored fields must gain the additive contact field.'
);

$legacy_presets = $preset_manager->defaults();
unset( $legacy_presets['used_forklift_inquiry'] );
unset( $legacy_presets['irrigation_quick_inquiry']['mobile_fields'] );
$GLOBALS['harness_options'][ YBY_Inquiry_Preset_Manager::OPTION_KEY ] = $legacy_presets;
$legacy_preset_manager = new YBY_Inquiry_Preset_Manager( $legacy_field_manager );
harness_assert(
	is_array( $legacy_preset_manager->get_preset( 'used_forklift_inquiry' ) ),
	'Legacy stored presets must gain the new built-in forklift preset.'
);
harness_assert(
	array( 'name', 'contact', 'message' ) === $legacy_preset_manager->get_preset( 'irrigation_quick_inquiry' )['mobile_fields'],
	'Legacy built-in presets must gain default mobile fields.'
);

echo "PASS inquiry-modal-default-contract-harness\n";
