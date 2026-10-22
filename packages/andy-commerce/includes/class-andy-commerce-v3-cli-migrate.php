<?php
/** Dry-run by default; --apply is explicitly required to mutate the ledger. */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_V3_CLI_Migrate_Command {
    public function __invoke(array $args, array $assoc_args): void {
        $pending = Andy_Commerce_V3_Migration_Runner::pending();
        if ( array() === $pending ) {
            WP_CLI::success( 'No pending BYL V3 migrations.' );
            return;
        }
        foreach ( $pending as $id => $migration ) {
            WP_CLI::log( $id . ' — ' . ( $migration['description'] ?? 'no description' ) );
        }
        if ( ! isset( $assoc_args['apply'] ) ) {
            WP_CLI::warning( 'Dry-run only. Re-run with --apply.' );
            return;
        }
        foreach ( array_keys( $pending ) as $id ) {
            WP_CLI::log( 'Applying ' . $id . '...' );
            Andy_Commerce_V3_Migration_Runner::apply( $id );
        }
        WP_CLI::success( 'Pending BYL V3 migrations applied and verified.' );
    }
}
