<?php
/**
 * Dev-only, CLI-only, one-time M3 draft schema bootstrap.
 * php WP-CLI eval-file only; requires exact site + DB + owner backup acceptance.
 * No WordPress options, orders, products, Woo tables or activation.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit(1); }
$fail = static function ( string $reason ): void { WP_CLI::error( 'M3_SCHEMA_BLOCKED: ' . $reason ); };
if ( getenv( 'ANDY_M3_DEV_SCHEMA_GATE' ) !== 'YES'
    || getenv( 'ANDY_M3_OWNER_BACKUP_WAIVER' ) !== 'YES' ) { $fail( 'explicit schema and backup gates required' ); }
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) || ! in_array( WP_ENVIRONMENT_TYPE, array( 'development', 'staging' ), true ) ) {
    $fail( 'not a Dev environment' );
}
if ( parse_url( home_url(), PHP_URL_HOST ) !== 'dev.beyourlover.com' ) { $fail( 'site hostname mismatch' ); }
if ( ! defined( 'DB_NAME' ) || DB_NAME !== 'u247343654_NUOBf'
    || ! defined( 'DB_HOST' ) || DB_HOST !== '127.0.0.1' ) { $fail( 'database boundary mismatch' ); }
global $wpdb;
if ( $wpdb->prefix !== 'wp_' ) { $fail( 'unexpected WordPress table prefix' ); }
$engine = strtolower( (string) $wpdb->get_var( 'SELECT @@default_storage_engine' ) );
if ( $engine !== 'innodb' ) { $fail( 'transactional InnoDB default required' ); }

$plugin_dir = WP_PLUGIN_DIR . '/andy-commerce/';
require_once $plugin_dir . 'includes/class-andy-commerce-marketing-draft-repository.php';
$drafts = $wpdb->prefix . 'andy_commerce_campaign_drafts';
$audit = $wpdb->prefix . 'andy_commerce_campaign_audit';
$exists = static function ( string $name ) use ( $wpdb ): bool {
    return (int) $wpdb->get_var( $wpdb->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s', $name
    ) ) !== 0;
};
if ( $exists( $drafts ) || $exists( $audit ) ) {
    $fail( 'one or both campaign tables already exist; no automatic modification' );
}
$schema = ( new Andy_Commerce_M3_Draft_Repository( $wpdb ) )->schemaSql( $wpdb->get_charset_collate() );
$created = array();
try {
    foreach ( $schema as $index => $sql ) {
        if ( false === $wpdb->query( $sql ) ) { throw new RuntimeException( 'create table failed at index ' . $index ); }
        $name = 0 === $index ? $drafts : $audit;
        $created[] = $name;
        if ( ! $exists( $name ) ) { throw new RuntimeException( 'table creation unconfirmed' ); }
        $storage = strtolower( (string) $wpdb->get_var( $wpdb->prepare(
            'SELECT ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=%s', $name
        ) ) );
        if ( $storage !== 'innodb' ) { throw new RuntimeException( 'transactional table engine not InnoDB' ); }
    }
} catch ( Throwable $e ) {
    foreach ( array_reverse( $created ) as $name ) {
        $wpdb->query( 'DROP TABLE IF EXISTS `' . str_replace( '`', '', $name ) . '`' );
    }
    $fail( 'schema failed, newly created isolated tables reverted' );
}
WP_CLI::success( 'M3_DEV_SCHEMA_CREATED_2_TABLES_INNODB_OFF_ONLY' );
