<?php
/** V2.2 Premium popup: zero-IO renderer, preset, visual isolation and legacy fallback regression. */
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
$GLOBALS['popup_options'] = array();
function get_option( $key, $fallback = array() ) { return $GLOBALS['popup_options']; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return strip_tags( (string) $v ); }
function esc_url_raw( $v ) { return (string) $v; }
function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $v ) { return esc_attr( $v ); }
function esc_attr__( $v, $domain = '' ) { return esc_attr( $v ); }
function esc_html__( $v, $domain = '' ) { return esc_html( $v ); }
function rest_url( $path ) { return 'https://dev.example.test/wp-json/' . $path; }
class YBY_Newsletter_REST_Controller {
    public static function subscribe_ready() { return false; }
    public static function policy_version() { return 'test-only'; }
}
class Premium_Test_Presets {
    public $enabled = true;
    public function get_preset( $id ) {
        if ( ! in_array( $id, array( 'irrigation_quick_inquiry', 'bottle_oem_inquiry', 'bottle_wholesale_inquiry' ), true ) ) { return null; }
        return array(
            'id' => $id, 'label' => $id, 'enabled' => $this->enabled, 'version' => '1.0',
            'source_component' => 'inquiry_modal',
            'fields' => $id === 'bottle_oem_inquiry'
                ? array( 'name', 'company', 'contact', 'estimated_quantity' )
                : array( 'name', 'company', 'contact' ),
            'mobile_fields' => array( 'name', 'contact' )
        );
    }
}
class Premium_Test_Fields {
    public function get_fields() {
        $fields = array();
        foreach ( array( 'name', 'company', 'contact', 'estimated_quantity' ) as $id ) { $fields[ $id ] = array( 'id' => $id, 'enabled' => true ); }
        return $fields;
    }
}
class Premium_Test_Manager {
    public $presets;
    public function __construct() { $this->presets = new Premium_Test_Presets(); }
    public function get_preset_manager() { return $this->presets; }
    public function get_field_manager() { return new Premium_Test_Fields(); }
}
class Premium_Test_Renderer {
    public $captures = array();
    public function render_modal( $preset, $fields, $attributes = array(), $mobile_fields = array() ) {
        $this->captures[] = compact( 'preset', 'fields', 'attributes', 'mobile_fields' );
        return '<div id="' . esc_attr( $attributes['id'] ) . '" class="yby-inquiry-modal ' .
          esc_attr( implode( ' ', $attributes['class'] ) ) . '" data-yby-inquiry-modal data-yby-preset="' .
          esc_attr( $preset['id'] ) . '"><form data-yby-inquiry-form data-yby-source-component="' .
          esc_attr( $preset['source_component'] ) . '"></form></div>';
    }
}
require dirname( __DIR__ ) . '/inc/class-yby-global-popup.php';
$assert = static function( $ok, $label ) { if ( ! $ok ) { fwrite( STDERR, "FAIL PREMIUM V22: $label\n" ); exit( 1 ); } };
$manager = new Premium_Test_Manager();
$renderer = new Premium_Test_Renderer();
$popup = new YBY_Global_Popup( $manager, $renderer );
$render = static function() use ( $popup ) { ob_start(); $popup->render(); return ob_get_clean(); };
$classic = $render();
$first = $renderer->captures[0];
$assert( $first['preset']['id'] === 'irrigation_quick_inquiry', 'existing classic default preset' );
$assert( ! in_array( 'andy-popup-template--premium-v22', $first['attributes']['class'], true ), 'classic remains visually unchanged' );
$assert( count( $first['mobile_fields'] ) === 0, 'classic original renderer contract kept' );
$assert( strpos( $classic, 'id="yby-global-inquiry-popup"' ) !== false, 'original modal ID kept' );
$assert( strpos( $classic, 'data-yby-global-popup="inquiry"' ) !== false, 'original popup trigger contract kept' );
$assert( strpos( $classic, 'data-yby-source-component="inquiry_modal"' ) !== false, 'original source marker kept' );
$GLOBALS['popup_options'] = array( 'inquiry_template' => 'premium_v22', 'inquiry_preset' => 'bottle_oem_inquiry' );
$oem = $render();
$second = $renderer->captures[1];
$assert( $second['preset']['id'] === 'bottle_oem_inquiry', 'OEM preset retained' );
$assert( in_array( 'andy-popup-template--premium-v22', $second['attributes']['class'], true ), 'scoped Premium class' );
$assert( count( $second['mobile_fields'] ) === 2 && $second['mobile_fields'][1]['id'] === 'contact', 'OEM mobile fields use same schema' );
$assert( strpos( $oem, 'data-yby-preset="bottle_oem_inquiry"' ) !== false, 'OEM preset metadata kept' );
$GLOBALS['popup_options']['inquiry_preset'] = 'bottle_wholesale_inquiry';
$wholesale = $render();
$assert( $renderer->captures[2]['preset']['id'] === 'bottle_wholesale_inquiry', 'Wholesale independent from OEM' );
$assert( strpos( $wholesale, 'data-yby-preset="bottle_wholesale_inquiry"' ) !== false, 'Wholesale attribution retained' );
$GLOBALS['popup_options']['inquiry_preset'] = 'unknown';
$render();
$assert( $renderer->captures[3]['preset']['id'] === 'irrigation_quick_inquiry', 'unknown selection returns safe classic preset' );
$bad = YBY_Global_Popup::sanitize( array( 'inquiry_template' => array( 'premium_v22' ), 'inquiry_preset' => array( 'bottle_oem_inquiry' ) ) );
$assert( $bad['inquiry_template'] === 'classic' && $bad['inquiry_preset'] === 'irrigation_quick_inquiry', 'non-scalar option injection cannot activate premium or alter source' );
$manager->presets->enabled = false;
$assert( strpos( $render(), 'data-yby-global-popup="inquiry"' ) === false, 'disabled preset fails closed' );
$stylesheet = file_get_contents( dirname( __DIR__ ) . '/public/css/andy-core-popup-premium-v22.css' );
$assert( strpos( $stylesheet, '.yby-global-popup--inquiry.andy-popup-template--premium-v22' ) !== false, 'CSS fully scoped' );
$assert( strpos( $stylesheet, '@media (max-width: 859px)' ) !== false, 'mobile CSS regression scope' );
$public = file_get_contents( dirname( __DIR__ ) . '/public/class-yby-public.php' );
$assert( strpos( $public, "'premium_v22' === \$popup_settings['inquiry_template']" ) !== false, 'stylesheet opt-in only' );
echo "PASS andy-premium-popup-v22-harness\n";
