<?php
/**
 * Hostinger automatic background updater policy matrix.
 * Pure PHP isolated test: no WordPress installation, network, cron or writes.
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['hb_host'] = 'ybybottle.com';
$GLOBALS['hb_channel'] = 'disabled';
$GLOBALS['hb_auto'] = false;

function get_option( $key, $default = false ) {
	if ( 'yby_core_update_channel' === $key ) {
		return $GLOBALS['hb_channel'];
	}
	if ( 'yby_core_automatic_updates' === $key ) {
		return $GLOBALS['hb_auto'];
	}
	return $default;
}
function home_url() { return 'https://' . $GLOBALS['hb_host'] . '/'; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function plugin_basename( $file ) { return 'yby-core/yby-core.php'; }
class YBY_GitHub_Release_Client {}

class HB_Loader {
	public $filters = array();
	public function add_filter( $hook, $object, $method, $priority, $args ) {
		$this->filters[ $hook ] = array( 'callback' => array( $object, $method ), 'priority' => $priority, 'args' => $args );
	}
	public function add_action( $hook, $object, $method, $priority, $args ) {}
}
require dirname( __DIR__ ) . '/inc/class-yby-updater.php';

$updater = new YBY_Updater( '/fixture/yby-core/yby-core.php', '2.2.6' );
$loader = new HB_Loader();
$updater->register_hooks( $loader );
$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( 'HOSTINGER_BACKGROUND_GATE_FAIL: ' . $message );
	}
};
$count = 0;

// Late-priority hook must override the managed Hostinger MU filters; no
// modifications to their code or WP_AUTO_UPDATE_CORE constants are required.
foreach ( array(
	'automatic_updater_disabled' => 'background_updater_disabled',
	'auto_update_plugin' => 'automatic_updates',
	'auto_update_core' => 'background_other_update_types',
	'auto_update_theme' => 'background_other_update_types',
	'auto_update_translation' => 'background_other_update_types',
) as $hook => $method ) {
	$assert( isset( $loader->filters[ $hook ] ), 'Missing registration: ' . $hook );
	$assert( $loader->filters[ $hook ]['priority'] > 10, 'Hook priority must exceed Hostinger: ' . $hook );
	$assert( $loader->filters[ $hook ]['callback'][1] === $method, 'Wrong callback: ' . $hook );
	$count += 3;
}

$andy = (object) array( 'plugin' => 'yby-core/yby-core.php' );
$other = (object) array( 'plugin' => 'some-other-plugin/other.php' );

// Hostinger's real mu-plugin previously returned true (global disabled)
// and false for plugin update permission. This asserts the registered
// callbacks enforce one, and ONLY one, signed updater destination.
$matrix = array(
	array( 'ybybottle.com', 'stable', true, true ),
	array( 'ybyirrigation.com', 'stable', true, true ),
	array( 'ybybottle.com', 'stable', false, false ),
	array( 'ybyirrigation.com', 'stable', false, false ),
	array( 'ybybottle.com', 'disabled', true, false ),
	array( 'ybyirrigation.com', 'disabled', true, false ),
	array( 'ybybottle.com', 'canary', true, false ),
	array( 'ybyirrigation.com', 'canary', true, false ),
	array( 'dev.ybybottle.com', 'canary', true, true ),
	array( 'dev.beyourlover.com', 'canary', true, true ),
	array( 'dev.ybybottle.com', 'canary', false, false ),
	array( 'dev.ybybottle.com', 'stable', true, false ),
	array( 'www.beyourlover.com', 'stable', true, false ),
	array( 'www.beyourlover.com', 'canary', true, false ),
	array( 'unknown.example', 'stable', true, false ),
	array( 'unknown.example', 'canary', true, false ),
);
foreach ( $matrix as $case ) {
	list( $GLOBALS['hb_host'], $GLOBALS['hb_channel'], $GLOBALS['hb_auto'], $allow ) = $case;
	$label = implode( '/', array_map( 'strval', $case ) );
	$assert( $updater->background_updater_disabled( true ) === ! $allow, 'Hostinger global gate: ' . $label );
	$assert( $updater->automatic_updates( false, $andy ) === $allow, 'Andy Core opt-in: ' . $label );
	$assert( $updater->automatic_updates( false, $other ) === false, 'Other plugin from Hostinger block: ' . $label );
	$assert( $updater->automatic_updates( true, $other ) === ( $allow ? false : true ), 'Do not change other plugins on non-opted site: ' . $label );
	$assert( $updater->automatic_updates( true, null ) === ( $allow ? false : true ), 'Null item must never auto-update on opted-in site: ' . $label );
	foreach ( array( 'auto_update_core', 'auto_update_theme', 'auto_update_translation' ) as $type ) {
		$assert( $updater->background_other_update_types( true ) === ! $allow, 'Prevent unrelated update type: ' . $type . ' ' . $label );
	}
	$count += 8;
}

// A later process may already have disabled the global updater for other
// reasons. When no Andy Core opt-in exists, never override that decision.
$GLOBALS['hb_host'] = 'www.beyourlover.com';
$GLOBALS['hb_channel'] = 'disabled';
$GLOBALS['hb_auto'] = false;
$assert( false === $updater->background_updater_disabled( false ), 'Do not enable disabled sites' );
$assert( false === $updater->background_other_update_types( false ), 'Preserve restrictions outside allowed sites' );
$count += 2;

echo "PASS Hostinger background updater scoped policy assertions={$count} no_network=1 no_writes=1 no_cron=1\n";
