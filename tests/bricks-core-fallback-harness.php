<?php
/** Core may provide its own Bricks element on sites without a child-theme owner. */
namespace Bricks {
	class Element {}
	class Elements {
		public static $elements = array();
		public static function register_element( $file, $name = '', $class = '' ) {
			require_once $file;
			if ( ! class_exists( $class, false ) ) {
				throw new \RuntimeException( 'Fallback element class failed to load' );
			}
			self::$elements[ $name ] = array( 'class' => $class );
		}
	}
}
namespace {
	define( 'ABSPATH', '/isolated-test/' );
	define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
	class YBY_Module_Registry {
		public static function is_enabled( $module ) {
			return 'inquiry_os' === $module;
		}
	}
	require dirname( __DIR__ ) . '/inc/class-yby-bricks-page-inquiry-bootstrap.php';
	YBY_Bricks_Page_Inquiry_Bootstrap::register();
	YBY_Bricks_Page_Inquiry_Bootstrap::register();
	if ( 'YBY_Core_Bricks_Page_Inquiry_Element' !== ( \Bricks\Elements::$elements['yby-page-inquiry']['class'] ?? '' ) ) {
		throw new \RuntimeException( 'Core fallback registration failed' );
	}
	if ( class_exists( 'YBY_Bricks_Page_Inquiry_Element', false ) ) {
		throw new \RuntimeException( 'Core fallback must not reserve child-theme class name' );
	}
	echo "BRICKS_CORE_FALLBACK_PASS\n";
}
