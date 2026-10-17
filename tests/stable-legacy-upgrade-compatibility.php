<?php
// Synthetic data only, inside disposable GitHub Actions MySQL.
if ( ! defined( 'ABSPATH' ) || 'true' !== getenv( 'YBY_DISPOSABLE_MYSQL_ONLY' ) ) {
    throw new RuntimeException( 'Not a disposable test environment' );
}
$phase = getenv( 'YBY_LEGACY_PHASE' );
$from = getenv( 'YBY_LEGACY_VERSION' );
$legacy = array( '1.5.2' => '1.3.0', '1.9.0' => '1.5.0' );
if ( ! isset( $legacy[$from] ) || ! in_array( $phase, array( 'seed', 'verify', 'rollback' ), true ) ) {
    throw new RuntimeException( 'Bad legacy fixture context' );
}
$assert = static function ( $pass, $message ) {
    if ( ! $pass ) { throw new RuntimeException( 'LEGACY_GATE_FAIL: ' . $message ); }
};
global $wpdb;
$leads = $wpdb->prefix . 'yby_leads';
$case = 'CI-LEGACY-' . str_replace( '.', '-', $from );
$option = 'yby_ci_synthetic_sentinel';
$required = array( 'yby_leads', 'yby_lead_management', 'yby_lead_activities',
    'yby_connector_idempotency', 'yby_connector_audit', 'yby_connector_affiliate_bindings',
    'yby_connector_coupon_bindings', 'yby_connector_payout_bindings',
    'yby_connector_payout_claims', 'yby_email_templates', 'yby_email_template_versions' );
$exists = static function( $name ) use ( $wpdb ) {
    $table = $wpdb->prefix . $name;
    return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
};
$found = count( array_filter( $required, $exists ) );
$version = get_option( 'yby_database_version', '' );
if ( 'rollback' === $phase ) {
    $assert( $from === '1.5.2' && YBY_CORE_VERSION === '1.5.2', 'Rollback source version invalid' );
    $assert( $version === '1.3.0' && $found === 3, 'Irrigation isolated rollback did not restore the original 1.3.0 / 3-table schema' );
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT name,email,project_details,created_at FROM {$leads} WHERE case_id=%s", $case ), ARRAY_A );
    $assert( is_array($row) && $row['name'] === 'Synthetic Lead' && $row['email'] === 'nonexistent@disposable.test'
        && $row['project_details'] === 'Synthetic only'
        && $row['created_at'] === '2026-01-01 00:00:00', 'Original synthetic lead not recovered by the isolated database rollback' );
    $assert( get_option( $option ) === 'synthetic-preserved', 'Original synthetic WP setting not recovered' );
    echo 'LEGACY_IRRIGATION_SYNTHETIC_DB_AND_CODE_ROLLBACK_PASS'.PHP_EOL;
    return;
}
if ( 'seed' === $phase ) {
    $assert( YBY_CORE_VERSION === $from, 'Historic plugin code version mismatch' );
    $assert( $version === $legacy[$from], 'Historic DB version not equivalent to Production readback' );
    $assert( $found === ( '1.5.2' === $from ? 3 : 11 ), 'Historic table count expected 3 or 11' );
    $result = $wpdb->insert( $leads, array( 'case_id'=>$case, 'name'=>'Synthetic Lead',
        'email'=>'nonexistent@disposable.test', 'project_details'=>'Synthetic only',
        'created_at'=>'2026-01-01 00:00:00' ) );
    $assert( $result === 1, 'Unable to insert synthetic lead into legacy schema' );
    update_option( $option, 'synthetic-preserved', false );
    if ( $from === '1.9.0' ) {
        $assert( class_exists( 'YBY_Update_Verifier' ) && class_exists( 'YBY_Update_Backup' ), 'Old Bottle signed updater is missing' );
        $meta_path = getenv( 'YBY_SIGNED_META_FILE' );
        $sig_path = getenv( 'YBY_SIGNED_SIG_FILE' );
        $assert( is_file( $meta_path ) && is_file( $sig_path ), 'Signed public Canary evidence not staged in disposable CI' );
        $meta = file_get_contents( $meta_path );
        $sig = file_get_contents( $sig_path );
        $assert( YBY_Update_Verifier::signature( $meta, $sig ), 'Historic Bottle updater cannot verify public v2.2.6 Ed25519' );
        $assert( YBY_Update_Verifier::metadata( $meta, '2.2.6', 'andy-core-v2.2.6.zip',
            '404e3a88df368dc9d270fb86be2447921479e34e7fe60c20ef3f497260700709' ), 'Historic Bottle updater rejects signed metadata' );
        $record = YBY_Update_Backup::create( WP_PLUGIN_DIR . '/yby-core', '1.9.0' );
        $assert( ! is_wp_error( $record ) && $record['version'] === '1.9.0', 'Historic Bottle updater failed code-only backup creation' );
        $assert( false !== YBY_Update_Backup::latest(), 'Historic Bottle updater backup invalid before code replacement' );
        echo 'LEGACY_BOTTLE_SIGNATURE_AND_BACKUP_PASS'.PHP_EOL;
    }
    echo 'LEGACY_BASELINE_PASS '.wp_json_encode( array( 'plugin'=>$from, 'schema'=>$version,
        'legacy_tables'=>$found, 'synthetic_rows'=>1 ) ).PHP_EOL;
    return;
}
$assert( YBY_CORE_VERSION === '2.2.6', 'Unexpected target plugin version' );
$assert( $version === '1.5.0' && YBY_RUNTIME_DATABASE_VERSION === '1.5.0', 'Migration not at 1.5.0' );
$assert( YBY_Update_Verifier::DATABASE_VERSION === '1.4.0', 'Signature compatibility marker changed' );
$assert( $found === 11, 'Expected 11 current Andy Core tables' );
$assert( ! YBY_Database::needs_install_or_upgrade(), 'Schema not complete or idempotent' );
$assert( YBY_Database::management_tables_exist(), 'Missing management schema/indexes' );
$assert( YBY_Database::connector_tables_exist(), 'Missing connector schema/indexes' );
$assert( YBY_Database::email_tables_exist(), 'Missing email schema/indexes' );
$row = $wpdb->get_row( $wpdb->prepare( "SELECT name,email,project_details,created_at FROM {$leads} WHERE case_id=%s", $case ), ARRAY_A );
$assert( is_array($row) && $row['name'] === 'Synthetic Lead'
    && $row['email'] === 'nonexistent@disposable.test'
    && $row['project_details'] === 'Synthetic only'
    && $row['created_at'] === '2026-01-01 00:00:00', 'Existing synthetic lead not preserved' );
$assert( get_option( $option ) === 'synthetic-preserved', 'Synthetic WP option not preserved' );
$assert( (new YBY_Updater( YBY_CORE_PLUGIN_FILE, YBY_CORE_VERSION ))->update_channel() === 'disabled',
    'Update channel must fail closed on unknown host' );
if ( $from === '1.9.0' ) {
    $saved_backup = YBY_Update_Backup::latest();
    $assert( is_array($saved_backup) && $saved_backup['version'] === '1.9.0', 'Code-only backup not compatible after upgrade' );
}
echo 'LEGACY_MIGRATION_PASS '.wp_json_encode( array( 'source'=>$from, 'target'=>'2.2.6',
    'schema'=>'1.5.0', 'tables'=>11, 'synthetic_leads_preserved'=>true,
    'synthetic_options_preserved'=>true, 'production_backup'=>'NOT_TESTED' ) ).PHP_EOL;
