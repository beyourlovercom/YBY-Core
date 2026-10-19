<?php
/** Scoped Update Control contract tests: no WordPress, network or external writes. */
define( 'ABSPATH', '/test/' );
define( 'YBY_CORE_PLUGIN_FILE', '/test/wp-content/plugins/yby-core/yby-core.php' );
$GLOBALS['settings'] = array();
$GLOBALS['events'] = array( 'wp_update_themes', 'wp_update_plugins', 'wp_version_check', 'wp_maybe_auto_update', 'woocommerce_cleanup_sessions', 'andy_mail_outbox' );
function get_option( $key, $default = false ) { return $GLOBALS['settings'][ $key ] ?? $default; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function plugin_basename( $file ) { return 'yby-core/yby-core.php'; }
function wp_next_scheduled( $hook ) { return in_array( $hook, $GLOBALS['events'], true ) ? 1 : false; }
function wp_clear_scheduled_hook( $hook ) { $GLOBALS['events'] = array_values( array_diff( $GLOBALS['events'], array( $hook ) ) ); return 1; }
class YBY_Updater {
	public $allowed = false;
	public function signed_background_auto_enabled() { return $this->allowed; }
}
class UC_Loader {
	public $registered = array();
	public function add_filter( $hook, $object, $method, $priority = 10, $args = 1 ) { $this->registered[ $hook ] = array( $method, $priority, $args ); }
	public function add_action( $hook, $object, $method, $priority = 10, $args = 1 ) { $this->registered[ $hook ] = array( $method, $priority, $args ); }
}
require dirname( __DIR__ ) . '/inc/class-yby-update-control.php';
$count = 0;
$assert = function ( $ok, $description ) use ( &$count ) {
	++$count;
	if ( ! $ok ) { throw new RuntimeException( 'FAIL ' . $description ); }
};
$updater = new YBY_Updater();
$control = new YBY_Update_Control( $updater );
$loader = new UC_Loader();
$control->register_hooks( $loader );
foreach ( array( 'wp_get_update_data', 'admin_head', 'auto_update_plugin', 'pre_schedule_event', 'init', 'admin_init' ) as $hook ) {
	$assert( isset( $loader->registered[ $hook ] ), 'register ' . $hook );
}
$assert( ! YBY_Update_Control::settings()['hide_plugin_notices'], 'safe default' );
$assert( array() === $control->blocked_cron_hooks(), 'no default cron cleanup' );
$assert( true === $control->guard_plugin_auto_updates( true, (object) array( 'plugin' => 'other/p.php' ) ), 'default third-party updater unchanged' );
$data = array( 'counts' => array( 'plugins' => 4, 'themes' => 2, 'wordpress' => 1, 'translations' => 1, 'total' => 8 ), 'title' => 'plugin update' );
$assert( $control->hide_plugin_update_counts( $data ) === $data, 'default badge unchanged' );
$GLOBALS['settings'][ YBY_Update_Control::OPTION ] = array( 'hide_plugin_notices' => true, 'block_plugin_auto' => true, 'prune_update_cron' => true );
$hidden = $control->hide_plugin_update_counts( $data, array( 'wordpress' => '1 WordPress Update', 'plugins' => '4 Plugin Updates', 'themes' => '2 Theme Updates' ) );
$assert( 0 === $hidden['counts']['plugins'] && 4 === $hidden['counts']['total'], 'remove plugin badges, keep core and theme alerts' );
$assert( false === strpos( $hidden['title'], 'Plugin' ), 'remove plugin notification title' );
$assert( false !== strpos( $hidden['title'], 'WordPress' ), 'keep WordPress title' );
ob_start(); $control->hide_plugin_update_rows(); $css = ob_get_clean();
$assert( false !== strpos( $css, '.plugin-update-tr' ) && false === strpos( $css, '.update-nag' ), 'plugin-only CSS' );
$assert( false === strpos( $css, '.notice-error.inline' ) && false === strpos( $css, '.notice-warning.inline' ), 'never hide general plugin errors or warnings' );
$assert( false === $control->guard_plugin_auto_updates( true, (object) array( 'plugin' => 'other/other.php' ) ), 'deny third party' );
$assert( false === $control->guard_plugin_auto_updates( true, (object) array( 'plugin' => 'yby-core/yby-core.php' ) ), 'deny unapproved signed auto' );
$assert( array( 'wp_update_themes', 'wp_update_plugins', 'wp_maybe_auto_update' ) === $control->blocked_cron_hooks(), 'exact cleanup whitelist' );
$assert( false === $control->prevent_update_cron_reschedule( null, (object) array( 'hook' => 'wp_maybe_auto_update' ) ), 'stop reschedule' );
$assert( null === $control->prevent_update_cron_reschedule( null, (object) array( 'hook' => 'woocommerce_cleanup_sessions' ) ), 'preserve Woo' );
$assert( null === $control->prevent_update_cron_reschedule( null, (object) array( 'hook' => 'andy_mail_outbox' ) ), 'preserve mail' );
$assert( true === $control->prevent_update_cron_reschedule( true, (object) array( 'hook' => 'wp_update_plugins' ) ), 'preserve previous filter' );
$control->prune_known_update_crons();
$assert( array( 'wp_version_check', 'woocommerce_cleanup_sessions', 'andy_mail_outbox' ) === $GLOBALS['events'], 'exact cron cleanup' );
$updater->allowed = true;
$assert( true === $control->guard_plugin_auto_updates( false, (object) array( 'plugin' => 'yby-core/yby-core.php' ) ), 'allow signed Andy opt-in' );
$assert( false === $control->guard_plugin_auto_updates( true, (object) array( 'plugin' => 'other/other.php' ) ), 'other plugins still blocked' );
$assert( array( 'wp_update_themes' ) === $control->blocked_cron_hooks(), 'retain signed updater hooks' );
$assert( null === $control->prevent_update_cron_reschedule( null, (object) array( 'hook' => 'wp_update_plugins' ) ), 'retain signed discovery' );
$assert( null === $control->prevent_update_cron_reschedule( null, (object) array( 'hook' => 'wp_maybe_auto_update' ) ), 'retain signed runner' );
$assert( ! in_array( 'wp_version_check', $control->blocked_cron_hooks(), true ), 'preserve core security version checking' );
echo 'PASS Andy Update Control V1 assertions=' . $count . " no_network=1 no_live_cron=1\n";
