<?php
/** Runtime and source-negative test; no WP, network or DB; assert allowlisted hook registration. */
error_reporting( E_ALL );
$source = file_get_contents( __DIR__ . '/../andy-commerce-observer.php' );
$fail = static function ( $ok, $message ) {
    if ( ! $ok ) { fwrite( STDERR, "FAIL {$message}\n" ); exit( 1 ); }
};
$forbidden = array(
    '/\badd_filter\s*\(/',
    '/\bregister_activation_hook\s*\(/', '/\bregister_deactivation_hook\s*\(/',
    '/\bregister_uninstall_hook\s*\(/', '/\bwp_schedule_event\s*\(/',
    '/\bregister_rest_route\s*\(/', '/\bwp_insert_post\s*\(/',
    '/\bwp_update_post\s*\(/', '/\bwp_mail\s*\(/',
    '/\bupdate_option\s*\(/', '/\badd_option\s*\(/',
    '/\bdelete_option\s*\(/', '/\bwp_remote_post\s*\(/',
    '/\bwp_remote_get\s*\(/', '/\bwp_set_auth_cookie\s*\(/',
    '/\bwpdb\s*->\s*(query|insert|update|delete)\s*\(/',
    '/\bWC\s*\(\s*\)\s*->\s*(cart|checkout|payment_gateways)/',
);
foreach ( $forbidden as $pattern ) {
    $fail( ! preg_match( $pattern, $source ), 'forbidden function: ' . $pattern );
}
// Analyze PHP source tokens, not comments or string literals, to prevent false positives.
$tokens = token_get_all( $source );
$registration = array();
for ( $i = 0, $len = count( $tokens ); $i < $len; $i++ ) {
    $token = $tokens[$i];
    if ( ! is_array( $token ) || $token[0] !== T_STRING ) { continue; }
    if ( in_array( strtolower( $token[1] ), array( 'add_action', 'add_filter' ), true ) ) {
        $j = $i + 1;
        while ( $j < $len && is_array( $tokens[$j] ) && in_array( $tokens[$j][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { $j++; }
        $fail( isset( $tokens[$j] ) && $tokens[$j] === '(', 'hook call malformed' );
        $j++;
        while ( $j < $len && is_array( $tokens[$j] ) && $tokens[$j][0] === T_WHITESPACE ) { $j++; }
        $arg = $tokens[$j] ?? null;
        $fail( is_array( $arg ) && $arg[0] === T_CONSTANT_ENCAPSED_STRING, 'dynamic hook forbidden' );
        $hook = trim( $arg[1], "'\"" );
        $registration[] = array( $token[1], $hook );
    }
}
$fail( $registration === array( array( 'add_action', 'admin_menu' ) ), 'hook whitelist ' . json_encode( $registration ) );
$fail( ! preg_match( '/require(?:_once)?\s*\(?\s*ANDY_COMMERCE_PLUGIN_DIR/', $source ), 'full Commerce bootstrap load forbidden' );
$fail( false === strpos( $source, 'class Andy_Commerce {' ), 'must not redefine Andy Commerce' );
$fail( false !== strpos( $source, "current_user_can( 'manage_options' )" ), 'admin capability guard' );
$fail( false !== strpos( $source, "! is_admin()" ), 'frontend route guard' );

define( 'ABSPATH', '/dummy/' );
$GLOBALS['observer_hooks'] = array();
$GLOBALS['observer_is_admin'] = false;
function is_admin() { return $GLOBALS['observer_is_admin']; }
function add_action( $hook, $handler, $priority = 10 ) { $GLOBALS['observer_hooks'][] = array( $hook, $priority ); }
require __DIR__ . '/../andy-commerce-observer.php';
$fail( count( $GLOBALS['observer_hooks'] ) === 0, 'guest frontend registers no hooks' );
$GLOBALS['observer_is_admin'] = true;
Andy_Commerce_Read_Only_Observer::register();
$fail( $GLOBALS['observer_hooks'] === array( array( 'admin_menu', 99 ) ), 'admin hook only' );
echo "PASS observer-nohooks-harness frontend=0 admin=1 no business hooks/writes\n";
