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
echo "PASS popup-field-studio-premium-compat-harness\n";
