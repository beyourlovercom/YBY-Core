<?php
/**
 * Bricks Builder's reusable, page-owned Inquiry modal element.
 * The existing YBY Inquiry shortcode owns field rendering and REST/Case ID.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Core_Bricks_Page_Inquiry_Element extends \Bricks\Element {
    public $category = 'general';
    public $name = 'yby-page-inquiry';
    public $icon = 'ti-email';
    public $nestable = false;

    public function get_label() {
        return __( 'YBY · Page Inquiry Popup', 'yby-core' );
    }

    public function set_control_groups() {
        $groups = array(
            'copy' => __( 'Popup copy (this page)', 'yby-core' ),
            'media' => __( 'Image (this page)', 'yby-core' ),
            'fields' => __( 'Desktop / mobile fields', 'yby-core' ),
            'visual' => __( 'Layout and color', 'yby-core' ),
            'routing' => __( 'Page CTA routing', 'yby-core' ),
        );
        foreach ( $groups as $key => $title ) {
            $this->control_groups[ $key ] = array( 'title' => $title, 'tab' => 'content' );
        }
    }

    private function control( $name, $group, $label, $type, $default = '' ) {
        $this->controls[ $name ] = array(
            'tab' => 'content', 'group' => $group, 'label' => $label,
            'type' => $type, 'default' => $default,
        );
    }

    public function set_controls() {
        $this->control( 'popup_title', 'copy', 'Popup title', 'text', 'Request a Wholesale Quote' );
        $this->control( 'popup_subtitle', 'copy', 'Subtitle', 'textarea' );
        $this->control( 'mobile_title', 'copy', 'Mobile title (optional)', 'text' );
        $this->control( 'submit_label', 'copy', 'Submit button', 'text', 'Submit Inquiry' );
        $this->control( 'mobile_submit_label', 'copy', 'Mobile submit button (optional)', 'text' );
        $this->control( 'privacy_note', 'copy', 'Privacy message', 'textarea' );
        $this->control( 'image', 'media', 'Popup image', 'image' );
        $this->control( 'image_alt', 'media', 'Image alt text', 'text' );
        $this->controls['mobile_image'] = array( 'tab' => 'content', 'group' => 'media', 'label' => 'Mobile image', 'type' => 'select', 'options' => array( 'hide' => 'Hide (recommended)', 'show' => 'Show' ), 'default' => 'hide' );
        $this->control( 'preset', 'fields', 'Server-side preset ID (default: Email required)', 'text', 'page_owned_email_required' );
        $this->control( 'desktop_fields', 'fields', 'Desktop field IDs, comma separated', 'text', 'name,email,whatsapp,country,product_interest,quantity,message' );
        $this->control( 'mobile_fields', 'fields', 'Mobile field IDs, comma separated (never empty)', 'text', 'name,email,whatsapp,country,product_interest,quantity,message' );
        $this->controls['dialog_width'] = array(
            'tab' => 'content', 'group' => 'visual', 'label' => 'Dialog max-width',
            'type' => 'select', 'options' => array(
                '760' => '760px', '900' => '900px', '960' => '960px', '1040' => '1040px'
            ), 'default' => '960',
        );
        $this->control( 'accent', 'visual', 'Button color (HEX)', 'text', '#2e7d32' );
        $this->controls['routing'] = array(
            'tab' => 'content', 'group' => 'routing', 'label' => 'Connect CTA buttons',
            'type' => 'select', 'options' => array(
                'page_all' => 'All page inquiry CTA buttons',
                'explicit' => 'Only explicit modal targets',
            ), 'default' => 'page_all',
        );
    }

    public function enqueue_scripts() {
        $url = YBY_CORE_PLUGIN_URL;
        $dir = YBY_CORE_PLUGIN_DIR;
        wp_enqueue_style(
            'yby-bricks-page-inquiry',
            $url . 'public/css/yby-bricks-page-inquiry.css',
            array(), (string) filemtime( $dir . 'public/css/yby-bricks-page-inquiry.css' )
        );
        wp_enqueue_script(
            'yby-bricks-page-inquiry',
            $url . 'public/js/yby-bricks-page-inquiry.js',
            array(), (string) filemtime( $dir . 'public/js/yby-bricks-page-inquiry.js' ), true
        );
    }

    private function get_image_url() {
        $img = isset( $this->settings['image'] ) && is_array( $this->settings['image'] )
            ? $this->settings['image'] : array();
        if ( ! empty( $img['id'] ) ) {
            $url = wp_get_attachment_image_url( absint( $img['id'] ), 'full' );
            if ( $url ) { return esc_url_raw( $url ); }
        }
        return isset( $img['url'] ) ? esc_url_raw( $img['url'] ) : '';
    }

    private function get_value( $key, $default = '' ) {
        return isset( $this->settings[ $key ] ) && is_scalar( $this->settings[ $key ] )
            ? sanitize_text_field( (string) $this->settings[ $key ] )
            : $default;
    }

    /**
     * Always expose fields required by the trusted server preset.
     * Page editors can still select optional fields per device.
     */
    private function ensure_required_fields( $value, $preset ) {
        $tokens = preg_split( '/[\\s,|]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
        $tokens = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $tokens ) ) ) );
        $required = array( 'name' );
        if ( is_array( $preset ) && 'email' === sanitize_key( $preset['validation']['contact_requirement'] ?? '' ) ) {
            $required[] = 'email';
        }
        return implode( ',', array_values( array_unique( array_merge( $required, $tokens ) ) ) );
    }

    public function render() {
        if ( ! shortcode_exists( 'yby_inquiry_modal' ) ) { return; }
        $preset_id = sanitize_key( $this->get_value( 'preset', 'page_owned_email_required' ) );
        $preset = class_exists( 'YBY_Inquiry_Preset_Manager' )
            ? ( new YBY_Inquiry_Preset_Manager() )->get_preset( $preset_id )
            : null;
        $fields = $this->get_value( 'desktop_fields', 'name,email,whatsapp,country,product_interest,quantity,message' );
        $mobile = $this->get_value( 'mobile_fields', $fields );
        if ( '' === trim( $mobile ) ) { $mobile = $fields; }
        $fields = $this->ensure_required_fields( $fields, $preset );
        $mobile = $this->ensure_required_fields( $mobile, $preset );
        $page = absint( get_the_ID() );
        $id = 'yby-page-inquiry-' . $page . '-' . sanitize_html_class( (string) $this->id );
        $id = substr( $id, 0, 85 );
        $args = array(
            'id' => $id,
            'preset' => $preset_id,
            'layout' => 'split_visual',
            'desktop_fields' => $fields,
            'mobile_fields' => $mobile,
            'title' => $this->get_value( 'popup_title', 'Request a Wholesale Quote' ),
            'mobile_title' => $this->get_value( 'mobile_title' ),
            'subtitle' => $this->get_value( 'popup_subtitle' ),
            'submit_label' => $this->get_value( 'submit_label', 'Submit Inquiry' ),
            'mobile_submit_label' => $this->get_value( 'mobile_submit_label' ),
            'privacy_note' => $this->get_value( 'privacy_note' ),
            'image' => $this->get_image_url(),
            'image_alt' => $this->get_value( 'image_alt' ),
            'class' => 'yby-page-inquiry-owned',
        );
        $parts = array();
        foreach ( $args as $key => $value ) {
            $parts[] = $key . '="' . esc_attr( $value ) . '"';
        }
        $shortcode = '[yby_inquiry_modal ' . implode( ' ', $parts ) . ']';
        $deferred = do_shortcode( $shortcode );
        if ( false === strpos( $deferred, '<!-- yby_inquiry_modal:' ) ) {
            if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
                echo '<p role="alert">' . esc_html__( 'Inquiry preset unavailable. Save a valid server-side preset first.', 'yby-core' ) . '</p>';
            }
            return;
        }
        $accent = sanitize_hex_color( $this->get_value( 'accent', '#2e7d32' ) );
        if ( ! $accent ) { $accent = '#2e7d32'; }
        $width = $this->get_value( 'dialog_width', '960' );
        if ( ! in_array( $width, array( '760', '900', '960', '1040' ), true ) ) { $width = '960'; }
        $mobile_image = $this->get_value( 'mobile_image', 'hide' );
        if ( ! in_array( $mobile_image, array( 'hide', 'show' ), true ) ) { $mobile_image = 'hide'; }
        $routing = $this->get_value( 'routing', 'page_all' );
        if ( ! in_array( $routing, array( 'page_all', 'explicit' ), true ) ) { $routing = 'page_all'; }

        $this->set_attribute( '_root', 'class', 'yby-bricks-inquiry-config' );
        echo '<div ' . $this->render_attributes( '_root' );
        echo ' data-yby-inquiry-page-module="1"';
        echo ' data-yby-modal-id="' . esc_attr( $id ) . '"';
        echo ' data-yby-cta-routing="' . esc_attr( $routing ) . '"';
        echo ' data-yby-modal-width="' . esc_attr( $width ) . '"';
        echo ' data-yby-modal-accent="' . esc_attr( $accent ) . '"';
        echo ' data-yby-mobile-image="' . esc_attr( $mobile_image ) . '">';
        if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
            echo '<p class="yby-bricks-inquiry-preview">';
            echo esc_html__( 'Page-owned inquiry modal configured: ', 'yby-core' );
            echo esc_html( $args['title'] );
            echo '</p>';
        }
        echo '</div>';
        echo $deferred; // Core shortcode only returns an HTML comment; actual dialog renders in wp_footer.
    }
}
