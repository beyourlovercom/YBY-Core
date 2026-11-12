<?php
/**
 * Read-only WP-CLI inventory for a single WordPress site's WPForms usage.
 *
 * Usage (from an approved site-specific environment):
 *   wp --path=/path/to/public_html --skip-plugins --skip-themes \
 *     eval-file /path/to/YBY-Core/tools/wpforms-migration/audit-site.php
 *
 * STDOUT: JSON summary, field types, form IDs, page IDs and integration flags.
 * No form entries, recipient emails, secrets, field values, options or database
 * mutations. Output must be reviewed before any migration or uninstall.
 */
if ( ! defined( 'ABSPATH' ) || ! isset( $wpdb ) ) {
	fwrite( STDERR, "This script requires a bootstrapped WordPress site (wp eval-file).\n" );
	exit( 1 );
}

require_once __DIR__ . '/class-inventory.php';

global $wpdb;

$limit = 500;
$forms = array();
$errors = array();

$records = $wpdb->get_results(
	"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'wpforms'
	 AND post_status NOT IN ('trash', 'auto-draft') ORDER BY ID ASC LIMIT {$limit}",
	ARRAY_A
);
if ( null === $records ) {
	$errors[] = 'wpforms_query_failed';
	$records = array();
}

foreach ( $records as $record ) {
	$decoded = json_decode( (string) $record['post_content'], true );
	if ( ! is_array( $decoded ) ) {
		$errors[] = 'invalid_wpforms_json_id:' . absint( $record['ID'] );
		continue;
	}
	$forms[] = Andy_WPForms_Migration_Inventory::inspect_form( $record['ID'], $decoded );
}

$needle = '%' . $wpdb->esc_like( 'wpforms' ) . '%';
$published_rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT ID, post_type FROM {$wpdb->posts}
		 WHERE post_status = 'publish' AND post_type <> 'revision'
		 AND post_content LIKE %s ORDER BY ID ASC LIMIT {$limit}",
		$needle
	),
	ARRAY_A
);
if ( null === $published_rows ) {
	$errors[] = 'published_references_query_failed';
	$published_rows = array();
}

$elementor_rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT DISTINCT p.ID, p.post_type FROM {$wpdb->postmeta} AS m
		 INNER JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
		 WHERE m.meta_key = '_elementor_data' AND m.meta_value LIKE %s
		 AND p.post_status = 'publish' ORDER BY p.ID ASC LIMIT {$limit}",
		$needle
	),
	ARRAY_A
);
if ( null === $elementor_rows ) {
	$errors[] = 'elementor_references_query_failed';
	$elementor_rows = array();
}

$option_references = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->options}
		 WHERE option_value LIKE %s AND option_name NOT LIKE %s",
		$needle,
		'_transient%'
	)
);
if ( null === $option_references ) {
	$errors[] = 'option_references_query_failed';
}

$active = (array) get_option( 'active_plugins', array() );
$network_active = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array();
$is_active = static function ( $slug ) use ( $active, $network_active ) {
	return in_array( $slug, $active, true ) || array_key_exists( $slug, $network_active );
};
$reference_ids = static function ( $rows ) {
	return array_values(
		array_map(
			static function ( $row ) {
				return array( 'post_id' => absint( $row['ID'] ), 'post_type' => sanitize_key( $row['post_type'] ) );
			},
			$rows
		)
	);
};

$report = array(
	'schema_version' => 1,
	'read_only' => true,
	'site' => home_url( '/' ),
	'wpforms_installed' => file_exists( WP_PLUGIN_DIR . '/wpforms-lite/wpforms.php' ),
	'wpforms_active' => $is_active( 'wpforms-lite/wpforms.php' ),
	'andy_core_installed' => file_exists( WP_PLUGIN_DIR . '/yby-core/yby-core.php' ),
	'andy_core_active' => $is_active( 'yby-core/yby-core.php' ),
	'forms' => $forms,
	'summary' => Andy_WPForms_Migration_Inventory::summarize( $forms ),
	'possible_references' => array(
		'published_post_content' => $reference_ids( $published_rows ),
		'published_elementor_meta' => $reference_ids( $elementor_rows ),
		'options_count' => null === $option_references ? null : (int) $option_references,
		'not_exhaustive' => true,
	),
	'errors' => $errors,
	'truncated_at_500' => count( $records ) === $limit
		|| count( $published_rows ) === $limit || count( $elementor_rows ) === $limit,
	'gates' => array(
		'map_all_form_fields',
		'map_notifications_and_integrations',
		'update_all_embed_references',
		'verify_desktop_mobile_success_paths',
		'verify_real_email_delivery_and_lead_storage',
		'verify_anti_spam_and_rollback',
		'owner_pass_before_disabling_wpforms',
	),
);
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
