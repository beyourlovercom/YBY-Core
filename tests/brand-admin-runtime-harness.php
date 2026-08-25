<?php
/**
 * Source contract for the scoped Brand admin runtime surface.
 */

$root = dirname( __DIR__ );
$view = file_get_contents( $root . '/admin/views/brand-settings.php' );
$os   = file_get_contents( $root . '/inc/class-yby-brand-os.php' );

function brand_admin_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

foreach ( array( 'logo-default', 'logo-white', 'logo-black', 'favicon', 'subscribe-image' ) as $target ) {
	brand_admin_assert( false !== strpos( $view, 'class="button yby-media-button" data-yby-media-target="yby-brand-' . $target . '"' ), 'Brand media button missing for ' . $target );
}
brand_admin_assert( false !== strpos( $os, "wp_enqueue_media();" ), 'Brand page must explicitly enqueue media.' );
brand_admin_assert( false !== strpos( $os, "assets/js/yby-core-admin.js" ), 'Brand page must enqueue existing admin JS.' );
brand_admin_assert( false !== strpos( file_get_contents( $root . '/assets/js/yby-core-admin.js' ), '.yby-media-button' ), 'Existing media selector handler must be reused.' );
brand_admin_assert( false !== strpos( $os, "YBY_Brand_Profile::get_theme_config()" ), 'Preview must use normalized theme runtime projection.' );
brand_admin_assert( false !== strpos( $view, 'esc_attr( $preview_style )' ), 'Preview CSS must be escaped.' );
brand_admin_assert( false === strpos( $os, "'preview" ), 'Preview must not create a stored option key.' );
brand_admin_assert( false === strpos( $os, "'schema" ), 'Brand OS must not create schema authority.' );

echo "Brand admin runtime harness passed.\n";
