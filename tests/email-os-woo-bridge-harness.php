<?php
define( 'ABSPATH', __DIR__ . '/' );
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function esc_url_raw( $v ) { return (string) $v; }
function absint( $v ) { return abs( (int) $v ); }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function post_type_exists( $type ) { return 'viwec_template' === $type; }
function get_posts( $args ) { return 'customer_processing_order' === ( $args['meta_value'] ?? '' ) ? array( 321 ) : array(); }
function WC() { return $GLOBALS['fake_wc']; }
class Fake_Email {
    public $id; public $title; public $description;
    private $customer; private $recipient; private $enabled; private $manual;
    public function __construct( $id, $title, $customer, $recipient, $enabled = true, $manual = false ) {
        $this->id=$id; $this->title=$title; $this->description=$title . ' description';
        $this->customer=$customer; $this->recipient=$recipient; $this->enabled=$enabled; $this->manual=$manual;
    }
    public function is_customer_email(){ return $this->customer; }
    public function get_recipient(){ return $this->recipient; }
    public function is_enabled(){ return $this->enabled; }
    public function is_manual(){ return $this->manual; }
    public function get_content_type(){ return 'text/html'; }
}
class Fake_Mailer { public function get_emails(){ return array(
    new Fake_Email( 'customer_processing_order', 'Processing order', true, '' ),
    new Fake_Email( 'new_order', 'New order', false, 'ops@example.test' )
); } }
class Fake_WC { public function mailer(){ return new Fake_Mailer(); } }
$GLOBALS['fake_wc'] = new Fake_WC();
require_once dirname( __DIR__ ) . '/inc/class-yby-email-template-registry.php';
$items = ( new YBY_Email_Template_Registry() )->discover();
$assert = static function ( $ok, $message ) { if ( ! $ok ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$processing = $items['woocommerce:customer_processing_order'] ?? array();
$new_order = $items['woocommerce:new_order'] ?? array();
$assert( 'villatheme' === ( $processing['current_editor'] ?? '' ), 'VillaTheme ownership not detected' );
$assert( 'VillaTheme Customizer' === ( $processing['editor_label'] ?? '' ), 'VillaTheme label missing' );
$assert( 'Customer' === ( $processing['recipient'] ?? '' ), 'customer recipient contract failed' );
$assert( false !== strpos( $processing['editor_url'] ?? '', 'post=321&action=edit' ), 'VillaTheme editor URL missing' );
$assert( false !== strpos( $processing['settings_url'] ?? '', 'tab=email&section=customer_processing_order' ), 'Woo settings URL missing' );
$assert( 'woocommerce' === ( $new_order['current_editor'] ?? '' ), 'Woo native fallback not detected' );
$assert( 'WooCommerce 原生' === ( $new_order['editor_label'] ?? '' ), 'Woo native label missing' );
$assert( 'ops@example.test' === ( $new_order['recipient'] ?? '' ), 'admin recipient missing' );
$assert( ( $new_order['editor_url'] ?? '' ) === ( $new_order['settings_url'] ?? '' ), 'native editor must fall back to Woo settings' );
$view = file_get_contents( dirname( __DIR__ ) . '/admin/views/email-template-settings.php' );
$assert( false !== strpos( $view, 'WooCommerce = Native Editor Bridge' ), 'P4 governance panel missing' );
$assert( false !== strpos( $view, 'Customizer 编辑' ) && false !== strpos( $view, '预览 / 测试' ), 'P4 bridge actions missing' );
$assert( false !== strpos( $view, 'Andy Core 不注册 Woo outbound runtime override hooks' ), 'runtime takeover guard missing' );
echo "PASS email-os-woo-bridge-harness\n";
