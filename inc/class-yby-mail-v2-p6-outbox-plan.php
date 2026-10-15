<?php
/** Mail OS V2 P6 — inert metadata-only Outbox DDL and read-only SQL plans.
 * No hooks, WP runtime, dbDelta, query execution, migrations or mail.
 * A DDL string returned here does NOT authorize creating a table.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class YBY_Mail_V2_P6_Outbox_Plan {
    const TABLE_SUFFIX = 'yby_mail_v2_outbox';
    const SELECT_FIELDS = array(
        'opaque_id', 'state', 'channel', 'sender_key', 'priority',
        'attempt', 'max_attempts', 'next_attempt_at', 'diagnostic_code', 'state_version',
    );
    const INDEXES = array(
        'PRIMARY' => array( 'opaque_id' ),
        'uq_idempotency_key' => array( 'idempotency_key' ),
        'idx_state_priority_due' => array( 'state', 'priority', 'next_attempt_at' ),
        'idx_sender_state_due' => array( 'sender_key', 'state', 'next_attempt_at' ),
    );

    public static function columns() {
        return array(
            'opaque_id' => 'CHAR(64) NOT NULL',
            'idempotency_key' => 'CHAR(64) NOT NULL',
            'event_hash' => 'CHAR(64) NOT NULL',
            'payload_ref' => 'CHAR(64) NULL',
            'channel' => 'VARCHAR(32) NOT NULL',
            'sender_key' => 'VARCHAR(48) NOT NULL',
            'template_key' => 'VARCHAR(48) NOT NULL',
            'template_version' => 'INT UNSIGNED NOT NULL',
            'consent_snapshot_hash' => 'CHAR(64) NULL',
            'priority' => 'TINYINT UNSIGNED NOT NULL',
            'state' => 'VARCHAR(32) NOT NULL',
            'state_version' => 'BIGINT UNSIGNED NOT NULL',
            'attempt' => 'TINYINT UNSIGNED NOT NULL',
            'max_attempts' => 'TINYINT UNSIGNED NOT NULL',
            'next_attempt_at' => 'BIGINT UNSIGNED NOT NULL',
            'lease_token_hash' => 'CHAR(64) NULL',
            'lease_expires_at' => 'BIGINT UNSIGNED NULL',
            'diagnostic_code' => 'VARCHAR(32) NOT NULL',
            'created_at' => 'DATETIME(6) NOT NULL',
            'updated_at' => 'DATETIME(6) NOT NULL',
        );
    }

    private static function qi( $name ) { return chr( 96 ) . $name . chr( 96 ); }

    /** Reject arbitrary SQL identifiers, even though query plans never execute. */
    public static function table( $prefix ) {
        return is_string( $prefix ) &&
            1 === preg_match( '/\A[A-Za-z0-9_]{1,32}\z/D', $prefix ) &&
            strlen( $prefix . self::TABLE_SUFFIX ) <= 64
            ? $prefix . self::TABLE_SUFFIX : null;
    }

    /** Review-only DDL. No IF NOT EXISTS: schema drift must not be masked. */
    public static function ddl_proposal( $prefix ) {
        $table = self::table( $prefix );
        if ( null === $table ||
             array_keys( self::columns() ) !== YBY_Mail_V2_Governance::schema_contract()['columns'] ) {
            return array( 'status' => 'blocked', 'reason' => 'schema_contract_mismatch' );
        }
        $parts = array();
        foreach ( self::columns() as $col => $type ) { $parts[] = self::qi( $col ) . ' ' . $type; }
        foreach ( self::INDEXES as $name => $cols ) {
            $ids = implode( ',', array_map( array( __CLASS__, 'qi' ), $cols ) );
            if ( 'PRIMARY' === $name ) { $parts[] = 'PRIMARY KEY (' . $ids . ')'; }
            elseif ( 'uq_idempotency_key' === $name ) {
                $parts[] = 'UNIQUE KEY ' . self::qi( $name ) . ' (' . $ids . ')';
            } else { $parts[] = 'KEY ' . self::qi( $name ) . ' (' . $ids . ')'; }
        }
        return array(
            'status' => 'review_only', 'can_apply' => false,
            'requires_owner_schema_write_approval' => true,
            'table' => $table, 'columns' => self::columns(), 'indexes' => self::INDEXES,
            'sql' => 'CREATE TABLE ' . self::qi( $table ) . " (\n  " .
                     implode( ",\n  ", $parts ) .
                     "\n) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin",
            'preconditions' => array( 'isolated_disposable_database', 'verified_empty_target',
                'exact_schema_and_indexes', 'verified_backup', 'explicit_owner_approval' ),
            'rollback' => 'no_automatic_drop; separate_owner_review_for_any_destruction',
        );
    }

    /** Prepared-query templates ONLY. Never query a live database in P6. */
    public static function readonly_query( $prefix, $view, $page, $per_page, $gate ) {
        $deny = static function ( $reason ) {
            return array( 'status' => 'blocked', 'reason' => $reason, 'sql' => null );
        };
        if ( ! is_array( $gate ) ||
             ! YBY_Mail_V2_Governance::can_inspect_readonly(
                 $view, $gate['capability_verified'] ?? false, $gate['nonce_verified'] ?? false ) ) {
            return $deny( 'auth_gate' );
        }
        if ( ! in_array( $view, array( 'queue', 'failures', 'overview' ), true ) ) {
            // Current-state Outbox is NOT an append-only audit/event log.
            return $deny( 'no_verified_event_log_source' );
        }
        if ( true !== ( $gate['schema_verified'] ?? false ) ||
             true !== ( $gate['read_only_connection_verified'] ?? false ) ||
             true !== ( $gate['isolated_environment_verified'] ?? false ) ) {
            return $deny( 'source_gate' );
        }
        $table = self::table( $prefix );
        if ( null === $table || ! is_int( $page ) || $page < 1 || $page > 1000 ||
             ! is_int( $per_page ) || ! in_array( $per_page, array( 10, 20, 50 ), true ) ) {
            return $deny( 'invalid_query' );
        }
        $where = 'failures' === $view ?
            " WHERE state IN ('deferred','permanent_failed','uncertain')" : '';
        $fields = implode( ',', array_map( array( __CLASS__, 'qi' ), self::SELECT_FIELDS ) );
        $from = ' FROM ' . self::qi( $table );
        return array(
            'status' => 'read_only_plan', 'can_execute' => false,
            'table' => $table, 'source' => 'requires_verified_readonly_outbox',
            'rows_sql_template' => 'SELECT ' . $fields . $from . $where .
                ' ORDER BY priority ASC,next_attempt_at ASC,opaque_id ASC LIMIT %d OFFSET %d',
            'rows_sql_params' => array( $per_page, ( $page - 1 ) * $per_page ),
            'total_sql_template' => 'SELECT COUNT(*)' . $from . $where,
            'counts_sql_template' => 'SELECT state,COUNT(*) AS n' . $from . ' GROUP BY state',
            'columns' => self::SELECT_FIELDS,
            'page' => $page, 'per_page' => $per_page,
            'contains_pii' => false, 'mutations_allowed' => false,
            'data_status' => 'not_queried',
        );
    }

    /** External DB adapter must verify INFORMATION_SCHEMA and index definitions. */
    public static function schema_matches( $observed ) {
        if ( ! is_array( $observed ) ||
             array_keys( $observed ) !== array( 'columns', 'indexes' ) ||
             ! is_array( $observed['columns'] ) ||
             ! is_array( $observed['indexes'] ) ) { return false; }
        return self::columns() === $observed['columns'] &&
            self::INDEXES === $observed['indexes'] &&
            array_keys( self::columns() ) === YBY_Mail_V2_Governance::schema_contract()['columns'];
    }

    /** Deliberately no enqueue, retry, DDL, UPDATE, DELETE or send path. */
    public static function can_write( $action, $context = array() ) { return false; }
}
