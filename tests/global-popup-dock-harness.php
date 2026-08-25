<?php
/** Focused global popup/dock modal-target contract harness. */

define( 'ABSPATH', __DIR__ );
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $v, $d = '' ) { unset( $d ); return $v; }
function esc_attr__( $v, $d = '' ) { unset( $d ); return esc_attr( $v ); }
function __( $v, $d = '' ) { unset( $d ); return $v; }
function esc_url_raw( $v ) { return (string) $v; }
function wp_get_attachment_image_url( $id, $size ) { unset( $size ); return 17 === (int) $id ? 'https://example.test/popup.jpg' : ''; }
function absint( $v ) { return abs( (int) $v ); }
function apply_filters( $tag, $value ) { unset( $tag ); return $value; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function is_customize_preview() { return false; }

class YBY_Inquiry_Shortcodes {
	public static $compatible_modal_id = '';
	public static function get_compatible_modal_id() { return self::$compatible_modal_id; }
}
class YBY_Config { public static function get_popup_image_id() { return 17; } }
class HarnessPresetManager {
	public function get_preset( $id ) { return array( 'id' => $id, 'enabled' => true, 'label' => 'Inquiry', 'version' => '1.0', 'source_component' => 'inquiry_modal', 'fields' => array( 'name' ) ); }
	public function get_presets() { return array( 'irrigation_quick_inquiry' => array( 'enabled' => true ) ); }
}
class HarnessFieldManager { public function get_fields() { return array( 'name' => array( 'id' => 'name', 'type' => 'text', 'enabled' => true ) ); } }
class HarnessManager { public function get_preset_manager() { return new HarnessPresetManager(); } public function get_field_manager() { return new HarnessFieldManager(); } }
class HarnessRenderer { public function render_modal( $preset, $fields, $attributes ) { return '<div data-yby-inquiry-modal id="' . esc_attr( $attributes['id'] ) . '"><form></form></div>'; } }

require_once dirname( __DIR__ ) . '/inc/class-yby-global-popup-dock.php';

function harness_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } }
function capture_global_popup_output( $ui ) { ob_start(); $ui->render(); return ob_get_clean(); }

$ui = new YBY_Global_Popup_Dock( new HarnessManager(), new HarnessRenderer() );

YBY_Inquiry_Shortcodes::$compatible_modal_id = '';
$new_modal_html = capture_global_popup_output( $ui );
harness_assert( false !== strpos( $new_modal_html, 'id="yby-global-inquiry-modal"' ), 'No existing modal creates the canonical global modal.' );
harness_assert( false !== strpos( $new_modal_html, 'data-yby-modal-open="yby-global-inquiry-modal"' ), 'Free Quote targets the created global modal.' );
harness_assert( false !== strpos( $new_modal_html, 'href="#yby-global-inquiry-modal"' ), 'Free Quote href targets the created global modal.' );
harness_assert( 1 === substr_count( $new_modal_html, 'data-yby-inquiry-modal' ), 'No-existing-modal case renders one modal.' );
harness_assert( false !== strpos( $new_modal_html, 'data-yby-whatsapp-link' ), 'WhatsApp keeps its governed hydration hook.' );

YBY_Inquiry_Shortcodes::$compatible_modal_id = 'irrigation-inquiry-global';
$existing_modal_html = capture_global_popup_output( $ui );
harness_assert( false !== strpos( $existing_modal_html, 'data-yby-modal-open="irrigation-inquiry-global"' ), 'Free Quote targets the real existing modal ID.' );
harness_assert( false !== strpos( $existing_modal_html, 'href="#irrigation-inquiry-global"' ), 'Free Quote href targets the real existing modal ID.' );
harness_assert( false === strpos( $existing_modal_html, 'yby-global-inquiry-modal' ), 'Existing-modal case does not create a duplicate global modal.' );
harness_assert( 1 === substr_count( $existing_modal_html, 'data-yby-global-dock' ), 'Existing-modal case still renders one dock.' );

echo "PASS global-popup-dock-harness\n";
