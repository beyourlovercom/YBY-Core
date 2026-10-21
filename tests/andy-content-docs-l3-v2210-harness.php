<?php
/** Andy Content / Docs OS L3 routing regression. No live WordPress or database. */
$base = dirname( __DIR__ );
$nav = file_get_contents( $base . '/admin/class-yby-andy-os-navigation.php' );
$content = file_get_contents( $base . '/admin/class-yby-content-admin.php' );
$docs = file_get_contents( $base . '/admin/class-yby-docs-os-admin.php' );
$core = file_get_contents( $base . '/inc/class-yby-core.php' );
$shell = file_get_contents( $base . '/admin/class-yby-andy-admin-shell-v22.php' );
$assert = static function ( $ok, $reason ) { if ( ! $ok ) { fwrite( STDERR, "FAIL $reason\n" ); exit( 1 ); } };
$assert( strpos( $nav, "'admin.php?page=yby-docs-os'" ) !== false, 'real Docs L2 default' );
$assert( strpos( $nav, "YBY_Module_Registry::is_enabled( 'business_os' )" ) !== false, 'Business priority preserved' );
foreach ( array( 'yby-docs-os', 'yby-docs-all', 'yby-docs-directory', 'yby-docs-faq', 'yby-docs-tutorial', 'yby-docs-settings' ) as $slug ) {
    $assert( strpos( $nav, "'" . $slug . "' => array(" ) !== false, 'Docs L3 entry ' . $slug );
}
$assert( strpos( $nav, "edit-tags.php?taxonomy=doc_tag&post_type=docs" ) !== false, 'Docs tag taxonomy L3 link' );
$assert( strpos( $content, 'YBY_Andy_Admin_Shell_V22::render' ) !== false, 'Content old URL gets native server L3 shell' );
foreach ( array( "self::PAGE_SLUG, 'Docs OS', 'docs-os-dashboard.php'",
                  "'docs-os-all-docs.php', \$data",
                  "self::DIRECTORY_PAGE_SLUG, 'Docs OS', 'docs-os-directory.php'",
                  "self::SETTINGS_PAGE_SLUG, 'Docs OS', 'docs-os-settings.php'" ) as $route ) {
    $assert( strpos( $docs, $route ) !== false, 'Docs original view still rendered inside L3 shell: ' . $route );
}
$assert( strpos( $docs, "isset( \$_POST['yby_docs_bulk_submit'] )" ) !== false, 'bulk handlers retained' );
$assert( strpos( $docs, "isset( \$_POST['yby_docs_directory_action'] )" ) !== false, 'category handlers retained' );
$assert( strpos( $docs, "isset( \$_POST['yby_docs_settings_submit'] )" ) !== false, 'settings handlers retained' );
$assert( strpos( $shell, "call_user_func( \$body );" ) !== false, 'original server controller once' );
$assert( strpos( $core, "'enqueue_styles'" ) !== false, 'native shell CSS registration retained' );
$assert( strpos( $core, "'enqueue_tertiary'" ) === false, 'old DOM mover not reinstated' );
echo "PASS andy-content-docs-l3-v2210-harness\n";
/** Run a tiny isolated PHP rendering smoke, using synthetic view templates. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$fixture_root = sys_get_temp_dir() . '/andy-l3-nav-' . getmypid() . '-' . substr( hash( 'sha256', __FILE__ ), 0, 10 ) . '/';
$view_root = $fixture_root . 'admin/views/';
if ( ! mkdir( $view_root, 0700, true ) ) { throw new RuntimeException( 'Cannot create test-only view fixture' ); }
register_shutdown_function( static function () use ( $fixture_root, $view_root ) {
    @unlink( $view_root . 'docs-os-dashboard.php' );
    @unlink( $view_root . 'content-overview.php' );
    @rmdir( $view_root );
    @rmdir( $fixture_root . 'admin/' );
    @rmdir( $fixture_root );
} );
file_put_contents( $view_root . 'docs-os-dashboard.php',
    '<?php ++$GLOBALS["docs_render_calls"]; echo "<section id=\"original-docs\">" . esc_html($data["marker"]) . "</section>";' );
file_put_contents( $view_root . 'content-overview.php',
    '<?php ++$GLOBALS["content_render_calls"]; echo "<section id=\"original-content\">" . esc_html(implode(",",array_keys($modules))) . "</section>";' );
define( 'YBY_CORE_PLUGIN_DIR', $fixture_root );
function is_admin() { return true; }
function current_user_can( $cap ) { return true; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $s, $domain = '' ) { return esc_html( $s ); }
function esc_attr__( $s, $domain = '' ) { return esc_html( $s ); }
function esc_url( $value ) { return esc_html( $value ); }
function wp_parse_url( $u, $part ) { return parse_url( $u, $part ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $s ) ); }
function wp_unslash( $s ) { return stripslashes( (string) $s ); }
class YBY_Module_Registry {
    public static function modules() { return array( 'docs_os' => array( 'admin_parent'=>'yby-content' ) ); }
    public static function is_enabled( $id ) { return true; }
}
class YBY_Andy_OS_Navigation {
    public static function tertiary_items( $page ) {
        if ( ! $GLOBALS['nav_smoke_enabled'] ) { return array(); }
        return array(
            array( 'title'=>'文档', 'url'=>'https://example.test/wp-admin/admin.php?page=yby-docs-os' ),
            array( 'title'=>'文档列表', 'url'=>'https://example.test/wp-admin/admin.php?page=yby-docs-all' ),
        );
    }
}
require $base . '/admin/class-yby-andy-admin-shell-v22.php';
require $base . '/admin/class-yby-docs-os-admin.php';
require $base . '/admin/class-yby-content-admin.php';
class DocsShellSmoke extends YBY_Docs_OS_Admin {
    public function render_smoke() {
        $this->render_workbench( 'yby-docs-os', 'Docs OS', 'docs-os-dashboard.php', array( 'marker'=>'UNCHANGED_ORIGINAL_VIEW' ) );
    }
}
$GLOBALS['nav_smoke_enabled'] = true;
$GLOBALS['docs_render_calls'] = 0;
$GLOBALS['content_render_calls'] = 0;
$_GET['page'] = 'yby-docs-os';
ob_start(); ( new DocsShellSmoke() )->render_smoke(); $html = ob_get_clean();
$assert( 1 === $GLOBALS['docs_render_calls'], 'Docs original view executes exactly once' );
$assert( 1 === substr_count( $html, 'andy-core-shell-v22__rail"'), 'one server-owned Docs L3 nav' );
$assert( 2 === substr_count( $html, 'class="andy-core-shell-v22__link"'), 'two authorized Docs tertiary links' );
$assert( strpos( $html, 'UNCHANGED_ORIGINAL_VIEW' ) !== false, 'original Docs body survives L3' );
$_GET['page'] = 'yby-content';
ob_start(); ( new YBY_Content_Admin() )->render_overview(); $overview = ob_get_clean();
$assert( 1 === $GLOBALS['content_render_calls'], 'Content legacy view executes exactly once' );
$assert( strpos( $overview, 'original-content' ) !== false && strpos( $overview, 'andy-core-shell-v22__rail' ) !== false, 'Content old deep link gets proper shell' );
$GLOBALS['nav_smoke_enabled'] = false;
$_GET['page'] = 'yby-docs-os';
ob_start(); ( new DocsShellSmoke() )->render_smoke(); $fallback = ob_get_clean();
$assert( 2 === $GLOBALS['docs_render_calls'], 'Docs fallback still executes original body once' );
$assert( strpos( $fallback, 'andy-core-shell-v22__rail' ) === false, 'no empty fake tertiary UI on disabled module' );
echo "PASS andy-content-docs-l3-server-render-smoke\n";

