<?php
/**
 * READ-ONLY site-local draft generator for WPForms -> Andy Core Field Studio.
 *
 * Run with WP-CLI eval-file and --skip-plugins --skip-themes.
 * Prints JSON candidate options, all presets DISABLED; NEVER updates options.
 * Rebuild/review with existing WordPress admin workflow after Owner approval.
 */
global $wpdb;
if ( ! defined( 'ABSPATH' ) || ! isset( $wpdb ) ) {
	fwrite( STDERR, "Use wp eval-file inside an initialized WordPress site.\n" );
	exit( 1 );
}
require_once __DIR__ . '/class-draft-plan.php';

$records = $wpdb->get_results(
	"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'wpforms'
	AND post_status NOT IN ('trash', 'auto-draft') ORDER BY ID ASC LIMIT 17",
	ARRAY_A
);
if ( null === $records ) {
	fwrite( STDERR, "WPForms draft query failed; no configuration changed.\n" );
	exit( 1 );
}
$fields  = array();
$presets = array();
$forms   = array();
$errors  = array();
if ( count( $records ) > 16 ) {
	$errors[] = 'preset_capacity_exceeded';
}

foreach ( $records as $record ) {
	$decoded = json_decode( (string) $record['post_content'], true );
	if ( ! is_array( $decoded ) ) {
		$errors[] = 'malformed_source_form_id:' . absint( $record['ID'] );
		continue;
	}
	$plan = Andy_WPForms_Migration_Draft::build_form( $record['ID'], $decoded );
	$forms[] = array(
		'form_id' => $plan['form_id'],
		'source_field_bindings' => $plan['source_field_bindings'],
		'blockers' => $plan['blockers'],
	);
	foreach ( $plan['candidate_state']['fields'] as $id => $definition ) {
		if ( isset( $fields[ $id ] ) ) {
			$errors[] = 'duplicate_custom_field:' . $id;
		} else {
			$fields[ $id ] = $definition;
		}
	}
	foreach ( $plan['candidate_state']['presets'] as $id => $definition ) {
		$presets[ $id ] = $definition;
	}
}
if ( count( $fields ) > 30 ) {
	$errors[] = 'field_studio_custom_field_capacity_exceeded';
}

$report = array(
	'schema_version' => 1,
	'site' => home_url( '/' ),
	'read_only' => true,
	'source_form_count' => count( $records ),
	'proposed_fields' => count( $fields ),
	'candidate_option_name' => 'yby_popup_field_studio_v2',
	'candidate_state' => array(
		'version' => 2,
		'fields' => $fields,
		'presets' => $presets,
	),
	'forms' => $forms,
	'errors' => $errors,
	'ready_to_apply' => false,
	'ready_to_uninstall' => false,
	'caution' => 'DRAFT ONLY. Verify notifications, consent, anti-spam, form display, page embeds, UAT and rollback. No automatic writes.',
);
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
