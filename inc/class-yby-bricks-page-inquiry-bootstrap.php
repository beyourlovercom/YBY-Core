<?php
/**
 * Optional Bricks adapter for page-owned inquiry popups.
 * Bricks is not a dependency of Andy Core; this hook is a no-op without it.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Bricks_Page_Inquiry_Bootstrap {
    public static function register() {
        if ( ! class_exists( '\Bricks\\Elements' ) || ! class_exists( '\Bricks\\Element' ) ) { return; }
        if ( ! YBY_Module_Registry::is_enabled( 'inquiry_os' ) ) { return; }
        \Bricks\\Elements::register_element(
            YBY_CORE_PLUGIN_DIR . 'inc/elements/class-yby-bricks-page-inquiry-element.php',
            'yby-page-inquiry',
            'YBY_Bricks_Page_Inquiry_Element'
        );
    }
}
