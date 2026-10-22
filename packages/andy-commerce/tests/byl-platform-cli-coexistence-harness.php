<?php
/** php this-file.php [legacy] — test both legacy active and retired CLI loading. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
define('WP_CLI', true);
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.invalid/plugins/'; }
$GLOBALS['hooks'] = array();
function add_action($name, $callback, $priority = 10) {
    $GLOBALS['hooks'][$name][] = $callback;
}
class WP_CLI {
    public static array $commands = array();
    public static function add_command($name, $callback): void {
        if (isset(self::$commands[$name])) {
            fwrite(STDERR, 'FAIL duplicate WP-CLI ' . $name . PHP_EOL);
            exit(1);
        }
        self::$commands[$name] = $callback;
    }
    public static function has_command($name): bool { return isset(self::$commands[$name]); }
}
$legacy = in_array('legacy', $argv, true);
if ($legacy) {
    eval('class BYL_CLI_Migrate_Command {}');
    WP_CLI::add_command('byl migrate', 'BYL_CLI_Migrate_Command');
}
require dirname(__DIR__) . '/andy-commerce.php';
if (count($GLOBALS['hooks']['plugins_loaded'] ?? array()) < 2) {
    fwrite(STDERR, 'FAIL plugin loaded CLI hook not queued' . PHP_EOL);
    exit(1);
}
andy_commerce_register_migration_cli();
$want = $legacy ? 'BYL_CLI_Migrate_Command' : 'Andy_Commerce_V3_CLI_Migrate_Command';
if (WP_CLI::$commands['byl migrate'] !== $want ||
    WP_CLI::$commands['andy-commerce migrate'] !== 'Andy_Commerce_V3_CLI_Migrate_Command') {
    fwrite(STDERR, 'FAIL CLI alias ownership' . PHP_EOL);
    exit(1);
}
echo 'PASS byl-platform-cli-coexistence-harness ' . ($legacy ? 'legacy' : 'retired') . PHP_EOL;
