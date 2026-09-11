<?php
/**
 * Source contract for the scoped Brand admin runtime surface.
 */

$root = dirname( __DIR__ );
$normalize = static function ( $value ) {
	return str_replace( "\r\n", "\n", (string) $value );
};
$view   = $normalize( file_get_contents( $root . '/admin/views/brand-settings.php' ) );
$os     = $normalize( file_get_contents( $root . '/inc/class-yby-brand-os.php' ) );
$studio = $normalize( file_get_contents( $root . '/admin/class-yby-project-studio.php' ) );

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
brand_admin_assert( false !== strpos( $os, '$this->page_hook = add_submenu_page(' ), 'Brand page must capture the registered submenu hook.' );brand_admin_assert( false !== strpos( $os, 'public function page_hook()' ), 'Brand page must expose the captured submenu hook for verification.' );
brand_admin_assert( false !== strpos( $os, 'if ( $this->page_hook !== $hook_suffix )' ), 'Brand assets must use the captured submenu hook.' );
brand_admin_assert( false === strpos( $os, "YBY_Project_Studio::menu_slug() . '_page_' . self::page_slug()" ), 'Brand assets must not reconstruct the submenu hook.' );
brand_admin_assert( false !== strpos( $studio, "assets/js/yby-core-admin.js',\n\t\t\tarray( 'jquery' )" ), 'Project Studio shared admin JS must declare jquery as its dependency.' );
brand_admin_assert( false !== strpos( $os, "assets/js/yby-core-admin.js',\n\t\t\tarray( 'jquery' )" ), 'Brand shared admin JS must retain its jquery dependency.' );
brand_admin_assert( false !== strpos( file_get_contents( $root . '/assets/js/yby-core-admin.js' ), '.yby-media-button' ), 'Existing media selector handler must be reused.' );
brand_admin_assert( false !== strpos( $os, "YBY_Brand_Profile::get_theme_config()" ), 'Preview must use normalized theme runtime projection.' );
brand_admin_assert( false !== strpos( $view, 'esc_attr( $preview_style )' ), 'Preview CSS must be escaped.' );
brand_admin_assert( false === strpos( $os, "'preview" ), 'Preview must not create a stored option key.' );
brand_admin_assert( false === strpos( $os, "'schema" ), 'Brand OS must not create schema authority.' );

echo "Brand admin runtime harness passed.\n";