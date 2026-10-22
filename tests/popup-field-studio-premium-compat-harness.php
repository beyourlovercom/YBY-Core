<?php
/**
 * Verify Studio field scheme and Premium V2.2 visual scheme are orthogonal.
 * Offline test only: no WordPress, database, network or submissions.
 */
require __DIR__ . '/andy-premium-popup-v22-harness.php';

class YBY_Popup_Field_Studio {
    public static function has_preset( $id ) {
        return 'site_test_premium' === $id;
    }
    public static function desktop_fields( $id ) {
        return array( 'name', 'company', 'contact' );
    }
    public static function render_attributes( $id ) {
        return array(
            'class' => array( 'yby-popup-field-studio' ),
            'layout' => 'split_visual',
            'mobile_layout' => '',
            'title' => 'Site Quote',
            'subtitle' => 'Site-specific text',
            'image' => 'https://example.test/product.webp',
            'field_labels' => array( 'company' => 'Company' ),
            'field_required' => array( 'company' => false ),
            'mobile_field_labels' => array( 'contact' => 'Email / WhatsApp' ),
            'mobile_field_required' => array( 'contact' => true ),
        );
    }
}
class Studio_Premium_Test_Presets extends Premium_Test_Presets {
    public function get_preset( $id ) {
        if ( 'site_test_premium' !== $id ) { return parent::get_preset( $id ); }
        return array(
            'id' => $id,
            'label' => 'Site Test Premium',
            'enabled' => true,
            'version' => '2.0',
            'source_component' => 'inquiry_modal',
            'fields' => array( 'name', 'company', 'contact' ),
            'mobile_fields' => array( 'name', 'contact' ),
            'page_profiles' => array(),
        );
    }
}
$manager->presets = new Studio_Premium_Test_Presets();
$GLOBALS['popup_options'] = array(
    'inquiry_template' => 'premium_v22',
    'inquiry_preset' => 'site_test_premium',
);
$GLOBALS['test_page_profile'] = '';
$markup = $render();
$last = end( $renderer->captures );
$assert( $last['preset']['id'] === 'site_test_premium', 'site-only preset can be selected' );
$assert( in_array( 'andy-popup-template--premium-v22', $last['attributes']['class'], true ), 'Premium class survives Studio merge' );
$assert( in_array( 'yby-popup-field-studio', $last['attributes']['class'], true ), 'Studio class survives Premium merge' );
$assert( 'premium_split' === $last['attributes']['layout'], 'Premium desktop layout remains authoritative' );
$assert( 'premium_stack' === $last['attributes']['mobile_layout'], 'Premium mobile layout remains authoritative' );
$assert( count( $last['mobile_fields'] ) === 2, 'mobile fields still based on selected preset' );
$assert( strpos( $markup, 'data-yby-preset="site_test_premium"' ) !== false, 'site-specific source preset retained in popup' );
$assert( strpos( $markup, 'data-yby-global-popup="inquiry"' ) !== false, 'global popup trigger contract retained' );
$GLOBALS['popup_options']['inquiry_template'] = 'classic';
$render();
$classic_site = end( $renderer->captures );
$assert( ! in_array( 'andy-popup-template--premium-v22', $classic_site['attributes']['class'], true ), 'Classic selection does not inherit Premium class' );

/** An authenticated, nonce-bound preview bypasses Page Profile on this one
 * request only. It does not modify the live Global Popup configuration. */
function is_user_logged_in() { return ! empty( $GLOBALS['preview_user_logged_in'] ); }
function current_user_can( $capability ) { return ! empty( $GLOBALS['preview_owner'] ) && 'manage_options' === $capability; }
function wp_verify_nonce( $nonce, $action ) { return 'valid-test-nonce' === $nonce && 'yby_popup_studio_preview' === $action; }
function wp_unslash( $value ) { return $value; }
class Studio_Preview_Scoped_Presets extends Studio_Premium_Test_Presets {
    public function get_preset( $id ) {
        $preset = parent::get_preset( $id );
        if ( 'site_test_premium' === $id && is_array( $preset ) ) {
            $preset['page_profiles'] = array( 'bottle_oem' );
        }
        return $preset;
    }
}
$manager->presets = new Studio_Preview_Scoped_Presets();
$GLOBALS['preview_user_logged_in'] = true;
$GLOBALS['preview_owner'] = true;
$GLOBALS['test_page_profile'] = 'unrelated_home';
$GLOBALS['popup_options'] = array( 'inquiry_template' => 'classic', 'inquiry_preset' => 'irrigation_quick_inquiry' );
$_GET = array( 'yby_popup_preview' => 'inquiry', 'yby_studio_preview' => 'site_test_premium', 'yby_studio_nonce' => 'valid-test-nonce' );
$assert( 'site_test_premium' === YBY_Global_Popup::studio_preview_preset_id(), 'authorized site-preset preview accepted' );
$preview_markup = $render();
$preview_capture = end( $renderer->captures );
$assert( 'site_test_premium' === $preview_capture['preset']['id'], 'actual native renderer receives selected site preset' );
$assert( strpos( $preview_markup, 'data-yby-preset="site_test_premium"' ) !== false, 'site preset preview appears despite unrelated Page Profile' );
$assert( 'irrigation_quick_inquiry' === $GLOBALS['popup_options']['inquiry_preset'], 'Global Popup option unchanged after preview' );
$_GET['yby_studio_nonce'] = 'invalid-test-nonce';
$assert( '' === YBY_Global_Popup::studio_preview_preset_id(), 'invalid nonce cannot preview scoped preset' );
$GLOBALS['preview_owner'] = false;
$_GET['yby_studio_nonce'] = 'valid-test-nonce';
$assert( '' === YBY_Global_Popup::studio_preview_preset_id(), 'non-owner cannot preview scoped preset' );
$GLOBALS['preview_owner'] = true;
$_GET = array();
$GLOBALS['popup_options']['inquiry_preset'] = 'site_test_premium';
$normal_markup = $render();
$assert( strpos( $normal_markup, 'data-yby-preset="site_test_premium"' ) === false, 'normal frontend still enforces Page Profile restriction' );

echo "PASS popup-field-studio-premium-compat-harness\n";
