<?php
/**
 * Owner-authorized DEV-ONLY synthetic draft CRUD canary.
 * Run via WP-CLI eval-file with --skip-plugins --skip-themes.
 * Writes ONLY to the new isolated Andy Commerce draft/audit tables.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || getenv( 'ANDY_M3_DEV_CANARY' ) !== 'YES' ) { exit(1); }
$fail = static function ( string $reason ): void { WP_CLI::error( 'M3_DEV_CANARY_FAIL: ' . $reason ); };
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) || WP_ENVIRONMENT_TYPE !== 'staging'
    || parse_url( home_url(), PHP_URL_HOST ) !== 'dev.beyourlover.com'
    || DB_HOST !== '127.0.0.1' || DB_NAME !== 'u247343654_NUOBf' ) { $fail( 'environment mismatch' ); }
global $wpdb;
if ( $wpdb->prefix !== 'wp_' ) { $fail( 'table prefix mismatch' ); }
require_once WP_PLUGIN_DIR . '/andy-commerce/includes/class-andy-commerce-marketing-draft-sandbox.php';
require_once WP_PLUGIN_DIR . '/andy-commerce/includes/class-andy-commerce-marketing-draft-repository.php';
$repo = new Andy_Commerce_M3_Draft_Repository( $wpdb );
$id = 'm3-canary-20261012';
if ( $repo->find( $id ) ) { $fail( 'canary ID already exists; refuse overwrite' ); }
$sample = array(
    'schema_version'=>1, 'id'=>$id, 'name'=>'Synthetic M3 Dev canary',
    'type'=>'spend_save', 'currency'=>'USD', 'status'=>'draft', 'active'=>false,
    'priority'=>0, 'countries'=>array( 'US' ), 'stack_with_coupon'=>false,
    'rule'=>array( 'threshold_minor'=>10000, 'discount_minor'=>500 ),
);
try {
    $a = $repo->save( $sample, 1 );
    if ( $a['revision'] !== 1 || $a['active'] !== false ) { $fail( 'create mismatch' ); }
    $sample['name'] = 'Synthetic M3 Dev canary revised';
    $b = $repo->save( $sample, 1, 1 );
    if ( $b['revision'] !== 2 ) { $fail( 'revision mismatch' ); }
    $stale_rejected = false;
    try { $repo->save( $sample, 1, 1 ); } catch ( DomainException $e ) { $stale_rejected = true; }
    if ( ! $stale_rejected ) { $fail( 'stale revision accepted' ); }
    $repo->archive( $id, 2, 1 );
    $after = $repo->find( $id );
    if ( ! $after || $after['status'] !== 'archived' || (int) $after['active'] !== 0 || (int) $after['revision'] !== 3 ) {
        $fail( 'archive mismatch' );
    }
    $audit = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}andy_commerce_campaign_audit WHERE campaign_id=%s", $id
    ) );
    if ( $audit !== 3 ) { $fail( 'audit count mismatch' ); }
    WP_CLI::success( 'M3_DEV_REAL_MYSQL_CRUD_AUDIT_ARCHIVE_PASS; ACTIVE_OFF' );
} catch ( Throwable $e ) {
    $fail( 'read/write assertion failed without altering any existing Woo data' );
}
