<?php
/** Wholesale Country Preview: no WordPress, network, order, or site writes. */
define( 'ABSPATH', '/isolated/' );
$GLOBALS['test_host'] = 'www.ybyirrigation.com';
$GLOBALS['test_env']  = 'production';
function sanitize_title( $value ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $value ) ), '-' ); }
function home_url( $path = '' ) { return 'https://' . $GLOBALS['test_host'] . $path; }
function wp_get_environment_type() { return $GLOBALS['test_env']; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function get_post( $id ) { return null; }
class WP_Error {
    private $code;
    public function __construct( $code, $message ) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
require dirname( __DIR__ ) . '/inc/class-yby-wholesale-country-clone-v2.php';
$count = 0;
$check = static function ( $condition, $message ) use ( &$count ) {
    ++$count;
    if ( ! $condition ) { throw new RuntimeException( 'FAIL: ' . $message ); }
};
$countries = YBY_Wholesale_Country_Clone_V2::countries();
$check( 16 === count( $countries ), '16 reviewed countries' );
$check( 'Germany' === $countries['DE'], 'canonical country registry' );
$de = YBY_Wholesale_Country_Clone_V2::slugs( 'DE' );
$check( 'wholesale-irrigation-germany' === $de['project'], 'canonical project slug' );
$check( 'irrigation-wholesale-germany' === $de['page'], 'canonical landing slug' );
$check( null === YBY_Wholesale_Country_Clone_V2::slugs( 'UNKNOWN' ), 'unknown country denied' );
$check( 'v2_unknown_country' === YBY_Wholesale_Country_Clone_V2::preflight( 'NO' )->get_error_code(), 'invalid country fails closed' );
$check( 'v2_env' === YBY_Wholesale_Country_Clone_V2::preflight( 'DE' )->get_error_code(), 'production denied before DB access' );
$GLOBALS['test_env'] = 'development';
$check( 'v2_env' === YBY_Wholesale_Country_Clone_V2::preflight( 'DE' )->get_error_code(), 'non-Dev hostname denied' );
$GLOBALS['test_host'] = 'dev.ybyirrigation.com';
$check( 'v2_source' === YBY_Wholesale_Country_Clone_V2::preflight( 'DE' )->get_error_code(), 'missing published source denied' );
$check( ! method_exists( 'YBY_Wholesale_Country_Clone_V2', 'create_drafts' ), 'no draft writer present' );
$root = dirname( __DIR__ );
$files = array(
  'admin/class-yby-project-studio.php',
  'admin/views/wholesale-country-clone-v2.php',
  'inc/class-yby-wholesale-country-clone-v2.php',
);
foreach ( $files as $file ) {
    $source = file_get_contents( $root . '/' . $file );
    $check( false === strpos( $source, 'wp_insert_post(' ), $file . ' has no post insertion' );
    $check( false === strpos( $source, 'yby_wholesale_create' ), $file . ' has no POST writer route' );
}
$controller = file_get_contents( $root . '/admin/class-yby-project-studio.php' );
$view = file_get_contents( $root . '/admin/views/wholesale-country-clone-v2.php' );
$check( false !== strpos( $controller, 'YBY_Andy_Admin_Shell_V22::render' ), 'current navigation shell preserved' );
$check( false !== strpos( $controller, 'YBY_Andy_OS_Navigation::root_capability()' ), 'existing root role rules preserved' );
$check( false !== strpos( $controller, 'dev.ybyirrigation.com' ), 'Dev host allowlist' );
$check( false === strpos( $view, 'method="post"' ), 'GET-only read-only controls' );
$check( false !== strpos( $view, 'Read-only preview.' ), 'preview state explicitly shown' );
echo 'PASS Wholesale Country V2 isolated read-only preflight and V2.2 native Studio preservation (' . $count . ' checks)' . PHP_EOL;
