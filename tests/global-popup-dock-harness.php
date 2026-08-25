<?php
/** Focused global popup/dock contract harness. */

define( 'ABSPATH', __DIR__ );
define( 'YBY_CORE_PLUGIN_DIR', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $v, $domain = '' ) { unset( $domain ); return $v; }
function esc_attr__( $v, $domain = '' ) { unset( $domain ); return esc_attr( $v ); }
function __( $v, $domain = '' ) { unset( $domain ); return $v; }
function esc_url_raw( $v ) { return filter_var( (string) $v, FILTER_SANITIZE_URL ); }
function wp_get_attachment_image_url( $id, $size ) { unset( $size ); return 17 === (int) $id ? 'https://example.test/popup.jpg' : ''; }
function absint( $v ) { return abs( (int) $v ); }
function apply_filters( $tag, $value ) { unset( $tag ); return ! empty( $GLOBALS['harness_exclude'] ) ? true : $value; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function wp_doing_cron() { return false; }
function is_customize_preview() { return false; }

class YBY_Inquiry_Shortcodes { public static $deferred = false; public static function has_deferred_modals() { return self::$deferred; } }
class YBY_Config { public static function get_popup_image_id() { return 17; } }
class HarnessPresetManager {
	public function get_preset( $id ) { return array( 'id' => $id, 'enabled' => true, 'label' => 'Inquiry', 'version' => '1.0', 'source_component' => 'inquiry_modal', 'fields' => array( 'name' ) ); }
	public function get_presets() { return array( 'irrigation_quick_inquiry' => array( 'enabled' => true ) ); }
}
class HarnessFieldManager { public function get_fields() { return array( 'name' => array( 'id' => 'name', 'type' => 'text', 'enabled' => true ) ); } }
class HarnessManager { public function get_preset_manager() { return new HarnessPresetManager(); } public function get_field_manager() { return new HarnessFieldManager(); } }
class HarnessRenderer { public function render_modal( $preset, $fields, $attributes ) { return '<div data-yby-inquiry-modal id="' . esc_attr( $attributes['id'] ) . '"><form></form></div>'; } }

require_once dirname( __DIR__ ) . '/inc/class-yby-global-popup-dock.php';

$ui = new YBY_Global_Popup_Dock( new HarnessManager(), new HarnessRenderer() );
$html = capture_global_popup_output( $ui );

function capture_global_popup_output( $ui ) { ob_start(); $ui->render(); return ob_get_clean(); }
function harness_assert( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } }

harness_assert( 1 === substr_count( $html, 'data-yby-global-dock' ), 'Global dock renders once.' );
harness_assert( 1 === substr_count( $html, 'data-yby-inquiry-modal' ), 'One global modal owner renders.' );
harness_assert( false !== strpos( $html, 'site_global_free_quote' ), 'Free Quote source is governed.' );
harness_assert( false !== strpos( $html, 'site_global_whatsapp' ), 'WhatsApp source is governed.' );
harness_assert( false !== strpos( $html, 'data-yby-whatsapp-link' ), 'WhatsApp uses runtime hydration hook.' );
harness_assert( false !== strpos( $html, 'yby-global-inquiry-modal' ), 'Dock hands off to canonical modal.' );

YBY_Inquiry_Shortcodes::$deferred = true;
$existing_modal_html = capture_global_popup_output( $ui );
harness_assert( 0 === substr_count( $existing_modal_html, 'data-yby-inquiry-modal' ), 'Existing compatible modal prevents a duplicate global modal.' );
harness_assert( 1 === substr_count( $existing_modal_html, 'data-yby-global-dock' ), 'Existing modal still receives one global dock.' );

$GLOBALS['harness_exclude'] = true;
harness_assert( '' === capture_global_popup_output( $ui ), 'Builder/admin exclusion prevents frontend global UI.' );
echo "PASS global-popup-dock-harness\n";
