<?php
/**
 * Offline-only migration inventory contract tests. No WordPress, SQL or mail.
 *
 * Run: php tests/wpforms-migration-inventory-harness.php
 */
require_once dirname( __DIR__ ) . '/tools/wpforms-migration/class-inventory.php';

function migration_must( $ok, $message ) {
	if ( ! $ok ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

$normal = array(
	'fields' => array(
		'1' => array( 'id' => 1, 'type' => 'name', 'required' => 1 ),
		'2' => array( 'id' => 2, 'type' => 'email', 'required' => 1 ),
		'3' => array(
			'id' => 3, 'type' => 'select',
			'choices' => array(
				array( 'label' => 'Small', 'value' => 'private-1' ),
				array( 'label' => 'Large', 'value' => 'private-2' ),
			),
		),
		'4' => array( 'id' => 4, 'type' => 'textarea' ),
	),
	'settings' => array(
		'notifications' => array( '1' => array( 'email' => 'private@example.test' ) ),
		'confirmations' => array( '1' => array( 'message' => 'Thank you.' ) ),
	),
);
$scan = Andy_WPForms_Migration_Inventory::inspect_form( 248, $normal );
migration_must( $scan['form_id'] === 248, 'keeps form ID' );
migration_must( $scan['suggested_preset_id'] === 'site_wpforms_248', 'site preset name' );
migration_must( count( $scan['fields'] ) === 4, 'inventory four fields' );
migration_must( $scan['fields'][2]['target_type'] === 'select', 'select mapped' );
migration_must( $scan['fields'][2]['choice_count'] === 2, 'choice count retained' );
migration_must( $scan['has_notifications'], 'notification dependency detected' );
migration_must( in_array( 'notification_routing_requires_review', $scan['blockers'], true ), 'email manual gate' );
migration_must( false === $scan['ready_for_uninstall'], 'never suggests uninstall' );
migration_must( false === strpos( json_encode( $scan ), 'private@example.test' ), 'no email address leakage' );
migration_must( false === strpos( json_encode( $scan ), 'private-1' ), 'no field value leakage' );

$unusual = array(
	'fields' => array(
		'1' => array( 'type' => 'file-upload' ),
		'2' => array( 'type' => 'payment-single' ),
		'3' => array( 'type' => 'checkboxes' ),
	),
	'providers' => array( 'external' => array( 'secret' => 'do-not-export' ) ),
);
$review = Andy_WPForms_Migration_Inventory::inspect_form( 210, $unusual );
migration_must( count( $review['blockers'] ) >= 4, 'unusual fields and provider gated' );
migration_must( $review['fields'][0]['target_type'] === null, 'unsupported field not silently mapped' );
migration_must( false === strpos( json_encode( $review ), 'do-not-export' ), 'no provider secret leakage' );

$empty = Andy_WPForms_Migration_Inventory::inspect_form( 211, array( 'fields' => array() ) );
migration_must( in_array( 'empty_form_or_provider_only', $empty['blockers'], true ), 'empty forms blocked' );

$summary = Andy_WPForms_Migration_Inventory::summarize( array( $scan, $review, $empty ) );
migration_must( $summary['form_count'] === 3, 'summary forms' );
migration_must( $summary['field_count'] === 7, 'summary fields' );
migration_must( $summary['unsupported_field_count'] === 3, 'unsupported tally' );
migration_must( ! $summary['disable_wpforms_now'], 'no disable recommendation' );
migration_must( ! $summary['production_write_authorized'], 'no prod authorization' );

echo "PASS wpforms-migration-inventory-harness: 15 assertions; no production writes\n";
