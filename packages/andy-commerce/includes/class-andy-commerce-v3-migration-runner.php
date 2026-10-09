<?php
/** BYL V3 migration ledger compatibility, hosted by Andy Commerce. */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_V3_Migration_Runner {
    private const APPLIED_OPTION = 'byl_v3_applied_migrations';

    public static function discover(): array {
        $files = glob( ANDY_COMMERCE_PLUGIN_DIR . 'migrations/V3_DB_*.php' ) ?: array();
        sort( $files, SORT_STRING );
        $all = array();
        foreach ( $files as $file ) {
            $migration = require $file;
            if ( ! is_array( $migration ) || empty( $migration['id'] ) ) {
                throw new RuntimeException( 'Invalid migration: ' . basename( $file ) );
            }
            $all[(string) $migration['id']] = $migration;
        }
        return $all;
    }

    public static function applied(): array {
        $value = get_option( self::APPLIED_OPTION, array() );
        return is_array( $value ) ? $value : array();
    }

    public static function pending(): array {
        return array_diff_key( self::discover(), self::applied() );
    }

    public static function apply(string $id): void {
        $all = self::discover();
        if ( ! isset( $all[$id] ) ) { throw new RuntimeException( 'Unknown migration: ' . $id ); }
        $applied = self::applied();
        if ( isset( $applied[$id] ) ) { return; }
        $migration = $all[$id];
        $migration['up']();
        if ( isset( $migration['verify'] ) && ! (bool) $migration['verify']() ) {
            throw new RuntimeException( 'Verification failed: ' . $id );
        }
        $applied[$id] = array(
            'applied_at' => gmdate( 'c' ),
            'release_id' => defined( 'BYL_RELEASE_ID' ) ? BYL_RELEASE_ID : null,
        );
        update_option( self::APPLIED_OPTION, $applied, false );
    }
}
