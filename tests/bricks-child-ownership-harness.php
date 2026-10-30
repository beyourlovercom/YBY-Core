<?php
/** Prevent a shared plugin from loading a YBY site-owned Bricks element twice. */
namespace Bricks {
	class Element {}
	class Elements {
		public static $elements = array(
			'yby-page-inquiry' => array( 'class' => 'YBY_Bricks_Page_Inquiry_Element' ),
		);
		public static function register_element( $file, $name = '', $class = '' ) {
			throw new \RuntimeException( 'Core must not replace an existing child-theme element' );
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
	class YBY_Bricks_Page_Inquiry_Element extends \Bricks\Element {}
	require dirname( __DIR__ ) . '/inc/class-yby-bricks-page-inquiry-bootstrap.php';
	YBY_Bricks_Page_Inquiry_Bootstrap::register();
	if ( 'YBY_Bricks_Page_Inquiry_Element' !== \Bricks\Elements::$elements['yby-page-inquiry']['class'] ) {
		throw new \RuntimeException( 'Existing child theme Bricks element was replaced' );
	}
	if ( class_exists( 'YBY_Core_Bricks_Page_Inquiry_Element', false ) ) {
		throw new \RuntimeException( 'Core element unexpectedly loaded over child theme' );
	}
	echo "BRICKS_CHILD_OWNERSHIP_PASS\n";
}
