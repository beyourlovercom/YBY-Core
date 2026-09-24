<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$mode = getenv( 'ACV1_DEP_MODE' ) ?: 'ready';
$GLOBALS['hooks'] = array();
function plugin_dir_path($f){return dirname($f).'/';}
function plugin_dir_url($f){return 'https://example.test/wp-content/plugins/andy-commerce/';}
function plugin_basename($f){return 'andy-commerce/andy-commerce.php';}
function add_action($h,$cb,$p=10,$a=1){$GLOBALS['hooks'][$h][$p][]=$cb;}
function admin_url($p=''){return 'https://example.test/wp-admin/'.ltrim($p,'/');}
function current_user_can($c){return true;}
function esc_html__($v){return $v;}
function esc_html($v){return $v;}
function is_admin(){return false;}
if ( 'no-core' !== $mode ) {
	define( 'YBY_CORE_VERSION', '1.9.0' );
	class YBY_Addon_Registry { public static function register($id,$m){return true;} }
}
if ( 'no-woo' !== $mode && 'no-core' !== $mode ) { class WooCommerce {} }
require dirname( __DIR__ ) . '/andy-commerce.php';
$s = andy_commerce_dependency_status();
$ok = true;
if ( 'no-core' === $mode ) { $ok = !$s['core_loaded'] && !$s['ready']; }
elseif ( 'no-woo' === $mode ) { $ok = $s['core_loaded'] && $s['core_compatible'] && !$s['woo_loaded'] && !$s['ready']; }
else { $ok = $s['ready']; }
if ( !$ok ) { fwrite(STDERR, 'FAIL '.$mode.' '.json_encode($s)."\n"); exit(1); }
echo 'PASS dependency-matrix '.$mode.' '.json_encode($s)."\n";
