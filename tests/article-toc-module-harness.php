<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }

$GLOBALS['toc_options'] = array();
$GLOBALS['toc_hooks'] = array();
$GLOBALS['toc_is_admin'] = false;
$GLOBALS['toc_is_feed'] = false;
$GLOBALS['toc_is_embed'] = false;
$GLOBALS['toc_singular'] = false;
$GLOBALS['toc_post_type'] = '';
$GLOBALS['toc_enqueued_styles'] = array();
$GLOBALS['toc_enqueued_scripts'] = array();
$GLOBALS['toc_localized'] = array();

function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) {
	if ( is_array( $value ) || is_object( $value ) ) { return ''; }
	return trim( strip_tags( (string) $value ) );
}
function absint( $value ) { return abs( (int) $value ); }
function apply_filters( $hook, $value ) { return $value; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['toc_options'] ) ? $GLOBALS['toc_options'][ $key ] : $default; }
function add_option( $key, $value, $deprecated = '', $autoload = true ) {
	if ( array_key_exists( $key, $GLOBALS['toc_options'] ) ) { return false; }
	$GLOBALS['toc_options'][ $key ] = $value;
	$GLOBALS['toc_autoload'][ $key ] = $autoload;
	return true;
}
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['toc_options'][ $key ] = $value;
	$GLOBALS['toc_autoload'][ $key ] = $autoload;
	return true;
}
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function __( $text, $domain = null ) { return $text; }
function is_admin() { return $GLOBALS['toc_is_admin']; }
function is_feed() { return $GLOBALS['toc_is_feed']; }
function is_embed() { return $GLOBALS['toc_is_embed']; }
function is_singular( $post_types = '' ) {
	if ( false === $GLOBALS['toc_singular'] ) { return false; }
	$post_types = is_array( $post_types ) ? $post_types : array( $post_types );
	return in_array( $GLOBALS['toc_post_type'], $post_types, true );
}
function get_post_type() { return $GLOBALS['toc_post_type']; }
function wp_enqueue_style( $handle, $src, $deps = array(), $version = false ) {
	$GLOBALS['toc_enqueued_styles'][ $handle ] = compact( 'src', 'deps', 'version' );
}
function wp_enqueue_script( $handle, $src, $deps = array(), $version = false, $in_footer = false ) {
	$GLOBALS['toc_enqueued_scripts'][ $handle ] = compact( 'src', 'deps', 'version', 'in_footer' );
}
function wp_localize_script( $handle, $object_name, $data ) {
	$GLOBALS['toc_localized'][ $handle ] = array( 'object_name' => $object_name, 'data' => $data );
	return true;
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['toc_hooks'][ $hook ][ (int) $priority ][] = array( $callback, (int) $accepted_args );
}
function do_action( $hook, ...$args ) {
	if ( empty( $GLOBALS['toc_hooks'][ $hook ] ) ) { return; }
	ksort( $GLOBALS['toc_hooks'][ $hook ] );
	foreach ( $GLOBALS['toc_hooks'][ $hook ] as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
		}
	}
}

class YBY_Helpers {
	public static function admin_page_slug() { return 'yby-core'; }
}

define( 'YBY_CORE_PLUGIN_URL', 'https://example.test/wp-content/plugins/yby-core/' );
define( 'YBY_CORE_VERSION', '1.7.0' );

require_once dirname( __DIR__ ) . '/inc/class-yby-module-registry.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-settings-store.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-module-runtime.php';
require_once dirname( __DIR__ ) . '/inc/class-yby-article-toc-module.php';

$assert = static function ( $ok, $message ) {
	if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
};

$assert( YBY_Article_TOC_Module::register_module(), 'Article TOC Registry V2 registration failed' );
$module = YBY_Module_Registry::module( 'article_toc' );
$assert( ! empty( $module ), 'Article TOC module missing from registry' );
$assert( '文章目录' === $module['name'], 'Article TOC display name mismatch' );
$assert( false === $module['default_enabled'], 'Article TOC must default OFF' );
$assert( 'ready' === $module['status'], 'Article TOC module status must be ready' );
$assert( 'andy_core_settings_manage' === $module['capability'], 'Article TOC capability mismatch' );
$assert( array( 'core_runtime', 'module_registry' ) === $module['dependencies'], 'Article TOC dependencies mismatch' );
$assert( 'yby_article_toc_settings_v1' === $module['storage']['option_key'], 'Article TOC storage key mismatch' );
$assert( '1' === $module['storage']['schema_version'], 'Article TOC storage schema mismatch' );
$assert( false !== strpos( YBY_Module_Registry::settings_url( 'article_toc' ), 'tab=article-toc' ), 'Article TOC settings URL missing' );

$defaults = YBY_Article_TOC_Module::get_settings();
$assert( array( 'post' ) === $defaults['post_types'], 'Article TOC V1 must default to Posts only' );
$assert( 'Summary' === $defaults['inline_title'], 'Article TOC default title mismatch' );
$assert( 2 === $defaults['min_h2_count'], 'Article TOC default minimum H2 mismatch' );
$assert( 1200 === $defaults['floating_breakpoint'], 'Article TOC default breakpoint mismatch' );
$assert( ! array_key_exists( 'yby_article_toc_settings_v1', $GLOBALS['toc_options'] ), 'Reading defaults must not write storage' );

$sanitized = YBY_Article_TOC_Module::sanitize_settings(
	array(
		'post_types' => array( 'post', 'page', 'product', 'post' ),
		'inline_title' => '  Contents <script>x</script> ',
		'min_h2_count' => 1,
		'floating_breakpoint' => 3000,
	)
);
$assert( array( 'post' ) === $sanitized['post_types'], 'Article TOC V1 must reject non-Post scopes' );
$assert( 'Contents x' === $sanitized['inline_title'], 'Article TOC title sanitizer mismatch' );
$assert( 2 === $sanitized['min_h2_count'], 'Article TOC minimum H2 clamp mismatch' );
$assert( 1920 === $sanitized['floating_breakpoint'], 'Article TOC breakpoint upper clamp mismatch' );

$assert( YBY_Article_TOC_Module::save_settings(
	array(
		'post_types' => array( 'post' ),
		'inline_title' => 'Article Summary',
		'min_h2_count' => 3,
		'floating_breakpoint' => 1280,
	)
), 'Article TOC settings save failed' );
$assert( false === $GLOBALS['toc_autoload']['yby_article_toc_settings_v1'], 'Article TOC settings must use autoload=false' );
$saved = YBY_Article_TOC_Module::get_settings();
$assert( 'Article Summary' === $saved['inline_title'] && 3 === $saved['min_h2_count'] && 1280 === $saved['floating_breakpoint'], 'Article TOC saved settings readback mismatch' );

$runtime_off = new YBY_Module_Runtime();
$runtime_off->boot_registered_extensions();
$assert( empty( $GLOBALS['toc_hooks']['wp_enqueue_scripts'] ), 'OFF Article TOC must register zero frontend asset hooks' );

$GLOBALS['toc_options'][ YBY_Module_Registry::OPTION_KEY ] = array( 'article_toc' );
$runtime_on = new YBY_Module_Runtime();
$runtime_on->boot_registered_extensions();
$assert( isset( $GLOBALS['toc_hooks']['wp_enqueue_scripts'][18] ), 'ON Article TOC must register conditional frontend asset hook' );

$GLOBALS['toc_is_admin'] = true;
$GLOBALS['toc_singular'] = true;
$GLOBALS['toc_post_type'] = 'post';
$assert( false === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Admin request must fail Article TOC asset gate' );
$GLOBALS['toc_is_admin'] = false;
$GLOBALS['toc_is_feed'] = true;
$assert( false === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Feed request must fail Article TOC asset gate' );
$GLOBALS['toc_is_feed'] = false;
$GLOBALS['toc_post_type'] = 'page';
$assert( false === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Page must fail Posts-only Article TOC asset gate' );
$GLOBALS['toc_post_type'] = 'yby_landing_page';
$assert( false === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Landing Page must fail Posts-only Article TOC asset gate' );
$GLOBALS['toc_post_type'] = 'docs';
$assert( false === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Andy Docs must fail Posts-only Article TOC asset gate' );
$GLOBALS['toc_post_type'] = 'post';
$assert( true === YBY_Article_TOC_Module::should_enqueue_assets( $module ), 'Eligible single Post must pass Article TOC asset gate' );

do_action( 'wp_enqueue_scripts' );
$assert( isset( $GLOBALS['toc_enqueued_styles']['andy-article-toc'] ), 'Eligible Post must enqueue Article TOC CSS' );
$assert( isset( $GLOBALS['toc_enqueued_scripts']['andy-article-toc'] ), 'Eligible Post must enqueue Article TOC JS' );
$assert( true === $GLOBALS['toc_enqueued_scripts']['andy-article-toc']['in_footer'], 'Article TOC JS must load in footer' );
$assert( 'AndyArticleTOCConfig' === $GLOBALS['toc_localized']['andy-article-toc']['object_name'], 'Article TOC localized config object mismatch' );
$localized = $GLOBALS['toc_localized']['andy-article-toc']['data'];
$assert( 'Article Summary' === $localized['title'] && 3 === $localized['minH2Count'] && 1280 === $localized['floatingBreakpoint'], 'Article TOC localized settings mismatch' );

echo "PASS article-toc-module-harness\n";
