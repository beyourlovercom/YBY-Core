<?php
/** Offline tests for disabled WPForms -> Andy Core Field Studio V2 draft. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $s ) ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_url_raw( $s ) { return (string) $s; }
function wp_json_encode( $data ) { return json_encode( $data ); }
function migration_draft_must( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); }
}

$base = dirname( __DIR__ );
require_once $base . '/tools/wpforms-migration/class-draft-plan.php';
require_once $base . '/inc/class-yby-inquiry-field-manager.php';
require_once $base . '/inc/class-yby-popup-field-studio.php';

$source = array(
	'fields' => array(
		'1' => array( 'id' => 1, 'type' => 'name', 'required' => 1, 'label' => 'Full name' ),
		'2' => array( 'id' => 2, 'type' => 'email', 'required' => 1, 'label' => 'Email' ),
		'3' => array( 'id' => 3, 'type' => 'select', 'required' => 1, 'label' => 'Category',
			'choices' => array(
				array( 'label' => 'Small', 'value' => 'Small' ),
				array( 'label' => 'Large', 'value' => 'Large' ),
			),
		),
		'4' => array( 'id' => 4, 'type' => 'textarea', 'required' => 0, 'label' => 'Message' ),
	),
	'settings' => array( 'notifications' => array( '1' => array( 'email' => 'hidden@example.test' ) ) ),
);
$plan = Andy_WPForms_Migration_Draft::build_form( 248, $source );
migration_draft_must( $plan['candidate_state']['presets']['site_wpforms_248']['enabled'] === false, 'disabled default' );
migration_draft_must( $plan['source_field_bindings']['1'] === 'name', 'builtin name' );
migration_draft_must( $plan['source_field_bindings']['2'] === 'email', 'builtin email' );
migration_draft_must( $plan['source_field_bindings']['3'] === 'site_wpf248_f3', 'site field ID' );
migration_draft_must( count( $plan['candidate_state']['fields'] ) === 2, 'two custom fields' );
migration_draft_must( $plan['candidate_state']['fields']['site_wpf248_f3']['options'][0]['label'] === 'Small', 'label retained' );
migration_draft_must( $plan['candidate_state']['presets']['site_wpforms_248']['configs']['email']['mobile']['required'], 'mobile required preserved' );
migration_draft_must( ! $plan['ready_to_apply'] && ! $plan['ready_to_uninstall'], 'never autopublish' );
migration_draft_must( false === strpos( json_encode( $plan ), 'hidden@example.test' ), 'no recipients in draft' );
$clean = YBY_Popup_Field_Studio::sanitize( $plan['candidate_state'] );
migration_draft_must( isset( $clean['fields']['site_wpf248_f3'] ), 'real Studio sanitizer accepts field' );
migration_draft_must( isset( $clean['presets']['site_wpforms_248'] ), 'real Studio sanitizer accepts preset' );
migration_draft_must( !$clean['presets']['site_wpforms_248']['enabled'], 'real Studio still disabled' );

$changed_values = $source;
$changed_values['fields']['3']['choices'][0]['value'] = 'internal-sku-123';
$changed = Andy_WPForms_Migration_Draft::build_form( 248, $changed_values );
migration_draft_must( in_array( 'choice_value_semantics_requires_review', $changed['blockers'], true ), 'choice semantic difference blocks' );
migration_draft_must( false === strpos( json_encode( $changed ), 'internal-sku-123' ), 'no raw value leak' );

$unsupported = Andy_WPForms_Migration_Draft::build_form( 210, array(
	'fields' => array( '10' => array( 'id' => 10, 'type' => 'file-upload' ) ),
	'providers' => array( 'api_key' => 'hidden-secret' ),
) );
migration_draft_must( ! empty( $unsupported['blockers'] ), 'unsupported blocks' );
migration_draft_must( ! $unsupported['ready_to_apply'], 'provider never auto activates' );
migration_draft_must( false === strpos( json_encode( $unsupported ), 'hidden-secret' ), 'no provider secret leak' );

echo "PASS wpforms-migration-draft-harness: 17 assertions; Studio schema accepted, never activated\n";
