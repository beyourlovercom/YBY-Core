<?php
$root = dirname( __DIR__ );
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };

$core = file_get_contents( $root . '/inc/class-yby-core.php' );
$admin = file_get_contents( $root . '/admin/class-yby-admin.php' );
$view = file_get_contents( $root . '/admin/views/addons-page.php' );
$registry = file_get_contents( $root . '/inc/class-yby-addon-registry.php' );
$modules = file_get_contents( $root . '/inc/class-yby-module-registry.php' );

$assert( false !== strpos( $core, "class-yby-addon-registry.php" ), 'Addon Registry bootstrap missing' );
$assert( false !== strpos( $core, "'plugins_loaded', 'YBY_Addon_Registry', 'discover', 15" ), 'Addon discovery hook missing' );
$assert( false !== strpos( $admin, "'addons' === \$tab" ), 'Addons route missing' );
$assert( false !== strpos( $admin, "'addons' => 'Addons'" ), 'Addons tab missing' );
$assert( false !== strpos( $admin, "YBY_Addon_Registry::summary()" ), 'System Status addon projection missing' );
$assert( false !== strpos( $view, 'Andy Core Addon Registry' ) && false !== strpos( $view, '不负责安装、删除或自动启停插件' ), 'read-only Addons UI contract missing' );
$assert( false !== strpos( $modules, "'addon_registry' => 'Addon Registry'" ), 'foundation registry marker missing' );
$assert( false !== strpos( $registry, "Andy Core Addon" ) && false !== strpos( $registry, "Requires Andy Core" ), 'inactive-addon header discovery contract missing' );
$assert( false === stripos( $registry, 'woocommerce' ) && false === stripos( $registry, 'woo_' ), 'generic Addon Registry must not contain Woo-specific logic' );

echo "PASS v190-addon-admin-harness\n";
