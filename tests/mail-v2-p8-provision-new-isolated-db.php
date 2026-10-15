<?php
/**
 * P8 explicit one-time synthetic setup for an ALREADY hPanel-provisioned
 * separate database. Not imported into WordPress; NEVER touches WP DB.
 * Requires a scoped DB user with setup rights; after executing, the Owner MUST
 * downgrade its single Hostinger database user to SELECT-only in hPanel.
 */
if ( PHP_SAPI !== 'cli' || getenv( 'MAIL_P8_SETUP' ) !== 'EXPLICIT_NEW_DEV_DATABASE_ONLY' ) {
    exit( 80 );
}
$home = getenv( 'HOME' );
$wp_db = getenv( 'MAIL_P8_WORDPRESS_DB_NAME' );
if ( ! is_string( $home ) || '' === $home ||
     ! is_string( $wp_db ) || '' === $wp_db ) { exit( 81 ); }
$dir = $home . '/.byl-dev-canary/mail-v2-p8-dev-readonly';
$path = $dir . '/connection.php';
if ( realpath( $dir ) !== $dir || is_link( $dir ) || ! is_file( $path ) ||
     realpath( $path ) !== $path || is_link( $path ) ||
     ( fileperms( $path ) & 0077 ) !== 0 ) { exit( 82 ); }
$config = require $path;
if ( ! is_array( $config ) || array_keys( $config ) !== array(
    'dbname', 'user', 'password', 'socket', 'table'
) ) { exit( 83 ); }
foreach ( $config as $v ) { if ( ! is_string( $v ) || '' === $v ) { exit( 84 ); } }
if ( $config['dbname'] === $wp_db ||
     ! preg_match( '/\A[A-Za-z0-9_]{1,64}\z/D', $config['dbname'] ) ||
     ! preg_match( '/\A[A-Za-z0-9_]{1,64}\z/D', $config['user'] ) ||
     $config['table'] !== 'p8_yby_mail_v2_outbox' ||
     ! in_array( $config['socket'], array(
         '/var/lib/mysql/mysql.sock', '/run/mysqld/mysqld.sock'
     ), true ) || ! file_exists( $config['socket'] ) ) { exit( 85 ); }
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/admin/class-yby-mail-v2-p8-readonly.php';
$pdo = new PDO( 'mysql:unix_socket=' . $config['socket'] . ';dbname=' .
    $config['dbname'] . ';charset=ascii', $config['user'], $config['password'],
    array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 3 ) );
if ( $pdo->query( 'SELECT DATABASE()' )->fetchColumn() !== $config['dbname'] ) {
    throw new RuntimeException( 'ISOLATION_FAIL' );
}
$quoted = preg_quote( $config['dbname'], '/' );
$have_privilege = false;
foreach ( $pdo->query( 'SHOW GRANTS' )->fetchAll( PDO::FETCH_COLUMN ) as $grant ) {
    // Do not log grants: some provider versions may include auth data.
    $on_new_db = preg_match( '/\bON [\x60]?' . $quoted . '[\x60]?[.][*] TO\b/i', (string)$grant );
    $can_setup = preg_match( '/\bALL PRIVILEGES\b/i', (string)$grant ) ||
        ( preg_match( '/\bCREATE\b/i', (string)$grant ) &&
          preg_match( '/\bINSERT\b/i', (string)$grant ) );
    if ( $on_new_db && $can_setup ) {
        $have_privilege = true;
    } elseif ( ! preg_match( '/\bGRANT USAGE ON [*][.][*] TO\b/i', (string)$grant ) ) {
        throw new RuntimeException( 'UNEXPECTED_DATABASE_PRIVILEGE' );
    }
}
if ( ! $have_privilege ) { throw new RuntimeException( 'SCOPED_SETUP_PRIVILEGE_REQUIRED' ); }
$check = $pdo->prepare( 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=?' );
$check->execute( array( $config['dbname'] ) );
if ( (int)$check->fetchColumn() !== 0 ) {
    throw new RuntimeException( 'DATABASE_NOT_EMPTY_FAIL_CLOSED' );
}
$defs = array();
foreach ( YBY_Mail_V2_P8_Readonly::COLUMNS as $column => $definition ) {
    $defs[] = chr(96) . $column . chr(96) . ' ' . strtoupper( $definition );
}
foreach ( YBY_Mail_V2_P8_Readonly::INDEXES as $name => $cols ) {
    $columns = chr(96) . implode( chr(96) . ',' . chr(96), $cols ) . chr(96);
    if ( $name === 'PRIMARY' ) { $defs[] = 'PRIMARY KEY (' . $columns . ')'; }
    elseif ( $name === 'uq_idempotency_key' ) {
        $defs[] = 'UNIQUE KEY ' . chr(96) . $name . chr(96) . ' (' . $columns . ')';
    } else { $defs[] = 'KEY ' . chr(96) . $name . chr(96) . ' (' . $columns . ')'; }
}
$table = YBY_Mail_V2_P8_Readonly::TABLE;
$pdo->exec( 'CREATE TABLE ' . chr(96) . $table . chr(96) . " (\n  " .
    implode( ",\n  ", $defs ) . "\n) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin" );
$fields = array_keys( YBY_Mail_V2_P8_Readonly::COLUMNS );
$sql = 'INSERT INTO ' . $table . ' (' . implode( ',', $fields ) . ') VALUES (' .
    implode( ',', array_fill( 0, count( $fields ), '?' ) ) . ')';
$insert = $pdo->prepare( $sql );
$states = array( 'queued', 'deferred', 'uncertain', 'permanent_failed', 'accepted_by_provider' );
$diagnostics = array( 'queued'=>'none', 'deferred'=>'preacceptance_reject',
    'uncertain'=>'acceptance_unknown', 'permanent_failed'=>'permanent_reject',
    'accepted_by_provider'=>'provider_accepted' );
$pdo->beginTransaction();
for ( $i = 0; $i < 100; $i++ ) {
    $channel = 0 === $i % 25 ? 'critical_system' :
        ( 0 === $i % 5 ? 'account' : 'campaign' );
    $state = $states[ $i % count( $states ) ];
    $priority = 'critical_system' === $channel ? 0 : ( 'account' === $channel ? 1 : 2 );
    $row = array(
        hash( 'sha256', 'p8-synthetic-id-' . $i ),
        hash( 'sha256', 'p8-synthetic-idempotency-' . $i ),
        hash( 'sha256', 'p8-synthetic-event-' . $i ),
        null, $channel, 'campaign' === $channel ? 'newsletter_bulk' : 'system_tx',
        'synthetic_only', 1, 'campaign' === $channel ?
            hash( 'sha256', 'p8-synthetic-consent-' . $i ) : null,
        $priority, $state, 0, $state === 'queued' ? 0 : 1, 3,
        100 + $i, null, null, $diagnostics[ $state ],
        '2026-10-14 00:00:00.000000', '2026-10-14 00:00:00.000000',
    );
    if ( count( $row ) !== count( $fields ) ) {
        throw new RuntimeException( 'FIXTURE_SHAPE_FAILED' );
    }
    $insert->execute( $row );
}
$pdo->commit();
$total = (int)$pdo->query( 'SELECT COUNT(*) FROM ' . $table )->fetchColumn();
$failures = (int)$pdo->query(
    "SELECT COUNT(*) FROM " . $table .
    " WHERE state IN ('deferred','permanent_failed','uncertain')"
)->fetchColumn();
if ( $total !== 100 || $failures !== 60 ) {
    throw new RuntimeException( 'SYNTHETIC_ROW_COUNT_FAILED' );
}
// Do not add any real emails, addresses, private customer IDs, SMTP data.
echo "MAIL_P8_NEW_ISOLATED_DATABASE_SEEDED_PASS synthetic=100 failures=60\n";
echo "NEXT_REQUIRED_HPanel_ACTION=REVOKE_ALL_EXCEPT_SELECT\n";
echo "P8_LIVE_UI_DISABLED_UNTIL_SELECT_ONLY_GRANTS_VERIFIED\n";
